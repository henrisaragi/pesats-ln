<?php

declare(strict_types=1);

namespace App\Http\Requests\PermohonanExport;

use App\Enums\DocumentType;
use App\Enums\JenisPermohonan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermohonanRequest extends FormRequest
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
            'document_id' => ['required', 'integer', Rule::exists('documents', 'id')
                ->where('entity_id', $this->user()?->entity_id)
                ->where('type_document', DocumentType::Permohonan->value)
                ->whereNull('permohonan_id')],
            'jenis_permohonan' => ['required', Rule::enum(JenisPermohonan::class)],
            'year' => ['nullable', 'integer', 'between:2000,2099'],
        ];
    }
}
