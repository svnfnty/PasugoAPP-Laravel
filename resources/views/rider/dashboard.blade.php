@extends('layouts.app')

@section('content') 
<style>
    .mobile-card { @apply bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-100 mb-4; }
    .stat-pill { @apply flex-1 bg-slate-50 p-4 rounded-3xl text-center border border-slate-100; }
    .action-button { @apply w-full py-4 rounded-3xl font-black text-xs uppercase tracking-widest transition-all active:scale-95 shadow-lg; }
    
    @keyframes pulse-custom {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.8; transform: scale(0.98); }
    }
    .animate-urgent { animation: pulse-custom 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
</style>

<!-- Welcome Section -->
<div class="flex items-center justify-between mb-6 px-2">
    <div>
        <h1 class="text-2xl font-black tracking-tighter">Fleet Console</h1>
        <div class="flex items-center gap-1.5 mt-1">
            <span class="w-2 h-2 rounded-full bg-{{ $rider->status == 'available' ? 'emerald' : 'amber' }}-500"></span>
            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ $rider->status }}</span>
        </div>
    </div>
    <div class="flex gap-2">
         <button onclick="simulateLocation()" class="p-3 bg-slate-100 rounded-2xl text-xs">🛰️</button>
    </div>
</div>

<!-- Stats Grid -->
<div class="flex gap-3 mb-8">
    <div class="stat-pill border-orange-100 bg-orange-50/30">
        <div class="text-[10px] font-black text-orange-400 uppercase tracking-widest mb-1">Income</div>
        <div class="text-lg font-black tracking-tighter text-orange-600">₱{{ number_format($netIncome, 0) }}</div>
    </div>
    <div class="stat-pill">
        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Tasks</div>
        <div class="text-lg font-black tracking-tighter text-slate-800">{{ $totalOrders }}</div>
    </div>
    <div class="stat-pill">
        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Fleet</div>
        <div class="text-lg font-black tracking-tighter text-slate-800">Elite</div>
    </div>
</div>

<!-- Incoming Requests (Live) -->
<div id="realtime-requests-container" class="mb-4">
    <div id="live-requests-list" class="space-y-3">
        <div class="bg-slate-900 rounded-[2.5rem] p-8 text-center border border-slate-800 shadow-xl">
            <div class="w-12 h-12 bg-slate-800 text-white rounded-2xl flex items-center justify-center mx-auto mb-4 animate-pulse">📡</div>
            <p class="text-white font-black text-sm uppercase tracking-widest">Scanning Gingoog City</p>
            <p class="text-slate-500 text-[10px] font-bold mt-1 uppercase tracking-widest">Watching for express orders...</p>
        </div>
    </div>
</div>

<!-- Active Missions Section -->
<div class="mb-10">
    <h2 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-4 px-2">Active Missions</h2>
    
    @forelse($myActiveOrders as $order)
        <div class="mobile-card">
            <div class="flex justify-between items-center mb-4">
                <span class="text-[10px] font-black text-orange-500 bg-orange-50 px-3 py-1 rounded-full uppercase">ORDER #{{ $order->id }}</span>
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $order->status }}</span>
            </div>
            
            <h3 class="text-lg font-black leading-tight mb-4">{{ $order->details ?: 'Express PasugoAPP' }}</h3>
            
            <div class="space-y-3 mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-xs">🏠</div>
                    <p class="text-[11px] font-bold text-slate-500 truncate">{{ $order->pickup_address }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs">📍</div>
                    <p class="text-[11px] font-black text-slate-900 truncate">{{ $order->delivery_address }}</p>
                </div>
            </div>

            @if($order->status == 'accepted')
                <div class="bg-emerald-50 text-emerald-600 p-4 rounded-3xl text-center text-[10px] font-black uppercase tracking-widest border border-emerald-100">
                    Order Formalized - Proceed to Delivery
                </div>
            @elseif($order->status == 'picked_up')
                <form action="{{ route('rider.order.update', $order) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="delivered">
                    <button type="submit" class="action-button bg-emerald-600 text-white shadow-emerald-100">ARRIVED & DELIVERED</button>
                </form>
            @endif
        </div>
    @empty
        <div class="p-10 text-center bg-white rounded-[2.5rem] border border-dashed border-slate-200">
            <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">No active missions</p>
        </div>
    @endforelse
</div>

<!-- Rider Chat Window (Full Screen Mobile) -->
<div id="rider-chat-window" class="hidden fixed inset-0 bg-white z-[100] flex-col shadow-2xl">
    <!-- Chat Header -->
    <div class="flex items-center gap-3 px-4 py-3 bg-white border-b border-slate-100 min-h-[64px] shrink-0 pt-[max(12px,env(safe-area-inset-top))]">
        <button onclick="closeRiderChat()" class="w-9 h-9 border-none bg-transparent cursor-pointer flex items-center justify-center text-orange-500 rounded-full transition-colors shrink-0 hover:bg-slate-50">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/>
            </svg>
        </button>
        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-500 to-[#FF3D00] flex items-center justify-center text-lg shrink-0 text-white">👤</div>
        <div class="flex-1 min-w-0">
            <div id="chat-client-name" class="text-base font-bold text-slate-900 whitespace-nowrap overflow-hidden text-ellipsis">Client</div>
            <div class="flex items-center gap-1.5 text-[11px] text-slate-400 font-medium">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span>Active now</span>
            </div>
        </div>
    </div>
    
    <!-- Chat Route Banner -->
    <div id="rider-chat-banner" class="flex items-center gap-3 px-4 py-2.5 bg-slate-50 border-b border-slate-100 shrink-0">
        <div class="text-xl" id="rider-banner-icon">📍</div>
        <div>
            <div id="rider-banner-title" class="text-xs font-bold text-orange-500 uppercase tracking-[0.5px]">Secure Line</div>
            <div id="rider-banner-subtitle" class="text-xs text-slate-500 font-medium">Mission in progress</div>
        </div>
    </div>

    <!-- Chat Body -->
    <div class="flex-1 overflow-y-auto px-4 py-5 bg-slate-50 flex flex-col gap-1 [&::-webkit-scrollbar]:hidden" id="rider-chat-body">
        <div class="text-center py-2 my-2">
            <span class="inline-block text-[11px] font-semibold text-slate-400 bg-white px-4 py-1.5 rounded-full border border-slate-100">Mission accepted. Securing line...</span>
        </div>
    </div>

    <!-- Chat Input -->
    <div class="px-4 py-3 bg-white border-t border-slate-100 shrink-0 pb-[max(12px,env(safe-area-inset-bottom))]">
        <div id="mission-action-buttons" class="flex gap-2 mb-3 hidden">
            <button id="cancel-mission-btn" onclick="showCancelConfirm()" class="flex-1 bg-rose-500 text-white py-3.5 rounded-[24px] text-[10px] font-black uppercase tracking-[0.15em] transition-all hover:bg-rose-600 active:scale-95 shadow-lg shadow-rose-100">✕ CANCEL</button>
            <button id="order-place-btn" onclick="showFormalizeModal()" class="flex-[2] bg-slate-900 text-white py-3.5 rounded-[24px] text-[10px] font-black uppercase tracking-[0.2em] animate-urgent transition-all hover:bg-slate-800 active:scale-95 shadow-lg">FORMALIZE ORDER MISSION</button>
        </div>
        <div class="flex items-end gap-2">
            <button onclick="shareRiderLiveLocation()" class="w-[46px] h-[46px] rounded-full bg-slate-100 text-slate-500 flex items-center justify-center shrink-0 hover:bg-slate-200 active:scale-95 transition-all" title="Share Location">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                </svg>
            </button>
            <input type="text" id="rider-chat-input" class="flex-1 bg-slate-50 border-[1.5px] border-slate-200 rounded-[24px] px-5 py-3 font-sans text-sm font-medium text-slate-900 outline-none transition-colors max-h-[120px] min-h-[46px] resize-none placeholder:text-slate-400 focus:border-orange-500 focus:bg-white" placeholder="Type a message..." autocomplete="off">
            <button onclick="sendRiderMessage()" class="w-[46px] h-[46px] rounded-full bg-orange-500 border-none text-white cursor-pointer flex items-center justify-center transition-all shrink-0 hover:bg-[#E85D2C] active:scale-90">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
<script>
    // WebSocket Configuration with proper protocol detection
    const wsHost = '{{ config('broadcasting.connections.reverb.client_options.host') ?? config('broadcasting.connections.reverb.options.host') }}';
    const wsPort = '{{ config('broadcasting.connections.reverb.client_options.port') ?? config('broadcasting.connections.reverb.options.port') }}';
    
    // Determine if we should use TLS based on the current page protocol or host
    const isSecure = window.location.protocol === 'https:' || wsHost.includes('railway.app');
    const port = wsPort || (isSecure ? 443 : 8080);
    
    // Connection state management
    let connectionState = 'connecting';
    let reconnectAttempts = 0;
    const maxReconnectAttempts = 10;
    const reconnectDelay = 2000;

    const echo = new Echo({
        broadcaster: 'reverb',
        key: '{{ config('broadcasting.connections.reverb.key') }}',
        wsHost: wsHost,
        wsPort: isSecure ? 443 : port,
        wssPort: isSecure ? 443 : port,
        forceTLS: isSecure,
        enabledTransports: isSecure ? ['wss'] : ['ws', 'wss'],
        activityTimeout: 30000,
        pongTimeout: 10000,
    });

    // Connection status monitoring
    function updateConnectionStatus(status, message) {
        connectionState = status;
        console.log(`[Rider WebSocket] ${status}: ${message}`);
        
        // Dispatch custom event for UI updates
        window.dispatchEvent(new CustomEvent('rider-websocket-status', { 
            detail: { status, message } 
        }));
    }

    // Handle connection errors and reconnection
    function handleConnectionError(error) {
        console.error('[Rider WebSocket] Connection error:', error);
        updateConnectionStatus('error', 'Connection failed, attempting to reconnect...');
        
        if (reconnectAttempts < maxReconnectAttempts) {
            reconnectAttempts++;
            const delay = Math.min(reconnectDelay * Math.pow(1.5, reconnectAttempts - 1), 30000);
            
            console.log(`[Rider WebSocket] Reconnecting in ${delay}ms (attempt ${reconnectAttempts}/${maxReconnectAttempts})`);
            
            setTimeout(() => {
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
    updateConnectionStatus('connecting', `Connecting to ${wsHost}:${port} (TLS: ${isSecure})`);

    // Safe channel subscription with error handling
    function safeChannelSubscribe(channelName, eventName, callback) {
        try {
            const channel = echo.channel(channelName);
            channel.listen(eventName, callback);
            
            channel.error((error) => {
                console.error(`[Rider WebSocket] Channel ${channelName} error:`, error);
            });
            
            return channel;
        } catch (error) {
            console.error(`[Rider WebSocket] Failed to subscribe to ${channelName}:`, error);
            return null;
        }
    }

    const riderId = {{ $rider->id }};
    let activeClientId = null;
    let currentOrderId = null;
    let currentServiceType = 'order';

    // Restore missions on load
    const myMissions = @json($myMissions);
    
    window.onload = () => {
        initRiderLocation();
        if (myMissions.length > 0) {
            const mission = myMissions[0];
            activeClientId = mission.client_id;
            currentOrderId = mission.id;
            document.getElementById('chat-client-name').innerText = mission.client.name;
            
            const chatBtn = document.getElementById('order-place-btn');
            const actionBtns = document.getElementById('mission-action-buttons');
            const cancelBtn = document.getElementById('cancel-mission-btn');
            if (mission.status === 'mission_accepted') {
                openRiderChat(mission.client.name);
                currentServiceType = mission.service_type || 'order';
                chatBtn.innerText = currentServiceType === 'pahatod' ? 'FORMALIZE SERVICE' : 'FORMALIZE ORDER MISSION';
                chatBtn.onclick = showFormalizeModal;
                cancelBtn.classList.remove('hidden');
                actionBtns.classList.remove('hidden');
            } else {
                currentServiceType = mission.service_type || 'order';
                // For formalized orders, show COMPLETE DELIVERY button
                chatBtn.innerText = currentServiceType === 'pahatod' ? 'DROP OFF COMPLETE' : 'COMPLETE DELIVERY';
                chatBtn.onclick = completeDeliveryFromChat;
                cancelBtn.classList.add('hidden');
                actionBtns.classList.remove('hidden');
                document.getElementById('chat-head').classList.remove('hidden');
            }
        }
    };

    safeChannelSubscribe('rider.' + riderId, '.rider.ordered', (data) => addRequestToUI(data));
    
    safeChannelSubscribe('rider.' + riderId, '.rider.cancelled', (data) => {
        const card = document.getElementById('req-' + data.clientId);
        if (card) {
            card.classList.remove('animate-urgent');
            card.innerHTML = '<p class="text-[10px] font-black text-rose-500 uppercase">Mission Expired</p>';
            setTimeout(() => { card.remove(); checkEmptyRequests(); }, 2000);
        }
    });

    function addRequestToUI(data) {
        const list = document.getElementById('live-requests-list');
        const emptyState = list.querySelector('.bg-slate-900');
        if (emptyState && emptyState.querySelector('.animate-pulse')) emptyState.remove();
        
        if (document.getElementById('req-' + data.clientId)) return;

        let routeHtml = '';
        if (data.serviceType === 'pahatod' && data.pickup && data.dropoff) {
            routeHtml = `
                <div class="space-y-2 mb-4 bg-slate-800/50 p-4 rounded-3xl border border-slate-700">
                    <div class="flex items-center gap-2">
                        <span class="text-xs">🏠</span>
                        <p class="text-[10px] text-slate-300 font-bold truncate">${data.pickup.name || data.pickup}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs">📍</span>
                        <p class="text-[10px] text-white font-black truncate">${data.dropoff.name || data.dropoff}</p>
                    </div>
                </div>
            `;
        }

        const item = document.createElement('div');
        item.className = 'bg-slate-900 rounded-[2.5rem] p-6 shadow-2xl animate-urgent border-2 border-orange-500';
        item.id = 'req-' + data.clientId;
        item.innerHTML = `
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-white font-black text-lg">NEW ${data.serviceType.toUpperCase()}</h3>
                    <p class="text-orange-400 text-[10px] font-black uppercase tracking-widest mt-1">Client: ${data.clientName}</p>
                </div>
                <div class="bg-orange-500 text-white text-[10px] font-black px-2 py-1 rounded-full px-2 py-1">LIVE</div>
            </div>
            ${routeHtml}
            <div class="flex gap-2">
                <button class="flex-1 py-4 rounded-2xl bg-slate-800 text-white text-[10px] font-black uppercase" onclick="respond(${data.clientId}, 'decline', '${data.clientName}')">IGNORE</button>
                <button class="flex-[2] py-4 rounded-2xl bg-orange-600 text-white text-[10px] font-black uppercase" onclick='respond(${data.clientId}, "accept", "${data.clientName}", "${data.serviceType}", ${JSON.stringify(data.pickup)}, ${JSON.stringify(data.dropoff)})'>ACCEPT MISSION</button>
            </div>
        `;
        list.prepend(item);
        if(window.navigator.vibrate) window.navigator.vibrate([200, 100, 200]);
    }

    function checkEmptyRequests() {
        const list = document.getElementById('live-requests-list');
        if (list.children.length === 0) {
            list.innerHTML = `
                <div class="bg-slate-900 rounded-[2.5rem] p-8 text-center border border-slate-800">
                    <div class="w-12 h-12 bg-slate-800 text-white rounded-2xl flex items-center justify-center mx-auto mb-4">📡</div>
                    <p class="text-white font-black text-sm uppercase tracking-widest">Scanning Gingoog City</p>
                </div>
            `;
        }
    }

    function respond(clientId, decision, clientName, serviceType = 'order', pickup = null, dropoff = null) {
        const card = document.getElementById('req-' + clientId);
        if (card) card.querySelectorAll('button').forEach(b => b.disabled = true);

        fetch(`/rider/clients/${clientId}/respond`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ decision, service_type: serviceType, pickup, dropoff })
        }).then(r => r.json()).then(data => {
            if (card) card.remove();
            if (decision === 'accept') {
                activeClientId = clientId;
                currentOrderId = data.order ? data.order.id : null;
                currentServiceType = serviceType;
                
                // Add to myMissions for chat banner context
                if (data.order) {
                    data.order.client = { name: clientName };
                    myMissions.push(data.order);
                }
                
                openRiderChat(clientName);
                const chatBtn = document.getElementById('order-place-btn');
                const actionBtns = document.getElementById('mission-action-buttons');
                const cancelBtn = document.getElementById('cancel-mission-btn');
                chatBtn.innerText = currentServiceType === 'pahatod' ? 'FORMALIZE SERVICE' : 'FORMALIZE ORDER MISSION';
                cancelBtn.classList.remove('hidden');
                actionBtns.classList.remove('hidden');
                // Show chat head if closed later
                document.getElementById('chat-head').classList.remove('hidden');
            }
            checkEmptyRequests();
        });
    }

    safeChannelSubscribe('chat.rider.' + riderId, '.message.sent', (data) => {
        if (data.senderType === 'client' && activeClientId == data.senderId) {
            appendMessage(data.message, 'client', data.type, data.locationData);
            // Auto-open if window is closed
            if (document.getElementById('rider-chat-window').classList.contains('hidden')) {
                document.getElementById('rider-chat-window').classList.replace('hidden', 'flex');
                document.getElementById('chat-head').classList.add('hidden');
            }
        }
    });

    function openRiderChat(clientName) {
        document.getElementById('chat-client-name').innerText = clientName;
        document.getElementById('rider-chat-window').classList.replace('hidden', 'flex');
        document.getElementById('chat-head').classList.add('hidden'); // Hide chat head when window is open

        // Update Banner based on current mission if available
        const bannerTitle = document.getElementById('rider-banner-title');
        const bannerSub = document.getElementById('rider-banner-subtitle');
        const bannerIcon = document.getElementById('rider-banner-icon');

        const mission = myMissions.find(m => m.client_id == activeClientId);
        if (mission && (mission.service_type === 'pahatod' || mission.type === 'pahatod')) {
             bannerIcon.innerText = '🏍️';
             bannerTitle.innerText = 'Pahatod Ride';
             bannerSub.innerText = `${mission.pickup_address} → ${mission.delivery_address}`;
        } else {
             bannerIcon.innerText = '📍';
             bannerTitle.innerText = 'Secure Line';
             bannerSub.innerText = 'Mission in progress';
        }

        // Load Chat History
        const chatBody = document.getElementById('rider-chat-body');
        chatBody.innerHTML = '<div class="text-center py-8"><div class="animate-spin inline-block w-6 h-6 border-[3px] border-current border-t-transparent text-orange-600 rounded-full" role="status"></div><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-2">Loading History</p></div>';

        fetch(`/chat/history?client_id=${activeClientId}&rider_id=${riderId}`)
            .then(r => r.json())
            .then(messages => {
                chatBody.innerHTML = '';
                if (messages.length === 0) {
                    chatBody.innerHTML = '<p class="text-center text-[10px] text-slate-400 font-bold uppercase tracking-widest py-8">Start of your mission conversation</p>';
                }
                messages.forEach(msg => {
                    appendMessage(msg.message, msg.sender_type, msg.type, msg.location_data);
                });
            });

        // Listen for real-time tracking from this specific client
        safeChannelSubscribe('client.location.' + activeClientId, '.client.location.updated', (data) => {
            const mapImg = document.getElementById(`map-client-${activeClientId}`);
            const mapLink = document.getElementById(`link-client-${activeClientId}`);
            if (mapImg) {
                mapImg.src = `https://static-maps.yandex.ru/1.x/?lang=en_US&ll=${data.lng},${data.lat}&z=16&l=map&size=300,150&pt=${data.lng},${data.lat},pm2rdm`;
            }
            if (mapLink) {
                mapLink.href = `https://www.google.com/maps?q=${data.lat},${data.lng}`;
            }
        });
    }

    function appendMessage(text, type, msgType = 'text', locationData = null) {
        const body = document.getElementById('rider-chat-body');
        const group = document.createElement('div');
        group.className = 'flex flex-col mb-2 ' + (type === 'rider' ? 'items-end' : 'items-start');

        const bubble = document.createElement('div');
        bubble.className = 'max-w-[75%] px-4 py-3 text-[14px] font-medium leading-[1.45] break-words relative whitespace-pre-line ' + 
            (type === 'rider' 
                ? 'bg-orange-500 text-white rounded-[20px] rounded-tr-[4px]' 
                : 'bg-white text-slate-900 rounded-[20px] rounded-tl-[4px] border border-slate-200');
        
        if (msgType === 'location') {
            const isMe = type === 'rider';
            const entityId = isMe ? riderId : (activeClientId || 'unknown');
            const entityType = isMe ? 'rider' : 'client';
            
            bubble.innerHTML = `
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-2">
                        <span>📍</span>
                        <span class="font-bold">Live Location Shared</span>
                    </div>
                    <div class="w-full aspect-video bg-slate-100 rounded-xl overflow-hidden relative">
                        <img id="map-${entityType}-${entityId}" src="https://static-maps.yandex.ru/1.x/?lang=en_US&ll=${locationData.lng},${locationData.lat}&z=16&l=map&size=300,150&pt=${locationData.lng},${locationData.lat},pm2rdm" class="w-full h-full object-cover">
                    </div>
                    <a id="link-${entityType}-${entityId}" href="https://www.google.com/maps?q=${locationData.lat},${locationData.lng}" target="_blank" class="text-[10px] font-black uppercase tracking-widest ${isMe ? 'text-white/80' : 'text-slate-400'} underline">View on Google Maps</a>
                </div>
            `;
        } else {
            bubble.innerText = text;
        }

        const time = document.createElement('div');
        time.className = 'text-[10px] text-slate-400 mt-1 px-1 ' + (type === 'rider' ? 'text-right' : '');
        const now = new Date();
        time.innerText = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');

        group.appendChild(bubble);
        group.appendChild(time);
        body.appendChild(group);
        body.scrollTop = body.scrollHeight;
    }

    function shareRiderLiveLocation() {
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser');
            return;
        }

        navigator.geolocation.getCurrentPosition(position => {
            const { latitude, longitude } = position.coords;
            const text = "📍 Live Location Shared";
            
            fetch('/chat/send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ 
                    sender_id: riderId, 
                    receiver_id: activeClientId, 
                    message: text, 
                    sender_type: 'rider',
                    type: 'location',
                    location_data: { lat: latitude, lng: longitude },
                    order_id: currentOrderId
                })
            }).then(r => r.json()).then(data => {
                appendMessage(text, 'rider', 'location', { lat: latitude, lng: longitude });
            });
        }, err => {
            alert('Failed to get location: ' + err.message);
        });
    }

    function closeRiderChat() { 
        document.getElementById('rider-chat-window').classList.replace('flex', 'hidden'); 
        if (activeClientId) {
            document.getElementById('chat-head').classList.remove('hidden');
        }
    }

    function sendRiderMessage() {
        const input = document.getElementById('rider-chat-input');
        const text = input.value.trim();
        if (!text || !activeClientId) return;
        
        fetch('/chat/send', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ 
                sender_id: riderId, 
                receiver_id: activeClientId, 
                message: text, 
                sender_type: 'rider',
                order_id: currentOrderId
            })
        });
        appendMessage(text, 'rider');
        input.value = '';
    }

    function showFormalizeModal() {
        const modalBtn = document.querySelector('#formalize-modal button[onclick="placeOrder()"]');
        modalBtn.innerText = currentServiceType === 'pahatod' ? 'FORMALIZE SERVICE' : 'CONFIRM & SEND TO CLIENT';
        
        // Pre-fill details from last client message or mission details
        const bubbles = document.querySelectorAll('#rider-chat-body .bg-white');
        const lastMsg = bubbles.length > 0 ? bubbles[bubbles.length - 1].innerText : '';
        document.getElementById('formalize-details').value = lastMsg || (currentServiceType === 'pahatod' ? 'Pahatod Ride Service' : 'Express Pasugo Delivery');

        document.getElementById('formalize-modal').classList.replace('hidden', 'flex');
    }

    function closeFormalizeModal() {
        document.getElementById('formalize-modal').classList.replace('flex', 'hidden');
    }

    function completeDeliveryFromChat() {
        if (!currentOrderId) {
            console.error('No active order ID found to complete.');
            return;
        }
        const btn = document.getElementById('order-place-btn');
        btn.disabled = true;
        btn.innerText = 'COMPLETING...';

        fetch(`/rider/order/${currentOrderId}/status`, {
            method: 'PATCH',
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status: 'delivered' })
        }).then(r => r.json()).then(data => {
            btn.innerText = 'DELIVERED ✅';
            setTimeout(() => {
                location.reload();
            }, 1000);
        }).catch(err => {
            console.error('Completion Error:', err);
            btn.disabled = false;
            btn.innerText = 'COMPLETE DELIVERY';
        });
    }

    function placeOrder() {
        const amount = document.getElementById('formalize-amount').value;
        const serviceFee = document.getElementById('formalize-service-fee').value;
        const details = document.getElementById('formalize-details').value.trim();

        if (!amount || !serviceFee) {
            alert('Please enter both total amount and service fee');
            return;
        }
        if (!details) {
            alert('Please enter mission details');
            return;
        }

        const btn = document.getElementById('order-place-btn');
        const modalBtn = document.querySelector('#formalize-modal button[onclick="placeOrder()"]');
        
        if (btn.disabled) return;
        btn.disabled = true;
        modalBtn.disabled = true;
        modalBtn.innerText = 'PROCESSING...';

        fetch('{{ route('rider.order.place_from_chat') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ 
                client_id: activeClientId, 
                details, 
                type: currentServiceType,
                amount: amount,
                service_fee: serviceFee
            })
        })
.then(r => r.json()).then(data => {
            currentOrderId = data.order.id;
            btn.disabled = false;
            btn.innerText = currentServiceType === 'pahatod' ? 'DROP OFF COMPLETE' : 'COMPLETE DELIVERY';
            btn.onclick = completeDeliveryFromChat;
            // Hide cancel button after formalizing (order is now committed)
            document.getElementById('cancel-mission-btn').classList.add('hidden');
            closeFormalizeModal();
            
            // Optionally notify client that we are starting delivery
            appendMessage('Amount confirmed! I am starting the mission now.', 'rider');
        });
    }

    function updateLocationOnServer(lat, lng) {
        // Use secure URL to prevent Mixed Content errors
        const updateUrl = '{{ secure_url(route('rider.location.update', [], false)) }}';
        
        fetch(updateUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ lat, lng })
        }).catch(error => {
            console.error('[Location Update] Failed to update location:', error);
        });
    }

    async function initRiderLocation() {
        if (window.Capacitor && window.Capacitor.isNativePlatform && window.Capacitor.isNativePlatform()) {
            try {
                const Geolocation = window.Capacitor.Plugins.Geolocation;
                if (Geolocation) {
                    const permResult = await Geolocation.requestPermissions();
                    console.log('[Rider Geo] Permission result:', permResult);

                    await Geolocation.watchPosition(
                        { enableHighAccuracy: true, timeout: 15000, maximumAge: 5000 },
                        (position, err) => {
                            if (err) {
                                console.error('[Rider Geo] Watch error:', err);
                                return;
                            }
                            if (position) {
                                updateLocationOnServer(position.coords.latitude, position.coords.longitude);
                            }
                        }
                    );
                    return; // Native plugin active, don't fall through
                }
            } catch (e) {
                console.warn('[Rider Geo] Native GPS fallback to browser:', e.message || e);
            }
        }

        // Browser fallback
        if (navigator.geolocation) {
            navigator.geolocation.watchPosition(
                p => updateLocationOnServer(p.coords.latitude, p.coords.longitude),
                err => console.error('[Rider Geo] Browser geolocation error:', err.message),
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 5000 }
            );
        } else {
            console.warn('[Rider Geo] Geolocation not supported');
        }
    }

    function simulateLocation() {
        updateLocationOnServer(8.8258 + (Math.random()-0.5)*0.01, 125.0827 + (Math.random()-0.5)*0.01);
        alert('GPS SIGNAL SIMULATED');
    }
    function showCancelConfirm() {
        document.getElementById('cancel-confirm-modal').classList.replace('hidden', 'flex');
    }

    function closeCancelConfirm() {
        document.getElementById('cancel-confirm-modal').classList.replace('flex', 'hidden');
    }

    function cancelMissionFromChat() {
        if (!currentOrderId) {
            console.error('No active order ID found to cancel.');
            return;
        }

        const reasonSelect = document.getElementById('cancel-reason');
        const reason = reasonSelect.value || 'No reason provided';

        const confirmBtn = document.querySelector('#cancel-confirm-modal .bg-rose-500');
        confirmBtn.disabled = true;
        confirmBtn.innerText = 'CANCELLING...';

        fetch(`/rider/order/${currentOrderId}/cancel-mission`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ reason })
        }).then(r => r.json()).then(data => {
            closeCancelConfirm();
            appendMessage('❌ Mission has been cancelled. Reason: ' + reason, 'rider');
            
            // Reset state
            setTimeout(() => {
                location.reload();
            }, 1500);
        }).catch(err => {
            console.error('Cancel Error:', err);
            confirmBtn.disabled = false;
            confirmBtn.innerText = 'YES, CANCEL MISSION';
            alert('Failed to cancel mission. Please try again.');
        });
    }
</script>

<!-- Chat Head (Chat Bubble) -->
<div id="chat-head" class="hidden fixed bottom-24 right-6 w-14 h-14 bg-orange-600 rounded-full shadow-2xl flex items-center justify-center cursor-pointer z-[90] animate-bounce" onclick="openRiderChat(document.getElementById('chat-client-name').innerText)">
    <span class="text-2xl">💬</span>
    <div class="absolute -top-1 -right-1 w-5 h-5 bg-rose-500 rounded-full border-2 border-white flex items-center justify-center">
        <span class="text-[10px] text-white font-black">1</span>
    </div>
</div>
</div>

<!-- Formalize Modal -->
<div id="formalize-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[110] items-center justify-center px-6">
    <div class="bg-white rounded-[2.5rem] w-full max-w-md p-8 shadow-2xl animate-in fade-in zoom-in duration-300">
        <div class="text-4xl mb-4 text-center">💰</div>
        <h2 class="text-xl font-black text-slate-900 text-center mb-2">Finalize Amount</h2>
        <p class="text-slate-500 text-xs font-medium text-center mb-8 uppercase tracking-widest">Enter total cost to formalize mission</p>
        
        <div class="mb-4">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 mb-2 block">Service Fee</label>
            <div class="relative">
                <span class="absolute left-6 top-1/2 -translate-y-1/2 text-slate-400 font-black text-lg">₱</span>
                <input type="number" id="formalize-service-fee" step="0.01" 
                    class="w-full bg-slate-50 border-none rounded-3xl pl-12 pr-8 py-4 text-xl font-black focus:ring-2 focus:ring-orange-500 transition-all" 
                    placeholder="0.00">
            </div>
        </div>

        <div class="mb-4">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 mb-2 block">Total Cost (Items + Fee)</label>
            <div class="relative">
                <span class="absolute left-6 top-1/2 -translate-y-1/2 text-slate-400 font-black text-lg">₱</span>
                <input type="number" id="formalize-amount" step="0.01" 
                    class="w-full bg-slate-50 border-none rounded-3xl pl-12 pr-8 py-4 text-xl font-black focus:ring-2 focus:ring-orange-500 transition-all" 
                    placeholder="0.00">
            </div>
        </div>

        <div class="mb-6">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 mb-2 block">Mission Details</label>
            <textarea id="formalize-details" rows="3" 
                class="w-full bg-slate-50 border-none rounded-3xl px-6 py-4 text-sm font-bold focus:ring-2 focus:ring-orange-500 transition-all resize-none" 
                placeholder="What are you delivering or what route?"></textarea>
        </div>

        <div class="flex flex-col gap-3">
            <button onclick="placeOrder()" class="w-full bg-slate-900 text-white py-4 rounded-3xl font-black text-xs uppercase tracking-[0.2em] shadow-lg shadow-slate-200 hover:bg-slate-800 transition-all active:scale-95">CONFIRM & SEND TO CLIENT</button>
            <button onclick="closeFormalizeModal()" class="w-full py-4 rounded-3xl font-black text-[10px] text-slate-400 uppercase tracking-widest transition-all hover:bg-slate-50">Cancel</button>
        </div>
    </div>
</div>

<!-- Cancel Confirmation Modal -->
<div id="cancel-confirm-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[120] items-center justify-center px-6">
    <div class="bg-white rounded-[2.5rem] w-full max-w-md p-8 shadow-2xl">
        <div class="text-4xl mb-4 text-center">⚠️</div>
        <h2 class="text-xl font-black text-slate-900 text-center mb-2">Cancel Mission?</h2>
        <p class="text-slate-500 text-xs font-medium text-center mb-6 uppercase tracking-widest">This will notify the client and end the conversation</p>
        
        <div class="mb-6">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 mb-2 block">Reason for Cancellation</label>
            <select id="cancel-reason" class="w-full bg-slate-50 border-none rounded-3xl px-6 py-4 text-sm font-bold focus:ring-2 focus:ring-rose-500 transition-all appearance-none">
                <option value="Client is unresponsive">Client is unresponsive</option>
                <option value="Unable to fulfill the request">Unable to fulfill the request</option>
                <option value="Client requested cancellation">Client requested cancellation</option>
                <option value="Location too far">Location too far</option>
                <option value="Emergency / Personal reason">Emergency / Personal reason</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <div class="flex flex-col gap-3">
            <button onclick="cancelMissionFromChat()" class="w-full bg-rose-500 text-white py-4 rounded-3xl font-black text-xs uppercase tracking-[0.2em] shadow-lg shadow-rose-100 hover:bg-rose-600 transition-all active:scale-95">YES, CANCEL MISSION</button>
            <button onclick="closeCancelConfirm()" class="w-full py-4 rounded-3xl font-black text-[10px] text-slate-400 uppercase tracking-widest transition-all hover:bg-slate-50">Go Back</button>
        </div>
    </div>
</div>
@endsection
