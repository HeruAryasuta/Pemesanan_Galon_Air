<?php

namespace App\Services\Recommendation;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecommendationService
{
    public function hasPurchaseHistory(User $user): bool
    {
        return $this->getPurchasedProductIds($user)->isNotEmpty();
    }

    public function getPurchasedProductIds(?User $user): Collection
    {
        if (! $user) {
            return collect();
        }

        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.user_id', $user->id)
            ->where('orders.status', '!=', 'cancelled')
            ->pluck('order_items.product_id')
            ->unique()
            ->values();
    }

    public function getRecommendationsFor(?User $user, int $limit = 6): Collection
    {
        $purchaseHistory = $user
            ? DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.user_id', $user->id)
                ->where('orders.status', '!=', 'cancelled')
                ->select('order_items.product_id')
                ->selectRaw('SUM(order_items.quantity) as purchased_quantity')
                ->groupBy('order_items.product_id')
                ->get()
            : collect();

        if ($purchaseHistory->isEmpty()) {
            return $this->fallbackBestSellers($limit);
        }

        $activeProducts = Product::query()
            ->with('category')
            ->where('is_active', true)
            ->get();

        if ($activeProducts->isEmpty()) {
            return collect();
        }

        $purchasedProducts = Product::query()
            ->with('category')
            ->whereIn('id', $purchaseHistory->pluck('product_id'))
            ->get()
            ->keyBy('id');

        $documentTerms = $activeProducts->mapWithKeys(
            fn (Product $product): array => [$product->id => $this->productTerms($product)],
        );
        $inverseDocumentFrequencies = $this->inverseDocumentFrequencies($documentTerms);
        $productVectors = $documentTerms->map(
            fn (array $terms): array => $this->toTfidfVector($terms, $inverseDocumentFrequencies),
        );

        $profile = [];

        foreach ($purchaseHistory as $purchase) {
            $product = $purchasedProducts->get($purchase->product_id);

            if (! $product) {
                continue;
            }

            $terms = $documentTerms->get($product->id) ?? $this->productTerms($product);
            $vector = $this->toTfidfVector($terms, $inverseDocumentFrequencies);
            $weight = log(1 + (int) $purchase->purchased_quantity);

            foreach ($this->normalizeVector($vector) as $term => $value) {
                $profile[$term] = ($profile[$term] ?? 0.0) + $value * $weight;
            }
        }

        if ($profile === []) {
            return collect();
        }

        $purchasedProductIds = $purchaseHistory->pluck('product_id')->map(
            fn ($id): int => (int) $id,
        );
        $availableProducts = $activeProducts
            ->filter(fn (Product $product): bool => $product->stock > 0)
            ->values();

        $recommendations = $this->scoreProducts(
            $availableProducts->reject(fn (Product $product): bool => $purchasedProductIds->contains($product->id)),
            $productVectors,
            $profile,
        );

        if ($recommendations->isNotEmpty()) {
            return $recommendations->take($limit)->values();
        }

        return $this->scoreProducts(
            $availableProducts->filter(fn (Product $product): bool => $purchasedProductIds->contains($product->id)),
            $productVectors,
            $profile,
        )->take($limit)->values();
    }

    /**
     * @return Collection<int, array{anchor: Product, companion: Product, based_on_orders: bool}>
     */
    public function getFrequentlyBoughtTogether(?User $user, int $limit = 2): Collection
    {
        $purchasedProductIds = $this->getPurchasedProductIds($user);

        $pairs = DB::table('order_items as anchor_items')
            ->join('order_items as companion_items', 'anchor_items.order_id', '=', 'companion_items.order_id')
            ->join('orders', 'orders.id', '=', 'anchor_items.order_id')
            ->join('products as anchors', 'anchors.id', '=', 'anchor_items.product_id')
            ->join('products as companions', 'companions.id', '=', 'companion_items.product_id')
            ->whereColumn('anchor_items.product_id', '!=', 'companion_items.product_id')
            ->when($purchasedProductIds->isEmpty(), fn (Builder $query) => $query->whereColumn('anchor_items.product_id', '<', 'companion_items.product_id'))
            ->where('orders.status', '!=', 'cancelled')
            ->where('anchors.is_active', true)
            ->where('anchors.stock', '>', 0)
            ->where('companions.is_active', true)
            ->where('companions.stock', '>', 0)
            ->when($purchasedProductIds->isNotEmpty(), fn (Builder $query) => $query->whereIn('anchor_items.product_id', $purchasedProductIds))
            ->select('anchor_items.product_id as anchor_id', 'companion_items.product_id as companion_id')
            ->selectRaw('COUNT(DISTINCT anchor_items.order_id) as order_count')
            ->groupBy('anchor_items.product_id', 'companion_items.product_id')
            ->orderByDesc('order_count')
            ->limit($limit)
            ->get();

        if ($pairs->isNotEmpty()) {
            $products = Product::query()
                ->with('category')
                ->whereIn('id', $pairs->flatMap(fn ($pair) => [$pair->anchor_id, $pair->companion_id])->unique())
                ->get()
                ->keyBy('id');

            return $pairs
                ->map(fn ($pair): array => [
                    'anchor' => $products->get($pair->anchor_id),
                    'companion' => $products->get($pair->companion_id),
                    'based_on_orders' => true,
                ])
                ->filter(fn (array $pair): bool => $pair['anchor'] && $pair['companion'])
                ->values();
        }

        $products = $this->fallbackBestSellers($limit * 2);
        $fallbackPairs = collect();

        foreach ($products as $index => $product) {
            $companion = $products->first(fn (Product $candidate, int $candidateIndex): bool => (
                $candidateIndex > $index && $candidate->category_id !== $product->category_id
            ));

            if ($companion) {
                $fallbackPairs->push([
                    'anchor' => $product,
                    'companion' => $companion,
                    'based_on_orders' => false,
                ]);
            }

            if ($fallbackPairs->count() === $limit) {
                break;
            }
        }

        return $fallbackPairs;
    }

    private function fallbackBestSellers(
        int $limit,
        ?Collection $excludeIds = null,
        ?Collection $onlyIds = null,
    ): Collection {
        return Product::query()
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->when($excludeIds?->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $excludeIds))
            ->when($onlyIds?->isNotEmpty(), fn ($query) => $query->whereIn('id', $onlyIds))
            ->with('category')
            ->withCount('orderItems')
            ->orderByDesc('order_items_count')
            ->orderBy('name')
            ->take($limit)
            ->get();
    }

    /**
     * @return array<string, float>
     */
    private function productTerms(Product $product): array
    {
        $terms = [];
        $features = [
            [$product->category?->name, 2.0],
            [$product->name, 2.0],
            [$product->description, 1.0],
        ];

        foreach ($features as [$text, $weight]) {
            if (! $text) {
                continue;
            }

            preg_match_all('/[\p{L}\p{N}]+/u', mb_strtolower($text), $matches);

            foreach ($matches[0] as $term) {
                if (mb_strlen($term) < 2 || $this->isStopWord($term)) {
                    continue;
                }

                $terms[$term] = ($terms[$term] ?? 0.0) + $weight;
            }
        }

        return $terms;
    }

    private function isStopWord(string $term): bool
    {
        static $stopWords = [
            'adalah', 'anda', 'atau', 'bagi', 'berbagai', 'dan', 'dari',
            'dengan', 'di', 'ini', 'ke', 'kebutuhan', 'untuk', 'yang',
        ];

        return in_array($term, $stopWords, true);
    }

    /**
     * @param  Collection<int, array<string, float>>  $documentTerms
     * @return array<string, float>
     */
    private function inverseDocumentFrequencies(Collection $documentTerms): array
    {
        $documentCount = $documentTerms->count();
        $documentFrequencies = [];

        foreach ($documentTerms as $terms) {
            foreach (array_keys($terms) as $term) {
                $documentFrequencies[$term] = ($documentFrequencies[$term] ?? 0) + 1;
            }
        }

        $inverseDocumentFrequencies = [];

        foreach ($documentFrequencies as $term => $frequency) {
            $inverseDocumentFrequencies[$term] = log(1 + $documentCount / (1 + $frequency)) + 1;
        }

        return $inverseDocumentFrequencies;
    }

    /**
     * @param  array<string, float>  $terms
     * @param  array<string, float>  $inverseDocumentFrequencies
     * @return array<string, float>
     */
    private function toTfidfVector(array $terms, array $inverseDocumentFrequencies): array
    {
        $vector = [];

        foreach ($terms as $term => $frequency) {
            $vector[$term] = (1 + log($frequency)) * ($inverseDocumentFrequencies[$term] ?? 0.0);
        }

        return $vector;
    }

    /**
     * @param  array<string, float>  $vector
     * @return array<string, float>
     */
    private function normalizeVector(array $vector): array
    {
        $length = sqrt(array_sum(array_map(
            fn (float $value): float => $value ** 2,
            $vector,
        )));

        if ($length === 0.0) {
            return [];
        }

        return array_map(fn (float $value): float => $value / $length, $vector);
    }

    /**
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, array<string, float>>  $productVectors
     * @param  array<string, float>  $profile
     * @return Collection<int, Product>
     */
    private function scoreProducts(Collection $products, Collection $productVectors, array $profile): Collection
    {
        $profileLength = sqrt(array_sum(array_map(
            fn (float $value): float => $value ** 2,
            $profile,
        )));

        if ($profileLength === 0.0) {
            return collect();
        }

        return $products
            ->map(function (Product $product) use ($productVectors, $profile, $profileLength): Product {
                $vector = $productVectors->get($product->id, []);
                $vectorLength = sqrt(array_sum(array_map(
                    fn (float $value): float => $value ** 2,
                    $vector,
                )));

                $dotProduct = 0.0;

                foreach ($vector as $term => $value) {
                    $dotProduct += $value * ($profile[$term] ?? 0.0);
                }

                $product->setAttribute(
                    'similarity_score',
                    $vectorLength === 0.0 ? 0.0 : $dotProduct / ($vectorLength * $profileLength),
                );

                return $product;
            })
            ->filter(fn (Product $product): bool => $product->similarity_score > 0)
            ->sort(function (Product $left, Product $right): int {
                $scoreComparison = $right->similarity_score <=> $left->similarity_score;

                return $scoreComparison !== 0
                    ? $scoreComparison
                    : strcasecmp($left->name, $right->name);
            })
            ->values();
    }
}
