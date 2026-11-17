<?php

declare(strict_types=1);

namespace App\Models\KuotaTangkap;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Hasil satu kali perhitungan (insert-only). Input (E_i, N_i, pengaturan) dan hasil disimpan
 * apa adanya supaya perhitungan lama tetap bisa direproduksi walaupun data sumber berubah.
 */
class KuotaCalculationSnapshot extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'kuota_calculation_snapshots';

    protected $fillable = [
        'kuota_period_id',
        'version',
        'kuota_tangkap',
        'cadangan',
        'bobot_ekspor',
        'bobot_evaluasi',
        'kuota_dibagi',
        'total_realisasi_ekspor',
        'total_nilai_evaluasi',
        'tahun_realisasi',
        'rows',
        'warnings',
        'calculated_by',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'kuota_tangkap' => 'decimal:4',
            'cadangan' => 'decimal:4',
            'bobot_ekspor' => 'decimal:4',
            'bobot_evaluasi' => 'decimal:4',
            'kuota_dibagi' => 'integer',
            'total_realisasi_ekspor' => 'decimal:4',
            'total_nilai_evaluasi' => 'decimal:2',
            'tahun_realisasi' => 'integer',
            'rows' => 'array',
            'warnings' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn (): never => throw new LogicException('Snapshot perhitungan kuota tidak boleh diubah.'));
    }

    /**
     * Pesan peringatan diterjemahkan saat ditampilkan (bukan saat disimpan) supaya ikut locale aktif.
     *
     * @return list<string>
     */
    public function warningMessages(): array
    {
        return array_map(fn (array $warning): string => match ($warning['code'] ?? null) {
            'sum_e_zero' => __('Total realisasi ekspor (ΣE) tahun :tahun = 0, komponen ekspor seluruh perusahaan = 0. Porsi bobot ekspor tidak dibagi dan menjadi cadangan.', ['tahun' => $this->tahun_realisasi]),
            'sum_n_zero' => __('Total nilai evaluasi (ΣN) = 0, komponen evaluasi seluruh perusahaan = 0. Porsi bobot evaluasi tidak dibagi dan menjadi cadangan.'),
            'no_assesment' => __(':entitas belum memiliki assessment terverifikasi untuk komoditas ini (N = 0).', ['entitas' => $warning['entity'] ?? '-']),
            default => (string) ($warning['code'] ?? ''),
        }, $this->warnings ?? []);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(KuotaPeriod::class, 'kuota_period_id');
    }

    public function calculator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }
}
