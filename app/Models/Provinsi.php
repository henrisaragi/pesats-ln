<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provinsi extends Model
{
    use HasFactory;

    protected $table = 'provinsi';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
    ];

    public function kota(): HasMany
    {
        return $this->hasMany(Kota::class);
    }
}
