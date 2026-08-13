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
