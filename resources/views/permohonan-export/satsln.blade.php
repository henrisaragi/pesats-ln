{{--
    Preview SATS-LN (dipakai sebagai partial di permohonan-export/show.blade.php kolom kanan).
    Wrapper <html>/<head>/<body> dan <style> @page sengaja dilepas supaya bisa di-@include di
    dalam halaman lain (konsisten dengan semua partial lain di app ini) - CSS print/@page bisa
    dipasang lagi nanti kalau fitur cetak/PDF terpisah dibuat.

    Tampilan (Revisi 4) dirombak jadi kartu modern mengikuti desain umum app ini, TAPI urutan dan
    pengelompokan section (I-XII) tetap persis sama seperti template resmi aslinya - cuma
    tipografi/spacing/border yang berubah, bukan posisi/isi section.

    Field yang SUDAH tersedia dari modal Tambah/Ubah SATS-LN (Revisi Permohonan 3) di-bind ke
    data asli: nomor SATS-LN, data eksportir (entitas pemohon), data importir (customer/pembeli),
    pelabuhan keberangkatan/tujuan, maksud transaksi, berlaku sampai. Daftar spesies, QR code, dan
    tanda tangan TETAP statis/dummy - itu bagian dari task terpisah (kuota/qty/spesies) yang belum
    dikerjakan.
--}}
<div class="p-5 flex flex-col gap-5 text-default-700">
    {{-- Disclaimer atas --}}
    <div class="rounded-lg border border-warning/30 bg-warning/10 px-4 py-3 text-xs text-warning">
        <p class="font-medium">
            Dokumen ini hanya sah jika data yang dicetak pada kertas standar dari Kementerian Kehutanan sesuai
            dengan data ini.
        </p>
        <p class="mt-0.5 italic text-warning/80">
            This document is only valid if the data printed on the standard paper from the Ministry of Forestry
            matches the data presented herein.
        </p>
    </div>

    {{-- Header: judul permit + nomor + tipe --}}
    <div class="flex flex-wrap items-start justify-between gap-3 pb-4 border-b border-dashed border-default-200">
        <div>
            <p class="text-xs font-medium text-default-500 uppercase tracking-wide">
                Surat Angkut Tumbuhan dan Satwa Liar <span class="italic normal-case">(Permit)</span>
            </p>
            @if ($satsLn->satsln_number_issued)
                <p class="mt-1 text-lg font-semibold text-default-800">{{ $satsLn->satsln_number_issued }}</p>
            @else
                <p class="mt-1 inline-flex items-center gap-1 rounded-full border border-warning/30 bg-warning/10 px-2.5 py-1 text-xs font-medium text-warning">
                    Nomor akan otomatis di-generate
                    <span class="italic font-normal">(Number will be generated automatically)</span>
                </p>
            @endif
        </div>
        <span
            class="inline-flex items-center gap-1 rounded-full border border-primary/30 bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
            SATS-LN {{ $satsLn->type_satsln }}
        </span>
    </div>

    {{-- Alamat --}}
    {{-- <div class="text-xs text-default-500">
        <p class="font-medium text-default-600">Alamat <span class="italic">(Address)</span></p>
        <p class="mt-1">
            Manggala Wanabakti, Jl. Jend. Gatot Subroto, Jakarta 10270. Telp: 021-5720227, 5720227,
            5704501-04 Ext. 769, Fax (62-21) 5720227, 5734818.
        </p>
        <p class="italic">e-mail: macitesindonesia@gmail.com / subditmj.kkh@gmail.com</p>
    </div> --}}

    {{-- II & III: Eksportir / Importir --}}
    <div class="grid md:grid-cols-2 gap-4">
        <div class="rounded-lg border border-default-200 p-4">
            <p class="text-xs font-semibold text-default-500 uppercase tracking-wide">
                Diberikan kepada <span class="italic normal-case">(Exportir - name, address, country)</span>
            </p>
            <p class="mt-2 text-sm font-medium uppercase text-default-800">
                {{ $permohonan->entitas?->name }}, {{ $permohonan->entitas?->address }}, INDONESIA
            </p>
        </div>
        <div class="rounded-lg border border-default-200 p-4">
            <p class="text-xs font-semibold text-default-500 uppercase tracking-wide">
                Dikirim kepada <span class="italic normal-case">(Importir - name, address, country)</span>
            </p>
            <p class="mt-2 text-sm font-medium uppercase text-default-800">
                {{ $satsLn->customerCompany?->name ?? '-' }}@if ($satsLn->customerCompany)
                    , {{ $satsLn->customerCompany->address }}, {{ $satsLn->customerCompany->country?->name }}
                @endif
            </p>
        </div>
    </div>

    {{-- IV-VII: valid until / pelabuhan tujuan / pelabuhan pemberangkatan / maksud transaksi --}}
    <div class="grid sm:grid-cols-2 gap-4 text-sm">
        <div>
            <p class="text-xs text-default-500">Berlaku sampai dengan <span class="italic">(Valid until)</span></p>
            <p class="mt-1 font-medium text-default-800">{{ $satsLn->valid_until?->translatedFormat('d/m/Y') ?? '-' }}
            </p>
        </div>
        <div>
            <p class="text-xs text-default-500">Pelabuhan Tujuan <span class="italic">(Place Port of
                    Destination)</span></p>
            <p class="mt-1 font-medium text-default-800">{{ $satsLn->portOfDestination?->name ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs text-default-500">Pelabuhan Pemberangkatan <span class="italic">(Port of
                    exportation)</span></p>
            <p class="mt-1 font-medium text-default-800">{{ $satsLn->portOfExportation?->name ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs text-default-500">Maksud Transaksi <span class="italic">(Purpose of
                    transaction)</span></p>
            <p class="mt-1 font-medium text-default-800">{{ $satsLn->purposeOfTransaction?->name ?? '-' }}</p>
        </div>
    </div>

    {{-- VIII: intro + tabel daftar spesies --}}
    <div>
        <p class="text-center text-sm text-default-600">
            Pemegang Sertifikat ini diberi ijin untuk mengekspor satwa dan tumbuhan sebagai berikut
            <span class="block text-xs italic text-default-400">
                (The above mentioned permitee is authorized to export/import the wild animals and plants specified
                herein)
            </span>
        </p>

        <div class="mt-3 overflow-x-auto rounded-lg border border-default-200">
            <table class="min-w-full divide-y divide-default-200 text-sm">
                <thead class="bg-default-100">
                    <tr class="text-xs font-medium text-default-600 whitespace-nowrap">
                        <th class="px-3 py-2 text-center" scope="col">No.</th>
                        <th class="px-3 py-2 text-left" scope="col">
                            Nama Jenis <span class="block font-normal italic text-default-400">Name of species</span>
                        </th>
                        <th class="px-3 py-2 text-left" scope="col">
                            Jumlah <span class="block font-normal italic text-default-400">Quantity</span>
                        </th>
                        <th class="px-3 py-2 text-left" scope="col">
                            Keterangan <span class="block font-normal italic text-default-400">Description</span>
                        </th>
                        <th class="px-3 py-2 text-center" scope="col">
                            Appendiks (Sumber) <span class="block font-normal italic text-default-400">Source</span>
                        </th>
                        <th class="px-3 py-2 text-right" scope="col">
                            Realisasi Kuota <span class="block font-normal italic text-default-400">Total Exported Quota
                                (Year)</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-default-100 text-default-700">
                    @php
                        $itemsAreEditable =
                            $permohonan->status->isEditable() && auth()->user()->can('permohonan-export.edit');
                    @endphp
                    @forelse ($satsLn->items as $item)
                        @php
                            $itemTradeCode = $item->kuotaPerusahaan?->kuotaNasional?->tradeCode;
                            $itemSource = $item->kuotaPerusahaan?->kuotaNasional?->source;
                            $itemRealisasiNasional = $realisasiNasionalRunningTotalByItemId[$item->id] ?? 0.0;
                        @endphp
                        <tr
                            @if ($itemsAreEditable) class="cursor-pointer hover:bg-default-50"
                                data-hs-overlay="#satsln-item-edit-modal-{{ $item->id }}" @endif>
                            <td class="px-3 py-2 text-center text-default-500">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2">
                                {{ $item->species?->scientific_name }}@if ($item->species?->common_name)
                                    , {{ $item->species->common_name }}
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                {{ $itemTradeCode ? $itemTradeCode->formatQuota($item->qty) : number_format((float) $item->qty, 2, ',', '.') }}
                                {{ $itemTradeCode?->unit_name }}
                            </td>
                            <td class="px-3 py-2 text-default-500">{{ $item->description ?? '-' }}</td>
                            <td class="px-3 py-2 text-center">{{ $item->species?->trade_status }}
                                ({{ $itemSource?->code }})
                            </td>
                            <td class="px-3 py-2 text-right">
                                {{ $itemTradeCode ? $itemTradeCode->formatQuota($itemRealisasiNasional) : number_format($itemRealisasiNasional, 2, ',', '.') }}
                                /
                                {{ $itemTradeCode ? $itemTradeCode->formatQuota($item->kuotaNasional?->quota ?? 0) : number_format((float) ($item->kuotaNasional?->quota ?? 0), 2, ',', '.') }}
                                ({{ $item->kuotaNasional?->year }})
                            </td>
                        </tr>
                        @empty
                            <tr>
                                <td class="px-3 py-2 text-center text-default-400" colspan="6">
                                    {{ __('Belum ada spesies. Tambahkan lewat tombol "Tambah Spesies" di navbar.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($satsLn->items->isNotEmpty())
                @php
                    $totalTradeCode = $satsLn->items->first()?->kuotaNasional?->tradeCode;
                    $totalQty = (float) $satsLn->items->sum('qty');
                @endphp
                <p class="mt-2 text-sm font-medium text-default-700">
                    {{ __('Total') }} {{ $totalTradeCode ? $totalTradeCode->formatQuota($totalQty) : number_format($totalQty, 2, ',', '.') }}
                </p>
                @if ($totalTradeCode)
                    <p class="text-xs text-default-500">
                        {{ $totalTradeCode->quotaToWords($totalQty, app()->getLocale()) }}
                    </p>
                @endif
            @endif
        </div>

        {{-- IX: syarat khusus --}}
        <div class="rounded-lg border border-default-200 bg-default-50 p-4 text-xs text-default-500">
            <p class="font-medium text-default-600">Syarat khusus <span class="italic font-normal">(Special
                    conditions)</span></p>
            <p>Security Stamp No: SN-000001, Captive breed specimen no quota allocated</p>
        </div>



        {{-- Footer disclaimer --}}
        <div class="border-t border-dashed border-default-200 pt-3 text-xs text-default-400">
            <p>
                Dokumen ini hanya sah dan berlaku jika data yang dicetak pada kertas standar dari Kementerian
                Kehutanan sesuai dengan data digital.
            </p>
            <p class="italic">
                This document is only valid if the data printed on the standard paper from the Ministry of Forestry
                matches the data presented herein.
            </p>
        </div>
    </div>
