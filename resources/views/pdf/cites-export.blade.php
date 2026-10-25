<!DOCTYPE html>
<html lang="en">
{{-- Creator: Henri Saragi
FIxing

--}}

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>SATS-LN {{ $nomorsatln }}</title>
    <style>
        @page {
            margin: 70.875pt 21.645pt 28.35pt 19.845pt;
            /* top 2.5cm , right 0.6, bottom 1cm, left */
        }

        body {
            margin: 0px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7pt;
            line-height: normal;
        }

        .no {
            width: 1cm;
            font-size: 7pt;
            font-weight: bold;
            text-align: center
        }

        .title {
            width: 6cm;
            font-size: 7pt;
            font-weight: bold;
        }

        .row {
            width: 100%;
            display: table;
            table-layout: fixed;
        }

        .hg-1 {
            height: 0.7cm;
            vertical-align: middle;
        }

        .col-sp {
            width: 2%;
            display: table-cell;
        }

        .col-no {
            width: 4%;
            display: table-cell;
        }

        .col-1 {
            width: 8.33%;
            display: table-cell;
        }

        .col1set {
            width: 9.1%;
            display: table-cell;
        }

        .col-2 {
            width: 16.66%;
            display: table-cell;
        }

        .col-3 {
            width: 25%;
            display: table-cell;
        }

        .col-4 {
            width: 33.33%;
            display: table-cell;
        }

        .col-5 {
            width: 41.66%;
            display: table-cell;
        }

        .col-6 {
            width: 50%;
            display: table-cell;
        }

        .col-7 {
            width: 58.33%;
            display: table-cell;
        }

        .col-8 {
            width: 66.66%;
            display: table-cell;
        }

        .col-9 {
            width: 75%;
            display: table-cell;
        }

        .col-10 {
            width: 83.33%;
            display: table-cell;
        }

        .col-11 {
            width: 91.66%;
            display: table-cell;
        }

        .col-12 {
            width: 100%;
            display: table-cell;
        }
    </style>
</head>

<body>
    <table style="border-collapse: collapse; margin:auto;  width:100%; border:1px solid red 1px;">

        <!-- bagian alamat -->
        <tr>
            <td class="no" style="vertical-align: middle; height: 0.4cm; border:1px solid #000; border-right:none;">
            </td>
            <td colspan="7" style="border:1px solid #000;border-left:none;font-size:6pt; ">
                <div class="row ">
                    <div class="col-1 hg-1">
                        <b><u>Alamat</u><br>
                            <i>Address</i></b>
                    </div>
                    <div class="col-sp hg-1"><b>:</b></div>
                    <div class="col-6 hg-1">
                        Manggala Wanabakti, Jl.Jend. Gatot Subroto Jakarta, 10270, Telp: 021-5720227, 5720227,
                        5704501-04 Ext.769, Fax (62-21) 5720227, 5734818<br>
                        <i>e-mail: macitesindonesia@gmail.com / subditmj.kkh@gmail.com</i>
                    </div>

                </div>

            </td>
        </tr>

        <!-- bagian 1  diberikan kepada (eksportir) -->
        <tr>
            <td class="no" style="vertical-align: middle; height: 0.4cm;border:1px solid #000; border-right:none;">
                I.</td>
            <td colspan="7" style="border:1px solid #000; border-left:none;">
                <div class="row ">
                    <div class="col-3 hg-1">
                        <b><u>Surat Angkut Tumbuhan dan Satwa Liar</u><br>
                            <i>Permit</i></b>
                    </div>
                    <div class="col-sp hg-1"><b>:</b></div>
                    <div class="col-4 hg-1">
                        <b>No:</b> {{ $nomorsatln }}
                    </div>
                    <div class="col-3 hg-1">
                        <b>SATS-LN:</b> {{ $tipedoc }}
                    </div>
                </div>

            </td>
        </tr>


        <!-- bagian 2 diberikan kepada (eksportir) -->
        <tr>
            <td class="no" style="vertical-align: middle; height: 0.4cm;border:1px solid #000; border-right:none;">
                II.</td>
            <td colspan="7" style="border:1px solid #000; border-left:none;">
                <div class="row ">
                    <div class="col-3 hg-1">
                        <b><u>Diberikan kepada (nama, alamat, negara)</u><br>
                            <i>Exportir (name, address, country)</i></b>
                    </div>
                    <div class="col-sp hg-1"><b>:</b></div>
                    <div class="col-7 hg-1">
                        <span
                            style="text-transform: uppercase; font-size: {{ strlen($exportir) > 150 ? '6pt' : '7pt' }};">{{ $exportir }}</span>
                    </div>
                </div>

            </td>
        </tr>
        <!-- bagian 3 -->
        <tr>
            <td class="no" style="vertical-align: middle; height: 0.4cm;border:1px solid #000; border-right:none;">
                III.</td>
            <td colspan="7" style=" height: 0.4cm;border:1px solid #000; border-left:none;">
                <div class="row">
                    <div class="col-3 hg-1">
                        <b><u>Dikirim kepada (nama, alamat, negara)</u><br>
                            <i>Importir (name, address, country)</i></b>
                    </div>
                    <div class="col-sp hg-1"><b>:</b></div>
                    <div class="col-7 hg-1">
                        <span
                            style="text-transform: uppercase; font-size: {{ strlen($importir) > 150 ? '6pt' : '7pt' }};">{{ $importir }}</span>
                    </div>
                </div>

            </td>

        </tr>
        <!-- bagian 4 dan 5 berlaku dan Pelabuhan Tujuan -->
        @php
            $fz = 0;
            if (
                strlen($date_valid) == 24 ||
                strlen($port_destination) == 24 ||
                strlen($port_departur) == 24 ||
                strlen($purpose) == 24
            ) {
                $fz = 9;
            } else {
                $fz = 8;
            }
        @endphp

        <tr>
            <td style="vertical-align: middle; text-align:center; border:1px solid #000; border-right:none;"><b>IV.</b>
            </td>
            <td colspan="7" style="border:1px solid #000; border-left:none;">
                <div class="row">
                    <div class="col-3 hg-1"><u><b>Berlaku sampai dengan</b></u><br><b><i>Valid until</i></b></div>
                    <div class="col-sp hg-1" style="vertical-align: middle;"><b>:</b></div>
                    <div class="col-3 hg-1" style="vertical-align: middle; font-size: {{ $fz }}pt;">
                        {{ $date_valid }}</div>
                    <div class="col-no hg-1" style="vertical-align: middle;"><b>V.</b></div>
                    <div class="col-3 hg-1"><u><b>Pelabuhan Tujuan</b></u><br><b><i>Place Port of Destination</i></b>
                    </div>
                    <div class="col-sp hg-1" style="vertical-align: middle;"><b>:</b></div>
                    <div class="col-3 hg-1" style="vertical-align: middle; font-size: {{ $fz }}pt;">
                        {{ $port_destination }}</div>
                </div>

            </td>
        </tr>

        <!-- bagian 6 dan 7 berlaku dan Pelabuhan Tujuan -->
        <tr>
            <td style="vertical-align: middle; text-align:center;border:1px solid #000; border-right:none;"><b>VI.</b>
            </td>
            <td colspan="7" style="vertical-align: middle; border:1px solid #000; border-left:none;">
                <div class="row">
                    <div class="col-3 hg-1"><u><b>Pelabuhan Pemberangkatan</b></u><br><b><i>Port of exportation</i></b>
                    </div>
                    <div class="col-sp hg-1" style="vertical-align: middle;"><b>:</b></div>
                    <div class="col-3 hg-1" style="vertical-align: middle; font-size: {{ $fz }}pt;">
                        {{ $port_departur }}</div>
                    <div class="col-no hg-1" style="vertical-align: middle;"><b>VII.</b></div>
                    <div class="col-3 hg-1"><u><b>Maksud Transaksi</b></u><br><b><i>Purpose of transaction</i></b></div>
                    <div class="col-sp hg-1" style="vertical-align: middle;"><b>:</b></div>
                    <div class="col-3 hg-1" style="vertical-align: middle; font-size: {{ $fz }}pt;">
                        {{ $purpose }}</div>
                </div>
            </td>
        </tr>
        <!-- bagian 8 -->
        <tr>
            <td
                style="vertical-align: middle; text-align:center; border:1px solid #000; border-right:none;height: 0.8cm;">
                <b>VIII.</b>
            </td>

            <td colspan="7" style="text-align: center; border:1px solid #000; border-left:none;">
                <div class="row">

                </div>
                <div style="border-bottom: 1px solid #000; width:80%; margin: auto;"> Pemegang Sertifikat ini diberi
                    ijin untuk mengekspor satwa dan tumbuhan sebagai berikut<br></div>
                <i>The above mentioned permitee is authorized to export/import the wild animals and plants specified
                    here order</i>
            </td>
        </tr>
        <!-- bagian Header spesies -->
        <tr>
            <td style="vertical-align: middle; text-align:center; border: 1px solid #000;"><b>No.</b></td>
            <td style="width:7cm; vertical-align: middle; text-align:center; border: 1px solid #000; font-size:7pt;">
                <b><u>Nama Jenis</u></b><br>
                <b><i>Name of species <br>(Scientific Name, Indonesia, Common)</i></b>
            </td>
            <td style="width:2cm; vertical-align: middle; text-align:center; border: 1px solid #000; font-size:7pt;">
                <b><u>Jumlah</u></b><br>
                <b><i>Quantity</i></b>
            </td>
            <td style="width:5cm; vertical-align: middle; text-align:center; border: 1px solid #000; font-size:7pt;">
                <b><u>Keterangan</u></b><br>
                <b><i>Description</i></b>
            </td>
            <td colspan="2"
                style="width:2.5cm; vertical-align: middle; text-align:center; border: 1px solid #000; font-size:7pt;">
                <b>Appendiks <u>(Sumber) </u></b><br>
                <b><i>Appendices (Source)</i></b>
            </td>
            <td colspan="2" class="col-5"
                style="width:3.8cm; vertical-align: middle; text-align:center; border: 1px solid #000; font-size:7pt;">
                <b><u>Realisasi Kuota</u></b><br>
                <b><i>Total Exported Quota (Year)</i></b>
            </td>
        </tr>

        <!-- bagian Looping spesies -->
        @php
            $maxBaris = 15;
            $dataTampil = $satsLn->items->take($maxBaris);
            $jumlahTampil = $dataTampil->count();
            $tinggiPerBaris = 0.5; // Tinggi per baris
            // $sisaTinggi (tinggi baris kosong terakhir, cm) dikirim dari SatsLnPdfService - bisa diatur
            // dari input "Margin" di modal Print/Cetak, default SatsLnPrint::DEFAULT_SISA_TINGGI.

            $formatQty = fn($tradeCode, $value): string => $tradeCode
                ? $tradeCode->formatQuota($value)
                : number_format((float) $value, 2, ',', '.');
            $totalTradeCode = $satsLn->items->first()?->kuotaNasional?->tradeCode;
            $totalQty = (float) $satsLn->items->sum('qty');
        @endphp

        @foreach ($dataTampil as $index => $item)
            @php
                $bgColor = $index % 2 == 0 ? '#fff' : '#e6e6e6';
                $tradeCode = $item->kuotaNasional?->tradeCode;
                $realisasiNasionalBerjalan = $realisasiNasionalRunningTotalByItemId[$item->id] ?? 0.0;
            @endphp

            <tr style="background: {{ $bgColor }};">
                <td
                    style="height: {{ $tinggiPerBaris }}cm; vertical-align:top; border:1px solid #000; text-align:center;padding-top:10px;">
                    {{ $loop->iteration }}.</td>
                <td
                    style="height: {{ $tinggiPerBaris }}cm; vertical-align:top; border:1px solid #000; padding-left:10px; padding-top:10px;">
                    {{ collect([$item->species?->scientific_name, $item->species?->local_name, $item->species?->common_name])->filter()->implode(', ') }}
                </td>
                <td
                    style="height: {{ $tinggiPerBaris }}cm; vertical-align:top; border:1px solid #000;text-align:left;  padding-left:10px; padding-top:10px;">
                    {{ $formatQty($tradeCode, $item->qty) }} {{ $tradeCode?->unit_name }}
                </td>
                <td
                    style="height: {{ $tinggiPerBaris }}cm; vertical-align:top; border:1px solid #000; text-align:left; padding-left:10px; padding-top:10px;">
                    {{ $item->description ?? '' }}</td>
                <td colspan="2"
                    style="height: {{ $tinggiPerBaris }}cm;  vertical-align:top; border:1px solid #000; text-align:center;padding-top:10px; ">
                    {{ $item->species?->trade_status }}
                    {{ $item->kuotaNasional?->source?->code ? '(' . $item->kuotaNasional->source->code . ')' : '' }}
                </td>
                <td colspan="2"
                    style="height: {{ $tinggiPerBaris }}cm; vertical-align:top; border:1px solid #000; text-align:right; padding-right:10px;padding-top:10px;">
                    {{ $formatQty($tradeCode, $realisasiNasionalBerjalan) }} /
                    {{ $formatQty($tradeCode, $item->kuotaNasional?->quota ?? 0) }}
                    ({{ $item->kuotaNasional?->year ?? $satsLn->year }})
                </td>
            </tr>
        @endforeach

        <!-- Baris kosong terakhir -->
        @if ($jumlahTampil < $maxBaris)
            <tr>
                <td style="border: 1px solid #000; padding: 5px 5px; vertical-align: middle; height:{{ $sisaTinggi }}cm;"
                    colspan="8"></td>
            </tr>
        @endif

        <!-- akhir dari bagian Looping spesies -->
        <tr>
            <td colspan="2"
                style=" height:0.6cm; vertical-align: middle;  border:1px solid #000; border-right:none; ">
            </td>
            <td colspan="6"
                style=" height:0.6cm; vertical-align: middle;border:1px solid #000;  border-left:none; ">
                <b>Total
                    {{ $formatQty($totalTradeCode, $totalQty) }} {{ $totalTradeCode?->unit_name }}
                    @if ($totalTradeCode)
                        <i>({{ $totalTradeCode->quotaToWords($totalQty, 'en') }})</i>
                    @endif
                </b>
            </td>
        </tr>
        <tr>
            <td
                style=" height:0.4cm; border:1px solid #000; vertical-align: top; text-align:center; border-right:none; border-bottom:none; border-bottom:none;padding-top: 20px;">
                <b>IX.</b>
            </td>
            <td colspan="7"
                style=" height:0.4cm; border:1px solid #000; border-left:none; border-bottom:none; vertical-align: top; padding-left: 20px;">
                <div class="row">
                    <div class="col1set hg-1">
                        <b> <u> Syarat khusus</u><br>
                            <i>Special conditions</i></b>
                    </div>
                    <div class="col-sp hg-1">
                        <b>:</b>
                    </div>
                    <div class="col-7 hg-1">
                        <div style="font-size:5pt; border-bottom:1px solid #000; width:98%;">Tidak sah apabila ada
                            coretan/koreksi : untuk satwa hidup, hanya berlaku apabila pengangkutannya sesuai dengan
                            pengaturan IATA untuk satu kali pengiriman<br> </div>
                        <span style="font-size:4.9pt;"> Not valid for any correction: For live animals, this permit is
                            only valid if the transport conditions conform to the guidelines for transport of live
                            animals, or IATA regulation and valid for one shipment only</span>

                    </div>
                </div>

            </td>
        </tr>
        <tr>
            <td colspan="7"
                style="height: 1.2cm; vertical-align: top;padding-left: 35px; padding-top: 8px; border:1px solid #000; border-right:none;border-top:none;">

                @if (!empty($satsLn->security_stamp))
                    Security Stamp No: {{ $satsLn->security_stamp }}<br>
                @endif

                {!! nl2br(e($satsLn->special_conditions ?? '')) !!}
            </td>
            <td colspan="1"
                style="vertical-align: middle; text-align:right; height: 1.2cm; padding-right:25px; border:1px solid #000; border-left:none; border-top:none;">
            </td>
        </tr>
        <tr>
            <td
                style="border:#000 1px solid; vertical-align: top;text-align:center;border-right:none; border-bottom:none; padding-top:8px; ">
                <b>X.</b>
            </td>
            <td colspan="2"
                style="vertical-align:top;border-bottom:none; border:#000 1px solid;border-left:none;border-right:none; border-bottom:none;padding-top:8px;">
                <b><u>Sertifikat ini diterbitkan oleh</u></b><br>
                <b><i> This permit is issued by</i></b>
            </td>
            <td colspan="5"
                style="font-size:5pt; text-align: center; border:#000 1px solid; border-left:none; border-bottom:none;">
                <b>ATAS NAMA DIREKTUR JENDERAL<br>
                    <u>KONSERVASI SUMBER DAYA ALAM DAN EKOSISTEM</u></b><br>
                <span> FOR THE DIRECTOR GENERAL<br>OF ECOSYSTEM AND NATURAL RESOURCES CONSERVATION</span>
            </td>
        </tr>
        <tr>
            <td
                style="border:#000 1px solid; vertical-align: top;text-align:center;border-right:none; border-bottom:none; border-top:none; padding-top:8px;">
                <b></b>
            </td>
            <td
                style="vertical-align:bottom; border:#000 1px solid;border-left:none;border-right:none; border-top:none; padding-top:8px; height:1.9cm;">
                <div style="text-align:center; width:2cm;">
                    <b>Jakarta</b>
                </div>
            </td>
            <td
                style="vertical-align:bottom; border:#000 1px solid;border-left:none;border-right:none; border-top:none; padding-top:8px; height:1cm;">
                <div style="margin-left:-4cm; width:2cm; text-align:center;">
                    <b>{{ $publication_date }}</b>
                </div>
            </td>
            <td colspan="5"
                style="text-align: center; border:#000 1px solid;border-top:none; border-bottom:none; border-left:none;vertical-align:bottom; ">
                <b><br>
                    {{ $ttd->name }}</b><br>
            </td>
        </tr>

        <!-- bagian petugas -->

        <tr>
            <td colspan="8" style="border:1px solid #000; padding: 0.1cm; height:2cm;">
                <div class="row">
                    <div class="col-7" style="border:1px solid #000;">
                        <div class="row">
                            <div class="col-12"
                                style="text-align: center; padding:3px; border-bottom:#000 1px solid;">
                                <b>XI. Diisi oleh petugas pemeriksa pengiriman</b><br>
                                To be completed by official who inspect the shipment
                            </div>
                        </div>
                        <div class="row">
                            <!-- Tabel Kiri -->
                            <div style="display: inline-block; width: 48%; vertical-align: top;">
                                <table style="border-collapse: collapse; width: 100%;">
                                    <tr>
                                        <td colspan="2"
                                            style="font-size:7pt; text-align:center; border:1px solid #000; border-left:none; border-top:none; height:0.5cm;">
                                            Lihat kolom jenis / See column of species
                                        </td>
                                    </tr>
                                    <tr>
                                        <td
                                            style="border:1px solid #000; width:0.7cm; text-align:center; padding: 8px; border-left:none;">
                                            No.
                                        </td>
                                        <td style="border:1px solid #000; text-align:center; padding: 8px;">
                                            Jumlah / Quantity
                                        </td>
                                    </tr>
                                    <!-- Looping untuk No 1 - 15 -->
                                    <?php for ($i = 1; $i <= 15; $i++): ?>
                                    <tr>
                                        <td
                                            style="border:1px solid #000;height:0.3cm; text-align:center;border-bottom:none; border-left:none;">
                                            <?= $i ?>.
                                        </td>
                                        <td
                                            style="border:1px solid #000;height:0.3cm; border-left:none; border-bottom:none;">
                                        </td>
                                    </tr>
                                    <?php endfor; ?>
                                </table>
                            </div>

                            <!-- Tabel Kanan -->
                            <div style="display: inline-block; width: 48%; vertical-align: top; text-align: right;">
                                <table style="width: 100%;">
                                    <tr>
                                        <td style="padding: 10px; text-align:left; font-size:6pt;">
                                            <u>No. Bukti Pengiriman</u><br>
                                            <i>Bill of Ladding (Airway bill number)</i>
                                        </td>
                                        <td style="text-align: left; width:1cm;">:</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; text-align:left; font-size:6pt; height:1.5cm;">
                                            <u>Tanggal</u><br>
                                            <i>Date</i>
                                        </td>
                                        <td style="text-align: left; width:1cm;">:</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; text-align:left; font-size:6pt;">
                                            <u>Pelabuhan pemberangkatan</u><br>
                                            <i>Port of exportation</i>
                                        </td>
                                        <td style="text-align: left; width:1cm;">:</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2"
                                            style="font-size:6pt; text-align:center; vertical-align:bottom;">
                                            <div class="row" style="padding-top:2cm;">
                                                <div class="col-6">
                                                    <span style="visibility: hidden"> {{ $ttd->name }}</span><br>
                                                    <u>Cap</u><br>Official Stamp
                                                </div>
                                                <div class="col-6">
                                                    <span style="visibility: hidden"> {{ $ttd->name }}</span><br>
                                                    <u>Tanda Tangan</u><br>Signature
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Bagian XII: Pembaharuan -->
                    <div class="col-5" style="border:1px solid #000;">
                        <div class="row">
                            <div class="col-12"
                                style="text-align: center; padding:5px; border-bottom:#000 1px solid;">
                                <b><u>XII. Pembaharuan</u></b><br>Renewal
                            </div>
                        </div>
                        <div style="display: inline-block; width: 100%; vertical-align: top; text-align: right;">
                            <table style="width: 100%;">
                                <tr>
                                    <td style="padding: 10px; text-align:left; font-size:6pt;">
                                        <b><u>Berlaku sampai dengan</u><br>
                                            <i>Valid until</i></b>
                                    </td>
                                    <td style="text-align: left; vertical-align:middle; width:3cm;">:</td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="padding-left: 15px;">
                                        <span style="visibility:hidden; font-size:6pt;">{{ $date_valid }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px; text-align:left; font-size:6pt;">
                                        <b><u>Dikirim kepada (nama, alamat, negara)</u><br>
                                            <i>Importir (name, address, country)</i></b>
                                    </td>
                                    <td style="text-align: left; width:1cm;">:</td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="padding-left: 15px;">
                                        <span
                                            style="visibility:hidden; display: block; font-size: {{ strlen($importir) > 128 ? '4.8pt' : '6pt' }};">
                                            {{ $importir }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px; text-align:left; font-size:6pt;">
                                        <b><u>Pelabuhan pemberangkatan</u><br>
                                            <i>Port of exportation</i></b>
                                    </td>
                                    <td style="text-align: left; width:1cm;">:</td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="padding-left: 15px;">
                                        <span style="visibility:hidden; font-size:6pt;">{{ $port_departur }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2"
                                        style="font-size:6pt; text-align:center; vertical-align:bottom; ">
                                        <div class="row" style="padding-top:1.7cm;">
                                            <div class="col-6">
                                                <span style="visibility: hidden"> {{ $ttd->name }}</span><br>
                                                <u>Cap</u><br>Official Stamp
                                            </div>
                                            <div class="col-6">
                                                <span style="visibility: hidden"> {{ $ttd->name }}</span><br>
                                                <u>Tanda Tangan</u><br>Signature
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="8">
                <u>Dokumen ini hanya sah dan berlaku jika data yang dicetak pada kertas standar dari Kementerian
                    Kehutanan sesuai dengan data digital.</u><br><i>This document is only valid if the data printed on
                    the standard paper from the Ministry of Forestry matches the data presented herein.</i>
            </td>
        </tr>

    </table>


</body>

</html>
