<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppSetting extends Model
{
    protected $table = 'whatsapp_settings';

    protected $fillable = [
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
        ];
    }

    public static function isEnabled(): bool
    {
        return (bool) static::query()->value('is_enabled');
    }

    public static function setEnabled(bool $enabled): void
    {
        $setting = static::query()->first() ?? new static;
        $setting->is_enabled = $enabled;
        $setting->save();
    }
}
