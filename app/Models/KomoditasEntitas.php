<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\KomoditasEntitasStatus;
use App\Models\Entitas\Entitas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KomoditasEntitas extends Model
{
    use HasFactory;

    protected $table = 'komoditas_entitas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'komoditas_id',
        'entitas_id',
        'file_path',
        'tanggal_terbit',
        'tanggal_berakhir',
        'status',
        'verified_by',
        'verified_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_terbit' => 'date',
            'tanggal_berakhir' => 'date',
            'status' => KomoditasEntitasStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    public function komoditas(): BelongsTo
    {
        return $this->belongsTo(Komoditas::class);
    }

    public function entitas(): BelongsTo
    {
        return $this->belongsTo(Entitas::class, 'entitas_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
