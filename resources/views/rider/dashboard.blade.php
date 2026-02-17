@extends('layouts.app')

@section('content')
<style>
    .mobile-card { @apply bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-100 mb-4; }
    .stat-pill { @apply flex-1 bg-slate-50 p-4 rounded-3xl text-center border border-slate-100; }
    .action-button { @apply w-full py-4 rounded-3xl font-black text-xs uppercase tracking-widest transition-all active:scale-95 shadow-lg; }
    
    #rider-chat-body { @apply bg-[#fcfdfe] space-y-2; }
    .msg-bubble { @apply max-w-[80%] px-5 py-3 rounded-[1.8rem] text-[14px] font-medium shadow-sm relative transition-all animate-in slide-in-from-bottom-2; }
    .msg-rider { @apply self-end bg-gradient-to-tr from-orange-600 to-red-500 text-white rounded-tr-none shadow-orange-100; }
    .msg-client { @apply self-start bg-white text-slate-700 rounded-tl-none border border-slate-100; }
    .msg-tag { @apply text-[8px] font-black uppercase tracking-[0.15em] mb-1 opacity-60; }
    .rider-tag { @apply text-right mr-3 text-orange-600; }
    .client-tag { @apply text-left ml-3 text-slate-400; }

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
            
            <h3 class="text-lg font-black leading-tight mb-4">{{ $order->details ?: 'Express Fetch' }}</h3>
            
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
                <form action="{{ route('rider.order.update', $order) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="picked_up">
                    <div class="relative mb-3">
                        <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 font-black">₱</span>
                        <input type="number" name="total_amount" step="0.01" required 
                            class="w-full bg-slate-50 border-none rounded-3xl pl-10 pr-6 py-4 text-sm font-black focus:ring-2 focus:ring-orange-500" 
                            placeholder="Final Cost">
                    </div>
                    <button type="submit" class="action-button bg-slate-900 text-white shadow-slate-200">VERIFY PICKUP</button>
                </form>
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
    <div class="p-6 border-b border-slate-100 flex items-center justify-between safe-top">
        <div class="flex items-center gap-3">
            <button onclick="closeRiderChat()" class="w-10 h-10 rounded-2xl bg-slate-100 flex items-center justify-center text-xl">←</button>
            <div>
                <strong id="chat-client-name" class="block leading-none text-lg">Client</strong>
                <span class="text-[10px] font-black uppercase text-orange-500 tracking-widest mt-1 inline-block">Secure Line</span>
            </div>
        </div>
    </div>
    
    <div class="flex-1 overflow-y-auto p-6 flex flex-col" id="rider-chat-body">
        <div class="msg-bubble msg-rider">Mission accepted. Connecting to client secure line...</div>
    </div>

    <div class="p-6 border-t border-slate-50 safe-bottom">
        <button id="order-place-btn" onclick="placeOrder()" class="w-full bg-slate-900 text-white py-4 rounded-3xl text-[10px] font-black uppercase tracking-[0.2em] mb-4 hidden animate-urgent">FORMALIZE ORDER MISSION</button>
        <div class="flex gap-2">
            <input type="text" id="rider-chat-input" class="flex-1 bg-slate-100 border-none rounded-3xl px-6 py-4 text-sm font-bold focus:ring-2 focus:ring-orange-500" placeholder="Type instructions...">
            <button onclick="sendRiderMessage()" class="w-14 h-14 bg-orange-600 text-white rounded-3xl flex items-center justify-center text-xl shadow-lg shadow-orange-100">➤</button>
        </div>
    </div>
</div>

<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
<script>
    const echo = new Echo({
        broadcaster: 'reverb',
        key: '{{ config('broadcasting.connections.reverb.key') }}',
        wsHost: '{{ config('broadcasting.connections.reverb.options.host') }}',
        wsPort: '{{ config('broadcasting.connections.reverb.options.port') }}',
        wssPort: '{{ config('broadcasting.connections.reverb.options.port') }}',
        forceTLS: false,
        enabledTransports: ['ws', 'wss'],
    });

    const riderId = {{ $rider->id }};

    echo.channel('rider.' + riderId)
        .listen('.rider.ordered', (data) => addRequestToUI(data))
        .listen('.rider.cancelled', (data) => {
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

        const item = document.createElement('div');
        item.className = 'bg-slate-900 rounded-[2.5rem] p-6 shadow-2xl animate-urgent border-2 border-orange-500';
        item.id = 'req-' + data.clientId;
        item.innerHTML = `
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-white font-black text-lg">NEW ${data.serviceType.toUpperCase()}</h3>
                    <p class="text-orange-400 text-[10px] font-black uppercase tracking-widest mt-1">Client: ${data.clientName}</p>
                </div>
                <div class="bg-orange-500 text-white text-[10px] font-black px-3 py-1 rounded-full px-2 py-1">LIVE</div>
            </div>
            <div class="flex gap-2">
                <button class="flex-1 py-4 rounded-2xl bg-slate-800 text-white text-[10px] font-black uppercase" onclick="respond(${data.clientId}, 'decline', '${data.clientName}')">IGNORE</button>
                <button class="flex-[2] py-4 rounded-2xl bg-orange-600 text-white text-[10px] font-black uppercase" onclick="respond(${data.clientId}, 'accept', '${data.clientName}', '${data.serviceType}')">ACCEPT MISSION</button>
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

    function respond(clientId, decision, clientName, serviceType = 'order') {
        const card = document.getElementById('req-' + clientId);
        if (card) card.querySelectorAll('button').forEach(b => b.disabled = true);

        fetch(`/rider/clients/${clientId}/respond`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ decision, service_type: serviceType })
        }).then(() => {
            if (card) card.remove();
            if (decision === 'accept') {
                activeClientId = clientId;
                currentServiceType = serviceType;
                openRiderChat(clientName);
                document.getElementById('order-place-btn').classList.remove('hidden');
            }
            checkEmptyRequests();
        });
    }

    let activeClientId = null;
    let currentServiceType = null;

    echo.channel('chat.rider.' + riderId)
        .listen('.message.sent', (data) => {
            if (data.senderType === 'client' && activeClientId == data.senderId) {
                appendMessage(data.message, 'client');
                document.getElementById('rider-chat-window').classList.replace('hidden', 'flex');
            }
        });

    function openRiderChat(clientName) {
        document.getElementById('chat-client-name').innerText = clientName;
        document.getElementById('rider-chat-window').classList.replace('hidden', 'flex');
        document.getElementById('rider-chat-body').innerHTML = '<div class="msg-bubble msg-rider">Mission accepted. Securing line...</div>';
    }

    function appendMessage(text, type) {
        const body = document.getElementById('rider-chat-body');
        const msgContainer = document.createElement('div');
        msgContainer.className = 'flex flex-col mb-2 ' + (type === 'rider' ? 'items-end' : 'items-start');
        
        const tag = document.createElement('span');
        tag.className = 'msg-tag ' + (type === 'rider' ? 'rider-tag' : 'client-tag');
        tag.innerText = type === 'rider' ? 'YOU' : 'CLIENT';

        const bubble = document.createElement('div');
        bubble.className = 'msg-bubble ' + (type === 'rider' ? 'msg-rider' : 'msg-client');
        bubble.innerText = text;
        
        msgContainer.appendChild(tag);
        msgContainer.appendChild(bubble);
        body.appendChild(msgContainer);
        body.scrollTop = body.scrollHeight;
    }

    function closeRiderChat() { document.getElementById('rider-chat-window').classList.replace('flex', 'hidden'); }

    function sendRiderMessage() {
        const input = document.getElementById('rider-chat-input');
        const text = input.value.trim();
        if (!text || !activeClientId) return;
        
        fetch('/chat/send', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ sender_id: riderId, receiver_id: activeClientId, message: text, sender_type: 'rider' })
        });
        appendMessage(text, 'rider');
        input.value = '';
    }

    function placeOrder() {
        const btn = document.getElementById('order-place-btn');
        if (btn.disabled) return;
        btn.disabled = true;
        btn.innerText = 'UPLOADING DATA...';

        const lastMessage = document.getElementById('rider-chat-body').lastElementChild;
        const details = (lastMessage && lastMessage.classList.contains('msg-client')) ? lastMessage.innerText : 'Service Entry';

        fetch('{{ route('rider.order.place_from_chat') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ client_id: activeClientId, details, type: currentServiceType })
        }).then(r => r.json()).then(data => {
            btn.innerText = 'MISSION FORMALIZED ✅';
            if (activeClientId) window.open(`https://www.google.com/maps/dir/?api=1&destination=8.8258,125.0827`, '_blank');
        });
    }

    function updateLocationOnServer(lat, lng) {
        fetch('{{ route('rider.location.update') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ lat, lng })
        });
    }

    function initRiderLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.watchPosition(p => updateLocationOnServer(p.coords.latitude, p.coords.longitude), 
            null, { enableHighAccuracy: true });
        }
    }

    function simulateLocation() {
        updateLocationOnServer(8.8258 + (Math.random()-0.5)*0.01, 125.0827 + (Math.random()-0.5)*0.01);
        alert('GPS SIGNAL SIMULATED');
    }

    window.onload = initRiderLocation;
</script>
@endsection
