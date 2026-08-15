{{--
    Auto-triggered popup for newly-arrived notifications - shown only in the browser tab of the
    actively logged-in session that's viewing the page (client-side only, never on initial page
    load, only for notifications that arrive afterwards). Opened/populated entirely from JS
    (resources/js/components/notification-dropdown.js) via window.HSOverlay.open(...), never by
    a user click - see that file for the detection logic.

    "Lihat" marks the notification read and navigates to its url (if any). "Nanti Dulu" just
    closes the popup - the notification stays unread in the dropdown for later.
--}}
<div aria-labelledby="{{ $id }}-label"
     class="hs-overlay hidden size-full fixed top-0 start-0 z-95 overflow-x-hidden overflow-y-auto pointer-events-none"
     id="{{ $id }}" role="alertdialog" tabindex="-1">
    <div
        class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
        <div
            class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
            <div class="text-center">
                <div class="mx-auto size-12 rounded-full bg-primary/10 flex items-center justify-center">
                    <i class="size-6 text-primary" data-lucide="bell-ring"></i>
                </div>
                <div class="mt-5 text-center">
                    <h5 class="mb-1 text-lg font-semibold text-default-800" data-notification-alert-title
                        id="{{ $id }}-label"></h5>
                    <p class="text-default-500 text-sm font-normal" data-notification-alert-message></p>
                    <div class="mt-5 flex gap-2 justify-center">
                        <button class="btn btn-sm bg-default-150" data-notification-alert-later type="button">
                            {{ __('Nanti Dulu') }}
                        </button>
                        <button class="btn btn-sm bg-primary text-white" data-notification-alert-view type="button">
                            {{ __('Lihat') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
