<div>
    <label class="inline-block mb-2 text-sm font-medium" for="{{ $formId }}-file">{{ __('File Template (PDF/DOC/DOCX, maks. 5 MB)') }}</label>
    @if ($item)
        <p class="mb-2 text-xs text-default-500">
            @include('panduan.partials.template-view-button', [
                'template' => $item,
                'class' => 'text-primary hover:underline inline-flex items-center gap-1',
                'label' => $item->original_filename ?: __('File saat ini'),
                'viewerId' => 'template-viewer-modal',
            ])
            — {{ __('kosongkan jika tidak diubah') }}
        </p>
    @endif
    <input accept=".pdf,.doc,.docx" class="form-input" id="{{ $formId }}-file" name="file" type="file"/>
    @if ($isRedisplay && $errors->has('file'))
        <p class="mt-1 text-xs text-danger">{{ $errors->first('file') }}</p>
    @endif
</div>
