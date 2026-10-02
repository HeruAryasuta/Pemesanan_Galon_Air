<?php

namespace Tests\Feature\Admin;

use App\Models\Address;
use App\Models\Courier;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_pending_payment_queue_and_secure_proof(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        [, $pendingOrder] = $this->createTransferOrder(['payment_status' => 'pending_verification']);
        [, $unpaidOrder] = $this->createTransferOrder();
        $this->storeProof($pendingOrder);
        $this->storeProof($unpaidOrder);

        $this->actingAs($admin)
            ->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee('Verifikasi Pembayaran')
            ->assertSee(route('admin.orders.show', $pendingOrder), false)
            ->assertDontSee(route('admin.orders.show', $unpaidOrder), false);

        $this->get(route('admin.orders.show', $pendingOrder))
            ->assertOk()
            ->assertSee('Lihat bukti transfer')
            ->assertSee(route('admin.orders.payment-proof.show', $pendingOrder), false)
            ->assertSee('Terima dan verifikasi pembayaran')
            ->assertSee('Tolak bukti pembayaran');

        $this->get(route('admin.orders.payment-proof.show', $pendingOrder))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_admin_can_approve_transfer_and_then_process_order(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        [, $order] = $this->createTransferOrder(['payment_status' => 'pending_verification']);
        $this->storeProof($order);

        $this->actingAs($admin)
            ->post(route('admin.orders.review-payment', $order), [
                'decision' => 'approve',
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('success', 'Pembayaran transfer berhasil diverifikasi.');

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertNotNull($order->fresh()->payment_reviewed_at);

        $this->put(route('admin.orders.update', $order), ['status' => 'confirmed'])
            ->assertSessionHas('success', 'Status pesanan diperbarui.');
        $this->assertSame('confirmed', $order->fresh()->status);
    }

    public function test_admin_can_reject_transfer_with_note_and_customer_can_resubmit(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        [$customer, $order] = $this->createTransferOrder(['payment_status' => 'pending_verification']);
        $this->storeProof($order);

        $this->actingAs($admin)
            ->post(route('admin.orders.review-payment', $order), [
                'decision' => 'reject',
                'note' => 'Nominal pada bukti tidak sesuai.',
            ])
            ->assertSessionHas('success', 'Bukti pembayaran ditolak. Pelanggan dapat mengunggah bukti baru.');

        $this->assertSame('rejected', $order->fresh()->payment_status);
        $this->assertSame('Nominal pada bukti tidak sesuai.', $order->fresh()->payment_review_note);

        $this->actingAs($customer)
            ->get(route('user.orders.show', $order))
            ->assertOk()
            ->assertSee('Bukti ditolak — unggah ulang bukti')
            ->assertSee('Nominal pada bukti tidak sesuai.')
            ->assertSee('Unggah ulang bukti');
    }

    public function test_rejection_requires_a_note_and_only_pending_payments_can_be_reviewed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [, $order] = $this->createTransferOrder(['payment_status' => 'pending_verification']);

        $this->actingAs($admin)
            ->post(route('admin.orders.review-payment', $order), ['decision' => 'reject'])
            ->assertSessionHasErrors('note');

        $this->assertSame('pending_verification', $order->fresh()->payment_status);

        $order->update(['payment_status' => 'paid']);
        $this->post(route('admin.orders.review-payment', $order), ['decision' => 'approve'])
            ->assertSessionHasErrors('decision');
    }

    public function test_unverified_transfer_cannot_be_advanced_or_assigned(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [, $order] = $this->createTransferOrder(['payment_status' => 'pending_verification']);
        $courier = Courier::create([
            'name' => 'Kurir Uji',
            'phone' => '081234567890',
            'is_available' => true,
        ]);
        $order->delivery()->create(['status' => 'pending']);

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $order), ['status' => 'confirmed'])
            ->assertSessionHas('error', 'Pesanan transfer harus menunggu verifikasi pembayaran sebelum diproses.');

        $this->post(route('admin.orders.assign-courier', $order), ['courier_id' => $courier->id])
            ->assertSessionHas('error', 'Pesanan transfer belum dapat ditugaskan sebelum pembayaran diverifikasi.');

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertNull($order->delivery->fresh()->courier_id);
    }

    public function test_customer_cannot_access_payment_review_or_proof_file(): void
    {
        Storage::fake('local');
        $customer = User::factory()->create();
        $adminUser = User::factory()->create();
        [, $order] = $this->createTransferOrder(['payment_status' => 'pending_verification']);
        $this->storeProof($order);

        $this->actingAs($customer)
            ->get(route('admin.payments.index'))
            ->assertForbidden();

        $this->get(route('admin.orders.payment-proof.show', $order))
            ->assertForbidden();

        $this->actingAs($adminUser)
            ->post(route('admin.orders.review-payment', $order), ['decision' => 'approve'])
            ->assertForbidden();
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
        ], $attributes));

        return [$customer, $order];
    }

    private function storeProof(Order $order): void
    {
        $path = UploadedFile::fake()->createWithContent(
            'proof.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=')
        )->store('payment-proofs/' . $order->id, 'local');

        $order->update(['payment_proof_path' => $path]);
    }
}
