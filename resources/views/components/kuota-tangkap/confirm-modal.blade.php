{{-- Dialog konfirmasi aksi (POST/DELETE) - opsional textarea wajib (mis. catatan pengembalian). --}}
@props(['id', 'title', 'message', 'action', 'method' => 'POST', 'confirmLabel' => __('Ya, Lanjutkan'), 'confirmClass' => 'bg-primary', 'textarea' => null, 'textareaLabel' => null])
<div aria-labelledby="{{ $id }}-label" id="{{ $id }}"
     class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none whitespace-normal"
     role="dialog" tabindex="-1">
    <div class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-md m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
        <div class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
            <div class="absolute top-3 end-3">
                <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800" data-hs-overlay="#{{ $id }}" type="button">
                    <i class="size-5" data-lucide="x"></i>
                </button>
            </div>
            <form action="{{ $action }}" method="POST">
                @csrf
                @if (strtoupper($method) !== 'POST')
                    @method($method)
                @endif
                <input name="_modal" type="hidden" value="{{ $id }}">
                <h5 class="mb-1 text-lg font-semibold text-default-800 text-center" id="{{ $id }}-label">{{ $title }}</h5>
                <p class="text-default-500 text-sm text-center">{{ $message }}</p>
                @if ($textarea)
                    <div class="mt-4 text-start">
                        <label class="inline-block mb-2 text-sm font-medium" for="{{ $id }}-{{ $textarea }}">{{ $textareaLabel }}</label>
                        <textarea class="form-input" id="{{ $id }}-{{ $textarea }}" name="{{ $textarea }}" required rows="3">{{ old('_modal') === $id ? old($textarea) : '' }}</textarea>
                        @if (old('_modal') === $id && $errors->has($textarea))
                            <p class="mt-1 text-xs text-danger">{{ $errors->first($textarea) }}</p>
                        @endif
                    </div>
                @endif
                <div class="mt-5 flex gap-2 justify-center">
                    <button class="btn bg-default-150" data-hs-overlay="#{{ $id }}" type="button">{{ __('Batal') }}</button>
                    <button class="btn {{ $confirmClass }} text-white" type="submit">{{ $confirmLabel }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
