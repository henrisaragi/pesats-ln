// Wires any [data-document-viewer-url] trigger to the fullscreen document viewer modal it
// points at via its standard data-hs-overlay="#modal-id" attribute (Preline opens/closes the
// modal itself). Content is populated on Preline's own "open.hs.overlay" event - not on the
// raw click - because the trigger button itself also carries data-hs-overlay (Preline toggles
// open/close through the same attribute), so a plain click listener can't tell an "open" click
// from a "close" click. The iframe is cleared on "close.hs.overlay" so the document stops
// loading/rendering in the background once the modal is dismissed.
// See resources/views/components/document-viewer-modal.blade.php for the modal markup.

// Chrome's built-in PDF viewer shows its own toolbar (zoom, print, download) inside the
// iframe, which duplicates our modal's own header controls and includes Chrome's default
// print button. #toolbar=0 is a PDF open parameter Chrome's viewer honors to hide it -
// harmless no-op for non-PDF documents/browsers that ignore the hash.
export function withToolbarHidden(url) {
    if (!url || url === 'about:blank') {
        return url;
    }

    return url + (url.includes('#') ? '&toolbar=0' : '#toolbar=0');
}

document.addEventListener('click', function (event) {
    const trigger = event.target.closest('[data-document-viewer-url]');

    if (!trigger) {
        return;
    }

    const modalSelector = trigger.getAttribute('data-hs-overlay');
    const modal = modalSelector ? document.querySelector(modalSelector) : null;

    if (!modal) {
        return;
    }

    modal.dataset.documentViewerPendingUrl = trigger.getAttribute('data-document-viewer-url') || '';
    modal.dataset.documentViewerPendingTitle = trigger.getAttribute('data-document-viewer-title') || '';
    modal.dataset.documentViewerPendingDownloadUrl = trigger.getAttribute('data-document-viewer-download-url') || '';
});

document.querySelectorAll('[data-document-viewer-modal]').forEach(function (modal) {
    modal.addEventListener('open.hs.overlay', function () {
        const frame = modal.querySelector('[data-document-viewer-frame]');
        const title = modal.querySelector('[data-document-viewer-title]');
        const downloadLink = modal.querySelector('[data-document-viewer-download]');

        if (frame) {
            frame.src = withToolbarHidden(modal.dataset.documentViewerPendingUrl) || 'about:blank';
        }

        if (title) {
            title.textContent = modal.dataset.documentViewerPendingTitle || '';
        }

        if (downloadLink) {
            const downloadUrl = modal.dataset.documentViewerPendingDownloadUrl;
            downloadLink.href = downloadUrl || '#';
            downloadLink.classList.toggle('hidden', !downloadUrl);
        }
    });

    modal.addEventListener('close.hs.overlay', function () {
        const frame = modal.querySelector('[data-document-viewer-frame]');

        if (frame) {
            frame.src = 'about:blank';
        }
    });
});
