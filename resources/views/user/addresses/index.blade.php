@extends('layouts.user')

@section('title', 'Alamat Pengantaran | Padmatirta Wisesa Depo')

@section('content')
    @php($cartCount = array_sum(session('cart', [])))
    <div class="flex min-h-screen flex-col bg-[#f8f9ff]">
        <header class="bg-[#f8f9ff]">
            <nav class="mx-auto flex min-h-[53px] max-w-7xl flex-wrap items-center justify-between gap-x-4 px-3 py-1 sm:px-5 lg:flex-nowrap lg:px-8 lg:py-0" aria-label="Navigasi utama">
                <a href="{{ route('user.home') }}" class="order-1 shrink-0 text-[13px] font-extrabold tracking-tight text-[#09296d] sm:text-sm">Padmatirta Wisesa Depo</a>
                <div class="order-3 flex w-full justify-start overflow-x-auto sm:justify-center lg:order-2 lg:w-auto">
                    <x-user-navigation />
                </div>
                <div class="order-2 ml-auto flex shrink-0 items-center gap-1.5 lg:order-3">
                    <a href="{{ route('user.cart.index') }}" class="inline-flex items-center gap-1 rounded-md bg-[#edf2ff] px-2 py-1.5 text-[9px] font-bold text-[#09296d] transition hover:bg-[#dfe8ff]" aria-label="Keranjang, {{ $cartCount }} item">Keranjang @if ($cartCount > 0)<span class="rounded-full bg-white px-1.5 py-0.5 text-[10px]">{{ $cartCount }}</span>@endif</a>
                    <a href="{{ route('profile.edit') }}" class="rounded-md bg-[#09296d] px-2.5 py-1.5 text-[9px] font-bold text-white">Akun</a>
                </div>
            </nav>
        </header>

        <main class="mx-auto w-full max-w-5xl flex-1 px-4 py-6 sm:px-6 sm:py-9 lg:px-8">
            <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <a href="{{ route('profile.edit') }}" class="text-[10px] font-bold text-[#31569e] hover:text-[#09296d]">← Kembali ke akun</a>
                    <p class="mt-4 text-[10px] font-bold uppercase tracking-[0.14em] text-[#4162a5]">Pengantaran</p>
                    <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-[#172033] sm:text-3xl">Alamat Saya</h1>
                    <p class="mt-1 text-xs text-slate-500">Kelola alamat tujuan untuk pesanan Anda.</p>
                </div>
                <a href="{{ route('user.checkout.index') }}" class="text-xs font-bold text-[#31569e] hover:text-[#09296d]">Kembali ke checkout →</a>
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

            <div class="grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
                <section class="space-y-3" aria-label="Alamat tersimpan">
                    @forelse ($addresses as $address)
                        <details class="rounded-2xl border border-[#e8ecf6] bg-white p-4 shadow-[0_4px_14px_rgba(29,48,99,0.04)] sm:p-5" @if ($errors->any() && old('address_id') == $address->id) open @endif>
                            <summary class="flex cursor-pointer list-none items-start justify-between gap-3">
                                <span>
                                    <span class="flex flex-wrap items-center gap-2 text-sm font-bold text-[#202a3b]">{{ $address->label ?: 'Alamat pengantaran' }} @if ($address->is_primary)<span class="rounded-full bg-[#edf2ff] px-2 py-0.5 text-[8px] font-bold text-[#31569e]">Utama</span>@endif</span>
                                    <span class="mt-1 block text-[11px] leading-5 text-slate-600">{{ $address->full_address }}</span>
                                </span>
                                <span class="mt-0.5 shrink-0 text-[10px] font-bold text-[#31569e]">Ubah</span>
                            </summary>

                            <form method="POST" action="{{ route('user.addresses.update', $address) }}" class="mt-4 space-y-3 border-t border-[#edf0f7] pt-4">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="address_id" value="{{ $address->id }}">
                                <div>
                                    <label for="label-{{ $address->id }}" class="mb-1 block text-[10px] font-semibold text-slate-700">Label alamat</label>
                                    <input id="label-{{ $address->id }}" name="label" type="text" value="{{ old('address_id') == $address->id ? old('label') : $address->label }}" maxlength="100" class="w-full rounded-lg border-[#e0e6f3] px-3 py-2 text-xs focus:border-[#31569e] focus:ring-[#31569e]">
                                </div>
                                <div>
                                    <label for="full-address-{{ $address->id }}" class="mb-1 block text-[10px] font-semibold text-slate-700">Alamat lengkap</label>
                                    <textarea id="full-address-{{ $address->id }}" name="full_address" rows="3" maxlength="1000" required class="w-full rounded-lg border-[#e0e6f3] px-3 py-2 text-xs focus:border-[#31569e] focus:ring-[#31569e]">{{ old('address_id') == $address->id ? old('full_address') : $address->full_address }}</textarea>
                                </div>
                                <x-address-location-picker
                                    id="saved-{{ $address->id }}"
                                    :latitude="old('address_id') == $address->id ? old('latitude') : $address->latitude"
                                    :longitude="old('address_id') == $address->id ? old('longitude') : $address->longitude"
                                />
                                <label class="flex cursor-pointer items-center gap-2 text-[10px] text-slate-600">
                                    <input type="hidden" name="is_primary" value="0">
                                    <input type="checkbox" name="is_primary" value="1" @checked(old('address_id') == $address->id ? old('is_primary') : $address->is_primary) class="h-4 w-4 rounded border-slate-300 text-[#12377f] focus:ring-[#31569e]">
                                    Jadikan alamat utama
                                </label>
                                <div class="flex flex-wrap items-center gap-3">
                                    <button type="submit" class="inline-flex min-h-9 items-center justify-center rounded-lg bg-[#12377f] px-4 py-2 text-[10px] font-bold text-white hover:bg-[#09296d]">Simpan perubahan</button>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('user.addresses.destroy', $address) }}" class="mt-2" onsubmit="return confirm('Hapus alamat ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-[10px] font-semibold text-rose-700 hover:underline">Hapus alamat</button>
                            </form>
                        </details>
                    @empty
                        <div class="rounded-2xl border border-[#e8ecf6] bg-white p-5 text-center shadow-[0_4px_14px_rgba(29,48,99,0.04)]">
                            <h2 class="text-sm font-bold text-[#202a3b]">Belum ada alamat tersimpan</h2>
                            <p class="mt-1 text-[11px] text-slate-500">Tambahkan alamat agar lebih mudah saat checkout.</p>
                        </div>
                    @endforelse
                </section>

                <section class="rounded-2xl border border-[#e8ecf6] bg-white p-4 shadow-[0_5px_18px_rgba(29,48,99,0.05)] sm:p-5" aria-labelledby="add-address-title">
                    <p class="text-[9px] font-bold uppercase tracking-[0.1em] text-[#4162a5]">Alamat baru</p>
                    <h2 id="add-address-title" class="mt-1 text-sm font-bold text-[#202a3b]">Tambah alamat</h2>
                    <form method="POST" action="{{ route('user.addresses.store') }}" class="mt-4 space-y-3">
                        @csrf
                        <div>
                            <label for="new-label" class="mb-1 block text-[10px] font-semibold text-slate-700">Label alamat <span class="font-normal">(opsional)</span></label>
                            <input id="new-label" name="label" type="text" value="{{ old('address_id') ? '' : old('label') }}" maxlength="100" placeholder="Contoh: Rumah" class="w-full rounded-lg border-[#e0e6f3] px-3 py-2 text-xs focus:border-[#31569e] focus:ring-[#31569e]">
                        </div>
                        <div>
                            <label for="new-full-address" class="mb-1 block text-[10px] font-semibold text-slate-700">Alamat lengkap</label>
                            <textarea id="new-full-address" name="full_address" rows="4" maxlength="1000" required placeholder="Jalan, nomor rumah, RT/RW, kelurahan, dan patokan" class="w-full rounded-lg border-[#e0e6f3] px-3 py-2 text-xs focus:border-[#31569e] focus:ring-[#31569e]">{{ old('address_id') ? '' : old('full_address') }}</textarea>
                        </div>
                        <x-address-location-picker
                            id="new"
                            :latitude="old('address_id') ? '' : old('latitude')"
                            :longitude="old('address_id') ? '' : old('longitude')"
                        />
                        <label class="flex cursor-pointer items-center gap-2 text-[10px] text-slate-600">
                            <input type="hidden" name="is_primary" value="0">
                            <input type="checkbox" name="is_primary" value="1" @checked(! $addresses->contains('is_primary', true) || (! old('address_id') && old('is_primary'))) class="h-4 w-4 rounded border-slate-300 text-[#12377f] focus:ring-[#31569e]">
                            Jadikan alamat utama
                        </label>
                        <button type="submit" class="inline-flex min-h-10 w-full items-center justify-center rounded-lg bg-[#12377f] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#09296d]">Simpan alamat</button>
                    </form>
                </section>
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
