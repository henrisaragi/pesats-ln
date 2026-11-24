<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Kategori kelayakan dari persentase skor hasil verifikasi, mengikuti rumus Excel:
 * =IF(G>=85;"Sangat Layak";IF(G>=70;"Layak";IF(G>=60;"Layak Bersyarat";"Tidak Layak"))).
 */
enum AssesmentKelayakan: string
{
    case SangatLayak = 'sangat_layak';
    case Layak = 'layak';
    case LayakBersyarat = 'layak_bersyarat';
    case TidakLayak = 'tidak_layak';

    public static function fromPersentase(float $persentase): self
    {
        return match (true) {
            $persentase >= 85 => self::SangatLayak,
            $persentase >= 70 => self::Layak,
            $persentase >= 60 => self::LayakBersyarat,
            default => self::TidakLayak,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::SangatLayak => __('Sangat Layak'),
            self::Layak => __('Layak'),
            self::LayakBersyarat => __('Layak Bersyarat'),
            self::TidakLayak => __('Tidak Layak'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::SangatLayak, self::Layak => 'border-success/30 bg-success/10 text-success',
            self::LayakBersyarat => 'border-warning/30 bg-warning/10 text-warning',
            self::TidakLayak => 'border-danger/30 bg-danger/10 text-danger',
        };
    }
}
