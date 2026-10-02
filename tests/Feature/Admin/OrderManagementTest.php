<?php

namespace Tests\Feature\Admin;

use App\Models\Address;
use App\Models\Category;
use App\Models\Courier;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_order_queue_and_order_controls(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [, $order] = $this->createOrder();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Ringkasan Hari Ini')
            ->assertSee('Total Pesanan')
            ->assertSee('<svg class="h-5 w-5"', false)
            ->assertSee('<svg class="h-4 w-4"', false)
            ->assertSee('aria-label="Menu admin"', false)
            ->assertSee('Pesanan Hari Ini')
            ->assertSee('Pendapatan Diterima')
            ->assertSee('Pelanggan Aktif')
            ->assertSee('Kurir Tersedia')
            ->assertSee('Tren Pesanan (7 Hari)')
            ->assertSee('Status Pesanan')
            ->assertSee('Pesanan Terbaru')
            ->assertSee('Kembali ke website')
            ->assertSee(route('user.home'), false)
            ->assertSee('Galon Aqua');

        $this->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('Kelola Pesanan')
            ->assertSee('Belum dibayar')
            ->assertSee(route('admin.orders.show', $order), false);

        $this->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Perbarui status pesanan')
            ->assertSee('Tugaskan kurir')
            ->assertSee('COD')
            ->assertSee('Pembayaran COD dapat dicatat lunas setelah pesanan selesai diantar.');
    }

    public function test_admin_can_search_orders_by_order_id_or_customer_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$customer, $order] = $this->createOrder();
        [, $otherOrder] = $this->createOrder();

        $this->actingAs($admin)
            ->get(route('admin.orders.index', ['search' => $order->id]))
            ->assertOk()
            ->assertSee(route('admin.orders.show', $order), false)
            ->assertDontSee(route('admin.orders.show', $otherOrder), false);

        $this->get(route('admin.orders.index', ['search' => $customer->name]))
            ->assertOk()
            ->assertSee(route('admin.orders.show', $order), false)
            ->assertDontSee(route('admin.orders.show', $otherOrder), false);
    }

    public function test_admin_can_update_order_status_and_release_assigned_courier(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [, $order] = $this->createOrder();
        $courier = Courier::create([
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
            'vehicle_type' => 'Sepeda motor',
            'is_available' => false,
        ]);
        $order->delivery->update([
            'courier_id' => $courier->id,
            'status' => 'in_transit',
        ]);
        $order->update(['status' => 'processing']);

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $order), ['status' => 'delivered'])
            ->assertRedirect()
            ->assertSessionHas('success', 'Status pesanan diperbarui.');

        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertSame('delivered', $order->delivery->fresh()->status);
        $this->assertNotNull($order->delivery->fresh()->delivered_at);
        $this->assertTrue($courier->fresh()->is_available);
    }

    public function test_admin_can_assign_an_available_courier(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [, $order] = $this->createOrder();
        $courier = Courier::create([
            'name' => 'Siti Aminah',
            'phone' => '081234567891',
            'vehicle_type' => 'Sepeda motor',
            'is_available' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.orders.assign-courier', $order), ['courier_id' => $courier->id])
            ->assertRedirect()
            ->assertSessionHas('success', 'Kurir berhasil ditugaskan.');

        $this->assertSame($courier->id, $order->delivery->fresh()->courier_id);
        $this->assertSame('assigned', $order->delivery->fresh()->status);
        $this->assertFalse($courier->fresh()->is_available);
    }

    public function test_cod_payment_cannot_be_marked_paid_before_delivery(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [, $order] = $this->createOrder();

        $this->actingAs($admin)
            ->post(route('admin.orders.mark-paid', $order))
            ->assertRedirect()
            ->assertSessionHas('error', 'Pembayaran COD hanya dapat dicatat setelah pesanan selesai diantar.');

        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_admin_can_mark_delivered_cod_order_as_paid_once(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [, $order] = $this->createOrder(['status' => 'delivered']);
        $order->delivery->update(['status' => 'delivered']);

        $this->actingAs($admin)
            ->post(route('admin.orders.mark-paid', $order))
            ->assertRedirect()
            ->assertSessionHas('success', 'Pembayaran COD berhasil dicatat sebagai lunas.');

        $this->assertSame('paid', $order->fresh()->payment_status);

        $this->post(route('admin.orders.mark-paid', $order))
            ->assertRedirect()
            ->assertSessionHas('error', 'Pembayaran pesanan ini sudah tercatat lunas.');
    }

    public function test_customer_cannot_access_admin_order_controls(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('admin.orders.index'))
            ->assertForbidden();
    }

    /**
     * @param array<string, mixed> $orderAttributes
     * @return array{User, Order}
     */
    private function createOrder(array $orderAttributes = []): array
    {
        $customer = User::factory()->create();
        $address = Address::create([
            'user_id' => $customer->id,
            'label' => 'Rumah',
            'full_address' => 'Jl. Gunung Anyar No. 10, Surabaya',
            'is_primary' => true,
        ]);
        $category = Category::firstOrCreate(['slug' => 'air-galon'], [
            'name' => 'Air Galon',
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Galon Aqua',
            'slug' => 'galon-aqua-' . $customer->id,
            'price' => 21000,
            'stock' => 8,
            'unit' => 'galon',
            'is_active' => true,
        ]);
        $order = Order::create(array_merge([
            'user_id' => $customer->id,
            'address_id' => $address->id,
            'status' => 'pending',
            'total_price' => 47000,
            'delivery_fee' => 5000,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'notes' => 'Hubungi saat tiba.',
            'ordered_at' => now(),
        ], $orderAttributes));
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price_snapshot' => 21000,
        ]);
        Delivery::create([
            'order_id' => $order->id,
            'status' => 'pending',
        ]);

        return [$customer, $order];
    }
}
