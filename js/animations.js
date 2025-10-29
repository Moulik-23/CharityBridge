// CharityBridge - Elite Animation System
// FAANG-Level Scroll Animations & Micro-interactions

class AnimationController {
    constructor() {
        this.observer = null;
        this.header = null;
        this.init();
    }

    init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }

    setup() {
        this.setupScrollAnimations();
        this.setupHeaderScroll();
        this.setupCounterAnimations();
        this.setupParallax();
        this.setupMagneticButtons();
    }

    // Intersection Observer for Scroll Animations
    setupScrollAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    // Unobserve after animation for performance
                    this.observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observe all animation elements
        const animatedElements = document.querySelectorAll('.fade-in, .slide-in-left, .slide-in-right, .scale-in');
        animatedElements.forEach(el => this.observer.observe(el));
    }

    // Header Scroll Effect (Glassmorphism Enhancement)
    setupHeaderScroll() {
        this.header = document.querySelector('header');
        if (!this.header) return;

        let lastScroll = 0;
        let ticking = false;

        window.addEventListener('scroll', () => {
            lastScroll = window.scrollY;
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    this.updateHeader(lastScroll);
                    ticking = false;
                });
                ticking = true;
            }
        });
    }

    updateHeader(scrollY) {
        if (scrollY > 50) {
            this.header.classList.add('scrolled');
        } else {
            this.header.classList.remove('scrolled');
        }
    }

    // Animated Counters for Stats
    setupCounterAnimations() {
        const counterElements = document.querySelectorAll('.stats-number, .dashboard-stat-number');
        
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !entry.target.dataset.animated) {
                    this.animateCounter(entry.target);
                    entry.target.dataset.animated = 'true';
                }
            });
        }, { threshold: 0.5 });

        counterElements.forEach(el => counterObserver.observe(el));
    }

    animateCounter(element) {
        const target = parseInt(element.textContent.replace(/,/g, '')) || 1000;
        const duration = 2000;
        const startTime = performance.now();
        const startValue = 0;

        const updateCounter = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Easing function for smooth animation
            const easeOutQuad = progress * (2 - progress);
            const currentValue = Math.floor(startValue + (target - startValue) * easeOutQuad);
            
            element.textContent = currentValue.toLocaleString();
            
            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            } else {
                element.textContent = target.toLocaleString();
            }
        };

        requestAnimationFrame(updateCounter);
    }

    // Subtle Parallax Effect with No-Overlap Constraint
    setupParallax() {
        const parallaxElements = document.querySelectorAll('.hero-section, .auth-hero');
        
        if (parallaxElements.length === 0) return;

        let ticking = false;

        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    const scrolled = window.pageYOffset;
                    parallaxElements.forEach(el => {
                        // Very subtle parallax to avoid text overlap
                        const speed = 0.15;
                        const maxTransform = 30; // Maximum pixel movement
                        const transform = Math.min(scrolled * speed, maxTransform);
                        el.style.transform = `translateY(${transform}px)`;
                    });
                    ticking = false;
                });
                ticking = true;
            }
        });
        
        // Add spacing observer to prevent text overlap during animations
        this.setupOverlapPrevention();
    }
    
    // Prevent text overlap during scroll animations
    setupOverlapPrevention() {
        const sections = document.querySelectorAll('section');
        sections.forEach((section, index) => {
            // Ensure minimum spacing between sections
            if (index > 0) {
                section.style.marginTop = section.style.marginTop || '0';
                const currentMargin = parseInt(section.style.marginTop) || 0;
                if (currentMargin < 20) {
                    section.style.marginTop = '20px';
                }
            }
        });
    }

    // Magnetic Button Effect
    setupMagneticButtons() {
        const buttons = document.querySelectorAll('.btn-primary, .btn-secondary, .btn-accent');
        
        buttons.forEach(button => {
            button.addEventListener('mousemove', (e) => {
                const rect = button.getBoundingClientRect();
                const x = e.clientX - rect.left - rect.width / 2;
                const y = e.clientY - rect.top - rect.height / 2;
                
                // Subtle magnetic effect
                button.style.transform = `translate(${x * 0.1}px, ${y * 0.1}px)`;
            });

            button.addEventListener('mouseleave', () => {
                button.style.transform = '';
            });
        });
    }
}

// Smooth Scroll for Anchor Links
function setupSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Card Tilt Effect (3D Hover)
function setupCardTilt() {
    const cards = document.querySelectorAll('.role-link, .stats-card, .dashboard-card');
    
    cards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = (y - centerY) / 20;
            const rotateY = (centerX - x) / 20;
            
            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-12px) scale(1.03)`;
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = '';
        });
    });
}

// Floating Animation for Decorative Elements
function setupFloatingAnimations() {
    const style = document.createElement('style');
    style.textContent = `
        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        @keyframes floatRotate {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-15px) rotate(180deg);
            }
        }

        .hero-section .absolute:nth-child(1) {
            animation: float 6s ease-in-out infinite;
        }

        .hero-section .absolute:nth-child(2) {
            animation: floatRotate 8s ease-in-out infinite;
            animation-delay: 1s;
        }

        .hero-section .absolute:nth-child(3) {
            animation: float 7s ease-in-out infinite;
            animation-delay: 2s;
        }
    `;
    document.head.appendChild(style);
}

// Initialize everything
const animationController = new AnimationController();
setupSmoothScroll();
setupCardTilt();
setupFloatingAnimations();

// Performance optimization: Reduce animations on low-end devices
if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.body.style.setProperty('--transition-fast', '0ms');
    document.body.style.setProperty('--transition-base', '0ms');
    document.body.style.setProperty('--transition-slow', '0ms');
}

console.log('🎨 CharityBridge Elite Animations Loaded');
