<?php

declare(strict_types=1);

namespace App\Http\Requests\Panduan;

use App\Enums\FiturPanduan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TemplateSuratPernyataanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
            'fitur' => ['required', 'array', 'min:1'],
            'fitur.*' => ['required', 'distinct', Rule::enum(FiturPanduan::class)],
            'is_active' => ['boolean'],
            'file' => [$this->isMethod('POST') ? 'required' : 'nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ];
    }
}
