@props(['product', 'class' => ''])

<div class="relative flex aspect-[3/2] items-center justify-center overflow-hidden bg-gradient-to-br from-[#f0f3f5] to-[#e6ebec] {{ $class }}">
    @if ($product->image_path)
        <img src="{{ \Illuminate\Support\Str::startsWith($product->image_path, 'images/') ? asset($product->image_path) : \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="h-full w-full object-contain p-3">
    @elseif (\Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower($product->category->name), ['lpg', 'gas']))
        <svg class="h-[78%] w-[62%] text-[#3c8065]" viewBox="0 0 100 130" fill="none" aria-hidden="true">
            <path d="M31 25h38v12l8 9v59a9 9 0 0 1-9 9H32a9 9 0 0 1-9-9V46l8-9V25Z" fill="currentColor"/>
            <path d="M39 25v-8h22v8M35 17V9h30v8" stroke="#245b48" stroke-width="5" stroke-linejoin="round"/>
            <path d="M32 53h36M32 96h36" stroke="#d6e9dd" stroke-opacity=".7" stroke-width="3"/>
            <text x="50" y="76" fill="#eef8f0" font-size="8" font-weight="700" text-anchor="middle">LPG</text>
            <text x="50" y="87" fill="#eef8f0" font-size="6" text-anchor="middle">3 KG</text>
        </svg>
    @elseif (\Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower($product->category->name), ['minuman', 'kopi', 'teh']))
        <svg class="h-[76%] w-[55%] text-[#65728a]" viewBox="0 0 100 130" fill="none" aria-hidden="true">
            <path d="M32 25h36v87H32z" rx="8" fill="currentColor"/>
            <path d="M40 16h20v12H40z" rx="3" fill="#3c4960"/>
            <path d="M42 13h16v5H42z" rx="2" fill="#263348"/>
            <path d="M38 54h24v35H38z" rx="4" fill="#eff4f9"/>
            <path d="M42 64h16M42 72h12M42 80h14" stroke="#75849b" stroke-width="3" stroke-linecap="round"/>
        </svg>
    @else
        <svg class="h-[78%] w-[60%] text-[#58a8d2]" viewBox="0 0 100 130" fill="none" aria-hidden="true">
            <path d="M32 31h36l-4 81H36l-4-81Z" rx="13" fill="currentColor"/>
            <path d="M40 21h20v12H40z" rx="3" fill="#187db4"/>
            <path d="M42 13h16v10H42z" rx="3" fill="#145e9a"/>
            <path d="M36 56h28" stroke="#d9f3ff" stroke-opacity=".8" stroke-width="4"/>
            <path d="M39 43c6-4 16-4 22 0" stroke="white" stroke-opacity=".8" stroke-width="3" stroke-linecap="round"/>
        </svg>
    @endif
</div>
