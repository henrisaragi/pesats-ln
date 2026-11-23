// Wizard verifikasi Assesment (resources/views/assesment-isi/verifikasi/show.blade.php):
// - satu langkah = satu pertanyaan; indikator panah di atas menandai langkah aktif/tersimpan
// - "Simpan & Lanjut" menyimpan koreksi langkah itu via AJAX
//   (AssesmentIsiVerifikasiController::saveJawaban) lalu otomatis pindah ke langkah berikutnya;
//   "Sebelumnya" kembali tanpa menyimpan. Langkah terakhir menyimpan lalu submit form complete.
// - tombol "Lihat" bukti dukung di tiap langkah menampilkan pratinjau di iframe samping
// - checkbox "jawaban sesuai" mengembalikan pilihan ke jawaban entitas; memilih jawaban lain
//   otomatis meng-uncheck. Skor pembanding diambil dari data-skor pada <option>.

const form = document.querySelector('[data-verifikasi-wizard]');

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
}

if (form) {
    const steps = Array.from(form.querySelectorAll('[data-langkah]'));
    const navButtons = Array.from(form.querySelectorAll('[data-langkah-nav]'));
    const buktiFrame = form.querySelector('[data-bukti-frame]');
    const buktiKosong = form.querySelector('[data-bukti-kosong]');
    const buktiJudul = form.querySelector('[data-bukti-judul]');
    const saved = navButtons.map((button) => button.dataset.tersimpan === '1');
    const readonly = form.dataset.readonly === '1';
    let current = Number(form.dataset.langkahAwal || 0);

    function showBukti(url, label) {
        if (!buktiFrame) {
            return;
        }

        if (url) {
            buktiFrame.src = url;
            buktiFrame.classList.remove('hidden');
            buktiKosong?.classList.add('hidden');
        } else {
            buktiFrame.removeAttribute('src');
            buktiFrame.classList.add('hidden');
            buktiKosong?.classList.remove('hidden');
        }

        if (buktiJudul) {
            buktiJudul.textContent = label || '';
        }
    }

    // Langkah boleh dibuka lewat indikator kalau sudah tersimpan atau langkah pertama yang belum.
    function reachable(index) {
        return readonly || index === 0 || saved[index] || saved[index - 1];
    }

    function renderNav() {
        navButtons.forEach(function (button, index) {
            const active = index === current;

            button.classList.toggle('bg-primary', active);
            button.classList.toggle('text-white', active);
            button.classList.toggle('bg-success/15', !active && saved[index]);
            button.classList.toggle('text-success', !active && saved[index]);
            button.classList.toggle('bg-default-200', !active && !saved[index]);
            button.classList.toggle('text-default-600', !active && !saved[index]);
            button.querySelector('[data-langkah-check]')?.classList.toggle('hidden', !saved[index]);
            button.disabled = !reachable(index);

            if (active) {
                button.setAttribute('aria-current', 'step');
                button.scrollIntoView({block: 'nearest', inline: 'center'});
            } else {
                button.removeAttribute('aria-current');
            }
        });
    }

    function goTo(index) {
        current = Math.max(0, Math.min(index, steps.length - 1));

        steps.forEach((step, i) => step.classList.toggle('hidden', i !== current));
        renderNav();

        const firstBukti = steps[current]?.querySelector('[data-bukti-view]');
        showBukti(firstBukti?.dataset.buktiView, firstBukti?.dataset.buktiLabel);
    }

    function save(step) {
        const select = step.querySelector('[data-jawaban-verifikator-select]');

        return fetch(step.dataset.simpanUrl, {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json', 'Content-Type': 'application/json'},
            body: JSON.stringify({jawaban_id: select.value}),
        }).then(async function (response) {
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(data.message || 'Gagal menyimpan jawaban.');
            }
        });
    }

    steps.forEach(function (step, index) {
        const toggle = step.querySelector('[data-sesuai-toggle]');
        const select = step.querySelector('[data-jawaban-verifikator-select]');
        const skorLabel = step.querySelector('[data-skor-verifikator]');
        const nextButton = step.querySelector('[data-langkah-next]');
        const loading = step.querySelector('[data-simpan-loading]');
        const error = step.querySelector('[data-simpan-error]');

        function updateSkorLabel() {
            skorLabel.textContent = select.options[select.selectedIndex]?.dataset.skor ?? '-';
        }

        updateSkorLabel();

        toggle?.addEventListener('change', function () {
            if (toggle.checked) {
                select.value = select.dataset.jawabanEntitasId || select.value;
                updateSkorLabel();
            }
        });

        select.addEventListener('change', function () {
            if (toggle) {
                toggle.checked = select.value === select.dataset.jawabanEntitasId;
            }

            updateSkorLabel();
        });

        step.querySelectorAll('[data-bukti-view]').forEach(function (button) {
            button.addEventListener('click', () => showBukti(button.dataset.buktiView, button.dataset.buktiLabel));
        });

        step.querySelector('[data-langkah-back]')?.addEventListener('click', () => goTo(index - 1));

        nextButton?.addEventListener('click', function () {
            // Assesment yang sudah tidak Submitted hanya bisa dilihat: Next sekadar pindah langkah.
            if (readonly) {
                goTo(index + 1);

                return;
            }

            nextButton.disabled = true;
            loading?.classList.remove('hidden');
            error?.classList.add('hidden');

            save(step)
                .then(function () {
                    saved[index] = true;

                    if (index === steps.length - 1) {
                        form.submit();

                        return;
                    }

                    goTo(index + 1);
                })
                .catch(function (err) {
                    if (error) {
                        error.textContent = err.message;
                        error.classList.remove('hidden');
                    }
                })
                .finally(function () {
                    nextButton.disabled = false;
                    loading?.classList.add('hidden');
                });
        });
    });

    navButtons.forEach(function (button, index) {
        button.addEventListener('click', function () {
            if (reachable(index)) {
                goTo(index);
            }
        });
    });

    goTo(current);
}
