<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CourierController extends Controller
{
    public function index(): View
    {
        $couriers = Courier::latest()->paginate(15);

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
            Courier::create($validated);

            return redirect()->route('admin.couriers.index')->with('success', 'Kurir berhasil ditambahkan.');
        } catch (\Throwable $e) {
            Log::error('Gagal tambah kurir: ' . $e->getMessage());

            return back()->withInput()->with('error', 'Gagal menyimpan data kurir.');
        }
    }

    public function edit(Courier $courier): View
    {
        return view('admin.couriers.edit', compact('courier'));
    }

    public function update(Request $request, Courier $courier): RedirectResponse
    {
        $validated = $this->validated($request);

        try {
            $courier->update($validated);

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

            $courier->delete();

            return back()->with('success', 'Kurir berhasil dihapus.');
        } catch (\Throwable $e) {
            Log::error('Gagal hapus kurir #' . $courier->id . ': ' . $e->getMessage());

            return back()->with('error', 'Gagal menghapus kurir.');
        }
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'vehicle_type' => ['nullable', 'string', 'max:100'],
            'is_available' => ['boolean'],
        ]);
    }
}