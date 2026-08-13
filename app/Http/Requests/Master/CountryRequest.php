<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CountryRequest extends FormRequest
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
        $countryId = $this->route('country')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'iso_code' => [
                'required',
                'string',
                'size:2',
                Rule::unique('countries', 'iso_code')->ignore($countryId),
            ],
            'iso_code3' => [
                'nullable',
                'string',
                'size:3',
                Rule::unique('countries', 'iso_code3')->ignore($countryId),
            ],
            'phone_code' => ['nullable', 'string', 'max:20'],
        ];
    }
}
