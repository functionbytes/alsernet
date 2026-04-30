/**
 * List — AJAX paginated list with search, sort, bulk select, and row actions.
 *
 * Usage:
 *   new McList({
 *       container: '#my-list',
 *       url: '/api/items',
 *       perPage: 10,
 *       columns: ['name', 'status', 'created_at'],
 *       onRender: (items) => { ... },
 *   });
 *
 * Or declarative:
 *   <div data-mc-list data-url="/api/items" data-per-page="10">
 *       <div data-list-content></div>
 *       <div data-list-pagination></div>
 *   </div>
 */
class McList {
    constructor(options = {}) {
        this.container = typeof options.container === 'string'
            ? document.querySelector(options.container)
            : options.container;

        if (!this.container) return;

        this.url = options.url || this.container.dataset.url;
        this.perPage = options.perPage || parseInt(this.container.dataset.perPage) || 10;
        this.page = 1;
        this.sort = options.sort || '';
        this.sortDir = options.sortDir || 'desc';
        this.search = '';
        this.filters = {};
        this.selected = new Set();
        this.selectAll = false;
        this.loading = false;

        // Callbacks
        this.onRender = options.onRender || null;
        this.onLoad = options.onLoad || null;
        this.onError = options.onError || null;

        // Elements
        this.contentEl = this.container.querySelector('[data-list-content]');
        this.paginationEl = this.container.querySelector('[data-list-pagination]');
        this.searchInput = this.container.querySelector('[data-list-search]');
        this.loadingEl = this.container.querySelector('[data-list-loading]');
        this.infoEl = this.container.querySelector('[data-list-info]');

        this._bindEvents();
    }

    /**
     * Bind DOM events.
     */
    _bindEvents() {
        // Search input with debounce
        if (this.searchInput) {
            let timeout;
            this.searchInput.addEventListener('input', () => {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    this.search = this.searchInput.value;
                    this.page = 1;
                    this.load();
                }, 300);
            });
        }

        // Per-page select
        const perPageSelect = this.container.querySelector('[data-list-per-page]');
        if (perPageSelect) {
            perPageSelect.addEventListener('change', () => {
                this.perPage = parseInt(perPageSelect.value);
                this.page = 1;
                this.load();
            });
        }

        // Select all checkbox
        const selectAllCb = this.container.querySelector('[data-list-select-all]');
        if (selectAllCb) {
            selectAllCb.addEventListener('change', () => {
                this.selectAll = selectAllCb.checked;
                this._toggleAllCheckboxes(this.selectAll);
            });
        }
    }

    /**
     * Load data from server.
     */
    async load() {
        if (this.loading) return;
        this.loading = true;
        this._showLoading(true);

        const params = new URLSearchParams({
            page: this.page,
            per_page: this.perPage,
            keyword: this.search,
            sort_order: this.sort,
            sort_direction: this.sortDir,
            ...this.filters
        });

        try {
            const response = await fetch(`${this.url}?${params}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const html = await response.text();

            if (this.contentEl) {
                this.contentEl.innerHTML = html;
                this._bindRowEvents();
            }

            if (this.onLoad) this.onLoad(html);
        } catch (error) {
            console.error('McList load error:', error);
            if (this.onError) this.onError(error);
            if (window.McNotify) McNotify.error('Failed to load data');
        } finally {
            this.loading = false;
            this._showLoading(false);
        }
    }

    /**
     * Go to specific page.
     * @param {number} page
     */
    goToPage(page) {
        this.page = page;
        this.load();
    }

    /**
     * Set sort column and direction.
     * @param {string} column
     * @param {string} direction
     */
    setSort(column, direction = 'asc') {
        this.sort = column;
        this.sortDir = direction;
        this.page = 1;
        this.load();
    }

    /**
     * Set a filter value.
     * @param {string} key
     * @param {string} value
     */
    setFilter(key, value) {
        this.filters[key] = value;
        this.page = 1;
        this.load();
    }

    /**
     * Alias for setFilter — used by automations/forms views.
     */
    setParam(key, value) {
        return this.setFilter(key, value);
    }

    /**
     * Get selected item IDs.
     * @returns {Array}
     */
    getSelected() {
        return Array.from(this.selected);
    }

    /**
     * Refresh current page.
     */
    refresh() {
        this.load();
    }

    /**
     * Show/hide loading indicator.
     */
    _showLoading(show) {
        if (this.loadingEl) {
            this.loadingEl.style.display = show ? 'flex' : 'none';
        }
        if (this.contentEl) {
            this.contentEl.style.opacity = show ? '0.5' : '1';
        }
    }

    /**
     * Bind events on row elements after content load.
     */
    _bindRowEvents() {
        // Row checkboxes
        this.contentEl.querySelectorAll('[data-list-check]').forEach(cb => {
            cb.addEventListener('change', () => {
                const id = cb.dataset.listCheck;
                if (cb.checked) {
                    this.selected.add(id);
                } else {
                    this.selected.delete(id);
                }
            });
            // Restore checked state
            if (this.selected.has(cb.dataset.listCheck)) {
                cb.checked = true;
            }
        });

        // Pagination links
        this.contentEl.querySelectorAll('[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                this.goToPage(parseInt(link.dataset.page));
            });
        });

        // Sort links
        this.contentEl.querySelectorAll('[data-sort]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const col = link.dataset.sort;
                const dir = (this.sort === col && this.sortDir === 'asc') ? 'desc' : 'asc';
                this.setSort(col, dir);
            });
        });
    }

    /**
     * Toggle all checkboxes.
     */
    _toggleAllCheckboxes(checked) {
        this.contentEl.querySelectorAll('[data-list-check]').forEach(cb => {
            cb.checked = checked;
            const id = cb.dataset.listCheck;
            if (checked) {
                this.selected.add(id);
            } else {
                this.selected.delete(id);
            }
        });
    }
}

// Export
window.McList = McList;
