<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KelurahanRequest extends FormRequest
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
        $kelurahanId = $this->route('kelurahan')?->id;

        return [
            'kecamatan_id' => ['required', 'integer', 'exists:kecamatan,id'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('kelurahan', 'code')->ignore($kelurahanId),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
