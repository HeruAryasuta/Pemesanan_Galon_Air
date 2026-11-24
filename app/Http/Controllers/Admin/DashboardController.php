<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'period' => ['nullable', 'in:weekly,monthly,yearly,custom'],
            'from' => ['nullable', 'required_if:period,custom', 'date'],
            'to' => ['nullable', 'required_if:period,custom', 'date', 'after_or_equal:from'],
        ]);
        $period = $validated['period'] ?? 'weekly';
        $timezone = config('app.display_timezone');
        $today = CarbonImmutable::now($timezone)->startOfDay();
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
            $statusGradientStops[] = $status['color'].' '.number_format($donutOffset, 2, '.', '').'% '.number_format($donutOffset + $segment, 2, '.', '').'%';
            $donutOffset += $segment;
        }
        $statusGradient = $statusCounts->sum() > 0
            ? 'conic-gradient('.implode(', ', $statusGradientStops).')'
            : '#e2e8f0';

        $trendStart = match ($period) {
            'monthly' => $today->startOfMonth(),
            'yearly' => $today->startOfYear(),
            'custom' => CarbonImmutable::parse($validated['from'], $timezone)->startOfDay(),
            default => $today->subDays(6),
        };
        $trendEnd = $period === 'custom'
            ? CarbonImmutable::parse($validated['to'], $timezone)->endOfDay()
            : $today->endOfDay();
        $daysInRange = $trendStart->startOfDay()->diffInDays($trendEnd->startOfDay()) + 1;
        $trendGranularity = match (true) {
            $period === 'yearly' => 'month',
            $period === 'custom' && $daysInRange > 180 => 'month',
            $period === 'custom' && $daysInRange > 31 => 'week',
            default => 'day',
        };
        $trendBuckets = collect();
        foreach (Order::query()
            ->whereBetween('ordered_at', [$utcRange($trendStart), $utcRange($trendEnd)])
            ->orderBy('ordered_at')
            ->cursor() as $order) {
            $date = CarbonImmutable::parse($order->ordered_at, 'UTC')->setTimezone($timezone)->startOfDay();
            $bucketKey = match ($trendGranularity) {
                'month' => $date->format('Y-m'),
                'week' => 'week-'.intdiv($trendStart->startOfDay()->diffInDays($date), 7),
                default => $date->format('Y-m-d'),
            };
            $trendBuckets->put($bucketKey, (int) $trendBuckets->get($bucketKey, 0) + 1);
        }

        $trend = collect();
        if ($trendGranularity === 'month') {
            for ($date = $trendStart->startOfMonth(); $date <= $trendEnd; $date = $date->addMonth()) {
                $trend->push([
                    'label' => $date->locale('id')->translatedFormat('M Y'),
                    'count' => (int) $trendBuckets->get($date->format('Y-m'), 0),
                ]);
            }
        } elseif ($trendGranularity === 'week') {
            for ($index = 0; $index < ceil($daysInRange / 7); $index++) {
                $date = $trendStart->addDays($index * 7);
                $trend->push([
                    'label' => $date->locale('id')->translatedFormat('d M'),
                    'count' => (int) $trendBuckets->get('week-'.$index, 0),
                ]);
            }
        } else {
            for ($date = $trendStart->startOfDay(); $date <= $trendEnd; $date = $date->addDay()) {
                $trend->push([
                    'label' => $date->locale('id')->translatedFormat($period === 'weekly' ? 'D' : 'd M'),
                    'count' => (int) $trendBuckets->get($date->format('Y-m-d'), 0),
                ]);
            }
        }

        $maxTrend = max(1, (int) $trend->max('count'));
        $chartPoints = $trend->map(fn (array $day, int $index) => [
            'x' => 12 + ($index * 376 / max(1, $trend->count() - 1)),
            'y' => 142 - ($day['count'] / $maxTrend * 112),
        ]);
        $trendLine = $chartPoints->map(fn (array $point) => number_format($point['x'], 1, '.', '').','.number_format($point['y'], 1, '.', ''))->implode(' ');
        $trendTitle = match ($period) {
            'monthly' => 'Tren Pesanan Bulan Ini',
            'yearly' => 'Tren Pesanan Tahun Ini',
            'custom' => 'Tren Pesanan Periode Khusus',
            default => 'Tren Pesanan (7 Hari)',
        };

        $recentOrders = Order::query()
            ->with(['user', 'items.product'])
            ->latest('ordered_at')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'statusBreakdown',
            'statusGradient',
            'trend',
            'chartPoints',
            'trendLine',
            'recentOrders',
            'period',
            'trendStart',
            'trendEnd',
            'trendTitle',
        ));
    }
}
