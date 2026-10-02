@extends('layouts.courier')

@section('title', 'Detail Pengantaran')

@section('content')
    @php($statusLabels = ['assigned' => 'Menunggu dimulai', 'in_transit' => 'Dalam perjalanan', 'delivered' => 'Selesai diantar', 'failed' => 'Gagal'])
    <a href="{{ $delivery->status === 'delivered' ? route('courier.history') : route('courier.dashboard') }}" class="text-xs font-bold text-[#31569e] hover:underline">← Kembali</a>
    <div class="mb-6 mt-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#4162a5]">Detail tugas</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">Pesanan #{{ $delivery->order->id }}</h1>
            <p class="mt-1 text-sm text-slate-500">Dibuat {{ ($delivery->order->ordered_at ?? $delivery->order->created_at)->timezone(config('app.display_timezone'))->format('d M Y, H:i') }}</p>
        </div>
        <span class="rounded-full {{ $delivery->status === 'delivered' ? 'bg-emerald-50 text-emerald-700' : ($delivery->status === 'in_transit' ? 'bg-sky-50 text-sky-700' : 'bg-amber-50 text-amber-800') }} px-3 py-1.5 text-[10px] font-bold">{{ $statusLabels[$delivery->status] ?? $delivery->status }}</span>
    </div>

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(280px,0.75fr)]">
        <div class="space-y-5">
            <section class="rounded-xl border border-[#e6ebf5] bg-white p-4 shadow-sm sm:p-5">
                <h2 class="text-sm font-bold text-[#202a3b]">Informasi pelanggan</h2>
                <p class="mt-4 text-xs font-semibold text-[#202a3b]">{{ $delivery->order->user->name }}</p>
                <p class="mt-1 text-xs text-slate-600">{{ $delivery->order->user->phone ?: 'Nomor telepon tidak tersedia' }}</p>
                <h3 class="mt-4 text-[10px] font-bold uppercase tracking-wide text-slate-500">{{ $delivery->order->address?->label ?: 'Alamat pengantaran' }}</h3>
                <p class="mt-1 whitespace-pre-line text-xs leading-5 text-slate-700">{{ $delivery->order->address?->full_address ?? 'Alamat tidak tersedia' }}</p>
                @if ($delivery->order->notes)
                    <p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-xs leading-5 text-amber-900"><span class="font-bold">Catatan pesanan:</span> {{ $delivery->order->notes }}</p>
                @endif
            </section>

            @if ($delivery->routeStop?->route)
                <section class="rounded-xl border border-[#e6ebf5] bg-white p-4 shadow-sm sm:p-5">
                    <h2 class="text-sm font-bold text-[#202a3b]">Urutan rute</h2>
                    <p class="mt-1 text-[10px] text-slate-500">Rute {{ $delivery->routeStop->route->route_date->timezone(config('app.display_timezone'))->format('d M Y') }}</p>
                    <ol class="mt-4 space-y-3">
                        @foreach ($delivery->routeStop->route->stops as $stop)
                            <li class="flex gap-3 rounded-lg {{ $stop->delivery_id === $delivery->id ? 'bg-[#edf2ff]' : 'bg-slate-50' }} p-3">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white text-[10px] font-extrabold text-[#31569e]">{{ $stop->visit_order }}</span>
                                <span class="text-xs leading-5 text-slate-700">{{ $stop->delivery->order->address?->full_address ?? 'Alamat tidak tersedia' }}</span>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif
        </div>

        <div class="space-y-5">
            <section class="rounded-xl border border-[#e6ebf5] bg-white p-4 shadow-sm sm:p-5">
                <h2 class="text-sm font-bold text-[#202a3b]">Barang pesanan</h2>
                <ul class="mt-4 divide-y divide-[#edf0f7]">
                    @foreach ($delivery->order->items as $item)
                        <li class="flex items-start justify-between gap-3 py-3 first:pt-0 last:pb-0">
                            <span class="text-xs font-semibold text-[#202a3b]">{{ $item->product?->name ?? 'Produk tidak tersedia' }} <span class="font-normal text-slate-500">× {{ $item->quantity }}</span></span>
                            <span class="whitespace-nowrap text-xs text-slate-600">Rp{{ number_format($item->price_snapshot * $item->quantity, 0, ',', '.') }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4 flex justify-between border-t border-[#edf0f7] pt-4 text-xs">
                    <span class="font-semibold text-slate-600">Total pesanan</span>
                    <span class="font-extrabold text-[#09296d]">Rp{{ number_format($delivery->order->total_price, 0, ',', '.') }}</span>
                </div>
                <p class="mt-2 text-[10px] text-slate-500">Pembayaran: {{ $delivery->order->payment_method === 'cod' ? 'COD' : 'Transfer bank' }}</p>
            </section>

            @if ($delivery->status === 'assigned')
                <form method="POST" action="{{ route('courier.deliveries.status', $delivery) }}" class="rounded-xl border border-[#e6ebf5] bg-white p-4 shadow-sm sm:p-5">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="in_transit">
                    <p class="text-xs leading-5 text-slate-600">Mulai pengantaran saat kamu siap berangkat. Status pesanan pelanggan akan diperbarui.</p>
                    <button class="mt-4 min-h-10 w-full rounded-lg bg-[#12377f] px-4 py-2 text-xs font-bold text-white hover:bg-[#09296d]">Mulai pengantaran</button>
                </form>
            @elseif ($delivery->status === 'in_transit')
                <form method="POST" action="{{ route('courier.deliveries.status', $delivery) }}" class="rounded-xl border border-[#e6ebf5] bg-white p-4 shadow-sm sm:p-5" onsubmit="return confirm('Konfirmasi pesanan ini sudah diterima pelanggan?')">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="delivered">
                    <p class="text-xs leading-5 text-slate-600">Selesaikan tugas setelah pesanan diterima pelanggan. Status akan terlihat pada akun pelanggan dan admin.</p>
                    <button class="mt-4 min-h-10 w-full rounded-lg bg-emerald-700 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-800">Tandai selesai diantar</button>
                </form>
            @elseif ($delivery->status === 'delivered')
                <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 sm:p-5">
                    <p class="text-xs font-bold text-emerald-800">Pengantaran selesai</p>
                    <p class="mt-1 text-[10px] text-emerald-700">{{ $delivery->delivered_at?->timezone(config('app.display_timezone'))->format('d M Y, H:i') }}</p>
                </section>
            @endif
        </div>
    </div>
@endsection
