<?php

declare(strict_types=1);

namespace App\Http\Requests\PermohonanExport;

use App\Models\Permohonan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSignedStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * SATS-LN yang dicentang harus milik Permohonan ini, sudah dicetak, dan belum ditandatangani.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Permohonan $permohonan */
        $permohonan = $this->route('permohonan');

        return [
            'sats_ln_ids' => ['required', 'array', 'min:1'],
            'sats_ln_ids.*' => ['required', 'string', 'distinct', Rule::exists('sats_ln', 'public_id')
                ->where('permohonan_id', $permohonan->id)
                ->whereNotNull('printed_at')
                ->whereNull('signed_at')
                ->whereNull('deleted_at')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sats_ln_ids' => __('SATS-LN'),
            'sats_ln_ids.*' => __('SATS-LN'),
        ];
    }
}
