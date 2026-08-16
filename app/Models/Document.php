<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentType;
use App\Models\Entitas\Entitas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'documents';

    protected $fillable = [
        'entity_id',
        'created_by_id',
        'permohonan_id',
        'nomor_document',
        'type_document',
        'issued_date',
        'issued_country_id',
        'path',
    ];

    protected function casts(): array
    {
        return [
            'type_document' => DocumentType::class,
            'issued_date' => 'date',
        ];
    }

    public function entitas(): BelongsTo
    {
        return $this->belongsTo(Entitas::class, 'entity_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class);
    }

    public function issuedCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'issued_country_id');
    }

    public function satsLn(): BelongsToMany
    {
        return $this->belongsToMany(SatsLn::class, 'document_sats_ln');
    }
}
