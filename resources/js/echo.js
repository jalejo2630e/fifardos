import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Instancia perezosa: solo abrimos el WebSocket cuando se entra a una sala de /familia
// (no en el resto del sitio). Usamos canales públicos, así que no hace falta auth.
let echo = null;

export function getEcho() {
    if (echo) return echo;
    window.Pusher = Pusher;
    echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 80),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
    return echo;
}

export function leaveChannel(name) {
    if (echo) echo.leave(name);
}

export function disconnectEcho() {
    if (echo) { echo.disconnect(); echo = null; }
}
