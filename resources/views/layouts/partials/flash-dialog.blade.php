@if (session('success'))
    <x-dialog-success id="flash-dialog-success">{{ session('success') }}</x-dialog-success>
    <button class="hidden" data-hs-overlay="#flash-dialog-success" id="flash-dialog-trigger" type="button"></button>
@elseif (session('error'))
    <x-dialog-error id="flash-dialog-error">{{ session('error') }}</x-dialog-error>
    <button class="hidden" data-hs-overlay="#flash-dialog-error" id="flash-dialog-trigger" type="button"></button>
@elseif ($errors->any() && old('_modal'))
    <button class="hidden" data-hs-overlay="#{{ old('_modal') }}" id="flash-dialog-trigger" type="button"></button>
@endif

@if (session('success') || session('error') || ($errors->any() && old('_modal')))
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                var trigger = document.getElementById('flash-dialog-trigger');
                if (trigger) {
                    trigger.click();
                }
            }, 150);
        });
    </script>
@endif
