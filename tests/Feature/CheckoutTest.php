<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

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
        $address = $this->createAddress($user, ['is_primary' => true]);

        $this->actingAs($user)
            ->withSession(['cart' => [$product->id => 2]])
            ->get(route('user.checkout.index'))
            ->assertOk()
            ->assertSee('Pilih alamat pengantaran')
            ->assertSee($address->label)
            ->assertSee($address->full_address)
            ->assertSee('Galon Aqua')
            ->assertSee('Rp42.000')
            ->assertSee('Biaya pengantaran')
            ->assertSee('Rp5.000')
            ->assertSee('Rp47.000')
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
            ->assertSee('Tambahkan alamat pengantaran terlebih dahulu')
            ->assertSee(route('user.addresses.store'), false)
            ->assertSee('Simpan alamat')
            ->assertSee('Tambahkan alamat untuk lanjut');
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
        $this->assertSame(47000, $order->total_price);
        $this->assertSame(5000, $order->delivery_fee);
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
            ->assertSee('Ongkir Rp5.000')
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
        config(['delivery.flat_fee' => 8500]);
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
        $this->assertSame(29500, $order->total_price);
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
            'is_primary' => false,
        ], $attributes));
    }
}
