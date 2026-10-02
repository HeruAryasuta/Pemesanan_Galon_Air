@extends('layouts.admin')

@section('title', 'Kelola Kurir')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#4162a5]">Operasional toko</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">Kelola Kurir</h1>
            <p class="mt-1 text-sm text-slate-500">Atur data kurir, kendaraan, dan ketersediaan untuk pengantaran.</p>
        </div>
        <a href="{{ route('admin.couriers.create') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#12377f] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#09296d]">+ Tambah kurir</a>
    </div>

    @if ($couriers->isEmpty())
        <section class="rounded-2xl border border-[#e8ecf6] bg-white px-5 py-12 text-center shadow-sm">
            <h2 class="text-base font-bold text-[#202a3b]">Belum ada data kurir</h2>
            <p class="mt-2 text-sm text-slate-500">Tambahkan kurir agar pesanan dapat ditugaskan untuk pengantaran.</p>
            <a href="{{ route('admin.couriers.create') }}" class="mt-5 inline-flex min-h-10 items-center justify-center rounded-lg bg-[#12377f] px-4 py-2 text-xs font-bold text-white hover:bg-[#09296d]">Tambah kurir</a>
        </section>
    @else
        <section class="overflow-hidden rounded-xl border border-[#e6ebf5] bg-white shadow-sm" aria-label="Daftar kurir">
            <div class="flex items-center justify-between px-4 py-3 sm:px-5">
                <p class="text-xs font-semibold text-slate-600">{{ $couriers->total() }} kurir</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left">
                    <thead class="bg-[#eef3ff] text-[9px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3">Nama kurir</th>
                            <th scope="col" class="px-4 py-3">Email login</th>
                            <th scope="col" class="px-4 py-3">Nomor telepon</th>
                            <th scope="col" class="px-4 py-3">Kendaraan</th>
                            <th scope="col" class="px-4 py-3">Ketersediaan</th>
                            <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#edf0f7]">
                        @foreach ($couriers as $courier)
                            <tr class="text-xs hover:bg-[#fbfcff]">
                                <td class="px-4 py-3 font-bold text-[#202a3b]">{{ $courier->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $courier->user?->email ?? 'Belum ada akun' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $courier->phone }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $courier->vehicle_type ?: 'Belum diatur' }}</td>
                                <td class="px-4 py-3">
                                    @if ($courier->is_available)
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[9px] font-bold text-emerald-700">Tersedia</span>
                                    @else
                                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[9px] font-bold text-amber-800">Sedang bertugas</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.couriers.edit', $courier) }}" class="rounded-md border border-[#dce4f5] px-2.5 py-1.5 text-[10px] font-bold text-[#31569e] hover:bg-[#edf2ff]">Edit</a>
                                        <form method="POST" action="{{ route('admin.couriers.destroy', $courier) }}" onsubmit="return confirm('Hapus data kurir {{ addslashes($courier->name) }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="rounded-md border border-rose-200 px-2.5 py-1.5 text-[10px] font-bold text-rose-700 hover:bg-rose-50">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($couriers->hasPages())
                <div class="border-t border-[#edf0f7] px-4 py-3 sm:px-5">{{ $couriers->links() }}</div>
            @endif
        </section>
    @endif
@endsection
