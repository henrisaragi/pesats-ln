// Cosmetic-only preview of "tanggal berakhir" (issue date + 5 years) in the Komoditas
// Entitas create modal (resources/views/komoditas-entitas/index.blade.php). The
// authoritative value is always computed server-side in
// App\Http\Controllers\KomoditasEntitas\KomoditasEntitasController::store().

document.addEventListener('DOMContentLoaded', function () {
    const input = document.querySelector('[data-komoditas-entitas-tanggal-terbit]');
    const preview = document.querySelector('[data-komoditas-entitas-tanggal-berakhir-preview]');

    if (!input || !preview) {
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
