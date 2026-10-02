@props(['product'])

<article class="group flex h-full flex-col overflow-hidden rounded-2xl border border-[#edf0f7] bg-white shadow-[0_4px_13px_rgba(29,48,99,0.05)] transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
    <div class="relative">
        <a href="{{ route('user.products.show', $product) }}" class="block focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[#31569e]" aria-label="Lihat detail {{ $product->name }}">
            <x-product-image :product="$product" class="transition duration-300 group-hover:brightness-[0.98]" />
        </a>
        <span class="absolute right-2 top-2 inline-flex items-center gap-1 rounded-full bg-white/90 px-2 py-1 text-[8px] font-bold text-[#12377f] shadow-sm">
            <span class="h-1.5 w-1.5 rounded-full bg-[#12377f]"></span>
            @if ($product->stock < 1)
                Stok Habis
            @elseif ($product->stock >= 20)
                Stok Banyak
            @else
                Tersedia
            @endif
        </span>
    </div>

    <div class="flex flex-1 flex-col px-2.5 pb-2.5 pt-2">
        <p class="truncate text-[8px] font-semibold uppercase tracking-[0.08em] text-slate-500">{{ $product->category->name }}</p>
        <h3 class="mt-0.5 line-clamp-2 min-h-7 text-[9px] font-semibold leading-3.5 text-[#202a3b]">
            <a href="{{ route('user.products.show', $product) }}" class="rounded-sm hover:text-[#12377f] focus:outline-none focus:ring-2 focus:ring-[#31569e]">{{ $product->name }}</a>
        </h3>
        <div class="mt-1.5 flex items-center justify-between gap-2">
            <p class="text-sm font-bold tracking-tight text-[#09296d] sm:text-base">Rp{{ number_format($product->price, 0, ',', '.') }}</p>
            @if ($product->stock > 0)
                <form method="POST" action="{{ route('user.cart.store') }}">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#12377f] text-white transition hover:bg-[#09296d] focus:outline-none focus:ring-2 focus:ring-[#31569e] focus:ring-offset-1" aria-label="Tambah {{ $product->name }} ke keranjang">
                        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 4v12m-6-6h12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </button>
                </form>
            @else
                <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-400" aria-label="Stok habis"><svg class="h-3 w-3" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m6 6 8 8m0-8-8 8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span>
            @endif
        </div>
    </div>
</article>