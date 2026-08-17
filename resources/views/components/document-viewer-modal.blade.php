{{--
    Reusable fullscreen document viewer modal (Preline overlay). Drop one instance per page
    with a unique $id, then trigger it from any button/link carrying:
      - data-hs-overlay="#{{ $id }}"          (opens this modal, standard Preline attribute)
      - data-document-viewer-url="..."         (inline-preview URL, set as the iframe src)
      - data-document-viewer-title="..."       (shown in the modal header)
      - data-document-viewer-download-url="..." (optional, powers the header download button)
    Wiring is handled globally by resources/js/components/document-viewer.js (loaded once via
    app.js), so no per-page JS is needed to use it - see resources/views/komoditas-entitas/*.

    Optional named slot "switcher": extra header content between the title and the download
    button - dipakai Lampiran Dokumen SATS-LN untuk select "lihat lampiran lain" (lihat
    resources/views/permohonan-export/show.blade.php) - kosong/tidak dirender kalau tidak diisi,
    tidak mempengaruhi pemakaian lain komponen ini.
--}}
<div aria-labelledby="{{ $id }}-label" class="hs-overlay hidden size-full fixed top-0 start-0 z-90 overflow-hidden pointer-events-none"
     data-document-viewer-modal id="{{ $id }}" role="dialog" tabindex="-1">
    <div
        class="hs-overlay-open:opacity-100 opacity-0 ease-in-out transition-all duration-200 size-full flex flex-col pointer-events-auto bg-default-900/80">
        <div class="flex items-center justify-between gap-3 px-4 py-3 bg-white border-b border-default-200">
            <h3 class="font-semibold text-default-800 text-base truncate" data-document-viewer-title id="{{ $id }}-label">
                {{ __('Dokumen') }}
            </h3>
            <div class="flex items-center gap-2 shrink-0">
                {{ $switcher ?? '' }}
                <a class="btn btn-sm bg-primary text-white hidden" data-document-viewer-download href="#" target="_blank">
                    <i class="size-4 me-1" data-lucide="download"></i>{{ __('Unduh') }}
                </a>
                <button aria-label="{{ __('Tutup') }}" class="btn btn-icon size-8 bg-danger text-white"
                        data-hs-overlay="#{{ $id }}" type="button">
                    <i class="size-4.5" data-lucide="x"></i>
                </button>
            </div>
        </div>
        <div class="grow bg-default-200">
            <iframe class="size-full border-0" data-document-viewer-frame src="about:blank"
                    title="{{ __('Pratinjau Dokumen') }}"></iframe>
        </div>
    </div>
</div>
