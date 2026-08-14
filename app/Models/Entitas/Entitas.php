<?php

declare(strict_types=1);

namespace App\Models\Entitas;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Kota;
use App\Models\Provinsi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entitas extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'entities';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'entity_type',
        'name',
        'address',
        'provinsi_id',
        'kota_id',
        'kecamatan_id',
        'kelurahan_id',
    ];

    public function provinsi(): BelongsTo
    {
        return $this->belongsTo(Provinsi::class);
    }

    public function kota(): BelongsTo
    {
        return $this->belongsTo(Kota::class);
    }

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }

    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(Kelurahan::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'entity_id');
    }
}
