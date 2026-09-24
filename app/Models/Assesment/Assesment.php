<?php

declare(strict_types=1);

namespace App\Models\Assesment;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assesment extends Model
{
    use HasFactory;

    protected $table = 'assesments';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'judul',
        'created_by',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pertanyaan(): HasMany
    {
        return $this->hasMany(AssesmentPertanyaan::class, 'assesment_id');
    }

    public function isi(): HasMany
    {
        return $this->hasMany(AssesmentIsi::class, 'assesment_id');
    }
}
