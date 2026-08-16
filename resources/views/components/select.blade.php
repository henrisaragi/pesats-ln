@props([
    'id',
    'name',
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'required' => false,
    'multiple' => false,
    'dependsOn' => null,
    'dependsOn2' => null,
    'dependsOn3' => null,
    'remoteUrl' => null,
])

@php
    $selectedValues = is_array($selected) ? array_map('strval', $selected) : null;
@endphp

{{--
    Reusable searchable <select>. Any select with data-choice is auto-initialized by
    resources/js/components/select.js (Choices.js, Indonesian text) on DOMContentLoaded.

    - Static select (all options rendered up front): pass $options only.
    - Cascading select (options fetched from the server once a parent select changes,
      used by city/district/sub-district - see resources/js/components/select.js): pass
      $dependsOn (the parent select's #id) and $remoteUrl (a URL containing the literal
      placeholder "__PARENT__" where the parent's selected value is substituted).
--}}
<select
    {{ $attributes->merge(['class' => 'form-input']) }}
    id="{{ $id }}"
    name="{{ $name }}"
    data-choice
    @if ($multiple) multiple @endif
    @if ($dependsOn) data-choice-depends="{{ $dependsOn }}" @endif
    @if ($dependsOn2) data-choice-depends2="{{ $dependsOn2 }}" @endif
    @if ($dependsOn3) data-choice-depends3="{{ $dependsOn3 }}" @endif
    @if ($remoteUrl) data-choice-url="{{ $remoteUrl }}" @endif
    @required($required)
>
    @unless ($multiple)
        <option value="">{{ $placeholder ?? __('Pilih') }}</option>
    @endunless
    @foreach ($options as $option)
        @php
            $optionValue = is_array($option) ? $option['id'] : $option->id;
            $optionLabel = is_array($option) ? $option['name'] : $option->name;
            $isSelected = $selectedValues !== null
                ? in_array((string) $optionValue, $selectedValues, true)
                : (string) $selected === (string) $optionValue;
        @endphp
        <option value="{{ $optionValue }}" @selected($isSelected)>
            {{ $optionLabel }}
        </option>
    @endforeach
</select>
