/**
 * Sidebar — Handles sidebar expand/collapse and mobile behavior.
 */
class Sidebar {
    constructor() {
        this.app = null;
        this.sidebar = null;
        this.overlay = null;
        this.toggleBtn = null;
        this.isMobileOpen = false;
    }

    /**
     * Initialize sidebar behavior.
     */
    init() {
        this.app = document.querySelector('.mc-app');
        this.sidebar = document.querySelector('.mc-sidebar');
        this.overlay = document.querySelector('.mc-sidebar-overlay');

        if (!this.app || !this.sidebar) return;

        // Toggle buttons (mobile only)
        document.querySelectorAll('[data-sidebar-toggle]').forEach(btn => {
            btn.addEventListener('click', () => this.toggleMobile());
        });

        // Smart toggle — collapse on desktop, overlay open/close on tablet+mobile
        document.querySelectorAll('[data-sidebar-smart-toggle]').forEach(btn => {
            btn.addEventListener('click', () => {
                if (window.innerWidth <= 1199) {
                    this.toggleMobile();
                } else {
                    this.toggleCollapse();
                }
            });
        });

        // Overlay click closes mobile sidebar
        if (this.overlay) {
            this.overlay.addEventListener('click', () => this.closeMobile());
        }

        // Submenu toggles
        this.sidebar.querySelectorAll('[data-nav-toggle]').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleSubmenu(item);
            });
        });

        // Desktop collapse toggle
        document.querySelectorAll('[data-sidebar-collapse]').forEach(btn => {
            btn.addEventListener('click', () => this.toggleCollapse());
        });

        // Restore collapsed state — desktop only (>767px)
        this._applyCollapseState();

        // Bring the active nav item into view if it's below the fold of the
        // sidebar's own scroll container (server-rendered nav resets to top
        // on each navigation; deep-list items would otherwise stay hidden).
        this._scrollActiveIntoView();

        // On resize: close overlay sidebar at full desktop, reapply collapse state
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1200 && this.isMobileOpen) {
                this.closeMobile();
            }
            this._applyCollapseState();
        });

        // Close mobile sidebar on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isMobileOpen) {
                this.closeMobile();
            }
        });
    }

    /**
     * Toggle mobile sidebar open/close.
     */
    toggleMobile() {
        if (this.isMobileOpen) {
            this.closeMobile();
        } else {
            this.openMobile();
        }
    }

    /**
     * Open mobile sidebar.
     */
    openMobile() {
        this.app.classList.add('sidebar-mobile-open');
        this.isMobileOpen = true;
        document.body.style.overflow = 'hidden';
    }

    /**
     * Close mobile sidebar.
     */
    closeMobile() {
        this.app.classList.remove('sidebar-mobile-open');
        this.isMobileOpen = false;
        document.body.style.overflow = '';
    }

    /**
     * Toggle desktop collapsed state (ignored on mobile).
     */
    toggleCollapse() {
        if (window.innerWidth <= 1199) return; // no collapse on tablet/mobile
        this.app.classList.toggle('sidebar-collapsed');
        const isCollapsed = this.app.classList.contains('sidebar-collapsed');
        localStorage.setItem('mc-sidebar-collapsed', isCollapsed);
        this._syncCollapsedTooltips();
    }

    /**
     * Apply or remove collapsed state based on screen size.
     * Mobile (<768px): NEVER collapsed — always full width when open.
     * Desktop/tablet: respect localStorage preference.
     *
     * Also handshakes the FOUC pre-paint marker (`<html data-sidebar-collapsed>`
     * set by an inline script in `layouts/{admin,app}.blade.php`'s <head>):
     * when present, mirror to the canonical `.mc-app.sidebar-collapsed`
     * class then strip the html attr so further toggles use the standard
     * class-based selectors. This eliminates the expand → collapse
     * transition flash on page refresh / navigation.
     */
    _applyCollapseState() {
        var preCollapsed = document.documentElement.getAttribute('data-sidebar-collapsed') === 'true';

        if (window.innerWidth <= 1199) {
            this.app.classList.remove('sidebar-collapsed');
        } else if (preCollapsed || localStorage.getItem('mc-sidebar-collapsed') === 'true') {
            this.app.classList.add('sidebar-collapsed');
        }

        if (preCollapsed) {
            // Strip the FOUC marker — sidebar.css's `transition: none` rule was
            // scoped to it; transitions re-enable on the next frame so the user-
            // driven toggle (collapse button) animates as expected.
            document.documentElement.removeAttribute('data-sidebar-collapsed');
        }

        this._syncCollapsedTooltips();
    }

    /**
     * Mirror each nav-item's label text into a `data-tooltip` attribute
     * when the sidebar is collapsed (so Tooltip.js shows the label on
     * icon hover) and strip the attribute when uncollapsed (label is
     * visible inline — tooltip would be redundant noise).
     *
     * Tooltip.js positions `fixed` + appends the tooltip element to
     * `document.body`, so it escapes the sidebar's own scroll-clip
     * automatically. We pin `data-tooltip-position="right"` so the
     * popout opens away from the sidebar even on narrow viewports.
     *
     * Group labels (`.mc-nav-group-label`) are deliberately skipped —
     * they're section dividers, not interactive, and show no icon when
     * collapsed (display:none in collapsed CSS).
     */
    _syncCollapsedTooltips() {
        const isCollapsed = this.app.classList.contains('sidebar-collapsed');
        const items = this.sidebar.querySelectorAll('.mc-nav-item');
        items.forEach(item => {
            if (isCollapsed) {
                // Pull the visible label text from the inline span. Falls
                // back to aria-label or title if that span has been customised.
                const labelEl = item.querySelector('.mc-nav-item-label');
                const label = labelEl
                    ? labelEl.textContent.trim()
                    : (item.getAttribute('aria-label') || item.getAttribute('title') || '');
                if (label) {
                    item.setAttribute('data-tooltip', label);
                    item.setAttribute('data-tooltip-position', 'right');
                    item.setAttribute('data-tooltip-offset', '12');
                }
            } else {
                item.removeAttribute('data-tooltip');
                item.removeAttribute('data-tooltip-position');
                item.removeAttribute('data-tooltip-offset');
            }
        });
    }

    /**
     * Scroll the active nav item into view inside the sidebar scroll
     * container. No-op when already visible (top items shouldn't jump).
     * When scroll IS needed, place the item ~1/3 down the visible area
     * instead of flush to an edge — `scrollIntoView({block:'nearest'})`
     * was leaving deep items pinned to the bottom rim, which looked
     * cramped against the user-profile footer.
     */
    _scrollActiveIntoView() {
        const container = this.sidebar.querySelector('.mc-sidebar-nav');
        const active = container && container.querySelector('.mc-nav-item.active');
        if (!container || !active) return;

        const cRect = container.getBoundingClientRect();
        const aRect = active.getBoundingClientRect();
        if (aRect.top >= cRect.top && aRect.bottom <= cRect.bottom) return;

        const itemOffset = (aRect.top - cRect.top) + container.scrollTop;
        container.scrollTop = itemOffset - container.clientHeight / 3;
    }

    /**
     * Toggle a submenu open/close.
     * @param {HTMLElement} item - The nav item with submenu
     */
    toggleSubmenu(item) {
        const wasExpanded = item.classList.contains('expanded');

        // Close all other submenus at same level
        const siblings = item.parentElement.querySelectorAll(':scope > .mc-nav-item.expanded');
        siblings.forEach(s => s.classList.remove('expanded'));

        if (!wasExpanded) {
            item.classList.add('expanded');
        }
    }
}

// Export singleton
window.McSidebar = new Sidebar();
