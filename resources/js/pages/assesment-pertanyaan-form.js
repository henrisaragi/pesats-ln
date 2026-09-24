import '../components/repeatable-rows.js';

// Halaman builder pertanyaan Assesment (resources/views/assesment/pertanyaan/index.blade.php):
// - typeahead Aspek/Indikator Penilaian (>= 3 huruf, cari lewat AssesmentAspekController@search)
// - toggle input bobot per komoditas yang dicentang
// - toggle kolom skor per komoditas (di setiap baris jawaban, termasuk baris baru dari
//   repeatable-rows.js) mengikuti komoditas yang dicentang
// - checkbox "skor sama" per baris jawaban: menonaktifkan & menyamakan input skor antar komoditas

function debounce(fn, delay) {
    let timeout;

    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn.apply(this, args), delay);
    };
}

function checkedKomoditasIds() {
    return Array.from(document.querySelectorAll('[data-komoditas-checkbox]:checked')).map(function (el) {
        return el.value;
    });
}

function syncBobotVisibility() {
    const checkedIds = checkedKomoditasIds();

    document.querySelectorAll('[data-bobot-input]').forEach(function (input) {
        const visible = checkedIds.includes(input.dataset.komoditasId);
        input.classList.toggle('hidden', !visible);

        if (!visible) {
            input.value = '';
        }
    });
}

function syncSkorFieldsForRow(row) {
    const checkedIds = checkedKomoditasIds();

    row.querySelectorAll('[data-skor-field]').forEach(function (field) {
        const visible = checkedIds.includes(field.dataset.komoditasId);
        field.classList.toggle('hidden', !visible);

        const input = field.querySelector('[data-skor-input]');

        if (input && !visible) {
            input.value = '';
        }
    });
}

function syncAllSkorFields() {
    document.querySelectorAll('[data-jawaban-row]').forEach(syncSkorFieldsForRow);
}

function wireSkorSamaToggle(row) {
    const toggle = row.querySelector('[data-skor-sama-toggle]');

    if (!toggle) {
        return;
    }

    function applySama() {
        const visibleInputs = Array.from(row.querySelectorAll('[data-skor-field]:not(.hidden) [data-skor-input]'));

        if (visibleInputs.length === 0) {
            return;
        }

        const [first, ...rest] = visibleInputs;

        // readOnly (bukan disabled) - input disabled TIDAK ikut terkirim sama sekali saat form
        // di-submit (beda dari field yang cuma disembunyikan via class "hidden"), jadi skor
        // komoditas lain akan hilang total dari request kalau dipakai "disabled" di sini.
        rest.forEach(function (input) {
            input.readOnly = toggle.checked;
            input.classList.toggle('bg-default-100', toggle.checked);

            if (toggle.checked) {
                input.value = first.value;
            }
        });
    }

    toggle.addEventListener('change', applySama);

    row.querySelectorAll('[data-skor-input]').forEach(function (input) {
        input.addEventListener('input', function () {
            if (toggle.checked) {
                applySama();
            }
        });
    });
}

function wireJawabanRow(row) {
    syncSkorFieldsForRow(row);
    wireSkorSamaToggle(row);
}

document.addEventListener('DOMContentLoaded', function () {
    syncBobotVisibility();
    syncAllSkorFields();

    document.querySelectorAll('[data-jawaban-row]').forEach(wireJawabanRow);

    document.querySelectorAll('[data-komoditas-checkbox]').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            syncBobotVisibility();
            syncAllSkorFields();
        });
    });

    const jawabanRepeatable = document.getElementById('pertanyaan-jawaban-repeatable');

    jawabanRepeatable?.addEventListener('repeatable:added', function (event) {
        wireJawabanRow(event.detail.row);
    });

    const aspekInput = document.querySelector('[data-aspek-input]');
    const suggestions = document.querySelector('[data-aspek-suggestions]');
    const searchUrl = aspekInput?.dataset.aspekSearchUrl;

    if (!aspekInput || !suggestions || !searchUrl) {
        return;
    }

    function hideSuggestions() {
        suggestions.classList.add('hidden');
        suggestions.innerHTML = '';
    }

    function renderSuggestions(items) {
        if (items.length === 0) {
            hideSuggestions();

            return;
        }

        suggestions.innerHTML = items.map(function (item) {
            return `<button type="button" class="block w-full text-start px-3 py-2 text-sm hover:bg-default-150" data-aspek-suggestion="${item.id}">${item.nama}</button>`;
        }).join('');

        suggestions.classList.remove('hidden');
    }

    const search = debounce(function (query) {
        if (query.length < 3) {
            hideSuggestions();

            return;
        }

        fetch(`${searchUrl}?q=${encodeURIComponent(query)}`, {headers: {Accept: 'application/json'}})
            .then((response) => response.json())
            .then(renderSuggestions)
            .catch(() => hideSuggestions());
    }, 300);

    aspekInput.addEventListener('input', function () {
        search(aspekInput.value.trim());
    });

    suggestions.addEventListener('click', function (event) {
        const button = event.target.closest('[data-aspek-suggestion]');

        if (!button) {
            return;
        }

        aspekInput.value = button.textContent.trim();
        hideSuggestions();
    });

    document.addEventListener('click', function (event) {
        if (!aspekInput.contains(event.target) && !suggestions.contains(event.target)) {
            hideSuggestions();
        }
    });
});
