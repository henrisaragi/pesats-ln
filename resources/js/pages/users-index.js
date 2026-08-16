import { initDataTable } from '../data-table.js';

function debounce(fn, delay) {
    let timer;

    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}

// Strips whitespace as the user types, keeping the caret in place relative to the remaining text.
function stripSpaces(input) {
    const original = input.value;
    const cursor = input.selectionStart ?? original.length;
    const spacesBeforeCursor = (original.slice(0, cursor).match(/ /g) || []).length;
    const stripped = original.replace(/\s+/g, '');

    if (stripped !== original) {
        input.value = stripped;
        const newCursor = Math.max(0, cursor - spacesBeforeCursor);
        input.setSelectionRange(newCursor, newCursor);
    }

    return stripped;
}

function setupUsernameCheck(input) {
    const errorEl = input.parentElement.querySelector('[data-username-error]');

    const showError = (message) => {
        if (!errorEl) {
            return;
        }

        errorEl.textContent = message || '';
        errorEl.classList.toggle('hidden', !message);
    };

    const check = debounce(async (username) => {
        if (!username) {
            showError(null);

            return;
        }

        const excludeId = input.closest('form')?.querySelector('[name="_modal_user_id"]')?.value || '';
        const params = new URLSearchParams({ username });

        if (excludeId) {
            params.set('exclude', excludeId);
        }

        try {
            const response = await fetch(`${input.dataset.checkUrl}?${params.toString()}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const result = await response.json();

            showError(result.available ? null : result.message);
        } catch {
            // Network hiccup - the server-side check on submit remains the source of truth.
        }
    }, 400);

    input.addEventListener('input', () => check(stripSpaces(input)));
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-username-check]').forEach(setupUsernameCheck);

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
            const { id, name, username, email, entityId, nip, jabatan, phoneNumber, role, isActive } = editTrigger.dataset;

            editForm.action = editForm.dataset.actionTemplate.replace('__ID__', id);
            editForm.querySelector('[name="_modal_user_id"]').value = id;
            editForm.querySelector('[name="name"]').value = name || '';
            editForm.querySelector('[name="username"]').value = username || '';
            editForm.querySelector('[name="email"]').value = email || '';
            // entity_id/role are only editable <select>s for super-admins; for everyone
            // else they're locked hidden inputs (set server-side) and must stay untouched.
            const entityField = editForm.querySelector('[name="entity_id"]');
            if (entityField?.tagName === 'SELECT') {
                entityField.value = entityId || '';
            }

            const roleField = editForm.querySelector('[name="role"]');
            if (roleField?.tagName === 'SELECT') {
                roleField.value = role || '';
            }

            editForm.querySelector('[name="nip"]').value = nip || '';
            editForm.querySelector('[name="jabatan"]').value = jabatan || '';
            editForm.querySelector('[name="phone_number"]').value = phoneNumber || '';
            editForm.querySelector('[name="password"]').value = '';
            editForm.querySelector('input[name="is_active"][type="checkbox"]').checked = isActive === '1';
            editForm.querySelector('[data-username-error]')?.classList.add('hidden');

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
