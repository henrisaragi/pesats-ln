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

// Tombol "+" di sebelah select Customer pada modal Tambah/Ubah SATS-LN: buka modal Tambah
// Customer bersama (#customer-quick-create-modal), sambil menyimpan select tujuan dan modal
// SATS-LN yang harus dibuka kembali setelah customer baru tersimpan. Preline otomatis menutup
// modal SATS-LN yang sedang terbuka begitu modal Customer ini dibuka (default isClosePrev),
// jadi tidak perlu menutupnya manual di sini.
document.addEventListener('click', function (event) {
    const trigger = event.target.closest('[data-satsln-add-customer]');

    if (!trigger) {
        return;
    }

    const quickCreateModal = document.getElementById('customer-quick-create-modal');

    if (!quickCreateModal) {
        return;
    }

    quickCreateModal.dataset.targetSelect = trigger.dataset.targetSelect;
    quickCreateModal.dataset.returnModal = trigger.dataset.returnModal;
});

function clearQuickCustomerErrors(form) {
    form.querySelectorAll('[data-error]').forEach((el) => {
        el.textContent = '';
        el.classList.add('hidden');
    });
}

function showQuickCustomerErrors(form, errors) {
    Object.entries(errors).forEach(([field, messages]) => {
        const el = form.querySelector(`[data-error="${field}"]`);

        if (el) {
            el.textContent = messages[0];
            el.classList.remove('hidden');
        }
    });
}

document.addEventListener('submit', function (event) {
    const form = event.target.closest('[data-satsln-quick-customer-form]');

    if (!form) {
        return;
    }

    event.preventDefault();

    const quickCreateModal = document.getElementById('customer-quick-create-modal');
    const targetSelectId = quickCreateModal?.dataset.targetSelect;
    const returnModalId = quickCreateModal?.dataset.returnModal;
    const submitButton = form.querySelector('button[type="submit"]');

    clearQuickCustomerErrors(form);
    submitButton?.setAttribute('disabled', 'disabled');

    fetch(form.action, {
        method: 'POST',
        headers: { Accept: 'application/json' },
        body: new FormData(form),
    })
        .then(async (response) => {
            if (response.status === 422) {
                const { errors } = await response.json();
                showQuickCustomerErrors(form, errors);

                return;
            }

            if (!response.ok) {
                const errorEl = form.querySelector('[data-error="general"]');

                if (errorEl) {
                    errorEl.textContent = errorEl.dataset.message || '';
                    errorEl.classList.remove('hidden');
                }

                return;
            }

            const customer = await response.json();
            const targetSelect = targetSelectId ? document.getElementById(targetSelectId) : null;

            if (targetSelect?._choices) {
                targetSelect._choices.setChoices(
                    [{ value: String(customer.id), label: customer.name }],
                    'value',
                    'label',
                    false,
                );

                const newOption = targetSelect.querySelector(`option[value="${customer.id}"]`);
                if (newOption) {
                    newOption.dataset.countryId = String(customer.country_id);
                }

                targetSelect._choices.setChoiceByValue(String(customer.id));
                targetSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }

            form.reset();
            syncChoicesDisplay(form);

            if (returnModalId) {
                window.HSOverlay?.open(`#${returnModalId}`);
            }
        })
        .finally(() => {
            submitButton?.removeAttribute('disabled');
        });
});
