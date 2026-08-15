{{--
    Species select: options are fetched via AJAX (resources/js/components/select.js) whenever
    the parent komoditas select (#{{ $dependsOn }}) changes - see App\View\Components\SpeciesSelect.
    Only the currently selected species (if any, e.g. when editing) is preloaded server-side so
    it renders correctly before the parent fires a change event.
--}}
<x-select
    :attributes="$attributes"
    :depends-on="$dependsOn"
    :id="$id"
    :name="$name"
    :options="$options"
    :placeholder="$placeholder ?? __('Pilih Spesies')"
    :remote-url="$remoteUrl"
    :required="$required"
    :selected="$selected"
/>
