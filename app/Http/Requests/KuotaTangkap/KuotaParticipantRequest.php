<?php

declare(strict_types=1);

namespace App\Http\Requests\KuotaTangkap;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KuotaParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'entity_id' => [
                'required', 'integer',
                Rule::exists('entities', 'id')->where('entity_type', 'perusahaan')->whereNull('deleted_at'),
                Rule::unique('kuota_allocations', 'entity_id')->where('kuota_period_id', $this->route('period')->id),
            ],
            'permohonan' => ['nullable', 'numeric', 'min:0', 'max:99999999999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'entity_id.unique' => __('Perusahaan ini sudah menjadi peserta periode.'),
        ];
    }
}
