@extends('layouts.user')

@section('title', 'Pesanan Saya | Padmatirta Wisesa Depo')

@section('content')
    <div class="flex min-h-screen flex-col bg-[#f8f9ff]">
        <header class="bg-[#f8f9ff]">
            <nav class="mx-auto flex min-h-[53px] max-w-7xl flex-wrap items-center justify-between gap-x-4 px-3 py-1 sm:px-5 lg:flex-nowrap lg:px-8 lg:py-0" aria-label="Navigasi utama">
                <a href="{{ route('user.home') }}" class="order-1 shrink-0 text-[13px] font-extrabold tracking-tight text-[#09296d] sm:text-sm">Padmatirta Wisesa Depo</a>
                <div class="order-3 flex w-full justify-start overflow-x-auto sm:justify-center lg:order-2 lg:w-auto">
                    <x-user-navigation />
                </div>
                <div class="order-2 ml-auto flex shrink-0 items-center gap-1.5 lg:order-3">
                    <a href="{{ route('user.cart.index') }}" class="inline-flex items-center gap-1 rounded-md bg-[#edf2ff] px-2 py-1.5 text-[9px] font-bold text-[#09296d] transition hover:bg-[#dfe8ff] sm:px-2.5">Keranjang</a>
                    <a href="{{ route('profile.edit') }}" class="rounded-md bg-[#09296d] px-2.5 py-1.5 text-[9px] font-bold text-white transition hover:bg-[#123a8e]">Akun</a>
                </div>
            </nav>
        </header>

        <main class="mx-auto w-full max-w-5xl flex-1 px-4 py-6 sm:px-6 sm:py-9 lg:px-8">
            <div class="mb-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#4162a5]">Riwayat pembelian</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-[#172033] sm:text-3xl">Pesanan Saya</h1>
                <p class="mt-1 text-xs text-slate-500">Pantau pesanan dan status pengantaran Anda.</p>
            </div>

            @if (session('success') || session('error'))
                <div class="mb-5" role="status" aria-live="polite">
                    @if (session('success'))
                        <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</p>
                    @else
                        <p class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">{{ session('error') }}</p>
                    @endif
                </div>
            @endif

            @if ($orders->isEmpty())
                <section class="rounded-2xl border border-[#e8ecf6] bg-white px-5 py-12 text-center shadow-[0_5px_18px_rgba(29,48,99,0.05)] sm:py-16">
                    <h2 class="text-lg font-bold text-[#172033]">Belum ada pesanan</h2>
                    <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-500">Pesanan yang Anda buat akan tampil di sini.</p>
                    <a href="{{ route('user.products.index') }}" class="mt-6 inline-flex min-h-10 items-center justify-center rounded-lg bg-[#12377f] px-5 py-2 text-sm font-bold text-white transition hover:bg-[#09296d]">Mulai belanja</a>
                </section>
            @else
                <section class="space-y-4" aria-label="Daftar pesanan">
                    @foreach ($orders as $order)
                        <article class="rounded-2xl border border-[#e8ecf6] bg-white p-4 shadow-[0_4px_14px_rgba(29,48,99,0.04)] sm:p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-[#edf0f7] pb-3">
                                <div>
                                    <p class="text-[9px] font-bold uppercase tracking-[0.1em] text-slate-500">Pesanan #{{ $order->id }}</p>
                                    <p class="mt-1 text-[10px] text-slate-500">{{ $order->ordered_at?->timezone(config('app.display_timezone'))->format('d M Y, H:i') }}</p>
                                </div>
                                @php($statusLabels = ['pending' => 'Menunggu konfirmasi', 'confirmed' => 'Dikonfirmasi', 'processing' => 'Diproses', 'delivered' => 'Selesai', 'cancelled' => 'Dibatalkan'])
                                <span class="rounded-full bg-[#edf2ff] px-3 py-1 text-[9px] font-bold text-[#31569e]">{{ $statusLabels[$order->status] ?? $order->status }}</span>
                            </div>
                            <div class="mt-3 space-y-2">
                                @foreach ($order->items as $item)
                                    <div class="flex justify-between gap-3 text-[11px]">
                                        <span class="text-slate-600">{{ $item->product->name }} × {{ $item->quantity }}</span>
                                        <span class="shrink-0 font-semibold text-[#202a3b]">Rp{{ number_format($item->price_snapshot * $item->quantity, 0, ',', '.') }}</span>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-4 flex flex-wrap items-end justify-between gap-3 border-t border-[#edf0f7] pt-3">
                                <div class="max-w-lg">
                                    <p class="text-[9px] font-bold uppercase tracking-[0.08em] text-slate-500">Alamat pengantaran</p>
                                    <p class="mt-1 text-[10px] leading-5 text-slate-600">{{ $order->address->label ?: 'Alamat pengantaran' }} — {{ $order->address->full_address }}</p>
                                    @php($paymentLabels = ['unpaid' => 'Belum dibayar', 'pending_verification' => 'Menunggu verifikasi', 'rejected' => 'Bukti ditolak', 'paid' => 'Lunas'])
                                    <p class="mt-1 text-[10px] text-slate-600">Pembayaran: {{ $order->payment_method === 'cod' ? 'COD — Bayar saat diterima' : 'Transfer bank' }} · {{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}</p>
                                    @if ($order->notes)
                                        <p class="mt-2 text-[10px] leading-5 text-slate-600"><span class="font-semibold">Catatan:</span> {{ $order->notes }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="text-right">
                                        <p class="text-[9px] text-slate-500">Ongkir Rp{{ number_format($order->delivery_fee, 0, ',', '.') }}</p>
                                        <p class="text-xs font-extrabold text-[#09296d]">Total Rp{{ number_format($order->total_price, 0, ',', '.') }}</p>
                                    </div>
                                    <a href="{{ route('user.orders.show', $order) }}" class="text-[10px] font-bold text-[#31569e] hover:text-[#09296d] hover:underline" aria-label="Lihat detail pesanan #{{ $order->id }}">Detail pesanan →</a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </section>

                <div class="mt-5">{{ $orders->links() }}</div>
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
