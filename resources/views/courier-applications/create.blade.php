@extends('layouts.user')

@section('title', 'Pengajuan Calon Kurir | Padmatirta Wisesa Depo')

@section('content')
    <div class="min-h-screen bg-[#f7f8fc]">
        <header class="border-b border-[#e3e8f3] bg-white">
            <nav class="mx-auto flex min-h-14 max-w-5xl items-center justify-between gap-4 px-4 sm:px-6">
                <a href="{{ route('user.home') }}" class="text-sm font-extrabold tracking-tight text-[#09296d]">Padmatirta Wisesa Depo</a>
                <a href="{{ route('user.home') }}" class="text-xs font-semibold text-[#31569e] hover:underline">Kembali ke website</a>
            </nav>
        </header>

        <main class="mx-auto max-w-2xl px-4 py-8 sm:px-6 sm:py-12">
            <div class="mb-6">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#4162a5]">Bergabung bersama kami</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-[#172033] sm:text-3xl">Pengajuan Calon Kurir</h1>
                <p class="mt-2 text-sm leading-6 text-slate-600">Pastikan Anda memiliki kendaraan untuk pengantaran. Pengajuan akan diperiksa admin dan akun kurir baru aktif setelah disetujui.</p>
            </div>

            @if (session('success'))
                <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800" role="status">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-800" role="alert">
                    <p class="font-bold">Periksa kembali data pengajuan.</p>
                    <ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ route('courier-applications.store') }}" class="space-y-5">
                @csrf
                <section class="rounded-2xl border border-[#e6ebf5] bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-sm font-bold text-[#202a3b]">1. Konfirmasi kendaraan</h2>
                    <p class="mt-1 text-xs text-slate-500">Langkah ini diperlukan sebelum mengisi data diri.</p>
                    <fieldset class="mt-4">
                        <legend class="mb-2 text-xs font-semibold text-slate-700">Apakah Anda memiliki kendaraan untuk mengantar pesanan?</legend>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-[#dce4f5] px-3 py-3 text-xs font-semibold text-slate-700 hover:bg-[#f8faff]">
                                <input type="radio" name="has_vehicle" value="1" @checked(old('has_vehicle') === '1') class="border-slate-300 text-[#12377f] focus:ring-[#31569e]">
                                Ya, saya memiliki kendaraan
                            </label>
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-[#dce4f5] px-3 py-3 text-xs font-semibold text-slate-700 hover:bg-[#f8faff]">
                                <input type="radio" name="has_vehicle" value="0" @checked(old('has_vehicle') === '0') class="border-slate-300 text-[#12377f] focus:ring-[#31569e]">
                                Belum memiliki kendaraan
                            </label>
                        </div>
                        @error('has_vehicle')<p class="mt-2 text-xs text-rose-700">Pengajuan hanya dapat dilanjutkan jika Anda memiliki kendaraan.</p>@enderror
                    </fieldset>
                    <p id="vehicle-ineligible" class="mt-3 hidden rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">Mohon maaf, kendaraan diperlukan untuk menjadi kurir. Formulir pengajuan belum dapat dilanjutkan.</p>
                </section>

                <section id="applicant-details" @if (old('has_vehicle') !== '1') hidden @endif class="space-y-4 rounded-2xl border border-[#e6ebf5] bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-sm font-bold text-[#202a3b]">2. Data calon kurir</h2>
                    <div>
                        <label for="application-name" class="mb-1.5 block text-xs font-semibold text-slate-700">Nama lengkap</label>
                        <input id="application-name" name="name" value="{{ old('name') }}" maxlength="255" required class="w-full rounded-lg border-[#dce4f5] text-sm focus:border-[#31569e] focus:ring-[#31569e]" autocomplete="name">
                        @error('name')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="application-phone" class="mb-1.5 block text-xs font-semibold text-slate-700">Nomor telepon</label>
                        <input id="application-phone" name="phone" type="tel" value="{{ old('phone') }}" maxlength="20" required class="w-full rounded-lg border-[#dce4f5] text-sm focus:border-[#31569e] focus:ring-[#31569e]" autocomplete="tel" placeholder="Contoh: 081234567890">
                        @error('phone')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="application-nik" class="mb-1.5 block text-xs font-semibold text-slate-700">Nomor KTP/NIK</label>
                        <input id="application-nik" name="nik" type="text" value="{{ old('nik') }}" inputmode="numeric" pattern="[0-9]{16}" minlength="16" maxlength="16" required autocomplete="off" class="w-full rounded-lg border-[#dce4f5] text-sm focus:border-[#31569e] focus:ring-[#31569e]" placeholder="Masukkan tepat 16 digit angka" aria-describedby="application-nik-help">
                        <p id="application-nik-help" class="mt-1 text-[10px] text-slate-500">NIK disimpan secara terenkripsi dan harus terdiri dari 16 digit angka.</p>
                        @error('nik')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="application-email" class="mb-1.5 block text-xs font-semibold text-slate-700">Email untuk login</label>
                        <input id="application-email" name="email" type="email" value="{{ old('email') }}" maxlength="255" required class="w-full rounded-lg border-[#dce4f5] text-sm focus:border-[#31569e] focus:ring-[#31569e]" autocomplete="email">
                        @error('email')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="application-vehicle" class="mb-1.5 block text-xs font-semibold text-slate-700">Jenis kendaraan</label>
                        <select id="application-vehicle" name="vehicle_type" required class="w-full rounded-lg border-[#dce4f5] text-sm focus:border-[#31569e] focus:ring-[#31569e]">
                            <option value="">Pilih jenis kendaraan</option>
                            @foreach (config('courier.vehicle_types') as $vehicleType)
                                <option value="{{ $vehicleType }}" @selected(old('vehicle_type') === $vehicleType)>{{ $vehicleType }}</option>
                            @endforeach
                        </select>
                        @error('vehicle_type')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div class="border-t border-[#edf0f7] pt-4">
                        <h3 class="text-sm font-bold text-[#202a3b]">Kata sandi akun</h3>
                        <p class="mt-1 text-xs text-slate-500">Akun dan kata sandi baru dapat digunakan setelah pengajuan disetujui.</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="application-password" class="mb-1.5 block text-xs font-semibold text-slate-700">Kata sandi</label>
                            <input id="application-password" name="password" type="password" minlength="8" required autocomplete="new-password" class="w-full rounded-lg border-[#dce4f5] text-sm focus:border-[#31569e] focus:ring-[#31569e]">
                            @error('password')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="application-password-confirmation" class="mb-1.5 block text-xs font-semibold text-slate-700">Ulangi kata sandi</label>
                            <input id="application-password-confirmation" name="password_confirmation" type="password" minlength="8" required autocomplete="new-password" class="w-full rounded-lg border-[#dce4f5] text-sm focus:border-[#31569e] focus:ring-[#31569e]">
                        </div>
                    </div>
                    <button class="inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-[#12377f] px-5 text-sm font-bold text-white shadow-sm transition hover:bg-[#09296d] focus:outline-none focus:ring-2 focus:ring-[#31569e] focus:ring-offset-2">Kirim pengajuan</button>
                </section>
            </form>
        </main>
    </div>

    <script>
        const vehicleOptions = document.querySelectorAll('input[name="has_vehicle"]');
        const applicantDetails = document.getElementById('applicant-details');
        const ineligibleMessage = document.getElementById('vehicle-ineligible');
        const detailFields = applicantDetails.querySelectorAll('input');

        const setEligibility = (value) => {
            const eligible = value === '1';
            applicantDetails.hidden = !eligible;
            detailFields.forEach((field) => {
                field.disabled = !eligible;
            });
            ineligibleMessage.classList.toggle('hidden', eligible || value !== '0');
        };

        vehicleOptions.forEach((option) => {
            option.addEventListener('change', () => setEligibility(option.checked ? option.value : ''));
        });

        setEligibility(document.querySelector('input[name="has_vehicle"]:checked')?.value ?? '');
    </script>
@endsection
