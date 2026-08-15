<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurposeOfTransaction extends Model
{
    use HasFactory;

    protected $table = 'purpose_of_transactions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
    ];
}
