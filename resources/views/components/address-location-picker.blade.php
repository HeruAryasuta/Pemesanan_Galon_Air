@props(['id', 'latitude' => null, 'longitude' => null])

<div data-address-picker class="space-y-2.5">
    <div>
        <p class="text-[10px] font-semibold text-slate-700">Titik lokasi pada peta <span class="font-normal text-slate-500">(opsional)</span></p>
        <p class="mt-1 text-[9px] leading-4 text-slate-500">Geser peta lalu ketuk titik alamat. Marker juga dapat digeser.</p>
    </div>
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
    <p class="text-[9px] leading-4 text-slate-500">Peta memakai OpenStreetMap publik. Penyedia peta menerima permintaan ubin berdasarkan area yang sedang dilihat.</p>
</div>
