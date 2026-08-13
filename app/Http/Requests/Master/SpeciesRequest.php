<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SpeciesRequest extends FormRequest
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
        $speciesId = $this->route('species')?->id;

        return [
            'komoditas_id' => ['required', 'integer', 'exists:komoditas,id'],
            'scientific_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('species', 'scientific_name')->ignore($speciesId),
            ],
            'common_name' => ['nullable', 'string', 'max:255'],
            'local_name' => ['nullable', 'string', 'max:255'],
            'trade_status' => ['required', Rule::in(['I', 'II', 'III', 'Non Appendix'])],
        ];
    }
}
