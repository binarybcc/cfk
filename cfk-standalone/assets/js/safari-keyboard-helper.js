/**
 * Safari Keyboard Navigation Helper
 * Detects Safari browser and provides guidance for enabling keyboard navigation
 *
 * Safari on macOS requires users to enable keyboard navigation in:
 * System Settings > Keyboard > Keyboard navigation (toggle on)
 * OR
 * Safari > Settings > Advanced > "Press Tab to highlight each item on a webpage"
 */

(function() {
    'use strict';

    // Detect Safari browser
    function isSafari() {
        const ua = navigator.userAgent.toLowerCase();
        const isSafariBrowser = ua.indexOf('safari') !== -1 &&
                                ua.indexOf('chrome') === -1 &&
                                ua.indexOf('chromium') === -1;
        return isSafariBrowser;
    }

    // Detect iOS Safari
    function isIOSSafari() {
        const ua = navigator.userAgent;
        const iOS = /iPad|iPhone|iPod/.test(ua) && !window.MSStream;
        return iOS && isSafari();
    }

    // Detect macOS
    function isMacOS() {
        return navigator.platform.toUpperCase().indexOf('MAC') >= 0;
    }

    // Check if keyboard navigation seems to be working
    function testKeyboardNavigation() {
        // Try to detect if Tab key focus is working
        // This is a heuristic check
        return new Promise((resolve) => {
            let tabWorking = false;

            const testHandler = (e) => {
                if (e.key === 'Tab') {
                    tabWorking = true;
                    cleanup();
                    resolve(true);
                }
            };

            const cleanup = () => {
                document.removeEventListener('keydown', testHandler);
                clearTimeout(timeout);
            };

            const timeout = setTimeout(() => {
                cleanup();
                resolve(tabWorking);
            }, 3000); // Wait 3 seconds for user to press Tab

            document.addEventListener('keydown', testHandler);
        });
    }

    // Create and show notification banner
    function showKeyboardNavigationNotice() {
        // Check if user has dismissed this notice before
        if (localStorage.getItem('safari-keyboard-notice-dismissed') === 'true') {
            return;
        }

        const notice = document.createElement('div');
        notice.id = 'safari-keyboard-notice';
        notice.setAttribute('role', 'alert');
        notice.setAttribute('aria-live', 'polite');
        notice.innerHTML = `
            <div class="safari-notice-content">
                <div class="safari-notice-icon">⌨️</div>
                <div class="safari-notice-text">
                    <strong>Keyboard Navigation Tip for Safari Users</strong>
                    <p>To navigate this site using your keyboard (Tab, Shift+Tab, Enter), enable keyboard navigation:</p>
                    <ol>
                        <li><strong>macOS:</strong> System Settings → Keyboard → Turn on "Keyboard navigation"</li>
                        <li><strong>Safari:</strong> Safari → Settings → Advanced → Check "Press Tab to highlight each item on a webpage"</li>
                    </ol>
                    <p class="safari-notice-help">After enabling, press Tab to move between links and buttons.</p>
                </div>
                <button class="safari-notice-close" aria-label="Dismiss keyboard navigation notice">
                    ✕
                </button>
            </div>
        `;

        // Add styles
        const style = document.createElement('style');
        style.textContent = `
            #safari-keyboard-notice {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                background: #fff3cd;
                border-bottom: 3px solid #ffc107;
                padding: 16px;
                z-index: 9999;
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
                animation: slideDown 0.3s ease-out;
            }

            @keyframes slideDown {
                from {
                    transform: translateY(-100%);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }

            .safari-notice-content {
                max-width: 1200px;
                margin: 0 auto;
                display: flex;
                gap: 16px;
                align-items: flex-start;
            }

            .safari-notice-icon {
                font-size: 32px;
                flex-shrink: 0;
            }

            .safari-notice-text {
                flex: 1;
            }

            .safari-notice-text strong {
                display: block;
                font-size: 18px;
                margin-bottom: 8px;
                color: #856404;
            }

            .safari-notice-text p {
                margin: 8px 0;
                color: #856404;
                font-size: 14px;
                line-height: 1.5;
            }

            .safari-notice-text ol {
                margin: 8px 0;
                padding-left: 20px;
                color: #856404;
                font-size: 14px;
            }

            .safari-notice-text li {
                margin: 4px 0;
            }

            .safari-notice-help {
                font-weight: 600;
                margin-top: 12px !important;
            }

            .safari-notice-close {
                background: transparent;
                border: 2px solid #856404;
                color: #856404;
                font-size: 24px;
                width: 36px;
                height: 36px;
                border-radius: 50%;
                cursor: pointer;
                flex-shrink: 0;
                transition: all 0.2s;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 0;
            }

            .safari-notice-close:hover {
                background: #856404;
                color: #fff;
            }

            .safari-notice-close:focus {
                outline: 3px solid #ffc107;
                outline-offset: 2px;
            }

            @media (max-width: 768px) {
                .safari-notice-content {
                    flex-direction: column;
                }

                .safari-notice-icon {
                    display: none;
                }

                .safari-notice-text strong {
                    font-size: 16px;
                }
            }
        `;

        document.head.appendChild(style);
        document.body.insertBefore(notice, document.body.firstChild);

        // Add close button handler
        const closeBtn = notice.querySelector('.safari-notice-close');
        closeBtn.addEventListener('click', () => {
            notice.style.animation = 'slideDown 0.3s ease-out reverse';
            setTimeout(() => {
                notice.remove();
                style.remove();
            }, 300);
            localStorage.setItem('safari-keyboard-notice-dismissed', 'true');
        });

        // Auto-dismiss after 30 seconds
        setTimeout(() => {
            if (document.getElementById('safari-keyboard-notice')) {
                closeBtn.click();
            }
        }, 30000);
    }

    // Initialize
    function init() {
        // Only run on Safari
        if (!isSafari()) {
            return;
        }

        // On iOS Safari, show a simpler message (no keyboard navigation on touch devices)
        if (isIOSSafari()) {
            // iOS uses VoiceOver for accessibility, not keyboard navigation
            console.log('iOS Safari detected - VoiceOver is the primary accessibility tool');
            return;
        }

        // On macOS Safari, show keyboard navigation help
        if (isMacOS()) {
            // Show the notice immediately for Safari users on macOS
            // They can dismiss it if keyboard nav is already working
            setTimeout(showKeyboardNavigationNotice, 1000);
        }
    }

    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Export for manual testing
    window.SafariKeyboardHelper = {
        showNotice: showKeyboardNavigationNotice,
        isSafari: isSafari,
        isIOSSafari: isIOSSafari,
        isMacOS: isMacOS
    };

})();
