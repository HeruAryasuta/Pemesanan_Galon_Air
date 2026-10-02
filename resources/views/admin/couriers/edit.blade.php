@extends('layouts.admin')

@section('title', 'Edit Kurir')

@section('content')
    <a href="{{ route('admin.couriers.index') }}" class="text-xs font-bold text-[#31569e] hover:underline">← Kembali ke kurir</a>
    <div class="mb-6 mt-4">
        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#4162a5]">Operasional toko</p>
        <h1 class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">Edit Kurir</h1>
        <p class="mt-1 text-sm text-slate-500">Perbarui informasi {{ $courier->name }}.</p>
    </div>
    @if (session('error'))
        <p class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800" role="alert">{{ session('error') }}</p>
    @endif
    <form method="POST" action="{{ route('admin.couriers.update', $courier) }}">
        @csrf
        @method('PUT')
        @include('admin.couriers._form', ['submitLabel' => 'Simpan perubahan'])
    </form>
@endsection
