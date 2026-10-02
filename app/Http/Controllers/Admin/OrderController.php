<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with(['user', 'delivery'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->string('search'));
                $query->where(function ($orders) use ($search): void {
                    if (ctype_digit($search)) {
                        $orders->where('id', (int) $search)->orWhereHas('user', function ($users) use ($search): void {
                            $users->where('name', 'like', "%{$search}%");
                        });

                        return;
                    }

                    $orders->whereHas('user', function ($users) use ($search): void {
                        $users->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(15);

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $order->load(['user', 'address', 'items.product', 'delivery.courier']);
        $couriers = Courier::query()
            ->where('is_available', true)
            ->orWhere('id', $order->delivery?->courier_id)
            ->orderBy('name')
            ->get();

        return view('admin.orders.show', compact('order', 'couriers'));
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,processing,delivered,cancelled'],
        ]);

        try {
            DB::transaction(function () use ($order, $validated): void {
                $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

                if (
                    $lockedOrder->payment_method === 'bank_transfer'
                    && $lockedOrder->payment_status !== 'paid'
                    && ! in_array($validated['status'], ['pending', 'cancelled'], true)
                ) {
                    throw new \RuntimeException('Pesanan transfer harus menunggu verifikasi pembayaran sebelum diproses.');
                }

                $lockedOrder->update($validated);

                $delivery = $lockedOrder->delivery()->lockForUpdate()->first();
                if (! $delivery) {
                    return;
                }

                $deliveryStatus = match ($validated['status']) {
                    'delivered' => 'delivered',
                    'cancelled' => 'failed',
                    'processing' => 'in_transit',
                    default => $order->delivery->status,
                };

                $delivery->update([
                    'status' => $deliveryStatus,
                    'delivered_at' => $validated['status'] === 'delivered' ? now() : null,
                ]);

                if (in_array($validated['status'], ['delivered', 'cancelled'], true) && $delivery->courier_id) {
                    $hasActiveDeliveries = Courier::find($delivery->courier_id)?->deliveries()
                        ->whereIn('status', ['assigned', 'in_transit'])
                        ->exists();

                    if (! $hasActiveDeliveries) {
                        Courier::whereKey($delivery->courier_id)->update(['is_available' => true]);
                    }
                }
            });

            return back()->with('success', 'Status pesanan diperbarui.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Gagal update status order #' . $order->id . ': ' . $e->getMessage());

            return back()->with('error', 'Gagal memperbarui status pesanan.');
        }
    }

    public function assignCourier(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'courier_id' => ['required', 'exists:couriers,id'],
        ]);

        try {
            if (! $order->delivery) {
                return back()->with('error', 'Data pengantaran untuk pesanan ini tidak ditemukan.');
            }

            if (in_array($order->status, ['delivered', 'cancelled'], true)) {
                return back()->with('error', 'Kurir tidak dapat ditugaskan untuk pesanan selesai atau dibatalkan.');
            }

            DB::transaction(function () use ($order, $validated): void {
                $delivery = $order->delivery()->lockForUpdate()->firstOrFail();
                $courier = Courier::whereKey($validated['courier_id'])->lockForUpdate()->firstOrFail();
                $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

                if ($lockedOrder->payment_method === 'bank_transfer' && $lockedOrder->payment_status !== 'paid') {
                    throw new \RuntimeException('Pesanan transfer belum dapat ditugaskan sebelum pembayaran diverifikasi.');
                }

                if (! $courier->is_available && $delivery->courier_id !== $courier->id) {
                    throw new \RuntimeException('Kurir yang dipilih sedang tidak tersedia.');
                }

                if ($delivery->courier_id && $delivery->courier_id !== $courier->id) {
                    Courier::whereKey($delivery->courier_id)->update(['is_available' => true]);
                }

                $delivery->update([
                    'courier_id' => $courier->id,
                    'status' => 'assigned',
                ]);
                $courier->update(['is_available' => false]);
            });

            return back()->with('success', 'Kurir berhasil ditugaskan.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Gagal assign kurir ke order #' . $order->id . ': ' . $e->getMessage());

            return back()->with('error', 'Gagal menugaskan kurir.');
        }
    }

    public function markPaid(Order $order): RedirectResponse
    {
        try {
            DB::transaction(function () use ($order): void {
                $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

                if ($lockedOrder->payment_method !== 'cod') {
                    throw new \RuntimeException('Pembayaran hanya dapat dicatat untuk pesanan COD.');
                }

                if ($lockedOrder->status !== 'delivered') {
                    throw new \RuntimeException('Pembayaran COD hanya dapat dicatat setelah pesanan selesai diantar.');
                }

                if ($lockedOrder->payment_status === 'paid') {
                    throw new \RuntimeException('Pembayaran pesanan ini sudah tercatat lunas.');
                }

                $lockedOrder->update(['payment_status' => 'paid']);
            });

            return back()->with('success', 'Pembayaran COD berhasil dicatat sebagai lunas.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Gagal mencatat pembayaran order #' . $order->id . ': ' . $e->getMessage());

            return back()->with('error', 'Gagal mencatat pembayaran pesanan.');
        }
    }
}