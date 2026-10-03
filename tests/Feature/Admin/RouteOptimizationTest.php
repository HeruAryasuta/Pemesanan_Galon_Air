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
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RouteOptimizationTest extends TestCase
{
    use RefreshDatabase;

    private bool $osrmNoRoute = false;

    private ?array $osrmDistanceMatrix = null;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'routing.depot.latitude' => 0.0,
            'routing.depot.longitude' => 0.0,
            'services.osrm.url' => 'http://osrm.test',
        ]);

        Http::fake([
            'http://osrm.test/table/v1/driving/*' => fn (HttpRequest $request) => $this->tableResponse($request),
            'http://osrm.test/route/v1/driving/*' => fn (HttpRequest $request) => $this->routeResponse($request),
        ]);
    }

    public function test_admin_can_preview_osrm_tsp_route_for_selected_deliveries(): void
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
            ->assertViewHas('routingError', null);

        $response
            ->assertSee('Optimasi Rute')
            ->assertSee('Pratinjau rute')
            ->assertSee('Rute jalan')
            ->assertSee('Tetapkan ke Kurir')
            ->assertSee('Konfigurasi Rute')
            ->assertSee('optimasi TSP pada jaringan jalan OSRM')
            ->assertSee('Urutan pengantaran')
            ->assertSee('Optimasi jarak jalan (TSP)')
            ->assertSee('Estimasi waktu jalan');

        $response->assertViewHas('routePlan', function ($routePlan) use ($first, $nearer, $farther): bool {
            return $routePlan->pluck('id')->all() === [$first->id, $nearer->id, $farther->id]
                && $routePlan[0]->segment_distance_meters === 1000
                && $routePlan[1]->segment_duration_seconds === 120;
        })->assertViewHas('routeMapPoints', function ($routeMapPoints): bool {
            return $routeMapPoints[0]['label'] === 'D'
                && $routeMapPoints[0]['latitude'] === 0.0
                && count($routeMapPoints) === 4;
        })->assertViewHas('routeGeometry', fn (array $geometry): bool => count($geometry) === 4);
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
        $this->assertSame(3000, $route->total_distance_meters);
        $this->assertSame(360, $route->total_duration_seconds);
        $this->assertCount(4, $route->route_geometry);
        $this->assertSame(
            [$first->id, $nearer->id, $farther->id],
            $route->stops->pluck('delivery_id')->all(),
        );
        $this->assertSame(1000, $route->stops->first()->distance_from_previous_meters);
        $this->assertNotNull($route->stops->last()->distance_from_previous_meters);
        $this->assertFalse($courier->fresh()->is_available);
        $this->assertSame('assigned', $first->fresh()->status);
        $this->assertSame($courier->id, $farther->fresh()->courier_id);
    }

    public function test_exact_tsp_finds_a_shorter_road_distance_than_nearest_neighbor(): void
    {
        $this->osrmDistanceMatrix = [
            [0, 1, 2, 3],
            [1, 0, 2, 2],
            [2, 2, 0, 100],
            [3, 2, 1, 0],
        ];

        $admin = User::factory()->create(['role' => 'admin']);
        $firstNearest = $this->createDelivery(0.0, 0.01);
        $secondNearest = $this->createDelivery(0.0, 0.02);
        $thirdNearest = $this->createDelivery(0.0, 0.03);

        $this->actingAs($admin)
            ->get(route('admin.routes.optimize', [
                'delivery_ids' => [$firstNearest->id, $secondNearest->id, $thirdNearest->id],
            ]))
            ->assertOk()
            ->assertViewHas('routePlan', fn ($routePlan): bool => $routePlan->pluck('id')->all() === [
                $firstNearest->id,
                $thirdNearest->id,
                $secondNearest->id,
            ]);
    }

    public function test_larger_delivery_sets_return_a_complete_heuristic_route(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $deliveries = collect(range(1, 15))
            ->map(fn (int $index): Delivery => $this->createDelivery(0.0, $index / 100));

        $this->actingAs($admin)
            ->get(route('admin.routes.optimize', [
                'delivery_ids' => $deliveries->pluck('id')->all(),
            ]))
            ->assertOk()
            ->assertViewHas('routePlan', function ($routePlan) use ($deliveries): bool {
                return $routePlan->count() === $deliveries->count()
                    && $routePlan->pluck('id')->unique()->count() === $deliveries->count()
                    && $routePlan->pluck('id')->diff($deliveries->pluck('id'))->isEmpty();
            });
    }

    public function test_preview_shows_an_error_when_osrm_cannot_find_a_route(): void
    {
        $this->osrmNoRoute = true;

        $admin = User::factory()->create(['role' => 'admin']);
        $courier = $this->createCourier();
        $delivery = $this->createDelivery(0.1, 0.1);

        $this->actingAs($admin)
            ->get(route('admin.routes.optimize', [
                'courier_id' => $courier->id,
                'delivery_ids' => [$delivery->id],
            ]))
            ->assertOk()
            ->assertSee('OSRM tidak dapat menghitung matriks jarak jalan untuk titik yang dipilih.')
            ->assertDontSee('Tetapkan ke Kurir');
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

    private function tableResponse(HttpRequest $request)
    {
        if ($this->osrmNoRoute) {
            return Http::response(['code' => 'NoTable']);
        }

        if ($this->osrmDistanceMatrix !== null) {
            return Http::response([
                'code' => 'Ok',
                'distances' => $this->osrmDistanceMatrix,
            ]);
        }

        $coordinates = $this->requestCoordinates($request, '/table/v1/driving/');
        $distanceMatrix = [];
        foreach ($coordinates as [$fromLongitude, $fromLatitude]) {
            $row = [];
            foreach ($coordinates as [$toLongitude, $toLatitude]) {
                $longitudeDelta = $toLongitude - $fromLongitude;
                $latitudeDelta = $toLatitude - $fromLatitude;
                $row[] = (int) round(sqrt($longitudeDelta ** 2 + $latitudeDelta ** 2) * 100000);
            }
            $distanceMatrix[] = $row;
        }

        return Http::response([
            'code' => 'Ok',
            'distances' => $distanceMatrix,
        ]);
    }

    private function routeResponse(HttpRequest $request)
    {
        $coordinates = $this->requestCoordinates($request, '/route/v1/driving/');
        $legCount = count($coordinates) - 1;
        $legs = array_fill(0, $legCount, ['distance' => 1000, 'duration' => 120]);

        return Http::response([
            'code' => 'Ok',
            'routes' => [[
                'distance' => 1000 * $legCount,
                'duration' => 120 * $legCount,
                'legs' => $legs,
                'geometry' => ['type' => 'LineString', 'coordinates' => $coordinates],
            ]],
        ]);
    }

    /**
     * @return array<int, array{0: float, 1: float}>
     */
    private function requestCoordinates(HttpRequest $request, string $pathPrefix): array
    {
        $path = (string) parse_url($request->url(), PHP_URL_PATH);
        $coordinatePath = substr($path, strlen($pathPrefix));

        return array_map(function (string $coordinate): array {
            [$longitude, $latitude] = array_map('floatval', explode(',', $coordinate));

            return [$longitude, $latitude];
        }, explode(';', $coordinatePath));
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
