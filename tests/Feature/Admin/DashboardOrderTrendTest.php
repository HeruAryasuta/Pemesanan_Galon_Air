<?php

namespace Tests\Feature\Admin;

use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardOrderTrendTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_order_trend_by_custom_date_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();
        $address = Address::create([
            'user_id' => $customer->id,
            'label' => 'Rumah',
            'full_address' => 'Jl. Contoh No. 1, Surabaya',
        ]);
        $this->createOrder($customer, $address, '2026-10-01 03:00:00');
        $this->createOrder($customer, $address, '2026-10-02 03:00:00');
        $this->createOrder($customer, $address, '2026-09-30 03:00:00');

        $this->actingAs($admin)
            ->get(route('admin.dashboard', [
                'period' => 'custom',
                'from' => '2026-10-01',
                'to' => '2026-10-02',
            ]))
            ->assertOk()
            ->assertSee('Tren Pesanan Periode Khusus')
            ->assertViewHas('trend', function ($trend): bool {
                return $trend->pluck('count')->all() === [1, 1]
                    && $trend->pluck('label')->all() === ['01 Okt', '02 Okt'];
            });
    }

    public function test_dashboard_rejects_custom_trend_without_both_dates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard', ['period' => 'custom', 'from' => '2026-10-01']))
            ->assertSessionHasErrors('to');
    }

    public function test_yearly_dashboard_trend_groups_orders_by_month(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();
        $address = Address::create([
            'user_id' => $customer->id,
            'label' => 'Rumah',
            'full_address' => 'Jl. Contoh No. 1, Surabaya',
        ]);
        $timezone = config('app.display_timezone');
        $now = CarbonImmutable::now($timezone);
        $yearStart = $now->startOfYear();
        $secondMonthIndex = min(5, $now->month - 1);
        $this->createOrder($customer, $address, $yearStart->setTime(12, 0)->utc()->toDateTimeString());
        $this->createOrder($customer, $address, $yearStart->addMonths($secondMonthIndex)->setTime(12, 0)->utc()->toDateTimeString());

        $response = $this->actingAs($admin)
            ->get(route('admin.dashboard', ['period' => 'yearly']))
            ->assertOk()
            ->assertSee('Tren Pesanan Tahun Ini')
            ->assertViewHas('trend');
        $trend = $response->viewData('trend');

        $this->assertCount($now->month, $trend);
        $this->assertSame($secondMonthIndex === 0 ? 2 : 1, $trend[0]['count']);
        if ($secondMonthIndex > 0) {
            $this->assertSame(1, $trend[$secondMonthIndex]['count']);
        }
        $this->assertSame(2, $trend->sum('count'));
    }

    private function createOrder(User $customer, Address $address, string $orderedAt): Order
    {
        return Order::create([
            'user_id' => $customer->id,
            'address_id' => $address->id,
            'status' => 'pending',
            'total_price' => 26000,
            'delivery_fee' => 5000,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'ordered_at' => $orderedAt,
        ]);
    }
}
