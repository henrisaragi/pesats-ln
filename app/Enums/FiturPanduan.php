<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Fitur tujuan assign Template Surat Pernyataan & Video Panduan. Tambahkan case baru di sini
 * untuk menampilkan template/video di fitur lain (lihat scope AssignableToFitur::untukFitur()).
 */
enum FiturPanduan: string
{
    case AssesmentIsi = 'assesment-isi';

    public function label(): string
    {
        return match ($this) {
            self::AssesmentIsi => __('Isi Assesment'),
        };
    }
}
