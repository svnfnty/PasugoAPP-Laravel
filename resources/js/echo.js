import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

// WebSocket Configuration with proper protocol detection and fallbacks
const wsHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
const wsPort = import.meta.env.VITE_REVERB_PORT || (window.location.protocol === 'https:' ? 443 : 8080);
const wsKey = import.meta.env.VITE_REVERB_APP_KEY || 'app-key';
const wsScheme = import.meta.env.VITE_REVERB_SCHEME || (window.location.protocol === 'https:' ? 'https' : 'http');

// Determine if we should use TLS
const isSecure = window.location.protocol === 'https:' || wsHost.includes('railway.app') || wsScheme === 'https';

// Connection state management
let connectionState = 'connecting';
let reconnectAttempts = 0;
const maxReconnectAttempts = 10;
const reconnectDelay = 2000;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: wsKey,
    wsHost: wsHost,
    wsPort: isSecure ? 443 : wsPort,
    wssPort: isSecure ? 443 : wsPort,
    forceTLS: isSecure,
    enabledTransports: isSecure ? ['wss'] : ['ws', 'wss'],
    activityTimeout: 30000,
    pongTimeout: 10000,
});

// Connection status monitoring
function updateConnectionStatus(status, message) {
    connectionState = status;
    console.log(`[Echo WebSocket] ${status}: ${message}`);
    
    // Dispatch custom event for UI updates
    window.dispatchEvent(new CustomEvent('echo-websocket-status', { 
        detail: { status, message } 
    }));
}

// Handle connection errors and reconnection
function handleConnectionError(error) {
    console.error('[Echo WebSocket] Connection error:', error);
    updateConnectionStatus('error', 'Connection failed, attempting to reconnect...');
    
    if (reconnectAttempts < maxReconnectAttempts) {
        reconnectAttempts++;
        const delay = Math.min(reconnectDelay * Math.pow(1.5, reconnectAttempts - 1), 30000);
        
        console.log(`[Echo WebSocket] Reconnecting in ${delay}ms (attempt ${reconnectAttempts}/${maxReconnectAttempts})`);
        
        setTimeout(() => {
            updateConnectionStatus('reconnecting', `Attempt ${reconnectAttempts}...`);
        }, delay);
    } else {
        updateConnectionStatus('failed', 'Max reconnection attempts reached. Please refresh the page.');
    }
}

// Monitor connection state
window.Echo.connector.pusher.connection.bind('connected', () => {
    reconnectAttempts = 0;
    updateConnectionStatus('connected', 'WebSocket connected successfully');
});

window.Echo.connector.pusher.connection.bind('disconnected', () => {
    updateConnectionStatus('disconnected', 'WebSocket disconnected');
});

window.Echo.connector.pusher.connection.bind('error', (error) => {
    handleConnectionError(error);
});

// Initial connection attempt
updateConnectionStatus('connecting', `Connecting to ${wsHost}:${isSecure ? 443 : wsPort} (TLS: ${isSecure})`);
