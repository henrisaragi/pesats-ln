{{-- Modal form create/edit. Variabel: $modalId, $title, $action, $method, $fieldsView, $item, $fiturOptions --}}
@php($isRedisplay = old('_modal') === $modalId)
<div aria-labelledby="{{ $modalId }}-label" id="{{ $modalId }}"
     class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
     role="dialog" tabindex="-1">
    <div
        class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center whitespace-normal">
        <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
            <div class="card-header">
                <h3 class="font-bold text-default-800 text-base" id="{{ $modalId }}-label">{{ $title }}</h3>
                <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800" data-hs-overlay="#{{ $modalId }}" type="button">
                    <span class="sr-only">{{ __('Tutup') }}</span>
                    <i class="size-5" data-lucide="x"></i>
                </button>
            </div>
            <form action="{{ $action }}" enctype="multipart/form-data" method="POST">
                @csrf
                @if ($method !== 'POST')
                    @method($method)
                @endif
                <input name="_modal" type="hidden" value="{{ $modalId }}">
                <div class="p-4">
                    <div class="flex flex-col gap-y-4">
                        @include('panduan.partials.common-fields', ['formId' => $modalId, 'item' => $item, 'isRedisplay' => $isRedisplay])
                        @include($fieldsView, ['formId' => $modalId, 'item' => $item, 'isRedisplay' => $isRedisplay])
                    </div>
                </div>
                <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                    <button class="btn bg-default-150" data-hs-overlay="#{{ $modalId }}" type="button">{{ __('Batal') }}</button>
                    <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
