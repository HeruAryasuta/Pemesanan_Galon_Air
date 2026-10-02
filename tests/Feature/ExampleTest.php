<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_the_homepage_shows_the_delivery_hero_and_product_categories(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Padmatirta Wisesa Depo')
            ->assertSeeHtml('Pesan Air Galon, LPG &amp; Minuman dari Rumah')
            ->assertSee('Rekomendasi')
            ->assertSee('Cara Pesan')
            ->assertSee('Tentang Kami')
            ->assertSee('aria-current="page"', false)
            ->assertSee('href="' . route('user.products.index') . '"', false)
            ->assertSee('Air Galon')
            ->assertSee('LPG')
            ->assertSee('Air Mineral')
            ->assertSee('Minuman Ringan')
            ->assertSee('Cara Pesan')
            ->assertSee('Pilih produk')
            ->assertSee('Masukkan ke keranjang')
            ->assertSee('Checkout pesanan')
            ->assertSee('Pantau dan terima')
            ->assertSee('images/how-to/browse-products.svg', false)
            ->assertSee('images/how-to/add-to-cart.svg', false)
            ->assertSee('images/how-to/checkout-order.svg', false)
            ->assertSee('images/how-to/track-delivery.svg', false)
            ->assertSee('href="' . route('user.home') . '#cara-pesan"', false);
    }
}
