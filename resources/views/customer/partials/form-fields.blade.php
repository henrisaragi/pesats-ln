@php
    $formId = $customer?->id ?? 'new';
    $isRedisplay = old('_modal') === $modalId;
@endphp

<div class="p-4 overflow-y-auto max-h-[70vh]">
    <div class="flex flex-col gap-y-4">
        <div>
            <label class="inline-block mb-2 text-sm font-medium" for="customer-{{ $formId }}-name">
                {{ __('Nama Customer') }}
            </label>
            <input class="form-input" id="customer-{{ $formId }}-name" name="name" type="text"
                   value="{{ $isRedisplay ? old('name') : $customer?->name }}"/>
            @if ($isRedisplay && $errors->has('name'))
                <p class="mt-1 text-xs text-danger">{{ $errors->first('name') }}</p>
            @endif
        </div>
        <div>
            <label class="inline-block mb-2 text-sm font-medium" for="customer-{{ $formId }}-country">
                {{ __('Negara') }}
            </label>
            <x-country-select id="customer-{{ $formId }}-country" name="country_id"
                               :selected="$isRedisplay ? old('country_id') : $customer?->country_id"/>
            @if ($isRedisplay && $errors->has('country_id'))
                <p class="mt-1 text-xs text-danger">{{ $errors->first('country_id') }}</p>
            @endif
        </div>
        <div>
            <label class="inline-block mb-2 text-sm font-medium" for="customer-{{ $formId }}-address">
                {{ __('Alamat') }}
            </label>
            <textarea class="form-input" id="customer-{{ $formId }}-address" name="address"
                      rows="3">{{ $isRedisplay ? old('address') : $customer?->address }}</textarea>
            @if ($isRedisplay && $errors->has('address'))
                <p class="mt-1 text-xs text-danger">{{ $errors->first('address') }}</p>
            @endif
        </div>
    </div>
</div>
