// Wires the notification bell dropdown (resources/views/components/notification-dropdown.blade.php)
// to the database-backed notification endpoints (routes/notifications.php). Notifications are
// fetched on page load (for the badge) and again whenever the dropdown opens (Preline's
// "open.hs.dropdown" event, fired on the .hs-dropdown wrapper). Live push arrives separately via
// resources/js/echo.js, which calls window.refreshNotifications() exposed here so a new
// notification updates the badge/list without reopening the dropdown.

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
}

function appUrl(path) {
    const base = document.querySelector('meta[name="app-url"]')?.getAttribute('content') ?? '';

    return base.replace(/\/+$/, '') + path;
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
    queueNotificationAlerts(notifications);

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

    // Icons in dynamically-inserted HTML need to be re-scanned - lucide isn't exposed as
    // window.lucide in this app, see resources/js/vendor.js's window.lucideCreateIcons wrapper.
    window.lucideCreateIcons?.();
}

// isBackgroundPoll: request otomatis (load awal + interval) dikirim dengan header X-Background-Poll
// supaya tidak dihitung aktivitas user oleh App\Http\Middleware\EnsureAuthSessionIsFresh.
function fetchNotifications(dropdown, isBackgroundPoll = false) {
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    if (isBackgroundPoll) {
        headers['X-Background-Poll'] = '1';
    }

    return fetch(appUrl('/notifications'), { headers })
        .then((response) => {
            // Sesi habis/idle: reload supaya middleware auth mengarahkan ke halaman input ulang
            // password (bukan menampilkan "undefined" di badge).
            if (response.status === 401 || response.status === 419) {
                window.location.reload();

                return null;
            }

            return response.ok ? response.json() : null;
        })
        .then((payload) => {
            if (payload) {
                renderDropdown(dropdown, payload);
            }
        })
        .catch(() => {});
}

// Auto-popup (resources/views/components/notification-alert-modal.blade.php) for notifications
// that arrive *after* the page has loaded - never for what's already there on first fetch, so
// opening the page doesn't dump a wall of popups for old unread notifications. Only ever runs in
// the browser tab of whichever session is actively viewing the page, since it's driven purely by
// this session's own poll/push cycle.
let knownNotificationIds = null;
const pendingAlerts = [];
let alertShowing = false;

function playNotificationSound() {
    const audio = new Audio(appUrl('/sounds/notification.mp3'));
    audio.play().catch(() => {
        // Browsers block autoplay before the user has interacted with the page at all -
        // nothing to do about that, the popup itself still shows.
    });
}

function isOnChatPage() {
    return document.querySelector('[data-chat-page]') !== null;
}

function showNextAlert() {
    if (alertShowing || pendingAlerts.length === 0 || !window.HSOverlay) {
        return;
    }

    if (isOnChatPage()) {
        // No popup/sound while actively on the Chat page - real-time delivery there (see
        // resources/js/pages/chat.js) already shows the message live, and any non-chat
        // notification isn't urgent enough to interrupt an active conversation. The badge/list
        // still update normally (see fetchNotifications) - just no popup + sound.
        pendingAlerts.length = 0;

        return;
    }

    const modal = document.getElementById('notification-alert-modal');

    if (!modal) {
        return;
    }

    const notification = pendingAlerts.shift();
    alertShowing = true;

    modal.querySelector('[data-notification-alert-title]').textContent = notification.title ?? '';
    modal.querySelector('[data-notification-alert-message]').textContent = notification.message ?? '';
    modal.dataset.notificationAlertId = notification.id;
    modal.dataset.notificationAlertUrl = notification.url ?? '';

    playNotificationSound();
    window.HSOverlay.open(modal);
}

function queueNotificationAlerts(notifications) {
    if (knownNotificationIds === null) {
        // First fetch since page load - this is the existing baseline, not new arrivals.
        knownNotificationIds = new Set(notifications.map((notification) => notification.id));

        return;
    }

    notifications
        .filter((notification) => !notification.read && !knownNotificationIds.has(notification.id))
        .forEach((notification) => pendingAlerts.push(notification));

    notifications.forEach((notification) => knownNotificationIds.add(notification.id));

    showNextAlert();
}

document.addEventListener('DOMContentLoaded', function () {
    const dropdown = document.getElementById('notification-dropdown');
    const alertModal = document.getElementById('notification-alert-modal');

    alertModal?.addEventListener('close.hs.overlay', function () {
        alertShowing = false;
        setTimeout(showNextAlert, 400);
    });

    alertModal?.querySelector('[data-notification-alert-later]')?.addEventListener('click', function () {
        window.HSOverlay?.close(alertModal);
    });

    alertModal?.querySelector('[data-notification-alert-view]')?.addEventListener('click', function () {
        const id = alertModal.dataset.notificationAlertId;
        const url = alertModal.dataset.notificationAlertUrl;

        fetch(appUrl(`/notifications/${id}/read`), {
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
                window.HSOverlay?.close(alertModal);
            }
        });
    });

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

        fetch(appUrl(`/notifications/${id}/read`), {
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
        fetch(appUrl('/notifications/read-all'), {
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
        fetchNotifications(dropdown, true);
    };

    fetchNotifications(dropdown, true);

    // General notifications have no real-time push (see resources/js/echo.js) - this poll is
    // the only delivery mechanism, so it's kept close to real-time (3s, the fastest interval
    // used anywhere in the app) without the user having to open the dropdown first.
    setInterval(() => fetchNotifications(dropdown, true), 3000);
});
