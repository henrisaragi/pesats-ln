<?php

declare(strict_types=1);

namespace App\Enums;

enum KomoditasEntitasStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Menunggu Verifikasi'),
            self::Verified => __('Terverifikasi'),
            self::Rejected => __('Ditolak'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'border-warning/30 bg-warning/10 text-warning',
            self::Verified => 'border-success/30 bg-success/10 text-success',
            self::Rejected => 'border-danger/30 bg-danger/10 text-danger',
        };
    }
}
