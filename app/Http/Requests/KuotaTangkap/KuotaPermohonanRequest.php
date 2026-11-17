<?php

declare(strict_types=1);

namespace App\Http\Requests\KuotaTangkap;

use Illuminate\Foundation\Http\FormRequest;

class KuotaPermohonanRequest extends FormRequest
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
            'permohonan' => ['nullable', 'numeric', 'min:0', 'max:99999999999'],
        ];
    }
}
