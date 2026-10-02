<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\Delivery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function index(Request $request): View
    {
        $courier = $request->user()->courier;
        $deliveries = $courier->deliveries()
            ->with(['order.user', 'order.address', 'routeStop.route'])
            ->whereIn('status', ['assigned', 'in_transit'])
            ->whereHas('order', fn ($query) => $query->whereNotIn('status', ['cancelled', 'delivered']))
            ->latest()
            ->get();
        $completedCount = $courier->deliveries()->where('status', 'delivered')->count();

        return view('courier.deliveries.index', compact('courier', 'deliveries', 'completedCount'));
    }

    public function history(Request $request): View
    {
        $deliveries = $request->user()->courier->deliveries()
            ->with(['order.user', 'order.address'])
            ->where('status', 'delivered')
            ->latest('delivered_at')
            ->paginate(15);

        return view('courier.deliveries.history', compact('deliveries'));
    }

    public function show(Request $request, Delivery $delivery): View
    {
        $delivery = $this->ownedDelivery($request, $delivery->id, [
            'order.user',
            'order.address',
            'order.items.product',
            'routeStop.route.stops.delivery.order.address',
        ]);

        return view('courier.deliveries.show', compact('delivery'));
    }

    public function updateStatus(Request $request, Delivery $delivery): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:in_transit,delivered'],
        ]);

        DB::transaction(function () use ($request, $delivery, $validated): void {
            $courierId = $request->user()->courier->id;
            $lockedCourier = Courier::query()->whereKey($courierId)->lockForUpdate()->firstOrFail();
            $lockedDelivery = Delivery::query()
                ->whereKey($delivery->id)
                ->where('courier_id', $lockedCourier->id)
                ->lockForUpdate()
                ->firstOrFail();
            $order = $lockedDelivery->order()->lockForUpdate()->firstOrFail();

            if ($validated['status'] === 'in_transit') {
                abort_unless($lockedDelivery->status === 'assigned', 422, 'Pengantaran ini tidak dapat dimulai dari status saat ini.');
                abort_unless(! in_array($order->status, ['cancelled', 'delivered'], true), 422, 'Pesanan ini sudah ditutup.');

                $lockedDelivery->update(['status' => 'in_transit']);
                $order->update(['status' => 'processing']);

                return;
            }

            abort_unless($lockedDelivery->status === 'in_transit', 422, 'Pengantaran harus dimulai sebelum dapat diselesaikan.');
            abort_unless($order->status === 'processing', 422, 'Status pesanan tidak sesuai untuk menyelesaikan pengantaran.');

            $lockedDelivery->update([
                'status' => 'delivered',
                'delivered_at' => now(),
            ]);
            $order->update(['status' => 'delivered']);

            $hasActiveDeliveries = $lockedCourier->deliveries()
                ->whereIn('status', ['assigned', 'in_transit'])
                ->exists();

            if (! $hasActiveDeliveries) {
                $lockedCourier->update(['is_available' => true]);
            }
        });

        $message = $validated['status'] === 'delivered'
            ? 'Pengantaran berhasil diselesaikan.'
            : 'Pengantaran dimulai.';

        return redirect()->route('courier.deliveries.show', $delivery)->with('success', $message);
    }

    /**
     * @param  array<int, string>  $relations
     */
    private function ownedDelivery(Request $request, int $deliveryId, array $relations): Delivery
    {
        return $request->user()->courier->deliveries()
            ->with($relations)
            ->findOrFail($deliveryId);
    }
}
