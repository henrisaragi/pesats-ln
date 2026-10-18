<?php

declare(strict_types=1);

namespace App\Enums;

enum PermohonanStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case AntrianVerifikasi = 'antrian_verifikasi';
    case DalamProsesVerifikasi = 'dalam_proses_verifikasi';
    case Ditolak = 'ditolak';
    case AntrianCetak = 'antrian_cetak';
    case DicetakSebagian = 'dicetak_sebagian';
    case DitandatanganiSebagian = 'ditandatangani_sebagian';
    case SiapDiambil = 'siap_diambil';

    /**
     * Permission untuk opsi filter "Semua Status" di halaman verifikasi
     * (permohonan-export/verifikasi). Filter per status memakai verifikasiFilterPermission().
     */
    public const VERIFIKASI_FILTER_ALL_PERMISSION = 'permohonan.verify.filter.all';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Submitted => __('Diajukan'),
            self::AntrianVerifikasi => __('Antrian Verifikasi'),
            self::DalamProsesVerifikasi => __('Dalam Proses Verifikasi'),
            self::Ditolak => __('Ditolak'),
            self::AntrianCetak => __('Antrian Cetak'),
            self::DicetakSebagian => __('Telah Dicetak Sebagian'),
            self::DitandatanganiSebagian => __('Telah Ditandatangani Sebagian'),
            self::SiapDiambil => __('Siap Untuk Diambil'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'border-default-300 bg-default-150 text-default-600',
            self::Submitted, self::AntrianVerifikasi => 'border-info/30 bg-info/10 text-info',
            self::DalamProsesVerifikasi => 'border-warning/30 bg-warning/10 text-warning',
            self::Ditolak => 'border-danger/30 bg-danger/10 text-danger',
            self::AntrianCetak, self::DicetakSebagian, self::DitandatanganiSebagian => 'border-primary/30 bg-primary/10 text-primary',
            self::SiapDiambil => 'border-success/30 bg-success/10 text-success',
        };
    }

    /**
     * Permission yang mengaktifkan filter status ini di halaman verifikasi, di-assign ke role
     * lewat UI Roles/Permissions.
     */
    public function verifikasiFilterPermission(): string
    {
        return 'permohonan.verify.filter.'.$this->value;
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Ditolak], true);
    }
}
