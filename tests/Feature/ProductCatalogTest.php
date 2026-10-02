<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Database\Seeders\ProductCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_lists_active_products_and_categories(): void
    {
        $category = Category::create([
            'name' => 'Air Galon',
            'slug' => 'air-galon',
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Air Mineral 19 Liter',
            'description' => 'Air minum untuk kebutuhan keluarga.',
            'slug' => 'air-mineral-19-liter',
            'price' => 18000,
            'stock' => 8,
            'unit' => 'galon',
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Produk Nonaktif',
            'slug' => 'produk-nonaktif',
            'price' => 10000,
            'stock' => 5,
            'unit' => 'pcs',
            'is_active' => false,
        ]);

        $this->get(route('user.products.index'))
            ->assertOk()
            ->assertSee('Semua Produk')
            ->assertSee('Rekomendasi')
            ->assertSee('Cara Pesan')
            ->assertSee('Tentang Kami')
            ->assertSee('href="' . route('user.products.index') . '"', false)
            ->assertSee('border-[#173c91] text-[#09296d]', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('Air Mineral 19 Liter')
            ->assertSee('Air Galon')
            ->assertDontSee('Produk Nonaktif');
    }

    public function test_catalog_can_filter_by_category(): void
    {
        $waterCategory = Category::create([
            'name' => 'Air Galon',
            'slug' => 'air-galon',
        ]);
        $drinkCategory = Category::create([
            'name' => 'Minuman Ringan',
            'slug' => 'minuman-ringan',
        ]);

        $water = Product::create([
            'category_id' => $waterCategory->id,
            'name' => 'Galon Keluarga',
            'slug' => 'galon-keluarga',
            'price' => 18000,
            'stock' => 8,
            'unit' => 'galon',
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $drinkCategory->id,
            'name' => 'Teh Melati',
            'slug' => 'teh-melati',
            'price' => 5000,
            'stock' => 20,
            'unit' => 'botol',
            'is_active' => true,
        ]);

        $this->get(route('user.products.index', ['category' => $waterCategory->id]))
            ->assertOk()
            ->assertSee($water->name)
            ->assertDontSee('Teh Melati');
    }

    public function test_catalog_search_matches_category_names(): void
    {
        $lpgCategory = Category::create([
            'name' => 'LPG',
            'slug' => 'lpg',
        ]);

        $product = Product::create([
            'category_id' => $lpgCategory->id,
            'name' => 'Gas 3 Kg',
            'slug' => 'gas-3-kg',
            'price' => 22000,
            'stock' => 4,
            'unit' => 'tabung',
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => Category::create([
                'name' => 'Air Galon',
                'slug' => 'air-galon',
            ])->id,
            'name' => 'Air Minum',
            'slug' => 'air-minum',
            'price' => 18000,
            'stock' => 7,
            'unit' => 'galon',
            'is_active' => true,
        ]);

        $this->get(route('user.products.index', ['search' => 'LPG']))
            ->assertOk()
            ->assertSee($product->name)
            ->assertDontSee('Air Minum');
    }

    public function test_catalog_can_filter_by_price_and_availability(): void
    {
        $category = Category::create([
            'name' => 'Air Galon',
            'slug' => 'air-galon',
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Harga Terlalu Rendah',
            'slug' => 'harga-terlalu-rendah',
            'price' => 15000,
            'stock' => 10,
            'unit' => 'galon',
            'is_active' => true,
        ]);

        $matchingProduct = Product::create([
            'category_id' => $category->id,
            'name' => 'Produk Sesuai Filter',
            'slug' => 'produk-sesuai-filter',
            'price' => 24000,
            'stock' => 4,
            'unit' => 'galon',
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Stok Habis',
            'slug' => 'stok-habis',
            'price' => 26000,
            'stock' => 0,
            'unit' => 'galon',
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Harga Terlalu Tinggi',
            'slug' => 'harga-terlalu-tinggi',
            'price' => 35000,
            'stock' => 8,
            'unit' => 'galon',
            'is_active' => true,
        ]);

        $this->get(route('user.products.index', [
            'min_price' => 20000,
            'max_price' => 30000,
            'in_stock' => 1,
        ]))
            ->assertOk()
            ->assertSee($matchingProduct->name)
            ->assertDontSee('Harga Terlalu Rendah')
            ->assertDontSee('Stok Habis')
            ->assertDontSee('Harga Terlalu Tinggi');
    }

    public function test_catalog_rejects_an_invalid_price_range(): void
    {
        $this->get(route('user.products.index', [
            'min_price' => 30000,
            'max_price' => 20000,
        ]))->assertSessionHasErrors('max_price');
    }

    public function test_product_detail_shows_product_information_and_related_products(): void
    {
        $category = Category::create([
            'name' => 'Air Galon',
            'slug' => 'air-galon',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Galon Aqua 19 Liter',
            'description' => 'Air mineral untuk kebutuhan keluarga.',
            'slug' => 'galon-aqua-19-liter',
            'price' => 21000,
            'stock' => 12,
            'unit' => 'galon',
            'is_active' => true,
        ]);

        $relatedProduct = Product::create([
            'category_id' => $category->id,
            'name' => 'Galon Cleo 19 Liter',
            'slug' => 'galon-cleo-19-liter',
            'price' => 18500,
            'stock' => 8,
            'unit' => 'galon',
            'is_active' => true,
        ]);

        $this->get(route('user.products.show', $product))
            ->assertOk()
            ->assertSee('Rekomendasi')
            ->assertSee('aria-current="page"', false)
            ->assertSee($product->name)
            ->assertSee($product->description)
            ->assertSee('Tambah ke Keranjang')
            ->assertSee($relatedProduct->name)
            ->assertSee(route('user.cart.store'));
    }

    public function test_inactive_product_detail_is_not_available(): void
    {
        $category = Category::create([
            'name' => 'Air Galon',
            'slug' => 'air-galon',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Produk Nonaktif',
            'slug' => 'produk-nonaktif',
            'price' => 18000,
            'stock' => 0,
            'unit' => 'galon',
            'is_active' => false,
        ]);

        $this->get(route('user.products.show', $product))->assertNotFound();
    }

    public function test_product_catalog_seeder_creates_sample_products_without_resetting_existing_stock(): void
    {
        $this->seed(ProductCatalogSeeder::class);

        $product = Product::where('slug', 'galon-aqua-19-liter')->firstOrFail();
        $product->update(['stock' => 3]);

        $this->seed(ProductCatalogSeeder::class);

        $this->assertSame(5, Product::count());
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertDatabaseHas('products', [
            'slug' => 'lpg-pertamina-12kg-biru',
            'price' => 215000,
            'stock' => 0,
            'image_path' => 'images/products/lpg-pertamina-12kg.svg',
        ]);
        $this->assertDatabaseHas('categories', ['slug' => 'air-mineral', 'name' => 'Air Mineral']);
    }
}
