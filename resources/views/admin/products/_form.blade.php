<div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_300px]">
    <div class="space-y-4">
        <section class="space-y-4 rounded-xl border border-[#e6ebf5] bg-white p-4 shadow-sm sm:p-5">
            <div>
                <label for="product-name" class="mb-1.5 block text-xs font-semibold text-slate-700">Nama produk</label>
                <input id="product-name" name="name" type="text" value="{{ old('name', $product?->name) }}" maxlength="255" required class="w-full rounded-lg border-[#dce4f5] text-sm focus:border-[#31569e] focus:ring-[#31569e]" placeholder="Contoh: Air Galon 19 Liter">
                @error('name')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="product-category" class="mb-1.5 block text-xs font-semibold text-slate-700">Kategori</label>
                <select id="product-category" name="category_id" required class="w-full rounded-lg border-[#dce4f5] text-sm focus:border-[#31569e] focus:ring-[#31569e]">
                    <option value="">Pilih kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('category_id', $product?->category_id) === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                @if ($categories->isEmpty())<p class="mt-2 text-xs text-amber-700">Belum ada kategori. Tambahkan kategori terlebih dahulu sebelum membuat produk.</p>@endif
            </div>
            <div>
                <label for="product-description" class="mb-1.5 block text-xs font-semibold text-slate-700">Deskripsi <span class="font-normal text-slate-500">(opsional)</span></label>
                <textarea id="product-description" name="description" rows="5" class="w-full rounded-lg border-[#dce4f5] text-sm focus:border-[#31569e] focus:ring-[#31569e]" placeholder="Informasi singkat tentang produk">{{ old('description', $product?->description) }}</textarea>
                @error('description')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
            </div>
        </section>

        <section class="rounded-xl border border-[#e6ebf5] bg-white p-4 shadow-sm sm:p-5">
            <h2 class="text-sm font-bold text-[#202a3b]">Foto produk</h2>
            @if ($product?->image_path)
                <div class="mt-3 flex items-center gap-4">
                    <x-product-image :product="$product" class="h-24 w-28 rounded-xl border border-[#edf0f7]" />
                    <p class="text-xs leading-5 text-slate-500">Foto saat ini. Unggah gambar baru jika ingin menggantinya.</p>
                </div>
            @endif
            <label for="product-image" class="mt-4 block text-xs font-semibold text-slate-700">Unggah gambar (JPEG, PNG, WebP; maks. 2 MB)</label>
            <input id="product-image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full rounded-lg border border-[#dce4f5] bg-white text-xs text-slate-600 file:mr-4 file:border-0 file:bg-[#edf2ff] file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-[#31569e]">
            @error('image')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
        </section>
    </div>

    <aside class="space-y-4">
        <section class="space-y-4 rounded-xl border border-[#e6ebf5] bg-white p-4 shadow-sm sm:p-5">
            <h2 class="text-sm font-bold text-[#202a3b]">Harga & stok</h2>
            <div>
                <label for="product-price" class="mb-1.5 block text-xs font-semibold text-slate-700">Harga (Rp)</label>
                <input id="product-price" name="price" type="number" min="0" step="1" value="{{ old('price', $product?->price) }}" required class="w-full rounded-lg border-[#dce4f5] text-sm focus:border-[#31569e] focus:ring-[#31569e]" placeholder="21000">
                @error('price')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="product-stock" class="mb-1.5 block text-xs font-semibold text-slate-700">Stok</label>
                <input id="product-stock" name="stock" type="number" min="0" step="1" value="{{ old('stock', $product?->stock ?? 0) }}" required class="w-full rounded-lg border-[#dce4f5] text-sm focus:border-[#31569e] focus:ring-[#31569e]">
                @error('stock')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="product-unit" class="mb-1.5 block text-xs font-semibold text-slate-700">Satuan</label>
                <input id="product-unit" name="unit" type="text" maxlength="50" value="{{ old('unit', $product?->unit ?? 'pcs') }}" required class="w-full rounded-lg border-[#dce4f5] text-sm focus:border-[#31569e] focus:ring-[#31569e]" placeholder="galon">
                @error('unit')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
            </div>
        </section>

        <section class="rounded-xl border border-[#e6ebf5] bg-white p-4 shadow-sm sm:p-5">
            <label class="flex cursor-pointer items-start gap-3">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product?->is_active ?? true)) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-[#12377f] focus:ring-[#31569e]">
                <span>
                    <span class="block text-xs font-bold text-[#202a3b]">Produk aktif</span>
                    <span class="mt-1 block text-[10px] leading-4 text-slate-500">Produk aktif dapat dilihat dan dipesan pelanggan jika stok tersedia.</span>
                </span>
            </label>
        </section>

        <div class="flex flex-col gap-2">
            <button @disabled($categories->isEmpty()) class="min-h-10 rounded-lg bg-[#12377f] px-4 py-2 text-xs font-bold text-white hover:bg-[#09296d] disabled:cursor-not-allowed disabled:opacity-50">{{ $submitLabel }}</button>
            <a href="{{ route('admin.products.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#dce4f5] px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">Batal</a>
        </div>
    </aside>
</div>
