/**
 * UI/UX Enhancements - Interactive behaviors
 * Lightweight, performance-friendly interactions
 */

(function() {
    'use strict';

    // ========================================
    // 1. Smooth Scroll for Anchor Links
    // ========================================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // ========================================
    // 2. Lazy Image Loading Enhancement
    // ========================================
    const lazyImages = document.querySelectorAll('img[loading="lazy"]');
    lazyImages.forEach(img => {
        if (img.complete) {
            img.classList.add('loaded');
        } else {
            img.addEventListener('load', () => {
                img.classList.add('loaded');
            });
        }
    });

    // ========================================
    // 3. Button Ripple Effect
    // ========================================
    function createRipple(event) {
        const button = event.currentTarget;
        if (button.classList.contains('no-ripple')) return;
        
        const circle = document.createElement('span');
        const diameter = Math.max(button.clientWidth, button.clientHeight);
        const radius = diameter / 2;

        const rect = button.getBoundingClientRect();
        circle.style.width = circle.style.height = `${diameter}px`;
        circle.style.left = `${event.clientX - rect.left - radius}px`;
        circle.style.top = `${event.clientY - rect.top - radius}px`;
        circle.classList.add('ripple');

        const ripple = button.getElementsByClassName('ripple')[0];
        if (ripple) {
            ripple.remove();
        }

        button.appendChild(circle);
        
        setTimeout(() => circle.remove(), 600);
    }

    // Add ripple style if not exists
    if (!document.getElementById('ripple-styles')) {
        const style = document.createElement('style');
        style.id = 'ripple-styles';
        style.textContent = `
            .ripple {
                position: absolute;
                border-radius: 50%;
                transform: scale(0);
                animation: ripple-effect 0.6s linear;
                background-color: rgba(255, 255, 255, 0.4);
                pointer-events: none;
            }
            @keyframes ripple-effect {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }

    // Only apply ripple to buttons with explicit .btn-ripple class
    document.querySelectorAll('.btn-ripple').forEach(btn => {
        btn.style.position = 'relative';
        btn.style.overflow = 'hidden';
        btn.addEventListener('click', createRipple);
    });

    // ========================================
    // 4. Form Validation Feedback
    // ========================================
    document.querySelectorAll('form').forEach(form => {
        form.querySelectorAll('.form-control, .form-select').forEach(input => {
            input.addEventListener('invalid', function() {
                this.classList.add('shake');
                setTimeout(() => this.classList.remove('shake'), 500);
            });
        });
    });

    // ========================================
    // 5. Card Hover Effect Enhancement
    // ========================================
    document.querySelectorAll('.card.hover-lift, .card.hover-up').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.willChange = 'transform, box-shadow';
        });
        card.addEventListener('mouseleave', function() {
            this.style.willChange = 'auto';
        });
    });

    // ========================================
    // 6. Toast/Notification Auto-dismiss
    // ========================================
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        if (alert.dataset.autoDismiss !== 'false') {
            const delay = parseInt(alert.dataset.dismissDelay) || 5000;
            setTimeout(() => {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                if (bsAlert) {
                    alert.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => bsAlert.close(), 300);
                }
            }, delay);
        }
    });

    // ========================================
    // 7. Scroll Reveal Animation
    // ========================================
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const scrollObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                scrollObserver.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.reveal-on-scroll').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        scrollObserver.observe(el);
    });

    // Add revealed state styles
    if (!document.getElementById('reveal-styles')) {
        const style = document.createElement('style');
        style.id = 'reveal-styles';
        style.textContent = `
            .revealed {
                opacity: 1 !important;
                transform: translateY(0) !important;
            }
        `;
        document.head.appendChild(style);
    }

    // ========================================
    // 8. Mobile Touch Gestures
    // ========================================
    let touchStartX = 0;
    let touchEndX = 0;
    
    const sidebar = document.querySelector('.sidebar');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');
    
    if (sidebar && window.innerWidth < 992) {
        // Swipe right to open sidebar
        document.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        
        document.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, { passive: true });
        
        function handleSwipe() {
            const swipeDistance = touchEndX - touchStartX;
            const threshold = 80;
            
            // Swipe right from left edge to open
            if (swipeDistance > threshold && touchStartX < 30) {
                sidebar.classList.add('show');
                if (sidebarOverlay) sidebarOverlay.classList.add('show');
            }
            // Swipe left to close
            else if (swipeDistance < -threshold && sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
                if (sidebarOverlay) sidebarOverlay.classList.remove('show');
            }
        }
    }

    // ========================================
    // 9. Number Counter Animation
    // ========================================
    function animateCounter(element) {
        const target = parseInt(element.dataset.target) || parseInt(element.textContent);
        const duration = parseInt(element.dataset.duration) || 1000;
        const start = 0;
        const startTime = performance.now();
        
        function updateCounter(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const easeProgress = 1 - Math.pow(1 - progress, 3); // ease-out cubic
            const current = Math.floor(start + (target - start) * easeProgress);
            
            element.textContent = current.toLocaleString();
            
            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            } else {
                element.textContent = target.toLocaleString();
            }
        }
        
        requestAnimationFrame(updateCounter);
    }

    // Auto-animate counters when they come into view
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                counterObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    document.querySelectorAll('.animate-counter').forEach(el => {
        counterObserver.observe(el);
    });

    // ========================================
    // 10. Focus Trap for Modals (Accessibility)
    // ========================================
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('shown.bs.modal', function() {
            const focusableElements = this.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            if (focusableElements.length) {
                focusableElements[0].focus();
            }
        });
    });

    // ========================================
    // 11. Keyboard Navigation Enhancement
    // ========================================
    document.addEventListener('keydown', e => {
        // Escape key closes modals/dropdowns
        if (e.key === 'Escape') {
            const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
            openDropdowns.forEach(dropdown => {
                const toggle = dropdown.previousElementSibling;
                if (toggle) bootstrap.Dropdown.getInstance(toggle)?.hide();
            });
        }
    });

    // ========================================
    // 12. Page Transition Effect
    // ========================================
    window.addEventListener('beforeunload', function() {
        document.body.style.opacity = '0';
        document.body.style.transition = 'opacity 0.2s ease';
    });

    // ========================================
    // 13. Tooltip Initialization
    // ========================================
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipTriggerList.forEach(el => {
            new bootstrap.Tooltip(el, {
                animation: true,
                delay: { show: 200, hide: 100 }
            });
        });

        // Popovers
        const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
        popoverTriggerList.forEach(el => {
            new bootstrap.Popover(el);
        });
    }

    // ========================================
    // 14. Back to Top Button (opt-in via data attribute or existing element)
    // ========================================
    const scrollThreshold = 300;
    let backToTopBtn = document.querySelector('.back-to-top');
    
    // Only create if page explicitly opts in via body data attribute
    if (!backToTopBtn && document.body.dataset.backToTop === 'true') {
        backToTopBtn = document.createElement('button');
        backToTopBtn.className = 'back-to-top btn btn-primary shadow';
        backToTopBtn.innerHTML = '<i class="bi bi-arrow-up"></i>';
        backToTopBtn.setAttribute('aria-label', 'Back to top');
        backToTopBtn.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1050;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            padding: 0;
        `;
        document.body.appendChild(backToTopBtn);
    }

    if (backToTopBtn) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > scrollThreshold) {
                backToTopBtn.style.opacity = '1';
                backToTopBtn.style.visibility = 'visible';
            } else {
                backToTopBtn.style.opacity = '0';
                backToTopBtn.style.visibility = 'hidden';
            }
        }, { passive: true });

        backToTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ========================================
    // 15. Loading State Handler
    // ========================================
    window.setButtonLoading = function(button, isLoading) {
        if (isLoading) {
            button.classList.add('loading');
            button.disabled = true;
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading...';
        } else {
            button.classList.remove('loading');
            button.disabled = false;
            button.innerHTML = button.dataset.originalText || button.innerHTML;
        }
    };

})();
