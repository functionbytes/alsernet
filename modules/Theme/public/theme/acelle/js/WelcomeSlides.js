/**
 * WelcomeSlides — Onboarding tour for new customers.
 *
 * Auto-shows on first dashboard visit. Stores "seen" in localStorage.
 * Header icon re-triggers the tour at any time.
 *
 * Usage:
 *   McWelcomeSlides.init()          — auto-show if not seen
 *   McWelcomeSlides.show()          — force show (header icon)
 */
class WelcomeSlides {
    constructor() {
        this.STORAGE_KEY = 'acelle_welcome_seen';
        this.current = 0;
        this.overlay = null;
        this.isAnimating = false;
        this._onComplete = null;

        // Translations from Blade (window.McWelcomeSlidesI18n) with English fallbacks
        var t = window.McWelcomeSlidesI18n || {};
        var btn = (t.btn || {});
        this.btn = {
            next: btn.next || 'Next',
            back: btn.back || 'Back',
            skip: btn.skip || 'Skip',
            done: btn.done || 'Done'
        };

        this.slides = [
            {
                id: 'welcome',
                icon: 'rocket',
                color: 'teal',
                title: (t.welcome || {}).title || 'Welcome to Acelle Mail',
                desc:  (t.welcome || {}).desc  || 'Your all-in-one email marketing platform. Create campaigns, grow your audience, and drive results — all from one powerful dashboard.'
            },
            {
                id: 'campaigns',
                icon: 'campaigns',
                color: 'blue',
                title: (t.campaigns || {}).title || 'Powerful Campaigns',
                desc:  (t.campaigns || {}).desc  || 'Design stunning emails with our drag-and-drop builder, run A/B tests to find what works, and schedule sends for the perfect moment.'
            },
            {
                id: 'audience',
                icon: 'audience',
                color: 'green',
                title: (t.audience || {}).title || 'Know Your Audience',
                desc:  (t.audience || {}).desc  || 'Organize subscribers into smart lists and segments. Import contacts, track engagement, and deliver the right message to the right people.'
            },
            {
                id: 'automation',
                icon: 'automation',
                color: 'purple',
                title: (t.automation || {}).title || 'Automate Everything',
                desc:  (t.automation || {}).desc  || 'Set up triggered workflows that run on autopilot — welcome series, follow-ups, re-engagement, and more. Work smarter, not harder.'
            },
            {
                id: 'analytics',
                icon: 'analytics',
                color: 'orange',
                title: (t.analytics || {}).title || 'Track & Optimize',
                desc:  (t.analytics || {}).desc  || 'Real-time dashboards show opens, clicks, bounces, and conversions. Understand what resonates and continuously improve your results.'
            }
        ];
    }

    init() {
        if (!localStorage.getItem(this.STORAGE_KEY)) {
            this.show();
        }
    }

    show(onComplete) {
        this._onComplete = onComplete || null;
        this.current = 0;
        this._build();
        this._render();
        // Force reflow then activate
        this.overlay.offsetHeight;
        this.overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    close(markSeen) {
        if (markSeen !== false) {
            localStorage.setItem(this.STORAGE_KEY, Date.now());
        }
        var onComplete = this._onComplete;
        this._onComplete = null;
        this.overlay.classList.remove('active');
        document.body.style.overflow = '';
        setTimeout(() => {
            if (this.overlay && this.overlay.parentNode) {
                this.overlay.parentNode.removeChild(this.overlay);
            }
            this.overlay = null;
            if (typeof onComplete === 'function') onComplete();
        }, 350);
    }

    _build() {
        // Remove existing if any
        const existing = document.getElementById('mc-welcome-slides');
        if (existing) existing.remove();

        const el = document.createElement('div');
        el.id = 'mc-welcome-slides';
        el.className = 'mc-welcome-slides';
        el.innerHTML = `
            <div class="mc-welcome-backdrop"></div>
            <div class="mc-welcome-card">
                <button class="mc-welcome-close" aria-label="Close" data-welcome-close>
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 5l10 10M15 5L5 15"/></svg>
                </button>

                <div class="mc-welcome-slides-track">
                    ${this.slides.map((s, i) => `
                        <div class="mc-welcome-slide" data-slide="${i}">
                            <div class="mc-welcome-illustration mc-welcome-illustration--${s.color}">
                                ${this._svg(s.icon)}
                            </div>
                            <div class="mc-welcome-text">
                                <h2 class="mc-welcome-title">${s.title}</h2>
                                <p class="mc-welcome-desc">${s.desc}</p>
                            </div>
                        </div>
                    `).join('')}
                </div>

                <div class="mc-welcome-footer">
                    <div class="mc-welcome-dots">
                        ${this.slides.map((_, i) => `<button class="mc-welcome-dot${i === 0 ? ' active' : ''}" data-welcome-dot="${i}" aria-label="Slide ${i + 1}"></button>`).join('')}
                    </div>
                    <div class="mc-welcome-actions">
                        <button class="mc-welcome-btn mc-welcome-btn--skip" data-welcome-skip>${this.btn.skip}</button>
                        <button class="mc-welcome-btn mc-welcome-btn--back mc-hidden" data-welcome-back>
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 3L5 8l5 5"/></svg>
                            ${this.btn.back}
                        </button>
                        <button class="mc-welcome-btn mc-welcome-btn--next" data-welcome-next>
                            ${this.btn.next}
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3l5 5-5 5"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(el);
        this.overlay = el;

        // Events
        el.querySelector('[data-welcome-close]').addEventListener('click', () => this.close());
        el.querySelector('[data-welcome-skip]').addEventListener('click', () => this.close());
        el.querySelector('[data-welcome-next]').addEventListener('click', () => this._next());
        el.querySelector('[data-welcome-back]').addEventListener('click', () => this._prev());
        el.querySelector('.mc-welcome-backdrop').addEventListener('click', () => this.close());

        el.querySelectorAll('[data-welcome-dot]').forEach(dot => {
            dot.addEventListener('click', () => {
                this._goTo(parseInt(dot.dataset.welcomeDot));
            });
        });

        // Keyboard
        this._keyHandler = (e) => {
            if (!this.overlay) return;
            if (e.key === 'Escape') this.close();
            if (e.key === 'ArrowRight') this._next();
            if (e.key === 'ArrowLeft') this._prev();
        };
        document.addEventListener('keydown', this._keyHandler);
    }

    _next() {
        if (this.current < this.slides.length - 1) {
            this._goTo(this.current + 1);
        } else {
            // Last slide — done
            this.close();
        }
    }

    _prev() {
        if (this.current > 0) {
            this._goTo(this.current - 1);
        }
    }

    _goTo(index) {
        if (this.isAnimating || index === this.current) return;
        this.isAnimating = true;

        const dir = index > this.current ? 1 : -1;
        const slides = this.overlay.querySelectorAll('.mc-welcome-slide');
        const oldSlide = slides[this.current];
        const newSlide = slides[index];

        // Exit old
        oldSlide.classList.remove('active');
        oldSlide.classList.add(dir > 0 ? 'exit-left' : 'exit-right');

        // Enter new
        newSlide.classList.add(dir > 0 ? 'enter-right' : 'enter-left');
        newSlide.offsetHeight; // reflow
        newSlide.classList.remove('enter-right', 'enter-left');
        newSlide.classList.add('active');

        this.current = index;
        this._render();

        setTimeout(() => {
            oldSlide.classList.remove('exit-left', 'exit-right');
            this.isAnimating = false;
        }, 400);
    }

    _render() {
        if (!this.overlay) return;
        const isFirst = this.current === 0;
        const isLast = this.current === this.slides.length - 1;

        const backBtn = this.overlay.querySelector('[data-welcome-back]');
        const nextBtn = this.overlay.querySelector('[data-welcome-next]');
        const skipBtn = this.overlay.querySelector('[data-welcome-skip]');

        backBtn.classList.toggle('mc-hidden', isFirst);
        skipBtn.classList.toggle('mc-hidden', isLast);

        if (isLast) {
            nextBtn.innerHTML = `${this.btn.done} <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8.5l3.5 3.5L13 4"/></svg>`;
        } else {
            nextBtn.innerHTML = `${this.btn.next} <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3l5 5-5 5"/></svg>`;
        }

        // Dots
        this.overlay.querySelectorAll('.mc-welcome-dot').forEach((dot, i) => {
            dot.classList.toggle('active', i === this.current);
        });

        // Slides visibility
        this.overlay.querySelectorAll('.mc-welcome-slide').forEach((slide, i) => {
            slide.classList.toggle('active', i === this.current);
        });
    }

    /* ====================================================================
       SVG Illustrations — hand-crafted, unique per slide
       ==================================================================== */
    _svg(icon) {
        const svgs = {
            rocket: `<svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Launch pad -->
                <ellipse cx="100" cy="170" rx="55" ry="8" fill="var(--color-teal-light)" opacity="0.6"/>
                <!-- Flame -->
                <path d="M92 148c-2 12-6 22 8 30 14-8 10-18 8-30" fill="#FF8A65" opacity="0.9"/>
                <path d="M95 148c-1 8-3 16 5 22 8-6 6-14 5-22" fill="#FFD54F" opacity="0.9"/>
                <!-- Rocket body -->
                <path d="M100 40c-12 20-18 50-18 80v20h36v-20c0-30-6-60-18-80z" fill="var(--color-card-bg)" stroke="var(--color-teal)" stroke-width="2.5"/>
                <!-- Nose cone -->
                <path d="M100 40c-6 10-10 22-13 36h26c-3-14-7-26-13-36z" fill="var(--color-teal)" opacity="0.9"/>
                <!-- Window -->
                <circle cx="100" cy="100" r="10" fill="var(--color-teal-light)" stroke="var(--color-teal)" stroke-width="2"/>
                <circle cx="100" cy="100" r="5" fill="var(--color-teal)" opacity="0.3"/>
                <!-- Fins -->
                <path d="M82 120l-14 28h14z" fill="var(--color-teal)" opacity="0.7"/>
                <path d="M118 120l14 28h-14z" fill="var(--color-teal)" opacity="0.7"/>
                <!-- Stars -->
                <circle cx="40" cy="55" r="2.5" fill="var(--color-teal)" opacity="0.5"/>
                <circle cx="155" cy="45" r="2" fill="var(--color-teal)" opacity="0.4"/>
                <circle cx="50" cy="90" r="1.5" fill="var(--color-teal)" opacity="0.3"/>
                <circle cx="160" cy="80" r="2.5" fill="var(--color-teal)" opacity="0.5"/>
                <circle cx="35" cy="130" r="1.5" fill="var(--color-teal)" opacity="0.3"/>
                <circle cx="168" cy="120" r="1.5" fill="var(--color-teal)" opacity="0.3"/>
                <!-- Speed lines -->
                <path d="M60 60h15M55 75h10M145 55h-15M150 70h-10" stroke="var(--color-teal)" stroke-width="1.5" stroke-linecap="round" opacity="0.3"/>
            </svg>`,

            campaigns: `<svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Background circle -->
                <circle cx="100" cy="100" r="70" fill="var(--color-info-bg)" opacity="0.5"/>
                <!-- Email A -->
                <rect x="30" y="65" width="65" height="48" rx="6" fill="var(--color-card-bg)" stroke="var(--color-info)" stroke-width="2"/>
                <path d="M30 73l32.5 18L95 73" stroke="var(--color-info)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <text x="62.5" y="58" text-anchor="middle" fill="var(--color-info)" font-size="14" font-weight="700" font-family="system-ui">A</text>
                <!-- Email B -->
                <rect x="105" y="65" width="65" height="48" rx="6" fill="var(--color-card-bg)" stroke="var(--color-teal)" stroke-width="2"/>
                <path d="M105 73l32.5 18L170 73" stroke="var(--color-teal)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <text x="137.5" y="58" text-anchor="middle" fill="var(--color-teal)" font-size="14" font-weight="700" font-family="system-ui">B</text>
                <!-- VS divider -->
                <circle cx="100" cy="89" r="14" fill="var(--color-card-bg)" stroke="var(--color-border-strong)" stroke-width="1.5"/>
                <text x="100" y="94" text-anchor="middle" fill="var(--color-text-muted)" font-size="11" font-weight="600" font-family="system-ui">VS</text>
                <!-- Winner crown -->
                <path d="M125 130l4-10 8 5 8-12 8 12 8-5 4 10z" fill="#FFD54F" stroke="#FFA726" stroke-width="1.5"/>
                <rect x="125" y="130" width="40" height="6" rx="2" fill="#FFD54F" stroke="#FFA726" stroke-width="1.5"/>
                <!-- Bar chart -->
                <rect x="45" y="135" rx="3" width="16" height="30" fill="var(--color-info)" opacity="0.6"/>
                <rect x="67" y="125" rx="3" width="16" height="40" fill="var(--color-info)" opacity="0.8"/>
                <rect x="89" y="145" rx="3" width="16" height="20" fill="var(--color-teal)" opacity="0.5"/>
                <line x1="40" y1="165" x2="110" y2="165" stroke="var(--color-border)" stroke-width="1.5"/>
            </svg>`,

            audience: `<svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Background -->
                <circle cx="100" cy="100" r="70" fill="var(--color-success-bg)" opacity="0.5"/>
                <!-- Center person -->
                <circle cx="100" cy="75" r="16" fill="var(--color-card-bg)" stroke="var(--color-success)" stroke-width="2.5"/>
                <circle cx="100" cy="72" r="7" fill="var(--color-success)" opacity="0.6"/>
                <path d="M100 80c-4 0-7 2-7 5" stroke="var(--color-success)" stroke-width="2" stroke-linecap="round"/>
                <path d="M100 80c4 0 7 2 7 5" stroke="var(--color-success)" stroke-width="2" stroke-linecap="round"/>
                <!-- Left person -->
                <circle cx="55" cy="100" r="12" fill="var(--color-card-bg)" stroke="var(--color-teal)" stroke-width="2"/>
                <circle cx="55" cy="98" r="5" fill="var(--color-teal)" opacity="0.5"/>
                <!-- Right person -->
                <circle cx="145" cy="100" r="12" fill="var(--color-card-bg)" stroke="var(--color-info)" stroke-width="2"/>
                <circle cx="145" cy="98" r="5" fill="var(--color-info)" opacity="0.5"/>
                <!-- Bottom left -->
                <circle cx="68" cy="145" r="10" fill="var(--color-card-bg)" stroke="var(--color-warning)" stroke-width="2"/>
                <circle cx="68" cy="143.5" r="4" fill="var(--color-warning)" opacity="0.5"/>
                <!-- Bottom right -->
                <circle cx="132" cy="145" r="10" fill="var(--color-card-bg)" stroke="#C3B1E1" stroke-width="2"/>
                <circle cx="132" cy="143.5" r="4" fill="#C3B1E1" opacity="0.5"/>
                <!-- Connection lines -->
                <line x1="85" y1="85" x2="67" y2="95" stroke="var(--color-success)" stroke-width="1.5" stroke-dasharray="4 3" opacity="0.5"/>
                <line x1="115" y1="85" x2="133" y2="95" stroke="var(--color-success)" stroke-width="1.5" stroke-dasharray="4 3" opacity="0.5"/>
                <line x1="60" y1="112" x2="65" y2="135" stroke="var(--color-teal)" stroke-width="1.5" stroke-dasharray="4 3" opacity="0.4"/>
                <line x1="140" y1="112" x2="135" y2="135" stroke="var(--color-info)" stroke-width="1.5" stroke-dasharray="4 3" opacity="0.4"/>
                <line x1="80" y1="145" x2="120" y2="145" stroke="var(--color-border-strong)" stroke-width="1.5" stroke-dasharray="4 3" opacity="0.3"/>
                <!-- Segment badge -->
                <rect x="75" y="110" width="50" height="20" rx="10" fill="var(--color-success)" opacity="0.15"/>
                <text x="100" y="124" text-anchor="middle" fill="var(--color-success)" font-size="10" font-weight="600" font-family="system-ui">SEGMENT</text>
            </svg>`,

            automation: `<svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Background -->
                <circle cx="100" cy="100" r="70" fill="#F3E5F5" opacity="0.5"/>
                <!-- Trigger node -->
                <rect x="70" y="35" width="60" height="30" rx="15" fill="var(--color-card-bg)" stroke="#9C27B0" stroke-width="2"/>
                <circle cx="90" cy="50" r="4" fill="#9C27B0" opacity="0.6"/>
                <line x1="98" y1="50" x2="118" y2="50" stroke="#9C27B0" stroke-width="2" stroke-linecap="round"/>
                <!-- Arrow down -->
                <path d="M100 65v15" stroke="#9C27B0" stroke-width="2" stroke-dasharray="4 3"/>
                <path d="M95 75l5 5 5-5" stroke="#9C27B0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <!-- Decision diamond -->
                <path d="M100 85l20 15-20 15-20-15z" fill="var(--color-card-bg)" stroke="#9C27B0" stroke-width="2"/>
                <text x="100" y="104" text-anchor="middle" fill="#9C27B0" font-size="10" font-weight="600" font-family="system-ui">IF</text>
                <!-- Left branch -->
                <path d="M80 100l-20 15" stroke="var(--color-success)" stroke-width="2"/>
                <rect x="35" y="115" width="50" height="26" rx="6" fill="var(--color-card-bg)" stroke="var(--color-success)" stroke-width="2"/>
                <text x="60" y="132" text-anchor="middle" fill="var(--color-success)" font-size="9" font-weight="500" font-family="system-ui">Send Email</text>
                <!-- Right branch -->
                <path d="M120 100l20 15" stroke="var(--color-info)" stroke-width="2"/>
                <rect x="115" y="115" width="50" height="26" rx="6" fill="var(--color-card-bg)" stroke="var(--color-info)" stroke-width="2"/>
                <text x="140" y="132" text-anchor="middle" fill="var(--color-info)" font-size="9" font-weight="500" font-family="system-ui">Wait 2 days</text>
                <!-- Merge -->
                <path d="M60 141v10l40 10 40-10v-10" stroke="var(--color-border-strong)" stroke-width="1.5" stroke-dasharray="4 3"/>
                <!-- End node -->
                <circle cx="100" cy="168" r="10" fill="var(--color-card-bg)" stroke="#9C27B0" stroke-width="2"/>
                <circle cx="100" cy="168" r="4" fill="#9C27B0" opacity="0.5"/>
                <!-- Lightning bolts -->
                <path d="M40 55l3-8h5l-3 8z" fill="#FFD54F" stroke="#FFA726" stroke-width="1"/>
                <path d="M160 60l3-8h5l-3 8z" fill="#FFD54F" stroke="#FFA726" stroke-width="1"/>
            </svg>`,

            analytics: `<svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Background -->
                <circle cx="100" cy="100" r="70" fill="var(--color-warning-bg)" opacity="0.5"/>
                <!-- Dashboard frame -->
                <rect x="35" y="45" width="130" height="90" rx="8" fill="var(--color-card-bg)" stroke="var(--color-border-strong)" stroke-width="2"/>
                <!-- Title bar -->
                <rect x="35" y="45" width="130" height="18" rx="8" fill="var(--color-subtle-bg)"/>
                <rect x="35" y="55" width="130" height="8" fill="var(--color-subtle-bg)"/>
                <circle cx="46" cy="54" r="3" fill="var(--color-error)" opacity="0.6"/>
                <circle cx="56" cy="54" r="3" fill="var(--color-warning)" opacity="0.6"/>
                <circle cx="66" cy="54" r="3" fill="var(--color-success)" opacity="0.6"/>
                <!-- Line chart -->
                <polyline points="50,110 70,95 85,102 100,85 115,90 130,78 150,82" stroke="var(--color-teal)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M50 110 70 95 85 102 100 85 115 90 130 78 150 82V125H50z" fill="var(--color-teal)" opacity="0.08"/>
                <!-- Data dots -->
                <circle cx="70" cy="95" r="3" fill="var(--color-teal)"/>
                <circle cx="100" cy="85" r="3" fill="var(--color-teal)"/>
                <circle cx="130" cy="78" r="3" fill="var(--color-teal)"/>
                <!-- Stat badges below -->
                <rect x="40" y="148" width="35" height="28" rx="6" fill="var(--color-success-bg)"/>
                <text x="57.5" y="160" text-anchor="middle" fill="var(--color-success)" font-size="8" font-weight="600" font-family="system-ui">OPENS</text>
                <text x="57.5" y="172" text-anchor="middle" fill="var(--color-success)" font-size="10" font-weight="700" font-family="system-ui">42%</text>

                <rect x="82" y="148" width="35" height="28" rx="6" fill="var(--color-info-bg)"/>
                <text x="99.5" y="160" text-anchor="middle" fill="var(--color-info)" font-size="8" font-weight="600" font-family="system-ui">CLICKS</text>
                <text x="99.5" y="172" text-anchor="middle" fill="var(--color-info)" font-size="10" font-weight="700" font-family="system-ui">18%</text>

                <rect x="124" y="148" width="35" height="28" rx="6" fill="var(--color-warning-bg)"/>
                <text x="141.5" y="160" text-anchor="middle" fill="var(--color-warning)" font-size="8" font-weight="600" font-family="system-ui">CTR</text>
                <text x="141.5" y="172" text-anchor="middle" fill="var(--color-warning)" font-size="10" font-weight="700" font-family="system-ui">3.2%</text>

                <!-- Upward arrow -->
                <path d="M155 50l5-6 5 6" stroke="var(--color-success)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" opacity="0"/>
            </svg>`
        };
        return svgs[icon] || '';
    }
}

window.McWelcomeSlides = new WelcomeSlides();
