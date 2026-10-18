<?php

declare(strict_types=1);

namespace App\Http\Requests\PermohonanExport;

use App\Enums\MasaBerlakuSatsLn;
use App\Enums\PermohonanStatus;
use App\Models\Permohonan;
use App\Models\SatsLn;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class VerifySatsLnRequest extends FormRequest
{
    /**
     * Verifikasi per SATS-LN hanya boleh saat Permohonan masih di tahap verifikasi dan SATS-LN
     * tersebut belum pernah diverifikasi (nomor yang sudah terbit tidak boleh ditimpa).
     */
    public function authorize(): bool
    {
        $permohonan = $this->permohonan();
        $satsLn = $this->satsLn();

        abort_unless($satsLn->permohonan_id === $permohonan->id, 404);

        return in_array($permohonan->status, [PermohonanStatus::AntrianVerifikasi, PermohonanStatus::DalamProsesVerifikasi], true)
            && ! $satsLn->isVerified();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Field verifikasi hanya divalidasi saat verifikator setuju; saat tidak setuju cukup pesan.
        $whenAgree = 'exclude_unless:setuju,1';

        return [
            'setuju' => ['required', 'boolean'],
            'pesan' => ['exclude_unless:setuju,0', 'required', 'string', 'max:5000'],
            'satsln_number' => [$whenAgree, 'required', 'regex:/^\d{1,5}$/', 'not_regex:/^0+$/'],
            'year' => [$whenAgree, 'required', 'integer', 'between:2000,2099'],
            'security_stamp' => [$whenAgree, Rule::requiredIf(fn (): bool => $this->satsLn()->loadMissing('items.species')->requiresSecurityStamp()), 'nullable', 'string', 'max:255'],
            'masa_berlaku' => [$whenAgree, 'required', Rule::enum(MasaBerlakuSatsLn::class)],
            'publish_date' => [$whenAgree, 'required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'special_conditions' => [$whenAgree, 'nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->isAgreed() || $validator->errors()->hasAny(['setuju', 'satsln_number', 'year'])) {
                    return;
                }

                if (SatsLn::issuedNumberExists($this->satslnNumberIssued(), $this->satsLn()->id)) {
                    $validator->errors()->add('satsln_number', __('Nomor telah digunakan.'));
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'setuju' => __('Persetujuan'),
            'pesan' => __('Pesan'),
            'satsln_number' => __('Nomor SATS-LN'),
            'security_stamp' => __('Security Stamp'),
            'masa_berlaku' => __('Interval Masa Berlaku'),
            'publish_date' => __('Tanggal Terbit'),
            'special_conditions' => __('Syarat Khusus'),
        ];
    }

    public function isAgreed(): bool
    {
        return $this->boolean('setuju');
    }

    public function satslnNumberIssued(): string
    {
        return SatsLn::composeIssuedNumber($this->integer('year'), $this->string('satsln_number')->toString());
    }

    private function permohonan(): Permohonan
    {
        return $this->route('permohonan');
    }

    private function satsLn(): SatsLn
    {
        return $this->route('satsLn');
    }
}
