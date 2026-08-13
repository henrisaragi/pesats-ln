<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Komoditas extends Model
{
    use HasFactory;

    protected $table = 'komoditas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
    ];

    public function species(): HasMany
    {
        return $this->hasMany(Species::class);
    }
}
