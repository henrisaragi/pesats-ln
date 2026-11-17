{{-- Field header periode. $prefix = id unik form, $modalId = nilai _modal, $period = null saat create. --}}
@php
    $useOld = old('_modal') === $modalId;
    $value = fn (string $field) => $useOld ? old($field) : ($period?->{$field} ?? null);
@endphp
<div class="flex flex-col gap-y-4">
    <div>
        <label class="inline-block mb-2 text-sm font-medium" for="{{ $prefix }}-provinsi">{{ __('Provinsi') }}</label>
        @if ($scopedProvinsi)
            <input name="provinsi_id" type="hidden" value="{{ $scopedProvinsi->id }}">
            <input class="form-input bg-default-100" id="{{ $prefix }}-provinsi" readonly type="text" value="{{ $scopedProvinsi->name }}">
        @else
            <x-province-select id="{{ $prefix }}-provinsi" :selected="$value('provinsi_id') !== null ? (string) $value('provinsi_id') : null"/>
        @endif
        @if ($useOld && $errors->has('provinsi_id'))
            <p class="mt-1 text-xs text-danger">{{ $errors->first('provinsi_id') }}</p>
        @endif
    </div>
    <div>
        <label class="inline-block mb-2 text-sm font-medium" for="{{ $prefix }}-komoditas">{{ __('Komoditas') }}</label>
        <select class="form-input" data-choice id="{{ $prefix }}-komoditas" name="komoditas_id">
            <option value="">{{ __('Pilih Komoditas') }}</option>
            @foreach ($komoditasOptions as $option)
                <option value="{{ $option->id }}" @selected((int) $value('komoditas_id') === $option->id)>{{ $option->name }}</option>
            @endforeach
        </select>
        @if ($useOld && $errors->has('komoditas_id'))
            <p class="mt-1 text-xs text-danger">{{ $errors->first('komoditas_id') }}</p>
        @endif
    </div>
    <div>
        <label class="inline-block mb-2 text-sm font-medium" for="{{ $prefix }}-species">{{ __('Jenis TSL') }}</label>
        <x-species-select depends-on="{{ $prefix }}-komoditas" id="{{ $prefix }}-species" name="species_id"
                          :selected="$value('species_id') !== null ? (string) $value('species_id') : null"/>
        @if ($useOld && $errors->has('species_id'))
            <p class="mt-1 text-xs text-danger">{{ $errors->first('species_id') }}</p>
        @endif
    </div>
    <div>
        <label class="inline-block mb-2 text-sm font-medium" for="{{ $prefix }}-trade-code">{{ __('Kode Perdagangan') }}</label>
        <select class="form-input" data-choice id="{{ $prefix }}-trade-code" name="trade_code_id">
            <option value="">{{ __('Pilih Kode Perdagangan') }}</option>
            @foreach ($tradeCodeOptions as $option)
                <option value="{{ $option->id }}" @selected((int) $value('trade_code_id') === $option->id)>{{ $option->code }} - {{ $option->name }}</option>
            @endforeach
        </select>
        @if ($useOld && $errors->has('trade_code_id'))
            <p class="mt-1 text-xs text-danger">{{ $errors->first('trade_code_id') }}</p>
        @endif
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="inline-block mb-2 text-sm font-medium" for="{{ $prefix }}-year">{{ __('Tahun') }}</label>
            <input class="form-input" id="{{ $prefix }}-year" name="year" type="number" value="{{ $value('year') ?? $activeYear }}">
            @if ($useOld && $errors->has('year'))
                <p class="mt-1 text-xs text-danger">{{ $errors->first('year') }}</p>
            @endif
        </div>
        <div>
            <label class="inline-block mb-2 text-sm font-medium" for="{{ $prefix }}-kuota">{{ __('Kuota Ekspor (Q)') }}</label>
            <input class="form-input" id="{{ $prefix }}-kuota" min="0" name="kuota_tangkap" step="any" type="number"
                   value="{{ $value('kuota_tangkap') !== null ? rtrim(rtrim((string) $value('kuota_tangkap'), '0'), '.') : '' }}">
            @if ($useOld && $errors->has('kuota_tangkap'))
                <p class="mt-1 text-xs text-danger">{{ $errors->first('kuota_tangkap') }}</p>
            @endif
        </div>
    </div>
    <div>
        <label class="inline-block mb-2 text-sm font-medium" for="{{ $prefix }}-catatan">{{ __('Catatan') }}</label>
        <textarea class="form-input" id="{{ $prefix }}-catatan" name="catatan" rows="2">{{ $value('catatan') }}</textarea>
    </div>
</div>
