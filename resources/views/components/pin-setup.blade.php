{{-- PIN Setup Component for Profile/Settings Page --}}
<div id="pin-setup-section" class="pin-setup-section">
    <h3>🔐 Security Settings</h3>
    <p class="pin-setup-desc">Set up a 4-digit PIN for quick and secure access to your account.</p>
    
    <div id="pin-setup-status" class="pin-setup-status">
        <span class="status-icon">🔓</span>
        <span class="status-text">PIN not set up</span>
    </div>
    
    <button type="button" id="btn-setup-pin" class="btn-setup-pin" onclick="PinSetup.showSetupModal()">
        Set Up PIN
    </button>
    
    <button type="button" id="btn-remove-pin" class="btn-remove-pin" style="display: none;" onclick="PinSetup.removePin()">
        Remove PIN
    </button>
</div>

{{-- PIN Setup Modal --}}
<div id="pin-setup-modal" class="pin-modal" style="display: none;">
    <div class="pin-modal-overlay" onclick="PinSetup.hideSetupModal()"></div>
    <div class="pin-modal-content">
        <div class="pin-header">
            <div class="pin-icon">🔐</div>
            <h3>Set Up PIN</h3>
            <p>Create a 4-digit PIN for quick access</p>
        </div>
        
        <div class="pin-setup-steps">
            <div class="step-indicator">
                <span class="step active" data-step="1">1</span>
                <span class="step-line"></span>
                <span class="step" data-step="2">2</span>
            </div>
            
            <div class="step-content" id="step-1">
                <p class="step-label">Enter 4-digit PIN</p>
                <div class="pin-display">
                    <div class="pin-dot" data-index="0"></div>
                    <div class="pin-dot" data-index="1"></div>
                    <div class="pin-dot" data-index="2"></div>
                    <div class="pin-dot" data-index="3"></div>
                </div>
            </div>
            
            <div class="step-content" id="step-2" style="display: none;">
                <p class="step-label">Confirm your PIN</p>
                <div class="pin-display">
                    <div class="pin-dot" data-index="0"></div>
                    <div class="pin-dot" data-index="1"></div>
                    <div class="pin-dot" data-index="2"></div>
                    <div class="pin-dot" data-index="3"></div>
                </div>
            </div>
        </div>
        
        <input type="tel" 
               id="pin-setup-input" 
               class="pin-hidden-input" 
               maxlength="4" 
               inputmode="numeric" 
               pattern="[0-9]*"
               autocomplete="off">
        
        <div class="pin-keypad">
            <div class="keypad-row">
                <button type="button" class="keypad-btn" data-num="1">1</button>
                <button type="button" class="keypad-btn" data-num="2">2</button>
                <button type="button" class="keypad-btn" data-num="3">3</button>
            </div>
            <div class="keypad-row">
                <button type="button" class="keypad-btn" data-num="4">4</button>
                <button type="button" class="keypad-btn" data-num="5">5</button>
                <button type="button" class="keypad-btn" data-num="6">6</button>
            </div>
            <div class="keypad-row">
                <button type="button" class="keypad-btn" data-num="7">7</button>
                <button type="button" class="keypad-btn" data-num="8">8</button>
                <button type="button" class="keypad-btn" data-num="9">9</button>
            </div>
            <div class="keypad-row">
                <button type="button" class="keypad-btn keypad-clear" data-action="clear">C</button>
                <button type="button" class="keypad-btn" data-num="0">0</button>
                <button type="button" class="keypad-btn keypad-backspace" data-action="backspace">⌫</button>
            </div>
        </div>
        
        <div class="pin-actions">
            <button type="button" class="btn-cancel" onclick="PinSetup.hideSetupModal()">Cancel</button>
        </div>
        
        <p class="pin-error" id="pin-setup-error" style="display: none;">
            <span class="error-icon">⚠️</span>
            <span class="error-text">PINs do not match</span>
        </p>
    </div>
</div>

<style>
.pin-setup-section {
    background: white;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.pin-setup-section h3 {
    margin: 0 0 8px 0;
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
}

.pin-setup-desc {
    margin: 0 0 16px 0;
    font-size: 14px;
    color: #6b7280;
}

.pin-setup-status {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    padding: 12px;
    background: #f3f4f6;
    border-radius: 8px;
}

.pin-setup-status.enabled {
    background: #ecfdf5;
    color: #059669;
}

.status-icon {
    font-size: 20px;
}

.status-text {
    font-size: 14px;
    font-weight: 500;
}

.btn-setup-pin {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-setup-pin:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
}

.btn-remove-pin {
    width: 100%;
    padding: 14px;
    background: #fee2e2;
    color: #dc2626;
    border: 2px solid #fecaca;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-remove-pin:hover {
    background: #fecaca;
}

/* PIN Setup Modal */
.pin-setup-steps {
    margin: 24px 0;
}

.step-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 20px;
}

.step {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #e5e7eb;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 700;
    transition: all 0.3s ease;
}

.step.active {
    background: #3b82f6;
    color: white;
}

.step.completed {
    background: #10b981;
    color: white;
}

.step-line {
    width: 40px;
    height: 2px;
    background: #e5e7eb;
    transition: all 0.3s ease;
}

.step-line.completed {
    background: #10b981;
}

.step-label {
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 16px;
}

.step-content {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.btn-cancel {
    padding: 12px 24px;
    background: transparent;
    color: #6b7280;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-cancel:hover {
    background: #f3f4f6;
}
</style>

<script>
(function() {
    'use strict';
    
    let currentStep = 1;
    let firstPin = '';
    let setupToken = null;
    
    // Check PIN status on load
    function checkPinStatus() {
        const pinEnabled = localStorage.getItem('pasugo_pin_enabled') === 'true';
        const statusEl = document.getElementById('pin-setup-status');
        const setupBtn = document.getElementById('btn-setup-pin');
        const removeBtn = document.getElementById('btn-remove-pin');
        
        if (pinEnabled) {
            statusEl.classList.add('enabled');
            statusEl.innerHTML = '<span class="status-icon">🔒</span><span class="status-text">PIN is enabled</span>';
            setupBtn.style.display = 'none';
            removeBtn.style.display = 'block';
        } else {
            statusEl.classList.remove('enabled');
            statusEl.innerHTML = '<span class="status-icon">🔓</span><span class="status-text">PIN not set up</span>';
            setupBtn.style.display = 'block';
            removeBtn.style.display = 'none';
        }
    }
    
    // Show setup modal
    function showSetupModal() {
        const modal = document.getElementById('pin-setup-modal');
        const input = document.getElementById('pin-setup-input');
        
        // Reset state
        currentStep = 1;
        firstPin = '';
        setupToken = localStorage.getItem('pasugo_auth_token');
        
        if (!setupToken) {
            alert('Please login first');
            return;
        }
        
        updateStepDisplay();
        modal.style.display = 'flex';
        
        setTimeout(() => {
            input.focus();
            input.click();
        }, 100);
    }
    
    // Hide setup modal
    function hideSetupModal() {
        const modal = document.getElementById('pin-setup-modal');
        const input = document.getElementById('pin-setup-input');
        
        modal.style.display = 'none';
        input.value = '';
        updateDots('');
        hideError();
    }
    
    // Update step display
    function updateStepDisplay() {
        const steps = document.querySelectorAll('.step');
        const stepContents = document.querySelectorAll('.step-content');
        const line = document.querySelector('.step-line');
        
        steps.forEach((step, index) => {
            step.classList.remove('active', 'completed');
            if (index + 1 < currentStep) {
                step.classList.add('completed');
            } else if (index + 1 === currentStep) {
                step.classList.add('active');
            }
        });
        
        stepContents.forEach((content, index) => {
            content.style.display = (index + 1 === currentStep) ? 'block' : 'none';
        });
        
        if (currentStep > 1) {
            line.classList.add('completed');
        } else {
            line.classList.remove('completed');
        }
    }
    
    // Add digit
    function addDigit(digit) {
        const input = document.getElementById('pin-setup-input');
        if (input.value.length < 4) {
            input.value += digit;
            updateDots(input.value);
            
            if (input.value.length === 4) {
                setTimeout(handlePinComplete, 200);
            }
        }
    }
    
    // Clear input
    function clearInput() {
        const input = document.getElementById('pin-setup-input');
        input.value = '';
        updateDots('');
        hideError();
    }
    
    // Backspace
    function backspace() {
        const input = document.getElementById('pin-setup-input');
        input.value = input.value.slice(0, -1);
        updateDots(input.value);
        hideError();
    }
    
    // Update dots display
    function updateDots(pin) {
        const dots = document.querySelectorAll('#pin-setup-modal .pin-dot');
        dots.forEach((dot, index) => {
            dot.classList.remove('filled', 'active');
            if (index < pin.length) {
                dot.classList.add('filled');
            } else if (index === pin.length) {
                dot.classList.add('active');
            }
        });
    }
    
    // Handle PIN entry complete
    function handlePinComplete() {
        const input = document.getElementById('pin-setup-input');
        const pin = input.value;
        
        if (currentStep === 1) {
            // Store first PIN and go to confirmation
            firstPin = pin;
            currentStep = 2;
            input.value = '';
            updateDots('');
            updateStepDisplay();
            
            setTimeout(() => {
                input.focus();
            }, 100);
        } else if (currentStep === 2) {
            // Verify PINs match
            if (pin === firstPin) {
                // Save PIN
                savePin(pin);
            } else {
                showError('PINs do not match. Please try again.');
                currentStep = 1;
                firstPin = '';
                input.value = '';
                updateDots('');
                updateStepDisplay();
            }
        }
    }
    
    // Save PIN to server
    async function savePin(pin) {
        try {
            const response = await fetch(window.location.origin + '/api/pin/setup', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    token: setupToken,
                    pin: pin,
                }),
            });
            
            const data = await response.json();
            
            if (response.ok) {
                localStorage.setItem('pasugo_pin_enabled', 'true');
                alert('PIN setup successful!');
                hideSetupModal();
                checkPinStatus();
            } else {
                showError(data.message || 'Failed to setup PIN');
            }
        } catch (error) {
            console.error('PIN setup error:', error);
            showError('Error setting up PIN. Please try again.');
        }
    }
    
    // Remove PIN
    async function removePin() {
        if (!confirm('Are you sure you want to remove your PIN?')) {
            return;
        }
        
        const token = localStorage.getItem('pasugo_auth_token');
        if (!token) {
            alert('Please login first');
            return;
        }
        
        try {
            const response = await fetch(window.location.origin + '/api/pin/disable', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    token: token,
                }),
            });
            
            if (response.ok) {
                localStorage.removeItem('pasugo_pin_enabled');
                alert('PIN removed successfully');
                checkPinStatus();
            } else {
                alert('Failed to remove PIN');
            }
        } catch (error) {
            console.error('Error removing PIN:', error);
            alert('Error removing PIN');
        }
    }
    
    // Show error
    function showError(message) {
        const errorEl = document.getElementById('pin-setup-error');
        if (errorEl) {
            errorEl.querySelector('.error-text').textContent = message;
            errorEl.style.display = 'flex';
        }
    }
    
    // Hide error
    function hideError() {
        const errorEl = document.getElementById('pin-setup-error');
        if (errorEl) {
            errorEl.style.display = 'none';
        }
    }
    
    // Setup keypad handlers
    function setupKeypad() {
        const keypadBtns = document.querySelectorAll('#pin-setup-modal .keypad-btn');
        
        keypadBtns.forEach(btn => {
            ['click', 'touchend'].forEach(eventType => {
                btn.addEventListener(eventType, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const num = this.dataset.num;
                    const action = this.dataset.action;
                    
                    if (num !== undefined) {
                        addDigit(num);
                    } else if (action === 'clear') {
                        clearInput();
                    } else if (action === 'backspace') {
                        backspace();
                    }
                    
                    // Haptic feedback
                    if (window.navigator && window.navigator.vibrate) {
                        window.navigator.vibrate(10);
                    }
                });
            });
        });
    }
    
    // Setup hidden input handlers
    function setupInput() {
        const input = document.getElementById('pin-setup-input');
        
        input.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4);
            updateDots(this.value);
            
            if (this.value.length === 4) {
                setTimeout(handlePinComplete, 200);
            }
        });
        
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pasted = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 4);
            this.value = pasted;
            updateDots(pasted);
            
            if (pasted.length === 4) {
                setTimeout(handlePinComplete, 200);
            }
        });
    }
    
    // Initialize
    function init() {
        checkPinStatus();
        setupKeypad();
        setupInput();
    }
    
    // Expose API
    window.PinSetup = {
        showSetupModal,
        hideSetupModal,
        removePin,
        checkPinStatus,
    };
    
    // Auto-init
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
