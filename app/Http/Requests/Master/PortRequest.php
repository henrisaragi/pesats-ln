<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PortRequest extends FormRequest
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
        $portId = $this->route('port')?->id;

        return [
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('ports', 'code')->ignore($portId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['sea', 'air'])],
        ];
    }
}
