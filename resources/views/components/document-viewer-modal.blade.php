{{--
    Reusable fullscreen document viewer modal (Preline overlay). Drop one instance per page
    with a unique $id, then trigger it from any button/link carrying:
      - data-hs-overlay="#{{ $id }}"          (opens this modal, standard Preline attribute)
      - data-document-viewer-url="..."         (inline-preview URL, set as the iframe src)
      - data-document-viewer-title="..."       (shown in the modal header)
      - data-document-viewer-download-url="..." (optional, powers the header download button)
    Kosongkan data-document-viewer-url (string kosong) untuk file yang tidak bisa dipratinjau
    (mis. DOC/DOCX): modal menampilkan pesan + tombol Unduh saja.
    Video (YouTube embed / file mp4 lewat route stream) juga cukup dipasang sebagai
    data-document-viewer-url - iframe memutarnya langsung.
    isClosePrev=false: modal ini boleh dibuka dari dalam modal lain tanpa menutup modal tersebut.
    Lihat skill .claude/skills/file-viewer-modal.
    Wiring is handled globally by resources/js/components/document-viewer.js (loaded once via
    app.js), so no per-page JS is needed to use it - see resources/views/komoditas-entitas/*.

    Optional named slot "switcher": extra header content between the title and the download
    button - dipakai Lampiran Dokumen SATS-LN untuk select "lihat lampiran lain" (lihat
    resources/views/permohonan-export/show.blade.php) - kosong/tidak dirender kalau tidak diisi,
    tidak mempengaruhi pemakaian lain komponen ini.

    Optional named slot "aside": panel kiri yang di-scroll terpisah, dokumen tampil di kanan -
    dipakai halaman verifikasi Permohonan untuk menampilkan detail SATS-LN di samping dokumen
    (lihat resources/views/permohonan-export/verifikasi/show.blade.php). Lebar panel bisa diubah
    dengan menggeser pemisah di antara panel dan dokumen (mouse/touch, atau panah kiri/kanan saat
    pemisah fokus) - ditangani di resources/js/components/document-viewer.js.
--}}
<div aria-labelledby="{{ $id }}-label" class="hs-overlay hidden size-full fixed top-0 start-0 z-90 overflow-hidden pointer-events-none"
     data-document-viewer-modal data-hs-overlay-options='{"isClosePrev": false}' id="{{ $id }}" role="dialog" tabindex="-1">
    <div
        class="hs-overlay-open:opacity-100 opacity-0 ease-in-out transition-all duration-200 size-full flex flex-col pointer-events-auto bg-default-900/80">
        <div class="flex items-center justify-between gap-3 px-4 py-3 bg-white border-b border-default-200">
            <h3 class="font-semibold text-default-800 text-base truncate" data-document-viewer-title id="{{ $id }}-label">
                {{ __('Dokumen') }}
            </h3>
            <div class="flex items-center gap-2 shrink-0">
                {{ $switcher ?? '' }}
                <a class="btn btn-sm bg-primary text-white hidden" data-document-viewer-download href="#">
                    <i class="size-4 me-1" data-lucide="download"></i>{{ __('Unduh') }}
                </a>
                <button aria-label="{{ __('Tutup') }}" class="btn btn-icon size-8 bg-danger text-white"
                        data-hs-overlay="#{{ $id }}" type="button">
                    <i class="size-4.5" data-lucide="x"></i>
                </button>
            </div>
        </div>
        <div class="grow min-h-0 flex">
            @isset($aside)
                <div class="shrink-0 overflow-y-auto bg-white hidden md:block" data-document-viewer-aside style="width: 40%">
                    {{ $aside }}
                </div>
                <div aria-label="{{ __('Ubah lebar panel') }}" aria-orientation="vertical" aria-valuemax="80" aria-valuemin="20" aria-valuenow="40"
                     class="hidden md:flex w-2 shrink-0 cursor-col-resize items-center justify-center bg-default-200 hover:bg-primary/30 focus:bg-primary/30 focus:outline-none touch-none select-none"
                     data-document-viewer-resizer role="separator" tabindex="0">
                    <span class="h-10 w-0.5 rounded-full bg-default-400"></span>
                </div>
            @endisset
            <div class="grow bg-default-200 relative">
                <iframe allow="autoplay; fullscreen; picture-in-picture" allowfullscreen class="size-full border-0" data-document-viewer-frame src="about:blank"
                        title="{{ __('Pratinjau Dokumen') }}"></iframe>
                <div class="absolute inset-0 hidden flex-col items-center justify-center gap-3 p-6 text-center bg-default-100" data-document-viewer-unsupported>
                    <i class="size-10 text-default-400" data-lucide="file-x"></i>
                    <p class="text-sm text-default-600">{{ __('Pratinjau tidak tersedia untuk jenis file ini. Silakan unduh file melalui tombol Unduh.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
