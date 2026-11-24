<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'period' => ['nullable', 'in:weekly,monthly,yearly,custom'],
            'from' => ['nullable', 'required_if:period,custom', 'date'],
            'to' => ['nullable', 'required_if:period,custom', 'date', 'after_or_equal:from'],
        ]);

        $timezone = config('app.display_timezone');
        $today = CarbonImmutable::now($timezone);
        $period = $validated['period'] ?? ((isset($validated['from']) || isset($validated['to'])) ? 'custom' : 'monthly');
        [$from, $to] = match ($period) {
            'weekly' => [$today->startOfDay()->subDays(6), $today->endOfDay()],
            'yearly' => [$today->startOfYear(), $today->endOfYear()],
            'custom' => [
                isset($validated['from'])
                    ? CarbonImmutable::parse($validated['from'], $timezone)->startOfDay()
                    : (isset($validated['to'])
                        ? CarbonImmutable::parse($validated['to'], $timezone)->startOfMonth()
                        : $today->startOfMonth()),
                isset($validated['to'])
                    ? CarbonImmutable::parse($validated['to'], $timezone)->endOfDay()
                    : (isset($validated['from']) ? $today->endOfDay() : $today->endOfMonth()),
            ],
            default => [$today->startOfMonth(), $today->endOfMonth()],
        };

        $fromUtc = $from->utc();
        $toUtc = $to->utc();
        $ordersInPeriod = Order::query()->whereBetween('ordered_at', [$fromUtc, $toUtc]);

        $totalRevenue = (clone $ordersInPeriod)
            ->where('payment_status', 'paid')
            ->where('status', '!=', 'cancelled')
            ->sum('total_price');

        $totalOrders = (clone $ordersInPeriod)->count();
        $cancelledOrders = (clone $ordersInPeriod)->where('status', 'cancelled')->count();

        $topProducts = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereBetween('orders.ordered_at', [$fromUtc, $toUtc])
            ->where('orders.status', '!=', 'cancelled')
            ->select('products.name', 'products.unit', DB::raw('SUM(order_items.quantity) as total_sold'))
            ->groupBy('products.id', 'products.name', 'products.unit')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get();

        return view('admin.reports.index', compact(
            'totalRevenue',
            'totalOrders',
            'cancelledOrders',
            'topProducts',
            'from',
            'to',
            'period',
        ));
    }
}
