<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderCancellationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()
            ->orders()
            ->with(['items.product', 'delivery.courier', 'address'])
            ->latest()
            ->paginate(10);

        return view('user.orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order): View
    {
        abort_if($order->user_id !== $request->user()->id, 403);

        $order->load(['items.product', 'address.serviceZone', 'delivery.courier', 'delivery.routeStop.route']);

        return view('user.orders.show', compact('order'));
    }

    public function cancel(Request $request, Order $order, OrderCancellationService $cancellationService): RedirectResponse
    {
        abort_if($order->user_id !== $request->user()->id, 404);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $cancellationService->cancel($order, $request->user(), $validated['reason'] ?? null);
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages(['order' => $exception->getMessage()]);
        } catch (\Throwable $exception) {
            Log::error('Gagal membatalkan pesanan #'.$order->id.': '.$exception->getMessage());

            return back()->with('error', 'Pesanan gagal dibatalkan. Silakan coba lagi.');
        }

        $order->refresh();
        $message = $order->payment_status === 'paid'
            ? 'Pesanan dibatalkan. Pengembalian dana menunggu diproses oleh admin.'
            : 'Pesanan berhasil dibatalkan dan stok telah dikembalikan.';

        return redirect()->route('user.orders.show', $order)->with('success', $message);
    }
}
