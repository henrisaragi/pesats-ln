<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SatsLnStatus;
use App\Models\Concerns\HasPublicId;
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
    use HasFactory, HasPublicId, SoftDeletes;

    protected $table = 'sats_ln';

    protected $fillable = [
        'permohonan_id',
        'nomor_urut',
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
        'status_satsln',
        'issued_by_id',
        'printed_user_id',
        'printed_at',
        'signed_at',
        'special_conditions',
        'security_stamp',
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
            'nomor_urut' => 'integer',
            'status_satsln' => SatsLnStatus::class,
        ];
    }

    public function isVerified(): bool
    {
        return $this->verified_id !== null;
    }

    /**
     * Verifikasi boleh dibuka ulang (untuk mengubah data) selama SATS-LN belum dicetak.
     */
    public function canBeReopened(): bool
    {
        return $this->isVerified() && $this->printed_at === null;
    }

    /**
     * Penandatanganan hanya untuk SATS-LN yang sudah dicetak dan belum ditandatangani.
     */
    public function canBeMarkedSigned(): bool
    {
        return $this->printed_at !== null && $this->signed_at === null;
    }

    /**
     * Security Stamp wajib (dan baru ditampilkan) kalau ada spesies Appendix I/II/III di SATS-LN
     * ini - spesies Non Appendix tidak memakai stamp. Butuh relasi items.species.
     */
    public function requiresSecurityStamp(): bool
    {
        return $this->items->contains(
            fn (SatsLnItem $item): bool => $item->species !== null && $item->species->trade_status !== 'Non Appendix'
        );
    }

    /**
     * Nomor tersimpan = 2 digit terakhir Tahun Aktif + 5 digit nomor, mis. 2026 + "1" => "2600001".
     */
    public static function composeIssuedNumber(int $year, string $number): string
    {
        return substr((string) $year, -2).str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    public static function issuedNumberExists(string $satslnNumberIssued, ?int $exceptSatsLnId = null): bool
    {
        return self::withTrashed()
            ->where('satsln_number_issued', $satslnNumberIssued)
            ->when($exceptSatsLnId !== null, fn ($query) => $query->whereKeyNot($exceptSatsLnId))
            ->exists();
    }

    /**
     * 5 digit nomor tanpa prefix tahun, mis. "2600001" => "00001" (untuk mengisi ulang modal).
     */
    public function issuedNumberWithoutPrefix(): ?string
    {
        return $this->satsln_number_issued === null ? null : substr($this->satsln_number_issued, 2);
    }

    /**
     * Nomor tampil SATS-LN, mis. "00001/IV/SATS-LN/2026" - satsln_number_issued tersimpan dengan
     * prefix 2 digit tahun ("2600001"), prefix itu dibuang di sini.
     */
    public function issuedNumberLabel(): ?string
    {
        if ($this->satsln_number_issued === null) {
            return null;
        }

        return substr($this->satsln_number_issued, 2).'/IV/SATS-LN/'.$this->year;
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

    public function prints(): HasMany
    {
        return $this->hasMany(SatsLnPrint::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ActivitySatsLn::class);
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'document_sats_ln');
    }
}
