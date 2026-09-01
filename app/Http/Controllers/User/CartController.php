<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(): View
    {
        $cart = session('cart', []);
        $products = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');

        return view('user.cart.index', compact('cart', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $product = Product::findOrFail($validated['product_id']);

            if ($product->stock < $validated['quantity']) {
                return back()->with('error', 'Stok produk tidak mencukupi.');
            }

            $cart = session('cart', []);
            $cart[$product->id] = ($cart[$product->id] ?? 0) + $validated['quantity'];
            session(['cart' => $cart]);

            return back()->with('success', 'Produk berhasil ditambahkan ke keranjang.');
        } catch (\Throwable $e) {
            Log::error('Gagal menambah item ke cart: ' . $e->getMessage());

            return back()->with('error', 'Terjadi kesalahan, silakan coba lagi.');
        }
    }

    public function update(Request $request, int $productId): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $cart = session('cart', []);

        if (! isset($cart[$productId])) {
            return back()->with('error', 'Item tidak ditemukan di keranjang.');
        }

        $cart[$productId] = $validated['quantity'];
        session(['cart' => $cart]);

        return back()->with('success', 'Keranjang diperbarui.');
    }

    public function destroy(int $productId): RedirectResponse
    {
        $cart = session('cart', []);
        unset($cart[$productId]);
        session(['cart' => $cart]);

        return back()->with('success', 'Item dihapus dari keranjang.');
    }
}