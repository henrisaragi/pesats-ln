<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Entitas\Entitas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KuotaPerusahaan extends Model
{
    use HasFactory;

    protected $table = 'kuota_perusahaan';

    protected $fillable = [
        'entity_id',
        'kuota_nasional_id',
        'quota',
        'year',
    ];

    protected function casts(): array
    {
        return [
            'quota' => 'decimal:4',
            'year' => 'integer',
        ];
    }

    public function entitas(): BelongsTo
    {
        return $this->belongsTo(Entitas::class, 'entity_id');
    }

    public function kuotaNasional(): BelongsTo
    {
        return $this->belongsTo(KuotaNasional::class);
    }

    public function satsLnItems(): HasMany
    {
        return $this->hasMany(SatsLnItem::class);
    }

    /**
     * "100/1000" style label: kuota perusahaan ini dibanding total kuota nasional-nya,
     * diformat sesuai jumlah desimal & mode pembulatan kode perdagangannya.
     */
    public function quotaRatioLabel(): ?string
    {
        $tradeCode = $this->kuotaNasional?->tradeCode;

        if ($tradeCode === null) {
            return null;
        }

        return $tradeCode->formatQuota($this->quota).'/'.$tradeCode->formatQuota($this->kuotaNasional->quota);
    }
}
