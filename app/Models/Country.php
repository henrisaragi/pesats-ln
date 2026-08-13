<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory;

    protected $table = 'countries';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'iso_code',
        'iso_code3',
        'phone_code',
    ];

    public function ports(): HasMany
    {
        return $this->hasMany(Port::class);
    }
}
