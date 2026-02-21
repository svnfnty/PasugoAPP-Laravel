<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Dashboard - Live Requests</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --success: #10b981;
            --danger: #ef4444;
            --bg: #f8fafc;
        }
        body { font-family: 'Outfit', sans-serif; background: var(--bg); padding: 40px; }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { margin-bottom: 24px; color: var(--primary); }
        .request-card {
            background: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #e2e8f0;
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .details h3 { margin-bottom: 4px; }
        .details p { color: #64748b; font-size: 0.9rem; }
        .actions { display: flex; gap: 12px; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn-accept { background: var(--success); color: white; }
        .btn-decline { background: var(--danger); color: white; }
        #no-requests { text-align: center; color: #94a3b8; padding: 100px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div id="ws-status-bar" style="background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 8px; margin-bottom: 20px; display: none; text-align: center; font-weight: 600; font-size: 0.9rem;">
            ⚠️ WebSocket Disconnected. Trying to reconnect...
        </div>

        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
            <div>
                <h1>Rider Dashboard</h1>
                <p id="rider-status" style="color: var(--success); font-weight: 600;">🟢 Online & Receiving Requests</p>
            </div>
            <div style="text-align: right;">
                <strong id="rider-name-display">Loading...</strong>
            </div>
        </header>

        <div id="requests-container">
            <div id="no-requests">
                <div style="font-size: 3rem; margin-bottom: 16px;">📭</div>
                <p>No active requests. Stay tuned!</p>
            </div>
        </div>

        <!-- FLOATING DEBUG CONSOLE - MOVED TO TOP FOR VISIBILITY -->
        <div id="debug-console" style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); width: 90%; max-width: 500px; background: #0f172a; color: #4ade80; border-radius: 12px; font-family: 'Courier New', monospace; font-size: 0.85rem; height: 250px; overflow-y: auto; z-index: 10000; box-shadow: 0 20px 50px rgba(0,0,0,0.5); border: 2px solid #334155;">
            <div style="position: sticky; top: 0; background: #1e293b; color: #f8fafc; padding: 10px 15px; font-weight: bold; border-bottom: 2px solid #334155; display: flex; justify-content: space-between; align-items: center;">
                <span>🚀 REVERB DEBUG MONITOR</span>
                <button onclick="location.reload()" style="background: #4f46e5; color: white; border: none; padding: 4px 10px; border-radius: 4px; font-size: 0.7rem; cursor: pointer;">Refresh Page</button>
            </div>
            <div id="debug-logs" style="padding: 15px;">Initializing system...</div>
        </div>
    </div>

    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
    <script>
        const riderId = {{ auth()->guard('rider')->id() }};
        const riderName = "{{ auth()->guard('rider')->user()->name }}";
        document.getElementById('rider-name-display').innerText = riderName;

        // WebSocket Configuration with dynamic host detection
        const configHost = '{{ config('broadcasting.connections.reverb.client_options.host') ?? config('broadcasting.connections.reverb.options.host') }}';
        const configPort = '{{ config('broadcasting.connections.reverb.client_options.port') ?? config('broadcasting.connections.reverb.options.port') }}';
        
        // Use window.location.hostname if config is empty, 127.0.0.1, or localhost
        const wsHost = (configHost && configHost !== '127.0.0.1' && configHost !== 'localhost') 
            ? configHost 
            : window.location.hostname;
            
        // Determine if we should use TLS (WSS)
        const isSecure = window.location.protocol === 'https:' || wsHost.includes('railway.app');
        const port = isSecure ? 443 : (configPort || 8081);

        console.log(`[WebSocket] Connecting to ${isSecure ? 'wss' : 'ws'}://${wsHost}:${port}`);

        const echo = new Echo({
            broadcaster: 'reverb',
            key: '{{ config('broadcasting.connections.reverb.key') }}',
            wsHost: wsHost,
            wsPort: port,
            wssPort: port,
            forceTLS: isSecure,
            enabledTransports: ['ws', 'wss'],
            activityTimeout: 30000,
            pongTimeout: 10000,
        });

        function logEvent(msg, data) {
            const logs = document.getElementById('debug-logs');
            const entry = document.createElement('div');
            entry.style.marginBottom = '4px';
            entry.innerHTML = `<span style="color: #94a3b8">[${new Date().toLocaleTimeString()}]</span> ${msg} ${data ? JSON.stringify(data) : ''}`;
            logs.prepend(entry);
        }

        logEvent(`SYSTEM: Joining channel: rider.${riderId}`);
        echo.channel('rider.' + riderId)
            .listen('.rider.ordered', (data) => {
                console.log('New Order Received:', data);
                logEvent('EVENT: rider.ordered', data);
                addRequestCard(data);
            });

        // Connection Status Monitoring
        const statusBar = document.getElementById('ws-status-bar');
        
        echo.connector.pusher.connection.bind('connected', () => {
            console.log('[WebSocket] Connected');
            logEvent('SYSTEM: WebSocket Connected');
            statusBar.style.display = 'none';
        });

        echo.connector.pusher.connection.bind('connecting', () => {
            console.log('[WebSocket] Connecting...');
            logEvent('SYSTEM: WebSocket Connecting...');
            statusBar.style.background = '#fef3c7';
            statusBar.style.color = '#92400e';
            statusBar.innerText = '📡 WebSocket Connecting...';
            statusBar.style.display = 'block';
        });

        echo.connector.pusher.connection.bind('unavailable', () => {
            console.log('[WebSocket] Unavailable');
            logEvent('SYSTEM: WebSocket Unavailable');
            statusBar.style.background = '#fee2e2';
            statusBar.style.color = '#991b1b';
            statusBar.innerText = '❌ WebSocket Unavailable. Waiting to retry...';
            statusBar.style.display = 'block';
        });

        echo.connector.pusher.connection.bind('failed', () => {
            console.log('[WebSocket] Failed');
            logEvent('SYSTEM: WebSocket Failed');
            statusBar.style.background = '#fee2e2';
            statusBar.style.color = '#991b1b';
            statusBar.innerText = '❌ WebSocket Connection Failed. Please refresh.';
            statusBar.style.display = 'block';
        });

        echo.connector.pusher.connection.bind('disconnected', () => {
            console.log('[WebSocket] Disconnected');
            logEvent('SYSTEM: WebSocket Disconnected');
            statusBar.style.background = '#f1f5f9';
            statusBar.style.color = '#475569';
            statusBar.innerText = '⚠️ WebSocket Disconnected.';
            statusBar.style.display = 'block';
        });

        function addRequestCard(data) {
            const container = document.getElementById('requests-container');
            const noRequests = document.getElementById('no-requests');
            if (noRequests) noRequests.remove();

            const card = document.createElement('div');
            card.className = 'request-card';
            card.id = 'req-' + data.clientId;
            let routeHtml = '';
            if (data.serviceType === 'pahatod' && data.pickup && data.dropoff) {
                routeHtml = `
                    <div style="margin-top: 12px; padding: 12px; background: #f1f5f9; border-radius: 8px; border-left: 4px solid var(--primary);">
                        <div style="margin-bottom: 8px;">
                            <span style="font-size: 0.7rem; color: #64748b; text-transform: uppercase; font-weight: 700; display: block;">Pickup</span>
                            <span style="font-weight: 600; color: #1e293b;">📍 ${data.pickup.name}</span>
                        </div>
                        <div>
                            <span style="font-size: 0.7rem; color: #64748b; text-transform: uppercase; font-weight: 700; display: block;">Destination</span>
                            <span style="font-weight: 600; color: #1e293b;">🏁 ${data.dropoff.name}</span>
                        </div>
                    </div>
                `;
            } else if (data.serviceType === 'pasugo') {
                routeHtml = `
                    <div style="margin-top: 12px; padding: 12px; background: #f5f3ff; border-radius: 8px; border-left: 4px solid #8b5cf6;">
                        <span style="font-weight: 600; color: #5b21b6;">📦 Pasugo Delivery / Errand</span>
                        <p style="font-size: 0.8rem; color: #7c3aed; margin-top: 4px;">Client needs a delivery/errand service.</p>
                    </div>
                `;
            }

            card.innerHTML = `
                <div class="details" style="flex: 1; margin-right: 20px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <span style="font-size: 1.2rem;">${data.serviceType === 'pasugo' ? '📦' : '🏍️'}</span>
                        <h3 style="margin: 0;">New ${data.serviceType.toUpperCase()} Request</h3>
                    </div>
                    <p>Client: <strong style="color: #1e293b;">${data.clientName}</strong></p>
                    ${routeHtml}
                </div>
                <div class="actions">
                    <button class="btn btn-decline" onclick="respond(${data.clientId}, 'decline', '${data.serviceType}')">Decline</button>
                    <button class="btn btn-accept" onclick="respond(${data.clientId}, 'accept', '${data.serviceType}')">Accept</button>
                </div>
            `;
            container.prepend(card);
            
            if (Notification.permission === "granted") {
                new Notification("New Delivery Request!", { body: `${data.clientName} needs a ${data.serviceType}` });
            }
        }

        function respond(clientId, decision, serviceType) {
            fetch(`/rider/clients/${clientId}/respond`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ decision: decision, service_type: serviceType })
            }).then(() => {
                const card = document.getElementById('req-' + clientId);
                if (card) card.remove();
                if (document.getElementById('requests-container').children.length === 0) {
                    location.reload();
                }
            });
        }

        // --- Real-time Location Tracking ---
        let lastLat = null;
        let lastLng = null;

        function updateLocationToServer(lat, lng) {
            // Only update if moved significantly or first time
            if (lastLat === lat && lastLng === lng) return;
            
            lastLat = lat;
            lastLng = lng;

            fetch('/rider/location', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ lat, lng })
            })
            .then(r => r.json())
            .then(data => console.log('Location Sync:', data))
            .catch(err => console.error('Sync Error:', err));
        }

        if ("geolocation" in navigator) {
            navigator.geolocation.watchPosition(position => {
                const { latitude, longitude } = position.coords;
                updateLocationToServer(latitude, longitude);
            }, error => {
                console.error("GPS Error:", error);
            }, {
                enableHighAccuracy: true,
                maximumAge: 10000,
                timeout: 5000
            });
        }

        if (Notification.permission !== "denied") {
            Notification.requestPermission();
        }
    </script>
</body>
</html>
