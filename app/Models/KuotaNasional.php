<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KuotaNasional extends Model
{
    use HasFactory;

    protected $table = 'kuota_nasional';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'komoditas_id',
        'species_id',
        'source_id',
        'trade_code_id',
        'quota',
        'year',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quota' => 'decimal:4',
            'year' => 'integer',
        ];
    }

    public function komoditas(): BelongsTo
    {
        return $this->belongsTo(Komoditas::class);
    }

    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(SourceStatus::class, 'source_id');
    }

    public function tradeCode(): BelongsTo
    {
        return $this->belongsTo(TradeCode::class);
    }

    public function kuotaPerusahaan(): HasMany
    {
        return $this->hasMany(KuotaPerusahaan::class);
    }
}
