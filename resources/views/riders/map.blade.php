<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=0">
    <title>PasugoAPP — Ride & Deliver</title>
    <meta name="description" content="Book rides and delivery services in Gingoog City with Pahatod and Pasugo">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Leaflet --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    {{-- Map Styles --}}
    <link rel="stylesheet" href="{{ asset('css/map.css') }}">
</head>

<body
    data-reverb-key="{{ config('broadcasting.connections.reverb.key') }}"
    data-reverb-host="{{ config('broadcasting.connections.reverb.options.host') }}"
    data-reverb-port="{{ config('broadcasting.connections.reverb.options.port') }}"
    data-csrf="{{ csrf_token() }}"
    data-client-id="{{ Auth::guard('client')->id() ?? 'guest' }}"
    data-active-mission="{{ isset($activeMission) ? json_encode($activeMission) : '' }}"
>

    {{-- ═══════════════════════════════════════════════════════
         MAP HEADER
    ═══════════════════════════════════════════════════════ --}}
    <div class="map-header" id="map-header">
        <a href="{{ route('client.dashboard') }}" class="btn-back" id="btn-back-main">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/>
            </svg>
        </a>
        <div class="logo-pill">
            <span class="logo-text">PasugoAPP</span>
        </div>
        <button class="btn-locate" id="btn-locate" onclick="window._pasugo.locateMe()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"/>
                <path d="M12 2v4M12 18v4M2 12h4M18 12h4"/>
            </svg>
        </button>
    </div>


    {{-- ═══════════════════════════════════════════════════════
         MAP
    ═══════════════════════════════════════════════════════ --}}
    <div id="map"></div>


    {{-- ═══════════════════════════════════════════════════════
         SERVICE SELECTOR (Pahatod / Pasugo)
    ═══════════════════════════════════════════════════════ --}}
    <div class="service-panel" id="service-panel">
        <div class="panel-handle-area" onclick="window._pasugo.togglePanel()">
            <div class="panel-handle"></div>
        </div>

        {{-- Service Tab Switcher --}}
        <div class="service-tabs" id="service-tabs">
            <button class="service-tab active" id="tab-pahatod" onclick="window._pasugo.switchTab('pahatod')">
                <span class="tab-icon">🏍️</span>
                <span class="tab-label">Pahatod</span>
                <span class="tab-desc">Ride</span>
            </button>
            <button class="service-tab" id="tab-pasugo" onclick="window._pasugo.switchTab('pasugo')">
                <span class="tab-icon">📦</span>
                <span class="tab-label">Pasugo</span>
                <span class="tab-desc">Deliver</span>
            </button>
        </div>

        {{-- ─── PAHATOD Form (Ride: Pickup → Dropoff) ─── --}}
        <div class="service-form" id="form-pahatod">
            <div class="route-inputs">
                <div class="route-line">
                    <div class="route-dots">
                        <div class="dot dot-start"></div>
                        <div class="dot-line"></div>
                        <div class="dot dot-end"></div>
                    </div>
                    <div class="route-fields">
                        <div class="input-group">
                            <input type="text" class="loc-input" id="pahatod-pickup"
                                   placeholder="Pickup location" autocomplete="off"
                                   onfocus="window._pasugo.onInputFocus('pahatod-pickup')">
                            <button class="btn-my-loc" onclick="window._pasugo.useMyLocation('pahatod-pickup')" title="Use my location">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="3"/>
                                    <path d="M12 2v4M12 18v4M2 12h4M18 12h4"/>
                                </svg>
                            </button>
                        </div>
                        <div class="input-group">
                            <input type="text" class="loc-input" id="pahatod-dropoff"
                                   placeholder="Where to?" autocomplete="off"
                                   onfocus="window._pasugo.onInputFocus('pahatod-dropoff')">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search suggestions dropdown --}}
            <div class="search-suggestions" id="suggestions-panel" style="display:none;">
                <div class="suggestions-list" id="suggestions-list"></div>
            </div>

            <button class="btn-find-rider" id="btn-find-pahatod" onclick="window._pasugo.findRiders('pahatod')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
                </svg>
                Find Riders Nearby
            </button>
        </div>

        {{-- ─── PASUGO Panel (Delivery / Errand — no route fields) ─── --}}
        <div class="service-form hidden" id="form-pasugo">
            <div class="pasugo-info">
                <div class="pasugo-info-icon">📦</div>
                <div class="pasugo-info-text">
                    <div class="pasugo-info-title">Send or Receive Anything</div>
                    <div class="pasugo-info-desc">Let a rider pick up and deliver items for you. Tap below to find available riders nearby.</div>
                </div>
            </div>

            <button class="btn-find-rider btn-find-pasugo" id="btn-find-pasugo" onclick="window._pasugo.findRiders('pasugo')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
                </svg>
                Find Delivery Riders
            </button>
        </div>

        {{-- ─── Riders List (shown after search) ─── --}}
        <div class="riders-section hidden" id="riders-section">
            <div class="riders-header">
                <div>
                    <h2 class="riders-title">
                        Nearby Riders
                        <span class="rider-count-badge" id="rider-count">
                            <span class="rider-count-dot"></span>
                            <span id="rider-count-num">0</span> online
                        </span>
                    </h2>
                    <p class="riders-subtitle">Gingoog City Network</p>
                </div>
                <button class="btn-back-search" onclick="window._pasugo.backToSearch()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/>
                    </svg>
                </button>
            </div>
            <div class="rider-list-scroll" id="rider-list">
                <div class="empty-state">
                    <div class="empty-icon">📡</div>
                    <div class="empty-title">Scanning for riders...</div>
                    <div class="empty-text">Looking for available riders in your area</div>
                </div>
            </div>
        </div>
    </div>


    {{-- ═══════════════════════════════════════════════════════
         RIDER PROFILE OVERLAY
    ═══════════════════════════════════════════════════════ --}}
    <div class="rider-profile-overlay" id="rider-profile">
        <div class="profile-backdrop" onclick="window._pasugo.closeProfile()"></div>
        <div class="profile-sheet">
            <button class="profile-close-btn" onclick="window._pasugo.closeProfile()">✕</button>

            <div class="profile-header">
                <div class="profile-avatar" id="profile-avatar">🛵</div>
                <div class="profile-name" id="profile-name">Rider Name</div>
                <div class="profile-status">
                    <span class="profile-status-dot"></span>
                    <span id="profile-status-text">Available</span>
                </div>
                <div class="profile-bio" id="profile-bio">Fleet Member</div>
            </div>

            <div class="profile-stats">
                <div class="stat-card">
                    <div class="stat-value" id="profile-trips">—</div>
                    <div class="stat-label">Trips</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="profile-rating">—</div>
                    <div class="stat-label">Rating</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="profile-eta">—</div>
                    <div class="stat-label">ETA</div>
                </div>
            </div>

            {{-- Service Buttons --}}
            <div class="profile-actions" id="profile-actions-buttons">
                <button class="btn-service btn-pahatod-action" id="btn-profile-pahatod"
                        onclick="window._pasugo.showProfileRoute()">
                    <span class="btn-service-icon">🏍️</span>
                    Pahatod — Book Ride
                </button>
                <button class="btn-service btn-pasugo-action" id="btn-profile-pasugo"
                        onclick="window._pasugo.openChatWithService('pasugo')">
                    <span class="btn-service-icon">📦</span>
                    Pasugo — Send / Deliver
                </button>
            </div>

            {{-- Pahatod Route Fields (shown after clicking Book Ride) --}}
            <div class="profile-route-form hidden" id="profile-route-form">
                <div class="profile-route-header">
                    <button class="btn-route-back" onclick="window._pasugo.hideProfileRoute()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <div class="profile-route-title">Set Your Route</div>
                </div>

                <div class="route-inputs">
                    <div class="route-line">
                        <div class="route-dots">
                            <div class="dot dot-start"></div>
                            <div class="dot-line"></div>
                            <div class="dot dot-end"></div>
                        </div>
                        <div class="route-fields">
                            <div class="input-group">
                                <input type="text" class="loc-input" id="profile-pickup"
                                       placeholder="Pickup location" autocomplete="off"
                                       onfocus="window._pasugo.onInputFocus('profile-pickup')">
                                <button class="btn-my-loc" onclick="window._pasugo.useMyLocation('profile-pickup')" title="Use my location">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="3"/>
                                        <path d="M12 2v4M12 18v4M2 12h4M18 12h4"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="input-group">
                                <input type="text" class="loc-input" id="profile-dropoff"
                                       placeholder="Where to?" autocomplete="off"
                                       onfocus="window._pasugo.onInputFocus('profile-dropoff')">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Suggestions for profile route inputs --}}
                <div class="search-suggestions" id="suggestions-panel-profile" style="display:none;">
                    <div class="suggestions-list" id="suggestions-list-profile"></div>
                </div>

                <button class="btn-find-rider btn-confirm-ride" id="btn-confirm-ride"
                        onclick="window._pasugo.confirmPahatodRide()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12l5 5L20 7"/>
                    </svg>
                    Confirm & Book Ride
                </button>
            </div>
        </div>
    </div>


    {{-- ═══════════════════════════════════════════════════════
         CHAT OVERLAY  (Messenger-like)
    ═══════════════════════════════════════════════════════ --}}
    <div class="chat-overlay" id="chat-overlay">

        {{-- Chat Header --}}
        <div class="chat-header">
            <button class="chat-back-btn" id="chat-back-btn" onclick="window._pasugo.closeChat()">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/>
                </svg>
            </button>
            <div class="chat-header-avatar" id="chat-avatar">🛵</div>
            <div class="chat-header-info">
                <div class="chat-header-name" id="chat-rider-name">Rider Name</div>
                <div class="chat-header-status">
                    <span class="chat-status-dot"></span>
                    <span id="chat-status-text">Active now</span>
                </div>
            </div>
            <div class="chat-timer" id="chat-timer">60s</div>
        </div>

        {{-- Route info banner --}}
        <div class="chat-route-banner" id="chat-route-banner" style="display:none;">
            <div class="route-banner-icon" id="route-banner-icon">🏍️</div>
            <div class="route-banner-info">
                <div class="route-banner-type" id="route-banner-type">Pahatod</div>
                <div class="route-banner-detail" id="route-banner-detail">Pickup → Dropoff</div>
            </div>
        </div>

        {{-- Chat Body --}}
        <div class="chat-body" id="chat-body">
            {{-- Messages populate here via JS --}}
        </div>

        {{-- Waiting State --}}
        <div class="waiting-animation" id="chat-waiting" style="display:none;">
            <div class="waiting-spinner"></div>
            <div class="waiting-text">Waiting for rider to accept...</div>
            <div class="waiting-sub">This may take up to 60 seconds</div>
        </div>

        {{-- Chat Input --}}
        <div class="chat-input-area" id="chat-input-area">
            <div class="chat-input-wrap">
                <input type="text" class="chat-input" id="chat-input"
                       placeholder="Type a message..." autocomplete="off">
                <button class="chat-send-btn" id="chat-send-btn" onclick="window._pasugo.sendMessage()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </button>
            </div>
        </div>

    </div>


    {{-- ═══════════════════════════════════════════════════════
         SCRIPTS
    ═══════════════════════════════════════════════════════ --}}
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
    <script src="{{ asset('js/map.js') }}"></script>


</body>
</html>
