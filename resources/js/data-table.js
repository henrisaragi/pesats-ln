import DataTable from 'datatables.net-dt';

// Translated per the app's active locale (see resources/views/layouts/partials/title-meta.blade.php).
// This fallback only applies if that script tag is somehow missing from the page.
const FALLBACK_LANGUAGE = {
    processing: 'Memuat...',
    search: 'Cari:',
    lengthMenu: 'Tampilkan _MENU_ data',
    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
    infoEmpty: 'Tidak ada data',
    infoFiltered: '(disaring dari _MAX_ total data)',
    loadingRecords: 'Memuat...',
    zeroRecords: 'Data tidak ditemukan',
    emptyTable: 'Belum ada data.',
    paginate: {
        first: 'Awal',
        last: 'Akhir',
        next: 'Selanjutnya',
        previous: 'Sebelumnya',
    },
};

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

export function initDataTable(selector, options = {}) {
    const { drawCallback: onDraw, ajax: ajaxOptions, ...rest } = options;

    return new DataTable(selector, {
        serverSide: true,
        processing: true,
        ordering: false,
        dom: 'rt<"dt-footer"lip>',
        language: window.dataTableLanguage || FALLBACK_LANGUAGE,
        ajax: {
            headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
            ...ajaxOptions,
        },
        drawCallback: (settings) => {
            window.lucideCreateIcons?.();
            window.HSStaticMethods?.autoInit();
            onDraw?.(settings);
        },
        ...rest,
    });
}
