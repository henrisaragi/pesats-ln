// Keeps the Kuota Perusahaan page in sync with the "Tahun Aktif" (active year) picker in
// the topbar (resources/views/layouts/partials/topbar.blade.php), which stores its
// selection in localStorage and, on change, dispatches a global "active-year-changed"
// event. The active year is also embedded server-side into the cascading select AJAX
// URLs (see resources/views/kuota-perusahaan/index.blade.php), so keeping ?year in the
// URL in sync with localStorage keeps those endpoints querying the right year.

document.addEventListener('DOMContentLoaded', function () {
    const marker = document.querySelector('[data-kuota-perusahaan-page]');

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
