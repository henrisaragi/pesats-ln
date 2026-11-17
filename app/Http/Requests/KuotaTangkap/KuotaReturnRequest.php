<?php

declare(strict_types=1);

namespace App\Http\Requests\KuotaTangkap;

use Illuminate\Foundation\Http\FormRequest;

class KuotaReturnRequest extends FormRequest
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
            'catatan_pengembalian' => ['required', 'string', 'max:2000'],
        ];
    }
}
