<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JenisPermohonan;
use App\Enums\PermohonanStatus;
use App\Models\Entitas\Entitas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permohonan extends Model
{
    use HasFactory, SoftDeletes;

    protected const MAX_SATS_LN = 25;

    protected $table = 'permohonan';

    protected $fillable = [
        'entity_id',
        'nomor_permohonan',
        'jenis_permohonan',
        'status',
        'submitted_at',
        'verified_by',
        'verified_at',
        'catatan_verifikasi',
    ];

    protected function casts(): array
    {
        return [
            'jenis_permohonan' => JenisPermohonan::class,
            'status' => PermohonanStatus::class,
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function entitas(): BelongsTo
    {
        return $this->belongsTo(Entitas::class, 'entity_id');
    }

    public function document(): HasOne
    {
        return $this->hasOne(Document::class, 'permohonan_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function satsLn(): HasMany
    {
        return $this->hasMany(SatsLn::class);
    }

    public function hasReachedMaxSatsLn(): bool
    {
        return $this->satsLn()->count() >= self::MAX_SATS_LN;
    }
}
