<?php

declare(strict_types=1);

namespace App\Models\Assesment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jawaban entitas untuk satu pertanyaan tetap tersimpan (jawaban_entitas_id) sebagai pembanding
 * meski verifikator mengoreksi (jawaban_verifikator_id) - lihat claude/fitur-assesment.md.
 */
class AssesmentIsiJawaban extends Model
{
    use HasFactory;

    protected $table = 'assesment_isi_jawaban';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'isi_id',
        'pertanyaan_id',
        'jawaban_entitas_id',
        'jawaban_verifikator_id',
    ];

    public function isi(): BelongsTo
    {
        return $this->belongsTo(AssesmentIsi::class, 'isi_id');
    }

    public function pertanyaan(): BelongsTo
    {
        return $this->belongsTo(AssesmentPertanyaan::class, 'pertanyaan_id');
    }

    public function jawabanEntitas(): BelongsTo
    {
        return $this->belongsTo(AssesmentJawaban::class, 'jawaban_entitas_id');
    }

    public function jawabanVerifikator(): BelongsTo
    {
        return $this->belongsTo(AssesmentJawaban::class, 'jawaban_verifikator_id');
    }

    /**
     * Jawaban yang dipakai untuk perhitungan skor: koreksi verifikator kalau ada, kalau tidak
     * jawaban asli entitas.
     */
    public function jawabanFinalId(): ?int
    {
        return $this->jawaban_verifikator_id ?? $this->jawaban_entitas_id;
    }

    public function isSesuai(): bool
    {
        return $this->jawaban_verifikator_id === null
            || $this->jawaban_verifikator_id === $this->jawaban_entitas_id;
    }
}
