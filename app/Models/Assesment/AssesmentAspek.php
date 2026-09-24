<?php

declare(strict_types=1);

namespace App\Models\Assesment;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Master data global "Indikator/Aspek Penilaian" - dicari via typeahead lintas judul assesment,
 * dibuat otomatis (firstOrCreate case-insensitive by nama) saat admin mengetik nama baru ketika
 * membuat pertanyaan - lihat claude/fitur-assesment.md.
 */
class AssesmentAspek extends Model
{
    use HasFactory;

    protected $table = 'assesment_aspek';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nama',
        'created_by',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pertanyaan(): HasMany
    {
        return $this->hasMany(AssesmentPertanyaan::class, 'aspek_id');
    }
}
