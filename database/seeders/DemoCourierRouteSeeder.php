<?php

namespace Database\Seeders;

use App\Models\Courier;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Product;
use App\Models\Route as DeliveryRoute;
use App\Models\User;
use App\Services\Routing\OsrmTspService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class DemoCourierRouteSeeder extends Seeder
{
    private const COURIER_EMAIL = 'kurir.demo.20261003@example.test';

    private const CUSTOMER_EMAIL = 'pelanggan.demo.rute@example.test';

    private const DEMO_ORDER_NOTE = 'Pesanan dummy untuk demo alamat dan rute kurir.';

    public function run(OsrmTspService $osrmTspService): void
    {
        $courier = Courier::query()
            ->whereHas('user', fn ($query) => $query->where('email', self::COURIER_EMAIL))
            ->first();

        if ($courier === null) {
            throw new RuntimeException('Akun kurir demo tidak ditemukan. Buat akun kurir demo terlebih dahulu.');
        }

        $customer = User::query()->where('email', self::CUSTOMER_EMAIL)->first();
        if ($customer !== null && ($customer->name !== 'Pelanggan Demo Rute' || $customer->role !== 'customer')) {
            throw new RuntimeException('Email pelanggan demo sudah digunakan akun lain; tidak ada data yang diubah.');
        }

        if ($customer === null) {
            $customer = User::query()->create([
                'name' => 'Pelanggan Demo Rute',
                'email' => self::CUSTOMER_EMAIL,
                'phone' => '081234567891',
                'email_verified_at' => now(),
                'password' => Str::random(48),
            ]);
        }

        $address = $customer->addresses()->firstOrCreate(
            ['label' => 'Rumah Demo Rute'],
            [
                'full_address' => 'Jalan Rungkut Menanggal, Gunung Anyar, Surabaya, Jawa Timur',
                'latitude' => -7.3500,
                'longitude' => 112.7700,
                'is_primary' => true,
            ],
        );

        if ($address->latitude === null || $address->longitude === null) {
            throw new RuntimeException('Alamat demo sudah ada tetapi belum mempunyai koordinat.');
        }

        $product = Product::query()->where('slug', 'galon-aqua-19-liter')->first();
        if ($product === null || ! $product->is_active) {
            throw new RuntimeException('Produk demo Galon Aqua 19L tidak tersedia atau tidak aktif.');
        }

        $order = Order::query()->firstOrCreate(
            [
                'user_id' => $customer->id,
                'notes' => self::DEMO_ORDER_NOTE,
            ],
            [
                'address_id' => $address->id,
                'status' => 'confirmed',
                'total_price' => $product->price + (int) config('delivery.flat_fee'),
                'delivery_fee' => (int) config('delivery.flat_fee'),
                'payment_method' => 'cod',
                'payment_status' => 'unpaid',
                'ordered_at' => now(),
            ],
        );

        if ((int) $order->address_id !== (int) $address->id || $order->status !== 'confirmed') {
            throw new RuntimeException('Pesanan demo sudah berubah; tidak ada data yang ditimpa.');
        }

        $order->items()->firstOrCreate(
            ['product_id' => $product->id],
            [
                'quantity' => 1,
                'price_snapshot' => $product->price,
            ],
        );

        $delivery = Delivery::query()->firstOrCreate(
            ['order_id' => $order->id],
            ['courier_id' => $courier->id, 'status' => 'assigned'],
        );

        if ($delivery->status !== 'pending' && $delivery->status !== 'assigned') {
            throw new RuntimeException('Pengantaran demo sudah selesai atau tidak lagi dapat ditugaskan.');
        }

        if ($delivery->routeStop()->exists()) {
            $this->command?->info('Rute demo kurir sudah tersedia; tidak ada data yang diubah.');

            return;
        }

        if ($courier->routes()->whereDate('route_date', now()->toDateString())->exists()) {
            throw new RuntimeException('Kurir sudah memiliki rute hari ini. Pengantaran demo tidak ditambahkan agar rute aktifnya tidak terganggu.');
        }

        $optimizedRoute = $osrmTspService->optimize(collect([
            $delivery->load('order.address'),
        ]));

        DB::transaction(function () use ($courier, $delivery, $optimizedRoute, $order): void {
            $lockedCourier = Courier::query()->whereKey($courier->id)->lockForUpdate()->firstOrFail();
            $lockedDelivery = Delivery::query()->whereKey($delivery->id)->lockForUpdate()->firstOrFail();
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedDelivery->routeStop()->exists()
                || ! in_array($lockedDelivery->status, ['pending', 'assigned'], true)
                || $lockedOrder->status !== 'confirmed'
                || ($lockedDelivery->courier_id !== null && (int) $lockedDelivery->courier_id !== (int) $lockedCourier->id)) {
                throw new RuntimeException('Status data demo berubah saat rute sedang dibuat; silakan jalankan seeder kembali.');
            }

            $route = DeliveryRoute::query()->create([
                'courier_id' => $lockedCourier->id,
                'route_date' => now()->toDateString(),
                'status' => 'planned',
                'total_distance_meters' => $optimizedRoute['distance_meters'],
                'total_duration_seconds' => $optimizedRoute['duration_seconds'],
                'route_geometry' => $optimizedRoute['geometry'],
            ]);

            $route->stops()->create([
                'delivery_id' => $lockedDelivery->id,
                'visit_order' => 1,
                'distance_from_previous_meters' => $optimizedRoute['segments'][0]['distance_meters'],
            ]);

            $lockedDelivery->update([
                'courier_id' => $lockedCourier->id,
                'status' => 'assigned',
            ]);
            $lockedCourier->update(['is_available' => false]);
        });

        $this->command?->info('Demo route assigned: order #'.$order->id.', delivery #'.$delivery->id.'.');
    }
}
