/**
 * McAppTour — Interactive product tour with spotlight & tooltip overlay.
 *
 * Highlights real UI elements one-by-one with a dimmed backdrop,
 * spotlight cutout, positioned tooltip with arrow, and smooth transitions.
 *
 * Usage:
 *   McAppTour.init()               — auto-show on dashboard if not seen
 *   McAppTour.start()              — start tour from step 1
 *   McAppTour.start(true)          — force restart (clears saved state)
 *
 * The help (?) icon in topbar calls McAppTour.start(true) to replay.
 */
class AppTour {
    constructor() {
        this.STORAGE_KEY = 'acelle_app_tour_completed';
        this.current = 0;
        this.overlay = null;
        this.isActive = false;
        this._resizeTimer = null;
        this._scrollTimer = null;

        // Tour steps — each targets a real DOM element
        // On mobile (< 768px), sidebar is hidden — target the hamburger toggle instead
        this.steps = [
            {
                target: window.innerWidth < 768 ? '.mc-sidebar-toggle' : '.mc-sidebar',
                title: 'Navigation',
                desc: window.innerWidth < 768
                    ? 'Tap the menu icon to open the sidebar. Browse campaigns, lists, automations, and settings from one place.'
                    : 'Your command center. Browse campaigns, lists, automations, and settings from the sidebar. It collapses on mobile for a clean workspace.',
                position: window.innerWidth < 768 ? 'bottom' : 'right',
                icon: 'home',
                color: 'teal',
                offset: { x: 8, y: 0 },
                spotlightPadding: window.innerWidth < 768 ? 6 : 8,
            },
            {
                target: '.mc-stat-grid',
                title: 'Your Quota at a Glance',
                desc: 'Track lists, campaigns, and subscriber usage in real-time. Progress bars turn red when you approach your plan limit.',
                position: 'bottom',
                icon: 'chart',
                color: 'blue',
                spotlightPadding: 12,
            },
            {
                target: '.mc-grow-section',
                title: 'Quick Actions',
                desc: 'Create campaigns, build lists, and view reports — your quick action cards. Jump straight into the campaign wizard from here.',
                position: 'bottom',
                icon: 'mail',
                color: 'green',
                spotlightPadding: 10,
            },
            {
                target: '.mc-topbar-search',
                title: 'Global Search',
                desc: 'Find campaigns, lists, subscribers, and templates instantly. Just start typing — results appear as you go.',
                position: 'bottom',
                icon: 'search',
                color: 'purple',
                spotlightPadding: 8,
            },
            {
                target: '#theme-toggle',
                title: 'Dark Mode & Themes',
                desc: 'Switch between light and dark mode, or pick a color scheme that matches your brand. Your preference is saved automatically.',
                position: 'bottom-end',
                icon: 'palette',
                color: 'orange',
                spotlightPadding: 6,
            },
            {
                target: '.mc-sidebar-nav .mc-nav-group:nth-child(3)',
                title: 'Audience & Lists',
                desc: 'Organize your subscribers into lists and segments. Import contacts, track engagement, and deliver targeted messages.',
                position: 'right',
                icon: 'group',
                color: 'green',
                spotlightPadding: 6,
            },
            {
                target: '.mc-topbar-user',
                title: 'Account & Settings',
                desc: 'Manage your profile, subscription, billing, API keys, and team members. Everything about your account lives here.',
                position: 'bottom-end',
                icon: 'settings',
                color: 'teal',
                spotlightPadding: 6,
            },
        ];
    }

    /**
     * Auto-init on dashboard — show tour if never completed.
     * Checks both server flag (McOnboardingCompleted) and localStorage.
     */
    init() {
        if (window.McOnboardingCompleted) return;
        if (!localStorage.getItem(this.STORAGE_KEY)) {
            setTimeout(() => this.start(), 800);
        }
    }

    /**
     * Start the tour.
     * @param {boolean} forceRestart — if true, clears completion state
     */
    start(forceRestart) {
        if (this.isActive) return;
        if (forceRestart) {
            localStorage.removeItem(this.STORAGE_KEY);
        }
        this.current = 0;
        this.isActive = true;
        this._build();
        this._showStep(0);
    }

    /**
     * End the tour and clean up.
     */
    end(markComplete) {
        if (!this.isActive) return;
        this.isActive = false;

        if (markComplete !== false) {
            localStorage.setItem(this.STORAGE_KEY, Date.now());
            this._saveToServer();
        }

        // Animate out
        if (this.overlay) {
            this.overlay.classList.remove('active');
            setTimeout(() => {
                if (this.overlay && this.overlay.parentNode) {
                    this.overlay.parentNode.removeChild(this.overlay);
                }
                this.overlay = null;
            }, 350);
        }

        document.body.style.overflow = '';
        document.removeEventListener('keydown', this._keyHandler);
        window.removeEventListener('resize', this._onResize);
        window.removeEventListener('scroll', this._onScroll, true);
    }

    /* ==================================================================
       PRIVATE — Build, render, navigate
       ================================================================== */

    _build() {
        // Remove existing
        var existing = document.getElementById('mc-app-tour');
        if (existing) existing.remove();

        var el = document.createElement('div');
        el.id = 'mc-app-tour';
        el.className = 'mc-app-tour';
        el.innerHTML =
            '<svg class="mc-tour-overlay" width="100%" height="100%">' +
                '<defs>' +
                    '<mask id="mc-tour-mask">' +
                        '<rect x="0" y="0" width="100%" height="100%" fill="white"/>' +
                        '<rect id="mc-tour-cutout" x="0" y="0" width="0" height="0" rx="12" fill="black"/>' +
                    '</mask>' +
                '</defs>' +
                '<rect x="0" y="0" width="100%" height="100%" fill="var(--color-overlay)" mask="url(#mc-tour-mask)"/>' +
            '</svg>' +
            '<div class="mc-tour-tooltip" style="visibility:hidden">' +
                '<div class="mc-tour-tooltip-arrow"></div>' +
                '<div class="mc-tour-tooltip-header">' +
                    '<div class="mc-tour-tooltip-icon"></div>' +
                    '<div class="mc-tour-tooltip-counter"></div>' +
                '</div>' +
                '<h3 class="mc-tour-tooltip-title"></h3>' +
                '<p class="mc-tour-tooltip-desc"></p>' +
                '<div class="mc-tour-tooltip-actions">' +
                    '<button class="mc-tour-btn mc-tour-btn--skip" data-tour-skip>Skip tour</button>' +
                    '<div class="mc-tour-tooltip-nav">' +
                        '<button class="mc-tour-btn mc-tour-btn--back" data-tour-back>' +
                            '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 2.5L4.5 7 9 11.5"/></svg>' +
                        '</button>' +
                        '<button class="mc-tour-btn mc-tour-btn--next" data-tour-next>Next</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

        document.body.appendChild(el);
        this.overlay = el;

        // Bind events
        el.querySelector('[data-tour-skip]').addEventListener('click', () => this.end());
        el.querySelector('[data-tour-next]').addEventListener('click', () => this._next());
        el.querySelector('[data-tour-back]').addEventListener('click', () => this._prev());

        // Click on overlay (outside tooltip) = skip
        el.querySelector('.mc-tour-overlay').addEventListener('click', (e) => {
            if (e.target.closest('.mc-tour-tooltip')) return;
            this.end();
        });

        // Keyboard
        this._keyHandler = (e) => {
            if (!this.isActive) return;
            if (e.key === 'Escape') this.end();
            if (e.key === 'ArrowRight' || e.key === 'Enter') this._next();
            if (e.key === 'ArrowLeft') this._prev();
        };
        document.addEventListener('keydown', this._keyHandler);

        // Reposition on resize/scroll
        this._onResize = () => {
            clearTimeout(this._resizeTimer);
            this._resizeTimer = setTimeout(() => {
                if (this.isActive) this._positionStep(this.current);
            }, 100);
        };
        this._onScroll = () => {
            clearTimeout(this._scrollTimer);
            this._scrollTimer = setTimeout(() => {
                if (this.isActive) this._positionStep(this.current);
            }, 50);
        };
        window.addEventListener('resize', this._onResize);
        window.addEventListener('scroll', this._onScroll, true);

        // Activate with animation
        requestAnimationFrame(() => {
            el.classList.add('active');
        });
        document.body.style.overflow = 'hidden';
    }

    _next() {
        if (this.current < this.steps.length - 1) {
            this._showStep(this.current + 1);
        } else {
            this.end();
        }
    }

    _prev() {
        if (this.current > 0) {
            this._showStep(this.current - 1);
        }
    }

    _showStep(index) {
        var step = this.steps[index];
        var targetEl = document.querySelector(step.target);

        // If target doesn't exist, skip to next/end
        if (!targetEl) {
            if (index < this.steps.length - 1) {
                this._showStep(index + 1);
            } else {
                this.end();
            }
            return;
        }

        this.current = index;

        // Scroll target into view smoothly
        targetEl.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });

        // Wait for scroll, then position
        setTimeout(() => this._positionStep(index), 350);
    }

    _positionStep(index) {
        if (!this.overlay || !this.isActive) return;

        var step = this.steps[index];
        var targetEl = document.querySelector(step.target);
        if (!targetEl) return;

        var rect = targetEl.getBoundingClientRect();
        var pad = step.spotlightPadding || 10;

        // Update SVG cutout (spotlight)
        var cutout = document.getElementById('mc-tour-cutout');
        if (cutout) {
            cutout.setAttribute('x', rect.left - pad);
            cutout.setAttribute('y', rect.top - pad);
            cutout.setAttribute('width', rect.width + pad * 2);
            cutout.setAttribute('height', rect.height + pad * 2);
            cutout.setAttribute('rx', step.target === '.mc-sidebar' ? '0' : '12');
        }

        // Update tooltip content
        var tooltip = this.overlay.querySelector('.mc-tour-tooltip');
        var counter = this.overlay.querySelector('.mc-tour-tooltip-counter');
        var title = this.overlay.querySelector('.mc-tour-tooltip-title');
        var desc = this.overlay.querySelector('.mc-tour-tooltip-desc');
        var icon = this.overlay.querySelector('.mc-tour-tooltip-icon');
        var backBtn = this.overlay.querySelector('[data-tour-back]');
        var nextBtn = this.overlay.querySelector('[data-tour-next]');
        var skipBtn = this.overlay.querySelector('[data-tour-skip]');

        counter.textContent = (index + 1) + ' / ' + this.steps.length;
        title.textContent = step.title;
        desc.textContent = step.desc;
        icon.innerHTML = this._icon(step.icon);
        icon.className = 'mc-tour-tooltip-icon mc-tour-tooltip-icon--' + step.color;

        // Back button visibility
        backBtn.style.display = index === 0 ? 'none' : '';

        // Last step = "Done" button
        var isLast = index === this.steps.length - 1;
        skipBtn.style.display = isLast ? 'none' : '';
        nextBtn.textContent = isLast ? 'Done' : 'Next';
        nextBtn.innerHTML = isLast
            ? 'Done <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 7.5l3 3L11.5 4"/></svg>'
            : 'Next <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 2.5L9.5 7 5 11.5"/></svg>';

        // Position tooltip relative to target
        this._positionTooltip(tooltip, rect, step.position, pad, step.offset);

        // Arrow positioning
        tooltip.setAttribute('data-position', step.position);

        // Reveal tooltip now that it's positioned
        tooltip.style.visibility = '';

        // Focus next button
        nextBtn.focus();
    }

    _positionTooltip(tooltip, rect, position, pad, offset) {
        var gap = 16;
        var tooltipW = 340;
        var ox = (offset && offset.x) || 0;
        var oy = (offset && offset.y) || 0;
        var vw = window.innerWidth;
        var vh = window.innerHeight;

        // Reset
        tooltip.style.top = '';
        tooltip.style.left = '';
        tooltip.style.right = '';
        tooltip.style.bottom = '';

        var top, left;

        switch (position) {
            case 'right':
                top = rect.top + rect.height / 2 - 80 + oy;
                left = rect.right + pad + gap + ox;
                // If overflows right, flip to bottom
                if (left + tooltipW > vw - 16) {
                    top = rect.bottom + pad + gap + oy;
                    left = Math.max(16, rect.left + rect.width / 2 - tooltipW / 2 + ox);
                    tooltip.setAttribute('data-position', 'bottom');
                }
                break;
            case 'left':
                top = rect.top + rect.height / 2 - 80 + oy;
                left = rect.left - pad - gap - tooltipW + ox;
                if (left < 16) {
                    top = rect.bottom + pad + gap + oy;
                    left = Math.max(16, rect.left + rect.width / 2 - tooltipW / 2 + ox);
                    tooltip.setAttribute('data-position', 'bottom');
                }
                break;
            case 'bottom':
                top = rect.bottom + pad + gap + oy;
                left = Math.max(16, rect.left + rect.width / 2 - tooltipW / 2 + ox);
                break;
            case 'top':
                top = rect.top - pad - gap + oy;
                left = Math.max(16, rect.left + rect.width / 2 - tooltipW / 2 + ox);
                tooltip.style.transform = 'translateY(-100%)';
                break;
            case 'bottom-end':
                top = rect.bottom + pad + gap + oy;
                left = rect.right - tooltipW + ox;
                if (left < 16) left = 16;
                break;
            default:
                top = rect.bottom + pad + gap + oy;
                left = Math.max(16, rect.left + ox);
        }

        // Clamp within viewport
        if (left + tooltipW > vw - 16) left = vw - tooltipW - 16;
        if (left < 16) left = 16;
        if (top < 16) top = 16;
        if (position !== 'top' && top + 200 > vh) top = vh - 220;

        tooltip.style.top = top + 'px';
        tooltip.style.left = left + 'px';
        if (position !== 'top') tooltip.style.transform = '';
    }

    _saveToServer() {
        var tokenMeta = document.querySelector('meta[name="csrf-token"]');
        var token = tokenMeta ? tokenMeta.getAttribute('content') : '';
        var saveUrl = this.overlay ? this.overlay.getAttribute('data-save-url') : null;
        if (!saveUrl) {
            // Derive from current page
            saveUrl = '/rui/account/tour-complete';
        }

        var formData = new FormData();
        formData.append('_token', token);
        formData.append('tour_completed', '1');

        fetch(saveUrl, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        }).catch(function() {
            // Silent fail — localStorage already has the state
        });
    }

    /* ==================================================================
       SVG Icons (inline, matches mc-icon style)
       ================================================================== */
    _icon(name) {
        var icons = {
            home: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5L10 4l7 6.5"/><path d="M5 9v7a1 1 0 001 1h3v-4h2v4h3a1 1 0 001-1V9"/></svg>',
            chart: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="10" width="3" height="7" rx="0.5"/><rect x="8.5" y="6" width="3" height="11" rx="0.5"/><rect x="14" y="3" width="3" height="14" rx="0.5"/></svg>',
            mail: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="16" height="12" rx="2"/><path d="M2 7l8 4.5L18 7"/></svg>',
            search: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="8.5" cy="8.5" r="5.5"/><path d="M13 13l4.5 4.5"/></svg>',
            palette: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="10" r="8"/><circle cx="10" cy="5.5" r="1.5" fill="currentColor"/><circle cx="6" cy="8" r="1.5" fill="currentColor"/><circle cx="14" cy="8" r="1.5" fill="currentColor"/><circle cx="7" cy="12.5" r="1.5" fill="currentColor"/></svg>',
            group: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="7" cy="7" r="3"/><path d="M1 17c0-2.8 2.7-5 6-5s6 2.2 6 5"/><circle cx="14.5" cy="7.5" r="2.5"/><path d="M15 12c2.2.4 4 2 4 4"/></svg>',
            settings: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="10" r="2.5"/><path d="M10 2v2.5M10 15.5V18M2 10h2.5M15.5 10H18M4.34 4.34l1.77 1.77M13.89 13.89l1.77 1.77M4.34 15.66l1.77-1.77M13.89 6.11l1.77-1.77"/></svg>',
        };
        return icons[name] || icons.home;
    }
}

window.McAppTour = new AppTour();
