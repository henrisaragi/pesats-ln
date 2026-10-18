<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Interval masa berlaku SATS-LN yang dipilih verifikator di modal Verifikasi - tanggal berakhir
 * (valid_until) selalu dihitung dari tanggal terbit + interval ini, logika yang sama dicerminkan
 * di resources/js/pages/permohonan-show.js untuk pratinjau di modal.
 */
enum MasaBerlakuSatsLn: string
{
    case TigaBulan = '3_bulan';
    case EnamBulan = '6_bulan';
    case FullDate = 'full_date';

    public function label(): string
    {
        return match ($this) {
            self::TigaBulan => __('3 Bulan'),
            self::EnamBulan => __('6 Bulan'),
            self::FullDate => __('Full-date (31 Maret tahun berikutnya)'),
        };
    }

    /**
     * Interval yang menghasilkan valid_until tersimpan - dipakai untuk mengisi ulang modal saat
     * SATS-LN diverifikasi ulang (interval sendiri tidak disimpan).
     */
    public static function matching(CarbonInterface $publishDate, CarbonInterface $validUntil): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->validUntil($publishDate)->isSameDay($validUntil)) {
                return $case;
            }
        }

        return null;
    }

    public function validUntil(CarbonInterface $publishDate): CarbonImmutable
    {
        $publishDate = CarbonImmutable::instance($publishDate)->startOfDay();

        return match ($this) {
            self::TigaBulan => $publishDate->addMonthsNoOverflow(3),
            self::EnamBulan => $publishDate->addMonthsNoOverflow(6),
            self::FullDate => $publishDate->setDate($publishDate->year + 1, 3, 31),
        };
    }
}
