<?php

namespace App\Services\Recommendation;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

class RecommendationService
{
    public function getRecommendationsFor(User $user, int $limit = 6): Collection
    {
        $purchasedProductIds = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.user_id', $user->id)
            ->pluck('order_items.product_id')
            ->unique();

        if ($purchasedProductIds->isEmpty()) {
            return $this->fallbackBestSellers($limit);
        }

        $categoryIds = Product::whereIn('id', $purchasedProductIds)->pluck('category_id')->unique();

        $recommended = Product::query()
            ->whereIn('category_id', $categoryIds)
            ->whereNotIn('id', $purchasedProductIds)
            ->where('is_active', true)
            ->inRandomOrder()
            ->take($limit)
            ->get();

        return $recommended->isEmpty() ? $this->fallbackBestSellers($limit) : $recommended;
    }

    private function fallbackBestSellers(int $limit): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->withCount('orderItems')
            ->orderByDesc('order_items_count')
            ->take($limit)
            ->get();
    }
}