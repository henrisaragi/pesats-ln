<?php

declare(strict_types=1);

namespace App\Http\Requests\AssesmentIsi;

use Illuminate\Foundation\Http\FormRequest;

class AssesmentIsiJawabanRequest extends FormRequest
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
            'jawaban' => ['required', 'array'],
            'jawaban.*' => ['required', 'integer', 'exists:assesment_jawaban,id'],
        ];
    }
}
