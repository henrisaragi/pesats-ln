<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HargaPatokan extends Model
{
    use HasFactory;

    protected $table = 'harga_patokan';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'source_id',
        'trade_code_id',
        'species_id',
        'harga',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(SourceStatus::class, 'source_id');
    }

    public function tradeCode(): BelongsTo
    {
        return $this->belongsTo(TradeCode::class);
    }

    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }
}
