document.addEventListener('DOMContentLoaded', function () {
    const editModal = document.getElementById('kecamatan-edit-modal');
    const editForm = document.getElementById('kecamatan-edit-form');

    document.addEventListener('click', function (event) {
        const editTrigger = event.target.closest('[data-kecamatan-edit]');

        if (editTrigger && editForm) {
            const { id, code, name, kotaId } = editTrigger.dataset;

            editForm.action = editForm.dataset.actionTemplate.replace('__ID__', id);
            editForm.querySelector('[name="_modal_kecamatan_id"]').value = id;
            editForm.querySelector('[name="kota_id"]').value = kotaId || '';
            editForm.querySelector('[name="code"]').value = code || '';
            editForm.querySelector('[name="name"]').value = name || '';

            window.HSOverlay?.open(editModal);

            return;
        }

        const deleteTrigger = event.target.closest('[data-kecamatan-delete]');
        const deleteModal = document.getElementById('kecamatan-delete-modal');
        const deleteForm = document.getElementById('kecamatan-delete-form');

        if (deleteTrigger && deleteModal && deleteForm) {
            deleteForm.action = deleteForm.dataset.actionTemplate.replace('__ID__', deleteTrigger.dataset.id);
            deleteModal.querySelector('[data-delete-name]').textContent = deleteTrigger.dataset.name || '';

            window.HSOverlay?.open(deleteModal);
        }
    });
});
