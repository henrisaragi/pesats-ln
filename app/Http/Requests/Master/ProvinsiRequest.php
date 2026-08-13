<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProvinsiRequest extends FormRequest
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
        $provinsiId = $this->route('provinsi')?->id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('provinsi', 'code')->ignore($provinsiId),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
