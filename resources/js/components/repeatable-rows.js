// Util generic untuk baris form yang bisa ditambah/dihapus (dipakai builder pertanyaan Assesment
// untuk baris jawaban & baris bukti dukung - lihat resources/js/pages/assesment-pertanyaan-form.js).
//
// Markup yang dibutuhkan per container:
//   <div data-repeatable>
//     <template data-repeatable-template>...markup 1 baris, name="xxx[__INDEX__][...]"...</template>
//     <div data-repeatable-rows>...baris awal (index 0) sudah dirender server-side...</div>
//     <button type="button" data-repeatable-add>+ Tambah</button>
//   </div>
// Setiap baris wajib punya elemen berpenanda [data-repeatable-row] dan tombol hapus
// [data-repeatable-remove] di dalamnya. __INDEX__ pada template diganti index baris berikutnya
// supaya name attribute jadi array PHP yang valid (jawaban[1][teks], dst).
//
// Event "repeatable:added" (detail: { row, container }) di-dispatch ke container setiap kali
// baris baru ditambah, supaya JS halaman bisa wiring perilaku tambahan pada baris itu (mis. toggle
// kolom skor per komoditas di assesment-pertanyaan-form.js).

function nextIndex(container) {
    const rows = container.querySelectorAll('[data-repeatable-rows] > [data-repeatable-row]');

    return rows.length;
}

function wireRemove(row, container) {
    const removeButtons = row.querySelectorAll('[data-repeatable-remove]');

    removeButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const rows = container.querySelectorAll('[data-repeatable-rows] > [data-repeatable-row]');

            if (rows.length <= 1) {
                return;
            }

            row.remove();
        });
    });
}

document.querySelectorAll('[data-repeatable]').forEach(function (container) {
    const template = container.querySelector('[data-repeatable-template]');
    const rowsContainer = container.querySelector('[data-repeatable-rows]');
    const addButton = container.querySelector('[data-repeatable-add]');

    if (!template || !rowsContainer || !addButton) {
        return;
    }

    rowsContainer.querySelectorAll('[data-repeatable-row]').forEach(function (row) {
        wireRemove(row, container);
    });

    addButton.addEventListener('click', function () {
        const index = nextIndex(container);
        const html = template.innerHTML.replaceAll('__INDEX__', String(index));
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        const row = wrapper.firstElementChild;

        if (!row) {
            return;
        }

        rowsContainer.appendChild(row);
        wireRemove(row, container);
        container.dispatchEvent(new CustomEvent('repeatable:added', {detail: {row: row, container: container}}));
    });
});
