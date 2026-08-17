import Choices from 'choices.js';

// Indonesian text for every Choices.js instance created from [data-choice] selects
// (see resources/views/components/select.blade.php and the country/province/city/
// district/sub-district/port select components in app/View/Components).
const CHOICES_I18N = {
    loadingText: 'Memuat...',
    noResultsText: 'Tidak ada hasil ditemukan',
    noChoicesText: 'Tidak ada pilihan tersedia',
    // Kosong sengaja - hint "tekan untuk memilih" yang muncul di sebelah kanan opsi yang
    // di-highlight dihapus atas permintaan user (lihat juga override CSS di _choices.css yang
    // menghapus padding kosong yang tadinya disediakan untuk teks ini).
    itemSelectText: '',
    uniqueItemText: 'Nilai ini sudah dipilih',
    addItemText: (value) => `Tekan Enter untuk menambahkan "${value}"`,
};

function baseOptions() {
    return {
        ...CHOICES_I18N,
        searchEnabled: true,
        searchResultLimit: 50,
        shouldSort: false,
        allowHTML: false,
        placeholder: true,
        classNames: {
            containerOuter: ['choices', 'choices--pesats'],
        },
    };
}

function placeholderLabel(el) {
    return el.querySelector('option[value=""]')?.textContent?.trim() || 'Pilih';
}

function initStatic(el) {
    return new Choices(el, baseOptions());
}

async function fetchOptions(url) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
        },
    });

    if (!response.ok) {
        return [];
    }

    return response.json();
}

// Most cascading selects depend on a single parent (e.g. kota depends-on provinsi), substituted
// into "__PARENT__" in data-choice-url. A select can optionally also declare data-choice-depends2
// and data-choice-depends3 (up to 2 extra parent element ids) - their values are substituted into
// "__PARENT2__" / "__PARENT3__" in the same URL, for the rare case a remote endpoint needs several
// selected values at once (e.g. kuota-perusahaan's trade-code/species pickers, which narrow down
// through komoditas + status asal (+ kode perdagangan) - see resources/views/kuota-perusahaan).
const PARENT_KEYS = ['choiceDepends', 'choiceDepends2', 'choiceDepends3'];
const PARENT_PLACEHOLDERS = ['__PARENT__', '__PARENT2__', '__PARENT3__'];

function initCascading(el) {
    const parents = PARENT_KEYS.map((key) => (el.dataset[key] ? document.getElementById(el.dataset[key]) : null));
    const remoteUrlTemplate = el.dataset.choiceUrl;
    const placeholder = placeholderLabel(el);
    const hasPreselected = el.value !== '';

    const instance = new Choices(el, baseOptions());

    if (!parents[0]) {
        return instance;
    }

    if (!hasPreselected) {
        instance.disable();
    }

    async function loadOptions({ silent = false } = {}) {
        const values = parents.map((parent) => parent?.value);

        if (parents.some((parent, index) => parent && !values[index])) {
            instance.setChoices([{ value: '', label: placeholder, placeholder: true, selected: true }], 'value', 'label', true);
            instance.disable();
            if (!silent) {
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }

            return;
        }

        instance.disable();
        instance.setChoices(
            [{ value: '', label: 'Memuat...', placeholder: true, selected: true, disabled: true }],
            'value',
            'label',
            true,
        );

        let url = remoteUrlTemplate;
        parents.forEach((parent, index) => {
            if (parent) {
                url = url.replace(PARENT_PLACEHOLDERS[index], encodeURIComponent(values[index]));
            }
        });

        const items = await fetchOptions(url);
        const choices = [
            { value: '', label: placeholder, placeholder: true, selected: true },
            ...items.map((item) => ({ value: String(item.id), label: item.name })),
        ];

        instance.setChoices(choices, 'value', 'label', true);
        instance.enable();

        if (!silent) {
            el.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    parents.forEach((parent) => parent?.addEventListener('change', () => loadOptions()));

    return instance;
}

export function initSelects(root = document) {
    root.querySelectorAll('[data-choice]:not([data-choice-initialized])').forEach((el) => {
        el.setAttribute('data-choice-initialized', 'true');

        // Stashed on the element so page-specific scripts can drive the Choices.js widget
        // programmatically (enable/disable, setChoiceByValue) - see resources/js/pages/documents-index.js
        // for the "lock country to Indonesia unless the document type allows a free choice" case.
        el._choices = el.dataset.choiceDepends ? initCascading(el) : initStatic(el);
    });
}

document.addEventListener('DOMContentLoaded', () => initSelects());

// Re-run for content injected later (e.g. rows/forms loaded via AJAX), mirroring
// window.lucideCreateIcons in resources/js/vendor.js.
window.initChoices = initSelects;
