@extends('layouts.user')

@section('title', $product->name . ' | Padmatirta Wisesa Depo')

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

        <main class="mx-auto max-w-6xl px-4 py-5 sm:px-6 sm:py-8 lg:px-8">
            <nav class="mb-4 flex items-center gap-2 text-[10px] font-semibold text-slate-500" aria-label="Breadcrumb">
                <a href="{{ route('user.home') }}" class="hover:text-[#12377f]">Beranda</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('user.products.index') }}" class="hover:text-[#12377f]">Produk</a>
                <span aria-hidden="true">/</span>
                <span class="truncate text-slate-700" aria-current="page">{{ $product->name }}</span>
            </nav>

            @if (session('success') || session('error') || $errors->any())
                <div class="mb-4" role="status" aria-live="polite">
                    @if (session('success'))
                        <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</p>
                    @elseif (session('error'))
                        <p class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">{{ session('error') }}</p>
                    @else
                        <p class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">{{ $errors->first() }}</p>
                    @endif
                </div>
            @endif

            <section class="grid gap-5 rounded-2xl border border-[#edf0f7] bg-white p-3 shadow-[0_5px_18px_rgba(29,48,99,0.05)] sm:p-5 md:grid-cols-2 md:gap-8" aria-labelledby="product-name">
                <div class="relative self-start overflow-hidden rounded-xl">
                    <x-product-image :product="$product" class="aspect-square rounded-xl" />
                    <span class="absolute right-3 top-3 rounded-full bg-white/90 px-3 py-1.5 text-[10px] font-bold text-[#12377f] shadow-sm">
                        @if ($product->stock < 1)
                            Stok habis
                        @elseif ($product->stock >= 20)
                            Stok banyak
                        @else
                            Tersedia
                        @endif
                    </span>
                </div>

                <div class="flex flex-col py-1 sm:py-2">
                    <a href="{{ route('user.products.index', ['category' => $product->category_id]) }}" class="w-fit rounded-full bg-[#edf2ff] px-3 py-1.5 text-[10px] font-bold text-[#214b9a] hover:bg-[#dfe8ff]">{{ $product->category->name }}</a>
                    <h1 id="product-name" class="mt-3 text-xl font-bold leading-tight tracking-tight text-[#172033] sm:text-2xl">{{ $product->name }}</h1>
                    <p class="mt-1 text-xs text-slate-500">Dijual per {{ $product->unit }}</p>

                    <p class="mt-5 text-2xl font-extrabold tracking-tight text-[#09296d]">Rp{{ number_format($product->price, 0, ',', '.') }}</p>

                    <div class="mt-5 border-t border-[#edf0f7] pt-4">
                        <h2 class="text-xs font-bold text-[#253047]">Deskripsi Produk</h2>
                        <p class="mt-2 whitespace-pre-line text-xs leading-5 text-slate-600">{{ $product->description ?: 'Informasi produk akan segera diperbarui.' }}</p>
                    </div>

                    <div class="mt-4 flex items-center gap-2 text-[10px] text-slate-600">
                        <span class="h-2 w-2 rounded-full {{ $product->stock > 0 ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                        @if ($product->stock > 0)
                            Tersedia{{ $product->stock < 10 ? ' — stok tersisa ' . $product->stock : '' }}
                        @else
                            Stok sedang habis
                        @endif
                    </div>

                    @if ($product->stock > 0)
                        <form method="POST" action="{{ route('user.cart.store') }}" class="mt-5 flex flex-wrap items-end gap-3">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <div>
                                <label for="quantity" class="mb-1.5 block text-[10px] font-bold text-slate-700">Jumlah</label>
                                <input id="quantity" name="quantity" type="number" min="1" max="{{ min($product->stock, 100) }}" value="{{ old('quantity', 1) }}" required class="w-24 rounded-lg border-[#e0e6f3] px-3 py-2 text-sm focus:border-[#31569e] focus:ring-[#31569e]">
                            </div>
                            <button type="submit" class="inline-flex min-h-[40px] flex-1 items-center justify-center gap-2 rounded-lg bg-[#12377f] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#09296d] sm:flex-none">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h8.6a2 2 0 0 0 1.95-1.55L21 8H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="19" r="1.4" fill="currentColor"/><circle cx="18" cy="19" r="1.4" fill="currentColor"/></svg>
                                Tambah ke Keranjang
                            </button>
                        </form>
                    @else
                        <button type="button" disabled class="mt-5 inline-flex min-h-[40px] w-full items-center justify-center rounded-lg bg-slate-200 px-4 py-2 text-xs font-bold text-slate-500 sm:w-fit">Stok Habis</button>
                    @endif

                    <a href="{{ route('user.products.index') }}" class="mt-4 w-fit text-[10px] font-semibold text-[#31569e] hover:underline">← Kembali ke katalog</a>
                </div>
            </section>

            @if ($relatedProducts->isNotEmpty())
                <section class="mt-8" aria-labelledby="related-products">
                    <div class="flex items-end justify-between gap-3">
                        <div>
                            <p class="text-[9px] font-bold uppercase tracking-[0.12em] text-[#4162a5]">Pilihan lainnya</p>
                            <h2 id="related-products" class="mt-1 text-lg font-bold text-[#1a2437]">Produk serupa</h2>
                        </div>
                        <a href="{{ route('user.products.index', ['category' => $product->category_id]) }}" class="text-[10px] font-bold text-[#31569e] hover:underline">Lihat semua</a>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($relatedProducts as $relatedProduct)
                            <x-product-card :product="$relatedProduct" />
                        @endforeach
                    </div>
                </section>
            @endif
        </main>

        <footer class="border-t border-[#dbe4f7] bg-[#e6edff]">
            <div class="mx-auto flex max-w-7xl flex-col gap-2 px-5 py-4 text-[9px] text-slate-600 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-8">
                <a href="{{ route('user.home') }}" class="font-bold text-[#09296d]">Padmatirta Wisesa Depo</a>
                <p>&copy; {{ now()->year }} Padmatirta Wisesa Depo. All rights reserved.</p>
                <a href="{{ route('user.home') }}#tentang-kami" class="font-semibold hover:text-[#09296d]">Area pengiriman</a>
            </div>
        </footer>
    </div>
@endsection
