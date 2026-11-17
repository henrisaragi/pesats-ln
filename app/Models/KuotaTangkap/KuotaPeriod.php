<?php

declare(strict_types=1);

namespace App\Models\KuotaTangkap;

use App\Enums\KuotaPeriodStatus;
use App\Models\Komoditas;
use App\Models\Provinsi;
use App\Models\Species;
use App\Models\TradeCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KuotaPeriod extends Model
{
    use SoftDeletes;

    protected $table = 'kuota_periods';

    protected $fillable = [
        'provinsi_id',
        'komoditas_id',
        'species_id',
        'trade_code_id',
        'year',
        'kuota_tangkap',
        'status',
        'latest_snapshot_id',
        'catatan',
        'catatan_pengembalian',
        'created_by',
        'submitted_by',
        'approved_by',
        'closed_by',
        'submitted_at',
        'approved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'provinsi_id' => 'integer',
            'year' => 'integer',
            'kuota_tangkap' => 'decimal:4',
            'status' => KuotaPeriodStatus::class,
            'submitted_by' => 'integer',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * Cakupan wilayah: user yang terhubung ke entitas UPT KSDA (balai) hanya melihat periode di
     * provinsi entitasnya; petugas internal (tanpa entitas) melihat semua.
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $provinsiId = static::scopedProvinsiIdFor($user);

        if ($provinsiId !== null) {
            $query->where('provinsi_id', $provinsiId);
        }
    }

    public static function scopedProvinsiIdFor(User $user): ?int
    {
        $entity = $user->entity;

        if ($entity === null || $entity->entity_type !== 'upt_ksda') {
            return null;
        }

        return (int) $entity->provinsi_id;
    }

    public function provinsi(): BelongsTo
    {
        return $this->belongsTo(Provinsi::class);
    }

    public function komoditas(): BelongsTo
    {
        return $this->belongsTo(Komoditas::class);
    }

    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    public function tradeCode(): BelongsTo
    {
        return $this->belongsTo(TradeCode::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(KuotaAllocation::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(KuotaCalculationSnapshot::class);
    }

    public function latestSnapshot(): BelongsTo
    {
        return $this->belongsTo(KuotaCalculationSnapshot::class, 'latest_snapshot_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(KuotaActivity::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function label(): string
    {
        return trim(($this->provinsi?->name ?? '').' · '.($this->species?->scientific_name ?? '').' · '.($this->tradeCode?->name ?? '').' · '.$this->year);
    }
}
