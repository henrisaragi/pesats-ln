<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\DocumentType;
use App\Models\Country;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $type = DocumentType::tryFrom((string) $this->input('type_document'));

        // Selain Export Permit/Import Permit/Country of Origin, negara penerbit dikunci ke
        // Indonesia di server terlepas dari apa yang dikirim form (field-nya disembunyikan di UI,
        // tapi tetap dipaksa ulang di sini supaya tidak bisa diakali lewat POST langsung).
        if ($type !== null && ! $type->allowsFreeCountrySelection()) {
            $this->merge(['issued_country_id' => Country::query()->where('iso_code', 'ID')->value('id')]);
        }

        // Tanpa permission documents.view-all, field Perusahaan dikunci ke entitas user sendiri
        // di UI (select disabled + hidden input) - dipaksa ulang di sini juga supaya tidak bisa
        // diakali lewat POST langsung ke entitas lain.
        $actor = $this->user();

        if ($actor && ! $actor->can('documents.view-all')) {
            $this->merge(['entity_id' => $actor->entity_id]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'entity_id' => ['required', 'integer', 'exists:entities,id'],
            'permohonan_id' => ['nullable', 'integer', 'exists:permohonan,id'],
            'sats_ln_ids' => ['nullable', 'array'],
            'sats_ln_ids.*' => ['integer', 'exists:sats_ln,id'],
            'nomor_document' => ['required', 'string', 'max:255'],
            'type_document' => ['required', Rule::enum(DocumentType::class)],
            'issued_date' => ['required', 'date'],
            'issued_country_id' => ['required', 'integer', 'exists:countries,id'],
            'file' => [$this->isMethod('POST') ? 'required' : 'nullable', 'file', 'mimes:pdf', 'max:5120'],
        ];
    }
}
