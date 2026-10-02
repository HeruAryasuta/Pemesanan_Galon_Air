<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->with('category')
            ->withCount('orderItems')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->string('search'));
                $query->where(function ($products) use ($search): void {
                    $products->where('name', 'like', "%{$search}%")
                        ->orWhereHas('category', fn ($categories) => $categories->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('category'), fn ($query) => $query->where('category_id', $request->integer('category')))
            ->when($request->input('status') === 'active', fn ($query) => $query->where('is_active', true))
            ->when($request->input('status') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest()
            ->paginate(15)
            ->withQueryString();
        $categories = Category::orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories'));
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
            $validated['slug'] = Str::slug($validated['name']).'-'.uniqid();
            $validated['image_path'] = $this->storeImage($request);
            Product::create($validated);

            return redirect()->route('admin.products.index')->with('success', 'Produk berhasil ditambahkan.');
        } catch (\Throwable $e) {
            Log::error('Gagal membuat produk: '.$e->getMessage());

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
        $validated = $this->validated($request);

        try {
            $oldImage = $product->image_path;
            if ($request->hasFile('image')) {
                $validated['image_path'] = $this->storeImage($request);
            }

            $product->update($validated);
            if (isset($validated['image_path']) && $oldImage && Str::startsWith($oldImage, 'products/')) {
                Storage::disk('public')->delete($oldImage);
            }

            return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diperbarui.');
        } catch (\Throwable $e) {
            Log::error('Gagal update produk #'.$product->id.': '.$e->getMessage());

            return back()->withInput()->with('error', 'Gagal memperbarui produk.');
        }
    }

    public function destroy(Product $product): RedirectResponse
    {
        try {
            if ($product->orderItems()->exists()) {
                return back()->with('error', 'Produk tidak bisa dihapus karena sudah memiliki riwayat pesanan.');
            }

            $imagePath = $product->image_path;
            $product->delete();
            if ($imagePath && Str::startsWith($imagePath, 'products/')) {
                Storage::disk('public')->delete($imagePath);
            }

            return back()->with('success', 'Produk berhasil dihapus.');
        } catch (\Throwable $e) {
            Log::error('Gagal hapus produk #'.$product->id.': '.$e->getMessage());

            return back()->with('error', 'Gagal menghapus produk.');
        }
    }

    public function toggleActive(Product $product): RedirectResponse
    {
        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('success', $product->is_active ? 'Produk berhasil diaktifkan.' : 'Produk berhasil dinonaktifkan.');
    }

    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $path = $request->file('image')->store('products', 'public');
        if ($path === false) {
            throw new \RuntimeException('Gambar produk gagal disimpan.');
        }

        return $path;
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:50'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
