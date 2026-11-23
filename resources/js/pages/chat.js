// Drives resources/views/chat/index.blade.php against routes/chat.php. Real-time delivery is
// dual-mechanism, same philosophy as resources/js/components/notification-dropdown.js: Echo/
// Reverb pushes updates instantly when it can connect (private channels "chat.queue" and
// "conversation.{id}", authorized in routes/channels.php, event name "message.sent" from
// App\Events\ChatMessageSent), and polling is the fallback that's guaranteed to work regardless
// of whether the WebSocket connects (see the mixed-content HTTPS/Herd caveat documented in
// claude/notif-task.md).

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
}

function appUrl(path) {
    const base = document.querySelector('meta[name="app-url"]')?.getAttribute('content') ?? '';

    return base.replace(/\/+$/, '') + path;
}

function jsonHeaders() {
    return { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
}

// Poll otomatis (daftar percakapan + pesan yang sedang dibuka) tidak dihitung aktivitas user oleh
// App\Http\Middleware\EnsureAuthSessionIsFresh - sama seperti poll lonceng notifikasi. Sesi
// yang basi dibalas 401, dan reload ke halaman input ulang password ditangani poll lonceng.
function backgroundPollHeaders() {
    return { ...jsonHeaders(), 'X-Background-Poll': '1' };
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';

    return div.innerHTML;
}

function initials(name) {
    if (!name) {
        return '?';
    }

    return name.trim().split(/\s+/).slice(0, 2).map((part) => part[0]).join('').toUpperCase();
}

function avatarMarkup(name) {
    return `<div class="flex items-center justify-center text-sm font-semibold rounded-full size-9 bg-default-200 text-default-700 shrink-0">${escapeHtml(initials(name))}</div>`;
}

function statusBadgeClass(status) {
    if (status === 'waiting') {
        return 'border-warning/30 bg-warning/10 text-warning';
    }

    if (status === 'active') {
        return 'border-success/30 bg-success/10 text-success';
    }

    return 'border-default-300 bg-default-150 text-default-500';
}

function counterpartName(conversation) {
    if (conversation.initiator_id === window.authUserId) {
        return conversation.claimed_by_name || window.chatText?.waitingForStaff || '...';
    }

    return conversation.initiator_name;
}

document.addEventListener('DOMContentLoaded', function () {
    const page = document.querySelector('[data-chat-page]');

    if (!page) {
        return;
    }

    const canRespond = page.dataset.chatCanRespond === '1';
    let currentConversationId = null;
    let activeConversationChannel = null;

    function renderConversationItem(conversation) {
        const name = counterpartName(conversation);
        const activeClass = conversation.id === currentConversationId ? 'bg-default-200' : 'hover:bg-default-150';
        const unread = conversation.unread_count > 0;
        const badge = unread
            ? `<span class="size-5 shrink-0 font-semibold bg-primary rounded-full text-white flex items-center justify-center text-[10px]" aria-label="${escapeHtml(window.chatText?.unreadCount ?? '')} ${conversation.unread_count}">${conversation.unread_count > 9 ? '9+' : conversation.unread_count}</span>`
            : '';

        return `
            <button class="w-full text-start flex gap-3 py-2 px-4 ${activeClass}" data-chat-open="${conversation.id}" type="button">
                ${avatarMarkup(name)}
                <div class="flex-grow min-w-0">
                    <h6 class="mb-1 text-default-800 ${unread ? 'font-bold' : 'font-semibold'} text-sm truncate">${escapeHtml(name)}</h6>
                    <p class="text-xs truncate ${unread ? 'text-default-800 font-medium' : 'text-default-500'}">${escapeHtml(conversation.last_message ?? '')}</p>
                </div>
                <div class="flex flex-col items-end gap-1 shrink-0">
                    <small class="text-xs text-default-500">${escapeHtml(conversation.last_message_at ?? '')}</small>
                    ${badge}
                </div>
            </button>
        `;
    }

    function renderList(payload) {
        ['queue', 'mine', 'history'].forEach((bucket) => {
            const container = document.querySelector(`[data-chat-list="${bucket}"]`);
            const empty = document.querySelector(`[data-chat-empty="${bucket}"]`);
            const items = payload[bucket] || [];

            if (container) {
                container.innerHTML = items.map(renderConversationItem).join('');
            }

            empty?.classList.toggle('hidden', items.length > 0);
        });
    }

    function fetchList() {
        return fetch(appUrl('/chat/list'), { headers: backgroundPollHeaders() })
            .then((response) => (response.ok ? response.json() : null))
            .then((payload) => {
                if (payload) {
                    renderList(payload);
                }
            })
            .catch(() => {
                // Transient failure (e.g. session expiring mid-poll) - the next 3s tick retries.
            });
    }

    function highlightActiveItem(id) {
        document.querySelectorAll('[data-chat-open]').forEach((button) => {
            const isActive = Number(button.getAttribute('data-chat-open')) === id;
            button.classList.toggle('bg-default-200', isActive);
            button.classList.toggle('hover:bg-default-150', !isActive);
        });
    }

    function showPanel() {
        document.querySelector('[data-chat-placeholder]')?.classList.add('hidden');
        const panel = document.querySelector('[data-chat-panel]');
        panel?.classList.remove('hidden');
        panel?.classList.add('flex');
    }

    function showPlaceholder() {
        document.querySelector('[data-chat-placeholder]')?.classList.remove('hidden');
        const panel = document.querySelector('[data-chat-panel]');
        panel?.classList.add('hidden');
        panel?.classList.remove('flex');
    }

    function renderPanelHeader(conversation) {
        const name = counterpartName(conversation);

        const nameEl = document.querySelector('[data-chat-panel-name]');

        if (nameEl) {
            nameEl.textContent = name;
        }

        const statusEl = document.querySelector('[data-chat-panel-status]');

        if (statusEl) {
            statusEl.textContent = conversation.status_label;
            statusEl.className = `inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border text-xs ${statusBadgeClass(conversation.status)}`;
        }

        const isClosed = conversation.status === 'closed';

        document.querySelector('[data-chat-end]')?.classList.toggle('hidden', isClosed);
        document.querySelector('[data-chat-form]')?.classList.toggle('hidden', isClosed);
        document.querySelector('[data-chat-closed-note]')?.classList.toggle('hidden', !isClosed);
    }

    function readStatusBadge(message) {
        const label = message.read
            ? (window.chatText?.read ?? 'Read')
            : (window.chatText?.sent ?? 'Sent');
        const icon = message.read ? 'check-check' : 'check';
        const classes = message.read
            ? 'border-success/30 bg-success/10 text-success'
            : 'border-default-300 bg-default-150 text-default-500';

        return `
            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 border text-[10px] ${classes}">
                <i class="size-3" data-lucide="${icon}"></i>${escapeHtml(label)}
            </span>
        `;
    }

    function renderBubble(message) {
        const mine = message.mine ?? (message.sender_id === window.authUserId);
        const bubble = `<div class="max-w-md p-4 card text-default-700 text-sm">${escapeHtml(message.body)}</div>`;
        const avatar = avatarMarkup(message.sender_name);

        if (mine) {
            return `
                <div class="flex gap-3 justify-end items-end">
                    <div class="flex flex-col items-end gap-1">
                        ${bubble}
                        ${readStatusBadge(message)}
                    </div>
                    ${avatar}
                </div>
            `;
        }

        return `<div class="flex gap-3 items-end">${avatar}${bubble}</div>`;
    }

    function messagesScrollArea() {
        return document.querySelector('[data-chat-panel] [data-simplebar] .simplebar-content-wrapper');
    }

    function isScrolledNearBottom() {
        const scrollArea = messagesScrollArea();

        if (!scrollArea) {
            return true;
        }

        return scrollArea.scrollHeight - scrollArea.scrollTop - scrollArea.clientHeight < 100;
    }

    function scrollMessagesToBottom() {
        const scrollArea = messagesScrollArea();

        if (scrollArea) {
            scrollArea.scrollTop = scrollArea.scrollHeight;
        }
    }

    // The 3-second poll (refreshOpenMessages) and live push both re-render the thread - forcing
    // the scroll to the bottom on every one of those would yank the view away while someone is
    // reading older messages. Only auto-scroll when they're already near the bottom (or forceScroll
    // is set, e.g. right after opening a conversation).
    function renderMessages(messages, forceScroll = false) {
        const container = document.querySelector('[data-chat-messages]');

        if (!container) {
            return;
        }

        const shouldScroll = forceScroll || isScrolledNearBottom();

        container.innerHTML = messages.map(renderBubble).join('');
        // Icons in dynamically-inserted HTML need to be re-scanned - lucide isn't exposed as
        // window.lucide in this app, see resources/js/vendor.js's window.lucideCreateIcons wrapper.
        window.lucideCreateIcons?.();

        if (shouldScroll) {
            scrollMessagesToBottom();
        }
    }

    function appendMessage(message) {
        const container = document.querySelector('[data-chat-messages]');

        if (!container) {
            return;
        }

        const shouldScroll = isScrolledNearBottom();

        container.insertAdjacentHTML('beforeend', renderBubble(message));
        window.lucideCreateIcons?.();

        if (shouldScroll) {
            scrollMessagesToBottom();
        }
    }

    function subscribeToConversation(id) {
        if (!window.Echo) {
            return;
        }

        if (activeConversationChannel) {
            window.Echo.leave(`conversation.${activeConversationChannel}`);
        }

        activeConversationChannel = id;

        window.Echo.private(`conversation.${id}`).listen('.message.sent', (event) => {
            if (event.conversation_id !== currentConversationId) {
                return;
            }

            if (event.sender_id !== window.authUserId) {
                appendMessage(event);
            }

            fetchList();
        });
    }

    function selectConversation(id) {
        currentConversationId = id;
        highlightActiveItem(id);
        subscribeToConversation(id);

        fetch(appUrl(`/chat/${id}/messages`), { headers: jsonHeaders() })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`Conversation ${id} is not accessible (${response.status}).`);
                }

                return response.json();
            })
            .then((payload) => {
                showPanel();
                renderPanelHeader(payload.conversation);
                renderMessages(payload.messages, true);
                // Opening a conversation marks its incoming messages as read server-side -
                // refresh the sidebar so that conversation's unread badge clears immediately.
                fetchList();
            })
            .catch(() => {
                // Not accessible (already claimed by someone else, ended and no longer visible,
                // stale link, etc.) - fall back to the empty state instead of leaving a broken
                // half-rendered panel or a raw JS exception.
                currentConversationId = null;
                showPlaceholder();
            });
    }

    function refreshOpenMessages() {
        if (!currentConversationId) {
            return;
        }

        fetch(appUrl(`/chat/${currentConversationId}/messages`), { headers: backgroundPollHeaders() })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`Conversation ${currentConversationId} is not accessible (${response.status}).`);
                }

                return response.json();
            })
            .then((payload) => {
                renderPanelHeader(payload.conversation);
                renderMessages(payload.messages);
            })
            .catch(() => {
                currentConversationId = null;
                showPlaceholder();
                fetchList();
            });
    }

    function sendMessage(body) {
        if (!currentConversationId) {
            return;
        }

        fetch(appUrl(`/chat/${currentConversationId}/messages`), {
            method: 'POST',
            headers: { ...jsonHeaders(), 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            body: JSON.stringify({ body }),
        })
            .then((response) => response.json())
            .then((payload) => {
                appendMessage(payload.message);
                fetchList();
            });
    }

    function startConversation(body) {
        fetch(appUrl('/chat'), {
            method: 'POST',
            headers: { ...jsonHeaders(), 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            body: JSON.stringify({ body }),
        })
            .then(async (response) => {
                if (!response.ok) {
                    const payload = await response.json().catch(() => ({}));

                    throw new Error(payload.message || window.chatText?.startFailed || 'Failed to start conversation.');
                }

                return response.json();
            })
            .then((payload) => {
                window.HSOverlay?.close(document.getElementById('chat-new-modal'));
                fetchList();
                selectConversation(payload.conversation.id);
            })
            .catch((error) => {
                const errorEl = document.querySelector('[data-chat-new-error]');

                if (errorEl) {
                    errorEl.textContent = error.message;
                    errorEl.classList.remove('hidden');
                }
            });
    }

    function endConversation() {
        if (!currentConversationId) {
            return;
        }

        fetch(appUrl(`/chat/${currentConversationId}/end`), {
            method: 'POST',
            headers: { ...jsonHeaders(), 'X-CSRF-TOKEN': csrfToken() },
        }).finally(() => {
            fetchList();
            refreshOpenMessages();
        });
    }

    document.addEventListener('click', function (event) {
        const opener = event.target.closest('[data-chat-open]');

        if (opener) {
            selectConversation(Number(opener.getAttribute('data-chat-open')));
        }
    });

    document.querySelector('[data-chat-form]')?.addEventListener('submit', function (event) {
        event.preventDefault();

        const input = event.target.querySelector('input[name="body"]');
        const body = input.value.trim();

        if (!body) {
            return;
        }

        input.value = '';
        sendMessage(body);
    });

    document.querySelector('[data-chat-end]')?.addEventListener('click', endConversation);

    document.querySelector('[data-chat-new-form]')?.addEventListener('submit', function (event) {
        event.preventDefault();

        const textarea = event.target.querySelector('textarea[name="body"]');
        const body = textarea.value.trim();

        document.querySelector('[data-chat-new-error]')?.classList.add('hidden');

        if (!body) {
            return;
        }

        textarea.value = '';
        startConversation(body);
    });

    if (window.Echo && canRespond) {
        window.Echo.private('chat.queue').listen('.message.sent', function () {
            fetchList();
        });
    }

    fetchList();

    const preselect = new URLSearchParams(window.location.search).get('conversation');

    if (preselect) {
        selectConversation(Number(preselect));
    }

    setInterval(fetchList, 3000);
    setInterval(refreshOpenMessages, 3000);
});
