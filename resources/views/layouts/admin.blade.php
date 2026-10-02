<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') | Padmatirta Wisesa Depo</title>
    <meta name="theme-color" content="#f8f9ff">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f7f8fc] font-sans text-[#172033] antialiased">
    <aside class="fixed inset-y-0 left-0 z-20 hidden w-56 flex-col border-r border-[#e2e8f5] bg-[#eef3ff] lg:flex">
        <a href="{{ route('admin.dashboard') }}" class="border-b border-[#dce5f7] px-5 py-5">
            <span class="block text-base font-extrabold tracking-tight text-[#12377f]">Admin Panel</span>
            <span class="mt-0.5 block text-[10px] font-medium text-slate-500">Padmatirta Wisesa Depo</span>
        </a>
        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5" aria-label="Menu admin">
            @php($adminLinks = [
                ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['route' => 'admin.orders.*', 'label' => 'Pesanan', 'url' => route('admin.orders.index')],
                ['route' => 'admin.payments.*', 'label' => 'Pembayaran', 'url' => route('admin.payments.index')],
                ['route' => 'admin.products.*', 'label' => 'Produk', 'url' => route('admin.products.index')],
                ['route' => 'admin.customers.*', 'label' => 'Pelanggan', 'url' => route('admin.customers.index')],
                ['route' => 'admin.couriers.*', 'label' => 'Kurir', 'url' => route('admin.couriers.index')],
                ['route' => 'admin.routes.*', 'label' => 'Optimasi Rute', 'url' => route('admin.routes.optimize')],
                ['route' => 'admin.reports.*', 'label' => 'Laporan', 'url' => route('admin.reports.index')],
            ])
            @foreach ($adminLinks as $link)
                <a href="{{ $link['url'] }}" @if (request()->routeIs($link['route'])) aria-current="page" @endif class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-xs font-semibold transition {{ request()->routeIs($link['route']) ? 'bg-[#4dd6ec] text-[#12377f]' : 'text-slate-600 hover:bg-white hover:text-[#12377f]' }}">
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center text-[#31569e]" aria-hidden="true">
                        @switch($link['label'])
                            @case('Dashboard')
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><rect x="2.5" y="2.5" width="6" height="6" rx="1"/><rect x="11.5" y="2.5" width="6" height="6" rx="1"/><rect x="2.5" y="11.5" width="6" height="6" rx="1"/><rect x="11.5" y="11.5" width="6" height="6" rx="1"/></svg>
                                @break
                            @case('Pesanan')
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none"><path d="M2.5 3.5h2l1.6 8.1a1.7 1.7 0 0 0 1.7 1.4h7.1a1.7 1.7 0 0 0 1.7-1.4L18 6H5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="8" cy="16.5" r="1.2" fill="currentColor"/><circle cx="15" cy="16.5" r="1.2" fill="currentColor"/></svg>
                                @break
                            @case('Pembayaran')
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none"><rect x="2.5" y="4" width="15" height="12" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M2.5 8h15M6 12h3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                                @break
                            @case('Produk')
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none"><path d="M3 6.5 10 3l7 3.5v8L10 18l-7-3.5v-8Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m3.5 6.8 6.5 3.3 6.5-3.3M10 10v7.5" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
                                @break
                            @case('Pelanggan')
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none"><circle cx="7" cy="6.5" r="2.7" stroke="currentColor" stroke-width="1.6"/><path d="M2.5 16a4.5 4.5 0 0 1 9 0M13 4.2a2.7 2.7 0 0 1 0 4.8m1.2 2a4 4 0 0 1 3.3 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                                @break
                            @case('Kurir')
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none"><path d="M2 5.5h9.5v8H2zM11.5 8h3.2l3.3 3v2.5h-6.5z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><circle cx="5.2" cy="14.5" r="1.7" fill="white" stroke="currentColor" stroke-width="1.5"/><circle cx="14.8" cy="14.5" r="1.7" fill="white" stroke="currentColor" stroke-width="1.5"/></svg>
                                @break
                            @case('Optimasi Rute')
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none"><path d="M5 3v5a2 2 0 0 0 2 2h6a2 2 0 0 1 2 2v5M5 3l-2 2m2-2 2 2m8 12-2-2m2 2 2-2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="5" cy="3" r="1.5" fill="currentColor"/><circle cx="15" cy="17" r="1.5" fill="currentColor"/></svg>
                                @break
                            @case('Laporan')
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none"><path d="M3 16.5V10m4.7 6.5V5.5m4.6 11v-4m4.7 4V3.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                                @break
                        @endswitch
                    </span>
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>
        <div class="space-y-2 border-t border-[#dce5f7] px-4 py-3">
            <a href="{{ route('user.home') }}" class="flex items-center gap-2 rounded-lg px-2 py-2 text-[11px] font-semibold text-[#31569e] transition hover:bg-white hover:text-[#12377f]">
                <span aria-hidden="true">←</span>
                <span>Kembali ke website</span>
            </a>
            <p class="px-2 text-[10px] text-slate-500">Panel operasional</p>
        </div>
    </aside>

    <div class="min-h-screen lg:pl-56">
        <header class="sticky top-0 z-10 border-b border-[#e3e8f3] bg-white/95 backdrop-blur">
            <div class="flex min-h-14 flex-wrap items-center justify-between gap-3 px-4 py-2 sm:px-6 lg:px-8">
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.dashboard') }}" class="text-sm font-extrabold text-[#12377f] lg:hidden">Admin Panel</a>
                    <a href="{{ route('user.home') }}" class="hidden rounded-lg border border-[#dce4f5] px-3 py-2 text-[11px] font-semibold text-[#31569e] transition hover:bg-[#edf2ff] sm:inline-flex lg:hidden">← Website</a>
                    <form method="GET" action="{{ route('admin.orders.index') }}" class="hidden sm:block">
                        <label class="sr-only" for="admin-global-search">Cari pesanan atau pelanggan</label>
                        <input id="admin-global-search" name="search" type="search" value="{{ request()->routeIs('admin.orders.*') ? request('search') : '' }}" placeholder="Cari pesanan atau pelanggan..." class="w-64 rounded-full border-[#e0e6f3] bg-[#f5f7fc] px-4 py-2 text-xs focus:border-[#31569e] focus:ring-[#31569e]">
                    </form>
                    <span class="hidden text-sm font-bold text-[#202a3b] sm:inline">@yield('title', 'Dashboard')</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#dce8ff] text-xs font-extrabold uppercase text-[#12377f]" aria-hidden="true">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                    <span class="hidden text-xs font-semibold text-slate-700 sm:inline">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg px-2 py-1.5 text-xs font-semibold text-slate-600 hover:bg-rose-50 hover:text-rose-700">Keluar</button>
                    </form>
                </div>
            </div>
            <nav class="flex gap-1 overflow-x-auto border-t border-[#edf0f7] px-3 py-2 lg:hidden" aria-label="Menu admin">
                @foreach ($adminLinks as $link)
                    <a href="{{ $link['url'] }}" class="shrink-0 rounded-lg px-3 py-2 text-[11px] font-semibold {{ request()->routeIs($link['route']) ? 'bg-[#dff8fc] text-[#12377f]' : 'text-slate-600 hover:bg-slate-50' }}">{{ $link['label'] }}</a>
                @endforeach
                <a href="{{ route('user.home') }}" class="shrink-0 rounded-lg px-3 py-2 text-[11px] font-semibold text-[#31569e] hover:bg-slate-50">← Website</a>
            </nav>
        </header>
        <main class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-[1500px]">
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
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>