<?php

namespace App\Support;

use App\Models\Courier;
use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CourierCapacity
{
    public function capacityFor(Courier $courier): int
    {
        return (int) config('courier.vehicle_capacities.'.$courier->vehicle_type, 0);
    }

    public function smallestVehicleTypeForLoad(int $load): ?string
    {
        $capacities = config('courier.vehicle_capacities', []);
        asort($capacities);

        foreach ($capacities as $vehicleType => $capacity) {
            if ($load <= (int) $capacity) {
                return (string) $vehicleType;
            }
        }

        return null;
    }

    public function orderLoad(Order $order): int
    {
        if ($order->relationLoaded('items')) {
            return (int) $order->items->sum('quantity');
        }

        return (int) $order->items()->sum('quantity');
    }

    public function activeLoad(Courier $courier, ?int $exceptOrderId = null): int
    {
        return (int) DB::table('order_items')
            ->join('deliveries', 'deliveries.order_id', '=', 'order_items.order_id')
            ->join('orders', 'orders.id', '=', 'deliveries.order_id')
            ->where('deliveries.courier_id', $courier->id)
            ->whereIn('deliveries.status', ['assigned', 'in_transit'])
            ->whereNotIn('orders.status', ['cancelled', 'delivered'])
            ->when($exceptOrderId, fn ($query) => $query->where('deliveries.order_id', '!=', $exceptOrderId))
            ->sum('order_items.quantity');
    }

    /**
     * @param  Collection<int, Courier>  $couriers
     * @return array<int, int>
     */
    public function activeLoads(Collection $couriers): array
    {
        if ($couriers->isEmpty()) {
            return [];
        }

        return DB::table('order_items')
            ->join('deliveries', 'deliveries.order_id', '=', 'order_items.order_id')
            ->join('orders', 'orders.id', '=', 'deliveries.order_id')
            ->whereIn('deliveries.courier_id', $couriers->modelKeys())
            ->whereIn('deliveries.status', ['assigned', 'in_transit'])
            ->whereNotIn('orders.status', ['cancelled', 'delivered'])
            ->select('deliveries.courier_id')
            ->selectRaw('SUM(order_items.quantity) as active_load')
            ->groupBy('deliveries.courier_id')
            ->pluck('active_load', 'courier_id')
            ->map(fn ($load): int => (int) $load)
            ->all();
    }

    public function fits(Courier $courier, int $additionalLoad, int $currentLoad = 0): bool
    {
        $capacity = $this->capacityFor($courier);

        return $capacity > 0 && $currentLoad + $additionalLoad <= $capacity;
    }
}
