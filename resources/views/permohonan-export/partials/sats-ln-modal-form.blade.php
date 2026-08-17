@php
    $formId = $satsLn?->id ?? 'new';
    $isNew = $satsLn === null;
    $isRedisplay = old('_modal') === $modalId;
@endphp

<form action="{{ $isNew ? route('permohonan-export.sats-ln.store', $permohonan) : route('permohonan-export.sats-ln.update', [$permohonan, $satsLn]) }}"
      method="POST">
    @csrf
    @unless ($isNew)
        @method('PUT')
    @endunless
    <input name="_modal" type="hidden" value="{{ $modalId }}">
    <input name="return_to" type="hidden" value="show">

    {{--
        Sengaja TIDAK diberi overflow-y-auto/max-h di sini (beda dari modal Documents/Customer):
        field-nya cuma 6 (2 baris grid 3 kolom) jadi tidak butuh scroll internal - kalau dipaksa
        overflow-y-auto, dropdown Choices.js (position:absolute) yang butuh ruang di bawah select
        malah ikut ter-clip oleh batas scroll container ini, bikin daftar pilihan hilang/kepotong.
    --}}
    <div class="p-4">
        <div class="grid md:grid-cols-3 gap-4">
            <div>
                <label class="inline-block mb-2 text-sm font-medium"
                       for="satsln-modal-{{ $formId }}-customer">{{ __('Customer') }}</label>
                <div class="flex items-center gap-2">
                    <div class="flex-1 min-w-0">
                        <select class="form-input" data-choice data-satsln-customer-select
                                id="satsln-modal-{{ $formId }}-customer" name="customer_company_id">
                            <option value="">{{ __('Pilih Customer') }}</option>
                            @foreach ($customerOptions as $option)
                                <option data-country-id="{{ $option->country_id }}" value="{{ $option->id }}"
                                        @selected(($isRedisplay ? old('customer_company_id') : $satsLn?->customer_company_id) == $option->id)>
                                    {{ $option->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @can('customer.edit')
                        <button aria-label="{{ __('Tambah Customer') }}"
                                class="btn size-9.25 shrink-0 bg-default-150 hover:bg-default-200"
                                data-hs-overlay="#customer-quick-create-modal" data-return-modal="{{ $modalId }}"
                                data-satsln-add-customer data-target-select="satsln-modal-{{ $formId }}-customer"
                                type="button">
                            <i class="size-4" data-lucide="plus"></i>
                        </button>
                    @endcan
                </div>
                @if ($isRedisplay && $errors->has('customer_company_id'))
                    <p class="mt-1 text-xs text-danger">{{ $errors->first('customer_company_id') }}</p>
                @endif
            </div>
            <div>
                <label class="inline-block mb-2 text-sm font-medium"
                       for="satsln-modal-{{ $formId }}-purpose">{{ __('Tujuan Transaksi') }}</label>
                <select class="form-input" data-choice id="satsln-modal-{{ $formId }}-purpose"
                        name="purpose_of_transaction_id">
                    <option value="">{{ __('Pilih Tujuan Transaksi') }}</option>
                    @foreach ($purposeOfTransactionOptions as $option)
                        <option value="{{ $option->id }}"
                                @selected(($isRedisplay ? old('purpose_of_transaction_id') : $satsLn?->purpose_of_transaction_id) == $option->id)>
                            {{ $option->name }}
                        </option>
                    @endforeach
                </select>
                @if ($isRedisplay && $errors->has('purpose_of_transaction_id'))
                    <p class="mt-1 text-xs text-danger">{{ $errors->first('purpose_of_transaction_id') }}</p>
                @endif
            </div>
            <div>
                <label class="inline-block mb-2 text-sm font-medium"
                       for="satsln-modal-{{ $formId }}-country-exportation">{{ __('Negara Keberangkatan') }}</label>
                <input class="form-input" disabled id="satsln-modal-{{ $formId }}-country-exportation" type="text"
                       value="{{ $indonesiaCountry?->name }}">
                <input name="country_of_exportation" type="hidden" value="{{ $indonesiaCountry?->id }}">
            </div>
            <div>
                <label class="inline-block mb-2 text-sm font-medium"
                       for="satsln-modal-{{ $formId }}-country-destination">{{ __('Negara Tujuan') }}</label>
                <x-select data-satsln-country-destination :options="$countryOptions" id="satsln-modal-{{ $formId }}-country-destination"
                          name="country_of_destination" :placeholder="__('Pilih Negara Tujuan')"
                          :selected="$isRedisplay ? old('country_of_destination') : $satsLn?->country_of_destination"/>
                @if ($isRedisplay && $errors->has('country_of_destination'))
                    <p class="mt-1 text-xs text-danger">{{ $errors->first('country_of_destination') }}</p>
                @endif
            </div>
            <div>
                <label class="inline-block mb-2 text-sm font-medium"
                       for="satsln-modal-{{ $formId }}-port-exportation">{{ __('Pelabuhan Keberangkatan') }}</label>
                <x-select :options="$indonesiaPortOptions" id="satsln-modal-{{ $formId }}-port-exportation"
                          name="port_of_exportation" :placeholder="__('Pilih Pelabuhan Keberangkatan')"
                          :selected="$isRedisplay ? old('port_of_exportation') : $satsLn?->port_of_exportation"/>
                @if ($isRedisplay && $errors->has('port_of_exportation'))
                    <p class="mt-1 text-xs text-danger">{{ $errors->first('port_of_exportation') }}</p>
                @endif
            </div>
            <div>
                <label class="inline-block mb-2 text-sm font-medium"
                       for="satsln-modal-{{ $formId }}-port-destination">{{ __('Pelabuhan Tujuan') }}</label>
                <x-select depends-on="satsln-modal-{{ $formId }}-country-destination"
                          id="satsln-modal-{{ $formId }}-port-destination" name="port_of_destination"
                          :options="$satsLn?->portOfDestination ? collect([(object) ['id' => $satsLn->portOfDestination->id, 'name' => $satsLn->portOfDestination->name]]) : collect()"
                          :selected="$isRedisplay ? old('port_of_destination') : $satsLn?->port_of_destination"
                          :placeholder="__('Pilih Pelabuhan Tujuan')"
                          :remote-url="route('permohonan-export.port-options', ['country' => '__PARENT__'])"/>
                <p class="mt-1 text-xs text-default-400">{{ __('Pilih Negara Tujuan terlebih dahulu.') }}</p>
                @if ($isRedisplay && $errors->has('port_of_destination'))
                    <p class="mt-1 text-xs text-danger">{{ $errors->first('port_of_destination') }}</p>
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
