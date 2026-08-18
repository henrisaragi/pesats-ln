<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\KomoditasEntitasStatus;
use App\Models\Entitas\Entitas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KomoditasEntitas extends Model
{
    use HasFactory;

    protected $table = 'komoditas_entitas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'komoditas_id',
        'entitas_id',
        'nomor_sertifikat',
        'file_path',
        'tanggal_terbit',
        'tanggal_berakhir',
        'status',
        'verified_by',
        'verified_at',
        'summary_cek',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_terbit' => 'date',
            'tanggal_berakhir' => 'date',
            'status' => KomoditasEntitasStatus::class,
            'verified_at' => 'datetime',
            'summary_cek' => 'array',
        ];
    }

    public function komoditas(): BelongsTo
    {
        return $this->belongsTo(Komoditas::class);
    }

    public function entitas(): BelongsTo
    {
        return $this->belongsTo(Entitas::class, 'entitas_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Sudah lewat tanggal_berakhir-nya, terlepas dari apakah status di database sudah sempat
     * diperbarui jadi Expired oleh job harian atau belum (lihat KomoditasEntitasService).
     */
    public function isPastExpiryDate(): bool
    {
        return $this->tanggal_berakhir->isPast();
    }

    /**
     * Verified tanpa verified_by berarti KomoditasEntitasAutoVerifikasiService yang meloloskannya
     * (semua pemeriksaan di summary_cek lolos), bukan verifikasi manual oleh user - lihat
     * claude/otomatis-verifikasi.md.
     */
    public function isVerifiedOtomatis(): bool
    {
        return $this->status === KomoditasEntitasStatus::Verified && $this->verified_by === null;
    }
}
