<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationCategory: string
{
    case Pengumuman = 'pengumuman';
    case Permohonan = 'permohonan';
    case InformasiLain = 'informasi_lain';

    public function label(): string
    {
        return match ($this) {
            self::Pengumuman => __('Pengumuman'),
            self::Permohonan => __('Permohonan'),
            self::InformasiLain => __('Informasi Lain'),
        };
    }
}
