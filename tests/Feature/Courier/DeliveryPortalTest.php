<?php

namespace Tests\Feature\Courier;

use App\Models\Address;
use App\Models\Category;
use App\Models\Courier;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Product;
use App\Models\Route as DeliveryRoute;
use App\Models\RouteStop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
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
            ->assertSee('Pesanan #'.$ownDelivery->order_id)
            ->assertDontSee('Pesanan #'.($ownDelivery->order_id + 1))
            ->assertSee('Selesai');
    }

    public function test_courier_can_view_delivery_details_and_start_then_complete_task(): void
    {
        $courier = $this->createCourier();
        $delivery = $this->createDelivery($courier, 'assigned');
        $delivery->order->address->update([
            'latitude' => -7.35,
            'longitude' => 112.77,
        ]);

        $this->actingAs($courier->user)
            ->get(route('courier.deliveries.show', $delivery))
            ->assertOk()
            ->assertSee('Detail tugas')
            ->assertSee('Jl. Gunung Anyar No. 10, Surabaya')
            ->assertSee('Galon Portal Test')
            ->assertSee('Mulai pengantaran')
            ->assertSee('Tampilkan rute ke tujuan')
            ->assertSee('data-courier-route-map', false)
            ->assertSee('data-destination-latitude="-7.35"', false)
            ->assertSee('data-destination-longitude="112.77"', false);

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
            ->assertSee('Pesanan #'.$delivery->order_id);
    }

    public function test_courier_can_see_route_map_and_saved_route_stop(): void
    {
        $courier = $this->createCourier();
        $delivery = $this->createDelivery($courier, 'assigned');
        $delivery->order->address->update([
            'latitude' => -7.35,
            'longitude' => 112.77,
        ]);
        $route = DeliveryRoute::create([
            'courier_id' => $courier->id,
            'route_date' => now()->toDateString(),
            'status' => 'planned',
            'total_distance_meters' => 1500,
            'total_duration_seconds' => 300,
            'route_geometry' => [[112.7678, -7.3415], [112.77, -7.35]],
        ]);
        RouteStop::create([
            'route_id' => $route->id,
            'delivery_id' => $delivery->id,
            'visit_order' => 1,
            'distance_from_previous_meters' => 1500,
        ]);

        $this->actingAs($courier->user)
            ->get(route('courier.deliveries.show', $delivery))
            ->assertOk()
            ->assertSee('data-courier-route-map', false)
            ->assertSee(route('courier.deliveries.route-from-location', $delivery), false)
            ->assertSee('Rute dari lokasi kurir')
            ->assertSee('Urutan rute');
    }

    public function test_courier_can_request_a_road_route_from_current_location_to_their_delivery(): void
    {
        config(['services.osrm.url' => 'http://osrm.test']);
        Http::fake([
            'http://osrm.test/route/v1/driving/*' => function (HttpRequest $request) {
                $legCount = 1;

                return Http::response([
                    'code' => 'Ok',
                    'routes' => [[
                        'distance' => 2345,
                        'duration' => 456,
                        'geometry' => [
                            'type' => 'LineString',
                            'coordinates' => [[112.75, -7.32], [112.76, -7.34], [112.77, -7.35]],
                        ],
                        'legs' => array_fill(0, $legCount, ['distance' => 2345, 'duration' => 456]),
                    ]],
                ]);
            },
        ]);

        $courier = $this->createCourier();
        $delivery = $this->createDelivery($courier, 'assigned');
        $delivery->order->address->update([
            'latitude' => -7.35,
            'longitude' => 112.77,
        ]);

        $this->actingAs($courier->user)
            ->postJson(route('courier.deliveries.route-from-location', $delivery), [
                'latitude' => -7.32,
                'longitude' => 112.75,
            ])
            ->assertOk()
            ->assertJsonPath('distance_meters', 2345)
            ->assertJsonPath('duration_seconds', 456)
            ->assertJsonPath('geometry.0', [112.75, -7.32]);

        Http::assertSent(function (HttpRequest $request): bool {
            $path = rawurldecode((string) parse_url($request->url(), PHP_URL_PATH));

            return str_starts_with($request->url(), 'http://osrm.test/route/v1/driving/')
                && str_ends_with($path, '112.7500000,-7.3200000;112.7700000,-7.3500000');
        });
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

        $this->postJson(route('courier.deliveries.route-from-location', $delivery), [
            'latitude' => -7.32,
            'longitude' => 112.75,
        ])->assertNotFound();

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
            'slug' => 'galon-portal-test-'.uniqid(),
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
