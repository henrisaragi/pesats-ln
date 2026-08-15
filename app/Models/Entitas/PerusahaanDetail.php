<?php

declare(strict_types=1);

namespace App\Models\Entitas;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerusahaanDetail extends Model
{
    use HasFactory;

    protected $table = 'perusahaan_details';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'entity_id',
        'npwp',
        'nib',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entitas::class, 'entity_id');
    }
}
