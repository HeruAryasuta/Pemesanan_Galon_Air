<?php

namespace App\Services;

use App\Models\Courier;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderCancellationService
{
    public function cancel(Order $order, User $actor, ?string $reason = null): void
    {
        DB::transaction(function () use ($order, $actor, $reason): void {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            $this->cancelLockedOrder($lockedOrder, $actor, $reason);
        });
    }

    public function expireUnpaidTransfer(Order $order): bool
    {
        return DB::transaction(function () use ($order): bool {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $deadline = $lockedOrder->payment_deadline_at;

            if (
                $lockedOrder->payment_method !== 'bank_transfer'
                || ! in_array($lockedOrder->payment_status, ['unpaid', 'rejected'], true)
                || ! in_array($lockedOrder->status, ['pending', 'confirmed'], true)
                || $lockedOrder->stock_restored_at !== null
                || $deadline === null
                || $deadline->isFuture()
            ) {
                return false;
            }

            $this->cancelLockedOrder(
                $lockedOrder,
                null,
                'Batas waktu pembayaran transfer telah berakhir.',
            );

            return true;
        });
    }

    public function markRefunded(Order $order, User $actor, string $reference, ?string $note = null): void
    {
        DB::transaction(function () use ($order, $actor, $reference, $note): void {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (
                $lockedOrder->status !== 'cancelled'
                || $lockedOrder->payment_status !== 'paid'
                || $lockedOrder->refund_status !== 'pending'
            ) {
                throw new RuntimeException('Pesanan ini tidak sedang menunggu pengembalian dana.');
            }

            $lockedOrder->update([
                'refund_status' => 'refunded',
                'refund_reference' => $reference,
                'refund_note' => $note,
                'refunded_at' => now(),
                'refund_processed_by' => $actor->id,
            ]);
        });
    }

    private function cancelLockedOrder(Order $lockedOrder, ?User $actor, ?string $reason): void
    {
        if (! in_array($lockedOrder->status, ['pending', 'confirmed'], true)) {
            throw new RuntimeException('Pesanan hanya dapat dibatalkan sebelum kurir mulai mengantar.');
        }
        if ($lockedOrder->stock_restored_at !== null) {
            throw new RuntimeException('Stok pesanan ini sudah pernah dipulihkan. Hubungi admin.');
        }

        $delivery = Delivery::query()->where('order_id', $lockedOrder->id)->lockForUpdate()->first();
        if ($delivery && ! in_array($delivery->status, ['pending', 'assigned'], true)) {
            throw new RuntimeException('Pengantaran sudah dimulai atau ditutup sehingga pesanan tidak dapat dibatalkan.');
        }

        $courierId = $delivery?->courier_id;
        $routeStop = $delivery
            ? RouteStop::query()->where('delivery_id', $delivery->id)->lockForUpdate()->first()
            : null;

        if ($routeStop) {
            $route = Route::query()->whereKey($routeStop->route_id)->lockForUpdate()->firstOrFail();
            if ($route->status !== 'planned') {
                throw new RuntimeException('Kurir sudah memulai rute. Pesanan tidak dapat dibatalkan dari halaman ini.');
            }
            $courierId = $courierId ?? $route->courier_id;

            $routeDeliveryIds = $route->stops()->pluck('delivery_id');
            Delivery::query()
                ->whereIn('id', $routeDeliveryIds)
                ->where('id', '!=', $delivery->id)
                ->lockForUpdate()
                ->get()
                ->each(fn (Delivery $routeDelivery) => $routeDelivery->update([
                    'courier_id' => null,
                    'status' => 'pending',
                ]));

            $route->delete();
        }

        $items = OrderItem::query()
            ->where('order_id', $lockedOrder->id)
            ->get(['product_id', 'quantity']);
        $products = Product::query()
            ->whereIn('id', $items->pluck('product_id'))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($products->count() !== $items->pluck('product_id')->unique()->count()) {
            throw new RuntimeException('Stok pesanan tidak dapat dipulihkan karena data produk tidak lengkap. Hubungi admin.');
        }

        foreach ($items as $item) {
            $products->get($item->product_id)->increment('stock', $item->quantity);
        }

        $isPaid = $lockedOrder->payment_status === 'paid';
        $lockedOrder->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => $actor?->id,
            'cancellation_reason' => $reason,
            'stock_restored_at' => now(),
            'payment_status' => $isPaid ? 'paid' : 'cancelled',
            'refund_status' => $isPaid ? 'pending' : 'not_required',
        ]);

        if ($delivery) {
            $delivery->update([
                'courier_id' => null,
                'status' => 'failed',
                'delivered_at' => null,
            ]);
        }

        if ($courierId) {
            $hasActiveDeliveries = Courier::query()
                ->find($courierId)
                ?->deliveries()
                ->whereIn('status', ['assigned', 'in_transit'])
                ->exists();

            if (! $hasActiveDeliveries) {
                Courier::query()->whereKey($courierId)->update(['is_available' => true]);
            }
        }
    }
}
