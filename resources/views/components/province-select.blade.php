{{-- Province select: full list preloaded (small dataset), searchable via Choices.js. Top of the province -> city -> district -> sub-district cascade. --}}
<x-select
    :attributes="$attributes"
    :id="$id"
    :name="$name"
    :options="$options"
    :placeholder="$placeholder ?? __('Pilih Provinsi')"
    :required="$required"
    :selected="$selected"
/>
