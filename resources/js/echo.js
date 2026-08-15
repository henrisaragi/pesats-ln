import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

// Live push for the notification dropdown (resources/js/components/notification-dropdown.js):
// Laravel's Notifiable trait broadcasts on the notifiable's default private channel
// ("App.Models.User.{id}", authorized in routes/channels.php) using the built-in
// ".Illuminate\\Notifications\\Events\\BroadcastNotificationCreated" event - we don't need a
// custom event/channel for this, just listen for it.
if (window.authUserId) {
    window.Echo.private(`App.Models.User.${window.authUserId}`)
        .notification(() => {
            window.refreshNotifications?.();
        });
}
