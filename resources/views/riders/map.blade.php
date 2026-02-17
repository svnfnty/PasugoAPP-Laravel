<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=0">
    <title>FETCH GINGOOG - Live Map</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Outfit', sans-serif; background: #f8fafc; height: 100vh; overflow: hidden; display: flex; flex-direction: column; }
        #map { flex: 1; z-index: 10; }
        
        .bottom-panel {
            @apply fixed bottom-0 left-0 right-0 z-[1000] bg-white rounded-t-[3rem] shadow-2xl transition-all duration-500 transform translate-y-0;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
        }
        .drag-handle { @apply w-12 h-1.5 bg-slate-200 rounded-full mx-auto my-4 cursor-pointer; }
        
        .rider-card {
            @apply flex items-center gap-4 p-5 rounded-3xl border border-slate-50 mb-3 bg-slate-50/50 transition-all active:scale-95 cursor-pointer;
        }
        .rider-card.selected { @apply border-orange-500 bg-orange-50/50; }
        
        #client-chat-window {
            @apply fixed bottom-0 left-0 right-0 h-[60vh] md:h-[50vh] bg-white z-[2000] flex-col hidden rounded-t-[3rem] shadow-[0_-20px_50px_rgba(0,0,0,0.1)] transition-all duration-700 ease-in-out;
        }
        #client-chat-window.full-screen {
            @apply h-[100vh] rounded-none;
        }
        
        #chat-body { @apply bg-[#fcfdfe] space-y-2; }
        .msg-bubble { @apply max-w-[80%] px-5 py-3 rounded-[1.8rem] text-[14px] font-medium shadow-sm relative transition-all animate-in slide-in-from-bottom-2; }
        .msg-client { @apply self-end bg-gradient-to-tr from-orange-600 to-red-500 text-white rounded-tr-none shadow-orange-100; }
        .msg-rider { @apply self-start bg-white text-slate-700 rounded-tl-none border border-slate-100; }
        .msg-tag { @apply text-[8px] font-black uppercase tracking-[0.15em] mb-1 opacity-60; }
        .client-tag { @apply text-right mr-3 text-orange-600; }
        .rider-tag { @apply text-left ml-3 text-slate-400; }
        
        .leaflet-touch .leaflet-bar { border: none; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        .leaflet-touch .leaflet-control-zoom-in, .leaflet-touch .leaflet-control-zoom-out { border-radius: 12px !important; margin-bottom: 5px; }
        
        .pulse-marker {
            width: 20px;
            height: 20px;
            background: rgba(234, 88, 12, 0.3);
            border: 2px solid #ea580c;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(3); opacity: 0; }
        }
    </style>
</head>
<body>

    <!-- Header / Branding -->
    <div class="fixed top-0 left-0 right-0 z-[500] p-4 flex justify-between items-center pointer-events-none">
        <a href="{{ route('client.dashboard') }}" class="pointer-events-auto w-12 h-12 bg-white shadow-xl rounded-2xl flex items-center justify-center text-xl">←</a>
        <div class="bg-white/80 backdrop-blur-md px-6 py-3 rounded-full shadow-xl pointer-events-auto border border-white/50">
            <span class="text-xl font-black italic tracking-tighter text-transparent bg-clip-text bg-gradient-to-r from-orange-600 to-red-600">FETCH</span>
        </div>
        <div class="w-12 h-12 invisible"></div>
    </div>

    <div id="map"></div>

    <!-- Bottom Selection Panel -->
    <div class="bottom-panel translate-y-[10%]" id="rider-panel">
        <div class="drag-handle" onclick="togglePanel()"></div>
        <div class="px-8 pb-4 cursor-pointer" onclick="togglePanel()">
            <h2 class="text-2xl font-black tracking-tighter text-slate-900">Nearby Riders</h2>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Gingoog City Express Network</p>
        </div>
        <div class="flex-1 overflow-y-auto px-6 pb-10" id="rider-list">
            <div class="p-10 text-center">
                <p class="text-slate-400 font-bold text-[10px] uppercase tracking-[0.2em] animate-pulse">Scanning Gingoog Network...</p>
            </div>
        </div>
    </div>

    <!-- Chat Interface -->
    <div id="client-chat-window">
        <!-- Chat Header -->
        <div class="p-6 border-b border-slate-50 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button onclick="closeChat()" class="p-3 bg-slate-100 rounded-2xl">←</button>
                <div>
                    <strong id="chat-rider-name" class="block text-lg font-black tracking-tight">Rider Name</strong>
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Secure Connection</span>
                    </div>
                </div>
            </div>
            <div id="service-countdown" class="hidden px-3 py-1 bg-orange-50 text-orange-600 text-[10px] font-black rounded-full border border-orange-100">60s</div>
        </div>

        <!-- Chat Body -->
        <div class="flex-1 overflow-y-auto p-6 flex flex-col pt-10" id="chat-body">
            <!-- Messages here -->
        </div>

        <!-- Order Controls (Initial Actions) -->
        <div id="chat-controls" class="p-6 border-t border-slate-50 hidden">
            <p class="text-[10px] font-black text-slate-400 uppercase mb-4 text-center tracking-[0.2em]">Select Mission Type</p>
            <div class="flex gap-3">
                <button onclick="startService('food')" class="flex-1 bg-orange-600 text-white py-4 rounded-3xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-orange-100 transition-all hover:bg-orange-700">Order Food</button>
                <button onclick="startService('pasugo')" class="flex-1 bg-slate-900 text-white py-4 rounded-3xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-slate-200 transition-all hover:bg-black">Pasugo</button>
            </div>
        </div>

        <!-- Chat Input (Hidden until acceptance) -->
        <div id="chat-input-container" class="p-6 border-t border-slate-50 hidden transition-all duration-500">
            <div class="flex gap-2">
                <input type="text" id="chat-input" class="flex-1 bg-slate-100 border-none rounded-3xl px-6 py-4 text-sm font-bold focus:ring-2 focus:ring-orange-500" placeholder="Message rider...">
                <button onclick="sendMessage()" class="w-14 h-14 bg-slate-900 text-white rounded-3xl flex items-center justify-center shadow-lg shadow-slate-200">➤</button>
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

        // Map Initialization
        const streetLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '© OpenStreetMap'
        });

        const satelliteLayer = L.tileLayer('http://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
            attribution: '© Google Maps'
        });

        const map = L.map('map', { 
            zoomControl: false,
            layers: [satelliteLayer] // Set satellite as default
        }).setView([8.8258, 125.0827], 15);

        L.control.layers({
            "Satellite": satelliteLayer,
            "Street": streetLayer
        }, null, { position: 'topright' }).addTo(map);

        L.control.zoom({ position: 'bottomright' }).addTo(map);

        let riderMarkers = {};
        let selectedRiderId = null;
        let countdownInterval = null;

        // Landmarks
        const landmarks = [
            { name: "Jollibee Gingoog", pos: [8.8258, 125.0827], icon: "🍔" },
            { name: "Gaisano Gingoog", pos: [8.8298, 125.0827], icon: "🏬" }
        ];
        landmarks.forEach(l => {
            L.marker(l.pos, {
                icon: L.divIcon({
                    html: `<div class="bg-white p-2 rounded-xl shadow-lg border border-slate-100 text-sm">${l.icon}</div>`,
                    className: '', iconSize: [30, 30]
                })
            }).addTo(map).bindTooltip(l.name);
        });

        // Sync Riders
        echo.channel('riders').listen('.rider.location.updated', (data) => {
            if (data.status === 'offline') {
                if (riderMarkers[data.riderId]) {
                    map.removeLayer(riderMarkers[data.riderId].marker);
                    delete riderMarkers[data.riderId];
                    renderRiderList();
                }
                return;
            }

            const pos = [data.lat, data.lng];
            if (!riderMarkers[data.riderId]) {
                const marker = L.marker(pos, {
                    icon: L.divIcon({
                        html: `<div class="bg-white p-2 rounded-2xl shadow-xl flex items-center justify-center border-2 border-${data.status === 'available' ? 'emerald' : 'amber'}-500 transition-all font-black" style="width:40px;height:40px">🛵</div>`,
                        className: 'rider-marker', iconSize: [40, 40]
                    })
                }).addTo(map);
                
                marker.on('click', () => selectRider(data.riderId, data.name));
                riderMarkers[data.riderId] = { marker, data };
            } else {
                riderMarkers[data.riderId].marker.setLatLng(pos);
                riderMarkers[data.riderId].data = data;
            }
            renderRiderList();
        });

        function renderRiderList() {
            const list = document.getElementById('rider-list');
            const riders = Object.values(riderMarkers);
            if (riders.length === 0) {
                list.innerHTML = '<div class="p-10 text-center text-slate-400 font-bold uppercase text-[10px]">No active riders found</div>';
                return;
            }
            
            list.innerHTML = riders.map(r => `
                <div class="rider-card ${selectedRiderId == r.data.riderId ? 'selected' : ''}" onclick="selectRider(${r.data.riderId}, '${r.data.name}')">
                    <div class="h-12 w-12 rounded-2xl bg-slate-900 text-white flex items-center justify-center text-xl">🛵</div>
                    <div class="flex-1">
                        <div class="flex justify-between items-center mb-0.5">
                            <h4 class="font-black text-slate-900 tracking-tight">${r.data.name}</h4>
                            <span class="text-[8px] font-black uppercase text-emerald-500 bg-emerald-50 px-2 rounded-full tracking-tighter">${r.data.status}</span>
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">${r.data.bio || 'Fleet Member'}</p>
                    </div>
                </div>
            `).join('');
        }

        function selectRider(id, name) {
            selectedRiderId = id;
            document.getElementById('chat-rider-name').innerText = name;
            document.getElementById('client-chat-window').classList.replace('hidden', 'flex');
            document.getElementById('chat-controls').classList.remove('hidden');
            document.getElementById('chat-input-container').classList.add('hidden'); // Hide input initially
            renderRiderList();
        }

        function closeChat() {
            document.getElementById('client-chat-window').classList.replace('flex', 'hidden');
            document.getElementById('client-chat-window').classList.remove('full-screen');
            document.getElementById('rider-panel').classList.remove('hidden'); // Show list again
            clearInterval(countdownInterval);
            document.getElementById('service-countdown').classList.add('hidden');
        }

        function startService(type) {
            if (!selectedRiderId) return;
            document.getElementById('chat-controls').classList.add('hidden');
            
            const msg = type === 'food' ? "🍔 Sending Food Mission Request..." : "📦 Sending Pasugo Mission Request...";
            appendMessage(msg, 'client');
            appendMessage("Please wait for rider confirmation. Secure line will open shortly.", 'rider');
            
            fetch(`/client/riders/${selectedRiderId}/order`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ service_type: type })
            });

            // Timer
            const timerEl = document.getElementById('service-countdown');
            timerEl.classList.remove('hidden');
            let timeLeft = 60;
            timerEl.innerText = timeLeft + 's';
            
            clearInterval(countdownInterval);
            countdownInterval = setInterval(() => {
                timeLeft--;
                timerEl.innerText = timeLeft + 's';
                if (timeLeft <= 0) {
                    clearInterval(countdownInterval);
                    appendMessage("Mission Request Expired. Syncing status...", 'rider');
                    timerEl.classList.add('hidden');
                    
                    // Notify server to cancel the request
                    fetch(`/client/riders/${selectedRiderId}/cancel`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });
                }
            }, 1000);
        }

        echo.channel('client.{{ Auth::guard('client')->id() ?? 'guest' }}')
            .listen('.rider.responded', (data) => {
                if (data.decision === 'accept') {
                    clearInterval(countdownInterval);
                    document.getElementById('service-countdown').classList.add('hidden');
                    document.getElementById('chat-input-container').classList.remove('hidden'); // SHOW INPUT NOW
                    
                    // Go Full Screen and Hide the List
                    document.getElementById('client-chat-window').classList.add('full-screen');
                    document.getElementById('rider-panel').classList.add('hidden');
                    
                    appendMessage("CONNECTED. Establishing secure protocol...", 'rider');
                } else {
                    appendMessage("RIDER UNAVAILABLE. Mission Cancelled.", 'rider');
                }
            })
            .listen('.rider.cancelled', (data) => {
                 appendMessage("MISSION CANCELLED BY SERVER.", 'rider');
            });

        echo.channel('chat.client.{{ Auth::guard('client')->id() ?? 'guest' }}')
            .listen('.message.sent', (data) => {
                if (data.senderType === 'rider' && selectedRiderId == data.senderId) {
                    appendMessage(data.message, 'rider');
                }
            });

        function appendMessage(text, type) {
            const body = document.getElementById('chat-body');
            const msgContainer = document.createElement('div');
            msgContainer.className = 'flex flex-col mb-2 ' + (type === 'rider' ? 'items-start' : 'items-end');
            
            const tag = document.createElement('span');
            tag.className = 'msg-tag ' + (type === 'rider' ? 'rider-tag' : 'client-tag');
            tag.innerText = type === 'rider' ? 'RIDER' : 'YOU';

            const bubble = document.createElement('div');
            bubble.className = 'msg-bubble ' + (type === 'rider' ? 'msg-rider' : 'msg-client');
            bubble.innerText = text;
            
            msgContainer.appendChild(tag);
            msgContainer.appendChild(bubble);
            body.appendChild(msgContainer);
            body.scrollTop = body.scrollHeight;
        }

        function sendMessage() {
            const input = document.getElementById('chat-input');
            const text = input.value.trim();
            if (!text || !selectedRiderId) return;
            
            fetch('/chat/send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ sender_id: {{ Auth::guard('client')->id() ?? 0 }}, receiver_id: selectedRiderId, message: text, sender_type: 'client' })
            });
            appendMessage(text, 'client');
            input.value = '';
        }

        function togglePanel() {
            const panel = document.getElementById('rider-panel');
            panel.classList.toggle('translate-y-[80%]');
        }
    </script>
</body>
</html>
