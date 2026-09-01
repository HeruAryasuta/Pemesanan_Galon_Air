<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->with('category')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->string('search') . '%'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        try {
            $validated['slug'] = Str::slug($validated['name']) . '-' . uniqid();
            Product::create($validated);

            return redirect()->route('admin.products.index')->with('success', 'Produk berhasil ditambahkan.');
        } catch (\Throwable $e) {
            Log::error('Gagal membuat produk: ' . $e->getMessage());

            return back()->withInput()->with('error', 'Gagal menyimpan produk.');
        }
    }

    public function edit(Product $product): View
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validated($request, $product->id);

        try {
            $product->update($validated);

            return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diperbarui.');
        } catch (\Throwable $e) {
            Log::error('Gagal update produk #' . $product->id . ': ' . $e->getMessage());

            return back()->withInput()->with('error', 'Gagal memperbarui produk.');
        }
    }

    public function destroy(Product $product): RedirectResponse
    {
        try {
            if ($product->orderItems()->exists()) {
                return back()->with('error', 'Produk tidak bisa dihapus karena sudah memiliki riwayat pesanan.');
            }

            $product->delete();

            return back()->with('success', 'Produk berhasil dihapus.');
        } catch (\Throwable $e) {
            Log::error('Gagal hapus produk #' . $product->id . ': ' . $e->getMessage());

            return back()->with('error', 'Gagal menghapus produk.');
        }
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:50'],
            'image_path' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);
    }
}