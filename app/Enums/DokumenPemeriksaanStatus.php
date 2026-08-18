<?php

declare(strict_types=1);

namespace App\Enums;

enum DokumenPemeriksaanStatus: string
{
    case Lolos = 'lolos';
    case Gagal = 'gagal';
    case Dilewati = 'dilewati';

    public function label(): string
    {
        return match ($this) {
            self::Lolos => __('Lolos'),
            self::Gagal => __('Gagal'),
            self::Dilewati => __('Dilewati'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Lolos => 'border-success/30 bg-success/10 text-success',
            self::Gagal => 'border-danger/30 bg-danger/10 text-danger',
            self::Dilewati => 'border-default-300 bg-default-150 text-default-600',
        };
    }
}
