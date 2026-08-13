<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeCode extends Model
{
    use HasFactory;

    protected $table = 'trade_codes';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'unit',
        'description',
    ];
}
