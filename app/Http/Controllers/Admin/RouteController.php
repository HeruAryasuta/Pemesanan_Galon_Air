<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\Delivery;
use App\Models\Route as DeliveryRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RouteController extends Controller
{
    public function optimize(Request $request): View
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

        $routePlan = $this->optimizeOrder($selectedDeliveries);
        foreach ($this->segmentDistances($routePlan) as $index => $distance) {
            $routePlan[$index]->setAttribute('segment_distance_meters', $distance);
        }
        $mapPoints = $this->mapPoints($routePlan);
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
            'mapPoints' => $mapPoints,
            'selectedIds' => $selectedIds,
            'selectedCourierId' => $validated['courier_id'] ?? null,
            'routeDate' => $validated['route_date'] ?? now()->toDateString(),
            'missingCoordinatesCount' => $missingCoordinatesCount,
            'totalDistanceMeters' => $this->totalDistance($routePlan),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'courier_id' => ['required', 'integer', 'exists:couriers,id'],
            'route_date' => ['required', 'date'],
            'delivery_ids' => ['required', 'array', 'min:1'],
            'delivery_ids.*' => ['integer', 'distinct', 'exists:deliveries,id'],
        ]);

        try {
            DB::transaction(function () use ($validated): void {
                $courier = Courier::whereKey($validated['courier_id'])->lockForUpdate()->firstOrFail();

                if (! $courier->is_available) {
                    throw new \RuntimeException('Kurir yang dipilih sedang tidak tersedia.');
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
                    throw new \RuntimeException('Sebagian pengantaran tidak dapat dimasukkan ke rute.');
                }

                $orderedDeliveries = $this->optimizeOrder($deliveries);
                $distances = $this->segmentDistances($orderedDeliveries);
                $route = DeliveryRoute::create([
                    'courier_id' => $courier->id,
                    'route_date' => $validated['route_date'],
                    'status' => 'planned',
                    'total_distance_meters' => array_sum($distances),
                ]);

                foreach ($orderedDeliveries as $index => $delivery) {
                    $route->stops()->create([
                        'delivery_id' => $delivery->id,
                        'visit_order' => $index + 1,
                        'distance_from_previous_meters' => $distances[$index] ?? null,
                    ]);

                    $delivery->update([
                        'courier_id' => $courier->id,
                        'status' => 'assigned',
                    ]);
                }

                $courier->update(['is_available' => false]);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

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

    /**
     * Use a deterministic nearest-neighbor ordering, starting from the lowest delivery ID.
     *
     * @param  Collection<int, Delivery>  $deliveries
     * @return Collection<int, Delivery>
     */
    private function optimizeOrder(Collection $deliveries): Collection
    {
        if ($deliveries->count() < 2) {
            return $deliveries->values();
        }

        $remaining = $deliveries->sortBy('id')->values();
        $ordered = collect([$remaining->shift()]);

        while ($remaining->isNotEmpty()) {
            /** @var Delivery $last */
            $last = $ordered->last();
            $next = $remaining->sortBy(fn (Delivery $candidate): float => $this->distanceMeters(
                (float) $last->order->address->latitude,
                (float) $last->order->address->longitude,
                (float) $candidate->order->address->latitude,
                (float) $candidate->order->address->longitude,
            ))->first();

            $ordered->push($next);
            $remaining = $remaining->reject(fn (Delivery $delivery): bool => $delivery->is($next))->values();
        }

        return $ordered;
    }

    /**
     * @param  Collection<int, Delivery>  $deliveries
     * @return array<int, int|null>
     */
    private function segmentDistances(Collection $deliveries): array
    {
        $distances = [];

        foreach ($deliveries as $index => $delivery) {
            if ($index === 0) {
                $distances[] = null;

                continue;
            }

            /** @var Delivery $previous */
            $previous = $deliveries[$index - 1];
            $distances[] = (int) round($this->distanceMeters(
                (float) $previous->order->address->latitude,
                (float) $previous->order->address->longitude,
                (float) $delivery->order->address->latitude,
                (float) $delivery->order->address->longitude,
            ));
        }

        return $distances;
    }

    /**
     * @param  Collection<int, Delivery>  $deliveries
     */
    private function totalDistance(Collection $deliveries): int
    {
        return (int) array_sum($this->segmentDistances($deliveries));
    }

    /**
     * @param  Collection<int, Delivery>  $deliveries
     * @return Collection<int, array{delivery_id: int, x: float, y: float}>
     */
    private function mapPoints(Collection $deliveries): Collection
    {
        if ($deliveries->isEmpty()) {
            return collect();
        }

        $latitudes = $deliveries->map(fn (Delivery $delivery): float => (float) $delivery->order->address->latitude);
        $longitudes = $deliveries->map(fn (Delivery $delivery): float => (float) $delivery->order->address->longitude);
        $minLatitude = $latitudes->min();
        $maxLatitude = $latitudes->max();
        $minLongitude = $longitudes->min();
        $maxLongitude = $longitudes->max();

        return $deliveries->map(function (Delivery $delivery) use (
            $minLatitude,
            $maxLatitude,
            $minLongitude,
            $maxLongitude,
        ): array {
            $latitudeRange = $maxLatitude - $minLatitude;
            $longitudeRange = $maxLongitude - $minLongitude;
            $latitudePosition = $latitudeRange === 0.0
                ? 0.5
                : (((float) $delivery->order->address->latitude - $minLatitude) / $latitudeRange);
            $longitudePosition = $longitudeRange === 0.0
                ? 0.5
                : (((float) $delivery->order->address->longitude - $minLongitude) / $longitudeRange);

            return [
                'delivery_id' => $delivery->id,
                'x' => 110 + (780 * $longitudePosition),
                'y' => 90 + (520 * (1 - $latitudePosition)),
            ];
        })->values();
    }

    private function distanceMeters(float $latitudeA, float $longitudeA, float $latitudeB, float $longitudeB): float
    {
        $earthRadiusMeters = 6_371_000;
        $latitudeDelta = deg2rad($latitudeB - $latitudeA);
        $longitudeDelta = deg2rad($longitudeB - $longitudeA);
        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($latitudeA)) * cos(deg2rad($latitudeB)) * sin($longitudeDelta / 2) ** 2;

        return 2 * $earthRadiusMeters * atan2(sqrt($a), sqrt(1 - $a));
    }
}
