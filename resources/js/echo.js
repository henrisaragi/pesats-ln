import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

// Real-time (WebSocket) push is reserved for the chat feature only (see
// resources/js/pages/chat.js, which subscribes to "conversation.{id}" / "chat.queue").
// General app notifications (the bell dropdown) intentionally do NOT use a live push
// listener here - they're picked up by resources/js/components/notification-dropdown.js's
// own polling instead. App\Notifications\AppNotification only broadcasts to 'database' now,
// so there is nothing for a general listener to subscribe to.
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: (document.querySelector('meta[name="app-url"]')?.getAttribute('content') ?? '').replace(/\/+$/, '') + '/broadcasting/auth',
});
