import { initDataTable } from '../data-table.js';

document.addEventListener('DOMContentLoaded', function () {
    const tableEl = document.getElementById('users-table');

    if (!tableEl) {
        return;
    }

    const table = initDataTable('#users-table', {
        ajax: {
            url: tableEl.dataset.ajaxUrl,
            data: function (params) {
                params.entity_id = document.getElementById('users-filter-entity')?.value || '';
            },
        },
        columns: [
            {
                data: 'name',
                render: function (data, type, row) {
                    if (type !== 'display') {
                        return data;
                    }

                    const jabatan = row.jabatan ? `<p class="text-default-500">${row.jabatan}</p>` : '';

                    return `<h6 class="mb-1.5 font-semibold">${data}</h6>${jabatan}`;
                },
            },
            { data: 'username' },
            { data: 'email' },
            { data: 'entity_name' },
            { data: 'role_name' },
            {
                data: 'nip',
                render: (data) => data ?? '-',
            },
            { data: 'status', orderable: false, searchable: false },
            { data: 'created_at' },
            { data: 'action', orderable: false, searchable: false },
        ],
    });

    const searchInput = document.getElementById('users-search');
    searchInput?.addEventListener('keyup', function () {
        table.search(searchInput.value).draw();
    });

    const entityFilter = document.getElementById('users-filter-entity');
    entityFilter?.addEventListener('change', function () {
        table.draw();
    });

    const editModal = document.getElementById('user-edit-modal');
    const editForm = document.getElementById('user-edit-form');

    document.addEventListener('click', function (event) {
        const editTrigger = event.target.closest('[data-user-edit]');

        if (editTrigger && editForm) {
            const { id, name, username, email, entityId, nip, jabatan, role, isActive } = editTrigger.dataset;

            editForm.action = editForm.dataset.actionTemplate.replace('__ID__', id);
            editForm.querySelector('[name="_modal_user_id"]').value = id;
            editForm.querySelector('[name="name"]').value = name || '';
            editForm.querySelector('[name="username"]').value = username || '';
            editForm.querySelector('[name="email"]').value = email || '';
            editForm.querySelector('[name="entity_id"]').value = entityId || '';
            editForm.querySelector('[name="nip"]').value = nip || '';
            editForm.querySelector('[name="jabatan"]').value = jabatan || '';
            editForm.querySelector('[name="role"]').value = role || '';
            editForm.querySelector('[name="password"]').value = '';
            editForm.querySelector('input[name="is_active"][type="checkbox"]').checked = isActive === '1';

            window.HSOverlay?.open(editModal);

            return;
        }

        const deleteTrigger = event.target.closest('[data-user-delete]');
        const deleteModal = document.getElementById('user-delete-modal');
        const deleteForm = document.getElementById('user-delete-form');

        if (deleteTrigger && deleteModal && deleteForm) {
            deleteForm.action = deleteForm.dataset.actionTemplate.replace('__ID__', deleteTrigger.dataset.id);
            deleteModal.querySelector('[data-delete-name]').textContent = deleteTrigger.dataset.name || '';

            window.HSOverlay?.open(deleteModal);
        }
    });
});
