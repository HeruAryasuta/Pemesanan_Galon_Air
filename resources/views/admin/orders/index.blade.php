@extends('layouts.admin')

@section('title', 'Kelola Pesanan')

@section('content')
    @php($statusLabels = ['pending' => 'Menunggu konfirmasi', 'confirmed' => 'Dikonfirmasi', 'processing' => 'Dalam pengantaran', 'delivered' => 'Selesai', 'cancelled' => 'Dibatalkan'])
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#4162a5]">Operasional toko</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">Kelola Pesanan</h1>
            <p class="mt-1 text-sm text-slate-500">Konfirmasi pesanan, atur pengantaran, dan pantau pembayaran COD maupun transfer.</p>
        </div>
        <form method="GET" action="{{ route('admin.orders.index') }}" class="flex items-center gap-2">
            <label for="order-status-filter" class="text-xs font-semibold text-slate-600">Status</label>
            <select id="order-status-filter" name="status" class="rounded-lg border-[#dce4f5] text-sm focus:border-[#31569e] focus:ring-[#31569e]">
                <option value="">Semua pesanan</option>
                @foreach ($statusLabels as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="rounded-lg bg-[#12377f] px-3 py-2 text-xs font-bold text-white hover:bg-[#09296d]">Filter</button>
        </form>
    </div>

    @if ($orders->isEmpty())
        <section class="rounded-2xl border border-[#e8ecf6] bg-white px-5 py-12 text-center">
            <h2 class="text-base font-bold">Belum ada pesanan</h2>
            <p class="mt-2 text-sm text-slate-500">Pesanan pelanggan akan tampil di sini.</p>
        </section>
    @else
        <section class="overflow-hidden rounded-2xl border border-[#e8ecf6] bg-white shadow-sm" aria-label="Daftar pesanan">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="bg-[#f4f7ff] text-[10px] uppercase tracking-wider text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-bold">Pesanan</th>
                            <th scope="col" class="px-4 py-3 font-bold">Pelanggan</th>
                            <th scope="col" class="px-4 py-3 font-bold">Status pesanan</th>
                            <th scope="col" class="px-4 py-3 font-bold">Pembayaran</th>
                            <th scope="col" class="px-4 py-3 text-right font-bold">Total</th>
                            <th scope="col" class="px-4 py-3 text-right font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#edf0f7]">
                        @foreach ($orders as $order)
                            <tr>
                                <td class="px-4 py-4">
                                    <p class="font-bold text-[#202a3b]">#{{ $order->id }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ ($order->ordered_at ?? $order->created_at)->timezone(config('app.display_timezone'))->format('d M Y, H:i') }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-semibold">{{ $order->user->name }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $order->user->email }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full bg-[#edf2ff] px-2.5 py-1 text-[10px] font-bold text-[#31569e]">{{ $statusLabels[$order->status] ?? $order->status }}</span>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="text-xs font-semibold">{{ $order->payment_method === 'cod' ? 'COD' : $order->payment_method }}</p>
                                    @php($paymentLabels = ['unpaid' => 'Belum dibayar', 'pending_verification' => 'Menunggu verifikasi', 'rejected' => 'Bukti ditolak', 'paid' => 'Lunas'])
                                    <p class="mt-1 text-[10px] {{ $order->payment_status === 'paid' ? 'font-bold text-emerald-700' : ($order->payment_status === 'rejected' ? 'font-bold text-rose-700' : 'text-amber-700') }}">{{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}</p>
                                </td>
                                <td class="px-4 py-4 text-right font-extrabold text-[#09296d]">Rp{{ number_format($order->total_price, 0, ',', '.') }}</td>
                                <td class="px-4 py-4 text-right">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="font-bold text-[#31569e] hover:underline">Kelola →</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        <div class="mt-5">{{ $orders->links() }}</div>
    @endif
@endsection
