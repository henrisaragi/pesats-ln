// Keeps the Kuota Nasional list in sync with the "Tahun Aktif" (active year) picker in
// the topbar (resources/views/layouts/partials/topbar.blade.php), which stores its
// selection in localStorage and, on change, dispatches a global "active-year-changed"
// event - no other page listens to it yet, so this is scoped to this page only.

document.addEventListener('DOMContentLoaded', function () {
    const marker = document.querySelector('[data-kuota-nasional-page]');

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
