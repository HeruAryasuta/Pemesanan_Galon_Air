<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ServiceZone;
use App\Support\ServiceZoneMatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function index(Request $request, ServiceZoneMatcher $serviceZoneMatcher): View|RedirectResponse
    {
        $cart = session('cart', []);

        if (empty($cart)) {
            return redirect()->route('user.cart.index')->with('error', 'Keranjang Anda kosong.');
        }

        $products = Product::with('category')->whereIn('id', array_keys($cart))->get();

        if ($products->count() !== count($cart)) {
            return redirect()->route('user.cart.index')->with('error', 'Ada produk di keranjang yang sudah tidak tersedia. Silakan periksa kembali keranjang Anda.');
        }

        $addresses = $request->user()->addresses()
            ->where('is_saved', true)
            ->with('serviceZone')
            ->orderByDesc('is_primary')
            ->latest()
            ->get();
        $serviceZones = ServiceZone::orderBy('id')->get();
        $addresses->each(function (Address $address) use ($serviceZoneMatcher): void {
            if (! $address->service_zone_id) {
                $address->setAttribute(
                    'suggested_service_zone_id',
                    $serviceZoneMatcher->matchAddressText($address->full_address)?->id,
                );
            }
        });
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
            'serviceZones',
            'deliveryFee',
            'cartHasUnavailableItems',
            'bankTransfer',
            'bankTransferConfigured',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $isNewAddress = $request->input('address_id') === 'new';
        $validated = $request->validate([
            'address_id' => [
                'required',
                $isNewAddress
                    ? Rule::in(['new'])
                    : Rule::exists('addresses', 'id')
                        ->where('user_id', $request->user()->id)
                        ->where('is_saved', true),
            ],
            'label' => ['nullable', 'string', 'max:100'],
            'full_address' => [$isNewAddress ? 'required' : 'nullable', 'string', 'max:1000'],
            'service_zone_id' => [$isNewAddress ? 'required' : 'nullable', 'integer', 'exists:service_zones,id'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'is_primary' => ['nullable', 'boolean'],
            'save_address' => ['nullable', 'boolean'],
            'service_zone_ids' => ['sometimes', 'array'],
            'service_zone_ids.*' => ['nullable', 'integer', 'exists:service_zones,id'],
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

        $address = $isNewAddress
            ? null
            : $request->user()->addresses()->where('is_saved', true)->findOrFail($validated['address_id']);
        $serviceZoneId = $isNewAddress
            ? $validated['service_zone_id']
            : ($address->service_zone_id ?? ($validated['service_zone_ids'][$address->id] ?? null));

        if (! $serviceZoneId) {
            return back()
                ->withErrors(['service_zone_ids.'.$address->id => 'Pilih wilayah pengantaran untuk alamat ini.'])
                ->withInput();
        }

        try {
            $order = DB::transaction(function () use ($request, $validated, $cart, $address, $isNewAddress, $serviceZoneId) {
                if ($isNewAddress) {
                    $saveAddress = (bool) ($validated['save_address'] ?? false);

                    if ($saveAddress && ($validated['is_primary'] ?? false)) {
                        $request->user()->addresses()->update(['is_primary' => false]);
                    }

                    $orderAddress = $request->user()->addresses()->create([
                        'label' => $validated['label'] ?? null,
                        'full_address' => $validated['full_address'],
                        'service_zone_id' => $serviceZoneId,
                        'latitude' => $validated['latitude'] ?? null,
                        'longitude' => $validated['longitude'] ?? null,
                        'is_primary' => $saveAddress && ($validated['is_primary'] ?? false),
                        'is_saved' => $saveAddress,
                    ]);
                } else {
                    $orderAddress = $request->user()->addresses()
                        ->where('is_saved', true)
                        ->whereKey($address->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (! $orderAddress->service_zone_id) {
                        $orderAddress->service_zone_id = $serviceZoneId;
                        $orderAddress->save();
                    }
                }

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
                    'address_id' => $orderAddress->id,
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
            Log::error('Checkout gagal: '.$e->getMessage());

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
