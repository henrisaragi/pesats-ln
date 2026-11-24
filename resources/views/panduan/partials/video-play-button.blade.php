{{-- Variabel: $video (VideoPanduan), $class, $label, $viewerId (id x-document-viewer-modal di halaman) --}}
<button class="{{ $class }}" data-hs-overlay="#{{ $viewerId }}"
        data-document-viewer-title="{{ $video->judul }}"
        data-document-viewer-url="{{ $video->viewerUrl() }}" type="button">
    <i class="size-4" data-lucide="play-circle"></i>{{ $label }}
</button>
