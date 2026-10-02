<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <meta name="theme-color" content="#f8f9ff">
        <title>@yield('title', 'Akun | Padmatirta Wisesa Depo')</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f8f9ff] font-sans text-slate-800 antialiased">
        <div class="flex min-h-screen flex-col">
            <header class="bg-[#f8f9ff]">
                <nav class="mx-auto flex min-h-14 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8" aria-label="Navigasi autentikasi">
                    <a href="{{ route('user.home') }}" class="text-sm font-extrabold tracking-tight text-[#09296d] sm:text-base">
                        Padmatirta Wisesa Depo
                    </a>
                    <a href="{{ route('user.products.index') }}" class="text-xs font-semibold text-[#454652] transition hover:text-[#09296d]">
                        Lihat katalog
                    </a>
                </nav>
            </header>

            <main class="relative flex flex-1 items-center justify-center overflow-hidden px-4 py-10 sm:px-6">
                <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                    <div class="absolute -left-24 top-16 h-64 w-64 rounded-full bg-[#dfe8ff]/70 blur-3xl"></div>
                    <div class="absolute -right-20 bottom-8 h-72 w-72 rounded-full bg-[#e6edff] blur-3xl"></div>
                </div>
                <div class="relative grid w-full max-w-4xl overflow-hidden rounded-3xl border border-[#e8ecf6] bg-white shadow-[0_18px_55px_rgba(29,48,99,0.10)] md:grid-cols-[0.9fr_1.1fr]">
                    <aside class="relative hidden flex-col justify-between overflow-hidden bg-[#12377f] p-8 text-white md:flex lg:p-10">
                        <div class="absolute -right-16 -top-16 h-56 w-56 rounded-full border-[32px] border-white/5"></div>
                        <div class="absolute -bottom-24 -left-20 h-72 w-72 rounded-full border-[40px] border-white/5"></div>
                        <a href="{{ route('user.home') }}" class="relative text-base font-extrabold tracking-tight">
                            Padmatirta Wisesa Depo
                        </a>
                        <div class="relative py-10">
                            <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 3.5 20 7v5.1c0 4.5-3.1 7.3-8 8.9-4.9-1.6-8-4.4-8-8.9V7l8-3.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                                    <path d="m8.5 12.2 2.2 2.2 4.8-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <h1 class="mt-5 text-2xl font-extrabold leading-tight lg:text-3xl">Kebutuhan harian, lebih mudah.</h1>
                            <p class="mt-3 max-w-xs text-sm leading-6 text-blue-100">Pesan air galon, LPG, dan minuman untuk diantar ke rumah Anda.</p>
                        </div>
                        <p class="relative text-[10px] text-blue-100">&copy; {{ now()->year }} Padmatirta Wisesa Depo</p>
                    </aside>

                    <section class="p-6 sm:p-9 lg:p-11">
                        <a href="{{ route('user.home') }}" class="text-sm font-extrabold tracking-tight text-[#09296d] md:hidden">Padmatirta Wisesa Depo</a>
                        {{ $slot }}
                    </section>
                </div>
            </main>
        </div>
    </body>
</html>
