<?php

declare(strict_types=1);

namespace App\Models\Assesment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssesmentIsiBukti extends Model
{
    use HasFactory;

    protected $table = 'assesment_isi_bukti';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'isi_id',
        'bukti_dukung_id',
        'file_path',
        'original_filename',
    ];

    public function isi(): BelongsTo
    {
        return $this->belongsTo(AssesmentIsi::class, 'isi_id');
    }

    public function buktiDukung(): BelongsTo
    {
        return $this->belongsTo(AssesmentBuktiDukung::class, 'bukti_dukung_id');
    }
}
