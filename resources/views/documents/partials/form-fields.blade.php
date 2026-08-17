@php
    $formId = $document?->id ?? 'new';
    $isRedisplay = old('_modal') === $modalId;
    // Perusahaan default ke entitas milik user login (kalau ada) saat membuat dokumen baru -
    // lihat claude/document-task.md Revisi.
    $defaultEntityId = $document?->entity_id ?? auth()->user()?->entity_id;
    $showCountry = $document?->type_document?->allowsFreeCountrySelection() ?? false;
    $canSelectAnyEntity = auth()->user()->can('documents.view-all');
@endphp

{{-- Sengaja tanpa overflow-y-auto/max-h: itu meng-clip dropdown Choices.js, lihat claude/permohonan-task.md. --}}
<div class="p-4">
    <div class="flex flex-col gap-y-4">
        <div>
            <label class="inline-block mb-2 text-sm font-medium" for="document-{{ $formId }}-entity">
                {{ __('Perusahaan') }}
            </label>
            @if ($canSelectAnyEntity)
                <select class="form-input" data-choice id="document-{{ $formId }}-entity" name="entity_id">
                    <option value="">{{ __('Pilih Perusahaan') }}</option>
                    @foreach ($entitasOptions as $option)
                        <option value="{{ $option->id }}"
                                @selected($isRedisplay ? (int) old('entity_id') === $option->id : $defaultEntityId === $option->id)>
                            {{ $option->name }}
                        </option>
                    @endforeach
                </select>
            @else
                <select class="form-input bg-default-100 text-default-500 cursor-not-allowed" disabled>
                    <option selected>{{ auth()->user()->entity?->name ?? __('Tidak ada entitas') }}</option>
                </select>
                <input name="entity_id" type="hidden" value="{{ auth()->user()->entity_id }}">
            @endif
            @if ($isRedisplay && $errors->has('entity_id'))
                <p class="mt-1 text-xs text-danger">{{ $errors->first('entity_id') }}</p>
            @endif
        </div>
        <div>
            <label class="inline-block mb-2 text-sm font-medium" for="document-{{ $formId }}-nomor">
                {{ __('Nomor Dokumen') }}
            </label>
            <input class="form-input" id="document-{{ $formId }}-nomor" name="nomor_document" type="text"
                   value="{{ $isRedisplay ? old('nomor_document') : $document?->nomor_document }}"/>
            @if ($isRedisplay && $errors->has('nomor_document'))
                <p class="mt-1 text-xs text-danger">{{ $errors->first('nomor_document') }}</p>
            @endif
        </div>
        <div>
            <label class="inline-block mb-2 text-sm font-medium" for="document-{{ $formId }}-type">
                {{ __('Tipe Dokumen') }}
            </label>
            <select class="form-input" data-choice data-document-type-select
                    data-country-wrapper="document-{{ $formId }}-country-wrapper"
                    id="document-{{ $formId }}-type" name="type_document">
                <option value="">{{ __('Pilih Tipe Dokumen') }}</option>
                @foreach ($documentTypeOptions as $option)
                    <option value="{{ $option->value }}"
                            @selected($isRedisplay ? old('type_document') === $option->value : $document?->type_document === $option)>
                        {{ $option->label() }}
                    </option>
                @endforeach
            </select>
            @if ($isRedisplay && $errors->has('type_document'))
                <p class="mt-1 text-xs text-danger">{{ $errors->first('type_document') }}</p>
            @endif
        </div>
        <div>
            <label class="inline-block mb-2 text-sm font-medium" for="document-{{ $formId }}-issued-date">
                {{ __('Tanggal Terbit') }}
            </label>
            <input class="form-input" id="document-{{ $formId }}-issued-date" name="issued_date" type="date"
                   value="{{ $isRedisplay ? old('issued_date') : $document?->issued_date?->format('Y-m-d') }}"/>
            @if ($isRedisplay && $errors->has('issued_date'))
                <p class="mt-1 text-xs text-danger">{{ $errors->first('issued_date') }}</p>
            @endif
        </div>
        <div id="document-{{ $formId }}-country-wrapper" class="{{ $showCountry ? '' : 'hidden' }}">
            <label class="inline-block mb-2 text-sm font-medium" for="document-{{ $formId }}-country">
                {{ __('Negara Penerbit') }}
            </label>
            <select class="form-input" data-choice id="document-{{ $formId }}-country" name="issued_country_id">
                @foreach ($countryOptions as $option)
                    <option value="{{ $option->id }}"
                            @selected($isRedisplay ? (int) old('issued_country_id') === $option->id : ($document?->issued_country_id ?? $indonesiaCountryId) === $option->id)>
                        {{ $option->name }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-default-400">
                {{ __('Hanya tampil untuk tipe dokumen Export Permit, Import Permit, atau Country of Origin - selain itu otomatis Indonesia.') }}
            </p>
            @if ($isRedisplay && $errors->has('issued_country_id'))
                <p class="mt-1 text-xs text-danger">{{ $errors->first('issued_country_id') }}</p>
            @endif
        </div>
        <div>
            <label class="inline-block mb-2 text-sm font-medium" for="document-{{ $formId }}-file">
                {{ __('File Dokumen (PDF, maks. 5MB)') }}
            </label>
            @if ($document)
                <p class="mb-2 text-xs text-default-500">
                    <button class="text-primary hover:underline inline-flex items-center gap-1"
                            data-document-viewer-download-url="{{ route('documents.download', $document) }}"
                            data-document-viewer-url="{{ route('documents.preview', $document) }}"
                            data-document-viewer-title="{{ $document->nomor_document }}"
                            data-hs-overlay="#document-viewer-modal" type="button">
                        <i class="size-3.5" data-lucide="file-text"></i>{{ __('Lihat Dokumen Saat Ini') }}
                    </button>
                    — {{ __('kosongkan jika tidak diubah') }}
                </p>
            @endif
            <input accept="application/pdf" class="form-input" id="document-{{ $formId }}-file" name="file"
                   type="file"/>
            @if ($isRedisplay && $errors->has('file'))
                <p class="mt-1 text-xs text-danger">{{ $errors->first('file') }}</p>
            @endif
        </div>
    </div>
</div>
