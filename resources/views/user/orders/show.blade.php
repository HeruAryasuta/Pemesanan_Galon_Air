@extends('layouts.user')

@section('title', 'Detail Pesanan #' . $order->id . ' | Padmatirta Wisesa Depo')

@section('content')
    @php($cartCount = array_sum(session('cart', [])))
    @php($orderStatuses = [
        'pending' => ['label' => 'Menunggu konfirmasi', 'description' => 'Pesanan Anda sudah diterima dan menunggu konfirmasi dari toko.'],
        'confirmed' => ['label' => 'Pesanan dikonfirmasi', 'description' => 'Pesanan Anda sudah dikonfirmasi dan sedang disiapkan.'],
        'processing' => ['label' => 'Dalam pengantaran', 'description' => 'Pesanan Anda sedang dalam proses pengantaran.'],
        'delivered' => ['label' => 'Pesanan selesai', 'description' => 'Pesanan telah selesai diantarkan. Terima kasih telah berbelanja.'],
        'cancelled' => ['label' => 'Pesanan dibatalkan', 'description' => 'Pesanan ini telah dibatalkan. Hubungi kami jika Anda membutuhkan bantuan.'],
    ])
    @php($currentOrderStatus = $orderStatuses[$order->status] ?? ['label' => $order->status, 'description' => 'Status terbaru untuk pesanan Anda.'])
    @php($statusBadgeClass = $order->status === 'cancelled' ? 'bg-rose-50 text-rose-700' : ($order->status === 'delivered' ? 'bg-emerald-50 text-emerald-700' : 'bg-[#edf2ff] text-[#31569e]'))
    @php($progressStatuses = ['pending', 'confirmed', 'processing', 'delivered'])
    @php($currentProgressIndex = array_search($order->status, $progressStatuses, true))
    <div class="flex min-h-screen flex-col bg-[#f8f9ff]">
        <header class="bg-[#f8f9ff]">
            <nav class="mx-auto flex min-h-[53px] max-w-7xl flex-wrap items-center justify-between gap-x-4 px-3 py-1 sm:px-5 lg:flex-nowrap lg:px-8 lg:py-0" aria-label="Navigasi utama">
                <a href="{{ route('user.home') }}" class="order-1 shrink-0 text-[13px] font-extrabold tracking-tight text-[#09296d] sm:text-sm">Padmatirta Wisesa Depo</a>
                <div class="order-3 flex w-full justify-start overflow-x-auto sm:justify-center lg:order-2 lg:w-auto">
                    <x-user-navigation />
                </div>
                <div class="order-2 ml-auto flex shrink-0 items-center gap-1.5 lg:order-3">
                    <a href="{{ route('user.cart.index') }}" class="inline-flex items-center gap-1 rounded-md bg-[#edf2ff] px-2 py-1.5 text-[9px] font-bold text-[#09296d] transition hover:bg-[#dfe8ff] sm:px-2.5" aria-label="Keranjang, {{ $cartCount }} item">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h8.6a2 2 0 0 0 1.95-1.55L21 8H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="19" r="1.4" fill="currentColor"/><circle cx="18" cy="19" r="1.4" fill="currentColor"/></svg>
                        Keranjang @if ($cartCount > 0)<span class="rounded-full bg-white px-1.5 py-0.5 text-[10px]">{{ $cartCount }}</span>@endif
                    </a>
                    <a href="{{ route('profile.edit') }}" class="rounded-md bg-[#09296d] px-2.5 py-1.5 text-[9px] font-bold text-white transition hover:bg-[#123a8e]">Akun</a>
                </div>
            </nav>
        </header>

        <main class="mx-auto w-full max-w-5xl flex-1 px-4 py-6 sm:px-6 sm:py-9 lg:px-8">
            <div class="mb-5">
                <a href="{{ route('user.orders.index') }}" class="text-[10px] font-bold text-[#31569e] hover:text-[#09296d]">← Kembali ke pesanan saya</a>
                @if (session('success'))
                    <p class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800" role="status">{{ session('success') }}</p>
                @elseif (session('error') || $errors->any())
                    <p class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-semibold text-rose-800" role="alert">{{ session('error') ?? $errors->first() }}</p>
                @endif
                <div class="mt-4 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#4162a5]">Pelacakan pesanan</p>
                        <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-[#172033] sm:text-3xl">Pesanan #{{ $order->id }}</h1>
                        <p class="mt-1 text-xs text-slate-500">Dibuat {{ ($order->ordered_at ?? $order->created_at)->timezone(config('app.display_timezone'))->format('d M Y, H:i') }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1.5 text-[10px] font-bold {{ $statusBadgeClass }}">{{ $currentOrderStatus['label'] }}</span>
                </div>
            </div>

            <section class="rounded-2xl border border-[#e8ecf6] bg-white p-4 shadow-[0_5px_18px_rgba(29,48,99,0.05)] sm:p-6" aria-labelledby="tracking-title">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#edf2ff] text-[#31569e]" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M12 21s7-5.2 7-12a7 7 0 1 0-14 0c0 6.8 7 12 7 12Z" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="9" r="2.5" stroke="currentColor" stroke-width="1.6"/></svg>
                    </span>
                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-[0.1em] text-[#4162a5]">Status saat ini</p>
                        <h2 id="tracking-title" class="mt-0.5 text-sm font-bold text-[#202a3b]">{{ $currentOrderStatus['label'] }}</h2>
                        <p class="mt-1 text-[11px] leading-5 text-slate-600">{{ $currentOrderStatus['description'] }}</p>
                    </div>
                </div>

                @if ($order->status === 'cancelled')
                    <div class="mt-5 rounded-xl border border-rose-100 bg-rose-50 p-3 text-[11px] leading-5 text-rose-800">
                        @if ($order->delivery?->status === 'failed')
                            Pengantaran ditandai gagal. Silakan hubungi layanan pelanggan untuk informasi lebih lanjut.
                        @else
                            Status proses pesanan ini telah dihentikan.
                        @endif
                    </div>
                @else
                    <ol class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4" aria-label="Tahapan pesanan">
                        @foreach ($progressStatuses as $index => $status)
                            @php($stepLabels = ['pending' => 'Pesanan diterima', 'confirmed' => 'Dikonfirmasi', 'processing' => 'Dalam pengantaran', 'delivered' => 'Selesai'])
                            @php($isComplete = $currentProgressIndex !== false && $index <= $currentProgressIndex)
                            <li class="relative">
                                <div class="flex items-center">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[10px] font-bold {{ $isComplete ? 'bg-[#12377f] text-white' : 'bg-slate-100 text-slate-400' }}">
                                        @if ($isComplete && $index < $currentProgressIndex)
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="m3 8 3.2 3.2L13 4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        @else
                                            {{ $index + 1 }}
                                        @endif
                                    </span>
                                    @if ($index < count($progressStatuses) - 1)
                                        <span class="ml-2 hidden h-0.5 flex-1 sm:block {{ $currentProgressIndex !== false && $index < $currentProgressIndex ? 'bg-[#31569e]' : 'bg-slate-100' }}" aria-hidden="true"></span>
                                    @endif
                                </div>
                                <p class="mt-2 text-[10px] font-semibold {{ $status === $order->status ? 'text-[#12377f]' : 'text-slate-500' }}">{{ $stepLabels[$status] }}</p>
                                @if ($status === $order->status)
                                    <p class="mt-0.5 text-[9px] text-slate-500">Status saat ini</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif

                @if ($order->delivery?->courier)
                    <div class="mt-5 flex flex-wrap items-start justify-between gap-3 rounded-xl border border-[#edf0f7] bg-[#fbfcff] p-3">
                        <div>
                            <p class="text-[9px] font-bold uppercase tracking-[0.1em] text-[#4162a5]">Kurir pengantaran</p>
                            <p class="mt-1 text-xs font-bold text-[#202a3b]">{{ $order->delivery->courier->name }}</p>
                            <p class="mt-0.5 text-[10px] text-slate-600">{{ $order->delivery->courier->vehicle_type }}</p>
                        </div>
                        @if ($order->delivery->courier->phone)
                            <a href="tel:{{ $order->delivery->courier->phone }}" class="text-[10px] font-bold text-[#31569e] hover:underline">{{ $order->delivery->courier->phone }}</a>
                        @endif
                    </div>
                @elseif ($order->status !== 'delivered' && $order->status !== 'cancelled')
                    <p class="mt-5 rounded-xl bg-[#f4f7ff] px-3 py-2.5 text-[10px] leading-5 text-slate-600">Informasi kurir akan tampil di sini setelah kurir ditugaskan.</p>
                @endif

                @if ($order->delivery?->routeStop?->eta && $order->delivery->status !== 'delivered')
                    <p class="mt-3 text-[10px] text-slate-600">Perkiraan tiba: <span class="font-bold text-[#202a3b]">{{ $order->delivery->routeStop->eta->timezone(config('app.display_timezone'))->format('d M Y, H:i') }}</span></p>
                @endif
                @if ($order->delivery?->delivered_at)
                    <p class="mt-3 text-[10px] text-slate-600">Selesai diantar: <span class="font-bold text-[#202a3b]">{{ $order->delivery->delivered_at->timezone(config('app.display_timezone'))->format('d M Y, H:i') }}</span></p>
                @endif
            </section>

            <div class="mt-4 grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_300px]">
                <section class="rounded-2xl border border-[#e8ecf6] bg-white p-4 shadow-[0_4px_14px_rgba(29,48,99,0.04)] sm:p-5" aria-labelledby="order-items-title">
                    <h2 id="order-items-title" class="text-sm font-bold text-[#202a3b]">Rincian pesanan</h2>
                    <div class="mt-3 divide-y divide-[#edf0f7]">
                        @foreach ($order->items as $item)
                            <div class="flex items-start justify-between gap-3 py-3 first:pt-0 last:pb-0">
                                <div>
                                    <p class="text-xs font-semibold text-[#202a3b]">{{ $item->product->name }}</p>
                                    <p class="mt-1 text-[10px] text-slate-500">{{ $item->quantity }} × Rp{{ number_format($item->price_snapshot, 0, ',', '.') }}</p>
                                </div>
                                <p class="shrink-0 text-xs font-bold text-[#09296d]">Rp{{ number_format($item->quantity * $item->price_snapshot, 0, ',', '.') }}</p>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4 space-y-3 border-t border-[#edf0f7] pt-4">
                        <div class="flex items-center justify-between gap-3 text-[11px]">
                            <span class="font-semibold text-slate-600">Subtotal produk</span>
                            <span class="font-semibold text-[#202a3b]">Rp{{ number_format($order->items->sum(fn ($item) => $item->quantity * $item->price_snapshot), 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 text-[11px]">
                            <span class="font-semibold text-slate-600">Biaya pengantaran</span>
                            <span class="font-semibold text-[#202a3b]">Rp{{ number_format($order->delivery_fee, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-3 border-t border-[#edf0f7] pt-4">
                        <span class="text-xs font-bold text-[#202a3b]">Total pesanan</span>
                        <span class="text-base font-extrabold text-[#09296d]">Rp{{ number_format($order->total_price, 0, ',', '.') }}</span>
                    </div>
                </section>

                <aside class="space-y-4">
                    <section class="rounded-2xl border border-[#e8ecf6] bg-white p-4 shadow-[0_4px_14px_rgba(29,48,99,0.04)]" aria-labelledby="delivery-address-title">
                        <h2 id="delivery-address-title" class="text-xs font-bold text-[#202a3b]">Alamat pengantaran</h2>
                        <p class="mt-2 text-[11px] font-semibold text-slate-700">{{ $order->address->label ?: 'Alamat pengantaran' }}</p>
                        <p class="mt-1 text-[10px] leading-5 text-slate-600">{{ $order->address->full_address }}</p>
                    </section>

                    <section class="rounded-2xl border border-[#e8ecf6] bg-white p-4 shadow-[0_4px_14px_rgba(29,48,99,0.04)]" aria-labelledby="payment-method-title">
                        <h2 id="payment-method-title" class="text-xs font-bold text-[#202a3b]">Metode pembayaran</h2>
                        <p class="mt-2 text-[11px] font-semibold text-slate-700">{{ $order->payment_method === 'cod' ? 'COD — Bayar saat diterima' : 'Transfer bank' }}</p>
                        @php($paymentStatusLabels = ['unpaid' => 'Belum dibayar', 'pending_verification' => 'Menunggu verifikasi admin', 'rejected' => 'Bukti ditolak — unggah ulang bukti', 'paid' => 'Pembayaran terverifikasi'])
                        <p class="mt-1 text-[10px] font-semibold {{ $order->payment_status === 'paid' ? 'text-emerald-700' : ($order->payment_status === 'rejected' ? 'text-rose-700' : 'text-amber-700') }}">{{ $paymentStatusLabels[$order->payment_status] ?? $order->payment_status }}</p>
                        @if ($order->payment_method === 'bank_transfer')
                            @php($bankTransfer = config('payments.bank_transfer'))
                            @if (filled($bankTransfer['bank_name']) && filled($bankTransfer['account_name']) && filled($bankTransfer['account_number']))
                                <div class="mt-3 rounded-lg bg-slate-50 p-3 text-[10px] leading-5 text-slate-600">
                                    <p class="font-bold text-[#202a3b]">Rekening tujuan</p>
                                    <p>{{ $bankTransfer['bank_name'] }} · {{ $bankTransfer['account_number'] }}</p>
                                    <p>a.n. {{ $bankTransfer['account_name'] }}</p>
                                </div>
                            @endif
                            @if ($order->payment_review_note)
                                <p class="mt-3 rounded-lg {{ $order->payment_status === 'rejected' ? 'bg-rose-50 text-rose-800' : 'bg-emerald-50 text-emerald-800' }} p-3 text-[10px] leading-5"><span class="font-bold">Catatan admin:</span> {{ $order->payment_review_note }}</p>
                            @endif
                            @if ($order->payment_status === 'pending_verification')
                                <p class="mt-3 text-[10px] leading-5 text-slate-500">Bukti dikirim {{ $order->payment_submitted_at?->timezone(config('app.display_timezone'))->format('d M Y, H:i') }}. Admin sedang meninjau pembayaran.</p>
                            @elseif (in_array($order->payment_status, ['unpaid', 'rejected'], true) && ! in_array($order->status, ['cancelled', 'delivered'], true))
                                <form method="POST" action="{{ route('user.orders.payment-proof', $order) }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                                    @csrf
                                    <div>
                                        <label for="payment-proof" class="mb-1.5 block text-[10px] font-semibold text-slate-700">Unggah bukti transfer (JPG, PNG, WebP, PDF; maks. 5 MB)</label>
                                        <input id="payment-proof" name="payment_proof" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" required class="block w-full rounded-lg border border-[#dce4f5] bg-white text-[10px] text-slate-600 file:mr-3 file:border-0 file:bg-[#edf2ff] file:px-3 file:py-2 file:text-[10px] file:font-bold file:text-[#31569e]">
                                        @error('payment_proof')<p class="mt-1 text-[10px] text-rose-700">{{ $message }}</p>@enderror
                                    </div>
                                    <button class="min-h-9 w-full rounded-lg bg-[#12377f] px-4 py-2 text-[10px] font-bold text-white hover:bg-[#09296d]">{{ $order->payment_status === 'rejected' ? 'Unggah ulang bukti' : 'Kirim bukti transfer' }}</button>
                                </form>
                            @endif
                        @endif
                    </section>

                    @if ($order->notes)
                        <section class="rounded-2xl border border-[#e8ecf6] bg-white p-4 shadow-[0_4px_14px_rgba(29,48,99,0.04)]" aria-labelledby="order-notes-title">
                            <h2 id="order-notes-title" class="text-xs font-bold text-[#202a3b]">Catatan pesanan</h2>
                            <p class="mt-2 whitespace-pre-line text-[10px] leading-5 text-slate-600">{{ $order->notes }}</p>
                        </section>
                    @endif
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
