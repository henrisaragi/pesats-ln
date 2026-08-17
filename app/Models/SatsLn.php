<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Customer\Customer;
use App\Models\Entitas\Entitas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SatsLn extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sats_ln';

    protected $fillable = [
        'permohonan_id',
        'entity_id',
        'customer_company_id',
        'type_satsln',
        'country_of_exportation',
        'country_of_destination',
        'port_of_exportation',
        'port_of_destination',
        'purpose_of_transaction_id',
        'publish_date',
        'valid_until',
        'verified_id',
        'issued_by_id',
        'printed_user_id',
        'printed_at',
        'signed_at',
        'special_conditions',
        'satsln_issued_type',
        'satsln_number_issued',
        'year',
    ];

    protected function casts(): array
    {
        return [
            'publish_date' => 'date',
            'valid_until' => 'date',
            'printed_at' => 'datetime',
            'signed_at' => 'datetime',
            'year' => 'integer',
        ];
    }

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class);
    }

    public function entitas(): BelongsTo
    {
        return $this->belongsTo(Entitas::class, 'entity_id');
    }

    public function customerCompany(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_company_id');
    }

    public function countryOfExportation(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_of_exportation');
    }

    public function countryOfDestination(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_of_destination');
    }

    public function portOfExportation(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'port_of_exportation');
    }

    public function portOfDestination(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'port_of_destination');
    }

    public function purposeOfTransaction(): BelongsTo
    {
        return $this->belongsTo(PurposeOfTransaction::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_id');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_id');
    }

    public function printedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SatsLnItem::class);
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'document_sats_ln');
    }
}
