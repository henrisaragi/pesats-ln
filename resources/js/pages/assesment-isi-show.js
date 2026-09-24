// Upload bukti dukung AJAX (resources/views/assesment-isi/show.blade.php): auto-upload begitu
// file dipilih, animasi loading selama request, dan mengganti file yang sudah ada kalau input yang
// sama dipakai ulang - servernya (AssesmentIsiController::uploadBukti) yang menghapus file lama di
// S3. Jawaban pertanyaan sendiri disimpan lewat submit form biasa (bukan AJAX) - lihat komentar di
// show.blade.php.

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
}

document.querySelectorAll('[data-bukti-upload]').forEach(function (widget) {
    const input = widget.querySelector('[data-bukti-input]');
    const loading = widget.querySelector('[data-bukti-loading]');
    const link = widget.querySelector('[data-bukti-link]');
    const filename = widget.querySelector('[data-bukti-filename]');
    const error = widget.querySelector('[data-bukti-error]');

    if (!input) {
        return;
    }

    input.addEventListener('change', function () {
        const file = input.files?.[0];

        if (!file) {
            return;
        }

        error?.classList.add('hidden');
        link?.classList.add('hidden');
        loading?.classList.remove('hidden');

        const formData = new FormData();
        formData.append('file', file);

        fetch(widget.dataset.uploadUrl, {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json'},
            body: formData,
        })
            .then(async (response) => {
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Gagal mengunggah file.');
                }

                return data;
            })
            .then(function (data) {
                if (filename) {
                    filename.textContent = data.original_filename;
                }

                if (link) {
                    link.href = data.preview_url;
                    link.classList.remove('hidden');
                }
            })
            .catch(function (err) {
                if (error) {
                    error.textContent = err.message || 'Gagal mengunggah file.';
                    error.classList.remove('hidden');
                }
            })
            .finally(function () {
                loading?.classList.add('hidden');
                input.value = '';
            });
    });
});
