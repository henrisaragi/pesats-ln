<?php

declare(strict_types=1);

namespace App\Enums;

enum JenisPermohonan: string
{
    case Baru = 'baru';
    case Perubahan = 'perubahan';
    case Perpanjangan = 'perpanjangan';

    public function label(): string
    {
        return match ($this) {
            self::Baru => __('Baru'),
            self::Perubahan => __('Perubahan'),
            self::Perpanjangan => __('Perpanjangan'),
        };
    }
}
