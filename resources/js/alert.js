// Reusable success/error alert dialog controller (Preline overlay).
// Backs the <x-alert-modal /> component. Usable from any page/JS:
//   AppAlert.success('Data berhasil disimpan.');
//   AppAlert.error('Role super-admin tidak dapat dihapus.', { title: 'Gagal' });

const STYLES = {
    success: {
        badge: 'bg-success/10',
        button: 'bg-success hover:bg-success/90',
    },
    error: {
        badge: 'bg-danger/10',
        button: 'bg-danger hover:bg-danger/90',
    },
};

function show(type, options = {}) {
    const el = document.getElementById(options.id || 'app-alert-modal');
    if (!el) {
        return;
    }

    const style = STYLES[type] ?? STYLES.success;
    const badge = el.querySelector('[data-alert-badge]');
    const iconSuccess = el.querySelector('[data-alert-icon-success]');
    const iconError = el.querySelector('[data-alert-icon-error]');
    const title = el.querySelector('[data-alert-title]');
    const message = el.querySelector('[data-alert-message]');
    const confirm = el.querySelector('[data-alert-confirm]');

    badge.className = `mx-auto flex items-center justify-center size-16 rounded-full ${style.badge}`;
    iconSuccess?.classList.toggle('hidden', type !== 'success');
    iconError?.classList.toggle('hidden', type !== 'error');
    title.textContent = options.title ?? el.dataset[`${type}Title`] ?? '';
    message.textContent = options.message ?? el.dataset[`${type}Message`] ?? '';
    confirm.className = `btn text-white btn-sm ${style.button}`;
    confirm.textContent = options.confirmLabel ?? confirm.dataset.defaultLabel ?? 'OK';

    window.HSOverlay?.open(el);
}

window.AppAlert = {
    show,
    success: (message, options = {}) => show('success', { ...options, message }),
    error: (message, options = {}) => show('error', { ...options, message }),
};

document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('app-alert-modal');
    const type = el?.dataset.flashType;

    if (type) {
        setTimeout(() => show(type, { message: el.dataset.flashMessage }), 150);
    }
});

// Reusable success/error toast (small, top-right, auto-dismiss). Backs <x-alert-toast />.
const TOAST_STYLES = {
    success: { badge: 'bg-success/10 text-success' },
    error: { badge: 'bg-danger/10 text-danger' },
};

let toastTimer = null;

function hideToast(el) {
    el.classList.add('opacity-0', 'translate-x-4');
    setTimeout(() => el.classList.add('hidden'), 300);
}

function showToast(type, options = {}) {
    const el = document.getElementById(options.id || 'app-alert-toast');
    if (!el) {
        return;
    }

    const style = TOAST_STYLES[type] ?? TOAST_STYLES.success;
    const badge = el.querySelector('[data-alert-toast-badge]');
    const iconSuccess = el.querySelector('[data-alert-toast-icon-success]');
    const iconError = el.querySelector('[data-alert-toast-icon-error]');
    const message = el.querySelector('[data-alert-toast-message]');

    badge.className = `shrink-0 flex items-center justify-center size-8 rounded-full ${style.badge}`;
    iconSuccess?.classList.toggle('hidden', type !== 'success');
    iconError?.classList.toggle('hidden', type !== 'error');
    message.textContent = options.message ?? el.dataset[`${type}Message`] ?? '';

    el.classList.remove('hidden');
    requestAnimationFrame(() => el.classList.remove('opacity-0', 'translate-x-4'));

    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => hideToast(el), options.duration ?? 3000);
}

window.AppToast = {
    show: showToast,
    success: (message, options = {}) => showToast('success', { ...options, message }),
    error: (message, options = {}) => showToast('error', { ...options, message }),
};

document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('app-alert-toast');
    const type = el?.dataset.flashType;

    if (type) {
        setTimeout(() => showToast(type, { message: el.dataset.flashMessage }), 150);
    }

    el?.querySelector('[data-alert-toast-close]')?.addEventListener('click', () => hideToast(el));
});
