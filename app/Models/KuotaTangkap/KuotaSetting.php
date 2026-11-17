<?php

declare(strict_types=1);

namespace App\Models\KuotaTangkap;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pengaturan global (satu baris) untuk semua komoditas: bobot ekspor/evaluasi dan cadangan,
 * disimpan sebagai pecahan (0.3000 = 30%). Nilainya di-snapshot setiap kali periode dihitung.
 */
class KuotaSetting extends Model
{
    protected $table = 'kuota_settings';

    protected $fillable = [
        'bobot_ekspor',
        'bobot_evaluasi',
        'cadangan',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'bobot_ekspor' => 'decimal:4',
            'bobot_evaluasi' => 'decimal:4',
            'cadangan' => 'decimal:4',
        ];
    }

    public static function current(): self
    {
        return static::query()->oldest('id')->first()
            ?? static::query()->create(['bobot_ekspor' => '0.3000', 'bobot_evaluasi' => '0.7000', 'cadangan' => '0.1000']);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
