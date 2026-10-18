<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Hasil verifikasi per SATS-LN (kolom sats_ln.status_satsln). Null = belum pernah diverifikasi.
 */
enum SatsLnStatus: string
{
    case Disetujui = 'disetujui';
    case OnHold = 'on_hold';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Disetujui => __('Disetujui'),
            self::OnHold => __('On Hold'),
            self::Ditolak => __('Ditolak'),
        };
    }

    /**
     * Sudah ada keputusan final verifikator (disetujui/ditolak) - syarat Disposisi Cetak.
     */
    public function isDecided(): bool
    {
        return $this !== self::OnHold;
    }
}
