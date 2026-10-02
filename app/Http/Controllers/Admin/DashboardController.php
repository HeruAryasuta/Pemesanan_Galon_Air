<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $timezone = config('app.display_timezone');
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $weekStart = $today->subDays(6);
        $monthStart = $today->startOfMonth();
        $monthEnd = $today->endOfMonth();
        $utcRange = fn (CarbonImmutable $date) => $date->utc()->toDateTimeString();

        $stats = [
            'total_orders' => Order::count(),
            'today_orders' => Order::whereBetween('ordered_at', [$utcRange($today), $utcRange($today->endOfDay())])->count(),
            'monthly_revenue' => Order::where('payment_status', 'paid')
                ->whereBetween('ordered_at', [$utcRange($monthStart), $utcRange($monthEnd)])
                ->sum('total_price'),
            'active_customers' => User::query()
                ->where('role', 'customer')
                ->whereHas('orders', fn ($orders) => $orders->whereBetween('ordered_at', [$utcRange($today->subDays(29)), $utcRange($today->endOfDay())]))
                ->count(),
            'available_couriers' => Courier::where('is_available', true)->count(),
            'total_couriers' => Courier::count(),
            'low_stock_products' => Product::where('stock', '<', 10)->count(),
        ];

        $statusCounts = Order::query()
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $statusTotal = max(1, (int) $statusCounts->sum());
        $statusBreakdown = collect([
            'delivered' => ['label' => 'Selesai', 'color' => '#173c91'],
            'processing' => ['label' => 'Dalam pengantaran', 'color' => '#46d6ee'],
            'confirmed' => ['label' => 'Dikonfirmasi', 'color' => '#8aa7ee'],
            'pending' => ['label' => 'Menunggu konfirmasi', 'color' => '#dbe5fb'],
            'cancelled' => ['label' => 'Dibatalkan', 'color' => '#f59a8b'],
        ])->map(fn (array $status, string $key) => [
            ...$status,
            'count' => (int) $statusCounts->get($key, 0),
            'percentage' => round(((int) $statusCounts->get($key, 0) / $statusTotal) * 100),
        ]);
        $statusGradientStops = [];
        $donutOffset = 0;
        foreach ($statusBreakdown as $status) {
            $segment = $status['count'] / $statusTotal * 100;
            $statusGradientStops[] = $status['color'] . ' ' . number_format($donutOffset, 2, '.', '') . '% ' . number_format($donutOffset + $segment, 2, '.', '') . '%';
            $donutOffset += $segment;
        }
        $statusGradient = $statusCounts->sum() > 0
            ? 'conic-gradient(' . implode(', ', $statusGradientStops) . ')'
            : '#e2e8f0';

        $trendOrders = Order::query()
            ->whereBetween('ordered_at', [$utcRange($weekStart), $utcRange($today->endOfDay())])
            ->pluck('ordered_at')
            ->map(fn ($date) => CarbonImmutable::parse($date, 'UTC')->setTimezone($timezone)->format('Y-m-d'))
            ->countBy();
        $trend = collect(range(0, 6))->map(function (int $offset) use ($weekStart, $trendOrders): array {
            $date = $weekStart->addDays($offset);

            return [
                'label' => $date->locale('id')->translatedFormat('D'),
                'count' => (int) $trendOrders->get($date->format('Y-m-d'), 0),
            ];
        });
        $maxTrend = max(1, (int) $trend->max('count'));
        $chartPoints = $trend->map(fn (array $day, int $index) => [
            'x' => 12 + ($index * 376 / 6),
            'y' => 142 - ($day['count'] / $maxTrend * 112),
        ]);
        $trendLine = $chartPoints->map(fn (array $point) => number_format($point['x'], 1, '.', '') . ',' . number_format($point['y'], 1, '.', ''))->implode(' ');

        $recentOrders = Order::query()
            ->with(['user', 'items.product'])
            ->latest('ordered_at')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'statusBreakdown', 'statusGradient', 'trend', 'chartPoints', 'trendLine', 'recentOrders'));
    }
}