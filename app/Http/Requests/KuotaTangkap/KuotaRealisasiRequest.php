<?php

declare(strict_types=1);

namespace App\Http\Requests\KuotaTangkap;

use Illuminate\Foundation\Http\FormRequest;

class KuotaRealisasiRequest extends FormRequest
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
            'realisasi_tangkap' => ['nullable', 'numeric', 'min:0', 'max:99999999999'],
            'sk_tangkap_nomor' => ['nullable', 'string', 'max:100'],
            'sk_tangkap_tanggal' => ['nullable', 'date'],
            'sk_tangkap_file' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ];
    }
}
