@if ($isActive)
    <span class="inline-flex rounded-full px-2.5 py-0.75 border text-xs border-success/30 bg-success/10 text-success">{{ __('Aktif') }}</span>
@else
    <span class="inline-flex rounded-full px-2.5 py-0.75 border text-xs border-default-300 bg-default-150 text-default-600">{{ __('Nonaktif') }}</span>
@endif
