<?php

declare(strict_types=1);

namespace App\Http\Requests\AssesmentIsi;

use Illuminate\Foundation\Http\FormRequest;

class AssesmentIsiStoreRequest extends FormRequest
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
            'assesment_id' => ['required', 'integer', 'exists:assesments,id'],
            'komoditas_id' => ['required', 'integer', 'exists:komoditas,id'],
            'surat_pernyataan' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'surat_pernyataan' => __('Surat Pernyataan'),
        ];
    }
}
