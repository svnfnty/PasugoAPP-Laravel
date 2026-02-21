{{-- PIN Entry Modal Component - Mobile Optimized --}}
<div id="pin-modal" class="pin-modal" style="display: none;">
    <div class="pin-modal-overlay" onclick="MobileAuth.hidePinModal()"></div>
    <div class="pin-modal-content">
        <div class="pin-header">
            <div class="pin-icon">🔒</div>
            <h3>Enter PIN</h3>
            <p>Please enter your 4-digit PIN</p>
        </div>
        
        {{-- Hidden actual input for mobile keyboard --}}
        <input type="tel" 
               id="pin-hidden-input" 
               class="pin-hidden-input" 
               maxlength="4" 
               inputmode="numeric" 
               pattern="[0-9]*"
               autocomplete="off"
               autocorrect="off"
               autocapitalize="off"
               spellcheck="false">
        
        {{-- Visual PIN display --}}
        <div class="pin-display">
            <div class="pin-dot" data-index="0"></div>
            <div class="pin-dot" data-index="1"></div>
            <div class="pin-dot" data-index="2"></div>
            <div class="pin-dot" data-index="3"></div>
        </div>
        
        <div class="pin-actions">
            <button type="button" class="btn-verify" id="pin-verify-btn">
                <span>Verify</span>
            </button>
            <button type="button" class="btn-logout" onclick="MobileAuth.logout()">
                <span>Use Full Login</span>
            </button>
        </div>
        
        <p class="pin-error" id="pin-error-msg" style="display: none;">
            <span class="error-icon">⚠️</span>
            <span class="error-text">Invalid PIN</span>
        </p>
        
        {{-- Numeric Keypad for fallback --}}
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
    </div>
</div>

<style>
.pin-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    touch-action: none;
}

.pin-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.pin-modal-content {
    position: relative;
    background: white;
    border-radius: 24px;
    padding: 32px 24px;
    width: 90%;
    max-width: 360px;
    text-align: center;
    box-shadow: 0 25px 80px rgba(0, 0, 0, 0.4);
    animation: pinSlideUp 0.3s ease-out;
    max-height: 90vh;
    overflow-y: auto;
}

@keyframes pinSlideUp {
    from {
        opacity: 0;
        transform: translateY(50px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.pin-header {
    margin-bottom: 28px;
}

.pin-icon {
    font-size: 56px;
    margin-bottom: 16px;
    animation: lockPulse 2s infinite;
}

@keyframes lockPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.pin-header h3 {
    font-size: 24px;
    font-weight: 800;
    color: #1f2937;
    margin: 0 0 8px 0;
}

.pin-header p {
    font-size: 15px;
    color: #6b7280;
    margin: 0;
}

/* Hidden input for mobile keyboard */
.pin-hidden-input {
    position: absolute;
    opacity: 0;
    height: 0;
    width: 0;
    pointer-events: none;
}

/* PIN Display Dots */
.pin-display {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin: 32px 0;
    padding: 20px;
    background: #f8fafc;
    border-radius: 16px;
    border: 2px solid #e2e8f0;
}

.pin-dot {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: 3px solid #cbd5e1;
    background: white;
    transition: all 0.2s ease;
    position: relative;
}

.pin-dot.filled {
    background: #3b82f6;
    border-color: #3b82f6;
    transform: scale(1.1);
}

.pin-dot.filled::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 8px;
    height: 8px;
    background: white;
    border-radius: 50%;
}

.pin-dot.active {
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
    animation: dotPulse 1s infinite;
}

@keyframes dotPulse {
    0%, 100% { box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2); }
    50% { box-shadow: 0 0 0 8px rgba(59, 130, 246, 0.1); }
}

/* Keypad */
.pin-keypad {
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid #e2e8f0;
}

.keypad-row {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-bottom: 12px;
}

.keypad-btn {
    width: 72px;
    height: 56px;
    border: none;
    border-radius: 12px;
    background: #f1f5f9;
    color: #1f2937;
    font-size: 24px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    touch-action: manipulation;
}

.keypad-btn:active {
    transform: scale(0.95);
    background: #e2e8f0;
}

.keypad-btn:active {
    background: #3b82f6;
    color: white;
}

.keypad-clear {
    color: #ef4444;
    font-size: 18px;
}

.keypad-backspace {
    color: #6b7280;
    font-size: 20px;
}

/* Action Buttons */
.pin-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 20px;
}

.btn-verify {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    border: none;
    padding: 16px 24px;
    border-radius: 14px;
    font-size: 17px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
}

.btn-verify:active {
    transform: scale(0.98);
    box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
}

.btn-verify:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-logout {
    background: transparent;
    color: #6b7280;
    border: 2px solid #e2e8f0;
    padding: 14px 24px;
    border-radius: 14px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    -webkit-tap-highlight-color: transparent;
}

.btn-logout:active {
    background: #f1f5f9;
    border-color: #cbd5e1;
}

/* Error Message */
.pin-error {
    margin-top: 16px;
    padding: 14px;
    background: #fef2f2;
    border-radius: 10px;
    color: #dc2626;
    font-size: 15px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    animation: shake 0.5s ease-in-out;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}

.pin-error .error-icon {
    font-size: 18px;
}

/* Mobile optimizations */
@media (max-width: 480px) {
    .pin-modal-content {
        padding: 28px 20px;
        width: 95%;
    }
    
    .pin-dot {
        width: 20px;
        height: 20px;
    }
    
    .keypad-btn {
        width: 64px;
        height: 52px;
        font-size: 22px;
    }
    
    .pin-header h3 {
        font-size: 22px;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .pin-modal-content {
        background: #1f2937;
    }
    
    .pin-header h3 {
        color: #f9fafb;
    }
    
    .pin-header p {
        color: #9ca3af;
    }
    
    .pin-display {
        background: #374151;
        border-color: #4b5563;
    }
    
    .pin-dot {
        background: #1f2937;
        border-color: #6b7280;
    }
    
    .pin-dot.filled {
        background: #60a5fa;
        border-color: #60a5fa;
    }
    
    .keypad-btn {
        background: #374151;
        color: #f9fafb;
    }
    
    .keypad-btn:active {
        background: #4b5563;
    }
    
    .btn-logout {
        color: #9ca3af;
        border-color: #4b5563;
    }
    
    .btn-logout:active {
        background: #374151;
        color: #e5e7eb;
    }
}
</style>

<script>
(function() {
    'use strict';
    
    let currentPin = '';
    const maxDigits = 4;
    
    // Get elements
    const hiddenInput = document.getElementById('pin-hidden-input');
    const dots = document.querySelectorAll('.pin-dot');
    const verifyBtn = document.getElementById('pin-verify-btn');
    const errorMsg = document.getElementById('pin-error-msg');
    const keypadBtns = document.querySelectorAll('.keypad-btn');
    
    // Update visual display
    function updateDisplay() {
        const pin = hiddenInput.value;
        
        dots.forEach((dot, index) => {
            dot.classList.remove('filled', 'active');
            if (index < pin.length) {
                dot.classList.add('filled');
            } else if (index === pin.length) {
                dot.classList.add('active');
            }
        });
        
        // Enable/disable verify button
        verifyBtn.disabled = pin.length !== maxDigits;
    }
    
    // Add digit
    function addDigit(digit) {
        if (hiddenInput.value.length < maxDigits) {
            hiddenInput.value += digit;
            updateDisplay();
            
            // Auto-submit when 4 digits entered
            if (hiddenInput.value.length === maxDigits) {
                setTimeout(submitPin, 200);
            }
        }
    }
    
    // Clear PIN
    function clearPin() {
        hiddenInput.value = '';
        updateDisplay();
        errorMsg.style.display = 'none';
    }
    
    // Backspace
    function backspace() {
        hiddenInput.value = hiddenInput.value.slice(0, -1);
        updateDisplay();
        errorMsg.style.display = 'none';
    }
    
    // Submit PIN
    async function submitPin() {
        const pin = hiddenInput.value;
        
        if (pin.length !== maxDigits) {
            showError('Please enter 4 digits');
            return;
        }
        
        verifyBtn.disabled = true;
        verifyBtn.innerHTML = '<span>Verifying...</span>';
        
        try {
            const isValid = await MobileAuth.verifyPin(pin);
            
            if (isValid) {
                // Success - reload page
                window.location.reload();
            } else {
                showError('Invalid PIN. Try again.');
                clearPin();
                hiddenInput.focus();
            }
        } catch (error) {
            console.error('PIN verification error:', error);
            showError('Error verifying PIN');
        } finally {
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = '<span>Verify</span>';
        }
    }
    
    // Show error
    function showError(message) {
        errorMsg.querySelector('.error-text').textContent = message;
        errorMsg.style.display = 'flex';
        
        // Shake animation
        const content = document.querySelector('.pin-modal-content');
        content.style.animation = 'none';
        setTimeout(() => {
            content.style.animation = 'shake 0.5s ease-in-out';
        }, 10);
    }
    
    // Setup keypad buttons
    keypadBtns.forEach(btn => {
        // Handle both click and touch
        ['click', 'touchend'].forEach(eventType => {
            btn.addEventListener(eventType, function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const num = this.dataset.num;
                const action = this.dataset.action;
                
                if (num !== undefined) {
                    addDigit(num);
                } else if (action === 'clear') {
                    clearPin();
                } else if (action === 'backspace') {
                    backspace();
                }
                
                // Haptic feedback if available
                if (window.navigator && window.navigator.vibrate) {
                    window.navigator.vibrate(10);
                }
            });
        });
    });
    
    // Hidden input handlers
    hiddenInput.addEventListener('input', function(e) {
        // Only allow numbers
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, maxDigits);
        updateDisplay();
        
        // Auto-submit
        if (this.value.length === maxDigits) {
            setTimeout(submitPin, 200);
        }
    });
    
    // Handle paste
    hiddenInput.addEventListener('paste', function(e) {
        e.preventDefault();
        const pasted = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, maxDigits);
        this.value = pasted;
        updateDisplay();
        
        if (pasted.length === maxDigits) {
            setTimeout(submitPin, 200);
        }
    });
    
    // Verify button
    verifyBtn.addEventListener('click', submitPin);
    
    // Focus hidden input when clicking on dots
    document.querySelector('.pin-display').addEventListener('click', function() {
        hiddenInput.focus();
    });
    
    // Watch for modal visibility
    const modal = document.getElementById('pin-modal');
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && (mutation.attributeName === 'style' || mutation.attributeName === 'class')) {
                const isVisible = modal.style.display !== 'none';
                if (isVisible) {
                    // Reset state
                    clearPin();
                    // Focus hidden input to show keyboard
                    setTimeout(() => {
                        hiddenInput.focus();
                        // Try to show numeric keyboard on mobile
                        hiddenInput.click();
                    }, 100);
                }
            }
        });
    });
    
    observer.observe(modal, { attributes: true, attributeFilter: ['style', 'class'] });
    
    // Handle keyboard events
    document.addEventListener('keydown', function(e) {
        if (modal.style.display === 'none') return;
        
        if (e.key >= '0' && e.key <= '9') {
            e.preventDefault();
            addDigit(e.key);
        } else if (e.key === 'Backspace') {
            e.preventDefault();
            backspace();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            submitPin();
        } else if (e.key === 'Escape') {
            e.preventDefault();
            MobileAuth.hidePinModal();
        }
    });
    
    // Prevent zoom on double tap
    let lastTouchEnd = 0;
    modal.addEventListener('touchend', function(e) {
        const now = Date.now();
        if (now - lastTouchEnd <= 300) {
            e.preventDefault();
        }
        lastTouchEnd = now;
    }, false);
    
    // Initialize
    updateDisplay();
    
    // Expose functions to global scope for debugging
    window.PinModal = {
        addDigit,
        clearPin,
        backspace,
        submitPin,
        getPin: () => hiddenInput.value,
        focus: () => hiddenInput.focus()
    };
})();
</script>
