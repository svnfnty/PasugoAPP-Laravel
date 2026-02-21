/**
 * Mobile Authentication Module
 * Handles persistent sessions and PIN authentication for Capacitor mobile apps
 */

(function() {
    'use strict';

    // Configuration - Use relative URLs to avoid mixed content issues
    const CONFIG = {
        TOKEN_KEY: 'pasugo_auth_token',
        USER_TYPE_KEY: 'pasugo_user_type',
        USER_ID_KEY: 'pasugo_user_id',
        PIN_ENABLED_KEY: 'pasugo_pin_enabled',
        DEVICE_ID_KEY: 'pasugo_device_id',
        // Use relative URL to avoid mixed content issues
        API_BASE_URL: '/api',
    };

    // State
    let currentToken = null;
    let isRestoring = false;
    let pinVerified = false;

    /**
     * Initialize the mobile auth system
     */
    function init() {
        console.log('[MobileAuth] Initializing...');
        console.log('[MobileAuth] Protocol:', window.location.protocol);
        console.log('[MobileAuth] Host:', window.location.host);
        console.log('[MobileAuth] Origin:', window.location.origin);
        
        // Always setup mobile auth
        setupMobileAuth();
        
        // Log environment
        if (isMobileApp()) {
            console.log('[MobileAuth] Mobile app environment detected');
        } else {
            console.log('[MobileAuth] Browser environment');
        }
    }

    /**
     * Check if running in Mobile WebView
     */
    function isMobileApp() {
        // Check for Capacitor
        if (typeof window.Capacitor !== 'undefined') {
            console.log('[MobileAuth] Capacitor detected');
            return true;
        }
        
        // Check user agent
        const ua = window.navigator?.userAgent || '';
        const webViewIndicators = [
            'Capacitor',
            'WebView',
            'wv',
        ];
        
        for (const indicator of webViewIndicators) {
            if (ua.match(new RegExp(indicator, 'i'))) {
                console.log('[MobileAuth] WebView detected via UA');
                return true;
            }
        }
        
        // Check for standalone mode (PWA)
        if (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) {
            return true;
        }
        
        // Android WebView detection
        if (/Android/.test(ua) && !/Chrome\/[0-9]/.test(ua)) {
            console.log('[MobileAuth] Android WebView detected');
            return true;
        }
        
        // Force mobile mode via URL param
        if (window.location.search.includes('force_mobile=true')) {
            return true;
        }
        
        return false;
    }

    /**
     * Setup mobile authentication handlers
     */
    function setupMobileAuth() {
        ensureDeviceId();
        restoreSession();
        interceptLoginForms();
    }

    /**
     * Generate or retrieve device ID
     */
    function ensureDeviceId() {
        let deviceId = localStorage.getItem(CONFIG.DEVICE_ID_KEY);
        if (!deviceId) {
            deviceId = 'device_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
            localStorage.setItem(CONFIG.DEVICE_ID_KEY, deviceId);
        }
        return deviceId;
    }

    /**
     * Get stored device ID
     */
    function getDeviceId() {
        return localStorage.getItem(CONFIG.DEVICE_ID_KEY) || ensureDeviceId();
    }

    /**
     * Check if current login page matches the user type
     */
    function isCorrectLoginPage(userType) {
        const currentPath = window.location.pathname;
        if (userType === 'rider') {
            return currentPath.includes('/rider/login');
        } else {
            return currentPath.includes('/client/login');
        }
    }

    /**
     * Try to restore session from stored token
     */
    async function restoreSession() {
        if (isRestoring) return;
        
        const token = localStorage.getItem(CONFIG.TOKEN_KEY);
        if (!token) {
            console.log('[MobileAuth] No stored token found');
            return;
        }

        const currentPath = window.location.pathname;
        const isLoginPage = currentPath.includes('/login');
        const isProtectedPage = currentPath.includes('/dashboard') || 
                               currentPath.includes('/order') || 
                               currentPath.includes('/map');
        
        console.log('[MobileAuth] Token found, validating...');
        console.log('[MobileAuth] Current path:', currentPath);
        
        isRestoring = true;
        
        try {
            // Use relative URL to avoid mixed content
            const validateUrl = CONFIG.API_BASE_URL + '/token/validate';
            console.log('[MobileAuth] Validating at:', validateUrl);
            
            const response = await fetch(validateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    token: token,
                    device_id: getDeviceId(),
                }),
            });

            console.log('[MobileAuth] Validate response status:', response.status);
            
            const data = await response.json();
            console.log('[MobileAuth] Validate response:', data);

            if (data.valid) {
                console.log('[MobileAuth] Token valid, user:', data.user?.email);
                currentToken = token;
                
                // Store PIN status
                if (data.pin_enabled) {
                    localStorage.setItem(CONFIG.PIN_ENABLED_KEY, 'true');
                    console.log('[MobileAuth] PIN is enabled');
                } else {
                    localStorage.removeItem(CONFIG.PIN_ENABLED_KEY);
                    console.log('[MobileAuth] PIN not enabled');
                }
                
                // If on login page with valid token
                if (isLoginPage) {
                    // Check if we're on the correct login page for this user type
                    if (!isCorrectLoginPage(data.user_type)) {
                        console.log('[MobileAuth] Wrong login page for user type:', data.user_type);
                        clearStoredAuth();
                        isRestoring = false;
                        return;
                    }
                    
                    // Store user info
                    localStorage.setItem(CONFIG.USER_TYPE_KEY, data.user_type);
                    
                    // If PIN is enabled and not yet verified, show PIN modal
                    if (data.pin_enabled && !pinVerified) {
                        console.log('[MobileAuth] PIN required - showing modal');
                        showPinModal();
                        window.targetAfterPin = data.user_type === 'rider' 
                            ? '/rider/dashboard' 
                            : '/client/dashboard';
                        isRestoring = false;
                        return;
                    }
                    
                    // No PIN required or already verified, redirect to dashboard
                    const dashboardUrl = data.user_type === 'rider' 
                        ? '/rider/dashboard' 
                        : '/client/dashboard';
                    console.log('[MobileAuth] Redirecting to:', dashboardUrl);
                    window.location.replace(dashboardUrl);
                    return;
                }
                
                // If on protected page with PIN enabled and not verified
                if (isProtectedPage && data.pin_enabled && !pinVerified) {
                    console.log('[MobileAuth] On protected page, PIN required');
                    showPinModal();
                }
                
                updateUIForLoggedInUser(data.user, data.user_type);
            } else {
                console.log('[MobileAuth] Token invalid:', data.message);
                clearStoredAuth();
                
                if (isProtectedPage) {
                    const loginUrl = currentPath.includes('rider') 
                        ? '/rider/login' 
                        : '/client/login';
                    window.location.replace(loginUrl);
                }
            }
        } catch (error) {
            console.error('[MobileAuth] Error validating token:', error);
            // Don't clear auth on network error, just log it
        } finally {
            isRestoring = false;
        }
    }

    /**
     * Intercept login forms to store tokens
     */
    function interceptLoginForms() {
        const loginForms = document.querySelectorAll('form[action*="login"]');
        
        loginForms.forEach(form => {
            form.addEventListener('submit', async function(e) {
                const rememberCheckbox = form.querySelector('input[name="remember"]');
                if (!rememberCheckbox || !rememberCheckbox.checked) {
                    return; // Let normal form submission proceed
                }

                e.preventDefault();
                
                const formData = new FormData(form);
                const userType = form.action.includes('rider') ? 'rider' : 'client';
                
                try {
                    // Use the form's action URL directly (relative)
                    const actionUrl = form.action;
                    console.log('[MobileAuth] Submitting login to:', actionUrl);
                    
                    const response = await fetch(actionUrl, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': getCsrfToken(),
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    const data = await response.json();
                    console.log('[MobileAuth] Login response:', data);

                    if (data.success && data.token) {
                        storeAuthData(data.token, userType, data.user.id);
                        console.log('[MobileAuth] Login successful, redirecting to:', data.redirect);
                        window.location.href = data.redirect;
                    } else {
                        alert(data.message || 'Login failed');
                    }
                } catch (error) {
                    console.error('[MobileAuth] Login error:', error);
                    // Fall back to normal form submission
                    form.submit();
                }
            });
        });
    }

    /**
     * Store authentication data
     */
    function storeAuthData(token, userType, userId) {
        localStorage.setItem(CONFIG.TOKEN_KEY, token);
        localStorage.setItem(CONFIG.USER_TYPE_KEY, userType);
        localStorage.setItem(CONFIG.USER_ID_KEY, userId.toString());
        currentToken = token;
        pinVerified = false;
        console.log('[MobileAuth] Auth data stored, token:', token.substring(0, 20) + '...');
    }

    /**
     * Clear stored authentication data
     */
    function clearStoredAuth() {
        localStorage.removeItem(CONFIG.TOKEN_KEY);
        localStorage.removeItem(CONFIG.USER_TYPE_KEY);
        localStorage.removeItem(CONFIG.USER_ID_KEY);
        localStorage.removeItem(CONFIG.PIN_ENABLED_KEY);
        currentToken = null;
        pinVerified = false;
        console.log('[MobileAuth] Auth data cleared');
    }

    /**
     * Setup PIN for quick access
     */
    async function setupPin(pin) {
        const token = localStorage.getItem(CONFIG.TOKEN_KEY);
        if (!token) {
            console.error('[MobileAuth] No token for PIN setup');
            alert('Please login first');
            return false;
        }

        try {
            console.log('[MobileAuth] Setting up PIN...');
            
            const response = await fetch(CONFIG.API_BASE_URL + '/pin/setup', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    token: token,
                    pin: pin,
                }),
            });

            const data = await response.json();
            console.log('[MobileAuth] PIN setup response:', data);
            
            if (response.ok) {
                localStorage.setItem(CONFIG.PIN_ENABLED_KEY, 'true');
                console.log('[MobileAuth] PIN setup successful');
                return true;
            } else {
                console.error('[MobileAuth] PIN setup failed:', data.message || data.error);
                return false;
            }
        } catch (error) {
            console.error('[MobileAuth] PIN setup error:', error);
            return false;
        }
    }

    /**
     * Show PIN entry modal
     */
    function showPinModal() {
        const modal = document.getElementById('pin-modal');
        if (modal) {
            modal.style.display = 'flex';
            modal.classList.add('active');
            
            // Reset PIN input
            const hiddenInput = document.getElementById('pin-hidden-input');
            if (hiddenInput) {
                hiddenInput.value = '';
                setTimeout(() => {
                    hiddenInput.focus();
                }, 100);
            }
            
            // Reset dots
            updatePinDots('');
            
            // Reset error
            const errorMsg = document.getElementById('pin-error-msg');
            if (errorMsg) errorMsg.style.display = 'none';
            
            console.log('[MobileAuth] PIN modal shown');
        } else {
            console.error('[MobileAuth] PIN modal not found in DOM');
        }
    }

    /**
     * Hide PIN entry modal
     */
    function hidePinModal() {
        const modal = document.getElementById('pin-modal');
        if (modal) {
            modal.style.display = 'none';
            modal.classList.remove('active');
        }
        
        const hiddenInput = document.getElementById('pin-hidden-input');
        if (hiddenInput) hiddenInput.value = '';
        
        updatePinDots('');
    }

    /**
     * Verify PIN
     */
    async function verifyPin(pin) {
        const token = localStorage.getItem(CONFIG.TOKEN_KEY);
        if (!token) {
            console.error('[MobileAuth] No token for PIN verification');
            return false;
        }

        try {
            console.log('[MobileAuth] Verifying PIN...');
            
            const response = await fetch(CONFIG.API_BASE_URL + '/pin/verify', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    token: token,
                    pin: pin,
                }),
            });

            const data = await response.json();
            console.log('[MobileAuth] PIN verify response:', data);
            
            if (data.valid) {
                console.log('[MobileAuth] PIN verified successfully');
                return true;
            } else {
                console.log('[MobileAuth] PIN invalid:', data.message);
                return false;
            }
        } catch (error) {
            console.error('[MobileAuth] PIN verification error:', error);
            return false;
        }
    }

    /**
     * Submit PIN for verification
     */
    async function submitPin() {
        const hiddenInput = document.getElementById('pin-hidden-input');
        const pin = hiddenInput ? hiddenInput.value : '';
        
        if (!pin || pin.length !== 4) {
            showPinError('Please enter 4 digits');
            return;
        }

        console.log('[MobileAuth] Submitting PIN...');
        const isValid = await verifyPin(pin);
        
        if (isValid) {
            pinVerified = true;
            hidePinModal();
            
            // Redirect to stored target URL or reload
            if (window.targetAfterPin) {
                console.log('[MobileAuth] PIN verified, redirecting to:', window.targetAfterPin);
                window.location.replace(window.targetAfterPin);
            } else {
                window.location.reload();
            }
        } else {
            showPinError('Invalid PIN. Try again.');
            if (hiddenInput) {
                hiddenInput.value = '';
                hiddenInput.focus();
            }
            updatePinDots('');
        }
    }

    /**
     * Show PIN error message
     */
    function showPinError(message) {
        const errorEl = document.getElementById('pin-error-msg');
        if (errorEl) {
            const errorText = errorEl.querySelector('.error-text');
            if (errorText) errorText.textContent = message;
            errorEl.style.display = 'flex';
        }
    }

    /**
     * Update PIN dots display
     */
    function updatePinDots(pin) {
        const dots = document.querySelectorAll('#pin-modal .pin-dot');
        if (!dots.length) return;
        
        dots.forEach((dot, index) => {
            dot.classList.remove('filled', 'active');
            if (index < pin.length) {
                dot.classList.add('filled');
            } else if (index === pin.length) {
                dot.classList.add('active');
            }
        });
    }

    /**
     * Logout and clear stored auth
     */
    async function logout() {
        const token = localStorage.getItem(CONFIG.TOKEN_KEY);
        
        if (token) {
            try {
                await fetch(CONFIG.API_BASE_URL + '/token/revoke', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ token: token }),
                });
            } catch (error) {
                console.error('[MobileAuth] Error revoking token:', error);
            }
        }

        clearStoredAuth();
        hidePinModal();
        window.location.href = '/';
    }

    /**
     * Update UI for logged in user
     */
    function updateUIForLoggedInUser(user, userType) {
        console.log('[MobileAuth] User logged in:', user?.name, 'Type:', userType);
    }

    /**
     * Get CSRF token from meta tag
     */
    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    /**
     * Debug function
     */
    function debugStatus() {
        console.log('[MobileAuth] Debug Status:');
        console.log('  - Protocol:', window.location.protocol);
        console.log('  - isMobileApp:', isMobileApp());
        console.log('  - Token exists:', !!localStorage.getItem(CONFIG.TOKEN_KEY));
        console.log('  - User Type:', localStorage.getItem(CONFIG.USER_TYPE_KEY));
        console.log('  - PIN Enabled:', localStorage.getItem(CONFIG.PIN_ENABLED_KEY));
        console.log('  - Current Path:', window.location.pathname);
        
        showDebugPanel();
    }
    
    /**
     * Show debug panel
     */
    function showDebugPanel() {
        let panel = document.getElementById('mobile-auth-debug');
        
        if (!panel) {
            panel = document.createElement('div');
            panel.id = 'mobile-auth-debug';
            panel.style.cssText = `
                position: fixed;
                bottom: 10px;
                left: 10px;
                right: 10px;
                background: rgba(0,0,0,0.9);
                color: #0f0;
                padding: 15px;
                border-radius: 10px;
                font-family: monospace;
                font-size: 12px;
                z-index: 99999;
                max-height: 200px;
                overflow-y: auto;
            `;
            document.body.appendChild(panel);
        }
        
        const token = localStorage.getItem(CONFIG.TOKEN_KEY);
        const tokenPreview = token ? token.substring(0, 20) + '...' : 'none';
        
        panel.innerHTML = `
            <div style="margin-bottom: 10px; font-weight: bold; color: #ff0;">📱 Mobile Auth Debug</div>
            <div>Protocol: ${window.location.protocol}</div>
            <div>isMobileApp: ${isMobileApp()}</div>
            <div>Token: ${tokenPreview}</div>
            <div>User Type: ${localStorage.getItem(CONFIG.USER_TYPE_KEY) || 'none'}</div>
            <div>PIN Enabled: ${localStorage.getItem(CONFIG.PIN_ENABLED_KEY) || 'false'}</div>
            <div>Current Path: ${window.location.pathname}</div>
            <div style="margin-top: 10px;">
                <button onclick="MobileAuth.hideDebug()" style="background:#f00;color:white;border:none;padding:5px 10px;border-radius:5px;cursor:pointer;">Close</button>
            </div>
        `;
    }
    
    function hideDebugPanel() {
        const panel = document.getElementById('mobile-auth-debug');
        if (panel) panel.remove();
    }

    /**
     * Public API
     */
    window.MobileAuth = {
        init: init,
        isMobileApp: isMobileApp,
        setupPin: setupPin,
        submitPin: submitPin,
        logout: logout,
        showPinModal: showPinModal,
        hidePinModal: hidePinModal,
        getDeviceId: getDeviceId,
        getToken: () => currentToken || localStorage.getItem(CONFIG.TOKEN_KEY),
        debug: debugStatus,
        showDebug: showDebugPanel,
        hideDebug: hideDebugPanel,
        isPinVerified: () => pinVerified,
    };

    // Auto-initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Show debug panel in mobile
    if (isMobileApp()) {
        setTimeout(() => {
            showDebugPanel();
        }, 500);
        
        setTimeout(() => {
            hideDebugPanel();
        }, 10000);
    }
})();
