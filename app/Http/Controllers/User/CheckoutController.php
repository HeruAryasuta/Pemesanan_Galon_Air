<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $cart = session('cart', []);

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Keranjang Anda kosong.');
        }

        $products = Product::whereIn('id', array_keys($cart))->get();
        $addresses = $request->user()->addresses;

        return view('user.checkout.index', compact('products', 'cart', 'addresses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'address_id' => ['required', 'exists:addresses,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $cart = session('cart', []);

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Keranjang Anda kosong.');
        }

        // Pastikan address memang milik user yang login, bukan milik user lain
        $address = Address::where('id', $validated['address_id'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $address) {
            return back()->with('error', 'Alamat tidak valid.');
        }

        try {
            $order = DB::transaction(function () use ($request, $validated, $cart, $address) {
                $products = Product::whereIn('id', array_keys($cart))->lockForUpdate()->get();

                $totalPrice = 0;
                foreach ($products as $product) {
                    $qty = $cart[$product->id];

                    if ($product->stock < $qty) {
                        throw new \RuntimeException("Stok {$product->name} tidak mencukupi.");
                    }

                    $totalPrice += $product->price * $qty;
                }

                $order = Order::create([
                    'user_id' => $request->user()->id,
                    'address_id' => $address->id,
                    'status' => 'pending',
                    'total_price' => $totalPrice,
                    'notes' => $validated['notes'] ?? null,
                    'ordered_at' => now(),
                ]);

                foreach ($products as $product) {
                    $qty = $cart[$product->id];

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $qty,
                        'price_snapshot' => $product->price,
                    ]);

                    $product->decrement('stock', $qty);
                }

                Delivery::create([
                    'order_id' => $order->id,
                    'status' => 'pending',
                ]);

                return $order;
            });

            session()->forget('cart');

            return redirect()->route('orders.index')->with('success', 'Pesanan berhasil dibuat.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Checkout gagal: ' . $e->getMessage());

            return back()->with('error', 'Terjadi kesalahan saat memproses pesanan. Silakan coba lagi.');
        }
    }
}