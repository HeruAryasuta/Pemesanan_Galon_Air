@extends('layouts.user')

@section('title', 'Checkout | Padmatirta Wisesa Depo')

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
                    <a href="{{ route('user.cart.index') }}" aria-label="Keranjang, {{ $cartCount }} item" class="inline-flex items-center gap-1 rounded-md bg-[#edf2ff] px-2 py-1.5 text-[9px] font-bold text-[#09296d] transition hover:bg-[#dfe8ff] sm:px-2.5">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h8.6a2 2 0 0 0 1.95-1.55L21 8H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="19" r="1.4" fill="currentColor"/><circle cx="18" cy="19" r="1.4" fill="currentColor"/></svg>
                        Keranjang @if ($cartCount > 0)<span class="rounded-full bg-white px-1.5 py-0.5 text-[10px]">{{ $cartCount }}</span>@endif
                    </a>
                    <a href="{{ route('profile.edit') }}" class="rounded-md bg-[#09296d] px-2.5 py-1.5 text-[9px] font-bold text-white transition hover:bg-[#123a8e]">Akun</a>
                </div>
            </nav>
        </header>

        <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-6 sm:px-6 sm:py-9 lg:px-8">
            <div class="mb-5">
                <a href="{{ route('user.cart.index') }}" class="text-[10px] font-bold text-[#31569e] hover:text-[#09296d]">← Kembali ke keranjang</a>
                <p class="mt-4 text-[10px] font-bold uppercase tracking-[0.14em] text-[#4162a5]">Satu langkah lagi</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-[#172033] sm:text-3xl">Checkout</h1>
                <p class="mt-1 text-xs text-slate-500">Pilih alamat pengantaran dan periksa kembali pesanan Anda.</p>
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

            <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div class="space-y-4">
                    <section class="rounded-2xl border border-[#e8ecf6] bg-white p-4 shadow-[0_4px_14px_rgba(29,48,99,0.04)] sm:p-5" aria-labelledby="delivery-address-title">
                        <div>
                            <p class="text-[9px] font-bold uppercase tracking-[0.1em] text-[#4162a5]">Pengantaran</p>
                            <h2 id="delivery-address-title" class="mt-1 text-sm font-bold text-[#202a3b]">Alamat tujuan</h2>
                        </div>

                        @if ($addresses->isNotEmpty())
                            <fieldset class="mt-4 space-y-2">
                                <legend class="sr-only">Pilih alamat pengantaran</legend>
                                @foreach ($addresses as $address)
                                    <label for="address-{{ $address->id }}" class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition has-[:checked]:border-[#31569e] has-[:checked]:bg-[#f4f7ff]">
                                        <input
                                            id="address-{{ $address->id }}"
                                            type="radio"
                                            name="address_id"
                                            value="{{ $address->id }}"
                                            form="place-order-form"
                                            required
                                            @checked((string) old('address_id', $addresses->firstWhere('is_primary', true)?->id ?? $addresses->first()->id) === (string) $address->id)
                                            class="mt-0.5 h-4 w-4 border-slate-300 text-[#12377f] focus:ring-[#31569e]"
                                        >
                                        <span class="min-w-0 flex-1">
                                            <span class="flex flex-wrap items-center gap-2 text-xs font-bold text-[#202a3b]">
                                                {{ $address->label ?: 'Alamat pengantaran' }}
                                                @if ($address->is_primary)
                                                    <span class="rounded-full bg-[#edf2ff] px-2 py-0.5 text-[8px] font-bold text-[#31569e]">Utama</span>
                                                @endif
                                            </span>
                                            <span class="mt-1 block text-[11px] leading-5 text-slate-600">{{ $address->full_address }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </fieldset>
                        @else
                            <p class="mt-4 rounded-xl bg-amber-50 px-3 py-2.5 text-[11px] leading-5 text-amber-800">Tambahkan alamat pengantaran terlebih dahulu agar pesanan dapat dibuat.</p>
                        @endif

                        <details class="mt-4 rounded-xl border border-[#e8ecf6] bg-[#fbfcff] p-3" @if ($addresses->isEmpty() || $errors->has('full_address') || $errors->has('label')) open @endif>
                            <summary class="cursor-pointer text-[11px] font-bold text-[#31569e]">Tambah alamat baru</summary>
                            <form method="POST" action="{{ route('user.addresses.store') }}" class="mt-3 space-y-3">
                                @csrf
                                <div>
                                    <label for="new-address-label" class="mb-1 block text-[10px] font-semibold text-slate-700">Label alamat <span class="font-normal text-slate-500">(opsional)</span></label>
                                    <input id="new-address-label" name="label" type="text" value="{{ old('label') }}" maxlength="100" placeholder="Contoh: Rumah" class="w-full rounded-lg border-[#e0e6f3] px-3 py-2 text-xs focus:border-[#31569e] focus:ring-[#31569e]">
                                </div>
                                <div>
                                    <label for="new-full-address" class="mb-1 block text-[10px] font-semibold text-slate-700">Alamat lengkap</label>
                                    <textarea id="new-full-address" name="full_address" rows="3" maxlength="1000" required class="w-full rounded-lg border-[#e0e6f3] px-3 py-2 text-xs focus:border-[#31569e] focus:ring-[#31569e]" placeholder="Jalan, nomor rumah, RT/RW, kelurahan, dan patokan">{{ old('full_address') }}</textarea>
                                </div>
                                <x-address-location-picker
                                    id="checkout-new"
                                    :latitude="old('latitude')"
                                    :longitude="old('longitude')"
                                />
                                <label class="flex cursor-pointer items-center gap-2 text-[10px] font-medium text-slate-600">
                                    <input type="checkbox" name="is_primary" value="1" @checked(old('is_primary')) class="h-4 w-4 rounded border-slate-300 text-[#12377f] focus:ring-[#31569e]">
                                    Jadikan alamat utama
                                </label>
                                <button type="submit" class="inline-flex min-h-9 items-center justify-center rounded-lg border border-[#dce4f5] px-4 py-2 text-[10px] font-bold text-[#31569e] transition hover:bg-[#edf2ff]">Simpan alamat</button>
                            </form>
                        </details>
                    </section>

                    <section class="rounded-2xl border border-[#e8ecf6] bg-white p-4 shadow-[0_4px_14px_rgba(29,48,99,0.04)] sm:p-5" aria-labelledby="payment-method-title">
                        <p class="text-[9px] font-bold uppercase tracking-[0.1em] text-[#4162a5]">Pembayaran</p>
                        <h2 id="payment-method-title" class="mt-1 text-sm font-bold text-[#202a3b]">Metode pembayaran</h2>
                        <label for="payment-method-cod" class="mt-4 flex cursor-pointer items-start gap-3 rounded-xl border border-[#31569e] bg-[#f4f7ff] p-3">
                            <input
                                id="payment-method-cod"
                                type="radio"
                                name="payment_method"
                                value="cod"
                                form="place-order-form"
                                required
                                @checked(old('payment_method', 'cod') === 'cod')
                                class="mt-0.5 h-4 w-4 border-slate-300 text-[#12377f] focus:ring-[#31569e]"
                            >
                            <span>
                                <span class="block text-xs font-bold text-[#202a3b]">COD — Bayar saat diterima</span>
                                <span class="mt-1 block text-[11px] leading-5 text-slate-600">Bayar langsung kepada kurir setelah pesanan sampai.</span>
                            </span>
                        </label>
                        @if ($bankTransferConfigured)
                            <label for="payment-method-transfer" class="mt-2 flex cursor-pointer items-start gap-3 rounded-xl border border-[#e0e6f3] p-3 transition hover:border-[#31569e] hover:bg-[#f8faff]">
                                <input
                                    id="payment-method-transfer"
                                    type="radio"
                                    name="payment_method"
                                    value="bank_transfer"
                                    form="place-order-form"
                                    @checked(old('payment_method') === 'bank_transfer')
                                    class="mt-0.5 h-4 w-4 border-slate-300 text-[#12377f] focus:ring-[#31569e]"
                                >
                                <span>
                                    <span class="block text-xs font-bold text-[#202a3b]">Transfer bank</span>
                                    <span class="mt-1 block text-[11px] leading-5 text-slate-600">Transfer sesuai total pesanan, lalu unggah bukti pembayaran.</span>
                                </span>
                            </label>
                            <div class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-[10px] leading-5 text-slate-600">
                                <p class="font-bold text-[#202a3b]">Rekening tujuan</p>
                                <p>{{ $bankTransfer['bank_name'] }} · {{ $bankTransfer['account_number'] }}</p>
                                <p>a.n. {{ $bankTransfer['account_name'] }}</p>
                            </div>
                        @else
                            <p class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-[10px] leading-5 text-slate-500">Pembayaran transfer belum tersedia. Silakan hubungi admin untuk informasi pembayaran.</p>
                        @endif
                    </section>

                    <section class="rounded-2xl border border-[#e8ecf6] bg-white p-4 shadow-[0_4px_14px_rgba(29,48,99,0.04)] sm:p-5" aria-labelledby="order-notes-title">
                        <h2 id="order-notes-title" class="text-sm font-bold text-[#202a3b]">Catatan untuk pesanan</h2>
                        <label for="order-notes" class="mt-3 block text-[10px] font-semibold text-slate-600">Catatan pengantaran <span class="font-normal">(opsional)</span></label>
                        <textarea id="order-notes" name="notes" form="place-order-form" rows="3" maxlength="500" placeholder="Contoh: titipkan ke satpam jika tidak ada orang di rumah." class="mt-1.5 w-full rounded-lg border-[#e0e6f3] px-3 py-2 text-xs focus:border-[#31569e] focus:ring-[#31569e]">{{ old('notes') }}</textarea>
                    </section>
                </div>

                <aside class="rounded-2xl border border-[#e8ecf6] bg-white p-4 shadow-[0_5px_18px_rgba(29,48,99,0.05)] lg:sticky lg:top-5" aria-labelledby="checkout-summary-title">
                    <h2 id="checkout-summary-title" class="text-sm font-bold text-[#202a3b]">Ringkasan pesanan</h2>
                    <div class="mt-4 max-h-64 space-y-3 overflow-y-auto border-b border-[#edf0f7] pb-4">
                        @foreach ($products as $product)
                            <div class="flex items-start justify-between gap-3 text-[11px]">
                                <span class="min-w-0 text-slate-600">{{ $product->name }} <span class="whitespace-nowrap">× {{ $cart[$product->id] }}</span></span>
                                <span class="shrink-0 font-semibold text-[#202a3b]">Rp{{ number_format($product->price * $cart[$product->id], 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="space-y-3 border-t border-[#edf0f7] py-4 text-[11px]">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-semibold text-slate-600">Subtotal produk</span>
                            <span class="font-semibold text-[#202a3b]">Rp{{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-semibold text-slate-600">Biaya pengantaran</span>
                            <span class="font-semibold text-[#202a3b]">Rp{{ number_format($deliveryFee, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t border-[#edf0f7] pt-4">
                        <span class="text-xs font-bold text-[#202a3b]">Total pesanan</span>
                        <span class="text-base font-extrabold text-[#09296d]">Rp{{ number_format($subtotal + $deliveryFee, 0, ',', '.') }}</span>
                    </div>
                    <p class="mt-2 text-[10px] leading-5 text-slate-500">Tarif pengantaran tetap Rp{{ number_format($deliveryFee, 0, ',', '.') }} per pesanan.</p>

                    @if ($cartHasUnavailableItems)
                        <p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-[10px] leading-4 text-amber-800" role="status">Ada produk yang stoknya tidak mencukupi atau sudah tidak tersedia. Periksa kembali keranjang Anda.</p>
                        <a href="{{ route('user.cart.index') }}" class="mt-3 inline-flex min-h-10 w-full items-center justify-center rounded-lg border border-[#dce4f5] px-4 py-2 text-xs font-bold text-[#31569e] hover:bg-[#edf2ff]">Periksa keranjang</a>
                    @elseif ($addresses->isEmpty())
                        <button type="button" disabled class="mt-4 inline-flex min-h-11 w-full cursor-not-allowed items-center justify-center rounded-lg bg-slate-300 px-4 py-2 text-xs font-bold text-slate-600">Tambahkan alamat untuk lanjut</button>
                    @else
                        <form id="place-order-form" method="POST" action="{{ route('user.checkout.store') }}">
                            @csrf
                            <button type="submit" class="mt-4 inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-[#12377f] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#09296d] focus:outline-none focus:ring-2 focus:ring-[#31569e] focus:ring-offset-2">Buat pesanan</button>
                        </form>
                    @endif
                    <p class="mt-3 text-center text-[9px] leading-4 text-slate-500">Pesanan akan tercatat dengan status menunggu konfirmasi.</p>
                </aside>
            </div>
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
