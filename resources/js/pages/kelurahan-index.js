document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (event) {
        const deleteTrigger = event.target.closest('[data-kelurahan-delete]');
        const deleteModal = document.getElementById('kelurahan-delete-modal');
        const deleteForm = document.getElementById('kelurahan-delete-form');

        if (deleteTrigger && deleteModal && deleteForm) {
            deleteForm.action = deleteForm.dataset.actionTemplate.replace('__ID__', deleteTrigger.dataset.id);
            deleteModal.querySelector('[data-delete-name]').textContent = deleteTrigger.dataset.name || '';

            window.HSOverlay?.open(deleteModal);
        }
    });
});
