<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\Delivery;
use App\Models\Route as DeliveryRoute;
use App\Services\Routing\OsrmTspService;
use App\Services\Routing\RouteOptimizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class RouteController extends Controller
{
    public function optimize(Request $request, OsrmTspService $osrmTspService): View
    {
        $validated = $request->validate([
            'courier_id' => ['nullable', 'integer', 'exists:couriers,id'],
            'route_date' => ['nullable', 'date'],
            'delivery_ids' => ['sometimes', 'array'],
            'delivery_ids.*' => ['integer', 'distinct', 'exists:deliveries,id'],
        ]);

        $couriers = Courier::query()
            ->where('is_available', true)
            ->orderBy('name')
            ->get();

        $deliveries = $this->eligibleDeliveries();
        $selectedIds = array_map('intval', $validated['delivery_ids'] ?? []);
        $selectedDeliveries = $deliveries->whereIn('id', $selectedIds)->values();

        if (count($selectedIds) !== $selectedDeliveries->count()) {
            throw ValidationException::withMessages([
                'delivery_ids' => 'Satu atau lebih pengantaran tidak lagi tersedia untuk dirutekan atau belum memiliki koordinat.',
            ]);
        }

        $routePlan = collect();
        $routeGeometry = [];
        $totalDistanceMeters = 0;
        $totalDurationSeconds = 0;
        $routingError = null;

        if ($selectedDeliveries->isNotEmpty()) {
            try {
                $optimizedRoute = $osrmTspService->optimize($selectedDeliveries);
                $routePlan = $optimizedRoute['deliveries'];
                $routeGeometry = $optimizedRoute['geometry'];
                $totalDistanceMeters = $optimizedRoute['distance_meters'];
                $totalDurationSeconds = $optimizedRoute['duration_seconds'];

                foreach ($routePlan as $index => $delivery) {
                    $delivery->setAttribute('segment_distance_meters', $optimizedRoute['segments'][$index]['distance_meters']);
                    $delivery->setAttribute('segment_duration_seconds', $optimizedRoute['segments'][$index]['duration_seconds']);
                }
            } catch (RouteOptimizationException $exception) {
                $routingError = $exception->getMessage();
            }
        }

        $routeMapPoints = [[
            'label' => 'D',
            'latitude' => (float) config('routing.depot.latitude'),
            'longitude' => (float) config('routing.depot.longitude'),
        ]];
        foreach ($routePlan as $index => $delivery) {
            $routeMapPoints[] = [
                'label' => (string) ($index + 1),
                'latitude' => (float) $delivery->order->address->latitude,
                'longitude' => (float) $delivery->order->address->longitude,
            ];
        }

        $missingCoordinatesCount = Delivery::query()
            ->whereIn('status', ['pending', 'assigned'])
            ->whereHas('order', fn ($query) => $query
                ->whereNotIn('status', ['cancelled', 'delivered'])
                ->where(fn ($orders) => $orders->where('payment_method', '!=', 'bank_transfer')->orWhere('payment_status', 'paid')))
            ->whereDoesntHave('order.address', function ($query): void {
                $query->whereNotNull('latitude')->whereNotNull('longitude');
            })
            ->count();

        return view('admin.routes.optimize', [
            'couriers' => $couriers,
            'deliveries' => $deliveries,
            'routePlan' => $routePlan,
            'routeMapPoints' => $routeMapPoints,
            'routeGeometry' => $routeGeometry,
            'routingError' => $routingError,
            'selectedIds' => $selectedIds,
            'selectedCourierId' => $validated['courier_id'] ?? null,
            'routeDate' => $validated['route_date'] ?? now()->toDateString(),
            'missingCoordinatesCount' => $missingCoordinatesCount,
            'totalDistanceMeters' => $totalDistanceMeters,
            'totalDurationSeconds' => $totalDurationSeconds,
        ]);
    }

    public function store(Request $request, OsrmTspService $osrmTspService): RedirectResponse
    {
        $validated = $request->validate([
            'courier_id' => ['required', 'integer', 'exists:couriers,id'],
            'route_date' => ['required', 'date'],
            'delivery_ids' => ['required', 'array', 'min:1'],
            'delivery_ids.*' => ['integer', 'distinct', 'exists:deliveries,id'],
        ]);

        try {
            $eligibleDeliveries = $this->eligibleDeliveries()
                ->whereIn('id', $validated['delivery_ids'])
                ->values();

            if ($eligibleDeliveries->count() !== count($validated['delivery_ids'])) {
                throw new RuntimeException('Sebagian pengantaran tidak dapat dimasukkan ke rute.');
            }

            $optimizedRoute = $osrmTspService->optimize($eligibleDeliveries);

            DB::transaction(function () use ($validated, $optimizedRoute): void {
                $courier = Courier::query()->whereKey($validated['courier_id'])->lockForUpdate()->firstOrFail();

                if (! $courier->is_available) {
                    throw new RuntimeException('Kurir yang dipilih sedang tidak tersedia.');
                }

                $deliveries = Delivery::query()
                    ->with('order.address')
                    ->whereIn('id', $validated['delivery_ids'])
                    ->whereIn('status', ['pending', 'assigned'])
                    ->whereHas('order', function ($query): void {
                        $query->whereNotIn('status', ['cancelled', 'delivered'])
                            ->where(fn ($orders) => $orders->where('payment_method', '!=', 'bank_transfer')->orWhere('payment_status', 'paid'));
                    })
                    ->whereHas('order.address', function ($query): void {
                        $query->whereNotNull('latitude')->whereNotNull('longitude');
                    })
                    ->lockForUpdate()
                    ->get();

                if ($deliveries->count() !== count($validated['delivery_ids'])) {
                    throw new RuntimeException('Sebagian pengantaran tidak dapat dimasukkan ke rute.');
                }

                $deliveriesById = $deliveries->keyBy('id');
                foreach ($deliveries as $delivery) {
                    $plannedDelivery = $optimizedRoute['deliveries']->firstWhere('id', $delivery->id);
                    if ($plannedDelivery === null
                        || (float) $plannedDelivery->order->address->latitude !== (float) $delivery->order->address->latitude
                        || (float) $plannedDelivery->order->address->longitude !== (float) $delivery->order->address->longitude) {
                        throw new RuntimeException('Koordinat pengantaran berubah saat rute sedang dibuat. Silakan optimalkan kembali.');
                    }
                }

                $orderedDeliveries = $optimizedRoute['deliveries']
                    ->map(fn (Delivery $delivery): ?Delivery => $deliveriesById->get($delivery->id));

                if ($orderedDeliveries->contains(null)) {
                    throw new RuntimeException('Daftar pengantaran berubah saat rute sedang dibuat. Silakan optimalkan kembali.');
                }

                $route = DeliveryRoute::query()->create([
                    'courier_id' => $courier->id,
                    'route_date' => $validated['route_date'],
                    'status' => 'planned',
                    'total_distance_meters' => $optimizedRoute['distance_meters'],
                    'total_duration_seconds' => $optimizedRoute['duration_seconds'],
                    'route_geometry' => $optimizedRoute['geometry'],
                ]);

                foreach ($orderedDeliveries as $index => $delivery) {
                    $route->stops()->create([
                        'delivery_id' => $delivery->id,
                        'visit_order' => $index + 1,
                        'distance_from_previous_meters' => $optimizedRoute['segments'][$index]['distance_meters'],
                    ]);

                    $delivery->update([
                        'courier_id' => $courier->id,
                        'status' => 'assigned',
                    ]);
                }

                $courier->update(['is_available' => false]);
            });
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->with('error', 'Gagal membuat rute pengantaran.');
        }

        return redirect()->route('admin.routes.optimize')
            ->with('success', 'Rute pengantaran berhasil dibuat.');
    }

    /**
     * @return Collection<int, Delivery>
     */
    private function eligibleDeliveries(): Collection
    {
        return Delivery::query()
            ->with(['order.address', 'order.user', 'order.items.product.category'])
            ->whereIn('status', ['pending', 'assigned'])
            ->whereHas('order', fn ($query) => $query
                ->whereNotIn('status', ['cancelled', 'delivered'])
                ->where(fn ($orders) => $orders->where('payment_method', '!=', 'bank_transfer')->orWhere('payment_status', 'paid')))
            ->whereHas('order.address', fn ($query) => $query->whereNotNull('latitude')->whereNotNull('longitude'))
            ->orderBy('id')
            ->get();
    }
}
