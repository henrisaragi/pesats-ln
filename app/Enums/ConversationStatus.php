<?php

declare(strict_types=1);

namespace App\Enums;

enum ConversationStatus: string
{
    case Waiting = 'waiting';
    case Active = 'active';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => __('Menunggu'),
            self::Active => __('Aktif'),
            self::Closed => __('Selesai'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Waiting => 'border-warning/30 bg-warning/10 text-warning',
            self::Active => 'border-success/30 bg-success/10 text-success',
            self::Closed => 'border-default-300 bg-default-150 text-default-500',
        };
    }
}
