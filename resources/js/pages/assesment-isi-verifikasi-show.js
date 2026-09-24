// Halaman detail verifikasi Assesment (resources/views/assesment-isi/verifikasi/show.blade.php):
// - switcher bukti dukung: ganti src iframe tanpa reload halaman
// - per pertanyaan: checkbox "jawaban sesuai" mengembalikan pilihan ke jawaban entitas, memilih
//   jawaban lain di <select> otomatis meng-uncheck "sesuai"
// - skor pembanding dihitung murni dari data-skor pada <option> (sudah di-render server, tanpa
//   request tambahan) setiap kali pilihan berubah

const bukti = document.querySelector('[data-bukti-switcher]');
const buktiFrame = document.querySelector('[data-bukti-frame]');

bukti?.addEventListener('change', function () {
    const option = bukti.options[bukti.selectedIndex];

    if (buktiFrame && option) {
        buktiFrame.src = option.dataset.previewUrl;
    }
});

document.querySelectorAll('[data-pertanyaan-koreksi]').forEach(function (card) {
    const toggle = card.querySelector('[data-sesuai-toggle]');
    const select = card.querySelector('[data-jawaban-verifikator-select]');
    const skorLabel = card.querySelector('[data-skor-verifikator]');

    if (!select) {
        return;
    }

    function updateSkorLabel() {
        const option = select.options[select.selectedIndex];
        skorLabel.textContent = option?.dataset.skor ?? '-';
    }

    updateSkorLabel();

    toggle?.addEventListener('change', function () {
        if (toggle.checked) {
            select.value = select.dataset.jawabanEntitasId || select.value;
            updateSkorLabel();
        }
    });

    select.addEventListener('change', function () {
        if (toggle && select.value !== select.dataset.jawabanEntitasId) {
            toggle.checked = false;
        } else if (toggle) {
            toggle.checked = true;
        }

        updateSkorLabel();
    });
});
