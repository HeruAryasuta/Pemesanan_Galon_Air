@extends('layouts.admin')

@section('title', 'Pengajuan Calon Kurir')

@section('content')
    <div class="mb-6">
        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#4162a5]">Rekrutmen</p>
        <h1 class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">Pengajuan Calon Kurir</h1>
        <p class="mt-1 text-sm text-slate-500">Periksa kelayakan kendaraan dan data calon sebelum membuat akun kurir.</p>
    </div>

    @if ($applications->isEmpty())
        <section class="rounded-2xl border border-[#e8ecf6] bg-white px-5 py-12 text-center shadow-sm">
            <h2 class="text-base font-bold text-[#202a3b]">Belum ada pengajuan calon kurir</h2>
            <p class="mt-2 text-sm text-slate-500">Pengajuan yang masuk dari website akan ditampilkan di sini.</p>
        </section>
    @else
        <section class="overflow-hidden rounded-xl border border-[#e6ebf5] bg-white shadow-sm" aria-label="Daftar pengajuan calon kurir">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left">
                    <thead class="bg-[#eef3ff] text-[9px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3">Calon kurir</th>
                            <th scope="col" class="px-4 py-3">Kontak</th>
                            <th scope="col" class="px-4 py-3">NIK</th>
                            <th scope="col" class="px-4 py-3">Kendaraan</th>
                            <th scope="col" class="px-4 py-3">Tanggal pengajuan</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                            <th scope="col" class="px-4 py-3 text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#edf0f7]">
                        @foreach ($applications as $application)
                            <tr class="text-xs">
                                <td class="px-4 py-3 font-bold text-[#202a3b]">{{ $application->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $application->email }}<br>{{ $application->phone }}</td>
                                <td class="px-4 py-3 font-mono text-slate-600">{{ $application->nik }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $application->vehicle_type }}<br><span class="text-[10px] text-emerald-700">Memiliki kendaraan</span></td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $application->created_at->timezone(config('app.display_timezone'))->format('d M Y, H:i') }}</td>
                                <td class="px-4 py-3">
                                    @php($statusClasses = ['pending' => 'bg-amber-50 text-amber-800', 'approved' => 'bg-emerald-50 text-emerald-700', 'rejected' => 'bg-rose-50 text-rose-700'])
                                    @php($statusLabels = ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'])
                                    <span class="rounded-full px-2.5 py-1 text-[9px] font-bold {{ $statusClasses[$application->status] }}">{{ $statusLabels[$application->status] }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($application->status === 'pending')
                                        <div class="flex flex-col items-end gap-2">
                                            <form method="POST" action="{{ route('admin.courier-applications.approve', $application) }}" onsubmit="return confirm('Setujui pengajuan dan buat akun kurir untuk {{ addslashes($application->name) }}?')">
                                                @csrf
                                                <label for="application-zone-{{ $application->id }}" class="sr-only">Pilih wilayah tugas untuk {{ $application->name }}</label>
                                                <select id="application-zone-{{ $application->id }}" name="service_zone_id" required class="mb-2 w-52 rounded-md border-[#dce4f5] text-[10px]">
                                                    <option value="">Pilih wilayah tugas</option>
                                                    @foreach ($serviceZones as $zone)
                                                        <option value="{{ $zone->id }}" @disabled($zone->couriers_count >= $zone->slot_count)>{{ $zone->name }} · {{ $zone->area_description }} ({{ $zone->couriers_count }}/{{ $zone->slot_count }})</option>
                                                    @endforeach
                                                </select>
                                                <button class="rounded-md bg-[#12377f] px-3 py-2 text-[10px] font-bold text-white hover:bg-[#09296d]">Setujui</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.courier-applications.reject', $application) }}" onsubmit="return confirm('Tolak pengajuan {{ addslashes($application->name) }}?')">
                                                @csrf
                                                <button class="rounded-md border border-rose-200 px-3 py-2 text-[10px] font-bold text-rose-700 hover:bg-rose-50">Tolak</button>
                                            </form>
                                        </div>
                                    @elseif ($application->reviewer)
                                        <span class="text-[10px] text-slate-500">Diproses oleh {{ $application->reviewer->name }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($applications->hasPages())
                <div class="border-t border-[#edf0f7] px-4 py-3 sm:px-5">{{ $applications->links() }}</div>
            @endif
        </section>
    @endif
@endsection
