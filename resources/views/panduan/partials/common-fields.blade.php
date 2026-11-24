{{-- Field bersama Template Surat Pernyataan & Video Panduan: judul, keterangan, assign fitur, status aktif. --}}
@php
    $selectedFitur = $isRedisplay
        ? (array) old('fitur', [])
        : ($item?->fitur->map(fn ($fitur) => $fitur->value)->all() ?? []);
    $isActive = $isRedisplay ? (bool) old('is_active') : ($item?->is_active ?? true);
@endphp
<div>
    <label class="inline-block mb-2 text-sm font-medium" for="{{ $formId }}-judul">{{ __('Judul') }}</label>
    <input class="form-input" id="{{ $formId }}-judul" name="judul" type="text"
           value="{{ $isRedisplay ? old('judul') : $item?->judul }}"/>
    @if ($isRedisplay && $errors->has('judul'))
        <p class="mt-1 text-xs text-danger">{{ $errors->first('judul') }}</p>
    @endif
</div>
<div>
    <label class="inline-block mb-2 text-sm font-medium" for="{{ $formId }}-keterangan">{{ __('Keterangan') }}</label>
    <textarea class="form-input" id="{{ $formId }}-keterangan" name="keterangan" rows="2">{{ $isRedisplay ? old('keterangan') : $item?->keterangan }}</textarea>
    @if ($isRedisplay && $errors->has('keterangan'))
        <p class="mt-1 text-xs text-danger">{{ $errors->first('keterangan') }}</p>
    @endif
</div>
<div>
    <span class="inline-block mb-2 text-sm font-medium">{{ __('Assign ke Fitur') }}</span>
    <div class="flex flex-wrap gap-4">
        @foreach ($fiturOptions as $fitur)
            <label class="flex items-center gap-2 text-sm" for="{{ $formId }}-fitur-{{ $fitur->value }}">
                <input class="form-checkbox" id="{{ $formId }}-fitur-{{ $fitur->value }}" name="fitur[]" type="checkbox"
                       value="{{ $fitur->value }}" @checked(in_array($fitur->value, $selectedFitur, true))/>
                {{ $fitur->label() }}
            </label>
        @endforeach
    </div>
    @if ($isRedisplay && ($errors->has('fitur') || $errors->has('fitur.*')))
        <p class="mt-1 text-xs text-danger">{{ $errors->first('fitur') ?: $errors->first('fitur.*') }}</p>
    @endif
</div>
<div>
    <label class="flex items-center gap-2 text-sm" for="{{ $formId }}-is-active">
        <input class="form-checkbox" id="{{ $formId }}-is-active" name="is_active" type="checkbox" value="1" @checked($isActive)/>
        {{ __('Aktif (ditampilkan di fitur yang di-assign)') }}
    </label>
</div>
