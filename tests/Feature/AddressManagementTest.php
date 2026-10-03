<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AddressManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_address_management_screen_lists_addresses_and_add_form(): void
    {
        $user = User::factory()->create();
        $address = $user->addresses()->create([
            'label' => 'Rumah',
            'full_address' => 'Jl. Gunung Anyar No. 10, Surabaya',
            'is_primary' => true,
        ]);

        $this->actingAs($user)
            ->get(route('user.addresses.index'))
            ->assertOk()
            ->assertSee('Alamat Saya')
            ->assertSee('Rumah')
            ->assertSee($address->full_address)
            ->assertSee('Tambah alamat')
            ->assertSee('Titik lokasi pada peta')
            ->assertSee('Gunakan lokasi saya')
            ->assertSee('data-use-current-location', false)
            ->assertSee('data-location-status', false)
            ->assertSee('name="latitude"', false)
            ->assertSee('name="longitude"', false)
            ->assertSee(route('user.addresses.update', $address), false)
            ->assertSee(route('user.addresses.destroy', $address), false);
    }

    public function test_address_management_screen_requires_authentication(): void
    {
        $this->get(route('user.addresses.index'))
            ->assertRedirect(route('login'));
    }

    public function test_customer_can_reverse_geocode_a_selected_address_point(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/reverse*' => Http::response([
                'name' => 'Toko Contoh',
                'display_name' => 'Toko Contoh, Jalan Rungkut, Surabaya, Jawa Timur, Indonesia',
            ]),
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('user.addresses.reverse-geocode'), [
                'latitude' => '-7.3181234',
                'longitude' => '112.7681234',
            ])
            ->assertOk()
            ->assertJson([
                'full_address' => 'Toko Contoh, Jalan Rungkut, Surabaya, Jawa Timur, Indonesia',
                'label' => 'Toko Contoh',
            ]);

        Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://nominatim.openstreetmap.org/reverse')
            && $request->hasHeader('User-Agent', 'PadmatirtaWisesaDepo/1.0')
            && (float) $request['lat'] === -7.3181234
            && (float) $request['lon'] === 112.7681234);
    }

    public function test_reverse_geocoding_rejects_invalid_coordinates(): void
    {
        Http::fake();

        $this->actingAs(User::factory()->create())
            ->postJson(route('user.addresses.reverse-geocode'), [
                'latitude' => 91,
                'longitude' => 112,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('latitude');

        Http::assertNothingSent();
    }

    public function test_reverse_geocoding_requires_authentication(): void
    {
        $this->postJson(route('user.addresses.reverse-geocode'), [
            'latitude' => -7.3181234,
            'longitude' => 112.7681234,
        ])->assertUnauthorized();
    }

    public function test_customer_can_add_and_edit_their_address(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('user.addresses.store'), [
                'label' => 'Rumah',
                'full_address' => 'Jl. Gunung Anyar No. 10, Surabaya',
                'is_primary' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $address = Address::sole();

        $this->assertTrue($address->is_primary);

        $this->put(route('user.addresses.update', $address), [
            'label' => 'Rumah Baru',
            'full_address' => 'Jl. Rungkut No. 20, Surabaya',
            'is_primary' => '0',
        ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'user_id' => $user->id,
            'label' => 'Rumah Baru',
            'full_address' => 'Jl. Rungkut No. 20, Surabaya',
            'latitude' => null,
            'longitude' => null,
            'is_primary' => false,
        ]);
    }

    public function test_customer_can_save_map_coordinates_when_adding_or_editing_an_address(): void
    {
        $user = User::factory()->create();
        $address = $user->addresses()->create([
            'label' => 'Rumah',
            'full_address' => 'Jl. Gunung Anyar No. 10, Surabaya',
        ]);

        $this->actingAs($user)
            ->put(route('user.addresses.update', $address), [
                'label' => 'Rumah',
                'full_address' => $address->full_address,
                'latitude' => '-7.3181234',
                'longitude' => '112.7681234',
                'is_primary' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'latitude' => -7.3181234,
            'longitude' => 112.7681234,
        ]);

        $this->actingAs($user)
            ->post(route('user.addresses.store'), [
                'label' => 'Kantor',
                'full_address' => 'Jl. Rungkut No. 20, Surabaya',
                'latitude' => '-7.3200000',
                'longitude' => '112.7700000',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'label' => 'Kantor',
            'latitude' => -7.32,
            'longitude' => 112.77,
        ]);
    }

    public function test_address_coordinates_must_be_selected_as_a_pair(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('user.addresses.store'), [
                'label' => 'Rumah',
                'full_address' => 'Jl. Gunung Anyar No. 10, Surabaya',
                'latitude' => '-7.3181234',
            ])
            ->assertSessionHasErrors('longitude');

        $this->assertDatabaseCount('addresses', 0);
    }

    public function test_customer_cannot_edit_or_delete_another_users_address(): void
    {
        $user = User::factory()->create();
        $address = User::factory()->create()->addresses()->create([
            'label' => 'Alamat privat',
            'full_address' => 'Alamat pengguna lain',
        ]);

        $this->actingAs($user)
            ->put(route('user.addresses.update', $address), [
                'full_address' => 'Alamat diubah',
            ])
            ->assertForbidden();

        $this->delete(route('user.addresses.destroy', $address))
            ->assertForbidden();

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'full_address' => 'Alamat pengguna lain',
        ]);
    }

    public function test_customer_can_delete_an_unused_address(): void
    {
        $user = User::factory()->create();
        $address = $user->addresses()->create([
            'label' => 'Alamat lama',
            'full_address' => 'Alamat yang akan dihapus',
        ]);

        $this->actingAs($user)
            ->delete(route('user.addresses.destroy', $address))
            ->assertRedirect()
            ->assertSessionHas('success', 'Alamat berhasil dihapus.');

        $this->assertDatabaseMissing('addresses', ['id' => $address->id]);
    }
}
