// Revisi Permohonan 3 (lihat claude/permohonan-task.md): modal Tambah/Ubah SATS-LN di halaman
// detail Permohonan.
// - Negara Tujuan otomatis terisi dari negara Customer yang dipilih (tetap bisa diganti manual
//   setelahnya) - country_id disimpan di data-country-id tiap <option> select customer.
// - Tombol Batal me-reset form ke nilai awalnya (blank untuk modal create, nilai record untuk
//   modal edit karena "value" dirender server-side) lalu menyinkronkan ulang tampilan Choices.js,
//   karena form.reset() native tidak otomatis mengupdate UI Choices.js.

function syncChoicesDisplay(form) {
    form.querySelectorAll('select[data-choice]').forEach((select) => {
        select._choices?.setChoiceByValue(select.value || '');
    });

    // Cascading selects (Pelabuhan Tujuan depends on Negara Tujuan) don't re-evaluate their
    // enabled/disabled state or option list just from setChoiceByValue() above - force them to
    // recompute by dispatching "change" on their declared parent, the same event they already
    // listen for (see initCascading() in resources/js/components/select.js).
    form.querySelectorAll('select[data-choice-depends]').forEach((select) => {
        const parent = document.getElementById(select.dataset.choiceDepends);
        parent?.dispatchEvent(new Event('change', { bubbles: true }));
    });
}

document.addEventListener('change', function (event) {
    if (!event.target.matches('[data-satsln-customer-select]')) {
        return;
    }

    const customerSelect = event.target;
    const selectedOption = customerSelect.options[customerSelect.selectedIndex];
    const countryId = selectedOption?.dataset.countryId;

    if (!countryId) {
        return;
    }

    const form = customerSelect.closest('form');
    const countrySelect = form?.querySelector('[data-satsln-country-destination]');

    if (!countrySelect?._choices) {
        return;
    }

    countrySelect._choices.setChoiceByValue(countryId);

    // Choices.js's setChoiceByValue() does not dispatch a native "change" event on the
    // underlying <select>, but the destination-port cascading select (resources/js/components/
    // select.js) listens for exactly that to refetch its options - dispatch it manually.
    countrySelect.dispatchEvent(new Event('change', { bubbles: true }));
});

document.addEventListener('click', function (event) {
    const trigger = event.target.closest('[data-satsln-modal-cancel]');

    if (!trigger) {
        return;
    }

    const form = trigger.closest('form');

    if (!form) {
        return;
    }

    form.reset();
    syncChoicesDisplay(form);
});
