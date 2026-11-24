<?php

namespace App\Http\Controllers;

use App\Models\Courier;
use App\Models\CourierApplication;
use App\Models\ServiceZone;
use App\Models\User;
use App\Support\CourierUsernameGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourierApplicationController extends Controller
{
    public function create(): View
    {
        return view('courier-applications.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'has_vehicle' => ['required', 'accepted'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'nik' => ['required', 'regex:/\A[0-9]{16}\z/'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('courier_applications', 'email')->where('status', 'pending'),
            ],
            'vehicle_type' => ['required', Rule::in(config('courier.vehicle_types'))],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'nik.regex' => 'NIK harus terdiri dari tepat 16 digit angka.',
            'vehicle_type.in' => 'Pilih jenis kendaraan yang tersedia.',
        ]);

        CourierApplication::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'nik' => $validated['nik'],
            'email' => $validated['email'],
            'vehicle_type' => $validated['vehicle_type'],
            'has_vehicle' => true,
            'password_hash' => Hash::make($validated['password']),
            'status' => 'pending',
        ]);

        return redirect()->route('courier-applications.create')
            ->with('success', 'Pengajuan berhasil dikirim dan menunggu pemeriksaan admin. Jika disetujui, Anda dapat masuk menggunakan email dan kata sandi yang didaftarkan.');
    }

    public function index(): View
    {
        $applications = CourierApplication::query()
            ->with('reviewer')
            ->latest()
            ->paginate(20);
        $serviceZones = ServiceZone::query()->withCount('couriers')->orderBy('id')->get();

        return view('admin.courier-applications.index', compact('applications', 'serviceZones'));
    }

    public function approve(Request $request, CourierApplication $courierApplication): RedirectResponse
    {
        if ($courierApplication->status !== 'pending') {
            return back()->with('error', 'Pengajuan ini sudah pernah diproses.');
        }

        $validated = $request->validate([
            'service_zone_id' => ['required', 'integer', 'exists:service_zones,id'],
        ]);

        if (User::where('email', $courierApplication->email)->exists()) {
            return back()->with('error', 'Email calon kurir sudah digunakan oleh akun lain. Periksa dan selesaikan secara manual sebelum menyetujui pengajuan.');
        }

        try {
            $username = DB::transaction(function () use ($courierApplication, $validated): string {
                $application = CourierApplication::query()
                    ->whereKey($courierApplication->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($application->status !== 'pending') {
                    abort(409, 'Pengajuan ini sudah pernah diproses.');
                }

                $zone = ServiceZone::query()->whereKey($validated['service_zone_id'])->lockForUpdate()->firstOrFail();
                if ($zone->couriers()->count() >= $zone->slot_count) {
                    throw new \RuntimeException('Slot wilayah sudah penuh. Tambahkan slot wilayah terlebih dahulu atau pilih wilayah lain.');
                }

                $courier = Courier::create([
                    'name' => $application->name,
                    'phone' => $application->phone,
                    'nik' => $application->nik,
                    'vehicle_type' => $application->vehicle_type,
                    'service_zone_id' => $zone->id,
                    'is_available' => true,
                ]);
                $username = app(CourierUsernameGenerator::class)->generate($courier);
                $account = User::create([
                    'name' => $application->name,
                    'username' => $username,
                    'email' => $application->email,
                    'phone' => $application->phone,
                    'password' => $application->password_hash,
                    'role' => 'courier',
                ]);
                $courier->user()->associate($account);
                $courier->save();

                $application->update([
                    'status' => 'approved',
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                    'password_hash' => null,
                ]);

                return $username;
            });
        } catch (\RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Pengajuan disetujui dan akun kurir dibuat. Username login: {$username}");
    }

    public function reject(CourierApplication $courierApplication): RedirectResponse
    {
        if ($courierApplication->status !== 'pending') {
            return back()->with('error', 'Pengajuan ini sudah pernah diproses.');
        }

        $courierApplication->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'password_hash' => null,
        ]);

        return back()->with('success', 'Pengajuan calon kurir ditolak.');
    }
}
