<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Siklus periode Pembagian Kuota Ekspor: draft → dihitung → diajukan → ditetapkan → ditutup.
 */
enum KuotaPeriodStatus: string
{
    case Draft = 'draft';
    case Dihitung = 'dihitung';
    case Diajukan = 'diajukan';
    case Ditetapkan = 'ditetapkan';
    case Ditutup = 'ditutup';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Dihitung => __('Dihitung'),
            self::Diajukan => __('Diajukan'),
            self::Ditetapkan => __('Ditetapkan'),
            self::Ditutup => __('Ditutup'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'border-default-300 bg-default-150 text-default-600',
            self::Dihitung => 'border-info/30 bg-info/10 text-info',
            self::Diajukan => 'border-warning/30 bg-warning/10 text-warning',
            self::Ditetapkan => 'border-success/30 bg-success/10 text-success',
            self::Ditutup => 'border-dark/30 bg-dark/10 text-default-700',
        };
    }

    /**
     * Header periode & daftar peserta masih bisa diubah (perubahan mengembalikan status ke draft).
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Dihitung], true);
    }
}
