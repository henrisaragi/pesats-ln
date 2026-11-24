// Modal "Ikuti Assesment" (resources/views/assesment-isi/partials/ikuti-modal.blade.php): satu modal
// bersama untuk semua baris - assesment & komoditas baris yang diklik diisikan ke hidden input,
// dan tombol "Mulai Assesment" baru aktif setelah file Surat Pernyataan dipilih.

const modal = document.getElementById('assesment-ikuti-modal');

if (modal) {
    const form = modal.querySelector('[data-assesment-ikuti-form]');
    const assesmentInput = modal.querySelector('[data-assesment-ikuti-assesment-id]');
    const komoditasInput = modal.querySelector('[data-assesment-ikuti-komoditas-id]');
    const fileInput = modal.querySelector('[data-assesment-ikuti-file]');
    const submitButton = modal.querySelector('[data-assesment-ikuti-submit]');

    const syncSubmitState = () => {
        submitButton.disabled = !(fileInput.files && fileInput.files.length > 0);
    };

    document.querySelectorAll('[data-assesment-ikuti]').forEach((button) => {
        button.addEventListener('click', () => {
            if (assesmentInput.value !== button.dataset.assesmentId || komoditasInput.value !== button.dataset.komoditasId) {
                fileInput.value = '';
            }

            assesmentInput.value = button.dataset.assesmentId;
            komoditasInput.value = button.dataset.komoditasId;
            syncSubmitState();
        });
    });

    fileInput.addEventListener('change', syncSubmitState);

    // Cegah submit ganda saat upload berjalan.
    form.addEventListener('submit', () => {
        submitButton.disabled = true;
    });

    syncSubmitState();
}
