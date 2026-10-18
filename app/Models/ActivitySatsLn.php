<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivitySatsLn extends Model
{
    protected $table = 'activity_satsln';

    protected $fillable = [
        'permohonan_id',
        'sats_ln_id',
        'user_id',
        'messages',
    ];

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class);
    }

    public function satsLn(): BelongsTo
    {
        return $this->belongsTo(SatsLn::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
