<?php

declare(strict_types=1);

namespace App\Models\Assesment;

use App\Models\Komoditas;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssesmentPertanyaan extends Model
{
    use HasFactory;

    protected $table = 'assesment_pertanyaan';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'assesment_id',
        'aspek_id',
        'pertanyaan',
        'informasi',
        'metode_verifikasi',
        'urutan',
        'created_by',
    ];

    public function assesment(): BelongsTo
    {
        return $this->belongsTo(Assesment::class, 'assesment_id');
    }

    public function aspek(): BelongsTo
    {
        return $this->belongsTo(AssesmentAspek::class, 'aspek_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function jawaban(): HasMany
    {
        return $this->hasMany(AssesmentJawaban::class, 'pertanyaan_id')->orderBy('urutan');
    }

    public function buktiDukung(): HasMany
    {
        return $this->hasMany(AssesmentBuktiDukung::class, 'pertanyaan_id')->orderBy('urutan');
    }

    /**
     * Komoditas yang dicakup pertanyaan ini, dengan bobot (%) masing-masing - lihat
     * assesment_pertanyaan_komoditas.
     */
    public function komoditas(): BelongsToMany
    {
        return $this->belongsToMany(Komoditas::class, 'assesment_pertanyaan_komoditas', 'pertanyaan_id', 'komoditas_id')
            ->withPivot('bobot')
            ->withTimestamps();
    }
}
