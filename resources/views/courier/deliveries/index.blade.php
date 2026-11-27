@extends('layouts.courier')

@section('title', 'Tugas Aktif')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#4162a5]">Portal kurir</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">Tugas Pengantaran</h1>
            <p class="mt-1 text-sm text-slate-500">Halo {{ $courier->name }}, berikut pengantaran yang ditugaskan kepadamu.</p>
        </div>
        <div class="rounded-xl border border-[#e6ebf5] bg-white px-4 py-3 text-xs shadow-sm">
            <span class="text-slate-500">Selesai</span>
            <span class="ml-2 font-extrabold text-[#12377f]">{{ number_format($completedCount, 0, ',', '.') }}</span>
        </div>
    </div>

    @if ($assignedRoutes->isNotEmpty())
        <section class="mb-5 space-y-3" aria-labelledby="assigned-routes-title">
            <div>
                <h2 id="assigned-routes-title" class="text-sm font-bold text-[#202a3b]">Rute yang ditetapkan</h2>
                <p class="mt-1 text-[10px] text-slate-500">Ikuti urutan pemberhentian yang telah disusun admin.</p>
            </div>
            @foreach ($assignedRoutes as $route)
                <article class="rounded-2xl border border-[#dce5f7] bg-white p-4 shadow-sm sm:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-xs font-extrabold text-[#12377f]">Rute #{{ $route->id }}</h3>
                            <p class="mt-1 text-[10px] text-slate-500">Tanggal pengiriman {{ $route->route_date->timezone(config('app.display_timezone'))->format('d M Y') }}</p>
                        </div>
                        <div class="flex gap-2 text-[9px] font-semibold text-slate-600">
                            <span class="rounded-full bg-[#edf2ff] px-2.5 py-1">{{ $route->stops->count() }} titik</span>
                            @if ($route->total_distance_meters)
                                <span class="rounded-full bg-[#edf2ff] px-2.5 py-1">{{ number_format($route->total_distance_meters / 1000, 1, ',', '.') }} km</span>
                            @endif
                        </div>
                    </div>
                    <ol class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($route->stops as $stop)
                            @php($currentDeliveryId = $deliveries->firstWhere('routeStop.route_id', $route->id)?->id)
                            <li class="flex items-start gap-2 rounded-lg {{ $stop->delivery_id === $currentDeliveryId ? 'bg-[#edf2ff]' : 'bg-slate-50' }} px-3 py-2.5">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#12377f] text-[9px] font-bold text-white">{{ $stop->visit_order }}</span>
                                <span class="min-w-0">
                                    <span class="block text-[9px] font-bold text-[#202a3b]">Pemberhentian {{ $stop->visit_order }}</span>
                                    <span class="mt-0.5 block text-[9px] leading-4 text-slate-600">{{ $stop->delivery->order->delivery_address ?? 'Alamat pengantaran tidak tersedia' }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ol>
                </article>
            @endforeach
        </section>
    @endif

    @if ($deliveries->isEmpty())
        <section class="rounded-2xl border border-[#e8ecf6] bg-white px-5 py-12 text-center shadow-sm">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#edf2ff] text-xl font-extrabold text-[#31569e]" aria-hidden="true">✓</span>
            <h2 class="mt-4 text-base font-bold text-[#202a3b]">Tidak ada tugas aktif</h2>
            <p class="mt-2 text-sm text-slate-500">Tugas baru akan muncul setelah admin menugaskan pengantaran kepadamu.</p>
        </section>
    @else
        <section class="grid gap-4 md:grid-cols-2" aria-label="Daftar tugas aktif">
            @foreach ($deliveries as $delivery)
                <article class="rounded-2xl border border-[#e6ebf5] bg-white p-4 shadow-sm sm:p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold text-[#202a3b]">Pesanan #{{ $delivery->order->id }}</p>
                            <p class="mt-1 text-[10px] text-slate-500">{{ ($delivery->order->ordered_at ?? $delivery->order->created_at)->timezone(config('app.display_timezone'))->format('d M Y, H:i') }}</p>
                        </div>
                        <span class="rounded-full {{ $delivery->status === 'in_transit' ? 'bg-sky-50 text-sky-700' : 'bg-amber-50 text-amber-800' }} px-2.5 py-1 text-[9px] font-bold">
                            {{ $delivery->status === 'in_transit' ? 'Dalam perjalanan' : 'Menunggu dimulai' }}
                        </span>
                    </div>
                    <div class="mt-4 border-t border-[#edf0f7] pt-4">
                        <p class="text-xs font-semibold text-[#202a3b]">{{ $delivery->order->user->name }}</p>
                        <p class="mt-1 text-xs leading-5 text-slate-600">{{ $delivery->order->delivery_address ?? 'Alamat pengantaran tidak tersedia' }}</p>
                        @if ($delivery->routeStop)
                            <p class="mt-2 text-[10px] font-semibold text-[#31569e]">Rute #{{ $delivery->routeStop->route->id }} · Pemberhentian {{ $delivery->routeStop->visit_order }} dari {{ $delivery->routeStop->route->stops->count() }} · {{ $delivery->routeStop->route->route_date->timezone(config('app.display_timezone'))->format('d M Y') }}</p>
                        @endif
                        @if ($delivery->order->notes)
                            <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-[10px] leading-4 text-amber-900"><span class="font-bold">Catatan:</span> {{ $delivery->order->notes }}</p>
                        @endif
                    </div>
                    <a href="{{ route('courier.deliveries.show', $delivery) }}" class="mt-4 inline-flex min-h-10 w-full items-center justify-center rounded-lg bg-[#12377f] px-4 py-2 text-xs font-bold text-white hover:bg-[#09296d]">Lihat detail tugas</a>
                </article>
            @endforeach
        </section>
    @endif
@endsection
