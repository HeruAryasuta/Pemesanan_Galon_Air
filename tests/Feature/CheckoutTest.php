<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ServiceZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private bool $osrmNoRoute = false;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.osrm.url' => 'http://osrm.test',
            'routing.depot.latitude' => -7.3414987,
            'routing.depot.longitude' => 112.7677984,
        ]);
        Http::fake([
            'http://osrm.test/route/v1/driving/*' => function () {
                if ($this->osrmNoRoute) {
                    return Http::response(['code' => 'NoRoute']);
                }

                return Http::response([
                    'code' => 'Ok',
                    'routes' => [[
                        'distance' => 10001,
                        'duration' => 1200,
                        'legs' => [
                            ['distance' => 5000, 'duration' => 600],
                            ['distance' => 5001, 'duration' => 600],
                        ],
                    ]],
                ]);
            },
        ]);
    }

    public function test_checkout_requires_authentication(): void
    {
        $this->get(route('user.checkout.index'))
            ->assertRedirect(route('login'));
    }

    public function test_empty_cart_redirects_to_cart(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('user.checkout.index'))
            ->assertRedirect(route('user.cart.index'))
            ->assertSessionHas('error', 'Keranjang Anda kosong.');
    }

    public function test_checkout_shows_delivery_addresses_and_order_summary(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $zone = ServiceZone::where('code', 'WIL-05')->firstOrFail();
        $address = $this->createAddress($user, [
            'is_primary' => true,
            'service_zone_id' => $zone->id,
        ]);

        $this->actingAs($user)
            ->withSession(['cart' => [$product->id => 2]])
            ->get(route('user.checkout.index'))
            ->assertOk()
            ->assertSee('Pilih alamat pengantaran')
            ->assertSee($address->label)
            ->assertSee($address->full_address)
            ->assertSee($zone->name)
            ->assertSee($zone->area_description)
            ->assertSee('id="checkout-new-service-zone"', false)
            ->assertSee('name="service_zone_id"', false)
            ->assertSee('value="'.$zone->id.'"', false)
            ->assertSee('Galon Aqua')
            ->assertSee('Rp42.000')
            ->assertSee('Diskon pembelian pertama (20%)')
            ->assertSee('Hak diskon baru terpakai setelah pesanan selesai diantar')
            ->assertSee('data-order-discount="8400"', false)
            ->assertSee('Biaya pengantaran')
            ->assertSee('Hitung...')
            ->assertSee('data-shipping-quote-url', false)
            ->assertSee('Metode pembayaran')
            ->assertSee('Titik lokasi pada peta')
            ->assertSee('name="latitude"', false)
            ->assertSee('name="longitude"', false)
            ->assertSee('COD — Bayar saat diterima')
            ->assertSee('Bayar langsung kepada kurir setelah pesanan sampai.')
            ->assertSee('Buat pesanan')
            ->assertSee(route('user.checkout.store'), false);
    }

    public function test_checkout_offers_address_creation_when_user_has_no_addresses(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        $this->actingAs($user)
            ->withSession(['cart' => [$product->id => 1]])
            ->get(route('user.checkout.index'))
            ->assertOk()
            ->assertSee('Belum ada alamat tersimpan')
            ->assertSee('id="checkout-address-new"', false)
            ->assertSee('name="save_address"', false)
            ->assertSee('Buat pesanan');
    }

    public function test_customer_can_place_order_with_a_new_address_without_saving_it_first(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $zone = ServiceZone::where('code', 'WIL-05')->firstOrFail();

        $this->actingAs($user)
            ->withSession(['cart' => [$product->id => 1]])
            ->post(route('user.checkout.store'), [
                'address_id' => 'new',
                'label' => 'Rumah',
                'full_address' => 'Jl. Gunung Anyar No. 10, Surabaya',
                'service_zone_id' => $zone->id,
                'latitude' => -7.35,
                'longitude' => 112.77,
                'payment_method' => 'cod',
            ])
            ->assertRedirect(route('user.orders.index'))
            ->assertSessionHas('success', 'Pesanan berhasil dibuat.');

        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'label' => 'Rumah',
            'service_zone_id' => $zone->id,
            'is_saved' => false,
        ]);

        $address = Address::sole();
        $this->assertSame($address->id, Order::sole()->address_id);

        $this->actingAs($user)
            ->get(route('user.addresses.index'))
            ->assertOk()
            ->assertDontSee('Jl. Gunung Anyar No. 10, Surabaya');
    }

    public function test_customer_can_save_a_new_checkout_address_for_future_use(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $zone = ServiceZone::where('code', 'WIL-05')->firstOrFail();

        $this->actingAs($user)
            ->withSession(['cart' => [$product->id => 1]])
            ->post(route('user.checkout.store'), [
                'address_id' => 'new',
                'label' => 'Rumah',
                'full_address' => 'Jl. Gunung Anyar No. 10, Surabaya',
                'service_zone_id' => $zone->id,
                'latitude' => -7.35,
                'longitude' => 112.77,
                'save_address' => '1',
                'is_primary' => '1',
                'payment_method' => 'cod',
            ])
            ->assertRedirect(route('user.orders.index'));

        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'service_zone_id' => $zone->id,
            'is_saved' => true,
            'is_primary' => true,
        ]);
    }

    public function test_checkout_suggests_and_saves_a_region_for_a_legacy_address(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $address = $this->createAddress($user, [
            'full_address' => 'Jl. Keputih, Kecamatan Sukolilo, Surabaya',
            'is_primary' => true,
            'service_zone_id' => null,
        ]);
        $zone = ServiceZone::where('code', 'WIL-06')->firstOrFail();

        $this->actingAs($user)
            ->withSession(['cart' => [$product->id => 1]])
            ->get(route('user.checkout.index'))
            ->assertOk()
            ->assertSee('id="checkout-address-zone-'.$address->id.'"', false)
            ->assertSee('value="'.$zone->id.'" selected', false);

        $this->withSession(['cart' => [$product->id => 1]])
            ->post(route('user.checkout.store'), [
                'address_id' => $address->id,
                'service_zone_ids' => [$address->id => $zone->id],
                'payment_method' => 'cod',
            ])
            ->assertRedirect(route('user.orders.index'))
            ->assertSessionHas('success', 'Pesanan berhasil dibuat.');

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'service_zone_id' => $zone->id,
        ]);
    }

    public function test_checkout_requires_a_region_for_an_unconfigured_saved_address(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $address = $this->createAddress($user, ['service_zone_id' => null]);

        $this->actingAs($user)
            ->withSession(['cart' => [$product->id => 1]])
            ->post(route('user.checkout.store'), [
                'address_id' => $address->id,
                'payment_method' => 'cod',
            ])
            ->assertSessionHasErrors('service_zone_ids.'.$address->id);

        $this->assertSame(0, Order::count());
    }

    public function test_customer_can_create_an_order_from_checkout(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(['stock' => 8]);
        $address = $this->createAddress($user, ['is_primary' => true]);

        $this->actingAs($user)
            ->withSession(['cart' => [$product->id => 2]])
            ->post(route('user.checkout.store'), [
                'address_id' => $address->id,
                'payment_method' => 'cod',
                'notes' => 'Hubungi saat tiba.',
            ])
            ->assertRedirect(route('user.orders.index'))
            ->assertSessionHas('success', 'Pesanan berhasil dibuat.')
            ->assertSessionMissing('cart');

        $order = Order::with('items')->sole();

        $this->assertSame($user->id, $order->user_id);
        $this->assertSame($address->id, $order->address_id);
        $this->assertSame(39100, $order->total_price);
        $this->assertSame(8400, $order->discount_amount);
        $this->assertSame(5500, $order->delivery_fee);
        $this->assertSame('cod', $order->payment_method);
        $this->assertSame('Hubungi saat tiba.', $order->notes);
        $this->assertSame(2, $order->items->sole()->quantity);
        $this->assertSame(21000, $order->items->sole()->price_snapshot);
        $this->assertSame(6, $product->fresh()->stock);
        $this->assertDatabaseHas('deliveries', [
            'order_id' => $order->id,
            'status' => 'pending',
        ]);

        $this->get(route('user.orders.index'))
            ->assertOk()
            ->assertSee('Pesanan berhasil dibuat.')
            ->assertSee('Galon Aqua')
            ->assertSee('Ongkir Rp5.500')
            ->assertSee('Pembayaran: COD — Bayar saat diterima · Belum dibayar')
            ->assertSee('Menunggu konfirmasi')
            ->assertSee('Hubungi saat tiba.');
    }

    public function test_checkout_rejects_unsupported_payment_methods(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $address = $this->createAddress($user);

        $this->actingAs($user)
            ->withSession(['cart' => [$product->id => 1]])
            ->post(route('user.checkout.store'), [
                'address_id' => $address->id,
                'payment_method' => 'credit_card',
            ])
            ->assertSessionHasErrors('payment_method');

        $this->assertSame(0, Order::count());
    }

    public function test_checkout_accepts_bank_transfer_when_account_is_configured(): void
    {
        config([
            'payments.bank_transfer.bank_name' => 'Bank Contoh',
            'payments.bank_transfer.account_name' => 'Padmatirta Wisesa Depo',
            'payments.bank_transfer.account_number' => '1234567890',
        ]);
        $user = User::factory()->create();
        $product = $this->createProduct();
        $address = $this->createAddress($user);

        $this->actingAs($user)
            ->withSession(['cart' => [$product->id => 1]])
            ->get(route('user.checkout.index'))
            ->assertOk()
            ->assertSee('Transfer bank')
            ->assertSee('1234567890');

        $this->withSession(['cart' => [$product->id => 1]])
            ->post(route('user.checkout.store'), [
                'address_id' => $address->id,
                'payment_method' => 'bank_transfer',
            ])
            ->assertRedirect(route('user.orders.index'));

        $this->assertSame('bank_transfer', Order::sole()->payment_method);
        $this->assertSame('unpaid', Order::sole()->payment_status);
        $this->assertEquals(
            Order::sole()->ordered_at->copy()->addHours(24)->timestamp,
            Order::sole()->payment_deadline_at->timestamp,
        );
        $this->assertSame(1, Order::sole()->items()->count());
    }

    public function test_checkout_does_not_accept_bank_transfer_without_account_configuration(): void
    {
        config([
            'payments.bank_transfer.bank_name' => null,
            'payments.bank_transfer.account_name' => null,
            'payments.bank_transfer.account_number' => null,
        ]);
        $user = User::factory()->create();
        $product = $this->createProduct();
        $address = $this->createAddress($user);

        $this->actingAs($user)
            ->withSession(['cart' => [$product->id => 1]])
            ->post(route('user.checkout.store'), [
                'address_id' => $address->id,
                'payment_method' => 'bank_transfer',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Pembayaran transfer belum tersedia. Silakan hubungi admin.');

        $this->assertSame(0, Order::count());
    }

    public function test_checkout_uses_the_configured_delivery_fee(): void
    {
        config(['delivery.minimum_fee' => 8500]);
        $user = User::factory()->create();
        $product = $this->createProduct(['stock' => 8]);
        $address = $this->createAddress($user);

        $this->actingAs($user)
            ->withSession(['cart' => [$product->id => 1]])
            ->post(route('user.checkout.store'), [
                'address_id' => $address->id,
                'payment_method' => 'cod',
            ])
            ->assertRedirect(route('user.orders.index'));

        $order = Order::sole();

        $this->assertSame(8500, $order->delivery_fee);
        $this->assertSame(25300, $order->total_price);
        $this->assertSame(4200, $order->discount_amount);
    }

    public function test_cancelled_orders_do_not_consume_first_purchase_discount(): void
    {
        $user = User::factory()->create();
        $address = $this->createAddress($user);
        $product = $this->createProduct();
        Order::create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'status' => 'cancelled',
            'total_price' => 26000,
            'discount_amount' => 4200,
            'delivery_fee' => 5000,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
        ]);

        $this->actingAs($user)
            ->withSession(['cart' => [$product->id => 1]])
            ->post(route('user.checkout.store'), [
                'address_id' => $address->id,
                'payment_method' => 'cod',
            ])
            ->assertRedirect(route('user.orders.index'));

        $order = Order::latest('id')->firstOrFail();
        $this->assertSame(4200, $order->discount_amount);
        $this->assertSame(22300, $order->total_price);
    }

    public function test_active_orders_reserve_first_purchase_promo_and_cancellation_returns_right(): void
    {
        $user = User::factory()->create();
        $address = $this->createAddress($user);
        $product = $this->createProduct();
        $promoOrder = Order::create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'status' => 'pending',
            'total_price' => 21800,
            'discount_amount' => 4200,
            'delivery_fee' => 5000,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
        ]);

        $this->actingAs($user)
            ->withSession(['cart' => [$product->id => 1]])
            ->post(route('user.checkout.store'), [
                'address_id' => $address->id,
                'payment_method' => 'cod',
            ])
            ->assertRedirect(route('user.orders.index'));

        $secondOrder = Order::latest('id')->firstOrFail();
        $this->assertSame(0, $secondOrder->discount_amount);

        $promoOrder->update(['status' => 'cancelled']);
        $secondOrder->update(['status' => 'cancelled']);
        $this->withSession(['cart' => [$product->id => 1]])
            ->post(route('user.checkout.store'), [
                'address_id' => $address->id,
                'payment_method' => 'cod',
            ])
            ->assertRedirect(route('user.orders.index'));

        $thirdOrder = Order::latest('id')->firstOrFail();
        $this->assertSame(4200, $thirdOrder->discount_amount);
    }

    public function test_uncompleted_order_holds_promo_but_completed_order_consumes_it(): void
    {
        $user = User::factory()->create();
        $address = $this->createAddress($user);
        $product = $this->createProduct();
        $order = Order::create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'status' => 'pending',
            'total_price' => 26000,
            'delivery_fee' => 5000,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
        ]);

        $this->actingAs($user)
            ->withSession(['cart' => [$product->id => 1]])
            ->get(route('user.checkout.index'))
            ->assertOk()
            ->assertSee('data-order-discount="0"', false)
            ->assertSee('Hak promo ditahan selama ada pesanan yang belum selesai');

        $order->update(['status' => 'delivered']);

        $this->withSession(['cart' => [$product->id => 1]])
            ->get(route('user.checkout.index'))
            ->assertOk()
            ->assertSee('data-order-discount="0"', false)
            ->assertSee('Promo pembelian pertama sudah digunakan setelah pesanan sebelumnya selesai diantar.');
    }

    public function test_checkout_does_not_apply_first_purchase_discount_to_returning_customers(): void
    {
        $user = User::factory()->create();
        $address = $this->createAddress($user);
        $product = $this->createProduct();
        Order::create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'status' => 'delivered',
            'total_price' => 26000,
            'delivery_fee' => 5000,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
        ]);

        $this->actingAs($user)
            ->withSession(['cart' => [$product->id => 1]])
            ->post(route('user.checkout.store'), [
                'address_id' => $address->id,
                'payment_method' => 'cod',
            ])
            ->assertRedirect(route('user.orders.index'));

        $order = Order::latest('id')->firstOrFail();
        $this->assertSame(0, $order->discount_amount);
        $this->assertSame(26500, $order->total_price);
    }

    public function test_customer_can_cancel_before_dispatch_and_restore_stock_without_changing_order_address(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(['stock' => 8]);
        $address = $this->createAddress($user);

        $this->actingAs($user)
            ->withSession(['cart' => [$product->id => 2]])
            ->post(route('user.checkout.store'), [
                'address_id' => $address->id,
                'payment_method' => 'cod',
            ])
            ->assertRedirect(route('user.orders.index'));

        $order = Order::with('delivery')->sole();
        $this->assertSame(6, $product->fresh()->stock);
        $this->assertSame('Rumah', $order->delivery_address_label);
        $this->assertSame('Jl. Gunung Anyar No. 10, Surabaya', $order->delivery_address);
        $this->assertSame(-7.35, (float) $order->delivery_latitude);

        $address->update([
            'label' => 'Rumah baru',
            'full_address' => 'Alamat yang diperbarui',
            'latitude' => -7.36,
            'longitude' => 112.78,
        ]);
        $order->refresh();
        $this->assertSame('Jl. Gunung Anyar No. 10, Surabaya', $order->delivery_address);
        $this->assertSame(-7.35, (float) $order->delivery_latitude);

        $this->post(route('user.orders.cancel', $order), ['reason' => 'Salah memilih jumlah'])
            ->assertRedirect(route('user.orders.show', $order))
            ->assertSessionHas('success', 'Pesanan berhasil dibatalkan dan stok telah dikembalikan.');

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('cancelled', $order->fresh()->payment_status);
        $this->assertSame('Salah memilih jumlah', $order->fresh()->cancellation_reason);
        $this->assertNotNull($order->fresh()->stock_restored_at);
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertSame('failed', $order->delivery->fresh()->status);

        $this->post(route('user.orders.cancel', $order), [])
            ->assertSessionHasErrors('order');
        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_customer_cannot_cancel_after_the_courier_starts_delivery(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(['stock' => 8]);
        $address = $this->createAddress($user);

        $this->actingAs($user)
            ->withSession(['cart' => [$product->id => 1]])
            ->post(route('user.checkout.store'), [
                'address_id' => $address->id,
                'payment_method' => 'cod',
            ])
            ->assertRedirect(route('user.orders.index'));

        $order = Order::with('delivery')->sole();
        $order->update(['status' => 'processing']);
        $order->delivery->update(['status' => 'in_transit']);

        $this->post(route('user.orders.cancel', $order), [])
            ->assertSessionHasErrors('order');

        $this->assertSame('processing', $order->fresh()->status);
        $this->assertSame(7, $product->fresh()->stock);
    }

    public function test_checkout_shipping_quote_uses_round_trip_distance_and_smallest_suitable_vehicle(): void
    {
        $user = User::factory()->create();
        $address = $this->createAddress($user);
        $product = $this->createProduct(['stock' => 40]);

        foreach ([
            1 => ['Sepeda motor', 5500],
            3 => ['Tosa', 11000],
            11 => ['Pick up', 11000],
            21 => ['Truck', 22000],
        ] as $quantity => [$vehicleType, $deliveryFee]) {
            $this->actingAs($user)
                ->withSession(['cart' => [$product->id => $quantity]])
                ->postJson(route('user.checkout.shipping-quote'), [
                    'address_id' => $address->id,
                ])
                ->assertOk()
                ->assertJsonPath('distance_meters', 10001)
                ->assertJsonPath('billed_kilometers', 11)
                ->assertJsonPath('vehicle_type', $vehicleType)
                ->assertJsonPath('delivery_fee', $deliveryFee);
        }

        Http::assertSent(function (HttpRequest $request): bool {
            $path = rawurldecode((string) parse_url($request->url(), PHP_URL_PATH));

            return str_ends_with($path, '112.7677984,-7.3414987;112.7700000,-7.3500000;112.7677984,-7.3414987');
        });
    }

    public function test_checkout_shipping_quote_uses_per_kilometer_rate_above_minimum(): void
    {
        config(['delivery.minimum_fee' => 5000]);
        Http::fake([
            'http://osrm.test/route/v1/driving/*' => Http::response([
                'code' => 'Ok',
                'routes' => [[
                    'distance' => 10001,
                    'duration' => 1200,
                    'legs' => [
                        ['distance' => 5000, 'duration' => 600],
                        ['distance' => 5001, 'duration' => 600],
                    ],
                ]],
            ]),
        ]);
        $user = User::factory()->create();
        $address = $this->createAddress($user);
        $product = $this->createProduct();

        $this->actingAs($user)
            ->withSession(['cart' => [$product->id => 1]])
            ->postJson(route('user.checkout.shipping-quote'), [
                'address_id' => $address->id,
            ])
            ->assertOk()
            ->assertJsonPath('distance_meters', 10001)
            ->assertJsonPath('billed_kilometers', 11)
            ->assertJsonPath('rate_per_km', 500)
            ->assertJsonPath('delivery_fee', 5500);
    }

    public function test_checkout_requires_valid_coordinates_and_osrm_route_before_creating_order(): void
    {
        $this->osrmNoRoute = true;
        $user = User::factory()->create();
        $product = $this->createProduct();
        $address = $this->createAddress($user);

        $this->actingAs($user)
            ->withSession(['cart' => [$product->id => 1]])
            ->post(route('user.checkout.store'), [
                'address_id' => $address->id,
                'payment_method' => 'cod',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'OSRM tidak dapat menghitung jarak pulang-pergi dari depo ke alamat.');

        $this->assertSame(0, Order::count());
    }

    public function test_checkout_rejects_an_address_owned_by_another_user(): void
    {
        $user = User::factory()->create();
        $otherUserAddress = $this->createAddress(User::factory()->create());
        $product = $this->createProduct();

        $this->actingAs($user)
            ->withSession(['cart' => [$product->id => 1]])
            ->post(route('user.checkout.store'), [
                'address_id' => $otherUserAddress->id,
                'payment_method' => 'cod',
            ])
            ->assertSessionHasErrors('address_id');

        $this->assertSame(0, Order::count());
    }

    public function test_checkout_does_not_create_an_order_when_stock_changed(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(['stock' => 1]);
        $address = $this->createAddress($user);

        $this->actingAs($user)
            ->withSession(['cart' => [$product->id => 2]])
            ->post(route('user.checkout.store'), [
                'address_id' => $address->id,
                'payment_method' => 'cod',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Stok Galon Aqua tidak mencukupi.')
            ->assertSessionHas('cart', [$product->id => 2]);

        $this->assertSame(0, Order::count());
        $this->assertSame(0, OrderItem::count());
        $this->assertSame(0, Delivery::count());
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_checkout_rejects_products_removed_from_the_catalog(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $this->createAddress($user);

        $this->actingAs($user)
            ->withSession(['cart' => [$product->id => 1]])
            ->get(route('user.checkout.index'))
            ->assertOk();

        $product->delete();

        $this->get(route('user.checkout.index'))
            ->assertRedirect(route('user.cart.index'))
            ->assertSessionHas('error');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createProduct(array $attributes = []): Product
    {
        $category = Category::create([
            'name' => 'Air Galon',
            'slug' => 'air-galon',
        ]);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Galon Aqua',
            'description' => 'Air minum untuk kebutuhan keluarga.',
            'slug' => 'galon-aqua',
            'price' => 21000,
            'stock' => 10,
            'unit' => 'galon',
            'is_active' => true,
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createAddress(User $user, array $attributes = []): Address
    {
        return $user->addresses()->create(array_merge([
            'label' => 'Rumah',
            'full_address' => 'Jl. Gunung Anyar No. 10, Surabaya',
            'service_zone_id' => ServiceZone::where('code', 'WIL-05')->value('id'),
            'latitude' => -7.35,
            'longitude' => 112.77,
            'is_primary' => false,
        ], $attributes));
    }
}
