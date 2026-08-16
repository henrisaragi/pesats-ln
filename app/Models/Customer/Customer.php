<?php

declare(strict_types=1);

namespace App\Models\Customer;

use App\Models\Country;
use App\Models\Entitas\Entitas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customer_companies';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'country_id',
        'address',
        'entity_id',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function entitas(): BelongsTo
    {
        return $this->belongsTo(Entitas::class, 'entity_id');
    }
}
