<?php

declare(strict_types=1);

namespace App\Enums;

enum AssesmentIsiStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Verified = 'verified';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Submitted => __('Menunggu Verifikasi'),
            self::Verified => __('Terverifikasi'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'border-default-300 bg-default-150 text-default-600',
            self::Submitted => 'border-warning/30 bg-warning/10 text-warning',
            self::Verified => 'border-success/30 bg-success/10 text-success',
        };
    }
}
