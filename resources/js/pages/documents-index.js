// Revisi (lihat claude/document-task.md): "Negara Penerbit" cuma tampil untuk tipe dokumen
// Export Permit / Import Permit / Country of Origin - selain itu field-nya disembunyikan
// sepenuhnya (bukan cuma di-disable). Server tetap memaksa nilainya ke Indonesia untuk tipe
// lain terlepas dari apa yang ada di field tersembunyi ini (lihat
// App\Http\Requests\DocumentRequest::prepareForValidation()).

const FREE_COUNTRY_TYPES = ['export_permit', 'import_permit', 'country_of_origin'];

function applyCountryVisibility(typeSelect) {
    const wrapper = document.getElementById(typeSelect.dataset.countryWrapper);

    if (!wrapper) {
        return;
    }

    wrapper.classList.toggle('hidden', !FREE_COUNTRY_TYPES.includes(typeSelect.value));
}

document.addEventListener('DOMContentLoaded', function () {
    const marker = document.querySelector('[data-documents-page]');

    if (!marker) {
        return;
    }

    document.querySelectorAll('[data-document-type-select]').forEach(applyCountryVisibility);

    document.addEventListener('change', function (event) {
        if (event.target.matches('[data-document-type-select]')) {
            applyCountryVisibility(event.target);
        }
    });
});
