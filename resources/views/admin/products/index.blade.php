@extends('layouts.admin')

@section('title', 'Kelola Produk')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#4162a5]">Katalog toko</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">Kelola Produk</h1>
            <p class="mt-1 text-sm text-slate-500">Atur informasi, harga, stok, dan ketersediaan produk.</p>
        </div>
        <a href="{{ route('admin.products.create') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#12377f] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#09296d]">+ Tambah produk</a>
    </div>

    <form method="GET" action="{{ route('admin.products.index') }}" class="mb-5 grid gap-3 rounded-xl border border-[#e6ebf5] bg-white p-4 shadow-sm sm:grid-cols-[minmax(0,1fr)_200px_180px_auto]">
        <div>
            <label for="product-search" class="sr-only">Cari nama atau kategori produk</label>
            <input id="product-search" type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama atau kategori produk..." class="w-full rounded-lg border-[#dce4f5] text-xs focus:border-[#31569e] focus:ring-[#31569e]">
        </div>
        <div>
            <label for="product-category" class="sr-only">Kategori</label>
            <select id="product-category" name="category" class="w-full rounded-lg border-[#dce4f5] text-xs focus:border-[#31569e] focus:ring-[#31569e]">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="product-status" class="sr-only">Status produk</label>
            <select id="product-status" name="status" class="w-full rounded-lg border-[#dce4f5] text-xs focus:border-[#31569e] focus:ring-[#31569e]">
                <option value="">Semua status</option>
                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button class="min-h-9 rounded-lg bg-[#12377f] px-4 text-xs font-bold text-white hover:bg-[#09296d]">Filter</button>
            @if (request()->hasAny(['search', 'category', 'status']))
                <a href="{{ route('admin.products.index') }}" class="inline-flex min-h-9 items-center rounded-lg border border-[#dce4f5] px-3 text-xs font-semibold text-slate-600 hover:bg-slate-50">Reset</a>
            @endif
        </div>
    </form>

    @if ($products->isEmpty())
        <section class="rounded-2xl border border-[#e8ecf6] bg-white px-5 py-12 text-center shadow-sm">
            <h2 class="text-base font-bold text-[#202a3b]">Produk tidak ditemukan</h2>
            <p class="mt-2 text-sm text-slate-500">Coba ubah filter atau tambahkan produk baru ke katalog.</p>
            <a href="{{ route('admin.products.create') }}" class="mt-5 inline-flex min-h-10 items-center justify-center rounded-lg bg-[#12377f] px-4 py-2 text-xs font-bold text-white hover:bg-[#09296d]">Tambah produk</a>
        </section>
    @else
        <section class="overflow-hidden rounded-xl border border-[#e6ebf5] bg-white shadow-sm" aria-label="Daftar produk">
            <div class="flex items-center justify-between px-4 py-3 sm:px-5">
                <p class="text-xs font-semibold text-slate-600">{{ $products->total() }} produk</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[880px] text-left">
                    <thead class="bg-[#eef3ff] text-[9px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3">Produk</th>
                            <th scope="col" class="px-4 py-3">Kategori</th>
                            <th scope="col" class="px-4 py-3 text-right">Harga</th>
                            <th scope="col" class="px-4 py-3 text-right">Stok</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                            <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#edf0f7]">
                        @foreach ($products as $product)
                            <tr class="text-xs hover:bg-[#fbfcff]">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <x-product-image :product="$product" class="h-12 w-14 shrink-0 rounded-lg" />
                                        <div class="min-w-0">
                                            <p class="truncate font-bold text-[#202a3b]">{{ $product->name }}</p>
                                            <p class="mt-1 text-[10px] text-slate-500">/{{ $product->slug }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $product->category->name }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-[#202a3b]">Rp{{ number_format($product->price, 0, ',', '.') }} / {{ $product->unit }}</td>
                                <td class="px-4 py-3 text-right font-semibold {{ $product->stock === 0 ? 'text-rose-700' : ($product->stock < 10 ? 'text-amber-700' : 'text-slate-700') }}">{{ $product->stock }}</td>
                                <td class="px-4 py-3">
                                    @if (! $product->is_active)
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[9px] font-bold text-slate-600">Nonaktif</span>
                                    @elseif ($product->stock === 0)
                                        <span class="rounded-full bg-rose-50 px-2.5 py-1 text-[9px] font-bold text-rose-700">Stok habis</span>
                                    @else
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[9px] font-bold text-emerald-700">Aktif</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.products.edit', $product) }}" class="rounded-md border border-[#dce4f5] px-2.5 py-1.5 text-[10px] font-bold text-[#31569e] hover:bg-[#edf2ff]">Edit</a>
                                        <form method="POST" action="{{ route('admin.products.toggle-active', $product) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="rounded-md border px-2.5 py-1.5 text-[10px] font-bold {{ $product->is_active ? 'border-amber-200 text-amber-800 hover:bg-amber-50' : 'border-emerald-200 text-emerald-800 hover:bg-emerald-50' }}">{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                        </form>
                                        @if ($product->order_items_count === 0)
                                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Hapus produk {{ addslashes($product->name) }} secara permanen?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="rounded-md border border-rose-200 px-2.5 py-1.5 text-[10px] font-bold text-rose-700 hover:bg-rose-50">Hapus</button>
                                            </form>
                                        @else
                                            <span class="text-[9px] text-slate-400" title="Produk memiliki riwayat pesanan">Ada riwayat</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-[#edf0f7] px-4 py-3 sm:px-5">{{ $products->links() }}</div>
        </section>
    @endif
@endsection
