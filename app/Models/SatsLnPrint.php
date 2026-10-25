<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SatsLnPrint extends Model
{
    use HasPublicId;

    /**
     * Margin (cm) = tinggi baris kosong terakhir tabel spesies - nilai awal input Margin di modal Print/Cetak.
     */
    public const DEFAULT_SISA_TINGGI = 3;

    public const MAX_SISA_TINGGI = 15;

    protected $fillable = [
        'sats_ln_id',
        'penandatangan_id',
        'printed_by_id',
        'sisa_tinggi',
        'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'sisa_tinggi' => 'decimal:2',
        ];
    }

    public function satsLn(): BelongsTo
    {
        return $this->belongsTo(SatsLn::class);
    }

    public function penandatangan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penandatangan_id');
    }

    public function printedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by_id');
    }
}
