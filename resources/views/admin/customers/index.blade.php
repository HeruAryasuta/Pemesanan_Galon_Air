@extends('layouts.admin')

@section('title', 'Kelola Pelanggan')

@section('content')
    <div class="mb-6">
        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#4162a5]">Data pelanggan</p>
        <h1 class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">Kelola Pelanggan</h1>
        <p class="mt-1 text-sm text-slate-500">Lihat informasi kontak, jumlah pesanan, dan alamat pelanggan.</p>
    </div>

    <form method="GET" action="{{ route('admin.customers.index') }}" class="mb-5 flex flex-col gap-3 rounded-xl border border-[#e6ebf5] bg-white p-4 shadow-sm sm:flex-row">
        <div class="flex-1">
            <label for="customer-search" class="sr-only">Cari nama, email, atau nomor telepon</label>
            <input id="customer-search" type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama, email, atau nomor telepon pelanggan..." class="w-full rounded-lg border-[#dce4f5] text-xs focus:border-[#31569e] focus:ring-[#31569e]">
        </div>
        <div class="flex gap-2">
            <button class="min-h-9 rounded-lg bg-[#12377f] px-4 text-xs font-bold text-white hover:bg-[#09296d]">Cari pelanggan</button>
            @if (request()->filled('search'))
                <a href="{{ route('admin.customers.index') }}" class="inline-flex min-h-9 items-center rounded-lg border border-[#dce4f5] px-3 text-xs font-semibold text-slate-600 hover:bg-slate-50">Reset</a>
            @endif
        </div>
    </form>

    @if ($customers->isEmpty())
        <section class="rounded-2xl border border-[#e8ecf6] bg-white px-5 py-12 text-center shadow-sm">
            <h2 class="text-base font-bold text-[#202a3b]">Pelanggan tidak ditemukan</h2>
            <p class="mt-2 text-sm text-slate-500">Coba kata kunci lain. Pelanggan baru akan tampil di sini setelah mendaftar.</p>
        </section>
    @else
        <section class="overflow-hidden rounded-xl border border-[#e6ebf5] bg-white shadow-sm" aria-label="Daftar pelanggan">
            <div class="flex items-center justify-between px-4 py-3 sm:px-5">
                <p class="text-xs font-semibold text-slate-600">{{ $customers->total() }} pelanggan</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left">
                    <thead class="bg-[#eef3ff] text-[9px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3">Pelanggan</th>
                            <th scope="col" class="px-4 py-3">Nomor telepon</th>
                            <th scope="col" class="px-4 py-3 text-right">Jumlah pesanan</th>
                            <th scope="col" class="px-4 py-3">Terdaftar</th>
                            <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#edf0f7]">
                        @foreach ($customers as $customer)
                            <tr class="text-xs hover:bg-[#fbfcff]">
                                <td class="px-4 py-3">
                                    <p class="font-bold text-[#202a3b]">{{ $customer->name }}</p>
                                    <p class="mt-1 text-[10px] text-slate-500">{{ $customer->email }}</p>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $customer->phone ?: 'Belum diisi' }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-700">{{ $customer->orders_count }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $customer->created_at->timezone(config('app.display_timezone'))->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.customers.show', $customer) }}" class="font-bold text-[#31569e] hover:underline">Lihat detail →</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        @if ($customers->hasPages())
            <div class="mt-5">{{ $customers->links() }}</div>
        @endif
    @endif
@endsection
