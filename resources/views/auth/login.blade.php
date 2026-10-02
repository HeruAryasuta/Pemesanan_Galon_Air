@section('title', 'Masuk | Padmatirta Wisesa Depo')

<x-guest-layout>
    <div class="mt-6 md:mt-0">
        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#4162a5]">Akun pelanggan</p>
        <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-[#172033] sm:text-3xl">Selamat datang kembali</h1>
        <p class="mt-2 text-xs leading-5 text-slate-500">Masuk untuk melanjutkan checkout dan melihat pesanan Anda.</p>
    </div>

    <x-auth-session-status class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800" :status="session('status')" />

    @if ($errors->any())
        <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-800" role="alert">
            <p class="font-semibold">Tidak dapat masuk. Periksa kembali email dan kata sandi Anda.</p>
            <x-input-error :messages="$errors->all()" class="mt-1" />
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label for="email" class="mb-1.5 block text-xs font-semibold text-slate-700">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="block w-full rounded-xl border-[#e0e6f3] px-3.5 py-2.5 text-sm text-slate-800 shadow-sm placeholder:text-slate-400 focus:border-[#31569e] focus:ring-[#31569e]" placeholder="nama@email.com">
            <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs text-rose-600" />
        </div>

        <div>
            <div class="mb-1.5 flex items-center justify-between gap-2">
                <label for="password" class="text-xs font-semibold text-slate-700">Kata sandi</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-[10px] font-semibold text-[#31569e] hover:text-[#09296d] hover:underline">Lupa kata sandi?</a>
                @endif
            </div>
            <input id="password" name="password" type="password" required autocomplete="current-password" class="block w-full rounded-xl border-[#e0e6f3] px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-[#31569e] focus:ring-[#31569e]">
            <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-xs text-rose-600" />
        </div>

        <div class="flex items-center justify-between gap-3 pt-1">
            <label for="remember" class="inline-flex cursor-pointer items-center gap-2 text-[11px] text-slate-600">
                <input id="remember" type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-[#12377f] focus:ring-[#31569e]">
                Ingat saya
            </label>
            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-[#12377f] px-5 text-xs font-bold text-white shadow-sm transition hover:bg-[#09296d] focus:outline-none focus:ring-2 focus:ring-[#31569e] focus:ring-offset-2">Masuk</button>
        </div>
    </form>

    <p class="mt-6 border-t border-[#edf0f7] pt-5 text-center text-xs text-slate-600">
        Belum punya akun?
        <a href="{{ route('register') }}" class="font-bold text-[#31569e] hover:text-[#09296d] hover:underline">Daftar sekarang</a>
    </p>
</x-guest-layout>
