<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Recommendation\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_recommendation_page_shows_available_products_and_frequently_bought_pairs(): void
    {
        $water = $this->createCategory('Air Galon', 'air-galon');
        $lpg = $this->createCategory('LPG', 'lpg');
        $purchasedProduct = $this->createProduct($water, 'Galon Aqua', 'galon-aqua');
        $recommendedProduct = $this->createProduct($water, 'Galon Cleo', 'galon-cleo');
        $companionProduct = $this->createProduct($lpg, 'LPG 3kg', 'lpg-3kg');
        $user = User::factory()->create();
        $address = $user->addresses()->create([
            'label' => 'Rumah',
            'full_address' => 'Jl. Gunung Anyar No. 10, Surabaya',
        ]);
        $order = $user->orders()->create([
            'address_id' => $address->id,
            'status' => 'delivered',
            'total_price' => 39000,
            'ordered_at' => now(),
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $purchasedProduct->id,
            'quantity' => 1,
            'price_snapshot' => $purchasedProduct->price,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $companionProduct->id,
            'quantity' => 1,
            'price_snapshot' => $companionProduct->price,
        ]);

        $recommendations = app(RecommendationService::class)->getRecommendationsFor($user, 2);
        $this->assertFalse($recommendations->contains('id', $purchasedProduct->id));

        $this->actingAs($user)
            ->get(route('user.recommendations.index'))
            ->assertOk()
            ->assertSee('Berdasarkan Pembelian Sebelumnya')
            ->assertSee($recommendedProduct->name)
            ->assertSee('Sering Dibeli Bersama')
            ->assertSee($companionProduct->name);
    }

    public function test_recommendations_rank_products_by_content_cosine_similarity_instead_of_category_alone(): void
    {
        $water = $this->createCategory('Air Galon', 'air-galon');
        $mineralWater = $this->createCategory('Air Mineral', 'air-mineral');
        $purchasedProduct = $this->createProduct(
            $water,
            'Galon Aqua Mineral 19L',
            'galon-aqua-mineral-19l',
            ['description' => 'Air mineral isi ulang untuk kebutuhan keluarga.'],
        );
        $sameCategoryProduct = $this->createProduct(
            $water,
            'Galon Cleo Refill',
            'galon-cleo-refill',
            ['description' => 'Air galon isi ulang.'],
        );
        $mostSimilarProduct = $this->createProduct(
            $mineralWater,
            'Aqua Mineral Galon 19L',
            'aqua-mineral-galon-19l',
            ['description' => 'Air minum mineral kemasan keluarga.'],
        );
        $user = User::factory()->create();
        $address = $user->addresses()->create([
            'label' => 'Rumah',
            'full_address' => 'Jl. Gunung Anyar No. 10, Surabaya',
        ]);
        $order = $user->orders()->create([
            'address_id' => $address->id,
            'status' => 'delivered',
            'total_price' => $purchasedProduct->price,
            'ordered_at' => now(),
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $purchasedProduct->id,
            'quantity' => 1,
            'price_snapshot' => $purchasedProduct->price,
        ]);

        $recommendations = app(RecommendationService::class)->getRecommendationsFor($user, 2);

        $this->assertSame($mostSimilarProduct->id, $recommendations->first()->id);
        $this->assertGreaterThan($recommendations->last()->similarity_score, $recommendations->first()->similarity_score);
        $this->assertGreaterThan(0, $recommendations->first()->similarity_score);
        $this->assertLessThanOrEqual(1, $recommendations->first()->similarity_score);

        $this->actingAs($user)
            ->get(route('user.recommendations.index'))
            ->assertOk()
            ->assertSee('Kemiripan ')
            ->assertSee($mostSimilarProduct->name);
    }

    public function test_guest_sees_popular_recommendations_without_purchase_history_claims(): void
    {
        $category = $this->createCategory('Air Galon', 'air-galon');
        $product = $this->createProduct($category, 'Galon Favorit', 'galon-favorit');

        $this->get(route('user.recommendations.index'))
            ->assertOk()
            ->assertSee('Pilihan Populer Untuk Anda')
            ->assertSee($product->name)
            ->assertDontSee('Berdasarkan Pembelian Sebelumnya');
    }

    public function test_recommendations_fall_back_to_reorder_products_when_no_unpurchased_products_are_available(): void
    {
        $category = $this->createCategory('Air Galon', 'air-galon');
        $firstProduct = $this->createProduct($category, 'Galon Aqua', 'galon-aqua');
        $secondProduct = $this->createProduct($category, 'Galon Cleo', 'galon-cleo');
        $user = User::factory()->create();
        $address = $user->addresses()->create([
            'label' => 'Rumah',
            'full_address' => 'Jl. Gunung Anyar No. 10, Surabaya',
        ]);

        foreach ([$firstProduct, $secondProduct] as $product) {
            $order = $user->orders()->create([
                'address_id' => $address->id,
                'status' => 'delivered',
                'total_price' => $product->price,
                'ordered_at' => now(),
            ]);
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'price_snapshot' => $product->price,
            ]);
        }

        $this->actingAs($user)
            ->get(route('user.recommendations.index'))
            ->assertOk()
            ->assertSee('Berdasarkan Pembelian Sebelumnya')
            ->assertSee('Pesan Ulang')
            ->assertSee($firstProduct->name)
            ->assertSee($secondProduct->name);
    }

    private function createCategory(string $name, string $slug): Category
    {
        return Category::create([
            'name' => $name,
            'slug' => $slug,
        ]);
    }

    private function createProduct(Category $category, string $name, string $slug, array $attributes = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => $slug,
            'price' => 20000,
            'stock' => 10,
            'unit' => 'pcs',
            'is_active' => true,
        ], $attributes));
    }
}
