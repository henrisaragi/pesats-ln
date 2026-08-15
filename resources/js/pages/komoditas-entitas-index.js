// Cosmetic-only preview of "tanggal berakhir" (issue date + 5 years) in the Komoditas
// Entitas create/edit modals (resources/views/komoditas-entitas/index.blade.php - there can be
// several edit modals, one per rejected row, plus the single create modal). The authoritative
// value is always computed server-side in
// App\Http\Controllers\KomoditasEntitas\KomoditasEntitasController::store()/update().

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-komoditas-entitas-tanggal-terbit]').forEach(function (input) {
        const preview = input.closest('div')?.querySelector('[data-komoditas-entitas-tanggal-berakhir-preview]');

        if (!preview) {
            return;
        }

        function updatePreview() {
            if (!input.value) {
                preview.textContent = '';

                return;
            }

            const issued = new Date(input.value);
            const expires = new Date(issued);
            expires.setFullYear(expires.getFullYear() + 5);

            const day = String(expires.getDate()).padStart(2, '0');
            const month = String(expires.getMonth() + 1).padStart(2, '0');
            const year = expires.getFullYear();

            preview.textContent = `(${day}/${month}/${year})`;
        }

        input.addEventListener('change', updatePreview);
    });
});
