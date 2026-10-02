@extends('layouts.admin')

@section('title', 'Detail Pelanggan')

@section('content')
    @php($statusLabels = ['pending' => 'Menunggu konfirmasi', 'confirmed' => 'Dikonfirmasi', 'processing' => 'Dalam pengantaran', 'delivered' => 'Selesai', 'cancelled' => 'Dibatalkan'])
    <a href="{{ route('admin.customers.index') }}" class="text-xs font-bold text-[#31569e] hover:underline">← Kembali ke pelanggan</a>

    <div class="mb-6 mt-4">
        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#4162a5]">Data pelanggan</p>
        <h1 class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">{{ $customer->name }}</h1>
        <p class="mt-1 text-sm text-slate-500">Terdaftar {{ $customer->created_at->timezone(config('app.display_timezone'))->format('d M Y') }}</p>
    </div>

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)]">
        <div class="space-y-5">
            <section class="rounded-xl border border-[#e6ebf5] bg-white p-4 shadow-sm sm:p-5">
                <h2 class="text-sm font-bold text-[#202a3b]">Informasi kontak</h2>
                <dl class="mt-4 space-y-4 text-xs">
                    <div>
                        <dt class="font-semibold text-slate-500">Email</dt>
                        <dd class="mt-1 break-all font-medium text-[#202a3b]">{{ $customer->email }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Nomor telepon</dt>
                        <dd class="mt-1 font-medium text-[#202a3b]">{{ $customer->phone ?: 'Belum diisi' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Jumlah pesanan</dt>
                        <dd class="mt-1 font-medium text-[#202a3b]">{{ $customer->orders_count }} pesanan</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-xl border border-[#e6ebf5] bg-white p-4 shadow-sm sm:p-5">
                <h2 class="text-sm font-bold text-[#202a3b]">Alamat tersimpan</h2>
                @forelse ($customer->addresses as $address)
                    <div class="{{ $loop->first ? 'mt-4' : 'mt-3 border-t border-[#edf0f7] pt-3' }}">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-xs font-bold text-[#202a3b]">{{ $address->label ?: 'Alamat' }}</p>
                            @if ($address->is_primary)
                                <span class="rounded-full bg-[#edf2ff] px-2 py-0.5 text-[9px] font-bold text-[#31569e]">Utama</span>
                            @endif
                        </div>
                        <p class="mt-1 whitespace-pre-line text-xs leading-5 text-slate-600">{{ $address->full_address }}</p>
                    </div>
                @empty
                    <p class="mt-3 text-xs text-slate-500">Pelanggan belum menyimpan alamat.</p>
                @endforelse
            </section>
        </div>

        <section class="overflow-hidden rounded-xl border border-[#e6ebf5] bg-white shadow-sm" aria-label="Riwayat pesanan pelanggan">
            <div class="flex items-center justify-between px-4 py-4 sm:px-5">
                <div>
                    <h2 class="text-sm font-bold text-[#202a3b]">Pesanan terbaru</h2>
                    <p class="mt-1 text-[10px] text-slate-500">Menampilkan maksimal 10 pesanan terbaru.</p>
                </div>
                <span class="rounded-full bg-[#edf2ff] px-2.5 py-1 text-[10px] font-bold text-[#31569e]">{{ $customer->orders_count }} total</span>
            </div>
            @forelse ($customer->orders as $order)
                <article class="border-t border-[#edf0f7] px-4 py-4 sm:px-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold text-[#202a3b]">Pesanan #{{ $order->id }}</p>
                            <p class="mt-1 text-[10px] text-slate-500">{{ ($order->ordered_at ?? $order->created_at)->timezone(config('app.display_timezone'))->format('d M Y, H:i') }}</p>
                        </div>
                        <span class="rounded-full bg-[#edf2ff] px-2.5 py-1 text-[9px] font-bold text-[#31569e]">{{ $statusLabels[$order->status] ?? $order->status }}</span>
                    </div>
                    <div class="mt-3 flex flex-wrap items-end justify-between gap-3">
                        <p class="text-[10px] leading-4 text-slate-500">{{ $order->address?->label ?: 'Alamat pengantaran' }}: {{ $order->address?->full_address ?? 'Alamat tidak tersedia' }}</p>
                        <p class="whitespace-nowrap text-xs font-extrabold text-[#09296d]">Rp{{ number_format($order->total_price, 0, ',', '.') }}</p>
                    </div>
                </article>
            @empty
                <div class="border-t border-[#edf0f7] px-5 py-10 text-center">
                    <p class="text-xs font-semibold text-[#202a3b]">Belum ada pesanan</p>
                    <p class="mt-1 text-[11px] text-slate-500">Riwayat pesanan pelanggan akan muncul di sini.</p>
                </div>
            @endforelse
        </section>
    </div>
@endsection
