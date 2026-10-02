@section('title', 'Daftar | Padmatirta Wisesa Depo')

<x-guest-layout>
    <div class="mt-6 md:mt-0">
        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#4162a5]">Mulai belanja</p>
        <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-[#172033] sm:text-3xl">Buat akun baru</h1>
        <p class="mt-2 text-xs leading-5 text-slate-500">Daftar untuk checkout lebih mudah dan pantau pesanan Anda.</p>
    </div>

    @if ($errors->any())
        <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-800" role="alert">
            <p class="font-semibold">Periksa kembali data yang Anda masukkan.</p>
            <x-input-error :messages="$errors->all()" class="mt-1" />
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-3.5">
        @csrf

        <div>
            <label for="name" class="mb-1.5 block text-xs font-semibold text-slate-700">Nama lengkap</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name" class="block w-full rounded-xl border-[#e0e6f3] px-3.5 py-2.5 text-sm text-slate-800 shadow-sm placeholder:text-slate-400 focus:border-[#31569e] focus:ring-[#31569e]" placeholder="Nama Anda">
            <x-input-error :messages="$errors->get('name')" class="mt-1.5 text-xs text-rose-600" />
        </div>

        <div>
            <label for="email" class="mb-1.5 block text-xs font-semibold text-slate-700">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" class="block w-full rounded-xl border-[#e0e6f3] px-3.5 py-2.5 text-sm text-slate-800 shadow-sm placeholder:text-slate-400 focus:border-[#31569e] focus:ring-[#31569e]" placeholder="nama@email.com">
            <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs text-rose-600" />
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-xs font-semibold text-slate-700">Kata sandi</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" class="block w-full rounded-xl border-[#e0e6f3] px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-[#31569e] focus:ring-[#31569e]">
            <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-xs text-rose-600" />
        </div>

        <div>
            <label for="password_confirmation" class="mb-1.5 block text-xs font-semibold text-slate-700">Konfirmasi kata sandi</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="block w-full rounded-xl border-[#e0e6f3] px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-[#31569e] focus:ring-[#31569e]">
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5 text-xs text-rose-600" />
        </div>

        <button type="submit" class="mt-2 inline-flex min-h-10 w-full items-center justify-center rounded-xl bg-[#12377f] px-5 text-xs font-bold text-white shadow-sm transition hover:bg-[#09296d] focus:outline-none focus:ring-2 focus:ring-[#31569e] focus:ring-offset-2">Buat akun</button>
    </form>

    <p class="mt-5 border-t border-[#edf0f7] pt-4 text-center text-xs text-slate-600">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="font-bold text-[#31569e] hover:text-[#09296d] hover:underline">Masuk</a>
    </p>
</x-guest-layout>
