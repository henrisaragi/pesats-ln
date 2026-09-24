<?php

declare(strict_types=1);

namespace App\Models\Assesment;

use App\Models\Komoditas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AssesmentJawaban extends Model
{
    use HasFactory;

    protected $table = 'assesment_jawaban';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'pertanyaan_id',
        'teks_jawaban',
        'urutan',
    ];

    public function pertanyaan(): BelongsTo
    {
        return $this->belongsTo(AssesmentPertanyaan::class, 'pertanyaan_id');
    }

    /**
     * Skor jawaban ini per komoditas - lihat assesment_jawaban_komoditas.
     */
    public function komoditas(): BelongsToMany
    {
        return $this->belongsToMany(Komoditas::class, 'assesment_jawaban_komoditas', 'jawaban_id', 'komoditas_id')
            ->withPivot('skor')
            ->withTimestamps();
    }

    public function skorUntukKomoditas(int $komoditasId): ?float
    {
        $pivot = $this->komoditas->firstWhere('id', $komoditasId)?->pivot;

        return $pivot !== null ? (float) $pivot->skor : null;
    }
}
