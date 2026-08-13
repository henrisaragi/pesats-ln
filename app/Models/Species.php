<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Species extends Model
{
    use HasFactory;

    protected $table = 'species';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'komoditas_id',
        'scientific_name',
        'common_name',
        'local_name',
        'trade_status',
    ];

    public function komoditas(): BelongsTo
    {
        return $this->belongsTo(Komoditas::class);
    }
}
