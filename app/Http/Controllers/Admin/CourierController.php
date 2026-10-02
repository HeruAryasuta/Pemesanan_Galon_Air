<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\User;
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
        $couriers = Courier::with('user')->latest()->paginate(15);

        return view('admin.couriers.index', compact('couriers'));
    }

    public function create(): View
    {
        return view('admin.couriers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        try {
            DB::transaction(function () use ($validated): void {
                $account = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'password' => Hash::make($validated['password']),
                    'role' => 'courier',
                ]);

                Courier::create([
                    'user_id' => $account->id,
                    'name' => $validated['name'],
                    'phone' => $validated['phone'],
                    'vehicle_type' => $validated['vehicle_type'] ?? null,
                    'is_available' => $validated['is_available'] ?? false,
                ]);
            });

            return redirect()->route('admin.couriers.index')->with('success', 'Kurir berhasil ditambahkan.');
        } catch (\Throwable $e) {
            Log::error('Gagal tambah kurir: ' . $e->getMessage());

            return back()->withInput()->with('error', 'Gagal menyimpan data kurir.');
        }
    }

    public function edit(Courier $courier): View
    {
        $courier->load('user');

        return view('admin.couriers.edit', compact('courier'));
    }

    public function update(Request $request, Courier $courier): RedirectResponse
    {
        $validated = $this->validated($request, $courier);

        try {
            DB::transaction(function () use ($courier, $validated): void {
                $account = $courier->user;

                if (! $account) {
                    $account = User::create([
                        'name' => $validated['name'],
                        'email' => $validated['email'],
                        'phone' => $validated['phone'],
                        'password' => Hash::make($validated['password']),
                        'role' => 'courier',
                    ]);
                    $courier->user()->associate($account);
                } else {
                    $account->update([
                        'name' => $validated['name'],
                        'email' => $validated['email'],
                        'phone' => $validated['phone'],
                        ...(! empty($validated['password']) ? ['password' => Hash::make($validated['password'])] : []),
                    ]);
                }

                $courier->update([
                    'name' => $validated['name'],
                    'phone' => $validated['phone'],
                    'vehicle_type' => $validated['vehicle_type'] ?? null,
                    'is_available' => $validated['is_available'] ?? false,
                ]);
            });

            return redirect()->route('admin.couriers.index')->with('success', 'Data kurir diperbarui.');
        } catch (\Throwable $e) {
            Log::error('Gagal update kurir #' . $courier->id . ': ' . $e->getMessage());

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
            Log::error('Gagal hapus kurir #' . $courier->id . ': ' . $e->getMessage());

            return back()->with('error', 'Gagal menghapus kurir.');
        }
    }

    private function validated(Request $request, ?Courier $courier = null): array
    {
        $hasAccount = $courier?->user_id !== null;

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'vehicle_type' => ['nullable', 'string', 'max:100'],
            'is_available' => ['boolean'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($courier?->user_id)],
            'password' => [$hasAccount ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ]);
    }
}