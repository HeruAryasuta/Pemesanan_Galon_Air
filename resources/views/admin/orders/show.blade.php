@extends('layouts.admin')

@section('title', 'Pesanan #' . $order->id)

@section('content')
    @php($statusLabels = ['pending' => 'Menunggu konfirmasi', 'confirmed' => 'Dikonfirmasi', 'processing' => 'Dalam pengantaran', 'delivered' => 'Selesai', 'cancelled' => 'Dibatalkan'])
    <a href="{{ route('admin.orders.index') }}" class="text-xs font-bold text-[#31569e] hover:underline">← Kembali ke pesanan</a>
    <div class="mb-6 mt-4 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#4162a5]">Kelola pesanan</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">Pesanan #{{ $order->id }}</h1>
            <p class="mt-1 text-sm text-slate-500">Dibuat {{ ($order->ordered_at ?? $order->created_at)->timezone(config('app.display_timezone'))->format('d M Y, H:i') }}</p>
        </div>
        <span class="rounded-full bg-[#edf2ff] px-3 py-1.5 text-xs font-bold text-[#31569e]">{{ $statusLabels[$order->status] ?? $order->status }}</span>
    </div>

    <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_340px]">
        <div class="space-y-5">
            <section class="rounded-2xl border border-[#e8ecf6] bg-white p-4 shadow-sm sm:p-5">
                <h2 class="text-base font-bold">Rincian produk</h2>
                <div class="mt-4 divide-y divide-[#edf0f7]">
                    @foreach ($order->items as $item)
                        <div class="flex items-start justify-between gap-4 py-3 first:pt-0">
                            <div>
                                <p class="text-sm font-semibold">{{ $item->product->name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $item->quantity }} × Rp{{ number_format($item->price_snapshot, 0, ',', '.') }}</p>
                            </div>
                            <p class="shrink-0 text-sm font-bold">Rp{{ number_format($item->quantity * $item->price_snapshot, 0, ',', '.') }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 space-y-2 border-t border-[#edf0f7] pt-3 text-xs">
                    <div class="flex justify-between"><span class="text-slate-600">Subtotal produk</span><span>Rp{{ number_format($order->items->sum(fn ($item) => $item->quantity * $item->price_snapshot), 0, ',', '.') }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-600">Biaya pengantaran</span><span>Rp{{ number_format($order->delivery_fee, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between border-t border-[#edf0f7] pt-3 text-sm font-extrabold text-[#09296d]"><span>Total pesanan</span><span>Rp{{ number_format($order->total_price, 0, ',', '.') }}</span></div>
                </div>
            </section>

            <section class="rounded-2xl border border-[#e8ecf6] bg-white p-4 shadow-sm sm:p-5">
                <h2 class="text-base font-bold">Pelanggan & pengantaran</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Pelanggan</p>
                        <p class="mt-1 text-sm font-semibold">{{ $order->user->name }}</p>
                        <p class="text-xs text-slate-500">{{ $order->user->email }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Alamat</p>
                        <p class="mt-1 text-xs leading-5 text-slate-700">{{ $order->address->label ?: 'Alamat pengantaran' }} — {{ $order->address->full_address }}</p>
                    </div>
                </div>
                @if ($order->notes)
                    <div class="mt-4 border-t border-[#edf0f7] pt-3">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Catatan pelanggan</p>
                        <p class="mt-1 whitespace-pre-line text-xs leading-5 text-slate-700">{{ $order->notes }}</p>
                    </div>
                @endif
                <div class="mt-4 border-t border-[#edf0f7] pt-3">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Kurir</p>
                    @if ($order->delivery?->courier)
                        <p class="mt-1 text-sm font-semibold">{{ $order->delivery->courier->name }}</p>
                        <p class="text-xs text-slate-500">{{ $order->delivery->courier->phone }} · {{ $order->delivery->courier->vehicle_type }}</p>
                    @else
                        <p class="mt-1 text-xs text-slate-500">Kurir belum ditugaskan.</p>
                    @endif
                </div>
            </section>
        </div>

        <aside class="space-y-5">
            <section class="rounded-2xl border border-[#e8ecf6] bg-white p-4 shadow-sm sm:p-5">
                <h2 class="text-sm font-bold">Perbarui status pesanan</h2>
                <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="mt-4 space-y-3">
                    @csrf
                    @method('PUT')
                    <label for="order-status" class="block text-xs font-semibold text-slate-600">Status</label>
                    <select id="order-status" name="status" required class="w-full rounded-lg border-[#dce4f5] text-sm focus:border-[#31569e] focus:ring-[#31569e]">
                        @foreach ($statusLabels as $value => $label)
                            <option value="{{ $value }}" @disabled($order->payment_method === 'bank_transfer' && $order->payment_status !== 'paid' && ! in_array($value, ['pending', 'cancelled'], true)) @selected($order->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if ($order->payment_method === 'bank_transfer' && $order->payment_status !== 'paid')
                        <p class="text-[10px] leading-4 text-amber-700">Pesanan transfer hanya dapat diproses setelah pembayaran terverifikasi.</p>
                    @endif
                    <button class="w-full rounded-lg bg-[#12377f] px-4 py-2.5 text-xs font-bold text-white hover:bg-[#09296d]">Simpan status</button>
                </form>
            </section>

            @if (! in_array($order->status, ['delivered', 'cancelled'], true) && $order->delivery && ($order->payment_method !== 'bank_transfer' || $order->payment_status === 'paid'))
                <section class="rounded-2xl border border-[#e8ecf6] bg-white p-4 shadow-sm sm:p-5">
                    <h2 class="text-sm font-bold">Tugaskan kurir</h2>
                    <form method="POST" action="{{ route('admin.orders.assign-courier', $order) }}" class="mt-4 space-y-3">
                        @csrf
                        <label for="courier-id" class="block text-xs font-semibold text-slate-600">Kurir tersedia</label>
                        <select id="courier-id" name="courier_id" required class="w-full rounded-lg border-[#dce4f5] text-sm focus:border-[#31569e] focus:ring-[#31569e]">
                            <option value="">Pilih kurir</option>
                            @foreach ($couriers as $courier)
                                <option value="{{ $courier->id }}" @selected($order->delivery->courier_id === $courier->id)>{{ $courier->name }}{{ $courier->vehicle_type ? ' · ' . $courier->vehicle_type : '' }}</option>
                            @endforeach
                        </select>
                        @if ($couriers->isEmpty())
                            <p class="text-xs text-amber-700">Belum ada kurir tersedia.</p>
                        @endif
                        <button @disabled($couriers->isEmpty()) class="w-full rounded-lg border border-[#dce4f5] px-4 py-2.5 text-xs font-bold text-[#31569e] hover:bg-[#edf2ff] disabled:cursor-not-allowed disabled:opacity-50">Simpan kurir</button>
                    </form>
                </section>
            @endif

            <section class="rounded-2xl border border-[#e8ecf6] bg-white p-4 shadow-sm sm:p-5">
                <h2 class="text-sm font-bold">Pembayaran</h2>
                @php($paymentStatusLabels = ['unpaid' => 'Belum dibayar', 'pending_verification' => 'Menunggu verifikasi', 'rejected' => 'Ditolak', 'paid' => 'Lunas / terverifikasi'])
                <div class="mt-3 flex items-center justify-between gap-3 text-xs">
                    <span class="text-slate-600">Metode</span>
                    <span class="font-semibold">{{ $order->payment_method === 'cod' ? 'COD' : 'Transfer bank' }}</span>
                </div>
                <div class="mt-2 flex items-center justify-between gap-3 text-xs">
                    <span class="text-slate-600">Status</span>
                    <span class="font-bold {{ $order->payment_status === 'paid' ? 'text-emerald-700' : ($order->payment_status === 'rejected' ? 'text-rose-700' : 'text-amber-700') }}">{{ $paymentStatusLabels[$order->payment_status] ?? $order->payment_status }}</span>
                </div>
                @if ($order->payment_method === 'bank_transfer')
                    @if ($order->payment_proof_path)
                        <a href="{{ route('admin.orders.payment-proof.show', $order) }}" target="_blank" rel="noopener" class="mt-4 inline-flex min-h-9 w-full items-center justify-center rounded-lg border border-[#dce4f5] px-4 py-2 text-xs font-bold text-[#31569e] hover:bg-[#edf2ff]">Lihat bukti transfer</a>
                    @endif
                    @if ($order->payment_submitted_at)
                        <p class="mt-2 text-[10px] text-slate-500">Dikirim {{ $order->payment_submitted_at->timezone(config('app.display_timezone'))->format('d M Y, H:i') }}</p>
                    @endif
                    @if ($order->payment_review_note)
                        <p class="mt-3 rounded-lg {{ $order->payment_status === 'rejected' ? 'bg-rose-50 text-rose-800' : 'bg-emerald-50 text-emerald-800' }} p-3 text-[10px] leading-5"><span class="font-bold">Catatan review:</span> {{ $order->payment_review_note }}</p>
                    @endif
                    @if ($order->payment_status === 'pending_verification' && $order->payment_proof_path)
                        <form method="POST" action="{{ route('admin.orders.review-payment', $order) }}" class="mt-4 space-y-3 border-t border-[#edf0f7] pt-4">
                            @csrf
                            <div>
                                <label for="payment-review-note" class="mb-1.5 block text-[10px] font-semibold text-slate-700">Catatan untuk pelanggan <span class="font-normal text-slate-500">(wajib jika ditolak)</span></label>
                                <textarea id="payment-review-note" name="note" rows="3" maxlength="1000" class="w-full rounded-lg border-[#dce4f5] text-xs focus:border-[#31569e] focus:ring-[#31569e]" placeholder="Contoh: nominal transfer tidak sesuai, silakan unggah bukti yang jelas.">{{ old('note') }}</textarea>
                            </div>
                            @error('decision')<p class="text-[10px] text-rose-700">{{ $message }}</p>@enderror
                            @error('note')<p class="text-[10px] text-rose-700">{{ $message }}</p>@enderror
                            <div class="grid gap-2">
                                <button name="decision" value="approve" class="min-h-9 rounded-lg bg-emerald-700 px-3 py-2 text-[10px] font-bold text-white hover:bg-emerald-800">Terima dan verifikasi pembayaran</button>
                                <button name="decision" value="reject" class="min-h-9 rounded-lg border border-rose-200 px-3 py-2 text-[10px] font-bold text-rose-700 hover:bg-rose-50">Tolak bukti pembayaran</button>
                            </div>
                        </form>
                    @endif
                @endif
                @if ($order->payment_method === 'cod' && $order->payment_status !== 'paid')
                    @if ($order->status === 'delivered')
                        <form method="POST" action="{{ route('admin.orders.mark-paid', $order) }}" class="mt-4">
                            @csrf
                            <button class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-xs font-bold text-white hover:bg-emerald-800">Catat pembayaran diterima</button>
                        </form>
                    @else
                        <p class="mt-3 rounded-lg bg-amber-50 p-3 text-[11px] leading-5 text-amber-800">Pembayaran COD dapat dicatat lunas setelah pesanan selesai diantar.</p>
                    @endif
                @endif
            </section>
        </aside>
    </div>
@endsection
