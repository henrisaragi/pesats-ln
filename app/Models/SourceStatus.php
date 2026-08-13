<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SourceStatus extends Model
{
    use HasFactory;

    protected $table = 'source_statuses';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'description',
    ];
}
