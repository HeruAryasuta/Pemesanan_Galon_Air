<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $salesByProduct = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->select('order_items.product_id')
            ->selectRaw('SUM(order_items.quantity) as units_sold')
            ->selectRaw('COUNT(DISTINCT order_items.order_id) as transaction_count')
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('order_items.product_id');

        $popularProducts = Product::query()
            ->joinSub($salesByProduct, 'product_sales', function ($join): void {
                $join->on('products.id', '=', 'product_sales.product_id');
            })
            ->select('products.*')
            ->addSelect('product_sales.units_sold', 'product_sales.transaction_count')
            ->with('category')
            ->where('products.is_active', true)
            ->where('products.stock', '>', 0)
            ->orderByDesc('product_sales.units_sold')
            ->orderByDesc('product_sales.transaction_count')
            ->orderBy('products.name')
            ->take(4)
            ->get();

        return view('user.home', compact('popularProducts'));
    }
}