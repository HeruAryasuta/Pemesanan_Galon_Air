<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_cart_prompts_customer_to_browse_products(): void
    {
        $this->get(route('user.cart.index'))
            ->assertOk()
            ->assertSee('Keranjang Anda masih kosong')
            ->assertSee('Lihat katalog produk')
            ->assertDontSee('Lanjut ke checkout');
    }

    public function test_cart_displays_products_quantities_and_subtotal(): void
    {
        $product = $this->createProduct([
            'name' => 'Galon Aqua',
            'price' => 21000,
            'stock' => 10,
        ]);

        $this->withSession(['cart' => [$product->id => 2]])
            ->get(route('user.cart.index'))
            ->assertOk()
            ->assertSee('Galon Aqua')
            ->assertSee('value="2"', false)
            ->assertSee('Rp42.000')
            ->assertSee('Lanjut ke checkout')
            ->assertSee(route('user.cart.update', $product->id), false)
            ->assertSee(route('user.cart.destroy', $product->id), false);
    }

    public function test_cart_update_respects_available_stock(): void
    {
        $product = $this->createProduct(['stock' => 5]);

        $this->withSession(['cart' => [$product->id => 1]])
            ->put(route('user.cart.update', $product->id), ['quantity' => 3])
            ->assertSessionHas('cart', [$product->id => 3]);

        $this->put(route('user.cart.update', $product->id), ['quantity' => 6])
            ->assertSessionHas('error', 'Jumlah produk melebihi stok yang tersedia.')
            ->assertSessionHas('cart', [$product->id => 3]);
    }

    public function test_cart_item_can_be_removed(): void
    {
        $product = $this->createProduct();

        $this->withSession(['cart' => [$product->id => 1]])
            ->delete(route('user.cart.destroy', $product->id))
            ->assertSessionHas('cart', []);
    }

    public function test_checkout_is_not_offered_when_cart_quantity_exceeds_stock(): void
    {
        $product = $this->createProduct(['stock' => 1]);

        $this->withSession(['cart' => [$product->id => 2]])
            ->get(route('user.cart.index'))
            ->assertOk()
            ->assertSee('Sesuaikan jumlah produk dengan stok yang tersedia sebelum checkout.')
            ->assertDontSee('Lanjut ke checkout');
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createProduct(array $attributes = []): Product
    {
        $category = Category::create([
            'name' => 'Air Galon',
            'slug' => 'air-galon',
        ]);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Air Galon',
            'description' => 'Air minum untuk kebutuhan harian.',
            'slug' => 'air-galon',
            'price' => 18000,
            'stock' => 8,
            'unit' => 'galon',
            'is_active' => true,
        ], $attributes));
    }
}
