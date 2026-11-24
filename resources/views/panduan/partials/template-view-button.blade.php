{{-- Variabel: $template (TemplateSuratPernyataan), $class, $label, $viewerId (id x-document-viewer-modal di halaman). DOC/DOCX: url kosong = viewer menampilkan pesan + tombol Unduh. --}}
<button class="{{ $class }}" data-hs-overlay="#{{ $viewerId }}"
        data-document-viewer-download-url="{{ route('template-surat-pernyataan.download', $template) }}"
        data-document-viewer-title="{{ $template->judul }}"
        data-document-viewer-url="{{ $template->isPreviewable() ? route('template-surat-pernyataan.preview', $template) : '' }}" type="button">
    <i class="size-4" data-lucide="file-text"></i>{{ $label }}
</button>
