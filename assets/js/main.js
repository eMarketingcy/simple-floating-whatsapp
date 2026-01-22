/**
 * eM WhatsApp - Modern JavaScript Module
 * Enhanced with ES6+, accessibility, and analytics tracking
 * Version: 3.0.0
 */

(function() {
    'use strict';

    /**
     * WhatsApp Button Handler Class
     */
    class WhatsAppButton {
        constructor() {
            this.button = null;
            this.wrapper = null;
            this.clickCount = 0;
            this.init();
        }

        /**
         * Initialize the WhatsApp button functionality
         */
        init() {
            // Wait for DOM to be ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.setup());
            } else {
                this.setup();
            }
        }

        /**
         * Setup button elements and event listeners
         */
        setup() {
            this.button = document.querySelector('.sfw-button');
            this.wrapper = document.querySelector('.sfw-wrapper');

            if (!this.button) {
                return;
            }

            this.attachEventListeners();
            this.enhanceAccessibility();
            this.observeVisibility();
        }

        /**
         * Attach all event listeners
         */
        attachEventListeners() {
            // Click event with visual feedback
            this.button.addEventListener('click', (e) => this.handleClick(e));

            // Keyboard support
            this.button.addEventListener('keydown', (e) => this.handleKeydown(e));

            // Touch events for mobile optimization
            if ('ontouchstart' in window) {
                this.button.addEventListener('touchstart', () => this.handleTouchStart(), { passive: true });
                this.button.addEventListener('touchend', () => this.handleTouchEnd(), { passive: true });
            }

            // Track hover duration (for analytics)
            let hoverStartTime = null;
            this.button.addEventListener('mouseenter', () => {
                hoverStartTime = Date.now();
            });

            this.button.addEventListener('mouseleave', () => {
                if (hoverStartTime) {
                    const hoverDuration = Date.now() - hoverStartTime;
                    this.trackEvent('hover', { duration: hoverDuration });
                    hoverStartTime = null;
                }
            });
        }

        /**
         * Handle button click
         */
        handleClick(e) {
            this.clickCount++;

            // Add visual click feedback
            this.addClickAnimation();

            // Track click event
            this.trackEvent('click', {
                phone: this.button.dataset.sfwPhone || 'unknown',
                position: this.wrapper?.dataset.sfwPosition || 'unknown',
                clickCount: this.clickCount
            });

            // Log to console (can be removed in production)
            console.log(`[eM WhatsApp] Button clicked (${this.clickCount} times)`);
        }

        /**
         * Handle keyboard navigation
         */
        handleKeydown(e) {
            // Support Enter and Space keys
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.button.click();
            }
        }

        /**
         * Handle touch start for mobile
         */
        handleTouchStart() {
            this.button.style.transform = 'scale(0.95)';
        }

        /**
         * Handle touch end for mobile
         */
        handleTouchEnd() {
            setTimeout(() => {
                this.button.style.transform = '';
            }, 150);
        }

        /**
         * Add click animation feedback
         */
        addClickAnimation() {
            this.button.style.transform = 'scale(0.95)';

            setTimeout(() => {
                this.button.style.transform = '';
            }, 150);
        }

        /**
         * Enhance accessibility features
         */
        enhanceAccessibility() {
            // Ensure proper ARIA attributes
            if (!this.button.hasAttribute('role')) {
                this.button.setAttribute('role', 'button');
            }

            // Add keyboard focus indicator
            this.button.addEventListener('focus', () => {
                this.button.classList.add('sfw-focused');
            });

            this.button.addEventListener('blur', () => {
                this.button.classList.remove('sfw-focused');
            });
        }

        /**
         * Observe button visibility for lazy loading/performance
         */
        observeVisibility() {
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            this.trackEvent('visible');
                        }
                    });
                }, { threshold: 0.1 });

                if (this.wrapper) {
                    observer.observe(this.wrapper);
                }
            }
        }

        /**
         * Track events (Google Analytics, GTM, or custom analytics)
         */
        trackEvent(eventName, eventData = {}) {
            // Google Analytics 4 (gtag.js)
            if (typeof gtag === 'function') {
                gtag('event', `whatsapp_${eventName}`, {
                    event_category: 'WhatsApp Button',
                    event_label: 'eM WhatsApp',
                    ...eventData
                });
            }

            // Google Analytics Universal (ga.js)
            if (typeof ga === 'function') {
                ga('send', 'event', 'WhatsApp Button', eventName, 'eM WhatsApp');
            }

            // Google Tag Manager (dataLayer)
            if (window.dataLayer && Array.isArray(window.dataLayer)) {
                window.dataLayer.push({
                    event: `whatsapp_${eventName}`,
                    eventCategory: 'WhatsApp Button',
                    eventAction: eventName,
                    eventLabel: 'eM WhatsApp',
                    ...eventData
                });
            }

            // Custom event for other analytics tools
            window.dispatchEvent(new CustomEvent('sfwAnalytics', {
                detail: {
                    event: eventName,
                    data: eventData
                }
            }));
        }
    }

    /**
     * Initialize the WhatsApp button
     */
    new WhatsAppButton();

})();