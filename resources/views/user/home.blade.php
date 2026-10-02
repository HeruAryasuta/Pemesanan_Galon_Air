@extends('layouts.user')

@section('title', 'Padmatirta Wisesa Depo | Pesan kebutuhan harian')

@section('content')
    @php($cartCount = array_sum(session('cart', [])))
    <div class="min-h-screen bg-[#eff3ff]">
        <header class="relative z-10 bg-[#f8f9ff]">
            <nav class="mx-auto flex min-h-[53px] max-w-7xl flex-wrap items-center justify-between gap-x-4 px-3 py-1 sm:px-5 lg:flex-nowrap lg:px-8 lg:py-0" aria-label="Navigasi utama">
                <a href="{{ route('user.home') }}" class="order-1 shrink-0 text-[13px] font-extrabold tracking-tight text-[#09296d] sm:text-sm">
                    Padmatirta Wisesa Depo
                </a>

                <div class="order-3 flex w-full justify-start overflow-x-auto sm:justify-center lg:order-2 lg:w-auto">
                    <x-user-navigation />
                </div>

                <div class="order-2 ml-auto flex shrink-0 items-center gap-1.5 lg:order-3">
                    <a href="{{ route('user.cart.index') }}" class="inline-flex items-center gap-1 rounded-md bg-[#edf2ff] px-2 py-1.5 text-[9px] font-bold text-[#09296d] transition hover:bg-[#dfe8ff] sm:px-2.5" aria-label="Keranjang, {{ $cartCount }} item">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h8.6a2 2 0 0 0 1.95-1.55L21 8H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="10" cy="19" r="1.4" fill="currentColor"/><circle cx="18" cy="19" r="1.4" fill="currentColor"/>
                        </svg>
                        Keranjang
                        @if ($cartCount > 0)
                            <span class="rounded-full bg-white px-1.5 py-0.5 text-[10px]">{{ $cartCount }}</span>
                        @endif
                    </a>
                    @guest
                        <a href="{{ route('login') }}" class="rounded-md bg-[#09296d] px-2.5 py-1.5 text-[9px] font-bold text-white transition hover:bg-[#123a8e]">Login</a>
                    @else
                        <a href="{{ route('profile.edit') }}" class="rounded-md bg-[#09296d] px-2.5 py-1.5 text-[9px] font-bold text-white transition hover:bg-[#123a8e]">Akun</a>
                    @endguest
                </div>
            </nav>
        </header>

        @if (session('success') || session('error') || $errors->any())
            <div class="mx-auto max-w-7xl px-5 pt-4 sm:px-8 lg:px-10" role="status" aria-live="polite">
                @if (session('success'))
                    <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</p>
                @elseif (session('error'))
                    <p class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">{{ session('error') }}</p>
                @else
                    <p class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">{{ $errors->first() }}</p>
                @endif
            </div>
        @endif

        <main>
            <section id="beranda" class="relative isolate flex min-h-[325px] items-center justify-center overflow-hidden bg-[#f7f8fc] px-5 py-12 text-center sm:min-h-[345px]">
                <div class="absolute inset-0 -z-10" aria-hidden="true">
                    <svg class="absolute inset-0 h-full w-full opacity-[0.18]" viewBox="0 0 1440 520" preserveAspectRatio="xMidYMid slice" fill="none">
                        <path d="M0 360h1440v160H0z" fill="#8290A9" fill-opacity=".24"/>
                        <path d="M0 365h1440M0 420h1440" stroke="#65738D" stroke-opacity=".35" stroke-width="5"/>
                        <path d="M78 85h360v228H78zM500 65h300v248H500zM912 82h410v231H912z" fill="#AAB4C5" fill-opacity=".22" stroke="#68768F" stroke-width="8"/>
                        <path d="M104 112h138v163H104zM263 112h148v163H263zM530 95h115v180H530zM663 95h112v180H663zM944 110h150v165H944zM1115 110h171v165h-171z" fill="#E8EDF5" fill-opacity=".55" stroke="#7C89A0" stroke-width="5"/>
                        <path d="M0 330h1440v45H0z" fill="#6F7D96" fill-opacity=".3"/>
                        <path d="M238 308c0-52 20-83 57-83s57 31 57 83v57H238v-57Z" fill="#5C6B84"/>
                        <circle cx="294" cy="205" r="23" fill="#65738D"/>
                        <path d="m270 260-43 40 19 17 50-33 49 39 18-19-53-54-40 10Z" fill="#56647C"/>
                        <path d="M314 286 350 333l27-5-23-58M256 290l-30 41 9 16 43-40" fill="#56647C"/>
                        <path d="M856 326h374v39H856z" fill="#697790"/>
                        <path d="M874 290h90v36h-90zM979 276h90v50h-90zM1086 294h122v32h-122z" fill="#C4CDDB"/>
                    </svg>
                    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_17%_43%,rgba(15,41,92,0.08),transparent_35%),radial-gradient(ellipse_at_79%_29%,rgba(73,111,181,0.09),transparent_38%),linear-gradient(110deg,rgba(243,244,248,0.86)_0%,rgba(255,255,255,0.84)_48%,rgba(241,244,250,0.87)_100%)]"></div>
                    <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-white/75 to-transparent"></div>
                    <div class="absolute left-[8%] top-12 h-48 w-32 rounded-t-3xl border border-white/60 bg-white/20 blur-[2px] sm:left-[14%] sm:w-44"></div>
                    <div class="absolute right-[7%] top-8 h-52 w-40 rounded-t-3xl border border-white/70 bg-white/25 blur-[2px] sm:right-[13%] sm:w-56"></div>
                </div>
                <div class="mx-auto max-w-3xl">
                    <span class="inline-flex rounded-full bg-[#e1ebff] px-3 py-1.5 text-[10px] font-semibold text-[#214b9a] sm:text-xs">
                        Layanan Pengantaran Cepat &amp; Terpercaya
                    </span>
                    <h1 class="mx-auto mt-5 max-w-[480px] text-[26px] font-bold leading-tight tracking-tight text-[#172033] sm:text-[28px]">
                        Pesan Air Galon, LPG &amp; Minuman dari Rumah
                    </h1>
                    <p class="mx-auto mt-3 max-w-xl text-xs leading-5 text-slate-600 sm:text-sm sm:leading-6">
                        Pesan kebutuhan harian dengan mudah. Melayani pengiriman di Gunung Anyar<br class="hidden sm:block"> dan sekitarnya.
                    </p>
                    <a href="{{ route('user.products.index') }}" class="mt-7 inline-flex items-center gap-2 rounded-lg bg-[#39d2ec] px-5 py-3 text-xs font-bold text-[#073b66] shadow-sm transition hover:-translate-y-0.5 hover:bg-[#22c4e0]">
                        Mulai Belanja
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                </div>
            </section>

            <section id="kategori" class="bg-[#eff3ff]">
                <div class="mx-auto max-w-7xl px-5 py-5 sm:px-8 sm:py-5 lg:px-8">
                    <div>
                        <h2 id="rekomendasi" class="text-xl font-bold tracking-tight text-[#1a2437] sm:text-2xl">Kategori Produk</h2>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-2.5 sm:grid-cols-[1.05fr_0.5fr_0.5fr]">
                        <a href="{{ route('user.products.index', ['search' => 'Air Galon']) }}" class="relative flex min-h-28 flex-col justify-between overflow-hidden rounded-2xl bg-[#fbfbff] p-4 shadow-[0_5px_16px_rgba(29,48,99,0.06)] transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#31569e] sm:row-span-2 sm:min-h-[126px]">
                            <div class="absolute -bottom-12 -right-8 h-28 w-28 rounded-full bg-[#e9edff]" aria-hidden="true"></div>
                            <div class="relative">
                                <svg class="h-6 w-6 text-[#09296d]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2.75C9.05 6.56 5.75 10.1 5.75 14.1a6.25 6.25 0 0 0 12.5 0C18.25 10.1 14.95 6.56 12 2.75Z" fill="currentColor"/><path d="M9 15.2c.28 1.4 1.2 2.2 2.5 2.55" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
                                <h3 class="mt-2 text-sm font-semibold text-[#182235]">Air Galon</h3>
                                <p class="mt-1 text-[11px] leading-4 text-slate-600">Pilihan air mineral galon terpercaya untuk kebutuhan harian.</p>
                            </div>
                        </a>

                        <a href="{{ route('user.products.index', ['search' => 'LPG']) }}" class="flex min-h-16 flex-col justify-center rounded-2xl bg-[#fbfbff] p-3 shadow-[0_5px_16px_rgba(29,48,99,0.06)] transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#31569e] sm:p-2.5">
                            <svg class="h-5 w-5 text-[#ff743c] sm:h-4 sm:w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M13.2 2.8c.7 4.1-3.7 5.2-2.4 8.8 1.1-.4 2-1.4 2.4-2.5 2.9 2 4.2 4.1 3.6 7a5.1 5.1 0 0 1-10.1-1c0-3.7 2.8-5.8 3.8-8.1.7-1.5 1.3-2.8 2.7-4.2Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <h3 class="mt-2 text-xs font-semibold text-[#182235] sm:mt-1.5 sm:text-[10px]">LPG</h3>
                            <p class="mt-1 text-[10px] leading-4 text-slate-600 sm:mt-0.5 sm:text-[9px] sm:leading-3">Gas elpiji berbagai ukuran.</p>
                        </a>

                        <a href="{{ route('user.products.index', ['search' => 'Air Mineral']) }}" class="flex min-h-16 flex-col justify-center rounded-2xl bg-[#fbfbff] p-3 shadow-[0_5px_16px_rgba(29,48,99,0.06)] transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#31569e] sm:p-2.5">
                            <svg class="h-5 w-5 text-[#008ca5] sm:h-4 sm:w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 4h6v3l1 1v10.5a1.5 1.5 0 0 1-1.5 1.5h-5A1.5 1.5 0 0 1 8 18.5V8l1-1V4Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M9 11h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            <h3 class="mt-2 text-xs font-semibold text-[#182235] sm:mt-1.5 sm:text-[10px]">Air Mineral</h3>
                            <p class="mt-1 text-[10px] leading-4 text-slate-600 sm:mt-0.5 sm:text-[9px] sm:leading-3">Botol &amp; Gelas.</p>
                        </a>

                        <a href="{{ route('user.products.index', ['search' => 'Minuman Ringan']) }}" class="flex min-h-[52px] flex-col justify-center rounded-2xl bg-[#fbfbff] p-3 shadow-[0_5px_16px_rgba(29,48,99,0.06)] transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#31569e] sm:col-span-2 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                            <div>
                                <h3 class="text-xs font-semibold text-[#182235]">Minuman Ringan</h3>
                                <p class="mt-1 text-[10px] leading-4 text-slate-600">Teh, Kopi, dan minuman manis lainnya.</p>
                            </div>
                            <svg class="mt-2 h-5 w-5 shrink-0 text-slate-700 sm:mt-0" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h16M6 4v10a6 6 0 0 0 12 0V4M18 8h2a2 2 0 0 1 0 4h-2M5 20h14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                    </div>
                </div>
            </section>

            <section id="cara-pesan" class="scroll-mt-20 bg-white py-12 sm:py-16">
                <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <span class="inline-flex rounded-full bg-[#e8efff] px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.12em] text-[#31569e]">Mudah dan praktis</span>
                        <h2 class="mt-3 text-2xl font-bold tracking-tight text-[#172033] sm:text-3xl">Cara Pesan</h2>
                        <p class="mt-2 text-xs leading-5 text-slate-600 sm:text-sm sm:leading-6">Ikuti empat langkah mudah untuk memesan kebutuhan harian dan menerima pengantaran di alamat Anda.</p>
                    </div>

                    <ol class="mt-8 grid gap-4 sm:grid-cols-2 lg:mt-10 lg:grid-cols-4">
                        <li class="overflow-hidden rounded-2xl border border-[#e8ecf6] bg-[#fbfcff] shadow-[0_5px_18px_rgba(29,48,99,0.05)]">
                            <div class="flex h-40 items-center justify-center bg-[#edf3ff] p-4">
                                <img src="{{ asset('images/how-to/browse-products.svg') }}" alt="" class="h-full w-full object-contain" loading="lazy">
                            </div>
                            <div class="p-4">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-[#12377f] text-xs font-extrabold text-white">1</span>
                                <h3 class="mt-3 text-sm font-bold text-[#202a3b]">Pilih produk</h3>
                                <p class="mt-1.5 min-h-10 text-[11px] leading-5 text-slate-600">Jelajahi katalog, lihat detail dan pilih kebutuhan yang ingin dipesan.</p>
                                <a href="{{ route('user.products.index') }}" class="mt-3 inline-flex items-center gap-1 text-[10px] font-bold text-[#31569e] hover:text-[#09296d]">
                                    Lihat katalog
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </a>
                            </div>
                        </li>

                        <li class="overflow-hidden rounded-2xl border border-[#e8ecf6] bg-[#fbfcff] shadow-[0_5px_18px_rgba(29,48,99,0.05)]">
                            <div class="flex h-40 items-center justify-center bg-[#eff9fb] p-4">
                                <img src="{{ asset('images/how-to/add-to-cart.svg') }}" alt="" class="h-full w-full object-contain" loading="lazy">
                            </div>
                            <div class="p-4">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-[#12377f] text-xs font-extrabold text-white">2</span>
                                <h3 class="mt-3 text-sm font-bold text-[#202a3b]">Masukkan ke keranjang</h3>
                                <p class="mt-1.5 min-h-10 text-[11px] leading-5 text-slate-600">Atur jumlah produk dan tambahkan ke keranjang selama stok tersedia.</p>
                                <a href="{{ route('user.cart.index') }}" class="mt-3 inline-flex items-center gap-1 text-[10px] font-bold text-[#31569e] hover:text-[#09296d]">
                                    Buka keranjang
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </a>
                            </div>
                        </li>

                        <li class="overflow-hidden rounded-2xl border border-[#e8ecf6] bg-[#fbfcff] shadow-[0_5px_18px_rgba(29,48,99,0.05)]">
                            <div class="flex h-40 items-center justify-center bg-[#f4f0ff] p-4">
                                <img src="{{ asset('images/how-to/checkout-order.svg') }}" alt="" class="h-full w-full object-contain" loading="lazy">
                            </div>
                            <div class="p-4">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-[#12377f] text-xs font-extrabold text-white">3</span>
                                <h3 class="mt-3 text-sm font-bold text-[#202a3b]">Checkout pesanan</h3>
                                <p class="mt-1.5 min-h-10 text-[11px] leading-5 text-slate-600">Masuk ke akun, pilih alamat pengantaran dan metode pembayaran COD atau transfer.</p>
                                <a href="{{ route('user.checkout.index') }}" class="mt-3 inline-flex items-center gap-1 text-[10px] font-bold text-[#31569e] hover:text-[#09296d]">
                                    Lanjut checkout
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </a>
                            </div>
                        </li>

                        <li class="overflow-hidden rounded-2xl border border-[#e8ecf6] bg-[#fbfcff] shadow-[0_5px_18px_rgba(29,48,99,0.05)]">
                            <div class="flex h-40 items-center justify-center bg-[#fff5ed] p-4">
                                <img src="{{ asset('images/how-to/track-delivery.svg') }}" alt="" class="h-full w-full object-contain" loading="lazy">
                            </div>
                            <div class="p-4">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-[#12377f] text-xs font-extrabold text-white">4</span>
                                <h3 class="mt-3 text-sm font-bold text-[#202a3b]">Pantau dan terima</h3>
                                <p class="mt-1.5 min-h-10 text-[11px] leading-5 text-slate-600">Pantau perkembangan pesanan melalui akun Anda dan terima pesanan dari kurir.</p>
                                <a href="{{ route('user.orders.index') }}" class="mt-3 inline-flex items-center gap-1 text-[10px] font-bold text-[#31569e] hover:text-[#09296d]">
                                    Lihat pesanan
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </a>
                            </div>
                        </li>
                    </ol>

                    <p class="mt-5 text-center text-[10px] leading-5 text-slate-500">Checkout dan pelacakan pesanan memerlukan akun pelanggan. Pembayaran transfer perlu dikonfirmasi admin sebelum pesanan diproses.</p>
                </div>
            </section>
        </main>

        <footer id="tentang-kami" class="bg-[#e6edff]">
            <div class="mx-auto max-w-7xl px-5 pb-4 pt-6 sm:px-8 lg:px-8">
                <div class="grid gap-5 sm:grid-cols-[1.5fr_1fr_1fr_1fr] lg:gap-10">
                    <div>
                        <h2 class="text-sm font-bold text-[#1a2437]">Padmatirta Wisesa Depo</h2>
                        <p class="mt-2 max-w-xs text-[10px] leading-4 text-slate-600">Melayani kebutuhan harian dengan cepat dan tepat di area Gunung Anyar dan sekitarnya.</p>
                    </div>
                    <div>
                        <h3 class="text-[10px] font-bold text-[#1a2437]">Layanan</h3>
                        <ul class="mt-2 space-y-1.5 text-[10px] text-slate-600">
                            <li><a href="#kategori" class="hover:text-[#09296d]">Air Galon</a></li>
                            <li><a href="#kategori" class="hover:text-[#09296d]">LPG</a></li>
                            <li><a href="#kategori" class="hover:text-[#09296d]">Minuman</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-[10px] font-bold text-[#1a2437]">Tautan</h3>
                        <ul class="mt-2 space-y-1.5 text-[10px] text-slate-600">
                            <li><a href="#tentang-kami" class="hover:text-[#09296d]">Contact Info</a></li>
                            <li><a href="#tentang-kami" class="hover:text-[#09296d]">Social Media</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-[10px] font-bold text-[#1a2437]">Area Pengiriman</h3>
                        <ul class="mt-2 space-y-1.5 text-[10px] text-slate-600">
                            <li>Gunung Anyar</li>
                            <li>Medokan Ayu</li>
                            <li>Rungkut</li>
                        </ul>
                    </div>
                </div>
                <div class="mt-5 border-t border-[#d4def5] pt-3 text-center text-[10px] text-slate-600">
                    &copy; {{ now()->year }} Padmatirta Wisesa Depo. All rights reserved.
                </div>
            </div>
        </footer>
    </div>
@endsection
