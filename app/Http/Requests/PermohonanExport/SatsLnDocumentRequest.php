<?php

declare(strict_types=1);

namespace App\Http\Requests\PermohonanExport;

use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Entitas\Entitas;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SatsLnDocumentRequest extends FormRequest
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
            'document_id' => ['required', 'integer', 'exists:documents,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var Entitas $entitas */
            $entitas = Auth::user()->entity;

            $document = Document::find($this->integer('document_id'));

            if ($document === null || $document->entity_id !== $entitas->id) {
                $validator->errors()->add(
                    'document_id',
                    __('Dokumen tidak valid untuk entitas ini.')
                );

                return;
            }

            // Dokumen bertipe Permohonan sudah dipilih di awal pembuatan Permohonan (lihat
            // Revisi Permohonan 1) - bukan untuk dilampirkan lagi ke SATS-LN.
            if ($document->type_document === DocumentType::Permohonan) {
                $validator->errors()->add(
                    'document_id',
                    __('Dokumen bertipe Permohonan tidak bisa dilampirkan ke SATS-LN.')
                );

                return;
            }

            $satsLn = $this->route('satsLn');

            if ($satsLn?->documents()->where('documents.id', $document->id)->exists()) {
                $validator->errors()->add(
                    'document_id',
                    __('Dokumen ini sudah dilampirkan ke SATS-LN ini.')
                );
            }
        });
    }
}
