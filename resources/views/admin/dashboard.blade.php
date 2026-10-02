@extends('layouts.admin')

@section('title', 'Dashboard Admin')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Ringkasan Hari Ini</h1>
        <p class="mt-1 text-sm text-slate-500">Pantau performa operasional Padmatirta Wisesa Depo terkini.</p>
    </div>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5" aria-label="Ringkasan operasional">
        <article class="rounded-xl border border-[#e6ebf5] bg-white p-4 shadow-[0_4px_16px_rgba(36,57,110,0.05)]">
            <div class="flex items-start justify-between">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#e9efff] text-[#173c91]" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none"><rect x="3" y="3" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M6.5 7h7m-7 3h7m-7 3h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </span>
                <span class="rounded-full bg-[#e9f9f6] px-2 py-1 text-[9px] font-bold text-emerald-700">Semua waktu</span>
            </div>
            <p class="mt-3 text-[10px] font-medium text-slate-500">Total Pesanan</p>
            <p class="mt-1 text-2xl font-extrabold text-[#202a3b]">{{ number_format($stats['total_orders'], 0, ',', '.') }}</p>
        </article>
        <article class="rounded-xl border border-[#e6ebf5] bg-white p-4 shadow-[0_4px_16px_rgba(36,57,110,0.05)]">
            <div class="flex items-start justify-between">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#fff0f0] text-rose-600" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none"><path d="M2.5 4h2l1.6 8a1.7 1.7 0 0 0 1.7 1.4h7.1a1.7 1.7 0 0 0 1.7-1.4L18 6.5H5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="8" cy="16.5" r="1.2" fill="currentColor"/><circle cx="15" cy="16.5" r="1.2" fill="currentColor"/><path d="M10 2v4m-2-2h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </span>
                <span class="rounded-full bg-[#edf7ff] px-2 py-1 text-[9px] font-bold text-sky-700">Hari ini</span>
            </div>
            <p class="mt-3 text-[10px] font-medium text-slate-500">Pesanan Hari Ini</p>
            <p class="mt-1 text-2xl font-extrabold text-[#202a3b]">{{ number_format($stats['today_orders'], 0, ',', '.') }}</p>
        </article>
        <article class="rounded-xl border border-[#173c91] bg-[#203f91] p-4 text-white shadow-[0_4px_16px_rgba(36,57,110,0.12)]">
            <div class="flex items-start justify-between">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/15" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none"><rect x="2.5" y="4" width="15" height="12" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M2.5 8h15M6 12h2.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><circle cx="13.5" cy="12.5" r="1.5" stroke="currentColor" stroke-width="1.5"/></svg>
                </span>
                <span class="rounded-full bg-white/15 px-2 py-1 text-[9px] font-bold">Bulan ini</span>
            </div>
            <p class="mt-3 text-[10px] font-medium text-blue-100">Pendapatan Diterima</p>
            <p class="mt-1 text-xl font-extrabold">Rp{{ number_format($stats['monthly_revenue'], 0, ',', '.') }}</p>
        </article>
        <article class="rounded-xl border border-[#e6ebf5] bg-white p-4 shadow-[0_4px_16px_rgba(36,57,110,0.05)]">
            <div class="flex items-start justify-between">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#e6fbff] text-cyan-700" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none"><circle cx="7" cy="6.5" r="2.7" stroke="currentColor" stroke-width="1.6"/><path d="M2.5 16a4.5 4.5 0 0 1 9 0M13 4.2a2.7 2.7 0 0 1 0 4.8m1.2 2a4 4 0 0 1 3.3 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                </span>
                <span class="rounded-full bg-[#f1f5ff] px-2 py-1 text-[9px] font-bold text-[#31569e]">30 hari</span>
            </div>
            <p class="mt-3 text-[10px] font-medium text-slate-500">Pelanggan Aktif</p>
            <p class="mt-1 text-2xl font-extrabold text-[#202a3b]">{{ number_format($stats['active_customers'], 0, ',', '.') }}</p>
        </article>
        <article class="rounded-xl border border-[#e6ebf5] bg-white p-4 shadow-[0_4px_16px_rgba(36,57,110,0.05)]">
            <div class="flex items-start justify-between">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#fff1e8] text-amber-800" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none"><path d="M2 5.5h9.5v8H2zM11.5 8h3.2l3.3 3v2.5h-6.5z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><circle cx="5.2" cy="14.5" r="1.7" fill="white" stroke="currentColor" stroke-width="1.5"/><circle cx="14.8" cy="14.5" r="1.7" fill="white" stroke="currentColor" stroke-width="1.5"/></svg>
                </span>
                <span class="h-2 w-2 rounded-full bg-cyan-500" aria-label="Kurir tersedia"></span>
            </div>
            <p class="mt-3 text-[10px] font-medium text-slate-500">Kurir Tersedia</p>
            <p class="mt-1 text-2xl font-extrabold text-[#202a3b]">{{ $stats['available_couriers'] }}<span class="ml-1 text-sm font-medium text-slate-400">/ {{ $stats['total_couriers'] }}</span></p>
        </article>
    </section>

    <div class="mt-5 grid gap-4 xl:grid-cols-[minmax(0,1.6fr)_minmax(270px,0.8fr)]">
        <section class="rounded-xl border border-[#e6ebf5] bg-white p-4 shadow-[0_4px_16px_rgba(36,57,110,0.05)] sm:p-5" aria-labelledby="weekly-orders-title">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 id="weekly-orders-title" class="text-sm font-bold text-[#202a3b]">Tren Pesanan (7 Hari)</h2>
                    <p class="mt-1 text-[10px] text-slate-500">Jumlah pesanan berdasarkan tanggal dibuat.</p>
                </div>
                <a href="{{ route('admin.orders.index') }}" class="text-[10px] font-bold text-[#31569e] hover:underline">Lihat detail</a>
            </div>
            <div class="mt-4">
                <svg viewBox="0 0 400 190" role="img" aria-label="Grafik tren jumlah pesanan tujuh hari terakhir" class="h-52 w-full overflow-visible">
                    @foreach ([0, 1, 2, 3] as $grid)
                        @php($gridY = 30 + ($grid * 37))
                        <line x1="12" y1="{{ $gridY }}" x2="388" y2="{{ $gridY }}" stroke="#e8edf6" stroke-width="1" />
                        <text x="0" y="{{ $gridY + 3 }}" fill="#94a3b8" font-size="8">{{ max(0, (int) ceil($trend->max('count') * (3 - $grid) / 3)) }}</text>
                    @endforeach
                    @php($lastPoint = $chartPoints->last())
                    <polygon points="{{ $trendLine }} {{ number_format($lastPoint['x'], 1, '.', '') }},150 12,150" fill="#173c91" fill-opacity="0.12" />
                    <polyline points="{{ $trendLine }}" fill="none" stroke="#173c91" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                    @foreach ($chartPoints as $point)
                        <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="3.5" fill="white" stroke="#173c91" stroke-width="2" />
                    @endforeach
                    @foreach ($trend as $index => $day)
                        <text x="{{ 12 + ($index * 376 / 6) }}" y="174" text-anchor="middle" fill="#64748b" font-size="9">{{ $day['label'] }}</text>
                    @endforeach
                </svg>
            </div>
        </section>

        <section class="rounded-xl border border-[#e6ebf5] bg-white p-4 shadow-[0_4px_16px_rgba(36,57,110,0.05)] sm:p-5" aria-labelledby="order-status-title">
            <h2 id="order-status-title" class="text-sm font-bold text-[#202a3b]">Status Pesanan</h2>
            <div class="mx-auto mt-5 flex h-36 w-36 items-center justify-center rounded-full" style="background: {{ $statusGradient }}">
                <div class="flex h-24 w-24 flex-col items-center justify-center rounded-full bg-white">
                    <span class="text-2xl font-extrabold text-[#202a3b]">{{ number_format($stats['total_orders'], 0, ',', '.') }}</span>
                    <span class="text-[9px] text-slate-500">pesanan</span>
                </div>
            </div>
            <ul class="mt-5 space-y-2">
                @foreach ($statusBreakdown as $status)
                    <li class="flex items-center justify-between gap-3 text-[10px]">
                        <span class="flex items-center gap-2 text-slate-600"><span class="h-2 w-2 rounded-full" style="background-color: {{ $status['color'] }}"></span>{{ $status['label'] }}</span>
                        <span class="font-semibold text-slate-700">{{ $status['count'] }} <span class="text-slate-400">({{ $status['percentage'] }}%)</span></span>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    <section class="mt-5 overflow-hidden rounded-xl border border-[#e6ebf5] bg-white shadow-[0_4px_16px_rgba(36,57,110,0.05)]" aria-labelledby="recent-orders-title">
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-5">
            <div>
                <h2 id="recent-orders-title" class="text-sm font-bold text-[#202a3b]">Pesanan Terbaru</h2>
                <p class="mt-1 text-[10px] text-slate-500">Aktivitas pesanan pelanggan terkini.</p>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="rounded-lg border border-[#d4dceb] px-3 py-2 text-[10px] font-bold text-slate-600 hover:bg-slate-50">Lihat semua</a>
        </div>
        @if ($recentOrders->isEmpty())
            <div class="border-t border-[#edf0f7] px-5 py-10 text-center">
                <p class="text-sm font-semibold text-slate-600">Belum ada pesanan untuk ditampilkan.</p>
                <p class="mt-1 text-xs text-slate-400">Pesanan pelanggan akan muncul di sini.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left">
                    <thead class="bg-[#eef3ff] text-[9px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3">ID Pesanan</th>
                            <th scope="col" class="px-4 py-3">Pelanggan</th>
                            <th scope="col" class="px-4 py-3">Produk</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                            <th scope="col" class="px-4 py-3 text-right">Pembayaran</th>
                            <th scope="col" class="px-4 py-3 text-right">Total Harga</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#edf0f7]">
                        @foreach ($recentOrders as $order)
                            @php($statusClasses = ['pending' => 'bg-amber-100 text-amber-800', 'confirmed' => 'bg-blue-100 text-blue-800', 'processing' => 'bg-indigo-100 text-indigo-800', 'delivered' => 'bg-cyan-100 text-cyan-800', 'cancelled' => 'bg-rose-100 text-rose-800'])
                            <tr class="text-[10px] hover:bg-[#fbfcff]">
                                <td class="whitespace-nowrap px-4 py-3.5 font-semibold text-slate-600">#ORD-{{ str_pad((string) $order->id, 3, '0', STR_PAD_LEFT) }}</td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#e1f5f9] text-[9px] font-bold uppercase text-[#13738a]">{{ mb_substr($order->user->name, 0, 1) }}</span>
                                        <span class="font-semibold text-slate-700">{{ $order->user->name }}</span>
                                    </div>
                                </td>
                                <td class="max-w-64 px-4 py-3.5 text-slate-500">
                                    @foreach ($order->items as $item)
                                        {{ $item->quantity }}× {{ $item->product->name }}@if (! $loop->last), @endif
                                    @endforeach
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="whitespace-nowrap rounded-full px-2 py-1 text-[9px] font-bold {{ $statusClasses[$order->status] ?? 'bg-slate-100 text-slate-700' }}">{{ ['pending' => 'Menunggu', 'confirmed' => 'Dikonfirmasi', 'processing' => 'Dalam pengantaran', 'delivered' => 'Selesai', 'cancelled' => 'Dibatalkan'][$order->status] ?? $order->status }}</span>
                                </td>
                                @php($paymentLabels = ['unpaid' => 'Belum dibayar', 'pending_verification' => 'Menunggu verifikasi', 'rejected' => 'Bukti ditolak', 'paid' => 'Lunas'])
                                <td class="whitespace-nowrap px-4 py-3.5 text-right text-slate-600">{{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}</td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-right font-semibold text-slate-700">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="hover:text-[#173c91]">Rp{{ number_format($order->total_price, 0, ',', '.') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
