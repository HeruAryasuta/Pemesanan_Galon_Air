@extends('layouts.admin')

@section('title', 'Optimasi Rute')

@section('content')
    <div class="mb-5">
        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#4162a5]">Operasional pengantaran</p>
        <h1 class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">Optimasi Rute</h1>
        <p class="mt-1 text-sm text-slate-500">Simulasikan beberapa pesanan sekaligus, bandingkan urutan manual dengan optimasi, dan hitung perjalanan pulang-pergi ke depo.</p>
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
    @if ($missingServiceZoneCount > 0)
        <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-900" role="status">
            {{ $missingServiceZoneCount }} pengantaran aktif belum memiliki wilayah. Perbarui wilayah pada alamat pelanggan agar pengantaran dapat dimasukkan ke rute.
        </div>
    @endif
    @if (! $selectedZoneId)
        <div class="mb-5 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-xs leading-5 text-sky-900" role="status">
            Pilih wilayah terlebih dahulu. Pesanan dan kurir yang ditampilkan akan dibatasi pada wilayah tersebut.
        </div>
    @endif

    @if ($routingError)
        <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs leading-5 text-rose-800" role="alert">
            {{ $routingError }}
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
                    <label for="route-zone" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Pilih wilayah</label>
                    <select id="route-zone" name="service_zone_id" onchange="this.form.requestSubmit()" class="w-full rounded-lg border-[#dce4f5] bg-[#f8faff] text-xs focus:border-[#31569e] focus:ring-[#31569e]">
                        <option value="">Pilih wilayah pengantaran</option>
                        @foreach ($serviceZones as $zone)
                            <option value="{{ $zone->id }}" @selected((string) $selectedZoneId === (string) $zone->id)>{{ $zone->name }} · {{ $zone->area_description }}</option>
                        @endforeach
                    </select>
                    @error('service_zone_id')<p class="mt-1 text-[10px] text-rose-700">{{ $message }}</p>@enderror
                </div>

                <div class="mt-3">
                    <label for="route-courier" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Pilih kurir</label>
                    <select id="route-courier" name="courier_id" class="w-full rounded-lg border-[#dce4f5] bg-[#f8faff] text-xs focus:border-[#31569e] focus:ring-[#31569e]">
                        <option value="">Pilih kurir</option>
                        @foreach ($couriers as $courier)
                            @php($hasRoute = in_array($courier->id, $occupiedCourierIds, true))
                            @php($hasCapacity = $courierCapacities[$courier->id] >= $selectedLoad + ($courierLoads[$courier->id] ?? 0))
                            @php($isBlocked = ! $courier->is_available || $hasRoute || ! $hasCapacity)
                            <option value="{{ $courier->id }}" @selected((string) $selectedCourierId === (string) $courier->id) @disabled($isBlocked)>
                                {{ $courier->name }} · {{ $courier->vehicle_type }} · kapasitas {{ $courierCapacities[$courier->id] }}{{ $hasRoute ? ' · Rute sudah terisi' : (! $courier->is_available ? ' · Sedang bertugas' : (! $hasCapacity ? ' · Kapasitas tidak cukup' : '')) }}
                            </option>
                        @endforeach
                    </select>
                    @if ($availableCourierCount === 0)
                        <p class="mt-1.5 text-[10px] leading-4 text-amber-700">Tidak ada kurir di wilayah ini yang tersedia dan mampu membawa muatan terpilih ({{ $selectedLoad }} {{ config('courier.capacity_unit') }}). Pilih kurir lain, wilayah lain, atau kurangi pesanan.</p>
                    @endif
                    @error('courier_id')<p class="mt-1 text-[10px] text-rose-700">{{ $message }}</p>@enderror
                </div>

                <div class="mt-3">
                    <label for="route-date" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Tanggal pengiriman</label>
                    <input id="route-date" name="route_date" type="date" value="{{ $routeDate }}" onchange="this.form.requestSubmit()" class="w-full rounded-lg border-[#dce4f5] bg-[#f8faff] text-xs focus:border-[#31569e] focus:ring-[#31569e]">
                    <p class="mt-1 text-[9px] leading-4 text-slate-500">Pilihan kurir diperbarui otomatis. Maksimal satu rute per kurir pada tanggal yang sama.</p>
                    @error('route_date')<p class="mt-1 text-[10px] text-rose-700">{{ $message }}</p>@enderror
                </div>

                <fieldset class="mt-4">
                    <legend class="mb-2 block text-[11px] font-semibold text-slate-600">Target optimasi</legend>
                    <div class="rounded-xl border-2 border-[#31569e] bg-[#edf2ff] px-3 py-2.5 text-center text-[10px] font-bold text-[#12377f]" aria-current="true">
                        Optimasi jarak jalan (TSP)
                    </div>
                </fieldset>
                <p class="mt-2 text-[9px] leading-4 text-slate-500">Rute dimulai dari depo, melewati semua pesanan, lalu kembali ke depo. Estimasi durasi tidak memperhitungkan lalu lintas langsung.</p>
            </section>

            <section class="rounded-2xl border border-[#e7ebf5] bg-white p-4 shadow-[0_5px_20px_rgba(29,48,99,0.05)]" aria-labelledby="scheduled-routes-heading">
                <div class="flex items-center justify-between gap-2">
                    <h2 id="scheduled-routes-heading" class="text-xs font-bold text-[#202a3b]">Rute pada tanggal ini</h2>
                    <span class="rounded-full bg-[#edf2ff] px-2 py-1 text-[9px] font-bold text-[#31569e]">{{ $scheduledRoutes->count() }} terisi</span>
                </div>
                @if ($scheduledRoutes->isEmpty())
                    <p class="mt-2 rounded-lg bg-slate-50 px-3 py-3 text-[10px] leading-4 text-slate-500">Belum ada rute ditetapkan. Kurir yang tersedia dapat dipilih.</p>
                @else
                    <ul class="mt-3 space-y-2">
                        @foreach ($scheduledRoutes as $scheduledRoute)
                            <li class="rounded-lg border border-[#edf0f7] bg-[#f8faff] px-3 py-2.5">
                                <p class="text-[10px] font-bold text-[#202a3b]">{{ $scheduledRoute->courier->name }}</p>
                                <p class="mt-1 text-[9px] text-slate-500">{{ $scheduledRoute->stops_count }} titik · {{ $scheduledRoute->status === 'completed' ? 'Selesai' : ($scheduledRoute->status === 'in_progress' ? 'Sedang berjalan' : 'Sudah ditetapkan') }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="rounded-2xl border border-[#e7ebf5] bg-white p-4 shadow-[0_5px_20px_rgba(29,48,99,0.05)]" aria-labelledby="active-deliveries-heading">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <div>
                        <h2 id="active-deliveries-heading" class="text-xs font-bold text-[#202a3b]">Pesanan aktif ({{ $deliveries->count() }})</h2>
                        <p class="mt-1 text-[9px] text-slate-500">Muatan saat ini: {{ $selectedLoad }} {{ config('courier.capacity_unit') }}</p>
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
                                    <span class="mt-1 block truncate text-[9px] leading-4 text-slate-500">{{ $delivery->order->delivery_address }}</span>
                                    <span class="mt-1 block text-[8px] text-slate-400">{{ number_format((float) $delivery->order->delivery_latitude, 5, ',', '.') }}, {{ number_format((float) $delivery->order->delivery_longitude, 5, ',', '.') }}</span>
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
                        <p class="text-[9px] text-slate-500">Rute pulang-pergi dari OSRM · depo ke semua titik lalu kembali</p>
                    </div>
                </div>
                <span class="rounded-full bg-[#f2f5fb] px-2.5 py-1 text-[9px] font-semibold text-slate-600">Depo · mulai & kembali · {{ number_format((float) config('routing.depot.latitude'), 5, ',', '.') }}, {{ number_format((float) config('routing.depot.longitude'), 5, ',', '.') }}</span>
            </div>

            <div
                class="relative h-[430px] overflow-hidden bg-[#eaf0ed] sm:h-[520px] xl:h-[570px]"
                data-route-map
                data-route-map-points="{{ json_encode($routeMapPoints, JSON_THROW_ON_ERROR) }}"
                data-route-map-geometry="{{ json_encode($routeGeometry, JSON_THROW_ON_ERROR) }}"
                role="img"
                aria-label="Peta rute jalan pulang-pergi dari depo melalui setiap titik pengantaran"
            >
                <div class="absolute left-3 top-3 inline-flex items-center gap-2 rounded-full border border-white/70 bg-white/95 px-3 py-1.5 text-[9px] font-semibold text-[#38455a] shadow-sm">
                    <span class="h-2 w-2 rounded-full bg-[#31569e]"></span>
                    {{ $routePlan->count() }} titik pengantaran
                </div>
                @if ($routePlan->isEmpty())
                    <p class="absolute bottom-3 left-3 rounded-lg border border-white/80 bg-white/90 px-3 py-2 text-[9px] leading-4 text-slate-600 shadow-sm">Pilih pesanan untuk menampilkan rute.</p>
                @endif
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#e9edf5] bg-white px-4 py-3">
                <p class="text-[10px] text-slate-500">
                    @if ($routePlan->isEmpty())
                        Rute belum dioptimalkan.
                    @else
                        Rute pulang-pergi hasil optimasi OSRM siap ditinjau.
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
                    <p class="mt-2 text-[9px] font-medium text-slate-500">Jarak pulang-pergi</p>
                    <p class="mt-0.5 text-sm font-extrabold text-[#202a3b]">{{ number_format($totalDistanceMeters / 1000, 1, ',', '.') }}<span class="ml-1 text-[10px] font-semibold text-slate-500">km</span></p>
                </div>
                <div class="rounded-xl border border-[#e5ebf8] bg-[#f5f7ff] p-3">
                    <svg class="h-4 w-4 text-[#168ca4]" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="7" stroke="currentColor" stroke-width="1.5"/><path d="M10 6v4l2.5 1.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    <p class="mt-2 text-[9px] font-medium text-slate-500">Pemberhentian</p>
                    <p class="mt-0.5 text-sm font-extrabold text-[#202a3b]">{{ $routePlan->count() }}<span class="ml-1 text-[10px] font-semibold text-slate-500">titik</span></p>
                </div>
                <div class="col-span-2 rounded-xl border border-[#e5ebf8] bg-[#f5f7ff] p-3">
                    <p class="text-[9px] font-medium text-slate-500">Estimasi waktu jalan</p>
                    <p class="mt-0.5 text-sm font-extrabold text-[#202a3b]">{{ intdiv($totalDurationSeconds, 3600) }}j {{ intdiv($totalDurationSeconds % 3600, 60) }}m</p>
                </div>
            </div>

            @if ($routePlan->isNotEmpty() && $manualDistanceMeters !== null && $comparisonDistanceMeters !== null)
                @php($distanceDifferenceMeters = $manualDistanceMeters - $comparisonDistanceMeters)
                <div class="mt-4 rounded-xl border border-[#dce6f7] bg-[#f7f9ff] p-3" aria-labelledby="route-comparison-heading">
                    <h3 id="route-comparison-heading" class="text-xs font-bold text-[#202a3b]">Perbandingan efisiensi jarak</h3>
                    <dl class="mt-2 space-y-2 text-[10px]">
                        <div class="flex items-center justify-between gap-2">
                            <dt class="text-slate-500">Manual · urutan daftar</dt>
                            <dd class="font-bold text-[#202a3b]">{{ number_format($manualDistanceMeters / 1000, 2, ',', '.') }} km</dd>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <dt class="text-slate-500">Optimasi · pulang-pergi</dt>
                            <dd class="font-bold text-[#202a3b]">{{ number_format($comparisonDistanceMeters / 1000, 2, ',', '.') }} km</dd>
                        </div>
                    </dl>
                    <p class="mt-2 rounded-lg bg-white px-2.5 py-2 text-[10px] font-semibold {{ $distanceDifferenceMeters >= 0 ? 'text-emerald-700' : 'text-amber-700' }}">
                        @if ($distanceDifferenceMeters > 0)
                            {{ number_format($distanceDifferenceMeters / 1000, 2, ',', '.') }} km lebih pendek ({{ number_format($distanceDifferenceMeters / max($manualDistanceMeters, 1) * 100, 1, ',', '.') }}%)
                        @elseif ($distanceDifferenceMeters === 0)
                            Jarak sama dengan urutan manual.
                        @else
                            {{ number_format(abs($distanceDifferenceMeters) / 1000, 2, ',', '.') }} km lebih jauh dari pembanding manual.
                        @endif
                    </p>
                    <p class="mt-2 text-[9px] leading-4 text-slate-500">Kedua pembanding memakai matriks jarak jalan OSRM yang sama. Manual mengikuti urutan pesanan pada daftar; total jarak di atas memakai geometri rute aktual.</p>
                </div>
            @elseif ($routePlan->isNotEmpty() && $comparisonDistanceMeters !== null)
                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3" role="status">
                    <h3 class="text-xs font-bold text-amber-900">Perbandingan efisiensi jarak</h3>
                    <p class="mt-2 text-[10px] leading-4 text-amber-800">Jarak manual tidak dapat dihitung karena OSRM tidak menemukan lintasan untuk urutan pesanan pada daftar. Rute hasil optimasi tetap dapat ditinjau.</p>
                    <p class="mt-2 text-[9px] leading-4 text-amber-800">Optimasi · {{ number_format($comparisonDistanceMeters / 1000, 2, ',', '.') }} km (matriks OSRM)</p>
                </div>
            @endif

            <div class="mt-4 border-t border-[#edf0f7] pt-4">
                <h3 class="text-xs font-bold text-[#202a3b]">Urutan pengantaran</h3>
                @if ($routePlan->isEmpty())
                    <p class="mt-3 rounded-xl bg-slate-50 px-3 py-4 text-[10px] leading-4 text-slate-500">Urutan akan ditampilkan setelah rute dioptimalkan.</p>
                @else
                    <ol class="mt-3 max-h-[310px] space-y-3 overflow-y-auto pr-1">
                        @foreach ($routePlan as $delivery)
                            <li class="relative flex gap-2.5">
                                <span class="absolute bottom-[-12px] left-[11px] top-6 border-l border-dashed border-[#bdcbe7]" aria-hidden="true"></span>
                                <span class="relative z-[1] flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#12377f] text-[9px] font-extrabold text-white">{{ $loop->iteration }}</span>
                                <div class="min-w-0 flex-1 pt-0.5">
                                    <p class="truncate text-[10px] font-bold text-[#202a3b]">ORD-{{ $delivery->order->id }}</p>
                                    <p class="truncate text-[9px] text-slate-600">{{ $delivery->order->user->name }}</p>
                                    <p class="mt-1 text-[8px] text-slate-500">{{ number_format($delivery->segment_distance_meters / 1000, 2, ',', '.') }} km dari {{ $loop->first ? 'depo' : 'titik sebelumnya' }} · {{ intdiv($delivery->segment_duration_seconds, 60) }} menit</p>
                                </div>
                            </li>
                        @endforeach
                        <li class="relative flex gap-2.5">
                            <span class="relative z-[1] flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#168ca4] text-[8px] font-extrabold text-white">D</span>
                            <div class="min-w-0 flex-1 pt-0.5">
                                <p class="truncate text-[10px] font-bold text-[#202a3b]">Kembali ke depo</p>
                                <p class="mt-1 text-[8px] text-slate-500">{{ number_format($returnDistanceMeters / 1000, 2, ',', '.') }} km · {{ intdiv($returnDurationSeconds, 60) }} menit dari titik terakhir</p>
                            </div>
                        </li>
                    </ol>
                @endif
            </div>

            @if ($selectedIds !== [] && $routePlan->isNotEmpty())
                <form method="POST" action="{{ route('admin.routes.store') }}" class="mt-4 border-t border-[#edf0f7] pt-4">
                    @csrf
                    <input type="hidden" name="courier_id" value="{{ $selectedCourierId }}">
                    <input type="hidden" name="route_date" value="{{ $routeDate }}">
                    <input type="hidden" name="service_zone_id" value="{{ $selectedZoneId }}">
                    @foreach ($routePlan as $delivery)
                        <input type="hidden" name="delivery_ids[]" value="{{ $delivery->id }}">
                    @endforeach
                    <button @disabled(! $selectedCourierId || ! $selectedCourierEligible) class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-lg bg-[#f28d54] px-3 py-2 text-xs font-bold text-[#542708] shadow-sm transition hover:bg-[#ec7a39] focus:outline-none focus:ring-2 focus:ring-[#e79567] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m5 3 12 7-12 7V3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                        Tetapkan ke Kurir
                    </button>
                </form>
            @endif

            <p class="mt-4 border-t border-[#edf0f7] pt-3 text-[9px] leading-4 text-slate-500">
                Semua jumlah pesanan memakai Nearest Neighbor untuk menyusun rute awal, lalu 2-opt memperbaiki urutan hingga tidak ada perbaikan lokal atau mencapai 20 putaran. Heuristik ini skalabel tetapi tidak menjamin solusi global paling pendek.
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
