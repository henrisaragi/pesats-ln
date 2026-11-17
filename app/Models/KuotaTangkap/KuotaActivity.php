<?php

declare(strict_types=1);

namespace App\Models\KuotaTangkap;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit log Pembagian Kuota Ekspor. kuota_period_id null = perubahan pengaturan global.
 */
class KuotaActivity extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'kuota_activities';

    protected $fillable = [
        'kuota_period_id',
        'kuota_allocation_id',
        'user_id',
        'action',
        'old_values',
        'new_values',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public static function record(
        ?KuotaPeriod $period,
        string $action,
        ?User $user,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $keterangan = null,
        ?KuotaAllocation $allocation = null,
    ): self {
        return static::query()->create([
            'kuota_period_id' => $period?->id,
            'kuota_allocation_id' => $allocation?->id,
            'user_id' => $user?->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'keterangan' => $keterangan,
        ]);
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'created' => __('Periode dibuat'),
            'updated' => __('Periode diubah'),
            'deleted' => __('Periode dihapus'),
            'participant_added' => __('Peserta ditambahkan'),
            'participant_removed' => __('Peserta dihapus'),
            'calculated' => __('Rekomendasi dihitung'),
            'allocated' => __('Penetapan disimpan'),
            'recommendation_copied' => __('Rekomendasi disalin'),
            'submitted' => __('Diajukan'),
            'approved' => __('Ditetapkan'),
            'returned' => __('Dikembalikan'),
            'closed' => __('Ditutup'),
            'realisasi_updated' => __('Realisasi ekspor diperbarui'),
            'settings_updated' => __('Pengaturan diubah'),
            default => $this->action,
        };
    }

    /**
     * Label kolom yang mudah dibaca untuk tampilan Audit Log (bukan nama kolom database).
     */
    public static function fieldLabel(string $field): string
    {
        return match ($field) {
            'provinsi_id' => __('Provinsi'),
            'komoditas_id' => __('Komoditas'),
            'species_id' => __('Jenis TSL'),
            'trade_code_id' => __('Kode Perdagangan'),
            'year' => __('Tahun'),
            'kuota_tangkap' => __('Kuota Ekspor (Q)'),
            'catatan' => __('Catatan'),
            'entity_id' => __('Perusahaan'),
            'permohonan' => __('Permohonan'),
            'ditetapkan' => __('Ditetapkan'),
            'alasan_penyesuaian' => __('Alasan Penyesuaian'),
            'realisasi_tangkap' => __('Realisasi Ekspor'),
            'sk_tangkap_nomor' => __('Nomor SK Kuota Ekspor'),
            'sk_tangkap_tanggal' => __('Tanggal SK Kuota Ekspor'),
            'sk_tangkap_path' => __('File SK Kuota Ekspor'),
            'version' => __('Versi Snapshot'),
            'kuota_dibagi' => __('Kuota Dibagi'),
            'total_realisasi_ekspor' => __('Total Realisasi Ekspor'),
            'total_nilai_evaluasi' => __('Total Nilai Evaluasi'),
            'peringatan' => __('Jumlah Peringatan'),
            'total_ditetapkan' => __('Total Ditetapkan'),
            'jumlah_baris' => __('Jumlah Baris'),
            default => $field,
        };
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(KuotaPeriod::class, 'kuota_period_id');
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(KuotaAllocation::class, 'kuota_allocation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
