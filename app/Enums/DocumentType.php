<?php

declare(strict_types=1);

namespace App\Enums;

enum DocumentType: string
{
    case Permohonan = 'permohonan';
    case ExportPermit = 'export_permit';
    case ImportPermit = 'import_permit';
    case CountryOfOrigin = 'country_of_origin';
    case SertifikatStandar = 'sertifikat_standar';
    case Bap = 'bap';
    case Perteks = 'perteks';
    case FormC = 'form_c';
    case SatsLn = 'sats_ln';
    case SatsDn = 'sats_dn';
    case SuratKeterangan = 'surat_keterangan';
    case SkIzinTangkap = 'sk_izin_tangkap';
    case SkKuotaEkspor = 'sk_kuota_ekspor';
    case SkBmp = 'sk_bmp';

    public function label(): string
    {
        return match ($this) {
            self::Permohonan => __('Permohonan'),
            self::ExportPermit => __('Export Permit'),
            self::ImportPermit => __('Import Permit'),
            self::CountryOfOrigin => __('Country of Origin'),
            self::SertifikatStandar => __('Sertifikat Standar'),
            self::Bap => __('Berita Acara Pemeriksaan (BAP)'),
            self::Perteks => __('Pertimbangan Teknis (Perteks)'),
            self::FormC => __('Form C'),
            self::SatsLn => __('SATS-LN'),
            self::SatsDn => __('SATS-DN'),
            self::SuratKeterangan => __('Surat Keterangan'),
            self::SkIzinTangkap => __('SK Izin Tangkap'),
            self::SkKuotaEkspor => __('SK Kuota Ekspor'),
            self::SkBmp => __('SK BMP'),
        };
    }

    /**
     * Hanya 3 tipe ini yang boleh bebas memilih negara penerbit - selain itu dikunci ke
     * Indonesia (lihat App\Http\Requests\DocumentRequest::prepareForValidation()).
     */
    public function allowsFreeCountrySelection(): bool
    {
        return in_array($this, [self::ExportPermit, self::ImportPermit, self::CountryOfOrigin], true);
    }
}
