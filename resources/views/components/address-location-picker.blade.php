@props(['id', 'latitude' => null, 'longitude' => null])

<div data-address-picker class="space-y-2.5">
    <input type="hidden" data-reverse-geocode-url value="{{ route('user.addresses.reverse-geocode') }}">
    <div>
        <p class="text-[10px] font-semibold text-slate-700">Titik lokasi pada peta <span class="font-normal text-slate-500">(opsional)</span></p>
        <p class="mt-1 text-[9px] leading-4 text-slate-500">Geser peta lalu ketuk titik alamat. Marker juga dapat digeser.</p>
        <button
            type="button"
            data-use-current-location
            class="mt-2 inline-flex min-h-9 items-center justify-center gap-2 rounded-lg border border-[#cdd8f0] bg-[#f4f7ff] px-3 py-2 text-[10px] font-bold text-[#12377f] transition hover:bg-[#e8efff] disabled:cursor-wait disabled:opacity-60"
        >
            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="6" stroke="currentColor" stroke-width="1.5"/><circle cx="10" cy="10" r="2" fill="currentColor"/><path d="M10 1.5v2m0 13v2m8.5-8.5h-2m-13 0h-2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            Gunakan lokasi saya
        </button>
        <p data-location-status class="mt-1 text-[9px] leading-4 text-slate-500" role="status" aria-live="polite"></p>
    </div>
    <p data-address-lookup-status class="text-[9px] leading-4 text-slate-500" role="status" aria-live="polite"></p>
    <div
        id="address-map-{{ $id }}"
        data-address-map
        class="z-0 h-56 w-full overflow-hidden rounded-xl border border-[#dce4f5] bg-[#eaf0ed] sm:h-64"
        role="application"
        aria-label="Peta untuk memilih titik alamat"
    ></div>
    <div class="grid grid-cols-2 gap-2">
        <div>
            <label for="latitude-{{ $id }}" class="mb-1 block text-[9px] font-semibold text-slate-600">Latitude</label>
            <input
                id="latitude-{{ $id }}"
                name="latitude"
                type="number"
                step="any"
                min="-90"
                max="90"
                value="{{ $latitude }}"
                placeholder="-7.3180000"
                class="w-full rounded-lg border-[#e0e6f3] px-2.5 py-2 text-[10px] focus:border-[#31569e] focus:ring-[#31569e]"
            >
        </div>
        <div>
            <label for="longitude-{{ $id }}" class="mb-1 block text-[9px] font-semibold text-slate-600">Longitude</label>
            <input
                id="longitude-{{ $id }}"
                name="longitude"
                type="number"
                step="any"
                min="-180"
                max="180"
                value="{{ $longitude }}"
                placeholder="112.7680000"
                class="w-full rounded-lg border-[#e0e6f3] px-2.5 py-2 text-[10px] focus:border-[#31569e] focus:ring-[#31569e]"
            >
        </div>
    </div>
    <p class="text-[9px] leading-4 text-slate-500">Alamat dikenali otomatis melalui Nominatim OpenStreetMap. Permintaan peta dan koordinat akan diproses oleh layanan publik OpenStreetMap.</p>
</div>
