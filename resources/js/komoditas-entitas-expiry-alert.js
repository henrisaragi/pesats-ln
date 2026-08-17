// Backs <x-komoditas-entitas-expiry-alert/> - dibuka otomatis begitu halaman selesai dimuat
// kalau elemennya punya data-flash-items (diisi server dari session flash sekali per login
// berhasil, lihat AuthenticationController::flashExpiringKomoditasAlert()). Sama seperti
// alert.js (data-flash-type/message untuk <x-alert-modal/>), murni dibaca dari data attribute -
// tidak ada request tambahan ke server.
document.addEventListener('DOMContentLoaded', function () {
    const el = document.querySelector('[data-komoditas-entitas-expiry-alert]');
    const raw = el?.dataset.flashItems;

    if (!raw) {
        return;
    }

    let items;
    try {
        items = JSON.parse(raw);
    } catch {
        return;
    }

    if (!Array.isArray(items) || items.length === 0) {
        return;
    }

    const list = el.querySelector('[data-expiry-alert-list]');

    if (list) {
        list.innerHTML = '';
        items.forEach((item) => {
            const li = document.createElement('li');
            li.className = 'text-sm text-default-700 flex justify-between gap-2 border-b border-dashed border-default-200 pb-1.5';

            const name = document.createElement('span');
            name.className = 'font-medium';
            name.textContent = item.komoditas ?? '';

            const date = document.createElement('span');
            date.className = 'text-default-500';
            date.textContent = item.tanggal_berakhir ?? '';

            li.append(name, date);
            list.appendChild(li);
        });
    }

    setTimeout(() => window.HSOverlay?.open(el), 150);
});
