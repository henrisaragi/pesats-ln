<?php

declare(strict_types=1);

namespace App\Models\Assesment;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan aktivitas verifikasi AssesmentIsi: setiap perubahan jawaban verifikator
 * (jawaban_diubah), penyelesaian verifikasi pertama (diverifikasi) dan revisi setelah
 * terverifikasi (direvisi - skor dihitung ulang & sertifikat diterbitkan ulang).
 */
class AssesmentIsiActivity extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'assesment_isi_activities';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'isi_id',
        'pertanyaan_id',
        'user_id',
        'action',
        'old_values',
        'new_values',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'jawaban_diubah' => __('Jawaban diubah'),
            'diverifikasi' => __('Diverifikasi'),
            'direvisi' => __('Verifikasi direvisi'),
            default => $this->action,
        };
    }

    public function isi(): BelongsTo
    {
        return $this->belongsTo(AssesmentIsi::class, 'isi_id');
    }

    public function pertanyaan(): BelongsTo
    {
        return $this->belongsTo(AssesmentPertanyaan::class, 'pertanyaan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
