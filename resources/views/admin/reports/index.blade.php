@extends('layouts.admin')

@section('title', 'Laporan')

@section('content')
    <div class="mb-6">
        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#4162a5]">Analisis bisnis</p>
        <h1 class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">Laporan</h1>
        <p class="mt-1 text-sm text-slate-500">Pantau pesanan, pendapatan yang sudah diterima, dan produk terlaris.</p>
    </div>

    <form method="GET" action="{{ route('admin.reports.index') }}" class="mb-5 grid gap-3 rounded-xl border border-[#e6ebf5] bg-white p-4 shadow-sm sm:grid-cols-[1fr_1fr_auto] sm:items-end">
        <div>
            <label for="report-from" class="mb-1.5 block text-xs font-semibold text-slate-700">Dari tanggal</label>
            <input id="report-from" type="date" name="from" value="{{ $from->toDateString() }}" required class="w-full rounded-lg border-[#dce4f5] text-xs focus:border-[#31569e] focus:ring-[#31569e]">
        </div>
        <div>
            <label for="report-to" class="mb-1.5 block text-xs font-semibold text-slate-700">Sampai tanggal</label>
            <input id="report-to" type="date" name="to" value="{{ $to->toDateString() }}" required class="w-full rounded-lg border-[#dce4f5] text-xs focus:border-[#31569e] focus:ring-[#31569e]">
        </div>
        <button class="min-h-9 rounded-lg bg-[#12377f] px-4 text-xs font-bold text-white hover:bg-[#09296d]">Tampilkan laporan</button>
    </form>

    <p class="mb-4 text-xs text-slate-500">
        Periode {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}
        <span class="ml-1">· Pendapatan menghitung pesanan lunas dan tidak dibatalkan.</span>
    </p>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3" aria-label="Ringkasan laporan">
        <article class="rounded-xl border border-[#e6ebf5] bg-white p-4 shadow-[0_4px_16px_rgba(36,57,110,0.05)]">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#e9efff] text-sm font-extrabold text-[#173c91]" aria-hidden="true">P</span>
            <p class="mt-3 text-[10px] font-medium text-slate-500">Total Pesanan</p>
            <p class="mt-1 text-2xl font-extrabold text-[#202a3b]">{{ number_format($totalOrders, 0, ',', '.') }}</p>
        </article>
        <article class="rounded-xl border border-[#173c91] bg-[#203f91] p-4 text-white shadow-[0_4px_16px_rgba(36,57,110,0.12)]">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/15 text-xs font-extrabold" aria-hidden="true">Rp</span>
            <p class="mt-3 text-[10px] font-medium text-blue-100">Pendapatan Diterima</p>
            <p class="mt-1 text-xl font-extrabold">Rp{{ number_format($totalRevenue, 0, ',', '.') }}</p>
        </article>
        <article class="rounded-xl border border-[#e6ebf5] bg-white p-4 shadow-[0_4px_16px_rgba(36,57,110,0.05)]">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#fff0f0] text-sm font-extrabold text-rose-600" aria-hidden="true">!</span>
            <p class="mt-3 text-[10px] font-medium text-slate-500">Pesanan Dibatalkan</p>
            <p class="mt-1 text-2xl font-extrabold text-[#202a3b]">{{ number_format($cancelledOrders, 0, ',', '.') }}</p>
        </article>
    </section>

    <section class="mt-5 overflow-hidden rounded-xl border border-[#e6ebf5] bg-white shadow-[0_4px_16px_rgba(36,57,110,0.05)]" aria-labelledby="top-products-title">
        <div class="px-4 py-4 sm:px-5">
            <h2 id="top-products-title" class="text-sm font-bold text-[#202a3b]">Produk Terlaris</h2>
            <p class="mt-1 text-[10px] text-slate-500">Berdasarkan jumlah unit pada pesanan yang tidak dibatalkan.</p>
        </div>
        @if ($topProducts->isEmpty())
            <div class="border-t border-[#edf0f7] px-5 py-10 text-center">
                <p class="text-xs font-semibold text-[#202a3b]">Belum ada data penjualan</p>
                <p class="mt-1 text-[11px] text-slate-500">Produk terlaris akan muncul setelah pesanan dibuat pada periode ini.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[440px] text-left">
                    <thead class="border-y border-[#edf0f7] bg-[#f8faff] text-[9px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3 sm:px-5">Peringkat</th>
                            <th scope="col" class="px-4 py-3">Nama Produk</th>
                            <th scope="col" class="px-4 py-3 text-right sm:px-5">Terjual</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#edf0f7]">
                        @foreach ($topProducts as $product)
                            <tr class="text-xs">
                                <td class="px-4 py-3 font-bold text-[#31569e] sm:px-5">#{{ $loop->iteration }}</td>
                                <td class="px-4 py-3 font-semibold text-[#202a3b]">{{ $product->name }}</td>
                                <td class="px-4 py-3 text-right font-bold text-slate-700 sm:px-5">{{ number_format($product->total_sold, 0, ',', '.') }} {{ $product->unit }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
