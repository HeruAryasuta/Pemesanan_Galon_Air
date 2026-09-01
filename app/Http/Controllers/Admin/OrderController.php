<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with(['user', 'delivery'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(15);

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $order->load(['user', 'address', 'items.product', 'delivery.courier']);

        return view('admin.orders.show', compact('order'));
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,processing,delivered,cancelled'],
        ]);

        try {
            $order->update($validated);

            return back()->with('success', 'Status pesanan diperbarui.');
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
            if (!$order->delivery) {
                return back()->with('error', 'Data pengantaran untuk pesanan ini tidak ditemukan.');
            }

            $order->delivery->update([
                'courier_id' => $validated['courier_id'],
                'status' => 'assigned',
            ]);

            return back()->with('success', 'Kurir berhasil ditugaskan.');
        } catch (\Throwable $e) {
            Log::error('Gagal assign kurir ke order #' . $order->id . ': ' . $e->getMessage());

            return back()->with('error', 'Gagal menugaskan kurir.');
        }
    }
}