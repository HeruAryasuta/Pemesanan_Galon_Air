<div class="flex items-center gap-5 whitespace-nowrap text-base font-semibold text-[#454652]">
    <a
        href="{{ route('user.home') }}"
        class="border-b-2 py-3 transition hover:text-[#09296d] {{ request()->routeIs('user.home') ? 'border-[#173c91] text-[#09296d]' : 'border-transparent' }}"
        @if (request()->routeIs('user.home')) aria-current="page" @endif
    >Beranda</a>
    <a
        href="{{ route('user.products.index') }}"
        class="border-b-2 py-3 transition hover:text-[#09296d] {{ request()->routeIs('user.products.*') ? 'border-[#173c91] text-[#09296d]' : 'border-transparent' }}"
        @if (request()->routeIs('user.products.*')) aria-current="page" @endif
    >Produk</a>
    <a
        href="{{ route('user.recommendations.index') }}"
        class="border-b-2 py-3 transition hover:text-[#09296d] {{ request()->routeIs('user.recommendations.*') ? 'border-[#173c91] text-[#09296d]' : 'border-transparent' }}"
        @if (request()->routeIs('user.recommendations.*')) aria-current="page" @endif
    >Rekomendasi</a>
    <a href="{{ route('user.home') }}#cara-pesan" class="border-b-2 border-transparent py-3 transition hover:text-[#09296d]">Cara Pesan</a>
    <a href="{{ route('user.home') }}#tentang-kami" class="border-b-2 border-transparent py-3 transition hover:text-[#09296d]">Tentang Kami</a>
</div>
