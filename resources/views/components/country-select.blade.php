{{-- Country select: full list preloaded (small dataset), searchable via Choices.js. --}}
<x-select
    :attributes="$attributes"
    :id="$id"
    :name="$name"
    :options="$options"
    :placeholder="$placeholder ?? __('Pilih Negara')"
    :required="$required"
    :selected="$selected"
/>
