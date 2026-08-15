<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeCode extends Model
{
    use HasFactory;

    protected $table = 'trade_codes';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'unit',
        'unit_name',
        'decimal_places',
        'rounding_enabled',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'rounding_enabled' => 'boolean',
        ];
    }

    /**
     * Format a quota value using this trade code's configured decimal places and rounding mode.
     */
    public function formatQuota(float|string $quota): string
    {
        $decimals = $this->decimal_places;
        $value = (float) $quota;

        if (! $this->rounding_enabled) {
            $factor = 10 ** $decimals;
            $value = floor($value * $factor) / $factor;
        }

        return number_format($value, $decimals, ',', '.');
    }
}
