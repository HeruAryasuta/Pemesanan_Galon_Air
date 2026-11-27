<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_upload_transfer_proof_for_their_order(): void
    {
        Storage::fake('local');
        [$customer, $order] = $this->createTransferOrder();
        $proof = $this->proofImage();

        $this->actingAs($customer)
            ->post(route('user.orders.payment-proof', $order), ['payment_proof' => $proof])
            ->assertRedirect(route('user.orders.show', $order))
            ->assertSessionHas('success', 'Bukti transfer berhasil dikirim dan menunggu verifikasi admin.');

        $order->refresh();
        $this->assertSame('pending_verification', $order->payment_status);
        $this->assertSame(1, $order->payment_proof_path ? 1 : 0);
        $this->assertNotNull($order->payment_submitted_at);
        Storage::disk('local')->assertExists($order->payment_proof_path);

        $this->get(route('user.orders.show', $order))
            ->assertOk()
            ->assertSee('Menunggu verifikasi admin');
    }

    public function test_customer_can_replace_rejected_proof_and_review_note_is_cleared(): void
    {
        Storage::fake('local');
        [$customer, $order] = $this->createTransferOrder([
            'payment_status' => 'rejected',
            'payment_review_note' => 'Gambar bukti kurang jelas.',
            'payment_reviewed_at' => now(),
        ]);

        $this->actingAs($customer)
            ->post(route('user.orders.payment-proof', $order), ['payment_proof' => $this->proofImage()])
            ->assertRedirect(route('user.orders.show', $order));

        $order->refresh();
        $this->assertSame('pending_verification', $order->payment_status);
        $this->assertNull($order->payment_review_note);
        $this->assertNull($order->payment_reviewed_at);
        $this->assertNotNull($order->payment_submitted_at);
    }

    public function test_customer_cannot_upload_proof_for_another_customers_order(): void
    {
        [$customer] = $this->createTransferOrder();
        [, $otherOrder] = $this->createTransferOrder();

        $this->actingAs($customer)
            ->post(route('user.orders.payment-proof', $otherOrder), ['payment_proof' => $this->proofImage()])
            ->assertNotFound();
    }

    public function test_customer_cannot_upload_proof_for_cod_or_when_review_is_pending(): void
    {
        [$customer, $codOrder] = $this->createTransferOrder(['payment_method' => 'cod']);
        $this->actingAs($customer)
            ->post(route('user.orders.payment-proof', $codOrder), ['payment_proof' => $this->proofImage()])
            ->assertSessionHasErrors('payment_proof');
    }

    public function test_customer_cannot_replace_proof_while_payment_is_pending_review(): void
    {
        Storage::fake('local');
        [$customer, $pendingOrder] = $this->createTransferOrder([
            'payment_status' => 'pending_verification',
            'payment_proof_path' => 'payment-proofs/previous.png',
        ]);

        $this->actingAs($customer)
            ->post(route('user.orders.payment-proof', $pendingOrder), ['payment_proof' => $this->proofImage()])
            ->assertSessionHasErrors('payment_proof');

        $this->assertSame('pending_verification', $pendingOrder->fresh()->payment_status);
    }

    public function test_customer_cannot_upload_proof_after_order_is_closed(): void
    {
        [, $pendingOrder] = $this->createTransferOrder([
            'status' => 'cancelled',
        ]);
        $this->actingAs($pendingOrder->user)
            ->post(route('user.orders.payment-proof', $pendingOrder), ['payment_proof' => $this->proofImage()])
            ->assertSessionHasErrors('payment_proof');
    }

    public function test_expired_unpaid_transfer_is_cancelled_and_stock_is_restored_once(): void
    {
        [, $order] = $this->createTransferOrder([
            'payment_deadline_at' => now()->subMinute(),
        ]);
        $product = $this->createReservedStockItem($order);

        Artisan::call('orders:expire-unpaid-bank-transfers');

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('cancelled', $order->payment_status);
        $this->assertNotNull($order->stock_restored_at);
        $this->assertSame('Batas waktu pembayaran transfer telah berakhir.', $order->cancellation_reason);
        $this->assertSame(6, $product->fresh()->stock);

        Artisan::call('orders:expire-unpaid-bank-transfers');

        $this->assertSame(6, $product->fresh()->stock);
    }

    public function test_transfer_with_proof_pending_review_is_not_expired_or_restocked(): void
    {
        [, $order] = $this->createTransferOrder([
            'payment_status' => 'pending_verification',
            'payment_deadline_at' => now()->subMinute(),
        ]);
        $product = $this->createReservedStockItem($order);

        Artisan::call('orders:expire-unpaid-bank-transfers');

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('pending_verification', $order->fresh()->payment_status);
        $this->assertNull($order->fresh()->stock_restored_at);
        $this->assertSame(4, $product->fresh()->stock);
    }

    public function test_late_proof_upload_expires_order_and_restores_stock(): void
    {
        Storage::fake('local');
        [$customer, $order] = $this->createTransferOrder([
            'payment_deadline_at' => now()->subMinute(),
        ]);
        $product = $this->createReservedStockItem($order);

        $this->actingAs($customer)
            ->post(route('user.orders.payment-proof', $order), ['payment_proof' => $this->proofImage()])
            ->assertSessionHasErrors('payment_proof');

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(6, $product->fresh()->stock);
    }

    public function test_customer_upload_is_limited_to_supported_files_and_five_megabytes(): void
    {
        Storage::fake('local');
        [$customer, $order] = $this->createTransferOrder();

        $this->actingAs($customer)
            ->post(route('user.orders.payment-proof', $order), [
                'payment_proof' => UploadedFile::fake()->create('script.txt', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors('payment_proof');

        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{User, Order}
     */
    private function createTransferOrder(array $attributes = []): array
    {
        $customer = User::factory()->create();
        $address = Address::create([
            'user_id' => $customer->id,
            'label' => 'Rumah',
            'full_address' => 'Jl. Gunung Anyar No. 10, Surabaya',
        ]);
        $order = Order::create(array_merge([
            'user_id' => $customer->id,
            'address_id' => $address->id,
            'status' => 'pending',
            'total_price' => 26000,
            'delivery_fee' => 5000,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'unpaid',
            'ordered_at' => now(),
            'payment_deadline_at' => now()->addHours((int) config('payments.bank_transfer.deadline_hours', 24)),
        ], $attributes));

        return [$customer, $order];
    }

    private function createReservedStockItem(Order $order): Product
    {
        $category = Category::create([
            'name' => 'Air Galon',
            'slug' => 'air-galon',
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Galon Aqua',
            'slug' => 'galon-aqua',
            'price' => 21000,
            'stock' => 4,
            'unit' => 'galon',
            'is_active' => true,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price_snapshot' => 21000,
        ]);

        return $product;
    }

    private function proofImage(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'transfer.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=')
        );
    }
}
