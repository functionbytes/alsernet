/**
 * McTabs — Lightweight tab switcher with event delegation.
 *
 * Usage (declarative — auto-inits via McTabs.init()):
 *
 *   <div class="mc-tabs" data-mc-tabs>
 *       <button class="mc-tab active" data-tab="basic">Basic</button>
 *       <button class="mc-tab" data-tab="featured">Featured</button>
 *   </div>
 *
 * Listens for clicks on [data-tab] children. On click:
 *   1. Toggles .active on the clicked tab
 *   2. Dispatches 'tab:change' custom event on the container
 *      → detail: { tab: 'featured', el: <button> }
 *
 * Use the event in your page JS:
 *   tabsEl.addEventListener('tab:change', (e) => {
 *       loadContent(e.detail.tab);
 *   });
 *
 * Optional: pair with panels via data-tab-panel:
 *   <div data-tab-panel="basic">...</div>
 *   <div data-tab-panel="featured" style="display:none">...</div>
 *   → Panels auto-show/hide when tabs change (if data-tab-panels is on container)
 */
class McTabs {
    constructor(el) {
        this.el = typeof el === 'string' ? document.querySelector(el) : el;
        if (!this.el || this.el._mcTabs) return;
        this.el._mcTabs = this;

        this.panelContainer = this.el.dataset.tabPanels
            ? document.querySelector(this.el.dataset.tabPanels)
            : null;

        this._bind();
    }

    static init() {
        document.querySelectorAll('[data-mc-tabs]').forEach(function(el) {
            new McTabs(el);
        });
    }

    _bind() {
        var self = this;
        this.el.addEventListener('click', function(e) {
            var tab = e.target.closest('[data-tab]');
            if (!tab || tab.classList.contains('active')) return;

            // Toggle active — works for .mc-tab and .mc-segmented-item
            self.el.querySelectorAll('[data-tab]').forEach(function(t) {
                t.classList.remove('active');
            });
            tab.classList.add('active');

            // Toggle panels if configured
            if (self.panelContainer) {
                self.panelContainer.querySelectorAll('[data-tab-panel]').forEach(function(p) {
                    p.style.display = p.dataset.tabPanel === tab.dataset.tab ? '' : 'none';
                });
            }

            // Dispatch event
            self.el.dispatchEvent(new CustomEvent('tab:change', {
                bubbles: true,
                detail: { tab: tab.dataset.tab, el: tab }
            }));
        });
    }

    /** Programmatically activate a tab by its data-tab value */
    activate(tabValue) {
        var tab = this.el.querySelector('[data-tab="' + tabValue + '"]');
        if (tab && !tab.classList.contains('active')) {
            tab.click();
        }
    }
}

window.McTabs = McTabs;
