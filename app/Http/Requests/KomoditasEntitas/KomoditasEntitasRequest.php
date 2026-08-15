<?php

declare(strict_types=1);

namespace App\Http\Requests\KomoditasEntitas;

use Illuminate\Foundation\Http\FormRequest;

class KomoditasEntitasRequest extends FormRequest
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
            'file' => ['required', 'file', 'mimes:pdf', 'max:5120'],
            'tanggal_terbit' => ['required', 'date'],
        ];
    }
}
