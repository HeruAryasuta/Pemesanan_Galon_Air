@extends('layouts.admin')

@section('title', 'Verifikasi Pembayaran')

@section('content')
    <div class="mb-6">
        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#4162a5]">Keuangan</p>
        <h1 class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">Verifikasi Pembayaran</h1>
        <p class="mt-1 text-sm text-slate-500">Tinjau bukti transfer pelanggan, lalu terima atau tolak dengan catatan.</p>
    </div>

    @if ($orders->isEmpty())
        <section class="rounded-2xl border border-[#e8ecf6] bg-white px-5 py-12 text-center shadow-sm">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-lg font-extrabold text-emerald-700" aria-hidden="true">✓</span>
            <h2 class="mt-4 text-base font-bold text-[#202a3b]">Tidak ada bukti menunggu verifikasi</h2>
            <p class="mt-2 text-sm text-slate-500">Bukti transfer baru dari pelanggan akan muncul di sini.</p>
        </section>
    @else
        <section class="overflow-hidden rounded-xl border border-[#e6ebf5] bg-white shadow-sm" aria-label="Pembayaran yang menunggu verifikasi">
            <div class="flex items-center justify-between px-4 py-3 sm:px-5">
                <p class="text-xs font-semibold text-slate-600">{{ $orders->total() }} pembayaran menunggu</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left">
                    <thead class="bg-[#eef3ff] text-[9px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3">Pesanan</th>
                            <th scope="col" class="px-4 py-3">Pelanggan</th>
                            <th scope="col" class="px-4 py-3">Bukti dikirim</th>
                            <th scope="col" class="px-4 py-3 text-right">Total transfer</th>
                            <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#edf0f7]">
                        @foreach ($orders as $order)
                            <tr class="text-xs hover:bg-[#fbfcff]">
                                <td class="px-4 py-4 font-bold text-[#202a3b]">#{{ $order->id }}</td>
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-[#202a3b]">{{ $order->user->name }}</p>
                                    <p class="mt-1 text-[10px] text-slate-500">{{ $order->user->email }}</p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $order->payment_submitted_at?->timezone(config('app.display_timezone'))->format('d M Y, H:i') }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-right font-bold text-[#09296d]">Rp{{ number_format($order->total_price, 0, ',', '.') }}</td>
                                <td class="px-4 py-4 text-right">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="font-bold text-[#31569e] hover:underline">Tinjau bukti →</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())
                <div class="border-t border-[#edf0f7] px-4 py-3 sm:px-5">{{ $orders->links() }}</div>
            @endif
        </section>
    @endif
@endsection
