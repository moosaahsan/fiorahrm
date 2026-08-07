import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

if (window.pusherConfig && window.pusherConfig.key) {
    window.Echo = new Echo({
        broadcaster: "pusher",
        key: window.pusherConfig.key,
        cluster: window.pusherConfig.cluster || 'ap2',
        forceTLS: true,
        wsHost: `ws-${window.pusherConfig.cluster || 'ap2'}.pusher.com`,
        wsPort: 443,
        wssPort: 443,
        enabledTransports: ['ws', 'wss'],
    });
    console.log('Laravel Echo initialized successfully with global config.');
} else {
    console.error('Laravel Echo failed to initialize: window.pusherConfig is missing or invalid.');
}
