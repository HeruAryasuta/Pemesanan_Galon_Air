<?php

namespace Tests\Feature\Admin;

use App\Models\Address;
use App\Models\Category;
use App\Models\Courier;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Product;
use App\Models\Route as DeliveryRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_preview_nearest_neighbor_route_for_selected_deliveries(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $courier = $this->createCourier();
        $first = $this->createDelivery(0.0, 0.0);
        $farther = $this->createDelivery(0.0, 1.0);
        $nearer = $this->createDelivery(0.0, 0.5);

        $response = $this->actingAs($admin)
            ->get(route('admin.routes.optimize', [
                'courier_id' => $courier->id,
                'route_date' => '2026-10-02',
                'delivery_ids' => [$first->id, $farther->id, $nearer->id],
            ]))
            ->assertOk()
            ->assertSee('Optimasi Rute')
            ->assertSee('Pratinjau rute')
            ->assertSee('bukan rute jalan raya/GPS')
            ->assertSee('Tetapkan ke Kurir')
            ->assertSee('Konfigurasi Rute')
            ->assertSee('Peta skematis')
            ->assertSee('Urutan pengantaran')
            ->assertSee('Jarak terpendek');

        $response->assertViewHas('routePlan', function ($routePlan) use ($first, $nearer, $farther): bool {
            return $routePlan->pluck('id')->all() === [$first->id, $nearer->id, $farther->id]
                && $routePlan[0]->segment_distance_meters === null
                && $routePlan[1]->segment_distance_meters > 0;
        })->assertViewHas('mapPoints', function ($mapPoints) use ($first, $nearer, $farther): bool {
            return $mapPoints->pluck('delivery_id')->all() === [$first->id, $nearer->id, $farther->id]
                && $mapPoints->every(fn (array $point): bool => $point['x'] >= 110
                    && $point['x'] <= 890
                    && $point['y'] >= 90
                    && $point['y'] <= 610);
        });
    }

    public function test_admin_can_create_route_in_the_previewed_order_and_assign_deliveries(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $courier = $this->createCourier();
        $first = $this->createDelivery(0.0, 0.0);
        $farther = $this->createDelivery(0.0, 1.0);
        $nearer = $this->createDelivery(0.0, 0.5);

        $this->actingAs($admin)
            ->post(route('admin.routes.store'), [
                'courier_id' => $courier->id,
                'route_date' => '2026-10-02',
                'delivery_ids' => [$first->id, $farther->id, $nearer->id],
            ])
            ->assertRedirect(route('admin.routes.optimize'))
            ->assertSessionHas('success', 'Rute pengantaran berhasil dibuat.');

        $route = DeliveryRoute::sole();
        $this->assertSame('2026-10-02', $route->route_date->toDateString());
        $this->assertGreaterThan(0, $route->total_distance_meters);
        $this->assertSame(
            [$first->id, $nearer->id, $farther->id],
            $route->stops->pluck('delivery_id')->all(),
        );
        $this->assertNull($route->stops->first()->distance_from_previous_meters);
        $this->assertNotNull($route->stops->last()->distance_from_previous_meters);
        $this->assertFalse($courier->fresh()->is_available);
        $this->assertSame('assigned', $first->fresh()->status);
        $this->assertSame($courier->id, $farther->fresh()->courier_id);
    }

    public function test_preview_rejects_deliveries_without_coordinates_or_that_are_not_active(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $courier = $this->createCourier();
        $delivery = $this->createDelivery(null, null);

        $this->actingAs($admin)
            ->get(route('admin.routes.optimize', [
                'courier_id' => $courier->id,
                'delivery_ids' => [$delivery->id],
            ]))
            ->assertSessionHasErrors('delivery_ids');
    }

    public function test_customer_cannot_preview_or_create_routes(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('admin.routes.optimize'))
            ->assertForbidden();

        $this->post(route('admin.routes.store'), [])
            ->assertForbidden();
    }

    private function createCourier(): Courier
    {
        return Courier::create([
            'name' => 'Siti Kurir',
            'phone' => '081234567890',
            'vehicle_type' => 'Sepeda motor',
            'is_available' => true,
        ]);
    }

    private function createDelivery(?float $latitude, ?float $longitude): Delivery
    {
        $customer = User::factory()->create();
        $address = Address::create([
            'user_id' => $customer->id,
            'label' => 'Rumah',
            'full_address' => 'Alamat '.$customer->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
        $category = Category::firstOrCreate(['slug' => 'route-test'], ['name' => 'Route Test']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Produk Rute '.$customer->id,
            'slug' => 'produk-rute-'.$customer->id,
            'price' => 20000,
            'stock' => 20,
            'unit' => 'pcs',
            'is_active' => true,
        ]);
        $order = Order::create([
            'user_id' => $customer->id,
            'address_id' => $address->id,
            'status' => 'confirmed',
            'total_price' => 20000,
            'ordered_at' => now(),
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'price_snapshot' => 20000,
        ]);

        return Delivery::create([
            'order_id' => $order->id,
            'status' => 'pending',
        ]);
    }
}
