<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ __('Sertifikat Assesment') }}</title>
    <style>
        @page { margin: 0; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 40px 60px;
            border: 10px solid #0f5132;
        }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-size: 28px; margin: 0; color: #0f5132; letter-spacing: 2px; }
        .header p { font-size: 12px; color: #555; margin: 4px 0 0; }
        .content { text-align: center; margin-top: 40px; }
        .content .label { font-size: 13px; color: #555; }
        .content .entitas { font-size: 24px; font-weight: bold; margin: 8px 0 20px; }
        .content .judul { font-size: 16px; margin-bottom: 6px; }
        .content .komoditas { font-size: 14px; color: #333; margin-bottom: 20px; }
        table.info { margin: 30px auto 0; font-size: 12px; }
        table.info td { padding: 4px 12px; }
        .footer { margin-top: 60px; text-align: right; font-size: 12px; }
    </style>
</head>
<body>
<div class="header">
    <h1>{{ __('SERTIFIKAT ASSESMENT') }}</h1>
    <p>{{ __('Direktorat Jenderal Konservasi Sumber Daya Alam dan Ekosistem') }}</p>
</div>
<div class="content">
    <p class="label">{{ __('Diberikan kepada') }}</p>
    <p class="entitas">{{ $isi->entitas?->name }}</p>
    <p class="judul">{{ __('Telah menyelesaikan') }} <strong>{{ $isi->assesment?->judul }}</strong></p>
    <p class="komoditas">{{ __('Untuk komoditas') }}: {{ $isi->komoditas?->name }}</p>

    <table class="info">
        <tr>
            <td>{{ __('Skor Total') }}</td>
            <td>: {{ number_format((float) $isi->skor_total, 2) }}</td>
        </tr>
        <tr>
            <td>{{ __('Tanggal Verifikasi') }}</td>
            <td>: {{ $isi->verified_at?->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td>{{ __('Diverifikasi Oleh') }}</td>
            <td>: {{ $isi->verifiedBy?->name ?? __('Sistem') }}</td>
        </tr>
    </table>
</div>
<div class="footer">
    <p>{{ now()->format('d/m/Y') }}</p>
</div>
</body>
</html>
