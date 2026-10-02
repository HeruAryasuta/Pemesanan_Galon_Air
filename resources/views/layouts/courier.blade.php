<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal Kurir') | Padmatirta Wisesa Depo</title>
    <meta name="theme-color" content="#f8f9ff">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f7f8fc] font-sans text-[#172033] antialiased">
    <div class="min-h-screen">
        <header class="sticky top-0 z-10 border-b border-[#e3e8f3] bg-white/95 backdrop-blur">
            <div class="mx-auto flex min-h-16 max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-2 sm:px-6">
                <a href="{{ route('courier.dashboard') }}" class="text-sm font-extrabold text-[#12377f]">Padmatirta Wisesa Depo <span class="ml-1 text-[10px] font-semibold text-slate-500">· Portal Kurir</span></a>
                <div class="flex items-center gap-3">
                    <span class="hidden text-xs font-semibold text-slate-700 sm:inline">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-rose-50 hover:text-rose-700">Keluar</button>
                    </form>
                </div>
            </div>
            <nav class="mx-auto flex max-w-6xl gap-1 overflow-x-auto px-4 pb-2 sm:px-6" aria-label="Navigasi kurir">
                <a href="{{ route('courier.dashboard') }}" @if (request()->routeIs('courier.dashboard')) aria-current="page" @endif class="shrink-0 rounded-lg px-3 py-2 text-xs font-semibold {{ request()->routeIs('courier.dashboard') ? 'bg-[#dff8fc] text-[#12377f]' : 'text-slate-600 hover:bg-slate-50' }}">Tugas aktif</a>
                <a href="{{ route('courier.history') }}" @if (request()->routeIs('courier.history')) aria-current="page" @endif class="shrink-0 rounded-lg px-3 py-2 text-xs font-semibold {{ request()->routeIs('courier.history') ? 'bg-[#dff8fc] text-[#12377f]' : 'text-slate-600 hover:bg-slate-50' }}">Riwayat</a>
            </nav>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:py-8">
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
        </main>
    </div>
</body>
</html>
