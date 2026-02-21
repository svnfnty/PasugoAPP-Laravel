/* ============================================================
   PASUGO MAP — JavaScript v2
   ============================================================
   Features:
     - Pahatod (Ride) & Pasugo (Delivery) service tabs
     - Pickup → Dropoff location inputs with geocoding
     - Route display on map
     - Real-time rider tracking via Reverb/Echo
     - Chat overlay with messenger-style UI
   ============================================================ */

(function () {
    'use strict';

    // ── Read server data from <body> data attributes ──────────
    const body = document.body;
    const REVERB_KEY = body.dataset.reverbKey;
    const rawHost = body.dataset.reverbHost;
    const REVERB_PORT = body.dataset.reverbPort;
    const CSRF_TOKEN = body.dataset.csrf;
    const CLIENT_ID = body.dataset.clientId || 'guest';

    // Fallback if REVERB_HOST is missing or local
    const REVERB_HOST = (rawHost && rawHost !== '127.0.0.1' && rawHost !== 'localhost')
        ? rawHost
        : window.location.hostname;

    // ── Echo / Reverb setup ──────────────────────────────────
    // Determine if we should use TLS based on the current page protocol or host
    const isSecure = window.location.protocol === 'https:' || REVERB_HOST.includes('railway.app');

    // On Railway, we must use 443 for WSS, ignoring the internal REVERB_PORT
    const port = isSecure ? 443 : (REVERB_PORT || 8081);

    // Connection state management
    let connectionState = 'connecting';
    let reconnectAttempts = 0;
    const maxReconnectAttempts = 10;
    const reconnectDelay = 2000; // Start with 2 seconds

    const echo = new Echo({
        broadcaster: 'reverb',
        key: REVERB_KEY,
        wsHost: REVERB_HOST,
        wsPort: port,
        wssPort: port,
        forceTLS: isSecure,
        enabledTransports: ['ws', 'wss'],
        activityTimeout: 30000,
        pongTimeout: 10000,
    });

    console.log(`[WebSocket] Echo initialized for host: ${REVERB_HOST}`);

    // Connection status monitoring
    function updateConnectionStatus(status, message) {
        connectionState = status;
        console.log(`[WebSocket] ${status}: ${message}`);

        // Dispatch custom event for UI updates
        window.dispatchEvent(new CustomEvent('websocket-status', {
            detail: { status, message }
        }));
    }

    // Handle connection errors and reconnection
    function handleConnectionError(error) {
        console.error('[WebSocket] Connection error:', error);
        updateConnectionStatus('error', 'Connection failed, attempting to reconnect...');

        if (reconnectAttempts < maxReconnectAttempts) {
            reconnectAttempts++;
            const delay = Math.min(reconnectDelay * Math.pow(1.5, reconnectAttempts - 1), 30000); // Max 30s delay

            console.log(`[WebSocket] Reconnecting in ${delay}ms (attempt ${reconnectAttempts}/${maxReconnectAttempts})`);

            setTimeout(() => {
                // Reconnect logic - Echo handles this automatically, but we monitor it
                updateConnectionStatus('reconnecting', `Attempt ${reconnectAttempts}...`);
            }, delay);
        } else {
            updateConnectionStatus('failed', 'Max reconnection attempts reached. Please refresh the page.');
        }
    }

    // Monitor connection state
    echo.connector.pusher.connection.bind('connected', () => {
        reconnectAttempts = 0;
        updateConnectionStatus('connected', 'WebSocket connected successfully');
    });

    echo.connector.pusher.connection.bind('disconnected', () => {
        updateConnectionStatus('disconnected', 'WebSocket disconnected');
    });

    echo.connector.pusher.connection.bind('error', (error) => {
        handleConnectionError(error);
    });

    // Initial connection attempt
    updateConnectionStatus('connecting', `Connecting to ${REVERB_HOST}:${port} (TLS: ${isSecure})`);


    // ══════════════════════════════════════════════════════════
    //  MAP INITIALISATION
    // ══════════════════════════════════════════════════════════

    const streetLayer = L.tileLayer(
        'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
        { attribution: '© OpenStreetMap' }
    );

    const satelliteLayer = L.tileLayer(
        'https://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}',
        { maxZoom: 20, subdomains: ['mt0', 'mt1', 'mt2', 'mt3'], attribution: '© Google Maps' }
    );

    const darkLayer = L.tileLayer(
        'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
        { attribution: '© OpenStreetMap' }
    );

    let currentMissionId = null;

    const map = L.map('map', {
        zoomControl: false,
        layers: [satelliteLayer]
    }).setView([8.8258, 125.0827], 15);

    // Default layer control removed to avoid overlap on mobile.
    // Functionality moved to the custom button in the header.

    L.control.zoom({ position: 'bottomright' }).addTo(map);

    // Force Leaflet to re-measure the container after the page fully renders.
    // This fixes blank/grey map tiles on Android WebView and Capacitor.
    setTimeout(function () { map.invalidateSize(); }, 100);
    setTimeout(function () { map.invalidateSize(); }, 500);
    window.addEventListener('resize', function () { map.invalidateSize(); });

    // Additional fix for Capacitor: when the WebView finishes loading
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(function () { map.invalidateSize(); }, 300);
    });


    // ══════════════════════════════════════════════════════════
    //  INITIAL RIDER FETCH
    // ══════════════════════════════════════════════════════════

    function fetchInitialRiders() {
        fetch('/api/riders')
            .then(r => r.json())
            .then(riders => {
                riders.forEach(rider => {
                    if (rider.status !== 'offline' && rider.lat && rider.lng) {
                        updateOrAddRiderMarker(rider);
                    }
                });
                updateRiderList();
            })
            .catch(err => console.error('[Map] Failed to fetch initial riders:', err));
    }

    function updateOrAddRiderMarker(data) {
        var pos = [data.lat, data.lng];

        if (!riderMarkers[data.id || data.riderId]) {
            var statusClass = data.status === 'available' ? '' : 'busy';
            var marker = L.marker(pos, {
                icon: L.divIcon({
                    html: '<div class="rider-map-marker ' + statusClass + '">🛵</div>',
                    className: '',
                    iconSize: [44, 44],
                    iconAnchor: [22, 22]
                })
            }).addTo(map);

            marker.on('click', function () { openRiderProfile(data.id || data.riderId); });
            riderMarkers[data.id || data.riderId] = {
                marker: marker,
                data: {
                    riderId: data.id || data.riderId,
                    name: data.name,
                    bio: data.bio,
                    lat: data.lat,
                    lng: data.lng,
                    status: data.status,
                    trips: data.trips,
                    rating: data.rating
                }
            };
        } else {
            const id = data.id || data.riderId;
            riderMarkers[id].marker.setLatLng(pos);
            // Re-apply status class if changed
            const statusClass = data.status === 'available' ? '' : 'busy';
            const markerDiv = riderMarkers[id].marker.getElement();
            if (markerDiv) {
                const innerDiv = markerDiv.querySelector('.rider-map-marker');
                if (innerDiv) {
                    innerDiv.className = 'rider-map-marker ' + statusClass;
                }
            }
            // Update stored data
            riderMarkers[id].data = Object.assign(riderMarkers[id].data, data);
            riderMarkers[id].data.riderId = id;
        }
    }

    // Call fetch on load
    fetchInitialRiders();


    // ══════════════════════════════════════════════════════════
    //  TOAST NOTIFICATION SYSTEM
    // ══════════════════════════════════════════════════════════

    function createToastContainer() {
        var existing = document.getElementById('pasugo-toast-container');
        if (existing) return existing;
        var container = document.createElement('div');
        container.id = 'pasugo-toast-container';
        container.style.cssText = 'position:fixed;top:0;left:0;right:0;z-index:99999;display:flex;flex-direction:column;align-items:center;padding:12px 16px;pointer-events:none;gap:8px;';
        document.body.appendChild(container);
        return container;
    }

    function showToast(message, type, duration) {
        type = type || 'warning';
        duration = duration || 5000;

        var container = createToastContainer();
        var toast = document.createElement('div');

        var colors = {
            warning: { bg: 'linear-gradient(135deg, #f59e0b, #d97706)', icon: '⚠️' },
            error: { bg: 'linear-gradient(135deg, #ef4444, #dc2626)', icon: '❌' },
            info: { bg: 'linear-gradient(135deg, #3b82f6, #2563eb)', icon: 'ℹ️' },
            success: { bg: 'linear-gradient(135deg, #10b981, #059669)', icon: '✅' }
        };
        var c = colors[type] || colors.warning;

        toast.style.cssText =
            'pointer-events:auto;max-width:420px;width:100%;padding:16px 20px;border-radius:16px;color:white;font-family:Inter,sans-serif;font-size:14px;font-weight:600;' +
            'display:flex;align-items:flex-start;gap:12px;box-shadow:0 8px 32px rgba(0,0,0,0.18),0 2px 8px rgba(0,0,0,0.1);' +
            'backdrop-filter:blur(12px);transform:translateY(-100%);opacity:0;transition:all 0.4s cubic-bezier(0.16,1,0.3,1);' +
            'background:' + c.bg + ';';

        var iconSpan = document.createElement('span');
        iconSpan.style.cssText = 'font-size:20px;flex-shrink:0;margin-top:1px;';
        iconSpan.textContent = c.icon;

        var textDiv = document.createElement('div');
        textDiv.style.cssText = 'flex:1;line-height:1.45;';
        textDiv.textContent = message;

        var closeBtn = document.createElement('button');
        closeBtn.style.cssText = 'background:rgba(255,255,255,0.25);border:none;color:white;width:24px;height:24px;border-radius:50%;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background 0.2s;';
        closeBtn.textContent = '✕';
        closeBtn.onmouseenter = function () { closeBtn.style.background = 'rgba(255,255,255,0.4)'; };
        closeBtn.onmouseleave = function () { closeBtn.style.background = 'rgba(255,255,255,0.25)'; };
        closeBtn.onclick = function () { dismissToast(toast); };

        toast.appendChild(iconSpan);
        toast.appendChild(textDiv);
        toast.appendChild(closeBtn);
        container.appendChild(toast);

        // Animate in
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                toast.style.transform = 'translateY(0)';
                toast.style.opacity = '1';
            });
        });

        // Auto dismiss
        var timeout = setTimeout(function () { dismissToast(toast); }, duration);
        toast._timeout = timeout;
    }

    function dismissToast(toast) {
        if (toast._dismissed) return;
        toast._dismissed = true;
        clearTimeout(toast._timeout);
        toast.style.transform = 'translateY(-100%)';
        toast.style.opacity = '0';
        setTimeout(function () { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 400);
    }


    // ══════════════════════════════════════════════════════════
    //  STATE
    // ══════════════════════════════════════════════════════════

    let riderMarkers = {};
    let selectedRiderId = null;
    let selectedRiderName = '';
    let selectedRiderData = null;
    let countdownInterval = null;
    let panelCollapsed = false;
    let activeTab = 'pahatod';      // 'pahatod' | 'pasugo'
    let activeInputField = null;
    let userLatLng = null;
    let userMarker = null;
    let routeLayer = null;
    let pickupMarker = null;
    let dropoffMarker = null;
    let searchTimeout = null;
    let activeServiceType = 'pahatod';
    let isRequestPending = false;

    // Location data
    let locations = {
        'pahatod-pickup': null,
        'pahatod-dropoff': null,
        'profile-pickup': null,
        'profile-dropoff': null,
    };


    // ══════════════════════════════════════════════════════════
    //  LANDMARKS
    // ══════════════════════════════════════════════════════════

    const landmarks = [
        { name: 'Jollibee Gingoog', pos: [8.8258, 125.0827], icon: '🍔' },
        { name: 'Gaisano Gingoog', pos: [8.8298, 125.0827], icon: '🏬' },
        { name: 'City Public Market', pos: [8.8235, 125.0835], icon: '🏪' },
        { name: 'Gingoog City Hall', pos: [8.8222, 125.0850], icon: '🏛️' },
    ];

    landmarks.forEach(function (l) {
        L.marker(l.pos, {
            icon: L.divIcon({
                html: '<div class="landmark-marker">' + l.icon + '</div>',
                className: '',
                iconSize: [36, 36],
                iconAnchor: [18, 18]
            })
        }).addTo(map).bindTooltip(l.name, { direction: 'top', offset: [0, -12] });
    });


    // ══════════════════════════════════════════════════════════
    //  MAP LAYERS TOGGLE
    // ══════════════════════════════════════════════════════════

    const layersArr = [satelliteLayer, streetLayer, darkLayer];
    const layerNames = ['Satellite', 'Street', 'Dark View'];
    let currentLayerIdx = 0; // Start with satellite as initialized in L.map

    function toggleLayers() {
        map.removeLayer(layersArr[currentLayerIdx]);
        currentLayerIdx = (currentLayerIdx + 1) % layersArr.length;
        map.addLayer(layersArr[currentLayerIdx]);

        showToast('Switched to ' + layerNames[currentLayerIdx], 'info', 2500);
    }


    // ══════════════════════════════════════════════════════════
    //  USER LOCATION
    // ══════════════════════════════════════════════════════════

    function locateMe() {
        // Try Capacitor's native geolocation first (Android/iOS)
        if (window.Capacitor && window.Capacitor.isNativePlatform && window.Capacitor.isNativePlatform()) {
            try {
                var Geolocation = window.Capacitor.Plugins.Geolocation;
                if (Geolocation) {
                    Geolocation.getCurrentPosition({ enableHighAccuracy: true, timeout: 15000 })
                        .then(function (pos) {
                            setUserPosition(pos.coords.latitude, pos.coords.longitude);
                        })
                        .catch(function (err) {
                            console.warn('[Capacitor Geo] Failed, falling back to browser:', err);
                            browserGeolocate();
                        });
                    return;
                }
            } catch (e) {
                console.warn('[Capacitor Geo] Plugin not available, using browser API');
            }
        }

        browserGeolocate();
    }

    function browserGeolocate() {
        if (!('geolocation' in navigator)) {
            console.warn('[Geo] Geolocation not supported by this browser');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function (pos) {
                setUserPosition(pos.coords.latitude, pos.coords.longitude);
            },
            function (err) {
                console.error('[Geo] Location error:', err.message);
                showToast('Unable to get your location. Please enable GPS.', 'info', 4000);
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 5000 }
        );
    }

    function setUserPosition(lat, lng) {
        userLatLng = [lat, lng];
        map.flyTo(userLatLng, 16, { duration: 0.8 });

        if (userMarker) {
            userMarker.setLatLng(userLatLng);
        } else {
            userMarker = L.marker(userLatLng, {
                icon: L.divIcon({
                    html: '<div class="user-marker"></div>',
                    className: '',
                    iconSize: [20, 20],
                    iconAnchor: [10, 10]
                })
            }).addTo(map).bindTooltip('You are here', { direction: 'top', offset: [0, -14] });
        }
    }

    // Auto-locate on load
    locateMe();

    // Periodically re-fetch riders as a fallback (every 30s)
    setInterval(function () {
        fetchInitialRiders();
    }, 30000);


    // ══════════════════════════════════════════════════════════
    //  TAB SWITCHING
    // ══════════════════════════════════════════════════════════

    function switchTab(tab) {
        activeTab = tab;

        // Tab buttons
        document.getElementById('tab-pahatod').classList.toggle('active', tab === 'pahatod');
        document.getElementById('tab-pasugo').classList.toggle('active', tab === 'pasugo');

        // Forms
        document.getElementById('form-pahatod').classList.toggle('hidden', tab !== 'pahatod');
        document.getElementById('form-pasugo').classList.toggle('hidden', tab !== 'pasugo');

        // Hide riders section when switching tabs
        document.getElementById('riders-section').classList.add('hidden');

        // Clear route when leaving Pahatod (i.e., switching to Pasugo)
        if (tab === 'pasugo') {
            clearRoute();
            activeInputField = null;
        }
    }


    // ══════════════════════════════════════════════════════════
    //  PANEL TOGGLE
    // ══════════════════════════════════════════════════════════

    function togglePanel() {
        var panel = document.getElementById('service-panel');
        panelCollapsed = !panelCollapsed;
        panel.classList.toggle('collapsed', panelCollapsed);
    }


    // ══════════════════════════════════════════════════════════
    //  LOCATION INPUT & SEARCH
    // ══════════════════════════════════════════════════════════

    function onInputFocus(fieldId) {
        activeInputField = fieldId;
    }

    function useMyLocation(fieldId) {
        if (!userLatLng) {
            locateMe();
            return;
        }

        var input = document.getElementById(fieldId);
        input.value = 'My Location';
        locations[fieldId] = { lat: userLatLng[0], lng: userLatLng[1], name: 'My Location' };
        updateRoutePreview();
    }

    // Attach search listeners to all loc-inputs
    document.querySelectorAll('.loc-input').forEach(function (input) {
        var debounce = null;
        input.addEventListener('input', function () {
            var val = input.value.trim();
            if (val.length < 2) {
                hideSuggestions();
                return;
            }
            clearTimeout(debounce);
            debounce = setTimeout(function () {
                searchLocation(val, input.id);
            }, 400);
        });
    });

    function searchLocation(query, fieldId) {
        // Use Nominatim for geocoding (free, no API key needed)
        var url = 'https://nominatim.openstreetmap.org/search?format=json&q=' +
            encodeURIComponent(query + ', Gingoog City, Philippines') +
            '&limit=5&addressdetails=1';

        fetch(url, { headers: { 'Accept-Language': 'en' } })
            .then(function (r) { return r.json(); })
            .then(function (results) {
                showSuggestions(results, fieldId);
            })
            .catch(function () { hideSuggestions(); });
    }

    function showSuggestions(results, fieldId) {
        var isProfile = fieldId.startsWith('profile');
        var panelId = isProfile ? 'suggestions-panel-profile' : 'suggestions-panel';
        var listId = isProfile ? 'suggestions-list-profile' : 'suggestions-list';
        var panel = document.getElementById(panelId);
        var list = document.getElementById(listId);

        if (!results || results.length === 0) {
            // Also show landmarks as suggestions
            var html = '';
            landmarks.forEach(function (l) {
                html += '<div class="suggestion-item" onclick="window._pasugo.selectSuggestion(\'' +
                    fieldId + '\', ' + l.pos[0] + ', ' + l.pos[1] + ', \'' + escapeHtml(l.name) + '\')">' +
                    '<div class="suggestion-icon">' + l.icon + '</div>' +
                    '<div><div class="suggestion-text">' + escapeHtml(l.name) + '</div>' +
                    '<div class="suggestion-sub">Gingoog City</div></div></div>';
            });
            list.innerHTML = html || '<div style="padding:16px;text-align:center;color:var(--text-tertiary);font-size:13px;">No results found</div>';
            panel.style.display = 'block';
            return;
        }

        var html = '';
        results.forEach(function (r) {
            var name = r.display_name.split(',').slice(0, 2).join(', ');
            html += '<div class="suggestion-item" onclick="window._pasugo.selectSuggestion(\'' +
                fieldId + '\', ' + r.lat + ', ' + r.lon + ', \'' + escapeHtml(name) + '\')">' +
                '<div class="suggestion-icon">📍</div>' +
                '<div><div class="suggestion-text">' + escapeHtml(name) + '</div>' +
                '<div class="suggestion-sub">' + escapeHtml(r.display_name.split(',').slice(2, 4).join(',').trim()) + '</div></div></div>';
        });
        list.innerHTML = html;
        panel.style.display = 'block';
    }

    function hideSuggestions() {
        document.getElementById('suggestions-panel').style.display = 'none';
        document.getElementById('suggestions-panel-profile').style.display = 'none';
    }

    function selectSuggestion(fieldId, lat, lng, name) {
        var input = document.getElementById(fieldId);
        input.value = name;
        locations[fieldId] = { lat: parseFloat(lat), lng: parseFloat(lng), name: name };
        hideSuggestions();
        updateRoutePreview();
    }


    // ══════════════════════════════════════════════════════════
    //  ROUTE PREVIEW ON MAP
    // ══════════════════════════════════════════════════════════

    function updateRoutePreview() {
        clearRoute();

        // Determine which fields to use (profile fields take precedence if profile is open)
        var isProfileOpen = document.getElementById('rider-profile').classList.contains('active');
        var isProfileRouteVisible = !document.getElementById('profile-route-form').classList.contains('hidden');

        var prefix = (isProfileOpen && isProfileRouteVisible) ? 'profile' : 'pahatod';

        // Only Pahatod/Profile has route fields
        if (activeTab !== 'pahatod' && prefix === 'pahatod') return;

        var pickup = locations[prefix + '-pickup'];
        var dropoff = locations[prefix + '-dropoff'];

        if (pickup) {
            pickupMarker = L.marker([pickup.lat, pickup.lng], {
                icon: L.divIcon({
                    html: '<div class="route-pin-start"></div>',
                    className: '',
                    iconSize: [14, 14],
                    iconAnchor: [7, 7]
                })
            }).addTo(map).bindTooltip(pickup.name, { direction: 'top', offset: [0, -10] });
        }

        if (dropoff) {
            dropoffMarker = L.marker([dropoff.lat, dropoff.lng], {
                icon: L.divIcon({
                    html: '<div class="route-pin-end"></div>',
                    className: '',
                    iconSize: [14, 14],
                    iconAnchor: [7, 7]
                })
            }).addTo(map).bindTooltip(dropoff.name, { direction: 'top', offset: [0, -10] });
        }

        if (pickup && dropoff) {
            var color = '#FF6B35'; // Pahatod color
            routeLayer = L.polyline(
                [[pickup.lat, pickup.lng], [dropoff.lat, dropoff.lng]],
                { color: color, weight: 4, opacity: 0.7, dashArray: '8, 8' }
            ).addTo(map);

            map.fitBounds(routeLayer.getBounds(), { padding: [60, 60] });
        } else if (pickup) {
            map.flyTo([pickup.lat, pickup.lng], 16, { duration: 0.6 });
        } else if (dropoff) {
            map.flyTo([dropoff.lat, dropoff.lng], 16, { duration: 0.6 });
        }
    }

    function clearRoute() {
        if (routeLayer) { map.removeLayer(routeLayer); routeLayer = null; }
        if (pickupMarker) { map.removeLayer(pickupMarker); pickupMarker = null; }
        if (dropoffMarker) { map.removeLayer(dropoffMarker); dropoffMarker = null; }
    }


    // ══════════════════════════════════════════════════════════
    //  FIND RIDERS
    // ══════════════════════════════════════════════════════════

    function findRiders(serviceType) {
        activeServiceType = serviceType;

        // Show riders section, hide forms
        document.getElementById('form-pahatod').classList.add('hidden');
        document.getElementById('form-pasugo').classList.add('hidden');
        document.getElementById('riders-section').classList.remove('hidden');

        // Update rider list
        updateRiderList();
    }

    function backToSearch() {
        document.getElementById('riders-section').classList.add('hidden');
        document.getElementById('form-' + activeTab).classList.remove('hidden');
    }


    // ══════════════════════════════════════════════════════════
    //  RIDER LOCATION SYNC (real-time)
    // ══════════════════════════════════════════════════════════

    // Safe channel subscription with error handling
    function safeChannelSubscribe(channelName, eventName, callback) {
        console.log(`[WebSocket] Subscribing to ${channelName} for event ${eventName}`);
        try {
            const channel = echo.channel(channelName);
            channel.listen(eventName, (data) => {
                console.log(`[WebSocket] Event ${eventName} received on ${channelName}:`, data);
                callback(data);
            });

            // Monitor subscription errors
            channel.error((error) => {
                console.error(`[WebSocket] Channel ${channelName} error:`, error);
            });

            return channel;
        } catch (error) {
            console.error(`[WebSocket] Failed to subscribe to ${channelName}:`, error);
            return null;
        }
    }

    safeChannelSubscribe('riders', '.rider.location.updated', function (data) {
        if (data.status === 'offline') {
            if (riderMarkers[data.riderId]) {
                map.removeLayer(riderMarkers[data.riderId].marker);
                delete riderMarkers[data.riderId];
                updateRiderList();
            }
            return;
        }

        updateOrAddRiderMarker(data);
        updateRiderList();
    });


    // ══════════════════════════════════════════════════════════
    //  RIDER LIST
    // ══════════════════════════════════════════════════════════

    function updateRiderList() {
        var list = document.getElementById('rider-list');
        var riders = Object.values(riderMarkers);
        var numEl = document.getElementById('rider-count-num');
        numEl.textContent = riders.length;

        if (riders.length === 0) {
            list.innerHTML =
                '<div class="empty-state">' +
                '<div class="empty-icon">🔍</div>' +
                '<div class="empty-title">No riders found</div>' +
                '<div class="empty-text">There are currently no active riders in your area. Please check back soon.</div>' +
                '</div>';
            return;
        }

        list.innerHTML = riders.map(function (r) {
            var isSelected = selectedRiderId == r.data.riderId ? 'selected' : '';
            var statusCls = r.data.status === 'available' ? 'available' : 'busy';
            return (
                '<div class="rider-card ' + isSelected + '" ' +
                'onclick="window._pasugo.directBookRider(' + r.data.riderId + ')" ' +
                'id="rider-card-' + r.data.riderId + '">' +
                '<div class="rider-avatar">🛵</div>' +
                '<div class="rider-info">' +
                '<div class="rider-name">' + escapeHtml(r.data.name) + '</div>' +
                '<div class="rider-bio">' + escapeHtml(r.data.bio || 'Fleet Member') + '</div>' +
                '</div>' +
                '<span class="rider-status-badge ' + statusCls + '">' + r.data.status + '</span>' +
                '</div>'
            );
        }).join('');
    }


    // ══════════════════════════════════════════════════════════
    //  RIDER PROFILE
    // ══════════════════════════════════════════════════════════

    function openRiderProfile(id) {
        var riderObj = riderMarkers[id];
        if (!riderObj) return;

        selectedRiderId = id;
        selectedRiderName = riderObj.data.name;
        selectedRiderData = riderObj.data;

        document.getElementById('profile-name').textContent = riderObj.data.name;
        document.getElementById('profile-bio').textContent = riderObj.data.bio || 'Fleet Member';
        document.getElementById('profile-status-text').textContent = riderObj.data.status === 'available' ? 'Available' : 'Busy';
        document.getElementById('profile-trips').textContent = riderObj.data.trips || '—';
        document.getElementById('profile-rating').textContent = riderObj.data.rating || '—';
        document.getElementById('profile-eta').textContent = riderObj.data.eta || '~5m';

        map.flyTo([riderObj.data.lat, riderObj.data.lng], 17, { duration: 0.8 });
        document.getElementById('rider-profile').classList.add('active');
        updateRiderList();
    }

    function closeProfile() {
        document.getElementById('rider-profile').classList.remove('active');
        hideProfileRoute();
    }

    function showProfileRoute() {
        document.getElementById('profile-actions-buttons').classList.add('hidden');
        document.getElementById('profile-route-form').classList.remove('hidden');
        // Reset inputs when showing
        document.getElementById('profile-pickup').value = '';
        document.getElementById('profile-dropoff').value = '';
        locations['profile-pickup'] = null;
        locations['profile-dropoff'] = null;
        clearRoute();
    }

    function hideProfileRoute() {
        document.getElementById('profile-actions-buttons').classList.remove('hidden');
        document.getElementById('profile-route-form').classList.add('hidden');
        clearRoute();
    }

    function confirmPahatodRide() {
        if (!locations['profile-pickup'] || !locations['profile-dropoff']) {
            showToast('Please set both pickup and dropoff locations.', 'info', 4000);
            return;
        }
        openChatWithService('pahatod', true);
    }


    function directBookRider(id) {
        var riderObj = riderMarkers[id];
        if (!riderObj) return;

        // Only direct to chat if available
        if (riderObj.data.status !== 'available') {
            showToast(
                '🛵 ' + escapeHtml(riderObj.data.name) + ' is currently accommodating another user. Please choose a different rider.',
                'warning',
                5000
            );
            return;
        }

        // For Pahatod, ensure route is set
        if (activeServiceType === 'pahatod') {
            if (!locations['pahatod-pickup'] || !locations['pahatod-dropoff']) {
                showToast('Please set your pickup and destination first.', 'info', 4000);
                backToSearch();
                return;
            }
        }

        selectedRiderId = id;
        selectedRiderName = riderObj.data.name;
        selectedRiderData = riderObj.data;

        // Direct to chat with current service type (Pahatod or Pasugo)
        openChatWithService(activeServiceType);
    }


    // ══════════════════════════════════════════════════════════
    //  CHAT WINDOW
    // ══════════════════════════════════════════════════════════

    function openChatWithService(type, fromProfile = false) {
        activeServiceType = type;
        closeProfile();
        openChat(fromProfile);
        setTimeout(function () { startService(type, fromProfile); }, 300);
    }

    function openChat(fromProfile = false) {
        var chatOverlay = document.getElementById('chat-overlay');
        document.getElementById('chat-rider-name').textContent = selectedRiderName;

        // Show route banner in chat
        var banner = document.getElementById('chat-route-banner');
        var prefix = fromProfile ? 'profile' : 'pahatod';
        var pickup = locations[prefix + '-pickup'];
        var dropoff = locations[prefix + '-dropoff'];

        if (activeServiceType === 'pahatod' && (pickup || dropoff)) {
            document.getElementById('route-banner-icon').textContent = '🏍️';
            document.getElementById('route-banner-type').textContent = 'Pahatod';
            document.getElementById('route-banner-detail').textContent = (pickup ? pickup.name : '...') + ' → ' + (dropoff ? dropoff.name : '...');
            banner.style.display = 'flex';
        } else if (activeServiceType === 'pasugo') {
            document.getElementById('route-banner-icon').textContent = '📦';
            document.getElementById('route-banner-type').textContent = 'Pasugo';
            document.getElementById('route-banner-detail').textContent = 'Delivery / Errand Request';
            banner.style.display = 'flex';
        } else { banner.style.display = 'none'; }

        // Fetch History
        var chatBody = document.getElementById('chat-body');
        chatBody.innerHTML = '<div class="msg-system"><span class="msg-system-text">Connecting to secure chat...</span></div>';

        fetch('/chat/history?client_id=' + CLIENT_ID + '&rider_id=' + selectedRiderId)
            .then(function (r) { return r.json(); })
            .then(function (messages) {
                chatBody.innerHTML = '';
                if (messages.length > 0) {
                    document.getElementById('chat-input-area').classList.add('visible');
                    messages.forEach(function (msg) {
                        appendMessage(msg.message, msg.sender_type === 'client' ? 'sent' : 'received');
                    });
                } else {
                    appendSystemMessage('Awaiting rider response...');
                }
            });

        document.getElementById('service-panel').style.display = 'none';
        chatOverlay.classList.add('active');
    }

    function closeChat() {
        var chatOverlay = document.getElementById('chat-overlay');
        chatOverlay.classList.remove('active');
        clearInterval(countdownInterval);
        document.getElementById('chat-timer').classList.remove('visible');

        if (isRequestPending && selectedRiderId) {
            fetch('/client/riders/' + selectedRiderId + '/cancel', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
            }).catch(function () { /* ignore cancel failures */ });
            isRequestPending = false;
        }
        // Restore service panel
        document.getElementById('service-panel').style.display = 'flex';
    }


    // ══════════════════════════════════════════════════════════
    //  SERVICE REQUEST
    // ══════════════════════════════════════════════════════════

    function startService(type, fromProfile = false) {
        if (!selectedRiderId || isRequestPending) return;

        // Prevent booking if mission is already active
        if (body.dataset.activeMission) {
            showToast('You already have an ongoing mission. Please complete it from your dashboard or active chat.', 'warning', 5000);
            closeChat();
            return;
        }

        isRequestPending = true;

        document.getElementById('chat-waiting').style.display = 'flex';

        var emoji = type === 'pasugo' ? '📦' : '🏍️';
        var label = type === 'pasugo' ? 'Pasugo Delivery' : 'Pahatod Ride';
        appendSystemMessage(emoji + ' ' + label + ' request sent');
        appendMessage('Waiting for rider to accept your request...', 'system');

        // Include route data in request (only Pahatod has it)
        var prefix = fromProfile ? 'profile' : 'pahatod';

        var pickup = locations[prefix + '-pickup'];
        var dropoff = locations[prefix + '-dropoff'];

        fetch('/client/riders/' + selectedRiderId + '/order', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({
                service_type: type,
                pickup: pickup,
                dropoff: dropoff
            })
        }).then(async r => {
            if (r.status === 409) {
                // Rider is busy / accommodating another user
                const data = await r.json();
                isRequestPending = false;
                clearInterval(countdownInterval);
                document.getElementById('chat-timer').classList.remove('visible');
                document.getElementById('chat-waiting').style.display = 'none';
                closeChat();
                showToast(
                    '🛵 ' + (data.rider_name || 'This rider') + ' is currently accommodating another user. Please choose a different rider.',
                    'warning',
                    6000
                );
                // Update the rider's status locally so the list reflects it
                if (riderMarkers[selectedRiderId]) {
                    riderMarkers[selectedRiderId].data.status = 'busy';
                    updateRiderList();
                }
            } else if (r.status === 403) {
                const data = await r.json();
                isRequestPending = false;
                closeChat();
                showToast(data.message, 'error', 5000);
                // If they have an active mission but it wasn't in dataset (e.g. just accepted), reload
                if (data.message.includes('mission')) location.reload();
            } else if (r.ok) {
                // Request accepted by server
                const data = await r.json();
                if (data.broadcast_warning) {
                    showToast('⚠️ Real-time notifications may be delayed. The rider will be notified shortly.', 'info', 5000);
                }
            } else {
                // Unexpected error
                isRequestPending = false;
                closeChat();
                showToast('Something went wrong. Please try again.', 'error', 4000);
            }
        }).catch(function (err) {
            console.error('[Order] Network error:', err);
            isRequestPending = false;
            closeChat();
            showToast('Network error. Please check your connection and try again.', 'error', 5000);
        });

        // Countdown timer
        var timerEl = document.getElementById('chat-timer');
        timerEl.classList.add('visible');
        var timeLeft = 60;
        timerEl.textContent = timeLeft + 's';

        clearInterval(countdownInterval);
        countdownInterval = setInterval(function () {
            timeLeft--;
            timerEl.textContent = timeLeft + 's';
            if (timeLeft <= 0) {
                clearInterval(countdownInterval);
                timerEl.classList.remove('visible');
                document.getElementById('chat-waiting').style.display = 'none';
                appendSystemMessage('⏱ Request expired');
                appendMessage('The rider did not respond in time. Please try again or select another rider.', 'received');

                fetch('/client/riders/' + selectedRiderId + '/cancel', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
                }).catch(function () { /* ignore cancel failures */ });
                isRequestPending = false;
            }
        }, 1000);
    }


    // ══════════════════════════════════════════════════════════
    //  RIDER RESPONSE LISTENER
    // ══════════════════════════════════════════════════════════

    safeChannelSubscribe('client.' + CLIENT_ID, '.rider.responded', function (data) {
        isRequestPending = false;
        if (data.decision === 'accept') {
            clearInterval(countdownInterval);
            document.getElementById('chat-timer').classList.remove('visible');
            document.getElementById('chat-waiting').style.display = 'none';
            document.getElementById('chat-input-area').classList.add('visible');

            appendSystemMessage('✅ Rider accepted your request!');
            appendMessage('Hey! I accepted your request. Let me know the details.', 'received');

            setTimeout(function () { document.getElementById('chat-input').focus(); }, 400);
        } else {
            document.getElementById('chat-waiting').style.display = 'none';
            clearInterval(countdownInterval);
            document.getElementById('chat-timer').classList.remove('visible');
            appendSystemMessage('❌ Rider declined');
            appendMessage('The rider is currently unavailable. Please try another rider.', 'received');
        }
    });

    safeChannelSubscribe('client.' + CLIENT_ID, '.rider.cancelled', function () {
        isRequestPending = false;
        document.getElementById('chat-waiting').style.display = 'none';
        clearInterval(countdownInterval);
        document.getElementById('chat-timer').classList.remove('visible');
        appendSystemMessage('🚫 Request cancelled');
    });


    // ══════════════════════════════════════════════════════════
    //  INCOMING MESSAGES
    // ══════════════════════════════════════════════════════════

    safeChannelSubscribe('chat.client.' + CLIENT_ID, '.message.sent', function (data) {
        console.log(`[Chat] Incoming message for client ${CLIENT_ID}:`, data);
        if (data.senderType === 'rider') {
            if (!selectedRiderId || selectedRiderId == data.senderId) {
                if (!selectedRiderId) selectedRiderId = data.senderId;
                appendMessage(data.message, 'received');

                // Auto-open chat if it's hidden
                const chatOverlay = document.getElementById('chat-overlay');
                if (chatOverlay && !chatOverlay.classList.contains('active')) {
                    openChat();
                }

                if (data.message.indexOf('🏁 MISSION COMPLETED') !== -1) {
                    setTimeout(function () { location.reload(); }, 3000);
                }
            } else {
                console.warn(`[Chat] Mismatch: sender ${data.senderId} vs selected ${selectedRiderId}`);
            }
        }
    });


    // ══════════════════════════════════════════════════════════
    //  MESSAGE HELPERS
    // ══════════════════════════════════════════════════════════

    function appendMessage(text, type) {
        var chatBody = document.getElementById('chat-body');
        var group = document.createElement('div');

        if (type === 'sent') {
            group.className = 'msg-group sent';
        } else if (type === 'received') {
            group.className = 'msg-group received';
        } else {
            group.className = 'msg-group';
        }

        if (type === 'system') {
            var bubble = document.createElement('div');
            bubble.className = 'msg-bubble msg-received';
            bubble.textContent = text;
            group.className = 'msg-group received';
            group.appendChild(bubble);
        } else {
            var bubble = document.createElement('div');
            bubble.className = 'msg-bubble ' + (type === 'sent' ? 'msg-sent' : 'msg-received');
            bubble.textContent = text;
            group.appendChild(bubble);

            var time = document.createElement('div');
            time.className = 'msg-time';
            var now = new Date();
            time.textContent = pad(now.getHours()) + ':' + pad(now.getMinutes());
            group.appendChild(time);
        }

        chatBody.appendChild(group);
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    function appendSystemMessage(text) {
        var chatBody = document.getElementById('chat-body');
        var div = document.createElement('div');
        div.className = 'msg-system';
        div.innerHTML = '<span class="msg-system-text">' + escapeHtml(text) + '</span>';
        chatBody.appendChild(div);
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    function sendMessage() {
        var input = document.getElementById('chat-input');
        var text = input.value.trim();
        if (!text || !selectedRiderId) return;

        fetch('/chat/send', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: JSON.stringify({
                sender_id: parseInt(CLIENT_ID) || 0,
                receiver_id: selectedRiderId,
                message: text,
                sender_type: 'client',
                order_id: currentMissionId
            })
        });

        appendMessage(text, 'sent');
        input.value = '';
        input.focus();
    }

    // Enter key to send
    document.getElementById('chat-input').addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });


    // ══════════════════════════════════════════════════════════
    //  TOUCH GESTURES FOR PANEL
    // ══════════════════════════════════════════════════════════

    (function () {
        var panel = document.getElementById('service-panel');
        var startY = 0, isDragging = false;

        panel.addEventListener('touchstart', function (e) {
            if (e.target.closest('.rider-card') || e.target.closest('.loc-input') || e.target.closest('.service-tab')) return;
            startY = e.touches[0].clientY;
            isDragging = true;
        }, { passive: true });

        panel.addEventListener('touchmove', function (e) {
            if (!isDragging) return;
            var diff = e.touches[0].clientY - startY;
            if (diff > 50 && !panelCollapsed) {
                togglePanel();
                isDragging = false;
            } else if (diff < -50 && panelCollapsed) {
                togglePanel();
                isDragging = false;
            }
        }, { passive: true });

        panel.addEventListener('touchend', function () { isDragging = false; }, { passive: true });
    })();


    // ══════════════════════════════════════════════════════════
    //  CLICK OUTSIDE - Close suggestions
    // ══════════════════════════════════════════════════════════

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.loc-input') && !e.target.closest('.search-suggestions')) {
            hideSuggestions();
        }
    });


    // ══════════════════════════════════════════════════════════
    //  MAP CLICK - Set location
    // ══════════════════════════════════════════════════════════

    map.on('click', function (e) {
        // Map click only sets location for Pahatod route fields or Profile route fields
        var isProfileRouteVisible = !document.getElementById('profile-route-form').classList.contains('hidden');
        if (!activeInputField) return;
        if (activeTab !== 'pahatod' && !isProfileRouteVisible) return;

        var input = document.getElementById(activeInputField);
        if (!input) return;

        var lat = e.latlng.lat.toFixed(6);
        var lng = e.latlng.lng.toFixed(6);

        // Reverse geocode
        fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var name = data.display_name ? data.display_name.split(',').slice(0, 2).join(', ') : 'Selected Location';
                input.value = name;
                locations[activeInputField] = { lat: parseFloat(lat), lng: parseFloat(lng), name: name };
                updateRoutePreview();
            })
            .catch(function () {
                input.value = 'Lat: ' + lat + ', Lng: ' + lng;
                locations[activeInputField] = { lat: parseFloat(lat), lng: parseFloat(lng), name: 'Selected Location' };
                updateRoutePreview();
            });
    });


    // ══════════════════════════════════════════════════════════
    //  UTILITIES
    // ══════════════════════════════════════════════════════════

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function pad(n) {
        return n.toString().padStart(2, '0');
    }


    // ══════════════════════════════════════════════════════════
    //  RESTORE MISSION
    // ══════════════════════════════════════════════════════════
    const activeMissionRaw = body.dataset.activeMission;
    if (activeMissionRaw) {
        try {
            const mission = JSON.parse(activeMissionRaw);
            selectedRiderId = mission.rider_id;
            selectedRiderName = mission.rider ? mission.rider.name : 'Rider';
            currentMissionId = mission.id;
            activeServiceType = mission.details && mission.details.includes('Delivery') ? 'pasugo' : 'pahatod';

            // Re-open chat after a short delay for initialization
            setTimeout(function () {
                if (mission.status === 'mission_accepted') {
                    openChat();
                    document.getElementById('chat-input-area').classList.add('visible');
                    appendMessage('We are still connected. Ready for your order details.', 'received');
                } else {
                    // For formalized orders, just prepare the chat but don't force open it on map
                    // Usually client is on dashboard anyway, but if they go to map, it's ready.
                }
            }, 500);
        } catch (e) { }
    }

    // ══════════════════════════════════════════════════════════
    //  PUBLIC API  (called from inline onclick handlers)
    // ══════════════════════════════════════════════════════════

    window._pasugo = {
        togglePanel: togglePanel,
        switchTab: switchTab,
        onInputFocus: onInputFocus,
        useMyLocation: useMyLocation,
        selectSuggestion: selectSuggestion,
        findRiders: findRiders,
        backToSearch: backToSearch,
        openRiderProfile: openRiderProfile,
        closeProfile: closeProfile,
        openChatWithService: openChatWithService,
        closeChat: closeChat,
        startService: startService,
        sendMessage: sendMessage,
        locateMe: locateMe,
        toggleLayers: toggleLayers,
        showProfileRoute: showProfileRoute,
        hideProfileRoute: hideProfileRoute,
        confirmPahatodRide: confirmPahatodRide,
        directBookRider: directBookRider,
    };

})();
