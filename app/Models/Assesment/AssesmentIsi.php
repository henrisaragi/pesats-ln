<?php

declare(strict_types=1);

namespace App\Models\Assesment;

use App\Enums\AssesmentIsiStatus;
use App\Models\Entitas\Entitas;
use App\Models\Komoditas;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu entitas "mengikuti" satu Assesment untuk satu komoditas - lihat claude/fitur-assesment.md.
 * Skor/bobot yang berlaku ditentukan oleh komoditas_id ini (setiap komoditas punya bobot & skor
 * berbeda), jadi entitas yang sama bisa punya banyak baris AssesmentIsi untuk Assesment yang sama
 * kalau komoditasnya berbeda.
 */
class AssesmentIsi extends Model
{
    use HasFactory;

    protected $table = 'assesment_isi';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'assesment_id',
        'entitas_id',
        'komoditas_id',
        'status',
        'submitted_at',
        'verified_at',
        'verified_by',
        'skor_total',
        'skor_entitas',
        'sertifikat_path',
        'surat_pernyataan_path',
        'surat_pernyataan_filename',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AssesmentIsiStatus::class,
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'skor_total' => 'decimal:2',
            'skor_entitas' => 'decimal:2',
        ];
    }

    public function assesment(): BelongsTo
    {
        return $this->belongsTo(Assesment::class, 'assesment_id');
    }

    public function entitas(): BelongsTo
    {
        return $this->belongsTo(Entitas::class, 'entitas_id');
    }

    public function komoditas(): BelongsTo
    {
        return $this->belongsTo(Komoditas::class, 'komoditas_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function jawaban(): HasMany
    {
        return $this->hasMany(AssesmentIsiJawaban::class, 'isi_id');
    }

    public function bukti(): HasMany
    {
        return $this->hasMany(AssesmentIsiBukti::class, 'isi_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(AssesmentIsiActivity::class, 'isi_id');
    }

    /**
     * Verifikator boleh memverifikasi assesment Submitted dan merevisi yang sudah Verified
     * (perubahan dicatat di activities, sertifikat diterbitkan ulang).
     */
    public function bisaDiverifikasi(): bool
    {
        return in_array($this->status, [AssesmentIsiStatus::Submitted, AssesmentIsiStatus::Verified], true);
    }
}
