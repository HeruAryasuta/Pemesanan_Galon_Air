<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'gte:min_price'],
            'in_stock' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:popular,newest,price_asc,price_desc'],
        ]);

        $query = Product::query()->where('is_active', true);

        if (! empty($validated['category'])) {
            $query->where('category_id', $validated['category']);
        }

        if (! empty($validated['search'])) {
            $search = trim($validated['search']);

            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'like', '%' . $search . '%'));
            });
        }

        if (isset($validated['min_price'])) {
            $query->where('price', '>=', $validated['min_price']);
        }

        if (isset($validated['max_price'])) {
            $query->where('price', '<=', $validated['max_price']);
        }

        if (! empty($validated['in_stock'])) {
            $query->where('stock', '>', 0);
        }

        $sort = $validated['sort'] ?? 'popular';

        $query->with('category');

        if ($sort === 'popular') {
            $query->withCount(['orderItems' => function ($orderItems): void {
                $orderItems->whereHas('order', fn ($orders) => $orders->where('status', '!=', 'cancelled'));
            }])->orderByDesc('order_items_count');
        } elseif ($sort === 'price_asc') {
            $query->orderBy('price');
        } elseif ($sort === 'price_desc') {
            $query->orderByDesc('price');
        } else {
            $query->latest();
        }

        $products = $query
            ->paginate(12)
            ->withQueryString();
        $categories = Category::orderBy('name')->get();
        $selectedCategory = isset($validated['category'])
            ? $categories->firstWhere('id', (int) $validated['category'])
            : null;
        $search = $validated['search'] ?? '';
        $minPrice = $validated['min_price'] ?? '';
        $maxPrice = $validated['max_price'] ?? '';
        $inStock = (bool) ($validated['in_stock'] ?? false);

        return view('user.products.index', compact(
            'products',
            'categories',
            'selectedCategory',
            'search',
            'minPrice',
            'maxPrice',
            'inStock',
            'sort',
        ));
    }

    public function show(Product $product): View
    {
        abort_if(! $product->is_active, 404);

        $product->load('category');

        $relatedProducts = Product::query()
            ->with('category')
            ->where('is_active', true)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->latest()
            ->take(4)
            ->get();

        return view('user.products.show', compact('product', 'relatedProducts'));
    }
}