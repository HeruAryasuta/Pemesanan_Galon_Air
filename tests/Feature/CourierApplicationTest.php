<?php

namespace Tests\Feature;

use App\Models\Courier;
use App\Models\CourierApplication;
use App\Models\ServiceZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CourierApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_can_apply_after_confirming_vehicle_ownership(): void
    {
        $this->get(route('courier-applications.create'))
            ->assertOk()
            ->assertSee('Konfirmasi kendaraan')
            ->assertSee('Kirim pengajuan')
            ->assertSee('Nomor KTP/NIK')
            ->assertSee('Tosa')
            ->assertSee('Truck')
            ->assertSee('Pick up');

        $this->post(route('courier-applications.store'), [
            ...$this->validApplication(),
            'has_vehicle' => '0',
        ])->assertSessionHasErrors('has_vehicle');

        $this->assertDatabaseCount('courier_applications', 0);

        $this->post(route('courier-applications.store'), [
            ...$this->validApplication(),
            'nik' => '12345678901234567',
        ])->assertSessionHasErrors('nik')
            ->assertSessionMissing('_old_input.nik');

        $this->post(route('courier-applications.store'), [
            ...$this->validApplication(),
            'vehicle_type' => 'Motor antik',
        ])->assertSessionHasErrors('vehicle_type');

        $this->assertDatabaseCount('courier_applications', 0);

        $this->post(route('courier-applications.store'), $this->validApplication())
            ->assertRedirect(route('courier-applications.create'))
            ->assertSessionHas('success');

        $application = CourierApplication::sole();
        $this->assertSame('pending', $application->status);
        $this->assertTrue($application->has_vehicle);
        $this->assertTrue(Hash::check('CourierPass123', $application->password_hash));
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('couriers', 0);
    }

    public function test_admin_approval_creates_courier_account_and_clears_application_password_hash(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $application = CourierApplication::create([
            ...$this->validApplication(),
            'has_vehicle' => true,
            'password_hash' => Hash::make('CourierPass123'),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.courier-applications.index'))
            ->assertOk()
            ->assertSee($application->name)
            ->assertSee('Sepeda motor');

        $this->post(route('admin.courier-applications.approve', $application), [
            'service_zone_id' => ServiceZone::query()->where('code', 'WIL-01')->value('id'),
        ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Pengajuan disetujui dan akun kurir dibuat. Username login: siti01PWD');

        $application->refresh();
        $courier = Courier::sole();
        $account = User::where('email', 'siti@example.test')->sole();

        $this->assertSame('approved', $application->status);
        $this->assertNull($application->password_hash);
        $this->assertSame($admin->id, $application->reviewed_by);
        $this->assertSame('3201234567890123', $application->nik);
        $this->assertNotSame('3201234567890123', $application->getRawOriginal('nik'));
        $this->assertSame('courier', $account->role);
        $this->assertSame('siti01PWD', $account->username);
        $this->assertSame($account->id, $courier->user_id);
        $this->assertSame('3201234567890123', $courier->nik);
        $this->assertSame('Sepeda motor', $courier->vehicle_type);
        $this->assertTrue(Hash::check('CourierPass123', $account->password));

        auth()->logout();
        $this->post('/login', ['email' => 'siti01PWD', 'password' => 'CourierPass123'])
            ->assertRedirect(route('courier.dashboard'));
    }

    public function test_admin_can_reject_application_and_rejected_email_can_apply_again(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $application = CourierApplication::create([
            ...$this->validApplication(),
            'has_vehicle' => true,
            'password_hash' => Hash::make('CourierPass123'),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.courier-applications.reject', $application))
            ->assertRedirect()
            ->assertSessionHas('success', 'Pengajuan calon kurir ditolak.');

        $application->refresh();
        $this->assertSame('rejected', $application->status);
        $this->assertNull($application->password_hash);

        $this->post(route('courier-applications.store'), $this->validApplication())
            ->assertRedirect(route('courier-applications.create'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('courier_applications', 2);
    }

    public function test_customer_cannot_review_courier_applications(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('admin.courier-applications.index'))
            ->assertForbidden();
    }

    /**
     * @return array<string, string>
     */
    private function validApplication(): array
    {
        return [
            'has_vehicle' => '1',
            'name' => 'Siti Aminah',
            'phone' => '081234567890',
            'nik' => '3201234567890123',
            'email' => 'siti@example.test',
            'vehicle_type' => 'Sepeda motor',
            'password' => 'CourierPass123',
            'password_confirmation' => 'CourierPass123',
        ];
    }
}
