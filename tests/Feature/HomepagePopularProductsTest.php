<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepagePopularProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_active_top_selling_products_from_non_cancelled_orders(): void
    {
        $category = Category::create([
            'name' => 'Air Galon',
            'slug' => 'air-galon',
        ]);
        $popularProduct = $this->createProduct($category, 'Air Galon Populer', 'air-galon-populer');
        $otherProduct = $this->createProduct($category, 'Air Galon Lain', 'air-galon-lain');
        $inactiveProduct = $this->createProduct($category, 'Produk Nonaktif', 'produk-nonaktif', false);
        $customer = User::factory()->create();
        $address = Address::create([
            'user_id' => $customer->id,
            'label' => 'Rumah',
            'full_address' => 'Jl. Contoh No. 1, Surabaya',
        ]);

        $this->createOrder($customer, $address, $popularProduct, 3);
        $this->createOrder($customer, $address, $popularProduct, 2);
        $this->createOrder($customer, $address, $otherProduct, 4);
        $this->createOrder($customer, $address, $popularProduct, 20, 'cancelled');
        $this->createOrder($customer, $address, $inactiveProduct, 30);

        $response = $this->get('/')
            ->assertOk()
            ->assertSee('Produk Terlaris')
            ->assertSee('Diskon 20%')
            ->assertSee('Hak promo baru terpakai setelah pesanan selesai diantar')
            ->assertSee('aria-label="Tampilkan slide Promo Pembelian Pertama"', false)
            ->assertSee('aria-label="Slide sebelumnya"', false)
            ->assertSee('aria-label="Slide berikutnya"', false)
            ->assertSee('Air Galon Populer')
            ->assertSee('5 unit terjual')
            ->assertSee('2 pesanan')
            ->assertDontSee('Produk Nonaktif')
            ->assertViewHas('popularProducts', function ($products) use ($popularProduct, $otherProduct): bool {
                return $products->pluck('id')->all() === [$popularProduct->id, $otherProduct->id]
                    && (int) $products[0]->units_sold === 5
                    && (int) $products[0]->transaction_count === 2;
            });

        $content = $response->getContent();
        $this->assertLessThan(strpos($content, 'id="kategori"'), strpos($content, 'id="produk-populer"'));
    }

    public function test_homepage_explains_when_there_are_no_sales_to_rank(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Belum ada data penjualan')
            ->assertSee('Jelajahi katalog');
    }

    private function createProduct(Category $category, string $name, string $slug, bool $isActive = true): Product
    {
        return Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => $slug,
            'price' => 18000,
            'stock' => 10,
            'unit' => 'galon',
            'is_active' => $isActive,
        ]);
    }

    private function createOrder(
        User $customer,
        Address $address,
        Product $product,
        int $quantity,
        string $status = 'delivered',
    ): Order {
        $order = Order::create([
            'user_id' => $customer->id,
            'address_id' => $address->id,
            'status' => $status,
            'total_price' => 18000 * $quantity,
            'delivery_fee' => 0,
            'payment_method' => 'cod',
            'payment_status' => $status === 'delivered' ? 'paid' : 'unpaid',
            'ordered_at' => now(),
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'price_snapshot' => 18000,
        ]);

        return $order;
    }
}
