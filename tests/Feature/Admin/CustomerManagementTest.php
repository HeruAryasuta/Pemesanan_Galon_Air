<?php

namespace Tests\Feature\Admin;

use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_customer_list_and_search_by_contact_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = $this->createCustomer([
            'name' => 'Siti Aminah',
            'email' => 'siti@example.test',
            'phone' => '081234567890',
        ]);
        $otherCustomer = $this->createCustomer([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.test',
            'phone' => '089876543210',
        ]);
        Order::create([
            'user_id' => $customer->id,
            'address_id' => $this->createAddress($customer)->id,
            'status' => 'pending',
            'total_price' => 26000,
            'ordered_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertSee('Kelola Pelanggan')
            ->assertSee($customer->name)
            ->assertSee($otherCustomer->name)
            ->assertSee('1')
            ->assertSee(route('admin.customers.show', $customer), false);

        $this->get(route('admin.customers.index', ['search' => 'siti@example.test']))
            ->assertOk()
            ->assertSee($customer->name)
            ->assertDontSee($otherCustomer->name);

        $this->get(route('admin.customers.index', ['search' => '081234567890']))
            ->assertOk()
            ->assertSee($customer->name)
            ->assertDontSee($otherCustomer->name);
    }

    public function test_admin_can_view_customer_detail_with_addresses_and_recent_orders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = $this->createCustomer(['name' => 'Siti Aminah']);
        $address = $this->createAddress($customer);
        Order::create([
            'user_id' => $customer->id,
            'address_id' => $address->id,
            'status' => 'delivered',
            'total_price' => 47000,
            'ordered_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertSee('Detail Pelanggan')
            ->assertSee('Informasi kontak')
            ->assertSee('Alamat tersimpan')
            ->assertSee($address->full_address)
            ->assertSee('Pesanan terbaru')
            ->assertSee('Pesanan #1')
            ->assertSee('Rp47.000');
    }

    public function test_admin_cannot_open_another_admin_as_customer_detail(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.customers.show', $otherAdmin))
            ->assertNotFound();
    }

    public function test_customer_cannot_access_customer_management_pages(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs($customer)
            ->get(route('admin.customers.index'))
            ->assertForbidden();

        $this->get(route('admin.customers.show', $customer))
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createCustomer(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => 'customer'], $attributes));
    }

    private function createAddress(User $customer): Address
    {
        return Address::create([
            'user_id' => $customer->id,
            'label' => 'Rumah',
            'full_address' => 'Jl. Gunung Anyar No. 10, Surabaya',
            'is_primary' => true,
        ]);
    }
}
