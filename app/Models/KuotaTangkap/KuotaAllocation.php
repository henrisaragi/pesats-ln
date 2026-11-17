<?php

declare(strict_types=1);

namespace App\Models\KuotaTangkap;

use App\Models\Assesment\AssesmentIsi;
use App\Models\Entitas\Entitas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KuotaAllocation extends Model
{
    protected $table = 'kuota_allocations';

    protected $fillable = [
        'kuota_period_id',
        'entity_id',
        'permohonan',
        'realisasi_ekspor',
        'nilai_evaluasi',
        'assesment_isi_id',
        'komponen_ekspor',
        'komponen_evaluasi',
        'rekomendasi',
        'rekomendasi_bulat',
        'ditetapkan',
        'alasan_penyesuaian',
        'realisasi_tangkap',
        'sk_tangkap_nomor',
        'sk_tangkap_tanggal',
        'sk_tangkap_path',
    ];

    protected function casts(): array
    {
        return [
            'permohonan' => 'decimal:4',
            'realisasi_ekspor' => 'decimal:4',
            'nilai_evaluasi' => 'decimal:2',
            'komponen_ekspor' => 'decimal:4',
            'komponen_evaluasi' => 'decimal:4',
            'rekomendasi' => 'decimal:4',
            'rekomendasi_bulat' => 'integer',
            'ditetapkan' => 'decimal:4',
            'realisasi_tangkap' => 'decimal:4',
            'sk_tangkap_tanggal' => 'date',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(KuotaPeriod::class, 'kuota_period_id');
    }

    public function entitas(): BelongsTo
    {
        return $this->belongsTo(Entitas::class, 'entity_id');
    }

    public function assesmentIsi(): BelongsTo
    {
        return $this->belongsTo(AssesmentIsi::class);
    }

    /**
     * Ditetapkan menyimpang dari rekomendasi bulat → alasan wajib diisi.
     */
    public static function requiresReason(?string $ditetapkan, ?int $rekomendasiBulat): bool
    {
        if ($ditetapkan === null || $ditetapkan === '' || $rekomendasiBulat === null) {
            return false;
        }

        return bccomp($ditetapkan, (string) $rekomendasiBulat, 4) !== 0;
    }
}
