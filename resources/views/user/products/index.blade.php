@extends('layouts.user')

@section('title', 'Semua Produk | Padmatirta Wisesa Depo')

@section('content')
    @php($cartCount = array_sum(session('cart', [])))
    <div class="min-h-screen bg-[#f8f9ff]">
        <header class="bg-[#f8f9ff]">
            <nav class="mx-auto flex min-h-[53px] max-w-7xl flex-wrap items-center justify-between gap-x-4 px-3 py-1 sm:px-5 lg:flex-nowrap lg:px-8 lg:py-0" aria-label="Navigasi utama">
                <a href="{{ route('user.home') }}" class="order-1 shrink-0 text-[13px] font-extrabold tracking-tight text-[#09296d] sm:text-sm">Padmatirta Wisesa Depo</a>
                <div class="order-3 flex w-full justify-start overflow-x-auto sm:justify-center lg:order-2 lg:w-auto">
                    <x-user-navigation />
                </div>
                <div class="order-2 ml-auto flex shrink-0 items-center gap-1.5 lg:order-3">
                    <a href="{{ route('user.cart.index') }}" class="inline-flex items-center gap-1 rounded-md bg-[#edf2ff] px-2 py-1.5 text-[9px] font-bold text-[#09296d] transition hover:bg-[#dfe8ff]" aria-label="Keranjang, {{ $cartCount }} item">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h8.6a2 2 0 0 0 1.95-1.55L21 8H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="19" r="1.4" fill="currentColor"/><circle cx="18" cy="19" r="1.4" fill="currentColor"/></svg>
                        Keranjang @if ($cartCount > 0)<span class="rounded-full bg-white px-1.5 py-0.5 text-[10px]">{{ $cartCount }}</span>@endif
                    </a>
                    @guest
                        <a href="{{ route('login') }}" class="rounded-md bg-[#09296d] px-2.5 py-1.5 text-[9px] font-bold text-white transition hover:bg-[#123a8e]">Login</a>
                    @else
                        <a href="{{ route('profile.edit') }}" class="rounded-md bg-[#09296d] px-2.5 py-1.5 text-[9px] font-bold text-white transition hover:bg-[#123a8e]">Akun</a>
                    @endguest
                </div>
            </nav>
        </header>

        <main class="mx-auto grid max-w-7xl gap-4 px-4 py-5 sm:grid-cols-[138px_minmax(0,1fr)] sm:px-5 sm:py-6 lg:gap-4 lg:px-8">
            <aside class="space-y-3" aria-label="Filter produk">
                <form method="GET" action="{{ route('user.products.index') }}" class="space-y-3">
                    <input type="hidden" name="search" value="{{ $search }}">
                    <input type="hidden" name="sort" value="{{ $sort }}">

                    <section class="rounded-2xl bg-white px-3 py-2.5 shadow-[0_4px_14px_rgba(29,48,99,0.06)]">
                        <h2 class="border-b border-[#e7ebf5] pb-2 text-[10px] font-bold text-[#253047]">Kategori</h2>
                        <div class="mt-2 space-y-1.5">
                            <label class="flex cursor-pointer items-center gap-1.5 text-[9px] text-slate-600">
                                <input type="radio" name="category" value="" @checked(! $selectedCategory) class="h-3 w-3 border-slate-300 text-[#12377f] focus:ring-[#12377f]">
                                Semua Kategori
                            </label>
                            @foreach ($categories as $category)
                                <label class="flex cursor-pointer items-center gap-1.5 text-[9px] text-slate-600">
                                    <input type="radio" name="category" value="{{ $category->id }}" @checked($selectedCategory?->id === $category->id) class="h-3 w-3 border-slate-300 text-[#12377f] focus:ring-[#12377f]">
                                    {{ $category->name }}
                                </label>
                            @endforeach
                        </div>
                    </section>

                    <section class="rounded-2xl bg-white px-3 py-2.5 shadow-[0_4px_14px_rgba(29,48,99,0.06)]">
                        <h2 class="border-b border-[#e7ebf5] pb-2 text-[10px] font-bold text-[#253047]">Rentang Harga</h2>
                        <div class="mt-2 space-y-1.5">
                            <label class="flex items-center gap-1.5 text-[9px] text-slate-600">
                                <span class="w-7 shrink-0">Min</span>
                                <input type="number" name="min_price" min="0" step="1000" value="{{ $minPrice }}" placeholder="Rp 0" class="w-full rounded-md border-[#e0e6f3] px-2 py-1 text-[9px] text-slate-700 placeholder:text-slate-400 focus:border-[#31569e] focus:ring-1 focus:ring-[#31569e]">
                            </label>
                            <label class="flex items-center gap-1.5 text-[9px] text-slate-600">
                                <span class="w-7 shrink-0">Max</span>
                                <input type="number" name="max_price" min="0" step="1000" value="{{ $maxPrice }}" placeholder="Rp ~" class="w-full rounded-md border-[#e0e6f3] px-2 py-1 text-[9px] text-slate-700 placeholder:text-slate-400 focus:border-[#31569e] focus:ring-1 focus:ring-[#31569e]">
                            </label>
                        </div>
                    </section>

                    <section class="rounded-2xl bg-white px-3 py-2.5 shadow-[0_4px_14px_rgba(29,48,99,0.06)]">
                        <h2 class="border-b border-[#e7ebf5] pb-2 text-[10px] font-bold text-[#253047]">Status Stok</h2>
                        <label class="mt-2 flex cursor-pointer items-center gap-1.5 text-[9px] text-slate-600">
                            <input type="checkbox" name="in_stock" value="1" @checked($inStock) class="h-3 w-3 rounded border-slate-300 text-[#12377f] focus:ring-[#12377f]">
                            Tersedia
                        </label>
                    </section>

                    <button type="submit" class="w-full rounded-lg bg-[#09296d] px-3 py-2 text-[10px] font-bold text-white transition hover:bg-[#123a8e]">Terapkan Filter</button>
                    @if ($selectedCategory || $minPrice !== '' || $maxPrice !== '' || $inStock || $search !== '')
                        <a href="{{ route('user.products.index', array_filter(['sort' => $sort !== 'popular' ? $sort : null])) }}" class="block text-center text-[9px] font-semibold text-[#31569e] hover:underline">Hapus filter</a>
                    @endif
                </form>
            </aside>

            <section class="min-w-0">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-[#1a2437] sm:text-2xl">
                            @if ($selectedCategory)
                                {{ $selectedCategory->name }}
                            @elseif ($search !== '')
                                Hasil Pencarian
                            @else
                                Semua Produk
                            @endif
                        </h1>
                        <p class="mt-1 text-[9px] text-slate-600 sm:text-[10px]">
                            Menampilkan {{ $products->total() }} produk siap antar di area Surabaya Timur.
                        </p>
                    </div>
                    <form method="GET" action="{{ route('user.products.index') }}" class="flex gap-2">
                        <input type="hidden" name="search" value="{{ $search }}">
                        <input type="hidden" name="category" value="{{ $selectedCategory?->id }}">
                        <input type="hidden" name="min_price" value="{{ $minPrice }}">
                        <input type="hidden" name="max_price" value="{{ $maxPrice }}">
                        @if ($inStock)
                            <input type="hidden" name="in_stock" value="1">
                        @endif
                        <label for="search" class="sr-only">Cari produk</label>
                        <div class="relative block">
                            <svg class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="8.8" cy="8.8" r="5.8" stroke="currentColor" stroke-width="1.6"/><path d="m13.2 13.2 4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                            <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Cari produk..." class="w-32 rounded-full border-[#e2e7f2] py-1.5 pl-8 pr-3 text-[9px] focus:border-[#31569e] focus:ring-[#31569e] sm:w-40">
                        </div>
                        <label for="sort" class="sr-only">Urutkan produk</label>
                        <select id="sort" name="sort" onchange="this.form.submit()" class="w-28 rounded-full border-[#e2e7f2] py-1.5 pl-3 pr-7 text-[9px] text-slate-700 focus:border-[#31569e] focus:ring-[#31569e] sm:w-32">
                            <option value="popular" @selected($sort === 'popular')>Terpopuler</option>
                            <option value="newest" @selected($sort === 'newest')>Terbaru</option>
                            <option value="price_asc" @selected($sort === 'price_asc')>Harga terendah</option>
                            <option value="price_desc" @selected($sort === 'price_desc')>Harga tertinggi</option>
                        </select>
                    </form>
                </div>

                @if (session('success') || session('error') || $errors->any())
                    <div class="mt-3" role="status" aria-live="polite">
                        @if (session('success'))
                            <p class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800">{{ session('success') }}</p>
                        @elseif (session('error'))
                            <p class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-800">{{ session('error') }}</p>
                        @else
                            <p class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-800">{{ $errors->first() }}</p>
                        @endif
                    </div>
                @endif

                @if ($products->isNotEmpty())
                    <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-4">
                        @foreach ($products as $product)
                            <x-product-card :product="$product" />
                        @endforeach
                    </div>

                    @if ($products->hasPages())
                        <nav class="mt-5 flex items-center justify-center border-t border-[#e6eaf4] pt-3" aria-label="Navigasi halaman katalog">
                            {{ $products->onEachSide(1)->links('pagination::simple-tailwind') }}
                        </nav>
                    @endif
                @else
                    <div class="mt-4 rounded-2xl border border-dashed border-[#d7deef] bg-white px-5 py-12 text-center">
                        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-[#edf2ff] text-[#214b9a]">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h8.6a2 2 0 0 0 1.95-1.55L21 8H6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="19" r="1.4" fill="currentColor"/><circle cx="18" cy="19" r="1.4" fill="currentColor"/></svg>
                        </span>
                        <h2 class="mt-3 text-sm font-extrabold text-slate-900">{{ $search !== '' || $selectedCategory || $minPrice !== '' || $maxPrice !== '' || $inStock ? 'Produk tidak ditemukan' : 'Katalog sedang disiapkan' }}</h2>
                        <p class="mx-auto mt-1.5 max-w-md text-[10px] leading-4 text-slate-500">
                            {{ $search !== '' || $selectedCategory || $minPrice !== '' || $maxPrice !== '' || $inStock ? 'Coba ubah kata kunci atau filter untuk menemukan produk yang kamu cari.' : 'Produk akan muncul di sini setelah ditambahkan ke katalog.' }}
                        </p>
                        @if ($search !== '' || $selectedCategory || $minPrice !== '' || $maxPrice !== '' || $inStock)
                            <a href="{{ route('user.products.index') }}" class="mt-4 inline-flex rounded-full bg-[#09296d] px-4 py-2 text-[10px] font-bold text-white transition hover:bg-[#123a8e]">Hapus filter</a>
                        @endif
                    </div>
                @endif
            </section>
        </main>

        <footer class="border-t border-[#dbe4f7] bg-[#e6edff]">
            <div class="mx-auto max-w-7xl px-5 pb-3 pt-4 sm:px-8 lg:px-8">
                <div class="grid gap-4 sm:grid-cols-[1.5fr_1fr_1fr]">
                    <div>
                        <a href="{{ route('user.home') }}" class="text-xs font-bold text-[#09296d]">Padmatirta Wisesa Depo</a>
                        <p class="mt-1.5 max-w-xs text-[9px] leading-4 text-slate-600">Layanan antar cepat kebutuhan harian Anda. Mengutamakan kepercayaan dan efisiensi.</p>
                    </div>
                    <div>
                        <h2 class="text-[9px] font-bold text-[#09296d]">Links</h2>
                        <p class="mt-1.5 text-[9px] leading-4 text-slate-600">Contact Info<br>Social Media</p>
                    </div>
                    <div>
                        <h2 class="text-[9px] font-bold text-[#09296d]">Area Layanan</h2>
                        <p class="mt-1.5 text-[9px] leading-4 text-slate-600">Gunung Anyar<br>Medokan Ayu<br>Rungkut</p>
                    </div>
                </div>
                <div class="mt-3 border-t border-[#d4def5] pt-2 text-center text-[9px] text-slate-600">
                    &copy; {{ now()->year }} Padmatirta Wisesa Depo. All rights reserved.
                </div>
            </div>
        </footer>
    </div>
@endsection
