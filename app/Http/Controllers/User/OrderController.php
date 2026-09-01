<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()
            ->orders()
            ->with(['items.product', 'delivery.courier'])
            ->latest()
            ->paginate(10);

        return view('user.orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order): View
    {
        abort_if($order->user_id !== $request->user()->id, 403);

        $order->load(['items.product', 'address', 'delivery.courier']);

        return view('user.orders.show', compact('order'));
    }
}