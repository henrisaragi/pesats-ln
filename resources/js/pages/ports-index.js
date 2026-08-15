import { initDataTable } from '../data-table.js';

document.addEventListener('DOMContentLoaded', function () {
    const tableEl = document.getElementById('ports-table');

    if (!tableEl) {
        return;
    }

    const table = initDataTable('#ports-table', {
        ajax: {
            url: tableEl.dataset.ajaxUrl,
            data: function (params) {
                params.country_id = document.getElementById('ports-filter-country')?.value || '';
            },
        },
        columns: [
            { data: 'code' },
            { data: 'name' },
            { data: 'country_name', orderable: false, searchable: false },
            { data: 'type_label', orderable: false, searchable: false },
            { data: 'action', orderable: false, searchable: false },
        ],
    });

    const searchInput = document.getElementById('ports-search');
    searchInput?.addEventListener('keyup', function () {
        table.search(searchInput.value).draw();
    });

    const countryFilter = document.getElementById('ports-filter-country');
    countryFilter?.addEventListener('change', function () {
        table.draw();
    });

    const editModal = document.getElementById('port-edit-modal');
    const editForm = document.getElementById('port-edit-form');

    document.addEventListener('click', function (event) {
        const editTrigger = event.target.closest('[data-port-edit]');

        if (editTrigger && editForm) {
            const { id, code, name, countryId, type } = editTrigger.dataset;

            editForm.action = editForm.dataset.actionTemplate.replace('__ID__', id);
            editForm.querySelector('[name="_modal_port_id"]').value = id;
            editForm.querySelector('[name="country_id"]').value = countryId || '';
            editForm.querySelector('[name="code"]').value = code || '';
            editForm.querySelector('[name="name"]').value = name || '';
            editForm.querySelector('[name="type"]').value = type || 'sea';

            window.HSOverlay?.open(editModal);

            return;
        }

        const deleteTrigger = event.target.closest('[data-port-delete]');
        const deleteModal = document.getElementById('port-delete-modal');
        const deleteForm = document.getElementById('port-delete-form');

        if (deleteTrigger && deleteModal && deleteForm) {
            deleteForm.action = deleteForm.dataset.actionTemplate.replace('__ID__', deleteTrigger.dataset.id);
            deleteModal.querySelector('[data-delete-name]').textContent = deleteTrigger.dataset.name || '';

            window.HSOverlay?.open(deleteModal);
        }
    });
});
