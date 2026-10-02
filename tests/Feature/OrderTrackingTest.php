<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Courier;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_open_order_detail_and_track_pending_status(): void
    {
        [$user, $order] = $this->createOrder([
            'ordered_at' => Carbon::parse('2026-09-29 17:03:00', 'UTC'),
        ]);

        $this->actingAs($user)
            ->get(route('user.orders.show', $order))
            ->assertOk()
            ->assertSee('Pelacakan pesanan')
            ->assertSee('30 Sep 2026, 00:03')
            ->assertSee('Menunggu konfirmasi')
            ->assertSee('Pesanan diterima')
            ->assertSee('Alamat pengantaran')
            ->assertSee('Jl. Gunung Anyar No. 10, Surabaya')
            ->assertSee('Galon Aqua')
            ->assertSee('Biaya pengantaran')
            ->assertSee('Rp5.000')
            ->assertSee('Rp47.000')
            ->assertSee('Metode pembayaran')
            ->assertSee('COD — Bayar saat diterima')
            ->assertSee('Belum dibayar');
    }

    public function test_customer_sees_assigned_courier_and_route_eta(): void
    {
        [$user, $order] = $this->createOrder(['status' => 'processing']);
        $courier = Courier::create([
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
            'vehicle_type' => 'Sepeda motor',
            'is_available' => false,
        ]);
        $delivery = $order->delivery;
        $delivery->update([
            'courier_id' => $courier->id,
            'status' => 'in_transit',
        ]);
        $route = Route::create([
            'courier_id' => $courier->id,
            'route_date' => now()->toDateString(),
            'status' => 'in_progress',
        ]);
        $eta = now()->addHour()->startOfMinute();
        RouteStop::create([
            'route_id' => $route->id,
            'delivery_id' => $delivery->id,
            'visit_order' => 1,
            'eta' => $eta,
        ]);

        $this->actingAs($user)
            ->get(route('user.orders.show', $order))
            ->assertOk()
            ->assertSee('Dalam pengantaran')
            ->assertSee('Kurir pengantaran')
            ->assertSee('Budi Santoso')
            ->assertSee('Sepeda motor')
            ->assertSee('tel:081234567890')
            ->assertSee($eta->timezone(config('app.display_timezone'))->format('d M Y, H:i'));
    }

    public function test_cancelled_order_shows_cancelled_tracking_state(): void
    {
        [$user, $order] = $this->createOrder(['status' => 'cancelled']);
        $order->delivery->update(['status' => 'failed']);

        $this->actingAs($user)
            ->get(route('user.orders.show', $order))
            ->assertOk()
            ->assertSee('Pesanan dibatalkan')
            ->assertSee('Pengantaran ditandai gagal.')
            ->assertDontSee('Tahapan pesanan');
    }

    public function test_customer_cannot_view_another_users_order(): void
    {
        [, $order] = $this->createOrder();

        $this->actingAs(User::factory()->create())
            ->get(route('user.orders.show', $order))
            ->assertForbidden();
    }

    public function test_order_tracking_requires_authentication(): void
    {
        [, $order] = $this->createOrder();

        $this->get(route('user.orders.show', $order))
            ->assertRedirect(route('login'));
    }

    /**
     * @param array<string, mixed> $orderAttributes
     * @return array{User, Order}
     */
    private function createOrder(array $orderAttributes = []): array
    {
        $user = User::factory()->create();
        $address = $user->addresses()->create([
            'label' => 'Rumah',
            'full_address' => 'Jl. Gunung Anyar No. 10, Surabaya',
        ]);
        $category = Category::create([
            'name' => 'Air Galon',
            'slug' => 'air-galon',
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Galon Aqua',
            'slug' => 'galon-aqua',
            'price' => 21000,
            'stock' => 8,
            'unit' => 'galon',
            'is_active' => true,
        ]);
        $order = $user->orders()->create(array_merge([
            'address_id' => $address->id,
            'status' => 'pending',
            'total_price' => 47000,
            'delivery_fee' => 5000,
            'payment_method' => 'cod',
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

        return [$user, $order];
    }
}
