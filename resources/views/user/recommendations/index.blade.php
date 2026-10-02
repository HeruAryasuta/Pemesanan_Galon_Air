@extends('layouts.user')

@section('title', 'Rekomendasi Produk | Padmatirta Wisesa Depo')

@section('content')
    @php($cartCount = array_sum(session('cart', [])))
    <div class="min-h-screen bg-[#f7f8fc]">
        <header class="bg-white">
            <nav class="mx-auto flex min-h-[58px] max-w-7xl flex-wrap items-center justify-between gap-x-5 px-4 sm:px-6 lg:flex-nowrap lg:px-8" aria-label="Navigasi utama">
                <a href="{{ route('user.home') }}" class="order-1 shrink-0 text-sm font-extrabold tracking-tight text-[#09296d]">
                    Padmatirta Wisesa Depo
                </a>

                <div class="order-3 flex w-full justify-start overflow-x-auto sm:justify-center lg:order-2 lg:w-auto">
                    <x-user-navigation />
                </div>

                <div class="order-2 ml-auto flex shrink-0 items-center gap-2 lg:order-3">
                    @guest
                        <a href="{{ route('login') }}" class="rounded-md px-3 py-2 text-xs font-semibold text-[#263044] transition hover:bg-[#f0f3fb]">Login</a>
                    @else
                        <a href="{{ route('profile.edit') }}" class="rounded-md px-3 py-2 text-xs font-semibold text-[#263044] transition hover:bg-[#f0f3fb]">Akun</a>
                    @endguest
                    <a href="{{ route('user.cart.index') }}" class="inline-flex items-center gap-2 rounded-lg bg-[#09296d] px-3 py-2 text-xs font-bold text-white transition hover:bg-[#123a8e]" aria-label="Keranjang, {{ $cartCount }} item">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h8.6a2 2 0 0 0 1.95-1.55L21 8H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="10" cy="19" r="1.4" fill="currentColor"/><circle cx="18" cy="19" r="1.4" fill="currentColor"/>
                        </svg>
                        Keranjang
                        @if ($cartCount > 0)
                            <span class="rounded-full bg-white/20 px-1.5 py-0.5 text-[10px]">{{ $cartCount }}</span>
                        @endif
                    </a>
                </div>
            </nav>
        </header>

        <main class="mx-auto min-h-[calc(100vh-230px)] max-w-7xl px-4 pb-12 pt-8 sm:px-6 sm:pt-10 lg:px-8">
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

            <div class="mb-7">
                <h1 class="text-2xl font-bold tracking-tight text-[#172033] sm:text-3xl">Rekomendasi Untuk Anda</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Pilihan produk yang membantu memenuhi kebutuhan harian Anda dengan lebih mudah.
                </p>
            </div>

            <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <section id="personalized-recommendations" aria-labelledby="personalized-heading">
                    <div class="mb-4">
                        <div class="flex items-center gap-2">
                            <svg class="h-5 w-5 text-[#12377f]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M5 4.75h14a1.5 1.5 0 0 1 1.5 1.5v11.5a1.5 1.5 0 0 1-1.5 1.5H5a1.5 1.5 0 0 1-1.5-1.5V6.25A1.5 1.5 0 0 1 5 4.75Z" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M7.5 9h9m-9 3.5h9m-9 3.5h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            </svg>
                            <h2 id="personalized-heading" class="text-base font-semibold text-[#1a2437]">
                                {{ $hasPurchaseHistory ? 'Berdasarkan Pembelian Sebelumnya' : 'Pilihan Populer Untuk Anda' }}
                            </h2>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $hasPurchaseHistory ? 'Diurutkan berdasarkan kemiripan nama, kategori, dan deskripsi produk dengan riwayat pesanan Anda.' : 'Produk pilihan pelanggan yang mungkin Anda butuhkan.' }}
                        </p>
                    </div>

                    @if ($recommendations->isNotEmpty())
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach ($recommendations as $product)
                                <article class="overflow-hidden rounded-2xl border border-[#e8ebf3] bg-white p-2.5 shadow-[0_4px_14px_rgba(29,48,99,0.05)] transition hover:-translate-y-0.5 hover:shadow-md">
                                    <a href="{{ route('user.products.show', $product) }}" class="group relative block overflow-hidden rounded-xl focus:outline-none focus:ring-2 focus:ring-[#31569e]" aria-label="Lihat detail {{ $product->name }}">
                                        <x-product-image :product="$product" class="aspect-[1.8/1] transition group-hover:brightness-[0.98]" />
                                        <span class="absolute left-2 top-2 inline-flex items-center gap-1 rounded-full bg-white/95 px-2.5 py-1 text-[10px] font-bold text-[#12377f] shadow-sm">
                                            <svg class="h-3 w-3" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="m8 1 1.8 4 4.2.5-3.1 2.8.8 4.2L8 10.4l-3.7 2.1.8-4.2L2 5.5 6.2 5 8 1Z"/></svg>
                                            @if ($purchasedProductIds->contains($product->id))
                                                Pesan Ulang
                                            @elseif ($hasPurchaseHistory)
                                                Untuk Anda
                                            @else
                                                Pilihan Depo
                                            @endif
                                        </span>
                                        @if ($hasPurchaseHistory && isset($product->similarity_score))
                                            <span class="absolute bottom-2 right-2 rounded-full bg-[#09296d]/90 px-2.5 py-1 text-[10px] font-bold text-white shadow-sm">
                                                Kemiripan {{ number_format($product->similarity_score * 100, 0) }}%
                                            </span>
                                        @endif
                                    </a>
                                    <div class="px-1 pb-1 pt-3">
                                        <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-500">{{ $product->category->name }}</p>
                                        <a href="{{ route('user.products.show', $product) }}" class="mt-1 block text-sm font-semibold text-[#202a3b] hover:text-[#12377f]">{{ $product->name }}</a>
                                        <div class="mt-2 flex items-center justify-between gap-3">
                                            <p class="text-sm font-bold text-[#09296d]">Rp{{ number_format($product->price, 0, ',', '.') }}</p>
                                            <span class="text-[10px] font-medium text-emerald-700">Stok tersedia</span>
                                        </div>
                                        <form method="POST" action="{{ route('user.cart.store') }}" class="mt-3">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="w-full rounded-full bg-[#09296d] px-3 py-2 text-xs font-bold text-white transition hover:bg-[#123a8e] focus:outline-none focus:ring-2 focus:ring-[#31569e] focus:ring-offset-2">
                                                Tambah ke Keranjang
                                            </button>
                                        </form>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-2xl border border-dashed border-[#cbd4e8] bg-white px-5 py-10 text-center">
                            <p class="text-sm font-semibold text-[#263044]">Belum ada rekomendasi produk</p>
                            <p class="mt-1 text-xs text-slate-500">Produk yang tersedia akan muncul di sini.</p>
                            <a href="{{ route('user.products.index') }}" class="mt-4 inline-flex rounded-lg bg-[#09296d] px-4 py-2 text-xs font-bold text-white hover:bg-[#123a8e]">Lihat Semua Produk</a>
                        </div>
                    @endif
                </section>

                <aside class="rounded-2xl bg-[#e9eaff] p-4 sm:p-5" aria-labelledby="together-heading">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-[#12377f]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M8 7.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Zm8 2a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7ZM10.5 11l3-1m-3 4 3 1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        </svg>
                        <h2 id="together-heading" class="text-base font-semibold text-[#1a2437]">
                            {{ $hasOrderBasedPairs ? 'Sering Dibeli Bersama' : 'Lengkapi Kebutuhan Anda' }}
                        </h2>
                    </div>
                    <p class="mt-1 text-xs leading-5 text-slate-600">
                        {{ $hasOrderBasedPairs ? 'Kombinasi produk yang sering dipesan dalam satu transaksi.' : 'Pilihan produk untuk melengkapi belanja harian.' }}
                    </p>

                    @if ($frequentlyBoughtTogether->isNotEmpty())
                        <div class="mt-4 space-y-3">
                            @foreach ($frequentlyBoughtTogether as $pair)
                                <div class="rounded-xl bg-white p-2.5">
                                    <div class="flex items-center gap-2.5">
                                        <a href="{{ route('user.products.show', $pair['anchor']) }}" class="h-12 w-12 shrink-0 overflow-hidden rounded-lg focus:outline-none focus:ring-2 focus:ring-[#31569e]" aria-label="Lihat {{ $pair['anchor']->name }}">
                                            <x-product-image :product="$pair['anchor']" class="h-full w-full" />
                                        </a>
                                        <div class="min-w-0 flex-1">
                                            <a href="{{ route('user.products.show', $pair['anchor']) }}" class="block truncate text-[11px] font-semibold text-[#263044] hover:text-[#12377f]">{{ $pair['anchor']->name }}</a>
                                            <p class="my-0.5 text-[10px] font-semibold text-[#75809a]" aria-hidden="true">+</p>
                                            <a href="{{ route('user.products.show', $pair['companion']) }}" class="block truncate text-[11px] font-semibold text-[#263044] hover:text-[#12377f]">{{ $pair['companion']->name }}</a>
                                        </div>
                                        <form method="POST" action="{{ route('user.cart.store') }}">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $pair['companion']->id }}">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#12377f] text-white transition hover:bg-[#09296d] focus:outline-none focus:ring-2 focus:ring-[#31569e] focus:ring-offset-2" aria-label="Tambah {{ $pair['companion']->name }} ke keranjang">
                                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 4v12m-6-6h12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-4 rounded-xl bg-white px-3 py-4 text-xs leading-5 text-slate-600">Belum ada cukup data pesanan untuk menyusun kombinasi produk.</p>
                    @endif

                    <a href="{{ route('user.products.index') }}" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-[#c7ccef] bg-white/70 px-3 py-2.5 text-xs font-bold text-[#12377f] transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#31569e]">
                        Lihat Semua Produk
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                </aside>
            </div>
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
                            <li><a href="{{ route('user.products.index', ['search' => 'Air Galon']) }}" class="hover:text-[#09296d]">Air Galon</a></li>
                            <li><a href="{{ route('user.products.index', ['search' => 'LPG']) }}" class="hover:text-[#09296d]">LPG</a></li>
                            <li><a href="{{ route('user.products.index', ['search' => 'Air Mineral']) }}" class="hover:text-[#09296d]">Minuman</a></li>
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
