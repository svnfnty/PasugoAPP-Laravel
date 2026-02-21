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

    /**
     * Initialize the mobile auth system
     */
    function init() {
        // Check if we're in a Capacitor environment
        if (isCapacitor()) {
            console.log('[MobileAuth] Capacitor environment detected');
            setupMobileAuth();
        } else {
            console.log('[MobileAuth] Browser environment - using standard session');
        }
    }

    /**
     * Check if running in Capacitor WebView
     */
    function isCapacitor() {
        return typeof window.Capacitor !== 'undefined' || 
               (window.navigator && window.navigator.userAgent && 
                window.navigator.userAgent.includes('Capacitor'));
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
     * Try to restore session from stored token
     */
    async function restoreSession() {
        if (isRestoring) return;
        
        const token = localStorage.getItem(CONFIG.TOKEN_KEY);
        if (!token) {
            console.log('[MobileAuth] No stored token found');
            return;
        }

        // Check if we're already on a protected page
        const currentPath = window.location.pathname;
        if (currentPath.includes('/dashboard') || currentPath.includes('/order')) {
            console.log('[MobileAuth] Already on protected page, validating token...');
            
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
                    console.log('[MobileAuth] Token valid, session restored');
                    currentToken = token;
                    
                    // Check if PIN is required
                    if (data.pin_enabled) {
                        showPinModal();
                    }
                    
                    // Update UI to show logged-in state
                    updateUIForLoggedInUser(data.user, data.user_type);
                } else {
                    console.log('[MobileAuth] Token invalid or expired');
                    clearStoredAuth();
                }
            } catch (error) {
                console.error('[MobileAuth] Error validating token:', error);
            } finally {
                isRestoring = false;
            }
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
                const remember = formData.get('remember') === 'on';
                
                // Determine user type from form action or URL
                const userType = form.action.includes('rider') ? 'rider' : 'client';
                
                try {
                    const response = await fetch(form.action, {
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
                        storeAuthData(data.token, userType, data.user.id, data.persistent_login_id);
                        
                        // Show PIN setup option
                        if (confirm('Would you like to set up a 4-digit PIN for quick access?')) {
                            setupPin(data.token);
                        } else {
                            // Redirect to dashboard
                            window.location.href = data.redirect;
                        }
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
    function storeAuthData(token, userType, userId, persistentLoginId) {
        localStorage.setItem(CONFIG.TOKEN_KEY, token);
        localStorage.setItem(CONFIG.USER_TYPE_KEY, userType);
        localStorage.setItem(CONFIG.USER_ID_KEY, userId.toString());
        currentToken = token;
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
    }

    /**
     * Setup PIN for quick access
     */
    async function setupPin(token) {
        const pin = prompt('Enter a 4-digit PIN:');
        if (!pin || pin.length !== 4 || !/^\d{4}$/.test(pin)) {
            alert('Please enter exactly 4 digits');
            return setupPin(token);
        }

        const confirmPin = prompt('Confirm your 4-digit PIN:');
        if (pin !== confirmPin) {
            alert('PINs do not match');
            return setupPin(token);
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
                alert('PIN setup successful! You can now use it for quick access.');
                // Redirect to dashboard
                const userType = localStorage.getItem(CONFIG.USER_TYPE_KEY);
                window.location.href = userType === 'rider' ? '/rider/dashboard' : '/client/dashboard';
            } else {
                alert('Failed to setup PIN: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('[MobileAuth] PIN setup error:', error);
            alert('Error setting up PIN');
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
        if (document.getElementById('pin-modal')) return;

        const modalHTML = `
            <div id="pin-modal" class="pin-modal" style="display: none;">
                <div class="pin-modal-content">
                    <h3>Enter PIN</h3>
                    <p>Please enter your 4-digit PIN to continue</p>
                    <div class="pin-inputs">
                        <input type="password" maxlength="1" class="pin-digit" data-index="0">
                        <input type="password" maxlength="1" class="pin-digit" data-index="1">
                        <input type="password" maxlength="1" class="pin-digit" data-index="2">
                        <input type="password" maxlength="1" class="pin-digit" data-index="3">
                    </div>
                    <div class="pin-actions">
                        <button type="button" class="btn-verify" onclick="MobileAuth.submitPin()">Verify</button>
                        <button type="button" class="btn-logout" onclick="MobileAuth.logout()">Logout</button>
                    </div>
                    <p class="pin-error" style="display: none; color: red;">Invalid PIN</p>
                </div>
            </div>
        `;

        const div = document.createElement('div');
        div.innerHTML = modalHTML;
        document.body.appendChild(div.firstElementChild);

        // Setup PIN input handlers
        setupPinInputs();
    }

    /**
     * Setup PIN input field handlers
     */
    function setupPinInputs() {
        const inputs = document.querySelectorAll('.pin-digit');
        
        inputs.forEach((input, index) => {
            input.addEventListener('input', function(e) {
                if (this.value.length === 1) {
                    if (index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }
                }
            });

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && this.value === '' && index > 0) {
                    inputs[index - 1].focus();
                }
                if (e.key === 'Enter') {
                    submitPin();
                }
            });
        });
    }

    /**
     * Submit PIN for verification
     */
    async function submitPin() {
        const inputs = document.querySelectorAll('.pin-digit');
        const pin = Array.from(inputs).map(input => input.value).join('');
        
        if (pin.length !== 4) {
            showPinError('Please enter all 4 digits');
            return;
        }

        const isValid = await verifyPin(pin);
        
        if (isValid) {
            hidePinModal();
            clearPinInputs();
            // Reload page to show authenticated content
            window.location.reload();
        } else {
            showPinError('Invalid PIN. Please try again.');
            clearPinInputs();
            inputs[0].focus();
        }
    }

    /**
     * Show PIN error message
     */
    function showPinError(message) {
        const errorEl = document.querySelector('.pin-error');
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.style.display = 'block';
        }
    }

    /**
     * Clear PIN input fields
     */
    function clearPinInputs() {
        const inputs = document.querySelectorAll('.pin-digit');
        inputs.forEach(input => input.value = '');
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
        // This can be extended to update navigation, show user info, etc.
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
     * Public API
     */
    window.MobileAuth = {
        init: init,
        isCapacitor: isCapacitor,
        setupPin: setupPin,
        submitPin: submitPin,
        logout: logout,
        showPinModal: showPinModal,
        hidePinModal: hidePinModal,
        getDeviceId: getDeviceId,
        getToken: () => currentToken || localStorage.getItem(CONFIG.TOKEN_KEY),
    };

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
