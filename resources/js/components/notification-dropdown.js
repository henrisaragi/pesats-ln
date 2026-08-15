// Wires the notification bell dropdown (resources/views/components/notification-dropdown.blade.php)
// to the database-backed notification endpoints (routes/notifications.php). Notifications are
// fetched on page load (for the badge) and again whenever the dropdown opens (Preline's
// "open.hs.dropdown" event, fired on the .hs-dropdown wrapper). Live push arrives separately via
// resources/js/echo.js, which calls window.refreshNotifications() exposed here so a new
// notification updates the badge/list without reopening the dropdown.

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';

    return div.innerHTML;
}

function renderItem(notification) {
    const unreadDot = notification.read ? '' : '<span class="w-1.5 h-1.5 bg-primary rounded-full shrink-0 mt-1.5"></span>';
    const isLink = Boolean(notification.url);
    const tag = isLink ? 'a' : 'div';
    const hrefAttr = isLink ? ` href="${escapeHtml(notification.url)}"` : '';

    return `
        <${tag}${hrefAttr} class="flex gap-3 p-4 items-start hover:bg-default-150 cursor-pointer ${notification.read ? '' : 'bg-primary/5'}"
             data-notification-id="${escapeHtml(notification.id)}" data-notification-item>
            <div class="size-10 rounded-md bg-primary/10 flex items-center justify-center shrink-0">
                <i class="size-5 text-primary" data-lucide="info"></i>
            </div>
            <div class="flex justify-between gap-2 w-full">
                <div>
                    <h6 class="mb-1 font-medium text-default-800 text-sm">${escapeHtml(notification.title)}</h6>
                    <p class="text-default-500 text-xs">${escapeHtml(notification.message)}</p>
                    <p class="flex items-center gap-1 text-default-400 text-xs mt-1.5">
                        <i class="size-3.5" data-lucide="clock"></i>
                        <span>${escapeHtml(notification.created_at)}</span>
                    </p>
                </div>
                ${unreadDot}
            </div>
        </${tag}>
    `;
}

function updateBadge(unreadCount) {
    const badge = document.querySelector('[data-notification-badge]');

    if (!badge) {
        return;
    }

    badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
    badge.classList.toggle('hidden', unreadCount <= 0);
}

function renderDropdown(dropdown, payload) {
    const { notifications, unread_count: unreadCount } = payload;

    updateBadge(unreadCount);

    dropdown.querySelectorAll('[data-notification-list]').forEach((container) => {
        const category = container.dataset.notificationList;
        const items = category === 'all'
            ? notifications
            : notifications.filter((notification) => notification.category === category);

        container.innerHTML = items.map(renderItem).join('');

        const empty = dropdown.querySelector(`[data-notification-empty="${category}"]`);

        if (empty) {
            empty.classList.toggle('hidden', items.length > 0);
        }
    });

    if (window.lucide) {
        window.lucide.createIcons();
    }
}

function fetchNotifications(dropdown) {
    return fetch('/notifications', {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
        .then((response) => response.json())
        .then((payload) => renderDropdown(dropdown, payload));
}

document.addEventListener('DOMContentLoaded', function () {
    const dropdown = document.getElementById('notification-dropdown');

    if (!dropdown) {
        return;
    }

    dropdown.addEventListener('open.hs.dropdown', function () {
        fetchNotifications(dropdown);
    });

    dropdown.addEventListener('click', function (event) {
        const item = event.target.closest('[data-notification-item]');

        if (!item) {
            return;
        }

        const id = item.getAttribute('data-notification-id');
        const url = item.getAttribute('href');

        if (url) {
            event.preventDefault();
        }

        fetch(`/notifications/${id}/read`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).finally(() => {
            if (url) {
                window.location.href = url;
            } else {
                fetchNotifications(dropdown);
            }
        });
    });

    const markAllButton = dropdown.querySelector('[data-notification-mark-all]');

    markAllButton?.addEventListener('click', function () {
        fetch('/notifications/read-all', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).finally(() => fetchNotifications(dropdown));
    });

    // Called by resources/js/echo.js when a notification is pushed live over Reverb, so the
    // badge/list stay in sync even if the dropdown is already open.
    window.refreshNotifications = function () {
        fetchNotifications(dropdown);
    };

    fetchNotifications(dropdown);

    // Reverb's live push (resources/js/echo.js) updates this instantly when it can reach the
    // browser, but a private WebSocket channel isn't guaranteed to connect in every environment
    // (e.g. mixed-content blocks a plain ws:// endpoint from an https:// page in local dev) - so
    // this poll is the mechanism that's guaranteed to work everywhere, keeping the badge/list
    // fresh without the user having to open the dropdown first.
    setInterval(() => fetchNotifications(dropdown), 20000);
});
