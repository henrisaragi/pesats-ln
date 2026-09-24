<?php

declare(strict_types=1);

namespace App\Models\Assesment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssesmentBuktiDukung extends Model
{
    use HasFactory;

    protected $table = 'assesment_bukti_dukung';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'pertanyaan_id',
        'label',
        'urutan',
    ];

    public function pertanyaan(): BelongsTo
    {
        return $this->belongsTo(AssesmentPertanyaan::class, 'pertanyaan_id');
    }
}
