<?php

declare(strict_types=1);

namespace App\Http\Requests\Entitas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EntitasRequest extends FormRequest
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
            'entity_type' => ['required', Rule::in(['perusahaan', 'penangkaran', 'perorangan', 'upt_ksda'])],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'provinsi_id' => ['nullable', 'integer', 'exists:provinsi,id'],
            'kota_id' => ['nullable', 'integer', 'exists:kota,id'],
            'kecamatan_id' => ['nullable', 'integer', 'exists:kecamatan,id'],
            'kelurahan_id' => ['nullable', 'integer', 'exists:kelurahan,id'],
        ];
    }
}
