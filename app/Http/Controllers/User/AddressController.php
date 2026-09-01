<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AddressController extends Controller
{
    public function index(Request $request): View
    {
        $addresses = $request->user()->addresses;

        return view('user.addresses.index', compact('addresses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        try {
            if ($validated['is_primary'] ?? false) {
                $request->user()->addresses()->update(['is_primary' => false]);
            }

            $request->user()->addresses()->create($validated);

            return back()->with('success', 'Alamat berhasil ditambahkan.');
        } catch (\Throwable $e) {
            Log::error('Gagal tambah alamat: ' . $e->getMessage());

            return back()->withInput()->with('error', 'Gagal menyimpan alamat.');
        }
    }

    public function update(Request $request, Address $address): RedirectResponse
    {
        abort_if($address->user_id !== $request->user()->id, 403);

        $validated = $this->validated($request);

        try {
            if ($validated['is_primary'] ?? false) {
                $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_primary' => false]);
            }

            $address->update($validated);

            return back()->with('success', 'Alamat berhasil diperbarui.');
        } catch (\Throwable $e) {
            Log::error('Gagal update alamat #' . $address->id . ': ' . $e->getMessage());

            return back()->withInput()->with('error', 'Gagal memperbarui alamat.');
        }
    }

    public function destroy(Request $request, Address $address): RedirectResponse
    {
        abort_if($address->user_id !== $request->user()->id, 403);

        try {
            if ($address->orders()->exists()) {
                return back()->with('error', 'Alamat tidak bisa dihapus karena sudah dipakai di riwayat pesanan.');
            }

            $address->delete();

            return back()->with('success', 'Alamat berhasil dihapus.');
        } catch (\Throwable $e) {
            Log::error('Gagal hapus alamat #' . $address->id . ': ' . $e->getMessage());

            return back()->with('error', 'Gagal menghapus alamat.');
        }
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'full_address' => ['required', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_primary' => ['boolean'],
        ]);
    }
}