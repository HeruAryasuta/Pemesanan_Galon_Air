<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\ServiceZone;
use App\Models\User;
use App\Support\CourierCapacity;
use App\Support\CourierUsernameGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourierController extends Controller
{
    public function index(): View
    {
        $couriers = Courier::with(['user', 'serviceZone'])->latest()->paginate(15);
        $serviceZones = ServiceZone::query()->withCount('couriers')->orderBy('id')->get();

        return view('admin.couriers.index', compact('couriers', 'serviceZones'));
    }

    public function create(): View
    {
        $serviceZones = ServiceZone::query()->withCount('couriers')->orderBy('id')->get();

        return view('admin.couriers.create', compact('serviceZones'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        try {
            $username = DB::transaction(function () use ($validated): string {
                $this->assertServiceZoneSlot((int) $validated['service_zone_id']);
                $courier = Courier::create([
                    'name' => $validated['name'],
                    'phone' => $validated['phone'],
                    'nik' => $validated['nik'],
                    'vehicle_type' => $validated['vehicle_type'],
                    'service_zone_id' => $validated['service_zone_id'],
                    'is_available' => $validated['is_available'] ?? false,
                ]);
                $username = app(CourierUsernameGenerator::class)->generate($courier);
                $account = User::create([
                    'name' => $validated['name'],
                    'username' => $username,
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'password' => Hash::make($validated['password']),
                    'role' => 'courier',
                ]);
                $courier->user()->associate($account);
                $courier->save();

                return $username;
            });

            return redirect()->route('admin.couriers.index')->with('success', "Kurir berhasil ditambahkan. Username login: {$username}");
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Gagal tambah kurir: '.$e->getMessage());

            return back()->withInput()->with('error', 'Gagal menyimpan data kurir.');
        }
    }

    public function edit(Courier $courier): View
    {
        $courier->load('user');
        $serviceZones = ServiceZone::query()->withCount('couriers')->orderBy('id')->get();

        return view('admin.couriers.edit', compact('courier', 'serviceZones'));
    }

    public function update(Request $request, Courier $courier): RedirectResponse
    {
        $validated = $this->validated($request, $courier);

        try {
            $username = DB::transaction(function () use ($courier, $validated): ?string {
                $lockedCourier = Courier::query()->whereKey($courier->id)->lockForUpdate()->firstOrFail();
                if (
                    $lockedCourier->service_zone_id !== (int) $validated['service_zone_id']
                    && $lockedCourier->deliveries()->whereIn('status', ['assigned', 'in_transit'])->exists()
                ) {
                    throw new \RuntimeException('Wilayah kurir tidak dapat diubah selama masih ada pengantaran aktif.');
                }

                $capacity = app(CourierCapacity::class);
                $activeLoad = $capacity->activeLoad($lockedCourier);
                $newCapacity = (int) config('courier.vehicle_capacities.'.$validated['vehicle_type'], 0);
                if ($activeLoad > $newCapacity) {
                    throw new \RuntimeException('Kapasitas kendaraan baru tidak mencukupi muatan pengantaran aktif.');
                }

                $this->assertServiceZoneSlot((int) $validated['service_zone_id'], $lockedCourier->id);
                $account = $lockedCourier->user;

                if (! $account) {
                    $username = app(CourierUsernameGenerator::class)->generate($lockedCourier);
                    $account = User::create([
                        'name' => $validated['name'],
                        'username' => $username,
                        'email' => $validated['email'],
                        'phone' => $validated['phone'],
                        'password' => Hash::make($validated['password']),
                        'role' => 'courier',
                    ]);
                    $lockedCourier->user()->associate($account);
                } else {
                    $username = $account->username ?: app(CourierUsernameGenerator::class)->generate($lockedCourier);
                    $account->update([
                        'name' => $validated['name'],
                        'username' => $username,
                        'email' => $validated['email'],
                        'phone' => $validated['phone'],
                        ...(! empty($validated['password']) ? ['password' => Hash::make($validated['password'])] : []),
                    ]);
                }

                $lockedCourier->update([
                    'name' => $validated['name'],
                    'phone' => $validated['phone'],
                    'nik' => $validated['nik'],
                    'vehicle_type' => $validated['vehicle_type'],
                    'service_zone_id' => $validated['service_zone_id'],
                    'is_available' => $validated['is_available'] ?? false,
                ]);

                return $username;
            });

            $message = $username
                ? "Data kurir diperbarui. Username login: {$username}"
                : 'Data kurir diperbarui.';

            return redirect()->route('admin.couriers.index')->with('success', $message);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Gagal update kurir #'.$courier->id.': '.$e->getMessage());

            return back()->withInput()->with('error', 'Gagal memperbarui data kurir.');
        }
    }

    public function destroy(Courier $courier): RedirectResponse
    {
        try {
            if ($courier->deliveries()->exists()) {
                return back()->with('error', 'Kurir tidak bisa dihapus karena memiliki riwayat pengantaran.');
            }

            DB::transaction(function () use ($courier): void {
                $account = $courier->user;
                $courier->delete();
                $account?->delete();
            });

            return back()->with('success', 'Kurir berhasil dihapus.');
        } catch (\Throwable $e) {
            Log::error('Gagal hapus kurir #'.$courier->id.': '.$e->getMessage());

            return back()->with('error', 'Gagal menghapus kurir.');
        }
    }

    public function addServiceZoneSlot(ServiceZone $serviceZone): RedirectResponse
    {
        try {
            DB::transaction(function () use ($serviceZone): void {
                $zone = ServiceZone::query()->whereKey($serviceZone->id)->lockForUpdate()->firstOrFail();
                if ($zone->slot_count >= 100) {
                    throw new \RuntimeException('Batas slot wilayah sudah mencapai 100.');
                }

                $zone->increment('slot_count');
            });
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Slot {$serviceZone->name} berhasil ditambahkan.");
    }

    private function validated(Request $request, ?Courier $courier = null): array
    {
        $hasAccount = $courier?->user_id !== null;

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'nik' => ['required', 'regex:/\A[0-9]{16}\z/'],
            'vehicle_type' => ['required', Rule::in(config('courier.vehicle_types'))],
            'service_zone_id' => ['required', 'integer', 'exists:service_zones,id'],
            'is_available' => ['boolean'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($courier?->user_id)],
            'password' => [$hasAccount ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ], [
            'nik.regex' => 'NIK harus terdiri dari tepat 16 digit angka.',
            'vehicle_type.in' => 'Pilih jenis kendaraan yang tersedia.',
        ]);
    }

    private function assertServiceZoneSlot(int $serviceZoneId, ?int $exceptCourierId = null): void
    {
        $zone = ServiceZone::query()->whereKey($serviceZoneId)->lockForUpdate()->firstOrFail();
        $assignedCount = Courier::query()
            ->where('service_zone_id', $zone->id)
            ->when($exceptCourierId, fn ($query) => $query->where('id', '!=', $exceptCourierId))
            ->count();

        if ($assignedCount >= $zone->slot_count) {
            throw new \RuntimeException('Slot wilayah sudah penuh. Tambahkan slot wilayah terlebih dahulu atau pilih wilayah lain.');
        }
    }
}
