@extends('layouts.courier')

@section('title', 'Riwayat Pengantaran')

@section('content')
    <div class="mb-6">
        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#4162a5]">Portal kurir</p>
        <h1 class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">Riwayat Pengantaran</h1>
        <p class="mt-1 text-sm text-slate-500">Daftar tugas yang sudah berhasil diantarkan.</p>
    </div>

    @if ($deliveries->isEmpty())
        <section class="rounded-2xl border border-[#e8ecf6] bg-white px-5 py-12 text-center shadow-sm">
            <h2 class="text-base font-bold text-[#202a3b]">Belum ada pengantaran selesai</h2>
            <p class="mt-2 text-sm text-slate-500">Pengantaran yang telah diselesaikan akan tercatat di sini.</p>
        </section>
    @else
        <section class="overflow-hidden rounded-xl border border-[#e6ebf5] bg-white shadow-sm" aria-label="Riwayat pengantaran">
            @foreach ($deliveries as $delivery)
                <article class="flex flex-wrap items-center justify-between gap-4 border-b border-[#edf0f7] px-4 py-4 last:border-b-0 sm:px-5">
                    <div>
                        <p class="text-xs font-bold text-[#202a3b]">Pesanan #{{ $delivery->order->id }} · {{ $delivery->order->user->name }}</p>
                        <p class="mt-1 text-[10px] text-slate-500">{{ $delivery->delivered_at?->timezone(config('app.display_timezone'))->format('d M Y, H:i') }} · {{ $delivery->order->address?->full_address ?? 'Alamat tidak tersedia' }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-extrabold text-[#09296d]">Rp{{ number_format($delivery->order->total_price, 0, ',', '.') }}</span>
                        <a href="{{ route('courier.deliveries.show', $delivery) }}" class="text-[10px] font-bold text-[#31569e] hover:underline">Detail</a>
                    </div>
                </article>
            @endforeach
        </section>
        @if ($deliveries->hasPages())
            <div class="mt-5">{{ $deliveries->links() }}</div>
        @endif
    @endif
@endsection
