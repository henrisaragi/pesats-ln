@php
    $formId = $satsLn?->id ?? 'new';
    $item = $satsLn?->items->first();
    $isNew = $satsLn === null;
@endphp

<form action="{{ $isNew ? route('pesats-export.sats-ln.store', $permohonan) : route('pesats-export.sats-ln.update', [$permohonan, $satsLn]) }}"
      method="POST">
    @csrf
    @unless ($isNew)
        @method('PUT')
    @endunless

    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <label class="inline-block mb-2 text-sm font-medium"
                   for="sats-ln-{{ $formId }}-source">{{ __('Status Asal') }}</label>
            <x-select id="sats-ln-{{ $formId }}-source" name="source_status_filter"
                      :options="$sourceStatusOptions"
                      :selected="$item?->kuotaPerusahaan?->kuotaNasional?->source_id"
                      :placeholder="__('Pilih Status Asal')"/>
            <p class="mt-1 text-xs text-default-400">
                {{ __('Hanya menampilkan status asal dari kuota yang dimiliki perusahaan.') }}
            </p>
        </div>
        <div>
            <label class="inline-block mb-2 text-sm font-medium"
                   for="sats-ln-{{ $formId }}-kuota-perusahaan">{{ __('Kuota Perusahaan') }}</label>
            <x-select depends-on="sats-ln-{{ $formId }}-source" id="sats-ln-{{ $formId }}-kuota-perusahaan"
                      name="kuota_perusahaan_id"
                      :options="$item?->kuotaPerusahaan ? collect([(object) ['id' => $item->kuotaPerusahaan->id, 'name' => trim(($item->kuotaPerusahaan->kuotaNasional?->komoditas?->name ?? '').' - '.($item->kuotaPerusahaan->kuotaNasional?->species?->scientific_name ?? ''))]]) : collect()"
                      :selected="$item?->kuota_perusahaan_id"
                      :placeholder="__('Pilih Kuota Perusahaan')"
                      :remote-url="route('pesats-export.kuota-perusahaan-options', ['source' => '__PARENT__'])"/>
        </div>
        <div>
            <label class="inline-block mb-2 text-sm font-medium"
                   for="sats-ln-{{ $formId }}-qty">{{ __('Jumlah (Qty)') }}</label>
            <input class="form-input" id="sats-ln-{{ $formId }}-qty" name="qty" step="0.01" type="number"
                   value="{{ $item?->qty }}"/>
        </div>
        <div>
            <label class="inline-block mb-2 text-sm font-medium"
                   for="sats-ln-{{ $formId }}-peb">{{ __('No. PEB') }}</label>
            <input class="form-input" id="sats-ln-{{ $formId }}-peb" name="peb_or_pib" type="text"
                   value="{{ $satsLn?->items->first()?->peb_or_pib }}"/>
        </div>
        <div>
            <label class="inline-block mb-2 text-sm font-medium"
                   for="sats-ln-{{ $formId }}-customer">{{ __('Perusahaan Pembeli') }}</label>
            <select class="form-input" data-choice id="sats-ln-{{ $formId }}-customer" name="customer_company_id">
                <option value="">{{ __('Pilih Perusahaan Pembeli') }}</option>
                @foreach ($customerCompanyOptions as $option)
                    <option value="{{ $option->id }}" @selected($satsLn?->customer_company_id === $option->id)>
                        {{ $option->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="inline-block mb-2 text-sm font-medium"
                   for="sats-ln-{{ $formId }}-purpose">{{ __('Tujuan Transaksi') }}</label>
            <select class="form-input" data-choice id="sats-ln-{{ $formId }}-purpose" name="purpose_of_transaction_id">
                <option value="">{{ __('Pilih Tujuan Transaksi') }}</option>
                @foreach ($purposeOfTransactionOptions as $option)
                    <option value="{{ $option->id }}" @selected($satsLn?->purpose_of_transaction_id === $option->id)>
                        {{ $option->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="inline-block mb-2 text-sm font-medium"
                   for="sats-ln-{{ $formId }}-country-exportation">{{ __('Negara Asal Ekspor') }}</label>
            <x-country-select id="sats-ln-{{ $formId }}-country-exportation" name="country_of_exportation"
                               :selected="$satsLn?->country_of_exportation"/>
        </div>
        <div>
            <label class="inline-block mb-2 text-sm font-medium"
                   for="sats-ln-{{ $formId }}-country-destination">{{ __('Negara Tujuan') }}</label>
            <x-country-select id="sats-ln-{{ $formId }}-country-destination" name="country_of_destination"
                               :selected="$satsLn?->country_of_destination"/>
        </div>
        <div>
            <label class="inline-block mb-2 text-sm font-medium"
                   for="sats-ln-{{ $formId }}-port-exportation">{{ __('Pelabuhan Muat') }}</label>
            <x-port-select id="sats-ln-{{ $formId }}-port-exportation" name="port_of_exportation"
                            :selected="$satsLn?->port_of_exportation"/>
        </div>
        <div>
            <label class="inline-block mb-2 text-sm font-medium"
                   for="sats-ln-{{ $formId }}-port-destination">{{ __('Pelabuhan Tujuan') }}</label>
            <x-port-select id="sats-ln-{{ $formId }}-port-destination" name="port_of_destination"
                            :selected="$satsLn?->port_of_destination"/>
        </div>
        <div>
            <label class="inline-block mb-2 text-sm font-medium"
                   for="sats-ln-{{ $formId }}-valid-until">{{ __('Masa Berlaku Sampai') }}</label>
            <input class="form-input" id="sats-ln-{{ $formId }}-valid-until" name="valid_until" type="date"
                   value="{{ $satsLn?->valid_until?->format('Y-m-d') }}"/>
        </div>
    </div>
    <div class="mt-4">
        <label class="inline-block mb-2 text-sm font-medium"
               for="sats-ln-{{ $formId }}-conditions">{{ __('Kondisi Khusus') }}</label>
        <textarea class="form-input" id="sats-ln-{{ $formId }}-conditions" name="special_conditions"
                  rows="2">{{ $satsLn?->special_conditions }}</textarea>
    </div>
    <div class="mt-4">
        <label class="inline-block mb-2 text-sm font-medium"
               for="sats-ln-{{ $formId }}-description">{{ __('Deskripsi') }}</label>
        <textarea class="form-input" id="sats-ln-{{ $formId }}-description" name="description"
                  rows="2">{{ $item?->description }}</textarea>
    </div>

    <div class="mt-4 flex justify-end">
        <button class="btn bg-primary text-white" type="submit">
            {{ $isNew ? __('Tambah SATS-LN') : __('Simpan Perubahan') }}
        </button>
    </div>
</form>

@unless ($isNew)
    <form action="{{ route('pesats-export.sats-ln.destroy', [$permohonan, $satsLn]) }}" class="mt-2" method="POST"
          onsubmit="return confirm('{{ __('Hapus SATS-LN ini?') }}');">
        @csrf
        @method('DELETE')
        <button class="btn btn-sm text-danger bg-transparent hover:bg-danger/10" type="submit">
            <i class="size-4 me-1" data-lucide="trash-2"></i>{{ __('Hapus SATS-LN') }}
        </button>
    </form>
@endunless
