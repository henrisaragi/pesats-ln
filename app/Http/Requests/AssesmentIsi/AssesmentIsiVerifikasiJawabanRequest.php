<?php

declare(strict_types=1);

namespace App\Http\Requests\AssesmentIsi;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Simpan koreksi verifikator untuk SATU pertanyaan (satu langkah wizard verifikasi).
 */
class AssesmentIsiVerifikasiJawabanRequest extends FormRequest
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
            'jawaban_id' => [
                'required',
                'integer',
                Rule::exists('assesment_jawaban', 'id')->where('pertanyaan_id', $this->route('pertanyaan')->id),
            ],
        ];
    }
}
