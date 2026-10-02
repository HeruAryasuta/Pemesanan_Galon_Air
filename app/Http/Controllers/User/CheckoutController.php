<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $cart = session('cart', []);

        if (empty($cart)) {
            return redirect()->route('user.cart.index')->with('error', 'Keranjang Anda kosong.');
        }

        $products = Product::with('category')->whereIn('id', array_keys($cart))->get();

        if ($products->count() !== count($cart)) {
            return redirect()->route('user.cart.index')->with('error', 'Ada produk di keranjang yang sudah tidak tersedia. Silakan periksa kembali keranjang Anda.');
        }

        $addresses = $request->user()->addresses()->orderByDesc('is_primary')->latest()->get();
        $deliveryFee = config('delivery.flat_fee');
        $bankTransfer = config('payments.bank_transfer');
        $bankTransferConfigured = filled($bankTransfer['bank_name'])
            && filled($bankTransfer['account_name'])
            && filled($bankTransfer['account_number']);
        $cartHasUnavailableItems = $products->contains(
            fn (Product $product) => ! $product->is_active || $product->stock < $cart[$product->id]
        );

        return view('user.checkout.index', compact(
            'products',
            'cart',
            'addresses',
            'deliveryFee',
            'cartHasUnavailableItems',
            'bankTransfer',
            'bankTransferConfigured',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'address_id' => ['required', Rule::exists('addresses', 'id')->where('user_id', $request->user()->id)],
            'payment_method' => ['required', Rule::in(['cod', 'bank_transfer'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['payment_method'] === 'bank_transfer' && ! $this->bankTransferConfigured()) {
            return back()->withInput()->with('error', 'Pembayaran transfer belum tersedia. Silakan hubungi admin.');
        }

        $cart = session('cart', []);

        if (empty($cart)) {
            return redirect()->route('user.cart.index')->with('error', 'Keranjang Anda kosong.');
        }

        $address = $request->user()->addresses()->findOrFail($validated['address_id']);

        try {
            $order = DB::transaction(function () use ($request, $validated, $cart, $address) {
                $products = Product::whereIn('id', array_keys($cart))->lockForUpdate()->get();

                if ($products->count() !== count($cart)) {
                    throw new \RuntimeException('Ada produk di keranjang yang sudah tidak tersedia. Silakan periksa kembali keranjang Anda.');
                }

                $subtotal = 0;
                foreach ($products as $product) {
                    $qty = $cart[$product->id];

                    if (! $product->is_active || $product->stock < $qty) {
                        throw new \RuntimeException("Stok {$product->name} tidak mencukupi.");
                    }

                    $subtotal += $product->price * $qty;
                }

                $deliveryFee = config('delivery.flat_fee');
                $order = Order::create([
                    'user_id' => $request->user()->id,
                    'address_id' => $address->id,
                    'status' => 'pending',
                    'total_price' => $subtotal + $deliveryFee,
                    'delivery_fee' => $deliveryFee,
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => 'unpaid',
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

            return redirect()->route('user.orders.index')->with('success', 'Pesanan berhasil dibuat.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Checkout gagal: ' . $e->getMessage());

            return back()->with('error', 'Terjadi kesalahan saat memproses pesanan. Silakan coba lagi.');
        }
    }

    private function bankTransferConfigured(): bool
    {
        $bankTransfer = config('payments.bank_transfer');

        return filled($bankTransfer['bank_name'])
            && filled($bankTransfer['account_name'])
            && filled($bankTransfer['account_number']);
    }
}