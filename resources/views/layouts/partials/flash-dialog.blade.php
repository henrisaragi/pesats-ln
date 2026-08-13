<x-alert-modal />

@if ($errors->any() && old('_modal'))
    <button class="hidden" data-hs-overlay="#{{ old('_modal') }}" id="flash-dialog-trigger" type="button"></button>

    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                document.getElementById('flash-dialog-trigger')?.click();
            }, 150);
        });
    </script>
@endif
