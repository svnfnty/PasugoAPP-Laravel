@extends('layouts.app')

@section('content')
<style>
    .client-card { @apply bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-100 mb-4; }
    .client-stat-pill { @apply flex-1 p-5 rounded-3xl text-center border border-slate-100; }
    .order-item { @apply flex justify-between items-center py-4 border-b border-slate-50 last:border-none; }
</style>

<!-- Header Section -->
<div class="mb-10 px-2">
    <h1 class="text-3xl font-black tracking-tighter text-slate-900 leading-tight">My Profile</h1>
    <p class="text-slate-400 font-bold text-[10px] uppercase tracking-[0.2em] mt-2">Verified PasugoAPP Client</p>
</div>

<!-- Quick Stats -->
<div class="flex gap-3 mb-8">
    <div class="client-stat-pill bg-orange-50 border-orange-100">
        <div class="text-[10px] font-black text-orange-400 uppercase tracking-widest mb-1">Spent</div>
        <div class="text-xl font-black tracking-tighter text-orange-600">₱{{ number_format($totalSpent, 0) }}</div>
    </div>
    <div class="client-stat-pill bg-white">
        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Orders</div>
        <div class="text-xl font-black tracking-tighter text-slate-800">{{ $orderCount }}</div>
    </div>
</div>

@php
    $hasActiveMission = $orders->whereIn('status', ['pending', 'mission_accepted', 'accepted', 'picked_up'])->first();
@endphp

<!-- Main Action -->
<div class="mb-12">
    @if($hasActiveMission)
        <div class="block w-full bg-slate-50 rounded-[2.5rem] p-8 text-center border border-slate-200">
            <div class="text-4xl mb-4">🛡️</div>
            <h2 class="text-xl font-black text-slate-400 tracking-tight">Active Duty</h2>
            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mt-2">You have an ongoing mission. Please complete it first.</p>
        </div>
    @else
        <a href="{{ route('client.riders.map') }}" class="block w-full group relative overflow-hidden bg-slate-900 rounded-[2.5rem] p-8 text-center shadow-2xl transition-all active:scale-[0.98]">
            <div class="absolute inset-0 bg-gradient-to-tr from-orange-600/20 to-transparent opacity-50 group-hover:opacity-100 transition-opacity"></div>
            <div class="relative z-10">
                <div class="text-4xl mb-4">🚀</div>
                <h2 class="text-xl font-black text-white tracking-tight">Express PasugoAPP</h2>
                <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mt-2">Choose a rider & start ordering</p>
            </div>
        </a>
    @endif
</div>

<!-- Recent Orders -->
<div class="mb-10">
    <div class="flex items-center justify-between mb-6 px-2">
        <h2 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em]">Recent History</h2>
        <span class="text-[10px] font-black text-emerald-500 bg-emerald-50 px-3 py-1 rounded-full uppercase">Live Updates</span>
    </div>

    @forelse($orders as $order)
        <div class="client-card">
            <div class="flex justify-between items-start mb-4">
                <div class="flex flex-col">
                    <span class="text-[10px] font-black text-slate-300 uppercase">#{{ $order->id }} • {{ $order->created_at->format('M d') }}</span>
                    <h3 class="font-black text-slate-900 text-lg mt-1 truncate max-w-[200px]">{{ $order->details ?: 'Express PasugoAPP' }}</h3>
                </div>
                <div class="text-right">
                    <div class="font-black text-slate-900 tracking-tighter">₱{{ number_format($order->total_amount, 0) }}</div>
                    <span class="text-[9px] font-black uppercase px-2 py-1 rounded-full mt-1 inline-block
                        @if($order->status == 'delivered') bg-emerald-100 text-emerald-700 
                        @elseif($order->status == 'cancelled') bg-rose-100 text-rose-700 
                        @else bg-blue-100 text-blue-700 @endif">
                        {{ str_replace('_', ' ', $order->status) }}
                    </span>
                </div>
            </div>
            
            <div class="flex items-center gap-3 pt-4 border-t border-slate-50">
                <div class="w-2 h-2 rounded-full bg-slate-200"></div>
                <p class="text-[11px] font-bold text-slate-400 truncate">{{ $order->pickup_address }}</p>
                <span class="text-slate-200">→</span>
                <p class="text-[11px] font-black text-slate-900 truncate">{{ $order->delivery_address }}</p>
            </div>
        </div>
    @empty
        <div class="p-16 text-center bg-white rounded-[2.5rem] border border-dashed border-slate-200">
            <div class="text-5xl mb-6">🏜️</div>
            <h3 class="text-xl font-black text-slate-900 mb-2">History is empty</h3>
            <p class="text-slate-400 text-sm font-medium mb-8 leading-relaxed px-4">Ready to experience the fastest delivery in Gingoog City?</p>
            <a href="{{ route('client.riders.map') }}" class="inline-block bg-slate-100 text-slate-900 font-black py-4 px-8 rounded-3xl text-xs uppercase tracking-widest hover:bg-slate-200 transition">
                Start Exploring
            </a>
        </div>
    @endforelse
</div>

<!-- Client Chat Window (Full Screen Mobile) -->
<div id="client-chat-window" class="hidden fixed inset-0 bg-white z-[100] flex-col shadow-2xl">
    <!-- Chat Header -->
    <div class="flex items-center gap-3 px-4 py-3 bg-white border-b border-slate-100 min-h-[64px] shrink-0 pt-[max(12px,env(safe-area-inset-top))]">
        <button onclick="closeClientChat()" class="w-9 h-9 border-none bg-transparent cursor-pointer flex items-center justify-center text-orange-500 rounded-full transition-colors shrink-0 hover:bg-slate-50">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/>
            </svg>
        </button>
        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-500 to-[#FF3D00] flex items-center justify-center text-lg shrink-0 text-white">🛵</div>
        <div class="flex-1 min-w-0">
            <div id="chat-rider-name" class="text-base font-bold text-slate-900 whitespace-nowrap overflow-hidden text-ellipsis">Rider</div>
            <div class="flex items-center gap-1.5 text-[11px] text-slate-400 font-medium">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span>Active now</span>
            </div>
        </div>
    </div>
    
    <!-- Chat Route Banner -->
    <div id="client-chat-banner" class="flex items-center gap-3 px-4 py-2.5 bg-slate-50 border-b border-slate-100 shrink-0">
        <div class="text-xl" id="client-banner-icon">📍</div>
        <div>
            <div id="client-banner-title" class="text-xs font-bold text-orange-500 uppercase tracking-[0.5px]">Secure Line</div>
            <div id="client-banner-subtitle" class="text-xs text-slate-500 font-medium">Mission in progress</div>
        </div>
    </div>

    <!-- Chat Body -->
    <div class="flex-1 overflow-y-auto px-4 py-5 bg-slate-50 flex flex-col gap-1 [&::-webkit-scrollbar]:hidden" id="client-chat-body">
        <div class="text-center py-2 my-2">
            <span class="inline-block text-[11px] font-semibold text-slate-400 bg-white px-4 py-1.5 rounded-full border border-slate-100">Rider connected. Negotiating details...</span>
        </div>
    </div>

    <!-- Chat Input -->
    <div class="px-4 py-3 bg-white border-t border-slate-100 shrink-0 pb-[max(12px,env(safe-area-inset-bottom))]">
        <div class="flex items-end gap-2">
            <button onclick="shareLiveLocation()" class="w-[46px] h-[46px] rounded-full bg-slate-100 text-slate-500 flex items-center justify-center shrink-0 hover:bg-slate-200 active:scale-95 transition-all" title="Share Location">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                </svg>
            </button>
            <input type="text" id="client-chat-input" class="flex-1 bg-slate-50 border-[1.5px] border-slate-200 rounded-[24px] px-5 py-3 font-sans text-sm font-medium text-slate-900 outline-none transition-colors max-h-[120px] min-h-[46px] resize-none placeholder:text-slate-400 focus:border-orange-500 focus:bg-white" placeholder="Type a message..." autocomplete="off">
            <button onclick="sendClientMessage()" class="w-[46px] h-[46px] rounded-full bg-orange-500 border-none text-white cursor-pointer flex items-center justify-center transition-all shrink-0 hover:bg-[#E85D2C] active:scale-90">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<!-- Chat Head (Chat Bubble) -->
<div id="client-chat-head" class="hidden fixed bottom-24 right-6 w-14 h-14 bg-orange-600 rounded-full shadow-2xl flex items-center justify-center cursor-pointer z-[90] animate-bounce" onclick="openClientChat(document.getElementById('chat-rider-name').innerText)">
    <span class="text-2xl">💬</span>
    <div class="absolute -top-1 -right-1 w-5 h-5 bg-rose-500 rounded-full border-2 border-white flex items-center justify-center">
        <span class="text-[10px] text-white font-black">1</span>
    </div>
</div>

<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
<script>
    const reverbHost = '{{ config('broadcasting.connections.reverb.options.host') }}'.replace(/^https?:\/\//, '');
    const echo = new Echo({
        broadcaster: 'reverb',
        key: '{{ config('broadcasting.connections.reverb.key') }}',
        wsHost: reverbHost,
        wsPort: '{{ config('broadcasting.connections.reverb.options.port') }}',
        wssPort: '{{ config('broadcasting.connections.reverb.options.port') }}',
        forceTLS: false,
        enabledTransports: ['ws', 'wss'],
    });

    const clientId = {{ auth()->guard('client')->id() }};
    let activeRiderId = null;

    // Restore mission on load
    const activeMission = @json($activeMission);
    
    window.onload = () => {
        if (activeMission) {
            activeRiderId = activeMission.rider_id;
            document.getElementById('chat-rider-name').innerText = activeMission.rider.name;
            
            if (activeMission.status === 'mission_accepted') {
                openClientChat(activeMission.rider.name);
            } else {
                document.getElementById('client-chat-head').classList.remove('hidden');
            }
        }
    };

    echo.channel('client.' + clientId)
        .listen('.rider.responded', (data) => {
            if (data.decision === 'accept') {
                activeRiderId = data.riderId;
                // Store mission data for ongoing context
                activeMission = { 
                    id: data.orderId, 
                    rider_id: data.riderId, 
                    rider: { name: data.riderName },
                    service_type: data.serviceType,
                    pickup_address: data.pickup ? (data.pickup.name || data.pickup) : '...',
                    delivery_address: data.dropoff ? (data.dropoff.name || data.dropoff) : '...'
                };
                openClientChat(data.riderName);
            }
        });

    echo.channel('chat.client.' + clientId)
        .listen('.message.sent', (data) => {
            if (data.senderType === 'rider' && activeRiderId == data.senderId) {
                appendClientMessage(data.message, 'rider', data.type, data.locationData);
                
                // Auto-open if closed
                if (document.getElementById('client-chat-window').classList.contains('hidden')) {
                    document.getElementById('client-chat-window').classList.replace('hidden', 'flex');
                    document.getElementById('client-chat-head').classList.add('hidden');
                }

                // If mission completed, reload to clear "Active Duty" status
                if (data.message.includes('🏁 MISSION COMPLETED')) {
                    setTimeout(() => {
                        location.reload();
                    }, 3000);
                }
            }
        });

    function openClientChat(riderName) {
        document.getElementById('chat-rider-name').innerText = riderName;
        document.getElementById('client-chat-window').classList.replace('hidden', 'flex');
        document.getElementById('client-chat-head').classList.add('hidden');

        // Update Banner
        const bannerTitle = document.getElementById('client-banner-title');
        const bannerSub = document.getElementById('client-banner-subtitle');
        const bannerIcon = document.getElementById('client-banner-icon');

        if (activeMission && (activeMission.service_type === 'pahatod' || activeMission.type === 'pahatod')) {
             bannerIcon.innerText = '🏍️';
             bannerTitle.innerText = 'Pahatod Ride';
             bannerSub.innerText = `${activeMission.pickup_address} → ${activeMission.delivery_address}`;
        } else {
             bannerIcon.innerText = '📍';
             bannerTitle.innerText = 'Secure Line';
             bannerSub.innerText = 'Mission in progress';
        }

        // Load History
        const chatBody = document.getElementById('client-chat-body');
        chatBody.innerHTML = '<div class="text-center py-8"><div class="animate-spin inline-block w-6 h-6 border-[3px] border-current border-t-transparent text-orange-600 rounded-full" role="status"></div><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-2">Loading History</p></div>';

        fetch(`/chat/history?client_id=${clientId}&rider_id=${activeRiderId}`)
            .then(r => r.json())
            .then(messages => {
                chatBody.innerHTML = '';
                if (messages.length === 0) {
                   chatBody.innerHTML = '<p class="text-center text-[10px] text-slate-400 font-bold uppercase tracking-widest py-8">Start of your mission conversation</p>';
                }
                messages.forEach(msg => {
                    appendClientMessage(msg.message, msg.sender_type, msg.type, msg.location_data);
                });
            });

        // Listen for real-time tracking from this specific rider
        echo.channel('riders')
            .listen('.rider.location.updated', (data) => {
                if (data.riderId == activeRiderId) {
                    const mapImg = document.getElementById(`map-rider-${activeRiderId}`);
                    const mapLink = document.getElementById(`link-rider-${activeRiderId}`);
                    if (mapImg) {
                        mapImg.src = `https://static-maps.yandex.ru/1.x/?lang=en_US&ll=${data.lng},${data.lat}&z=16&l=map&size=300,150&pt=${data.lng},${data.lat},pm2rdm`;
                    }
                    if (mapLink) {
                        mapLink.href = `https://www.google.com/maps?q=${data.lat},${data.lng}`;
                    }
                }
            });
    }

    function closeClientChat() {
        document.getElementById('client-chat-window').classList.replace('flex', 'hidden');
        if (activeRiderId) {
            document.getElementById('client-chat-head').classList.remove('hidden');
        }
    }

    function appendClientMessage(text, type, msgType = 'text', locationData = null) {
        const body = document.getElementById('client-chat-body');
        const group = document.createElement('div');
        group.className = 'flex flex-col mb-2 ' + (type === 'client' ? 'items-end' : 'items-start');

        const bubble = document.createElement('div');
        bubble.className = 'max-w-[75%] px-4 py-3 text-[14px] font-medium leading-[1.45] break-words relative whitespace-pre-line ' + 
            (type === 'client' 
                ? 'bg-orange-500 text-white rounded-[20px] rounded-tr-[4px]' 
                : 'bg-white text-slate-900 rounded-[20px] rounded-tl-[4px] border border-slate-200');
        
        if (msgType === 'location') {
            const isMe = type === 'client';
            const entityId = isMe ? clientId : (activeRiderId || 'unknown');
            const entityType = isMe ? 'client' : 'rider';

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
        time.className = 'text-[10px] text-slate-400 mt-1 px-1 ' + (type === 'client' ? 'text-right' : '');
        const now = new Date();
        time.innerText = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');

        group.appendChild(bubble);
        group.appendChild(time);
        body.appendChild(group);
        body.scrollTop = body.scrollHeight;
    }

    function shareLiveLocation() {
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
                    sender_id: clientId, 
                    receiver_id: activeRiderId, 
                    message: text, 
                    sender_type: 'client',
                    type: 'location',
                    location_data: { lat: latitude, lng: longitude },
                    order_id: activeMission ? activeMission.id : null
                })
            }).then(r => r.json()).then(data => {
                appendClientMessage(text, 'client', 'location', { lat: latitude, lng: longitude });
                startRealtimeTracking();
            });
        }, err => {
            alert('Failed to get location: ' + err.message);
        });
    }

    function startRealtimeTracking() {
        if (window.trackingInterval) clearInterval(window.trackingInterval);
        
        // Update every 10 seconds
        window.trackingInterval = setInterval(() => {
            navigator.geolocation.getCurrentPosition(position => {
                updateClientLocationOnServer(position.coords.latitude, position.coords.longitude);
            });
        }, 10000);
    }

    function updateClientLocationOnServer(lat, lng) {
        fetch('{{ route('client.location.update') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ lat, lng })
        });
    }

    function sendClientMessage() {
        const input = document.getElementById('client-chat-input');
        const text = input.value.trim();
        if (!text || !activeRiderId) return;
        
        fetch('/chat/send', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ 
                sender_id: clientId, 
                receiver_id: activeRiderId, 
                message: text, 
                sender_type: 'client',
                order_id: activeMission ? activeMission.id : null
            })
        });
        appendClientMessage(text, 'client');
        input.value = '';
    }
</script>
@endsection
