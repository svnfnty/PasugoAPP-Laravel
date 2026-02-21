{{-- PIN Entry Modal Component --}}
<div id="pin-modal" class="pin-modal" style="display: none;">
    <div class="pin-modal-overlay"></div>
    <div class="pin-modal-content">
        <div class="pin-header">
            <div class="pin-icon">🔒</div>
            <h3>Enter PIN</h3>
            <p>Please enter your 4-digit PIN to continue</p>
        </div>
        
        <div class="pin-inputs">
            <input type="password" maxlength="1" class="pin-digit" data-index="0" inputmode="numeric" pattern="[0-9]*">
            <input type="password" maxlength="1" class="pin-digit" data-index="1" inputmode="numeric" pattern="[0-9]*">
            <input type="password" maxlength="1" class="pin-digit" data-index="2" inputmode="numeric" pattern="[0-9]*">
            <input type="password" maxlength="1" class="pin-digit" data-index="3" inputmode="numeric" pattern="[0-9]*">
        </div>
        
        <div class="pin-actions">
            <button type="button" class="btn-verify" onclick="MobileAuth.submitPin()">
                <span>Verify</span>
            </button>
            <button type="button" class="btn-logout" onclick="MobileAuth.logout()">
                <span>Use Full Login</span>
            </button>
        </div>
        
        <p class="pin-error" style="display: none;">
            <span class="error-icon">⚠️</span>
            <span class="error-text">Invalid PIN</span>
        </p>
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
}

.pin-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
}

.pin-modal-content {
    position: relative;
    background: white;
    border-radius: 20px;
    padding: 32px 24px;
    width: 90%;
    max-width: 320px;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: pinSlideUp 0.3s ease-out;
}

@keyframes pinSlideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.pin-header {
    margin-bottom: 24px;
}

.pin-icon {
    font-size: 48px;
    margin-bottom: 12px;
}

.pin-header h3 {
    font-size: 22px;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 8px 0;
}

.pin-header p {
    font-size: 14px;
    color: #6b7280;
    margin: 0;
}

.pin-inputs {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-bottom: 24px;
}

.pin-digit {
    width: 56px;
    height: 64px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    text-align: center;
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    background: #f9fafb;
    transition: all 0.2s ease;
    outline: none;
}

.pin-digit:focus {
    border-color: #3b82f6;
    background: white;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.pin-digit.filled {
    border-color: #10b981;
    background: #ecfdf5;
}

.pin-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.btn-verify {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    border: none;
    padding: 14px 24px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-verify:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
}

.btn-verify:active {
    transform: translateY(0);
}

.btn-logout {
    background: transparent;
    color: #6b7280;
    border: 1px solid #e5e7eb;
    padding: 12px 24px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-logout:hover {
    background: #f3f4f6;
    color: #374151;
}

.pin-error {
    margin-top: 16px;
    padding: 12px;
    background: #fef2f2;
    border-radius: 8px;
    color: #dc2626;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    animation: shake 0.5s ease-in-out;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}

.pin-error .error-icon {
    font-size: 16px;
}

/* Mobile optimizations */
@media (max-width: 480px) {
    .pin-modal-content {
        padding: 28px 20px;
    }
    
    .pin-digit {
        width: 48px;
        height: 56px;
        font-size: 20px;
    }
    
    .pin-header h3 {
        font-size: 20px;
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
    
    .pin-digit {
        background: #374151;
        border-color: #4b5563;
        color: #f9fafb;
    }
    
    .pin-digit:focus {
        border-color: #60a5fa;
        background: #4b5563;
    }
    
    .btn-logout {
        color: #9ca3af;
        border-color: #4b5563;
    }
    
    .btn-logout:hover {
        background: #374151;
        color: #e5e7eb;
    }
}
</style>

<script>
(function() {
    'use strict';
    
    // Setup PIN input handlers when modal is shown
    function setupPinInputs() {
        const inputs = document.querySelectorAll('.pin-digit');
        
        inputs.forEach((input, index) => {
            // Clear previous handlers
            const newInput = input.cloneNode(true);
            input.parentNode.replaceChild(newInput, input);
            
            newInput.addEventListener('input', function(e) {
                // Only allow numbers
                this.value = this.value.replace(/[^0-9]/g, '');
                
                if (this.value.length === 1) {
                    this.classList.add('filled');
                    if (index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    } else {
                        // Last digit entered, auto-submit
                        setTimeout(() => {
                            if (typeof MobileAuth !== 'undefined') {
                                MobileAuth.submitPin();
                            }
                        }, 100);
                    }
                } else {
                    this.classList.remove('filled');
                }
            });

            newInput.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && this.value === '' && index > 0) {
                    inputs[index - 1].focus();
                }
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (typeof MobileAuth !== 'undefined') {
                        MobileAuth.submitPin();
                    }
                }
            });
            
            // Handle paste
            newInput.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 4);
                
                if (pastedData.length === 4) {
                    inputs.forEach((inp, i) => {
                        inp.value = pastedData[i] || '';
                        if (pastedData[i]) {
                            inp.classList.add('filled');
                        }
                    });
                    
                    setTimeout(() => {
                        if (typeof MobileAuth !== 'undefined') {
                            MobileAuth.submitPin();
                        }
                    }, 100);
                }
            });
        });
    }
    
    // Watch for modal display changes
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.target.id === 'pin-modal') {
                const display = window.getComputedStyle(mutation.target).display;
                if (display !== 'none') {
                    setupPinInputs();
                    // Focus first input
                    setTimeout(() => {
                        const firstInput = document.querySelector('.pin-digit[data-index="0"]');
                        if (firstInput) firstInput.focus();
                    }, 100);
                }
            }
        });
    });
    
    // Start observing when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('pin-modal');
            if (modal) {
                observer.observe(modal, { attributes: true, attributeFilter: ['style', 'class'] });
            }
        });
    } else {
        const modal = document.getElementById('pin-modal');
        if (modal) {
            observer.observe(modal, { attributes: true, attributeFilter: ['style', 'class'] });
        }
    }
})();
</script>
