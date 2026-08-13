<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KecamatanRequest extends FormRequest
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
        $kecamatanId = $this->route('kecamatan')?->id;

        return [
            'kota_id' => ['required', 'integer', 'exists:kota,id'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('kecamatan', 'code')->ignore($kecamatanId),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
