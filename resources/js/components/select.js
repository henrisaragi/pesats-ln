import Choices from 'choices.js';

// Indonesian text for every Choices.js instance created from [data-choice] selects
// (see resources/views/components/select.blade.php and the country/province/city/
// district/sub-district/port select components in app/View/Components).
const CHOICES_I18N = {
    loadingText: 'Memuat...',
    noResultsText: 'Tidak ada hasil ditemukan',
    noChoicesText: 'Tidak ada pilihan tersedia',
    itemSelectText: 'Tekan untuk memilih',
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
        itemSelectText: CHOICES_I18N.itemSelectText,
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

async function fetchOptions(url, parentValue) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const response = await fetch(url.replace('__PARENT__', encodeURIComponent(parentValue)), {
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

function initCascading(el) {
    const parent = document.getElementById(el.dataset.choiceDepends);
    const remoteUrlTemplate = el.dataset.choiceUrl;
    const placeholder = placeholderLabel(el);
    const hasPreselected = el.value !== '';

    const instance = new Choices(el, baseOptions());

    if (!parent) {
        return instance;
    }

    if (!hasPreselected) {
        instance.disable();
    }

    async function loadOptions(parentValue, { silent = false } = {}) {
        if (!parentValue) {
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

        const items = await fetchOptions(remoteUrlTemplate, parentValue);
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

    parent.addEventListener('change', () => loadOptions(parent.value));

    return instance;
}

export function initSelects(root = document) {
    root.querySelectorAll('[data-choice]:not([data-choice-initialized])').forEach((el) => {
        el.setAttribute('data-choice-initialized', 'true');

        if (el.dataset.choiceDepends) {
            initCascading(el);
        } else {
            initStatic(el);
        }
    });
}

document.addEventListener('DOMContentLoaded', () => initSelects());

// Re-run for content injected later (e.g. rows/forms loaded via AJAX), mirroring
// window.lucideCreateIcons in resources/js/vendor.js.
window.initChoices = initSelects;
