// Revisi Permohonan 3 (lihat claude/permohonan-task.md): modal Tambah/Ubah SATS-LN di halaman
// detail Permohonan.
// - Negara Tujuan otomatis terisi dari negara Customer yang dipilih (tetap bisa diganti manual
//   setelahnya) - country_id disimpan di data-country-id tiap <option> select customer.
// - Tombol Batal me-reset form ke nilai awalnya (blank untuk modal create, nilai record untuk
//   modal edit karena "value" dirender server-side) lalu menyinkronkan ulang tampilan Choices.js,
//   karena form.reset() native tidak otomatis mengupdate UI Choices.js.

import { withToolbarHidden } from '../components/document-viewer.js';

// Lampiran Dokumen SATS-LN: select "lihat lampiran lain" di dalam modal viewer fullscreen
// (#satsln-document-view-modal, lihat resources/views/permohonan-export/show.blade.php) -
// tiap <option> membawa data-preview-url/data-download-url/data-title lewat data attribute,
// dibaca langsung di sini (bukan lewat mekanisme data-document-viewer-url punya trigger button,
// karena itu cuma jalan sekali saat modal DIBUKA, bukan saat select di DALAM modal berubah).
document.addEventListener('change', function (event) {
    const select = event.target.closest('[data-satsln-document-select]');

    if (!select) {
        return;
    }

    const modal = document.getElementById('satsln-document-view-modal');
    const option = select.options[select.selectedIndex];

    if (!modal || !option) {
        return;
    }

    const frame = modal.querySelector('[data-document-viewer-frame]');
    const title = modal.querySelector('[data-document-viewer-title]');
    const downloadLink = modal.querySelector('[data-document-viewer-download]');

    if (frame) {
        frame.src = withToolbarHidden(option.dataset.previewUrl) || 'about:blank';
    }

    if (title) {
        title.textContent = option.dataset.title || '';
    }

    if (downloadLink) {
        downloadLink.href = option.dataset.downloadUrl || '#';
        downloadLink.classList.toggle('hidden', !option.dataset.downloadUrl);
    }
});

// Fitur cari daftar SATS-LN di kolom kiri (lihat claude/permohonan-task.md) - filter murni di sisi
// client karena semua data (nomor urut, customer, spesies) sudah dirender ke data-search-haystack
// tiap baris lewat resources/views/permohonan-export/show.blade.php, jadi tidak perlu AJAX.
document.addEventListener('input', function (event) {
    const input = event.target.closest('[data-satsln-search-input]');

    if (!input) {
        return;
    }

    const term = input.value.trim().toLowerCase();

    document.querySelectorAll('[data-satsln-search-item]').forEach((item) => {
        const matches = term === '' || (item.dataset.searchHaystack || '').includes(term);
        item.classList.toggle('hidden', !matches);
    });
});

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

// Modal Verifikasi SATS-LN (lihat claude/verifikasi-satsln.md):
// - prefix nomor & field "year" diambil dari Tahun Aktif (localStorage "tahun_aktif", diset saat
//   login/topbar), mis. 2026 => nomor 00001 tersimpan sebagai 2600001.
// - Tanggal Berakhir cuma pratinjau dari Tanggal Terbit + interval; nilai finalnya dihitung ulang
//   di server lewat App\Enums\MasaBerlakuSatsLn::validUntil() dengan aturan yang sama.
function parseIsoDate(value) {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value || '');

    return match ? new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3])) : null;
}

function formatIsoDate(date) {
    const pad = (number) => String(number).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

function addMonthsNoOverflow(date, months) {
    const target = new Date(date.getFullYear(), date.getMonth() + months, 1);
    const lastDay = new Date(target.getFullYear(), target.getMonth() + 1, 0).getDate();
    target.setDate(Math.min(date.getDate(), lastDay));

    return target;
}

function satsLnValidUntil(publishDate, interval) {
    switch (interval) {
        case '3_bulan':
            return addMonthsNoOverflow(publishDate, 3);
        case '6_bulan':
            return addMonthsNoOverflow(publishDate, 6);
        case 'full_date':
            return new Date(publishDate.getFullYear() + 1, 2, 31);
        default:
            return null;
    }
}

function syncSatsLnValidUntil(form) {
    const publishDate = parseIsoDate(form.querySelector('[data-satsln-verify-publish-date]')?.value);
    const interval = form.querySelector('[data-satsln-verify-interval]')?.value;
    const validUntil = publishDate ? satsLnValidUntil(publishDate, interval) : null;
    const output = form.querySelector('[data-satsln-verify-valid-until]');

    if (output) {
        output.value = validUntil ? formatIsoDate(validUntil) : '';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-satsln-verify-form]').forEach((form) => {
        const activeYear = localStorage.getItem('tahun_aktif');
        const yearInput = form.querySelector('[data-satsln-verify-year]');
        const prefix = form.querySelector('[data-satsln-verify-year-prefix]');

        // Verifikasi ulang: nomor sudah terikat tahun terbitnya, jadi Tahun Aktif tidak menimpanya.
        if (activeYear && /^\d{4}$/.test(activeYear) && yearInput && !yearInput.hasAttribute('data-satsln-verify-year-locked')) {
            yearInput.value = activeYear;

            // Tampil 4 digit (2026); yang tersimpan tetap prefix 2 digit (26) - lihat SatsLn::composeIssuedNumber().
            if (prefix) {
                prefix.textContent = activeYear;
            }
        }

        syncSatsLnValidUntil(form);
        setupSatsLnNumberCheck(form);
    });
});

// Ya => tampilkan semua field verifikasi; Tidak => hanya Pesan. Grup yang disembunyikan di-disable
// lewat <fieldset> supaya input-nya tidak ikut terkirim maupun memblok submit (atribut required).
document.addEventListener('change', function (event) {
    const radio = event.target.closest('[data-satsln-verify-agree]');

    if (!radio) {
        return;
    }

    radio.closest('[data-satsln-verify-form]').querySelectorAll('[data-satsln-verify-group]').forEach((group) => {
        const active = group.dataset.satslnVerifyGroup === radio.value;
        group.disabled = !active;
        group.classList.toggle('hidden', !active);
    });
});

// Cek live Nomor SATS-LN (pola sama dengan cek username di users-index.js) - validasi final tetap
// di server (VerifySatsLnRequest).
function setupSatsLnNumberCheck(form) {
    const input = form.querySelector('[data-satsln-number-check]');
    const errorEl = form.querySelector('[data-satsln-number-error]');
    const yearInput = form.querySelector('[data-satsln-verify-year]');

    if (!input || !errorEl) {
        return;
    }

    const showError = (message) => {
        errorEl.textContent = message || '';
        errorEl.classList.toggle('hidden', !message);
    };

    let timer;
    let latestRequest = 0;

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(async () => {
            const number = input.value.trim();

            if (!/^\d{1,5}$/.test(number)) {
                showError(null);

                return;
            }

            const requestId = ++latestRequest;
            const url = new URL(input.dataset.satslnNumberCheck, window.location.origin);
            url.searchParams.set('number', number);
            url.searchParams.set('year', yearInput?.value || '');

            try {
                const response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                });
                const result = await response.json();

                if (requestId === latestRequest) {
                    showError(result.available ? null : result.message);
                }
            } catch {
                // Gangguan jaringan - pengecekan server saat submit tetap jadi acuan.
            }
        }, 400);
    });
}

['change', 'input'].forEach((eventName) => {
    document.addEventListener(eventName, function (event) {
        if (!event.target.matches('[data-satsln-verify-publish-date], [data-satsln-verify-interval]')) {
            return;
        }

        syncSatsLnValidUntil(event.target.closest('[data-satsln-verify-form]'));
    });
});

// Modal Print/Cetak (verifikasi/show): URL PDF baru lengkap setelah penandatangan dipilih -
// ditaruh di data-document-viewer-url tombol "Tampilkan PDF" supaya modal viewer fullscreen
// (document-viewer.js) yang membuka PDF-nya.
document.addEventListener('change', function (event) {
    const select = event.target.closest('[data-satsln-print-signer]');

    if (!select) {
        return;
    }

    const button = document.querySelector('[data-satsln-print-show]');

    if (!button) {
        return;
    }

    const url = select.value
        ? button.dataset.satslnPrintUrl + '?penandatangan_id=' + encodeURIComponent(select.value)
        : '';

    button.setAttribute('data-document-viewer-url', url);
    button.setAttribute('data-document-viewer-download-url', url);
    button.disabled = !url;
});
