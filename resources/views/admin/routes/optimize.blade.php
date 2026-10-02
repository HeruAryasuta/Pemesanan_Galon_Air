@extends('layouts.admin')

@section('title', 'Optimasi Rute')

@section('content')
    <div class="mb-5">
        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#4162a5]">Operasional pengantaran</p>
        <h1 class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">Optimasi Rute</h1>
        <p class="mt-1 text-sm text-slate-500">Pilih pengantaran, tentukan kurir, lalu tinjau urutan pemberhentian hasil metode nearest neighbor.</p>
    </div>

    @if (session('success') || session('error') || $errors->any())
        <div class="mb-5" role="status" aria-live="polite">
            @if (session('success'))
                <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</p>
            @elseif (session('error'))
                <p class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">{{ session('error') }}</p>
            @else
                <p class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">{{ $errors->first() }}</p>
            @endif
        </div>
    @endif

    @if ($missingCoordinatesCount > 0)
        <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-900" role="status">
            {{ $missingCoordinatesCount }} pengantaran aktif belum dapat dirutekan karena alamat belum memiliki koordinat latitude dan longitude. Perbarui koordinat alamat sebelum memasukkannya ke rute.
        </div>
    @endif

    <div class="grid items-start gap-4 xl:grid-cols-[250px_minmax(360px,1fr)_270px]">
        <form id="route-preview-form" method="GET" action="{{ route('admin.routes.optimize') }}" class="space-y-4">
            <section class="rounded-2xl border border-[#e7ebf5] bg-white p-4 shadow-[0_5px_20px_rgba(29,48,99,0.05)]" aria-labelledby="route-config-heading">
                <h2 id="route-config-heading" class="flex items-center gap-2 text-sm font-bold text-[#202a3b]">
                    <svg class="h-4 w-4 text-[#31569e]" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3 5h14M5.5 10h9M8 15h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><circle cx="6" cy="5" r="1.5" fill="white" stroke="currentColor" stroke-width="1.5"/><circle cx="13" cy="10" r="1.5" fill="white" stroke="currentColor" stroke-width="1.5"/></svg>
                    Konfigurasi Rute
                </h2>

                <div class="mt-4">
                    <label for="route-courier" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Pilih kurir</label>
                    <select id="route-courier" name="courier_id" class="w-full rounded-lg border-[#dce4f5] bg-[#f8faff] text-xs focus:border-[#31569e] focus:ring-[#31569e]">
                        <option value="">Pilih kurir</option>
                        @foreach ($couriers as $courier)
                            <option value="{{ $courier->id }}" @selected((string) $selectedCourierId === (string) $courier->id)>{{ $courier->name }} · {{ $courier->vehicle_type ?: 'Kendaraan belum diatur' }}</option>
                        @endforeach
                    </select>
                    @if ($couriers->isEmpty())
                        <p class="mt-1.5 text-[10px] leading-4 text-amber-700">Tidak ada kurir tersedia. Selesaikan rute aktif atau tandai kurir tersedia sebelum membuat rute baru.</p>
                    @endif
                    @error('courier_id')<p class="mt-1 text-[10px] text-rose-700">{{ $message }}</p>@enderror
                </div>

                <div class="mt-3">
                    <label for="route-date" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Tanggal pengiriman</label>
                    <input id="route-date" name="route_date" type="date" value="{{ $routeDate }}" class="w-full rounded-lg border-[#dce4f5] bg-[#f8faff] text-xs focus:border-[#31569e] focus:ring-[#31569e]">
                    @error('route_date')<p class="mt-1 text-[10px] text-rose-700">{{ $message }}</p>@enderror
                </div>

                <fieldset class="mt-4">
                    <legend class="mb-2 block text-[11px] font-semibold text-slate-600">Target optimasi</legend>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="rounded-xl border-2 border-[#31569e] bg-[#edf2ff] px-2 py-2.5 text-center text-[10px] font-bold text-[#12377f]" aria-current="true">
                            <svg class="mx-auto mb-1 h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3 15.5 7 11l3 2 6.5-7M12 6h4.5v4.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Jarak terpendek
                        </div>
                        <div class="rounded-xl border border-[#e4e8f2] bg-slate-50 px-2 py-2.5 text-center text-[10px] font-medium text-slate-400" aria-disabled="true" title="Estimasi waktu tempuh belum tersedia">
                            <svg class="mx-auto mb-1 h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="7" stroke="currentColor" stroke-width="1.5"/><path d="M10 6v4l2.5 1.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                            Waktu tercepat
                        </div>
                    </div>
                </fieldset>
                <p class="mt-2 text-[9px] leading-4 text-slate-500">Waktu tempuh dan kondisi lalu lintas belum dihitung.</p>
            </section>

            <section class="rounded-2xl border border-[#e7ebf5] bg-white p-4 shadow-[0_5px_20px_rgba(29,48,99,0.05)]" aria-labelledby="active-deliveries-heading">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <div>
                        <h2 id="active-deliveries-heading" class="text-xs font-bold text-[#202a3b]">Pesanan aktif ({{ $deliveries->count() }})</h2>
                        <p class="mt-1 text-[9px] text-slate-500">Tugas dengan koordinat alamat</p>
                    </div>
                    @if ($deliveries->isNotEmpty())
                        <button type="button" data-select-all class="text-[9px] font-bold text-[#31569e] hover:text-[#09296d]">Pilih Semua</button>
                    @endif
                </div>

                @if ($deliveries->isEmpty())
                    <div class="rounded-xl bg-slate-50 px-3 py-7 text-center">
                        <p class="text-[11px] font-semibold text-[#202a3b]">Belum ada pesanan siap dirutekan</p>
                        <p class="mt-1 text-[9px] leading-4 text-slate-500">Pastikan alamat pesanan aktif memiliki koordinat.</p>
                    </div>
                @else
                    <div class="max-h-[430px] space-y-2 overflow-y-auto pr-1">
                        @foreach ($deliveries as $delivery)
                            <label class="flex cursor-pointer items-start gap-2.5 rounded-xl border border-[#e7ebf5] bg-white p-2.5 transition hover:border-[#b9c9eb] hover:bg-[#fbfcff] has-[:checked]:border-[#9db2df] has-[:checked]:bg-[#f4f7ff]">
                                <input type="checkbox" name="delivery_ids[]" value="{{ $delivery->id }}" @checked(in_array($delivery->id, $selectedIds, true)) class="mt-0.5 h-3.5 w-3.5 rounded border-slate-300 text-[#12377f] focus:ring-[#31569e]" data-route-delivery>
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center justify-between gap-2">
                                        <span class="truncate text-[10px] font-bold text-[#202a3b]">ORD-{{ $delivery->order->id }} · {{ $delivery->order->user->name }}</span>
                                        <span class="shrink-0 rounded-full bg-[#e8efff] px-1.5 py-0.5 text-[8px] font-semibold text-[#31569e]">{{ $delivery->order->items->first()?->product?->category?->name ?? 'Pesanan' }}</span>
                                    </span>
                                    <span class="mt-1 block truncate text-[9px] leading-4 text-slate-500">{{ $delivery->order->address->full_address }}</span>
                                    <span class="mt-1 block text-[8px] text-slate-400">{{ number_format((float) $delivery->order->address->latitude, 5, ',', '.') }}, {{ number_format((float) $delivery->order->address->longitude, 5, ',', '.') }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </section>
        </form>

        <section class="overflow-hidden rounded-2xl border border-[#e7ebf5] bg-white shadow-[0_5px_20px_rgba(29,48,99,0.06)]" aria-labelledby="route-map-heading">
            <div class="flex min-h-14 flex-wrap items-center justify-between gap-2 border-b border-[#e9edf5] px-4 py-3">
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-[#e9f2ff] text-[#31569e]" aria-hidden="true">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none"><path d="M10 17s5.5-5.1 5.5-9.3a5.5 5.5 0 1 0-11 0C4.5 11.9 10 17 10 17Z" stroke="currentColor" stroke-width="1.6"/><circle cx="10" cy="7.5" r="1.8" stroke="currentColor" stroke-width="1.5"/></svg>
                    </span>
                    <div>
                        <h2 id="route-map-heading" class="text-xs font-bold text-[#202a3b]">Pratinjau rute</h2>
                        <p class="text-[9px] text-slate-500">Tampilan skematis dari koordinat pesanan</p>
                    </div>
                </div>
                <span class="rounded-full bg-[#f2f5fb] px-2.5 py-1 text-[9px] font-semibold text-slate-600">Gunung Anyar, Surabaya</span>
            </div>

            <div class="relative h-[430px] overflow-hidden bg-[#eaf0ed] sm:h-[520px] xl:h-[570px]">
                <svg class="absolute inset-0 h-full w-full" viewBox="0 0 1000 700" preserveAspectRatio="xMidYMid slice" role="img" aria-label="Skema posisi koordinat dan urutan rute pengantaran">
                    <defs>
                        <pattern id="route-map-grid" width="110" height="100" patternUnits="userSpaceOnUse" patternTransform="rotate(-8)">
                            <path d="M0 0H110M0 0V100" fill="none" stroke="#d4dfd8" stroke-width="1"/>
                        </pattern>
                        <filter id="route-marker-shadow" x="-50%" y="-50%" width="200%" height="200%">
                            <feDropShadow dx="0" dy="2" stdDeviation="3" flood-color="#20304a" flood-opacity=".24"/>
                        </filter>
                    </defs>
                    <rect width="1000" height="700" fill="#edf2ed"/>
                    <rect width="1000" height="700" fill="url(#route-map-grid)"/>
                    <path d="M-40 100 1040 202M-40 335 1040 244M-40 570 1040 482M105 740 252-40M430 740 535-40M760 740 710-40M965 740 885-40" fill="none" stroke="#d7e2dc" stroke-width="36"/>
                    <path d="M-40 100 1040 202M-40 335 1040 244M-40 570 1040 482M105 740 252-40M430 740 535-40M760 740 710-40M965 740 885-40" fill="none" stroke="#fffefa" stroke-width="27"/>
                    <path d="M-20 434 1020 333M315 720 356-20M603 720 620-20" fill="none" stroke="#d8cfa4" stroke-width="8"/>
                    <path d="M-20 434 1020 333M315 720 356-20M603 720 620-20" fill="none" stroke="#fff5c8" stroke-width="4"/>
                    <path d="M60 60h165v92H60zm260 30h115v86H320zm365-15h180v106H685zM112 485h142v90H112zm380 42h176v118H492zm323-26h127v113H815z" fill="#dce9dd" stroke="#c8d9cd" stroke-width="2"/>
                    <path d="M80 205h112m88-80 50 1m290 30 88 1M158 395h94m190-47h115m250 65h78M290 608h100m330-13h74" stroke="#c7d4ce" stroke-width="5" stroke-linecap="round"/>

                    @if ($mapPoints->count() > 1)
                        <polyline
                            points="@foreach ($mapPoints as $point){{ number_format($point['x'], 1, '.', '') }},{{ number_format($point['y'], 1, '.', '') }}@if (! $loop->last) @endif @endforeach"
                            fill="none"
                            stroke="#fff"
                            stroke-width="14"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                        <polyline
                            points="@foreach ($mapPoints as $point){{ number_format($point['x'], 1, '.', '') }},{{ number_format($point['y'], 1, '.', '') }}@if (! $loop->last) @endif @endforeach"
                            fill="none"
                            stroke="#31569e"
                            stroke-width="8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    @endif

                    @foreach ($mapPoints as $index => $point)
                        <g transform="translate({{ number_format($point['x'], 1, '.', '') }} {{ number_format($point['y'], 1, '.', '') }})" filter="url(#route-marker-shadow)">
                            <circle r="24" fill="#fff" opacity=".95"/>
                            <circle r="18" fill="#12377f"/>
                            <text y="6" text-anchor="middle" fill="#fff" font-size="17" font-weight="700">{{ $index + 1 }}</text>
                        </g>
                    @endforeach
                </svg>

                <div class="absolute left-3 top-3 inline-flex items-center gap-2 rounded-full border border-white/70 bg-white/95 px-3 py-1.5 text-[9px] font-semibold text-[#38455a] shadow-sm">
                    <span class="h-2 w-2 rounded-full bg-[#31569e]"></span>
                    {{ $routePlan->count() }} titik pengantaran
                </div>
                @if ($routePlan->isEmpty())
                    <div class="absolute inset-0 flex items-center justify-center p-6">
                        <div class="max-w-xs rounded-2xl border border-white/80 bg-white/90 px-5 py-4 text-center shadow-sm backdrop-blur">
                            <p class="text-xs font-bold text-[#202a3b]">Pilih pesanan untuk melihat rute</p>
                            <p class="mt-1 text-[10px] leading-4 text-slate-600">Centang pesanan yang memiliki koordinat, lalu tekan Optimalkan Rute.</p>
                        </div>
                    </div>
                @endif
                <div class="absolute bottom-3 left-3 rounded-lg border border-white/80 bg-white/90 px-3 py-2 text-[9px] leading-4 text-slate-600 shadow-sm">
                    <span class="font-bold text-[#202a3b]">Peta skematis, bukan rute jalan raya/GPS</span><br>
                    Urutan dan jarak menggunakan garis lurus.
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#e9edf5] bg-white px-4 py-3">
                <p class="text-[10px] text-slate-500">
                    @if ($routePlan->isEmpty())
                        Rute belum dioptimalkan.
                    @else
                        Urutan nearest neighbor siap ditinjau.
                    @endif
                </p>
                <button type="submit" form="route-preview-form" class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-[#12377f] px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-[#09296d] focus:outline-none focus:ring-2 focus:ring-[#31569e] focus:ring-offset-2">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3 15.5 7 11l3 2 6.5-7M12 6h4.5v4.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Optimalkan Rute
                </button>
            </div>
        </section>

        <aside class="rounded-2xl border border-[#e7ebf5] bg-white p-4 shadow-[0_5px_20px_rgba(29,48,99,0.05)]" aria-labelledby="route-summary-heading">
            <h2 id="route-summary-heading" class="flex items-center gap-2 text-sm font-bold text-[#202a3b]">
                <svg class="h-4 w-4 text-[#168ca4]" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 2.75h7l3 3v11.5H5a1.5 1.5 0 0 1-1.5-1.5v-11A2 2 0 0 1 5 2.75Z" stroke="currentColor" stroke-width="1.5"/><path d="M12 3v3h3M7 10h6M7 13h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                Ringkasan Rute
            </h2>

            <div class="mt-4 grid grid-cols-2 gap-2">
                <div class="rounded-xl border border-[#e5ebf8] bg-[#f5f7ff] p-3">
                    <svg class="h-4 w-4 text-[#31569e]" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3 15.5 7 11l3 2 6.5-7M12 6h4.5v4.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <p class="mt-2 text-[9px] font-medium text-slate-500">Total jarak</p>
                    <p class="mt-0.5 text-sm font-extrabold text-[#202a3b]">{{ number_format($totalDistanceMeters / 1000, 1, ',', '.') }}<span class="ml-1 text-[10px] font-semibold text-slate-500">km</span></p>
                </div>
                <div class="rounded-xl border border-[#e5ebf8] bg-[#f5f7ff] p-3">
                    <svg class="h-4 w-4 text-[#168ca4]" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="7" stroke="currentColor" stroke-width="1.5"/><path d="M10 6v4l2.5 1.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    <p class="mt-2 text-[9px] font-medium text-slate-500">Pemberhentian</p>
                    <p class="mt-0.5 text-sm font-extrabold text-[#202a3b]">{{ $routePlan->count() }}<span class="ml-1 text-[10px] font-semibold text-slate-500">titik</span></p>
                </div>
            </div>

            <div class="mt-4 border-t border-[#edf0f7] pt-4">
                <h3 class="text-xs font-bold text-[#202a3b]">Urutan pengantaran</h3>
                @if ($routePlan->isEmpty())
                    <p class="mt-3 rounded-xl bg-slate-50 px-3 py-4 text-[10px] leading-4 text-slate-500">Urutan akan ditampilkan setelah rute dioptimalkan.</p>
                @else
                    <ol class="mt-3 max-h-[310px] space-y-3 overflow-y-auto pr-1">
                        @foreach ($routePlan as $delivery)
                            <li class="relative flex gap-2.5">
                                @if (! $loop->last)
                                    <span class="absolute bottom-[-12px] left-[11px] top-6 border-l border-dashed border-[#bdcbe7]" aria-hidden="true"></span>
                                @endif
                                <span class="relative z-[1] flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#12377f] text-[9px] font-extrabold text-white">{{ $loop->iteration }}</span>
                                <div class="min-w-0 flex-1 pt-0.5">
                                    <p class="truncate text-[10px] font-bold text-[#202a3b]">ORD-{{ $delivery->order->id }}</p>
                                    <p class="truncate text-[9px] text-slate-600">{{ $delivery->order->user->name }}</p>
                                    @if ($loop->first)
                                        <p class="mt-1 text-[8px] font-semibold text-[#31569e]">Titik awal urutan</p>
                                    @else
                                        <p class="mt-1 text-[8px] text-slate-500">{{ number_format($delivery->segment_distance_meters / 1000, 2, ',', '.') }} km garis lurus dari titik sebelumnya</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>

            @if ($selectedIds !== [] && $routePlan->isNotEmpty())
                <form method="POST" action="{{ route('admin.routes.store') }}" class="mt-4 border-t border-[#edf0f7] pt-4">
                    @csrf
                    <input type="hidden" name="courier_id" value="{{ $selectedCourierId }}">
                    <input type="hidden" name="route_date" value="{{ $routeDate }}">
                    @foreach ($routePlan as $delivery)
                        <input type="hidden" name="delivery_ids[]" value="{{ $delivery->id }}">
                    @endforeach
                    <button @disabled(! $selectedCourierId || $couriers->isEmpty()) class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-lg bg-[#f28d54] px-3 py-2 text-xs font-bold text-[#542708] shadow-sm transition hover:bg-[#ec7a39] focus:outline-none focus:ring-2 focus:ring-[#e79567] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m5 3 12 7-12 7V3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                        Tetapkan ke Kurir
                    </button>
                </form>
            @endif

            <p class="mt-4 border-t border-[#edf0f7] pt-3 text-[9px] leading-4 text-slate-500">
                Estimasi jarak berupa garis lurus antar koordinat. Titik awal memakai ID pengantaran terendah; rute jalan/GPS dan waktu tempuh tidak dihitung.
            </p>
        </aside>
    </div>

    @if ($deliveries->isNotEmpty())
        <script>
            document.querySelector('[data-select-all]')?.addEventListener('click', () => {
                const checkboxes = [...document.querySelectorAll('[data-route-delivery]')];
                const selectAll = checkboxes.some((checkbox) => !checkbox.checked);
                checkboxes.forEach((checkbox) => {
                    checkbox.checked = selectAll;
                });
            });
        </script>
    @endif
@endsection
