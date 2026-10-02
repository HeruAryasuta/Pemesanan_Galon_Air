@extends('layouts.user')

@section('title', 'Akun Saya | Padmatirta Wisesa Depo')

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
                    <a href="{{ route('user.cart.index') }}" class="inline-flex items-center gap-1 rounded-md bg-[#edf2ff] px-2 py-1.5 text-[9px] font-bold text-[#09296d] transition hover:bg-[#dfe8ff] sm:px-2.5" aria-label="Keranjang, {{ $cartCount }} item">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h8.6a2 2 0 0 0 1.95-1.55L21 8H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="19" r="1.4" fill="currentColor"/><circle cx="18" cy="19" r="1.4" fill="currentColor"/></svg>
                        Keranjang @if ($cartCount > 0)<span class="rounded-full bg-white px-1.5 py-0.5 text-[10px]">{{ $cartCount }}</span>@endif
                    </a>
                    <a href="{{ route('user.orders.index') }}" class="rounded-md bg-[#09296d] px-2.5 py-1.5 text-[9px] font-bold text-white transition hover:bg-[#123a8e]">Pesanan</a>
                </div>
            </nav>
        </header>

        <main class="mx-auto w-full max-w-5xl flex-1 px-4 py-6 sm:px-6 sm:py-9 lg:px-8">
            <div class="mb-5 flex flex-wrap items-center gap-4 rounded-2xl border border-[#e8ecf6] bg-white p-4 shadow-[0_4px_14px_rgba(29,48,99,0.04)] sm:p-5">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#edf2ff] text-base font-extrabold uppercase text-[#12377f]" aria-hidden="true">{{ mb_substr($user->name, 0, 1) }}</div>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#4162a5]">Pengaturan akun</p>
                    <h1 class="mt-0.5 truncate text-xl font-extrabold tracking-tight text-[#172033] sm:text-2xl">{{ $user->name }}</h1>
                    <p class="mt-0.5 truncate text-xs text-slate-500">{{ $user->email }}</p>
                </div>
                @if ($user->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="inline-flex min-h-9 items-center justify-center rounded-lg bg-[#12377f] px-3 py-2 text-[10px] font-bold text-white transition hover:bg-[#09296d]">Buka Panel Admin</a>
                @endif
                <a href="{{ route('user.addresses.index') }}" class="inline-flex min-h-9 items-center justify-center rounded-lg border border-[#dce4f5] px-3 py-2 text-[10px] font-bold text-[#31569e] transition hover:bg-[#edf2ff]">Kelola alamat</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex min-h-9 items-center justify-center rounded-lg border border-[#f1d6d6] px-3 py-2 text-[10px] font-bold text-rose-700 transition hover:bg-rose-50">Keluar</button>
                </form>
            </div>

            @if (session('status') === 'profile-updated' || session('status') === 'password-updated')
                <p class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800" role="status">
                    {{ session('status') === 'profile-updated' ? 'Informasi akun berhasil diperbarui.' : 'Kata sandi berhasil diperbarui.' }}
                </p>
            @endif

            <div class="grid items-start gap-4 lg:grid-cols-2">
                <section class="rounded-2xl border border-[#e8ecf6] bg-white p-4 shadow-[0_4px_14px_rgba(29,48,99,0.04)] sm:p-5" aria-labelledby="profile-information-title">
                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-[0.1em] text-[#4162a5]">Informasi pribadi</p>
                        <h2 id="profile-information-title" class="mt-1 text-sm font-bold text-[#202a3b]">Profil Anda</h2>
                        <p class="mt-1 text-[10px] text-slate-500">Perbarui nama dan alamat email akun.</p>
                    </div>

                    <form method="POST" action="{{ route('profile.update') }}" class="mt-4 space-y-3">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label for="name" class="mb-1 block text-[10px] font-semibold text-slate-700">Nama lengkap</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="name" class="w-full rounded-lg border-[#e0e6f3] px-3 py-2 text-xs text-slate-700 focus:border-[#31569e] focus:ring-[#31569e]">
                            <x-input-error :messages="$errors->get('name')" class="mt-1 text-[10px] text-rose-600" />
                        </div>
                        <div>
                            <label for="email" class="mb-1 block text-[10px] font-semibold text-slate-700">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username" class="w-full rounded-lg border-[#e0e6f3] px-3 py-2 text-xs text-slate-700 focus:border-[#31569e] focus:ring-[#31569e]">
                            <x-input-error :messages="$errors->get('email')" class="mt-1 text-[10px] text-rose-600" />
                        </div>
                        <button type="submit" class="inline-flex min-h-9 items-center justify-center rounded-lg bg-[#12377f] px-4 py-2 text-[10px] font-bold text-white transition hover:bg-[#09296d] focus:outline-none focus:ring-2 focus:ring-[#31569e] focus:ring-offset-2">Simpan perubahan</button>
                    </form>
                </section>

                <section class="rounded-2xl border border-[#e8ecf6] bg-white p-4 shadow-[0_4px_14px_rgba(29,48,99,0.04)] sm:p-5" aria-labelledby="password-title">
                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-[0.1em] text-[#4162a5]">Keamanan</p>
                        <h2 id="password-title" class="mt-1 text-sm font-bold text-[#202a3b]">Ubah kata sandi</h2>
                        <p class="mt-1 text-[10px] text-slate-500">Gunakan kata sandi yang kuat dan tidak mudah ditebak.</p>
                    </div>

                    <form method="POST" action="{{ route('password.update') }}" class="mt-4 space-y-3">
                        @csrf
                        @method('PUT')
                        <div>
                            <label for="update_password_current_password" class="mb-1 block text-[10px] font-semibold text-slate-700">Kata sandi saat ini</label>
                            <input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" class="w-full rounded-lg border-[#e0e6f3] px-3 py-2 text-xs text-slate-700 focus:border-[#31569e] focus:ring-[#31569e]">
                            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1 text-[10px] text-rose-600" />
                        </div>
                        <div>
                            <label for="update_password_password" class="mb-1 block text-[10px] font-semibold text-slate-700">Kata sandi baru</label>
                            <input id="update_password_password" name="password" type="password" autocomplete="new-password" class="w-full rounded-lg border-[#e0e6f3] px-3 py-2 text-xs text-slate-700 focus:border-[#31569e] focus:ring-[#31569e]">
                            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1 text-[10px] text-rose-600" />
                        </div>
                        <div>
                            <label for="update_password_password_confirmation" class="mb-1 block text-[10px] font-semibold text-slate-700">Konfirmasi kata sandi baru</label>
                            <input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="w-full rounded-lg border-[#e0e6f3] px-3 py-2 text-xs text-slate-700 focus:border-[#31569e] focus:ring-[#31569e]">
                        </div>
                        <button type="submit" class="inline-flex min-h-9 items-center justify-center rounded-lg border border-[#dce4f5] px-4 py-2 text-[10px] font-bold text-[#31569e] transition hover:bg-[#edf2ff] focus:outline-none focus:ring-2 focus:ring-[#31569e] focus:ring-offset-2">Perbarui kata sandi</button>
                    </form>
                </section>
            </div>

            <section class="mt-4 rounded-2xl border border-[#e8ecf6] bg-white p-4 shadow-[0_4px_14px_rgba(29,48,99,0.04)] sm:p-5" aria-labelledby="account-links-title">
                <p class="text-[9px] font-bold uppercase tracking-[0.1em] text-[#4162a5]">Akses cepat</p>
                <h2 id="account-links-title" class="mt-1 text-sm font-bold text-[#202a3b]">Kelola kebutuhan akun Anda</h2>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    <a href="{{ route('user.addresses.index') }}" class="flex items-center justify-between gap-3 rounded-xl border border-[#edf0f7] p-3 transition hover:border-[#cbd7f2] hover:bg-[#fbfcff]">
                        <span>
                            <span class="block text-xs font-bold text-[#202a3b]">Alamat pengantaran</span>
                            <span class="mt-1 block text-[10px] text-slate-500">Tambah dan atur alamat tujuan pesanan.</span>
                        </span>
                        <span class="text-[#31569e]" aria-hidden="true">→</span>
                    </a>
                    <a href="{{ route('user.orders.index') }}" class="flex items-center justify-between gap-3 rounded-xl border border-[#edf0f7] p-3 transition hover:border-[#cbd7f2] hover:bg-[#fbfcff]">
                        <span>
                            <span class="block text-xs font-bold text-[#202a3b]">Riwayat pesanan</span>
                            <span class="mt-1 block text-[10px] text-slate-500">Lihat pesanan dan status pengantaran.</span>
                        </span>
                        <span class="text-[#31569e]" aria-hidden="true">→</span>
                    </a>
                </div>
            </section>

            <details class="mt-4 rounded-2xl border border-rose-200 bg-white p-4 shadow-[0_4px_14px_rgba(29,48,99,0.03)] sm:p-5" @if ($errors->userDeletion->isNotEmpty()) open @endif>
                <summary class="cursor-pointer text-xs font-bold text-rose-700">Hapus akun</summary>
                <p class="mt-2 max-w-2xl text-[10px] leading-5 text-slate-600">Menghapus akun akan mengakhiri sesi Anda dan tidak dapat dibatalkan. Masukkan kata sandi untuk mengonfirmasi.</p>
                <form method="POST" action="{{ route('profile.destroy') }}" class="mt-3 flex flex-wrap items-end gap-3">
                    @csrf
                    @method('DELETE')
                    <div class="w-full max-w-xs">
                        <label for="delete-account-password" class="mb-1 block text-[10px] font-semibold text-slate-700">Kata sandi</label>
                        <input id="delete-account-password" name="password" type="password" required autocomplete="current-password" class="w-full rounded-lg border-[#f3cbd0] px-3 py-2 text-xs focus:border-rose-400 focus:ring-rose-400">
                        <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-1 text-[10px] text-rose-600" />
                    </div>
                    <button type="submit" class="inline-flex min-h-9 items-center justify-center rounded-lg bg-rose-700 px-4 py-2 text-[10px] font-bold text-white transition hover:bg-rose-800 focus:outline-none focus:ring-2 focus:ring-rose-400 focus:ring-offset-2">Hapus akun permanen</button>
                </form>
            </details>
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
