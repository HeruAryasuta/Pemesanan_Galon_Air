<?php

namespace Tests\Feature\Admin;

use App\Models\Address;
use App\Models\Courier;
use App\Models\Delivery;
use App\Models\Order;
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
            ->assertSee('Nama kurir');

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
                'vehicle_type' => 'Sepeda motor',
                'is_available' => '1',
                'email' => 'siti.kurir@example.test',
                'password' => 'CourierPass123',
                'password_confirmation' => 'CourierPass123',
            ])
            ->assertRedirect(route('admin.couriers.index'))
            ->assertSessionHas('success', 'Kurir berhasil ditambahkan.');

        $courier = Courier::sole();
        $this->assertTrue($courier->is_available);
        $this->assertSame('courier', $courier->user->role);
        $this->assertSame('siti.kurir@example.test', $courier->user->email);

        $this->put(route('admin.couriers.update', $courier), [
            'name' => 'Siti Aminah Putri',
            'phone' => '081234567891',
            'vehicle_type' => 'Mobil bak',
            'is_available' => '0',
            'email' => 'siti.kurir@example.test',
        ])
            ->assertRedirect(route('admin.couriers.index'))
            ->assertSessionHas('success', 'Data kurir diperbarui.');

        $courier->refresh();
        $this->assertSame('Siti Aminah Putri', $courier->name);
        $this->assertSame('081234567891', $courier->phone);
        $this->assertSame('Mobil bak', $courier->vehicle_type);
        $this->assertFalse($courier->is_available);
        $this->assertSame('Siti Aminah Putri', $courier->user->name);
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
            'vehicle_type' => 'Sepeda motor',
            'is_available' => true,
        ]);
    }
}
