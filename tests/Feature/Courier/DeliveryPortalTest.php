<?php

namespace Tests\Feature\Courier;

use App\Models\Address;
use App\Models\Category;
use App\Models\Courier;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_courier_login_redirects_to_its_delivery_portal(): void
    {
        $courier = $this->createCourier();

        $this->post(route('login'), [
            'email' => $courier->user->email,
            'password' => 'password',
        ])->assertRedirect(route('courier.dashboard'));

        $this->get(route('courier.dashboard'))->assertOk();
    }

    public function test_courier_sees_only_its_active_deliveries(): void
    {
        $courier = $this->createCourier();
        $otherCourier = $this->createCourier('Other Courier', 'other.courier@example.test');
        $ownDelivery = $this->createDelivery($courier, 'assigned');
        $this->createDelivery($otherCourier, 'assigned');
        $this->createDelivery($courier, 'delivered');

        $this->actingAs($courier->user)
            ->get(route('courier.dashboard'))
            ->assertOk()
            ->assertSee('Tugas Pengantaran')
            ->assertSee('Pesanan #' . $ownDelivery->order_id)
            ->assertDontSee('Pesanan #' . ($ownDelivery->order_id + 1))
            ->assertSee('Selesai');
    }

    public function test_courier_can_view_delivery_details_and_start_then_complete_task(): void
    {
        $courier = $this->createCourier();
        $delivery = $this->createDelivery($courier, 'assigned');

        $this->actingAs($courier->user)
            ->get(route('courier.deliveries.show', $delivery))
            ->assertOk()
            ->assertSee('Detail tugas')
            ->assertSee('Jl. Gunung Anyar No. 10, Surabaya')
            ->assertSee('Galon Portal Test')
            ->assertSee('Mulai pengantaran');

        $this->patch(route('courier.deliveries.status', $delivery), ['status' => 'in_transit'])
            ->assertRedirect(route('courier.deliveries.show', $delivery))
            ->assertSessionHas('success', 'Pengantaran dimulai.');

        $this->assertSame('in_transit', $delivery->fresh()->status);
        $this->assertSame('processing', $delivery->order->fresh()->status);

        $this->patch(route('courier.deliveries.status', $delivery), ['status' => 'delivered'])
            ->assertRedirect(route('courier.deliveries.show', $delivery))
            ->assertSessionHas('success', 'Pengantaran berhasil diselesaikan.');

        $this->assertSame('delivered', $delivery->fresh()->status);
        $this->assertNotNull($delivery->fresh()->delivered_at);
        $this->assertSame('delivered', $delivery->order->fresh()->status);
        $this->assertTrue($courier->fresh()->is_available);

        $this->get(route('courier.history'))
            ->assertOk()
            ->assertSee('Riwayat Pengantaran')
            ->assertSee('Pesanan #' . $delivery->order_id);
    }

    public function test_courier_remains_unavailable_while_another_delivery_is_active(): void
    {
        $courier = $this->createCourier();
        $delivery = $this->createDelivery($courier, 'in_transit');
        $this->createDelivery($courier, 'assigned');
        $delivery->order->update(['status' => 'processing']);

        $this->actingAs($courier->user)
            ->patch(route('courier.deliveries.status', $delivery), ['status' => 'delivered'])
            ->assertRedirect();

        $this->assertFalse($courier->fresh()->is_available);
    }

    public function test_courier_cannot_view_or_update_another_couriers_delivery(): void
    {
        $courier = $this->createCourier();
        $otherCourier = $this->createCourier('Other Courier', 'other.courier@example.test');
        $delivery = $this->createDelivery($otherCourier, 'assigned');

        $this->actingAs($courier->user)
            ->get(route('courier.deliveries.show', $delivery))
            ->assertNotFound();

        $this->patch(route('courier.deliveries.status', $delivery), ['status' => 'in_transit'])
            ->assertNotFound();

        $this->assertSame('assigned', $delivery->fresh()->status);
    }

    public function test_only_linked_courier_accounts_can_access_the_portal(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->get(route('courier.dashboard'))->assertRedirect(route('login'));
        $this->actingAs($customer)->get(route('courier.dashboard'))->assertForbidden();
        $this->actingAs($admin)->get(route('courier.dashboard'))->assertForbidden();
    }

    private function createCourier(string $name = 'Test Courier', string $email = 'courier@example.test'): Courier
    {
        $user = User::factory()->create([
            'name' => $name,
            'email' => $email,
            'role' => 'courier',
        ]);

        return Courier::create([
            'user_id' => $user->id,
            'name' => $name,
            'phone' => '081234567890',
            'vehicle_type' => 'Sepeda motor',
            'is_available' => false,
        ]);
    }

    private function createDelivery(Courier $courier, string $status): Delivery
    {
        $customer = User::factory()->create();
        $address = Address::create([
            'user_id' => $customer->id,
            'label' => 'Rumah',
            'full_address' => 'Jl. Gunung Anyar No. 10, Surabaya',
        ]);
        $category = Category::firstOrCreate(['slug' => 'portal-test'], ['name' => 'Portal Test']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Galon Portal Test',
            'slug' => 'galon-portal-test-' . uniqid(),
            'price' => 21000,
            'stock' => 10,
            'unit' => 'galon',
            'is_active' => true,
        ]);
        $orderStatus = match ($status) {
            'in_transit' => 'processing',
            'delivered' => 'delivered',
            default => 'confirmed',
        };
        $order = Order::create([
            'user_id' => $customer->id,
            'address_id' => $address->id,
            'status' => $orderStatus,
            'total_price' => 26000,
            'delivery_fee' => 5000,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'ordered_at' => now(),
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'price_snapshot' => 21000,
        ]);

        return Delivery::create([
            'order_id' => $order->id,
            'courier_id' => $courier->id,
            'status' => $status,
            'delivered_at' => $status === 'delivered' ? now() : null,
        ]);
    }
}
