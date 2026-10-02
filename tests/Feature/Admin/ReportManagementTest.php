<?php

namespace Tests\Feature\Admin;

use App\Models\Address;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_report_with_paid_revenue_and_non_cancelled_top_products(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();
        $address = Address::create([
            'user_id' => $customer->id,
            'label' => 'Rumah',
            'full_address' => 'Jl. Gunung Anyar No. 10, Surabaya',
        ]);
        $product = $this->createProduct();

        $this->createOrder($customer, $address, $product, [
            'status' => 'delivered',
            'payment_status' => 'paid',
            'total_price' => 47000,
            'ordered_at' => '2026-09-30 20:00:00',
        ], 2);
        $this->createOrder($customer, $address, $product, [
            'status' => 'processing',
            'payment_status' => 'unpaid',
            'total_price' => 26000,
            'ordered_at' => '2026-09-30 21:00:00',
        ], 1);
        $this->createOrder($customer, $address, $product, [
            'status' => 'cancelled',
            'payment_status' => 'unpaid',
            'total_price' => 26000,
            'ordered_at' => '2026-09-30 22:00:00',
        ], 9);

        $this->actingAs($admin)
            ->get(route('admin.reports.index', ['from' => '2026-10-01', 'to' => '2026-10-01']))
            ->assertOk()
            ->assertSee('Laporan')
            ->assertSee('Rp47.000')
            ->assertSee('3')
            ->assertSee('1')
            ->assertSee($product->name)
            ->assertSee('3 galon');
    }

    public function test_report_date_range_includes_entire_local_end_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();
        $address = Address::create([
            'user_id' => $customer->id,
            'label' => 'Rumah',
            'full_address' => 'Jl. Gunung Anyar No. 10, Surabaya',
        ]);
        $product = $this->createProduct();
        $this->createOrder($customer, $address, $product, [
            'status' => 'delivered',
            'payment_status' => 'paid',
            'total_price' => 47000,
            'ordered_at' => '2026-10-01 16:59:59',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports.index', ['from' => '2026-10-01', 'to' => '2026-10-01']))
            ->assertOk()
            ->assertSee('Rp47.000');
    }

    public function test_report_rejects_end_date_before_start_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.reports.index', ['from' => '2026-10-02', 'to' => '2026-10-01']))
            ->assertSessionHasErrors('to');
    }

    public function test_customer_cannot_access_reports(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('admin.reports.index'))
            ->assertForbidden();
    }

    private function createProduct(): Product
    {
        $category = Category::create([
            'name' => 'Air Galon',
            'slug' => 'air-galon',
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Galon Aqua',
            'slug' => 'galon-aqua',
            'price' => 21000,
            'stock' => 10,
            'unit' => 'galon',
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createOrder(User $customer, Address $address, Product $product, array $attributes, int $quantity = 1): Order
    {
        $order = Order::create(array_merge([
            'user_id' => $customer->id,
            'address_id' => $address->id,
            'status' => 'pending',
            'total_price' => 26000,
            'delivery_fee' => 5000,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'ordered_at' => now(),
        ], $attributes));

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'price_snapshot' => 21000,
        ]);

        return $order;
    }
}
