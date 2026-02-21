@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-white p-8 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-center mb-6">Client Login</h2>
    
    <form method="POST" action="{{ route('client.login') }}">
        @csrf
        
        <div class="mb-4">
            <label for="email" class="block text-gray-700 font-bold mb-2">Email Address</label>
            <input type="email" name="email" id="email" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500" required>
            @error('email')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
        
        <div class="mb-6">
            <label for="password" class="block text-gray-700 font-bold mb-2">Password</label>
            <input type="password" name="password" id="password" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500" required>
            @error('password')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4 flex items-center">
            <input type="checkbox" name="remember" id="remember" class="mr-2 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
            <label for="remember" class="text-gray-700 text-sm">Remember Me</label>
        </div>
        
        <button type="submit" class="w-full bg-orange-600 text-white font-bold py-2 px-4 rounded hover:bg-orange-700 transition">
            Login
        </button>
    </form>
    
    <p class="mt-4 text-center text-sm text-gray-600">
        Don't have an account? <a href="{{ route('client.register') }}" class="text-orange-600 hover:underline">Register here</a>
    </p>
</div>

<script>
(function() {
    'use strict';
    
    // Check if we're in a Capacitor/WebView environment
    function isCapacitor() {
        return typeof window.Capacitor !== 'undefined' || 
               (window.navigator && window.navigator.userAgent && 
                window.navigator.userAgent.includes('Capacitor'));
    }
    
    // Generate or get device ID
    function getDeviceId() {
        const key = 'pasugo_device_id';
        let deviceId = localStorage.getItem(key);
        if (!deviceId) {
            deviceId = 'client_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
            localStorage.setItem(key, deviceId);
        }
        return deviceId;
    }
    
    // Add mobile detection to form
    const loginForm = document.querySelector('form[action="{{ route('client.login') }}"]');
    if (loginForm && isCapacitor()) {
        // Add device ID field
        const deviceInput = document.createElement('input');
        deviceInput.type = 'hidden';
        deviceInput.name = 'device_id';
        deviceInput.value = getDeviceId();
        loginForm.appendChild(deviceInput);
        
        // Add device name
        const nameInput = document.createElement('input');
        nameInput.type = 'hidden';
        nameInput.name = 'device_name';
        nameInput.value = 'Android Client App';
        loginForm.appendChild(nameInput);
        
        console.log('[ClientLogin] Mobile environment detected, added device tracking');
    }
})();
</script>
@endsection
