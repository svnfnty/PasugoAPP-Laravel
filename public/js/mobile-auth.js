/**
 * Mobile Authentication Module
 * Handles persistent sessions and PIN authentication for Capacitor mobile apps
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        TOKEN_KEY: 'pasugo_auth_token',
        USER_TYPE_KEY: 'pasugo_user_type',
        USER_ID_KEY: 'pasugo_user_id',
        PIN_ENABLED_KEY: 'pasugo_pin_enabled',
        DEVICE_ID_KEY: 'pasugo_device_id',
        API_BASE_URL: window.location.origin + '/api',
    };

    // State
    let currentToken = null;
    let isRestoring = false;
    let pinVerified = false;

    /**
     * Initialize the mobile auth system
     */
    function init() {
        console.log('[MobileAuth] Initializing... UA:', window.navigator?.userAgent);
        
        // Always setup mobile auth - it works in both mobile and browser
        setupMobileAuth();
        
        // Log environment for debugging
        if (isMobileApp()) {
            console.log('[MobileAuth] Mobile app environment detected');
        } else {
            console.log('[MobileAuth] Browser environment - session persistence enabled');
        }
    }

    /**
     * Check if running in Mobile WebView (Capacitor or any WebView)
     */
    function isMobileApp() {
        // Check for Capacitor
        if (typeof window.Capacitor !== 'undefined') {
            return true;
        }
        
        // Check user agent for WebView indicators
        const ua = window.navigator?.userAgent || '';
        const webViewIndicators = [
            'Capacitor',
            'WebView',
            'wv',
            'Android.*Version/[0-9]',
        ];
        
        for (const indicator of webViewIndicators) {
            if (ua.match(new RegExp(indicator, 'i'))) {
                return true;
            }
        }
        
        // Check for standalone mode (PWA)
        if (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) {
            return true;
        }
        
        // Check if running in Android WebView (no window.chrome but has Android in UA)
        if (/Android/.test(ua) && !/Chrome\/[0-9]/.test(ua)) {
            return true;
        }
        
        // For debugging: check if we should force mobile mode via URL param
        if (window.location.search.includes('force_mobile=true')) {
            return true;
        }
        
        return false;
    }

    /**
     * Setup mobile authentication handlers
     */
    function setupMobileAuth() {
        // Generate device ID if not exists
        ensureDeviceId();
        
        // Try to restore session on page load
        restoreSession();
        
        // Intercept login form submissions
        interceptLoginForms();
        
        // Add PIN modal to page if not exists
        ensurePinModal();
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
        
        console.log('[MobileAuth] Current path:', currentPath, 'Token exists, validating...');
        
        isRestoring = true;
        
        try {
            const response = await fetch(CONFIG.API_BASE_URL + '/token/validate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({
                    token: token,
                    device_id: getDeviceId(),
                }),
            });

            const data = await response.json();

            if (data.valid) {
                console.log('[MobileAuth] Token valid, user:', data.user?.email);
                currentToken = token;
                
                // Store PIN status
                if (data.pin_enabled) {
                    localStorage.setItem(CONFIG.PIN_ENABLED_KEY, 'true');
                }
                
                // If on login page with valid token
                if (isLoginPage) {
                    // Check if we're on the correct login page for this user type
                    if (!isCorrectLoginPage(data.user_type)) {
                        console.log('[MobileAuth] Wrong login page for user type:', data.user_type);
                        // Don't redirect - let user login with different account
                        clearStoredAuth();
                        return;
                    }
                    
                    // Store user info for after PIN verification
                    localStorage.setItem(CONFIG.USER_TYPE_KEY, data.user_type);
                    
                    // If PIN is enabled, show PIN modal FIRST (before redirect)
                    if (data.pin_enabled && !pinVerified) {
                        console.log('[MobileAuth] PIN required - showing modal first');
                        showPinModal();
                        // Store target URL for after PIN verification
                        window.targetAfterPin = data.user_type === 'rider' 
                            ? '/rider/dashboard' 
                            : '/client/dashboard';
                        isRestoring = false;
                        return;
                    }
                    
                    // No PIN required or PIN already verified, redirect to dashboard
                    const dashboardUrl = data.user_type === 'rider' 
                        ? '/rider/dashboard' 
                        : '/client/dashboard';
                    console.log('[MobileAuth] Redirecting to:', dashboardUrl);
                    window.location.replace(dashboardUrl);
                    return;
                }
                
                // If on protected page with PIN enabled and not verified, show PIN modal
                if (isProtectedPage && data.pin_enabled && !pinVerified) {
                    console.log('[MobileAuth] On protected page, PIN required');
                    showPinModal();
                }
                
                // Update UI to show logged-in state
                updateUIForLoggedInUser(data.user, data.user_type);
            } else {
                console.log('[MobileAuth] Token invalid or expired, clearing');
                clearStoredAuth();
                
                // If on protected page with invalid token, redirect to login
                if (isProtectedPage) {
                    const loginUrl = currentPath.includes('rider') 
                        ? '/rider/login' 
                        : '/client/login';
                    window.location.replace(loginUrl);
                }
            }
        } catch (error) {
            console.error('[MobileAuth] Error validating token:', error);
        } finally {
            isRestoring = false;
        }
    }

    /**
     * Intercept login forms to store tokens
     */
    function interceptLoginForms() {
        // Find all login forms
        const loginForms = document.querySelectorAll('form[action*="login"]');
        
        loginForms.forEach(form => {
            form.addEventListener('submit', async function(e) {
                // Only intercept if remember me is checked
                const rememberCheckbox = form.querySelector('input[name="remember"]');
                if (!rememberCheckbox || !rememberCheckbox.checked) {
                    return; // Let normal form submission proceed
                }

                e.preventDefault();
                
                const formData = new FormData(form);
                
                // Determine user type from form action or URL
                const userType = form.action.includes('rider') ? 'rider' : 'client';
                
                try {
                    // Ensure HTTPS is used to avoid mixed content errors
                    let actionUrl = form.action;
                    if (window.location.protocol === 'https:' && actionUrl.startsWith('http:')) {
                        actionUrl = actionUrl.replace('http:', 'https:');
                    }
                    
                    const response = await fetch(actionUrl, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': getCsrfToken(),
                        },
                        body: formData,
                    });

                    const data = await response.json();

                    if (data.success && data.token) {
                        // Store token and user info
                        storeAuthData(data.token, userType, data.user.id);
                        
                        // Redirect to dashboard (PIN setup moved to profile settings)
                        window.location.href = data.redirect;
                    } else {
                        // Show error
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
        pinVerified = false; // Reset PIN verification on new login
        console.log('[MobileAuth] Auth data stored');
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
    }

    /**
     * Setup PIN for quick access - NOW CALLED FROM PROFILE PAGE ONLY
     */
    async function setupPin(pin) {
        const token = localStorage.getItem(CONFIG.TOKEN_KEY);
        if (!token) {
            alert('Please login first');
            return false;
        }

        try {
            const response = await fetch(CONFIG.API_BASE_URL + '/pin/setup', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({
                    token: token,
                    pin: pin,
                }),
            });

            const data = await response.json();
            
            if (response.ok) {
                localStorage.setItem(CONFIG.PIN_ENABLED_KEY, 'true');
                return true;
            } else {
                console.error('PIN setup failed:', data.message);
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
                hiddenInput.focus();
            }
            
            // Reset dots
            updatePinDots('');
            
            // Reset error
            const errorMsg = document.getElementById('pin-error-msg');
            if (errorMsg) errorMsg.style.display = 'none';
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
        
        // Clear the PIN input
        const hiddenInput = document.getElementById('pin-hidden-input');
        if (hiddenInput) hiddenInput.value = '';
        
        updatePinDots('');
    }

    /**
     * Verify PIN
     */
    async function verifyPin(pin) {
        const token = localStorage.getItem(CONFIG.TOKEN_KEY);
        if (!token) return false;

        try {
            const response = await fetch(CONFIG.API_BASE_URL + '/pin/verify', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({
                    token: token,
                    pin: pin,
                }),
            });

            const data = await response.json();
            return data.valid;
        } catch (error) {
            console.error('[MobileAuth] PIN verification error:', error);
            return false;
        }
    }

    /**
     * Ensure PIN modal exists in DOM
     */
    function ensurePinModal() {
        // PIN modal is included via blade component
        // This function is kept for backwards compatibility
    }

    /**
     * Submit PIN for verification (called from PIN modal)
     */
    async function submitPin() {
        const hiddenInput = document.getElementById('pin-hidden-input');
        const pin = hiddenInput ? hiddenInput.value : '';
        
        if (!pin || pin.length !== 4) {
            showPinError('Please enter 4 digits');
            return;
        }

        const isValid = await verifyPin(pin);
        
        if (isValid) {
            pinVerified = true;
            hidePinModal();
            
            // Redirect to stored target URL or reload
            if (window.targetAfterPin) {
                window.location.replace(window.targetAfterPin);
            } else {
                window.location.reload();
            }
        } else {
            showPinError('Invalid PIN. Try again.');
            // Clear and focus
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
        const dots = document.querySelectorAll('.pin-dot');
        if (!dots) return;
        
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
                // Revoke token on server
                await fetch(CONFIG.API_BASE_URL + '/token/revoke', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                    body: JSON.stringify({ token: token }),
                });
            } catch (error) {
                console.error('[MobileAuth] Error revoking token:', error);
            }
        }

        clearStoredAuth();
        hidePinModal();
        
        // Redirect to home
        window.location.href = '/';
    }

    /**
     * Update UI for logged in user
     */
    function updateUIForLoggedInUser(user, userType) {
        console.log('[MobileAuth] User logged in:', user.name, 'Type:', userType);
    }

    /**
     * Get CSRF token from meta tag
     */
    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    /**
     * Debug function to check current status
     */
    function debugStatus() {
        console.log('[MobileAuth] Debug Status:');
        console.log('  - isMobileApp:', isMobileApp());
        console.log('  - Token exists:', !!localStorage.getItem(CONFIG.TOKEN_KEY));
        console.log('  - User Type:', localStorage.getItem(CONFIG.USER_TYPE_KEY));
        console.log('  - Device ID:', localStorage.getItem(CONFIG.DEVICE_ID_KEY));
        console.log('  - PIN Enabled:', localStorage.getItem(CONFIG.PIN_ENABLED_KEY));
        console.log('  - Current Path:', window.location.pathname);
        
        showDebugPanel();
    }
    
    /**
     * Create and show debug panel
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
                box-shadow: 0 4px 20px rgba(0,0,0,0.5);
            `;
            document.body.appendChild(panel);
        }
        
        const token = localStorage.getItem(CONFIG.TOKEN_KEY);
        const tokenPreview = token ? token.substring(0, 20) + '...' : 'none';
        
        panel.innerHTML = `
            <div style="margin-bottom: 10px; font-weight: bold; color: #ff0;">📱 Mobile Auth Debug</div>
            <div>isMobileApp: ${isMobileApp()}</div>
            <div>User Agent: ${window.navigator?.userAgent?.substring(0, 50)}...</div>
            <div>Token: ${tokenPreview}</div>
            <div>User Type: ${localStorage.getItem(CONFIG.USER_TYPE_KEY) || 'none'}</div>
            <div>Device ID: ${(localStorage.getItem(CONFIG.DEVICE_ID_KEY) || 'none').substring(0, 20)}...</div>
            <div>PIN Enabled: ${localStorage.getItem(CONFIG.PIN_ENABLED_KEY) || 'false'}</div>
            <div>Current Path: ${window.location.pathname}</div>
            <div style="margin-top: 10px;">
                <button onclick="MobileAuth.hideDebug()" style="background:#f00;color:white;border:none;padding:5px 10px;border-radius:5px;cursor:pointer;">Close</button>
            </div>
        `;
    }
    
    /**
     * Hide debug panel
     */
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

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Auto-show debug panel in mobile for first 10 seconds
    if (isMobileApp()) {
        setTimeout(() => {
            showDebugPanel();
        }, 1000);
        
        setTimeout(() => {
            hideDebugPanel();
        }, 11000);
    }
})();
