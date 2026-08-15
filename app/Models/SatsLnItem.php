<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SatsLnItem extends Model
{
    use HasFactory;

    protected $table = 'sats_ln_items';

    protected $fillable = [
        'sats_ln_id',
        'kuota_nasional_id',
        'kuota_perusahaan_id',
        'species_id',
        'harga_patokan_id',
        'qty',
        'realisasi_on_port',
        'peb_or_pib',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:2',
            'realisasi_on_port' => 'decimal:2',
        ];
    }

    public function satsLn(): BelongsTo
    {
        return $this->belongsTo(SatsLn::class);
    }

    public function kuotaNasional(): BelongsTo
    {
        return $this->belongsTo(KuotaNasional::class);
    }

    public function kuotaPerusahaan(): BelongsTo
    {
        return $this->belongsTo(KuotaPerusahaan::class);
    }

    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    public function hargaPatokan(): BelongsTo
    {
        return $this->belongsTo(HargaPatokan::class);
    }
}
