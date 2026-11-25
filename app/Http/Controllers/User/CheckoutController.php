<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ServiceZone;
use App\Services\DeliveryFeeCalculator;
use App\Support\ServiceZoneMatcher;
use Illuminate\Http\JsonResponse;
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
            'cartHasUnavailableItems',
            'bankTransfer',
            'bankTransferConfigured',
        ));
    }

    public function shippingQuote(Request $request, DeliveryFeeCalculator $deliveryFeeCalculator): JsonResponse
    {
        $validated = $request->validate([
            'address_id' => [
                'required',
                $request->input('address_id') === 'new'
                    ? Rule::in(['new'])
                    : Rule::exists('addresses', 'id')
                        ->where('user_id', $request->user()->id)
                        ->where('is_saved', true),
            ],
            'latitude' => ['required_if:address_id,new', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['required_if:address_id,new', 'nullable', 'numeric', 'between:-180,180'],
        ]);
        $cart = $request->session()->get('cart', []);
        if ($cart === []) {
            return response()->json(['message' => 'Keranjang Anda kosong.'], 422);
        }

        if ($validated['address_id'] === 'new') {
            $latitude = (float) $validated['latitude'];
            $longitude = (float) $validated['longitude'];
        } else {
            $address = $request->user()->addresses()
                ->where('is_saved', true)
                ->findOrFail($validated['address_id']);
            if ($address->latitude === null || $address->longitude === null) {
                return response()->json([
                    'message' => 'Alamat ini belum memiliki titik peta. Pilih alamat lain atau gunakan alamat baru dan tentukan titiknya.',
                ], 422);
            }
            $latitude = (float) $address->latitude;
            $longitude = (float) $address->longitude;
        }

        try {
            return response()->json($deliveryFeeCalculator->quote(
                (int) array_sum($cart),
                $latitude,
                $longitude,
            ));
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function store(Request $request, DeliveryFeeCalculator $deliveryFeeCalculator): RedirectResponse
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
            'latitude' => [$isNewAddress ? 'required' : 'nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => [$isNewAddress ? 'required' : 'nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
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

        $deliveryLatitude = $isNewAddress ? $validated['latitude'] ?? null : $address->latitude;
        $deliveryLongitude = $isNewAddress ? $validated['longitude'] ?? null : $address->longitude;
        if ($deliveryLatitude === null || $deliveryLongitude === null) {
            return back()
                ->withErrors(['address_id' => 'Titik peta alamat wajib tersedia untuk menghitung ongkos kirim.'])
                ->withInput();
        }

        try {
            $deliveryQuote = $deliveryFeeCalculator->quote(
                (int) array_sum($cart),
                (float) $deliveryLatitude,
                (float) $deliveryLongitude,
            );
        } catch (\RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        try {
            $order = DB::transaction(function () use ($request, $validated, $cart, $address, $isNewAddress, $serviceZoneId, $deliveryQuote) {
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

                $deliveryFee = $deliveryQuote['delivery_fee'];
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
