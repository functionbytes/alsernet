/**
 * Dropdown — Click-toggle dropdown menus with fixed positioning.
 *
 * Dropdowns inside overflow containers (tables, cards) use position:fixed
 * to escape clipping. Position is calculated from trigger's viewport rect.
 *
 * Usage in HTML:
 *   <div class="mc-dropdown" data-dropdown>
 *       <button>Toggle</button>
 *       <div class="mc-dropdown-menu">
 *           <a class="mc-dropdown-item" href="#">Item</a>
 *       </div>
 *   </div>
 */
class Dropdown {
    constructor() {
        this.activeDropdown = null;
        this._initialized = false;
        this._scrollHandler = null;
    }

    /**
     * Initialize dropdown listeners (idempotent — safe to call multiple times).
     */
    init() {
        if (this._initialized) return;
        this._initialized = true;

        // Click on dropdown trigger
        document.addEventListener('click', (e) => {
            const dropdown = e.target.closest('[data-dropdown]');

            if (dropdown) {
                const menu = dropdown.querySelector('.mc-dropdown-menu');
                if (!menu) return;

                // If clicking a link, let it navigate (menu items OR btn-group main action)
                var clickedLink = e.target.closest('a[href]');
                if (clickedLink && (menu.contains(clickedLink) || clickedLink.classList.contains('mc-btn-group-main'))) {
                    this.closeAll();
                    return; // don't preventDefault — let the browser navigate
                }

                e.preventDefault();
                e.stopPropagation();

                if (menu.classList.contains('active')) {
                    this.close(menu);
                } else {
                    this.closeAll();
                    this.open(menu, dropdown);
                }
                return;
            }

            // Click outside — close all
            this.closeAll();
        });

        // Escape key closes
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAll();
            }
        });
    }

    /**
     * Check if element is inside an overflow container.
     */
    _isInOverflow(el) {
        var parent = el.parentElement;
        while (parent && parent !== document.body) {
            var overflow = getComputedStyle(parent).overflowX;
            if (overflow === 'auto' || overflow === 'scroll' || overflow === 'hidden') {
                return true;
            }
            parent = parent.parentElement;
        }
        return false;
    }

    /**
     * Check if viewport is mobile-sized.
     */
    _isMobile() {
        return window.innerWidth <= 767;
    }

    /**
     * Open a dropdown menu.
     * Inside overflow containers (tables): always use position:fixed (desktop + mobile).
     * Mobile only (no overflow): use bottom sheet.
     * Otherwise: standard position:absolute.
     */
    open(menu, dropdown) {
        if (dropdown && this._isInOverflow(dropdown)) {
            // Always escape overflow with fixed positioning — on mobile too.
            // The dropdown comes from the button, not a bottom sheet.
            this._openFixed(menu, dropdown);
        } else if (this._isMobile()) {
            this._openMobile(menu);
        } else {
            menu.classList.add('active');
        }
        this.activeDropdown = menu;
    }

    /**
     * Open dropdown as mobile bottom sheet with overlay.
     */
    _openMobile(menu) {
        // Create overlay if not exists
        var overlay = document.querySelector('.mc-dropdown-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'mc-dropdown-overlay';
            document.body.appendChild(overlay);
        }

        menu.classList.add('mc-dropdown-mobile');
        overlay.classList.add('active');

        // Force reflow then animate in
        menu.offsetHeight;
        menu.classList.add('active');

        // Close on overlay click
        overlay.onclick = () => this.closeAll();

        // Lock body scroll
        document.body.style.overflow = 'hidden';
    }

    /**
     * Open dropdown with fixed positioning (escapes overflow).
     * Coordinates are set BEFORE the active class to avoid a 1-frame jump.
     */
    _openFixed(menu, dropdown) {
        var trigger = dropdown.querySelector('.mc-btn-group, button, [class*="btn"]') || dropdown;
        var rect = trigger.getBoundingClientRect();
        var isUp = menu.classList.contains('mc-dropdown-menu-up');

        // If inside a modal (which uses transform, creating a new containing block
        // for position:fixed), move menu to body to avoid coordinate mismatch.
        var modal = dropdown.closest('.mc-modal');
        if (modal && !menu._originalParent) {
            menu._originalParent = menu.parentElement;
            menu._originalNextSibling = menu.nextSibling;
            document.body.appendChild(menu);
        }

        // Step 1: Apply fixed class while menu is still invisible (no active yet).
        menu.classList.add('mc-dropdown-fixed');

        // Step 2: Set coordinates BEFORE triggering the animation.
        if (isUp) {
            menu.style.bottom = (window.innerHeight - rect.top + 4) + 'px';
            menu.style.left = rect.left + 'px';
            menu.style.top = 'auto';
            menu.style.right = 'auto';
        } else {
            menu.style.top = (rect.bottom + 4) + 'px';
            menu.style.right = (window.innerWidth - rect.right) + 'px';
            menu.style.bottom = 'auto';
            menu.style.left = 'auto';
        }

        // Step 3: Force layout so the browser registers the position before animating.
        menu.offsetHeight;

        // Step 4: Flip up if going below viewport; clamp left edge to viewport.
        if (!isUp) {
            var menuRect = menu.getBoundingClientRect();
            if (menuRect.bottom > window.innerHeight - 8) {
                menu.style.top = (rect.top - menuRect.height - 4) + 'px';
            }
            // Prevent overflow past left edge (e.g. on narrow mobile screens)
            menuRect = menu.getBoundingClientRect();
            if (menuRect.left < 8) {
                menu.style.right = 'auto';
                menu.style.left = '8px';
            }
        }

        // Step 5: Now animate in — CSS transition runs from the correct position.
        menu.classList.add('active');

        // Close on scroll — debounce slightly to avoid immediate close.
        // Skip scrolls whose target is the menu itself or any descendant of
        // it: the menu has its own overflow:auto for tall option lists (e.g.
        // timezone picker), and we shouldn't close it when the user scrolls
        // through its options. We DO want to close on outer-page scroll
        // because the menu is fixed-positioned relative to the viewport and
        // would detach from its trigger otherwise.
        var scrollTimeout = null;
        this._scrollHandler = (e) => {
            if (e.target && e.target.nodeType === 1 && menu.contains(e.target)) return;
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => this.closeAll(), 50);
        };
        // Delay attaching scroll listener to avoid immediate trigger
        setTimeout(() => {
            window.addEventListener('scroll', this._scrollHandler, { capture: true, passive: true });
        }, 100);
    }

    /**
     * Close a dropdown menu.
     */
    close(menu) {
        menu.classList.remove('active');
        menu.classList.remove('mc-dropdown-fixed');
        menu.classList.remove('mc-dropdown-mobile');
        menu.style.top = '';
        menu.style.right = '';
        menu.style.bottom = '';
        menu.style.left = '';

        if (this.activeDropdown === menu) {
            this.activeDropdown = null;
        }

        // Move menu back to original parent if it was relocated for modal positioning
        if (menu._originalParent) {
            if (menu._originalNextSibling) {
                menu._originalParent.insertBefore(menu, menu._originalNextSibling);
            } else {
                menu._originalParent.appendChild(menu);
            }
            delete menu._originalParent;
            delete menu._originalNextSibling;
        }

        // Remove scroll listener
        if (this._scrollHandler) {
            window.removeEventListener('scroll', this._scrollHandler, { capture: true });
            this._scrollHandler = null;
        }

        // Clean up mobile overlay + body scroll
        var overlay = document.querySelector('.mc-dropdown-overlay');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    /**
     * Close all open dropdowns.
     */
    closeAll() {
        document.querySelectorAll('.mc-dropdown-menu.active').forEach(menu => {
            this.close(menu);
        });
    }
}

// Export singleton
window.McDropdown = new Dropdown();
