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
    // Atribut ada tapi kosong = file tidak bisa dipratinjau (mis. DOC/DOCX).
    modal.dataset.documentViewerPendingUnsupported = trigger.getAttribute('data-document-viewer-url') === '' ? '1' : '';
});

document.querySelectorAll('[data-document-viewer-modal]').forEach(function (modal) {
    modal.addEventListener('open.hs.overlay', function () {
        const frame = modal.querySelector('[data-document-viewer-frame]');
        const title = modal.querySelector('[data-document-viewer-title]');
        const downloadLink = modal.querySelector('[data-document-viewer-download]');

        const unsupported = modal.querySelector('[data-document-viewer-unsupported]');
        const isUnsupported = modal.dataset.documentViewerPendingUnsupported === '1';
        modal.dataset.documentViewerPendingUnsupported = '';

        if (frame) {
            frame.src = withToolbarHidden(modal.dataset.documentViewerPendingUrl) || 'about:blank';
        }

        if (unsupported) {
            unsupported.classList.toggle('hidden', !isUnsupported);
            unsupported.classList.toggle('flex', isUnsupported);
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

// Pemisah panel "aside" (slot opsional di document-viewer-modal) - geser untuk mengubah lebar
// panel kiri vs dokumen di kanan. Iframe dimatikan pointer-events-nya selama drag, kalau tidak
// iframe "menelan" pointermove begitu kursor masuk ke area dokumen dan drag terputus.
const ASIDE_MIN_PERCENT = 20;
const ASIDE_MAX_PERCENT = 80;
const ASIDE_KEYBOARD_STEP = 5;

function setAsideWidth(resizer, aside, percent) {
    const clamped = Math.min(ASIDE_MAX_PERCENT, Math.max(ASIDE_MIN_PERCENT, percent));

    aside.style.width = clamped + '%';
    resizer.setAttribute('aria-valuenow', String(Math.round(clamped)));
}

document.querySelectorAll('[data-document-viewer-resizer]').forEach(function (resizer) {
    const aside = resizer.previousElementSibling;
    const container = resizer.parentElement;
    const frame = container.querySelector('[data-document-viewer-frame]');

    if (!aside || !aside.hasAttribute('data-document-viewer-aside')) {
        return;
    }

    resizer.addEventListener('pointerdown', function (event) {
        event.preventDefault();
        resizer.setPointerCapture(event.pointerId);

        if (frame) {
            frame.style.pointerEvents = 'none';
        }
    });

    resizer.addEventListener('pointermove', function (event) {
        if (!resizer.hasPointerCapture(event.pointerId)) {
            return;
        }

        const bounds = container.getBoundingClientRect();
        setAsideWidth(resizer, aside, ((event.clientX - bounds.left) / bounds.width) * 100);
    });

    function stopDragging(event) {
        if (resizer.hasPointerCapture(event.pointerId)) {
            resizer.releasePointerCapture(event.pointerId);
        }

        if (frame) {
            frame.style.pointerEvents = '';
        }
    }

    resizer.addEventListener('pointerup', stopDragging);
    resizer.addEventListener('pointercancel', stopDragging);

    resizer.addEventListener('keydown', function (event) {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
            return;
        }

        event.preventDefault();
        const current = parseFloat(resizer.getAttribute('aria-valuenow')) || 40;
        setAsideWidth(resizer, aside, current + (event.key === 'ArrowRight' ? ASIDE_KEYBOARD_STEP : -ASIDE_KEYBOARD_STEP));
    });
});
