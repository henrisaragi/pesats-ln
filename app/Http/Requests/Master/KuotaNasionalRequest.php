<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class KuotaNasionalRequest extends FormRequest
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
            'komoditas_id' => ['required', 'integer', 'exists:komoditas,id'],
            'species_id' => ['required', 'integer', 'exists:species,id'],
            'source_id' => ['required', 'integer', 'exists:source_statuses,id'],
            'trade_code_id' => ['required', 'integer', 'exists:trade_codes,id'],
            'quota' => ['required', 'numeric', 'min:0'],
            'year' => ['required', 'integer', 'digits:4'],
        ];
    }
}
