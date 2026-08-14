{{-- Port select: full list preloaded (small dataset), optionally pre-filtered by :country-id, searchable via Choices.js. --}}
<x-select
    :attributes="$attributes"
    :id="$id"
    :name="$name"
    :options="$options"
    :placeholder="$placeholder ?? __('Pilih Pelabuhan')"
    :required="$required"
    :selected="$selected"
/>
