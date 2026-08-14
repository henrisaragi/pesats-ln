{{--
    Sub-district (kelurahan) select: options are fetched via AJAX (resources/js/components/select.js)
    whenever the parent district select (#{{ $dependsOn }}) changes - see App\View\Components\SubdistrictSelect.
    Only the currently selected sub-district (if any, e.g. when editing) is preloaded server-side so
    it renders correctly before the parent fires a change event.
--}}
<x-select
    :attributes="$attributes"
    :depends-on="$dependsOn"
    :id="$id"
    :name="$name"
    :options="$options"
    :placeholder="$placeholder ?? __('Pilih Kelurahan')"
    :remote-url="$remoteUrl"
    :required="$required"
    :selected="$selected"
/>
