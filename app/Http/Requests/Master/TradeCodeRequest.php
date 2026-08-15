<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TradeCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['rounding_enabled' => $this->boolean('rounding_enabled')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tradeCodeId = $this->route('tradeCode')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('trade_codes', 'code')->ignore($tradeCodeId),
            ],
            'unit' => ['nullable', 'string', 'max:50'],
            'unit_name' => ['nullable', 'string', 'max:255'],
            'decimal_places' => ['required', 'integer', Rule::in([0, 1, 2, 3, 4])],
            'rounding_enabled' => ['boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
