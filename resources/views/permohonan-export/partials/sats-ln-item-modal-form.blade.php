@php
    $item = $item ?? null;
    $isNew = $item === null;
    $isRedisplay = old('_modal') === $modalId;

    $kuotaNasional = $item?->kuotaNasional;
    $selectedSource = $isRedisplay ? old('source_id') : $kuotaNasional?->source_id;
    $selectedKomoditas = $isRedisplay ? old('komoditas_id') : $kuotaNasional?->komoditas_id;
    $selectedTradeCode = $isRedisplay ? old('trade_code_id') : $kuotaNasional?->trade_code_id;
    $selectedSpecies = $isRedisplay ? old('species_id') : $kuotaNasional?->species_id;
    $selectedQty = $isRedisplay ? old('qty') : $item?->qty;
    $selectedDescription = $isRedisplay ? old('description') : $item?->description;

    $komoditasForOptions = $oldKomoditas ?? $kuotaNasional?->komoditas;
    $tradeCodeForOptions = $oldTradeCode ?? $kuotaNasional?->tradeCode;
    $speciesForOptions = $oldSpecies ?? $kuotaNasional?->species;
@endphp

<form action="{{ $isNew ? route('permohonan-export.sats-ln.items.store', [$permohonan, $satsLn]) : route('permohonan-export.sats-ln.items.update', [$permohonan, $satsLn, $item]) }}"
      method="POST">
    @csrf
    @unless ($isNew)
        @method('PUT')
    @endunless
    <input name="_modal" type="hidden" value="{{ $modalId }}">

    <div class="p-4">
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="inline-block mb-2 text-sm font-medium" for="satsln-item-{{ $modalId }}-source">
                    {{ __('Status Asal') }}
                </label>
                <x-select :options="$sourceOptions" id="satsln-item-{{ $modalId }}-source" name="source_id"
                          :placeholder="__('Pilih Status Asal')" :selected="$selectedSource"/>
                @if ($isRedisplay && $errors->has('source_id'))
                    <p class="mt-1 text-xs text-danger">{{ $errors->first('source_id') }}</p>
                @endif
            </div>
            <div>
                <label class="inline-block mb-2 text-sm font-medium" for="satsln-item-{{ $modalId }}-komoditas">
                    {{ __('Komoditas') }}
                </label>
                <x-select depends-on="satsln-item-{{ $modalId }}-source" id="satsln-item-{{ $modalId }}-komoditas"
                          name="komoditas_id"
                          :options="$komoditasForOptions ? collect([(object) ['id' => $komoditasForOptions->id, 'name' => $komoditasForOptions->name]]) : collect()"
                          :selected="$selectedKomoditas" :placeholder="__('Pilih Komoditas')"
                          :remote-url="route('permohonan-export.item-komoditas-options', ['source' => '__PARENT__'])"/>
                @if ($isRedisplay && $errors->has('komoditas_id'))
                    <p class="mt-1 text-xs text-danger">{{ $errors->first('komoditas_id') }}</p>
                @endif
            </div>
            <div>
                <label class="inline-block mb-2 text-sm font-medium" for="satsln-item-{{ $modalId }}-trade-code">
                    {{ __('Trade Code') }}
                </label>
                <x-select depends-on="satsln-item-{{ $modalId }}-komoditas" depends-on2="satsln-item-{{ $modalId }}-source"
                          id="satsln-item-{{ $modalId }}-trade-code" name="trade_code_id"
                          :options="$tradeCodeForOptions ? collect([(object) ['id' => $tradeCodeForOptions->id, 'name' => $tradeCodeForOptions->code.' - '.$tradeCodeForOptions->name]]) : collect()"
                          :selected="$selectedTradeCode" :placeholder="__('Pilih Trade Code')"
                          :remote-url="route('permohonan-export.item-trade-code-options', ['source' => '__PARENT2__', 'komoditas' => '__PARENT__'])"/>
                @if ($isRedisplay && $errors->has('trade_code_id'))
                    <p class="mt-1 text-xs text-danger">{{ $errors->first('trade_code_id') }}</p>
                @endif
            </div>
            <div>
                <label class="inline-block mb-2 text-sm font-medium" for="satsln-item-{{ $modalId }}-species">
                    {{ __('Species') }}
                </label>
                <x-select depends-on="satsln-item-{{ $modalId }}-trade-code" depends-on2="satsln-item-{{ $modalId }}-komoditas"
                          depends-on3="satsln-item-{{ $modalId }}-source" id="satsln-item-{{ $modalId }}-species"
                          name="species_id"
                          :options="$speciesForOptions ? collect([(object) ['id' => $speciesForOptions->id, 'name' => $speciesForOptions->scientific_name]]) : collect()"
                          :selected="$selectedSpecies" :placeholder="__('Pilih Species')"
                          :remote-url="route('permohonan-export.item-species-options', ['source' => '__PARENT3__', 'komoditas' => '__PARENT2__', 'tradeCode' => '__PARENT__'])"/>
                @if ($isRedisplay && $errors->has('species_id'))
                    <p class="mt-1 text-xs text-danger">{{ $errors->first('species_id') }}</p>
                @endif
            </div>
            <div class="md:col-span-2">
                <label class="inline-block mb-2 text-sm font-medium" for="satsln-item-{{ $modalId }}-qty">
                    {{ __('Jumlah (Qty)') }}
                </label>
                <input class="form-input" id="satsln-item-{{ $modalId }}-qty" name="qty" step="0.01" type="number"
                       value="{{ $selectedQty }}">
                @if ($isRedisplay && $errors->has('qty'))
                    <p class="mt-1 text-xs text-danger">{{ $errors->first('qty') }}</p>
                @endif
            </div>
            <div class="md:col-span-2">
                <label class="inline-block mb-2 text-sm font-medium" for="satsln-item-{{ $modalId }}-description">
                    {{ __('Deskripsi') }}
                </label>
                <textarea class="form-input" id="satsln-item-{{ $modalId }}-description" name="description"
                          rows="2">{{ $selectedDescription }}</textarea>
                @if ($isRedisplay && $errors->has('description'))
                    <p class="mt-1 text-xs text-danger">{{ $errors->first('description') }}</p>
                @endif
            </div>
        </div>
    </div>
    <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
        <button class="btn bg-default-150" data-hs-overlay="#{{ $modalId }}" data-satsln-modal-cancel
                type="button">{{ __('Batal') }}</button>
        <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
    </div>
</form>
