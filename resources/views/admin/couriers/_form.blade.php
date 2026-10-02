<div class="max-w-3xl space-y-4">
    <section class="space-y-4 rounded-xl border border-[#e6ebf5] bg-white p-4 shadow-sm sm:p-5">
        <div>
            <label for="courier-name" class="mb-1.5 block text-xs font-semibold text-slate-700">Nama kurir</label>
            <input id="courier-name" name="name" type="text" value="{{ old('name', $courier?->name) }}" maxlength="255" required autocomplete="name" class="w-full rounded-lg border-[#dce4f5] text-sm focus:border-[#31569e] focus:ring-[#31569e]" placeholder="Nama lengkap kurir">
            @error('name')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="courier-phone" class="mb-1.5 block text-xs font-semibold text-slate-700">Nomor telepon</label>
            <input id="courier-phone" name="phone" type="tel" value="{{ old('phone', $courier?->phone) }}" maxlength="20" required autocomplete="tel" class="w-full rounded-lg border-[#dce4f5] text-sm focus:border-[#31569e] focus:ring-[#31569e]" placeholder="Contoh: 081234567890">
            @error('phone')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="courier-vehicle" class="mb-1.5 block text-xs font-semibold text-slate-700">Jenis kendaraan <span class="font-normal text-slate-500">(opsional)</span></label>
            <input id="courier-vehicle" name="vehicle_type" type="text" value="{{ old('vehicle_type', $courier?->vehicle_type) }}" maxlength="100" class="w-full rounded-lg border-[#dce4f5] text-sm focus:border-[#31569e] focus:ring-[#31569e]" placeholder="Contoh: Sepeda motor">
            @error('vehicle_type')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
        </div>
        <div class="border-t border-[#edf0f7] pt-4">
            <h2 class="text-sm font-bold text-[#202a3b]">Akun login kurir</h2>
            <p class="mt-1 text-[10px] text-slate-500">Kurir menggunakan email dan kata sandi ini untuk membuka portal tugas pengantaran.</p>
        </div>
        <div>
            <label for="courier-email" class="mb-1.5 block text-xs font-semibold text-slate-700">Email login</label>
            <input id="courier-email" name="email" type="email" value="{{ old('email', $courier?->user?->email) }}" maxlength="255" required autocomplete="email" class="w-full rounded-lg border-[#dce4f5] text-sm focus:border-[#31569e] focus:ring-[#31569e]" placeholder="kurir@email.com">
            @error('email')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="courier-password" class="mb-1.5 block text-xs font-semibold text-slate-700">Kata sandi {{ $courier?->user_id ? '(opsional, untuk mengganti)' : '' }}</label>
            <input id="courier-password" name="password" type="password" minlength="8" @required(! $courier?->user_id) autocomplete="new-password" class="w-full rounded-lg border-[#dce4f5] text-sm focus:border-[#31569e] focus:ring-[#31569e]" placeholder="{{ $courier?->user_id ? 'Biarkan kosong jika tidak diubah' : 'Minimal 8 karakter' }}">
            @error('password')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="courier-password-confirmation" class="mb-1.5 block text-xs font-semibold text-slate-700">Konfirmasi kata sandi</label>
            <input id="courier-password-confirmation" name="password_confirmation" type="password" minlength="8" @required(! $courier?->user_id) autocomplete="new-password" class="w-full rounded-lg border-[#dce4f5] text-sm focus:border-[#31569e] focus:ring-[#31569e]">
        </div>
    </section>

    <section class="rounded-xl border border-[#e6ebf5] bg-white p-4 shadow-sm sm:p-5">
        <input type="hidden" name="is_available" value="0">
        <label class="flex cursor-pointer items-start gap-3">
            <input type="checkbox" name="is_available" value="1" @checked(old('is_available', $courier?->is_available ?? true)) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-[#12377f] focus:ring-[#31569e]">
            <span>
                <span class="block text-xs font-bold text-[#202a3b]">Kurir tersedia</span>
                <span class="mt-1 block text-[10px] leading-4 text-slate-500">Kurir tersedia dapat dipilih untuk menerima tugas pengantaran.</span>
            </span>
        </label>
        @error('is_available')<p class="mt-2 text-xs text-rose-700">{{ $message }}</p>@enderror
    </section>

    <div class="flex flex-col gap-2 sm:flex-row">
        <button class="min-h-10 rounded-lg bg-[#12377f] px-4 py-2 text-xs font-bold text-white hover:bg-[#09296d]">{{ $submitLabel }}</button>
        <a href="{{ route('admin.couriers.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#dce4f5] px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">Batal</a>
    </div>
</div>
