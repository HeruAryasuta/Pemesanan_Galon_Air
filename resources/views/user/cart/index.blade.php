@extends('layouts.user')

@section('title', 'Keranjang Belanja | Padmatirta Wisesa Depo')

@section('content')
    @php($cartCount = array_sum($cart))
    @php($subtotal = $products->sum(fn ($product) => $product->price * $cart[$product->id]))
    <div class="flex min-h-screen flex-col bg-[#f8f9ff]">
        <header class="bg-[#f8f9ff]">
            <nav class="mx-auto flex min-h-[53px] max-w-7xl flex-wrap items-center justify-between gap-x-4 px-3 py-1 sm:px-5 lg:flex-nowrap lg:px-8 lg:py-0" aria-label="Navigasi utama">
                <a href="{{ route('user.home') }}" class="order-1 shrink-0 text-[13px] font-extrabold tracking-tight text-[#09296d] sm:text-sm">Padmatirta Wisesa Depo</a>
                <div class="order-3 flex w-full justify-start overflow-x-auto sm:justify-center lg:order-2 lg:w-auto">
                    <x-user-navigation />
                </div>
                <div class="order-2 ml-auto flex shrink-0 items-center gap-1.5 lg:order-3">
                    <a href="{{ route('user.cart.index') }}" aria-current="page" aria-label="Keranjang, {{ $cartCount }} item" class="inline-flex items-center gap-1 rounded-md bg-[#edf2ff] px-2 py-1.5 text-[9px] font-bold text-[#09296d] ring-1 ring-[#d9e3fa] sm:px-2.5">
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

        <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-6 sm:px-6 sm:py-9 lg:px-8">
            <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#4162a5]">Pesanan Anda</p>
                    <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-[#172033] sm:text-3xl">Keranjang Belanja</h1>
                    <p class="mt-1 text-xs text-slate-500">{{ $cartCount }} produk di keranjang</p>
                </div>
                <a href="{{ route('user.products.index') }}" class="text-xs font-bold text-[#31569e] transition hover:text-[#09296d]">← Lanjut belanja</a>
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

            @if ($products->isEmpty())
                <section class="rounded-2xl border border-[#e8ecf6] bg-white px-5 py-12 text-center shadow-[0_5px_18px_rgba(29,48,99,0.05)] sm:py-16" aria-labelledby="empty-cart-title">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-[#edf2ff] text-[#31569e]" aria-hidden="true">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none"><path d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h8.6a2 2 0 0 0 1.95-1.55L21 8H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="19" r="1.4" fill="currentColor"/><circle cx="18" cy="19" r="1.4" fill="currentColor"/></svg>
                    </div>
                    <h2 id="empty-cart-title" class="mt-4 text-lg font-bold text-[#172033]">Keranjang Anda masih kosong</h2>
                    <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-500">Pilih air galon, LPG, atau minuman yang Anda butuhkan dari katalog kami.</p>
                    <a href="{{ route('user.products.index') }}" class="mt-6 inline-flex min-h-10 items-center justify-center rounded-lg bg-[#12377f] px-5 py-2 text-sm font-bold text-white transition hover:bg-[#09296d]">Lihat katalog produk</a>
                </section>
            @else
                <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_300px]">
                    <section class="space-y-3" aria-label="Produk di keranjang">
                        @foreach ($products as $product)
                            @php($quantity = $cart[$product->id])
                            <article class="grid grid-cols-[88px_minmax(0,1fr)] gap-3 rounded-2xl border border-[#e8ecf6] bg-white p-3 shadow-[0_4px_14px_rgba(29,48,99,0.04)] sm:grid-cols-[120px_minmax(0,1fr)] sm:gap-4 sm:p-4">
                                <a href="{{ route('user.products.show', $product) }}" class="block overflow-hidden rounded-xl" aria-label="Lihat detail {{ $product->name }}">
                                    <x-product-image :product="$product" class="aspect-square" />
                                </a>
                                <div class="flex min-w-0 flex-col">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-[9px] font-bold uppercase tracking-[0.1em] text-slate-500">{{ $product->category->name }}</p>
                                            <h2 class="mt-1 text-sm font-bold leading-5 text-[#202a3b] sm:text-base">
                                                <a href="{{ route('user.products.show', $product) }}" class="hover:text-[#12377f]">{{ $product->name }}</a>
                                            </h2>
                                            <p class="mt-1 text-[10px] text-slate-500">Rp{{ number_format($product->price, 0, ',', '.') }} / {{ $product->unit }}</p>
                                        </div>
                                        <form method="POST" action="{{ route('user.cart.destroy', $product->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-md px-2 py-1 text-[10px] font-semibold text-slate-500 transition hover:bg-rose-50 hover:text-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-300" aria-label="Hapus {{ $product->name }} dari keranjang">Hapus</button>
                                        </form>
                                    </div>

                                    <div class="mt-auto flex flex-wrap items-end justify-between gap-3 pt-4">
                                        <form method="POST" action="{{ route('user.cart.update', $product->id) }}" class="flex items-end gap-2">
                                            @csrf
                                            @method('PUT')
                                            <div>
                                                <label for="quantity-{{ $product->id }}" class="mb-1 block text-[9px] font-semibold text-slate-600">Jumlah</label>
                                                <input id="quantity-{{ $product->id }}" name="quantity" type="number" min="1" max="{{ min($product->stock, 100) }}" value="{{ $quantity }}" @disabled($product->stock < 1) required class="w-20 rounded-lg border-[#e0e6f3] px-2.5 py-1.5 text-xs text-slate-700 focus:border-[#31569e] focus:ring-[#31569e]">
                                            </div>
                                            <button type="submit" @disabled($product->stock < 1) class="min-h-[34px] rounded-lg border border-[#dce4f5] px-3 py-1.5 text-[10px] font-bold text-[#31569e] transition hover:bg-[#edf2ff] disabled:cursor-not-allowed disabled:opacity-50">Perbarui</button>
                                        </form>
                                        <div class="text-right">
                                            @if ($product->stock < 1)
                                                <p class="text-[10px] font-semibold text-rose-600">Stok habis</p>
                                            @elseif ($quantity > $product->stock)
                                                <p class="text-[10px] font-semibold text-amber-700">Stok tersisa {{ $product->stock }} {{ $product->unit }}</p>
                                            @endif
                                            <p class="mt-1 text-sm font-extrabold text-[#09296d]">Rp{{ number_format($product->price * $quantity, 0, ',', '.') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </section>

                    <aside class="rounded-2xl border border-[#e8ecf6] bg-white p-4 shadow-[0_5px_18px_rgba(29,48,99,0.05)] lg:sticky lg:top-5" aria-labelledby="order-summary-title">
                        <h2 id="order-summary-title" class="text-sm font-bold text-[#202a3b]">Ringkasan Belanja</h2>
                        <div class="mt-4 flex items-center justify-between gap-3 border-b border-[#edf0f7] pb-3 text-xs text-slate-600">
                            <span>Total produk</span>
                            <span>{{ $cartCount }} item</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 py-4">
                            <span class="text-xs font-bold text-[#202a3b]">Subtotal</span>
                            <span class="text-base font-extrabold text-[#09296d]">Rp{{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>
                        <p class="text-[10px] leading-5 text-slate-500">Subtotal belum termasuk biaya pengantaran.</p>
                        @if ($products->contains(fn ($product) => $product->stock < $cart[$product->id]))
                            <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-[10px] leading-4 text-amber-800" role="status">Sesuaikan jumlah produk dengan stok yang tersedia sebelum checkout.</p>
                        @else
                            <a href="{{ route('user.checkout.index') }}" class="mt-4 inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-[#12377f] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#09296d] focus:outline-none focus:ring-2 focus:ring-[#31569e] focus:ring-offset-2">Lanjut ke checkout</a>
                        @endif
                    </aside>
                </div>
            @endif
        </main>

        <footer class="mt-auto border-t border-[#dbe4f7] bg-[#e6edff]">
            <div class="mx-auto flex max-w-7xl flex-col gap-2 px-5 py-4 text-[9px] text-slate-600 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-8">
                <a href="{{ route('user.home') }}" class="font-bold text-[#09296d]">Padmatirta Wisesa Depo</a>
                <p>&copy; {{ now()->year }} Padmatirta Wisesa Depo. All rights reserved.</p>
                <a href="{{ route('user.home') }}#tentang-kami" class="font-semibold hover:text-[#09296d]">Area pengiriman</a>
            </div>
        </footer>
    </div>
@endsection
