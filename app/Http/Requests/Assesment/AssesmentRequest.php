<?php

declare(strict_types=1);

namespace App\Http\Requests\Assesment;

use Illuminate\Foundation\Http\FormRequest;

class AssesmentRequest extends FormRequest
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
            'judul' => ['required', 'string', 'max:255'],
        ];
    }
}
