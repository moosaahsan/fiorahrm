import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: "pusher",
    key: window.pusherConfig?.key || import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: window.pusherConfig?.cluster || import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    wsHost: `ws-${window.pusherConfig?.cluster || import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
    wsPort: 443,
    wssPort: 443,
    enabledTransports: ['ws', 'wss'],
});
