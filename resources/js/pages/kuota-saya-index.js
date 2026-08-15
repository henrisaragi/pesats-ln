// Keeps the Kuota Saya page in sync with the "Tahun Aktif" (active year) picker in the
// topbar (resources/views/layouts/partials/topbar.blade.php), which stores its selection
// in localStorage and, on change, dispatches a global "active-year-changed" event - see
// resources/js/pages/kuota-nasional-index.js for the original pattern this mirrors.

document.addEventListener('DOMContentLoaded', function () {
    const marker = document.querySelector('[data-kuota-saya-page]');

    if (!marker) {
        return;
    }

    const STORAGE_KEY = 'tahun_aktif';

    function currentYearParam() {
        return new URLSearchParams(window.location.search).get('year');
    }

    function navigateToYear(year) {
        const url = new URL(window.location.href);
        url.searchParams.set('year', year);
        window.location.href = url.toString();
    }

    const activeYear = localStorage.getItem(STORAGE_KEY) || String(new Date().getFullYear());

    if (currentYearParam() !== activeYear) {
        navigateToYear(activeYear);

        return;
    }

    window.addEventListener('active-year-changed', function (event) {
        navigateToYear(event.detail.year);
    });
});
