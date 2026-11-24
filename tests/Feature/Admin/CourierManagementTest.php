<?php

namespace Tests\Feature\Admin;

use App\Models\Address;
use App\Models\Courier;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\ServiceZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_courier_list_and_forms(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $courier = $this->createCourier();

        $this->actingAs($admin)
            ->get(route('admin.couriers.index'))
            ->assertOk()
            ->assertSee('Kelola Kurir')
            ->assertSee($courier->name)
            ->assertSee($courier->phone)
            ->assertSee($courier->vehicle_type)
            ->assertSee('Tersedia')
            ->assertSee(route('admin.couriers.create'), false)
            ->assertSee(route('admin.couriers.edit', $courier), false);

        $this->get(route('admin.couriers.create'))
            ->assertOk()
            ->assertSee('Tambah Kurir')
            ->assertSee('Nama kurir')
            ->assertSee('Nomor KTP/NIK')
            ->assertSee('Tosa')
            ->assertSee('Truck')
            ->assertSee('Pick up');

        $this->get(route('admin.couriers.edit', $courier))
            ->assertOk()
            ->assertSee('Edit Kurir')
            ->assertSee($courier->name);
    }

    public function test_admin_can_create_and_update_courier(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.couriers.store'), [
                'name' => 'Siti Aminah',
                'phone' => '081234567890',
                'nik' => '3201234567890123',
                'vehicle_type' => 'Sepeda motor',
                'service_zone_id' => $this->gunungAnyarZoneId(),
                'is_available' => '1',
                'email' => 'siti.kurir@example.test',
                'password' => 'CourierPass123',
                'password_confirmation' => 'CourierPass123',
            ])
            ->assertRedirect(route('admin.couriers.index'))
            ->assertSessionHas('success', 'Kurir berhasil ditambahkan. Username login: siti01PWD');

        $courier = Courier::sole();
        $this->assertTrue($courier->is_available);
        $this->assertSame('courier', $courier->user->role);
        $this->assertSame('siti.kurir@example.test', $courier->user->email);
        $this->assertSame('siti01PWD', $courier->user->username);
        $this->assertSame('3201234567890123', $courier->nik);
        $this->assertNotSame('3201234567890123', $courier->getRawOriginal('nik'));

        $this->put(route('admin.couriers.update', $courier), [
            'name' => 'Siti Aminah Putri',
            'phone' => '081234567891',
            'nik' => '3201234567890124',
            'vehicle_type' => 'Truck',
            'service_zone_id' => $this->gunungAnyarZoneId(),
            'is_available' => '0',
            'email' => 'siti.kurir@example.test',
        ])
            ->assertRedirect(route('admin.couriers.index'))
            ->assertSessionHas('success', 'Data kurir diperbarui. Username login: siti01PWD');

        $courier->refresh();
        $this->assertSame('Siti Aminah Putri', $courier->name);
        $this->assertSame('081234567891', $courier->phone);
        $this->assertSame('3201234567890124', $courier->nik);
        $this->assertSame('Truck', $courier->vehicle_type);
        $this->assertFalse($courier->is_available);
        $this->assertSame('Siti Aminah Putri', $courier->user->name);
    }

    public function test_admin_must_add_a_zone_slot_before_assigning_another_courier_to_a_full_zone(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $zoneId = $this->gunungAnyarZoneId();
        $firstCourier = [
            'name' => 'Kurir Pertama',
            'phone' => '081111111111',
            'nik' => '3201234567890123',
            'vehicle_type' => 'Truck',
            'service_zone_id' => $zoneId,
            'email' => 'pertama@example.test',
            'password' => 'CourierPass123',
            'password_confirmation' => 'CourierPass123',
        ];

        $this->actingAs($admin)
            ->post(route('admin.couriers.store'), $firstCourier)
            ->assertRedirect(route('admin.couriers.index'))
            ->assertSessionHasNoErrors();

        $secondCourier = [
            ...$firstCourier,
            'name' => 'Kurir Kedua',
            'phone' => '082222222222',
            'nik' => '3201234567890124',
            'email' => 'kedua@example.test',
        ];

        $this->post(route('admin.couriers.store'), $secondCourier)
            ->assertRedirect()
            ->assertSessionHas('error', 'Slot wilayah sudah penuh. Tambahkan slot wilayah terlebih dahulu atau pilih wilayah lain.');
        $this->assertDatabaseCount('couriers', 1);

        $this->post(route('admin.couriers.zones.add-slot', $zoneId))
            ->assertRedirect()
            ->assertSessionHas('success', 'Slot Wilayah 5 berhasil ditambahkan.');

        $this->post(route('admin.couriers.store'), $secondCourier)
            ->assertRedirect(route('admin.couriers.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('couriers', 2);
        $this->assertDatabaseHas('service_zones', ['id' => $zoneId, 'slot_count' => 2]);
    }

    public function test_admin_edit_generates_username_for_existing_courier_account_without_one(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $account = User::factory()->create(['role' => 'courier', 'username' => null]);
        $courier = Courier::create([
            'user_id' => $account->id,
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
            'is_available' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.couriers.update', $courier), [
                'name' => 'Budi Santoso',
                'phone' => '081234567890',
                'nik' => '3201234567890123',
                'vehicle_type' => 'Sepeda motor',
                'service_zone_id' => $this->gunungAnyarZoneId(),
                'email' => $account->email,
            ])
            ->assertRedirect(route('admin.couriers.index'))
            ->assertSessionHas('success', 'Data kurir diperbarui. Username login: budi'.str_pad((string) $courier->id, 2, '0', STR_PAD_LEFT).'PWD');

        $this->assertSame('budi'.str_pad((string) $courier->id, 2, '0', STR_PAD_LEFT).'PWD', $account->fresh()->username);
    }

    public function test_admin_can_delete_courier_without_delivery_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $courier = $this->createCourier();

        $this->actingAs($admin)
            ->delete(route('admin.couriers.destroy', $courier))
            ->assertRedirect()
            ->assertSessionHas('success', 'Kurir berhasil dihapus.');

        $this->assertDatabaseMissing('couriers', ['id' => $courier->id]);
    }

    public function test_deleting_courier_without_delivery_history_also_removes_its_login_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $account = User::factory()->create(['role' => 'courier']);
        $courier = Courier::create([
            'user_id' => $account->id,
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
            'is_available' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.couriers.destroy', $courier))
            ->assertSessionHas('success', 'Kurir berhasil dihapus.');

        $this->assertDatabaseMissing('users', ['id' => $account->id]);
    }

    public function test_admin_cannot_delete_courier_with_delivery_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $courier = $this->createCourier();
        $customer = User::factory()->create();
        $address = Address::create([
            'user_id' => $customer->id,
            'label' => 'Rumah',
            'full_address' => 'Jl. Gunung Anyar No. 10, Surabaya',
            'is_primary' => true,
        ]);
        $order = Order::create([
            'user_id' => $customer->id,
            'address_id' => $address->id,
            'status' => 'pending',
            'total_price' => 26000,
            'ordered_at' => now(),
        ]);
        Delivery::create([
            'order_id' => $order->id,
            'courier_id' => $courier->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.couriers.destroy', $courier))
            ->assertRedirect()
            ->assertSessionHas('error', 'Kurir tidak bisa dihapus karena memiliki riwayat pengantaran.');

        $this->assertDatabaseHas('couriers', ['id' => $courier->id]);
    }

    public function test_customer_cannot_manage_couriers(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('admin.couriers.index'))
            ->assertForbidden();
    }

    private function createCourier(): Courier
    {
        return Courier::create([
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
            'nik' => '3201234567890123',
            'vehicle_type' => 'Sepeda motor',
            'service_zone_id' => $this->gunungAnyarZoneId(),
            'is_available' => true,
        ]);
    }

    private function gunungAnyarZoneId(): int
    {
        return (int) ServiceZone::query()->where('code', 'WIL-05')->value('id');
    }

    public function test_admin_courier_form_rejects_invalid_nik_and_vehicle_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.couriers.store'), [
                'name' => 'Siti Aminah',
                'phone' => '081234567890',
                'nik' => '123456789012345',
                'vehicle_type' => 'Motor antik',
                'email' => 'siti.kurir@example.test',
                'password' => 'CourierPass123',
                'password_confirmation' => 'CourierPass123',
            ])
            ->assertSessionHasErrors(['nik', 'vehicle_type'])
            ->assertSessionMissing('_old_input.nik');

        $this->assertDatabaseCount('couriers', 0);
    }
}
