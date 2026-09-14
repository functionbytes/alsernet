/**
 * PowerSearch — Command Palette (Cmd+K / Ctrl+K)
 *
 * A Spotlight/Linear-style search palette that searches across all entities,
 * provides quick actions, page navigation, and recent searches.
 *
 * Usage (from layout blade):
 *   window.McPowerSearch = new PowerSearch({
 *       endpoint: '/rui/power-search',
 *       mode: 'customer',       // or 'admin'
 *       triggerId: 'power-search-trigger',
 *       translations: { ... },
 *       pages: [ { title, url, icon, keywords } ],
 *       actions: [ { title, url, icon } ],
 *   });
 */
(function () {
    'use strict';

    // Badge color map — maps to mc-badge-{color} classes
    var BADGE_COLORS = {
        green: 'mc-badge-green',
        red: 'mc-badge-red',
        orange: 'mc-badge-orange',
        blue: 'mc-badge-blue',
        default: 'mc-badge-default',
    };

    // Detect Mac vs Windows/Linux for shortcut labels
    var IS_MAC = /Mac|iPhone|iPad|iPod/i.test(navigator.platform || navigator.userAgent || '');
    var SHORTCUT_LABEL = IS_MAC ? '\u2318K' : 'Ctrl+K';

    function PowerSearch(options) {
        this.endpoint = options.endpoint || '';
        this.mode = options.mode || 'customer';
        this.triggerId = options.triggerId || 'power-search-trigger';
        this.translations = options.translations || {};
        this.pages = options.pages || [];
        this.actions = options.actions || [];

        this._isOpen = false;
        this._activeIndex = -1;
        this._flatItems = [];       // all rendered items for keyboard nav
        this._debounceTimer = null;
        this._abortController = null;
        this._recentKey = 'mc_power_search_recent_' + this.mode;

        this._buildDOM();
        this._bindEvents();
        this._fixShortcutLabels();
    }

    // ================================================================
    // DOM construction
    // ================================================================

    PowerSearch.prototype._buildDOM = function () {
        // Backdrop
        this._backdrop = document.createElement('div');
        this._backdrop.className = 'mc-power-search-backdrop';

        // Container
        this._container = document.createElement('div');
        this._container.className = 'mc-power-search';
        this._container.setAttribute('role', 'dialog');
        this._container.setAttribute('aria-modal', 'true');
        this._container.setAttribute('aria-label', 'Search');

        // Header
        var header = document.createElement('div');
        header.className = 'mc-power-search-header';
        header.innerHTML =
            '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">' +
            '<circle cx="9" cy="9" r="6"/><path d="M13.5 13.5L18 18"/></svg>';

        this._input = document.createElement('input');
        this._input.type = 'text';
        this._input.className = 'mc-power-search-input';
        this._input.placeholder = this._t('placeholder', 'Search or jump to...');
        this._input.setAttribute('autocomplete', 'off');
        this._input.setAttribute('spellcheck', 'false');
        header.appendChild(this._input);

        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'mc-power-search-close';
        closeBtn.setAttribute('aria-label', 'Close');
        closeBtn.innerHTML = '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M5 5l10 10M15 5L5 15"/></svg>';
        var self = this;
        closeBtn.addEventListener('click', function () { self.close(); });
        header.appendChild(closeBtn);

        // Body (scrollable)
        this._body = document.createElement('div');
        this._body.className = 'mc-power-search-body';

        // Footer
        var footer = document.createElement('div');
        footer.className = 'mc-power-search-footer';
        footer.innerHTML =
            '<span class="mc-power-search-footer-hint"><kbd>\u2191\u2193</kbd> ' + this._t('footer.navigate', 'to navigate') + '</span>' +
            '<span class="mc-power-search-footer-hint"><kbd>\u21B5</kbd> ' + this._t('footer.select', 'to select') + '</span>' +
            '<span class="mc-power-search-footer-hint"><kbd>esc</kbd> ' + this._t('footer.close', 'to close') + '</span>';

        // Assemble
        this._container.appendChild(header);
        this._container.appendChild(this._body);
        this._container.appendChild(footer);

        document.body.appendChild(this._backdrop);
        document.body.appendChild(this._container);
    };

    // ================================================================
    // Event binding
    // ================================================================

    PowerSearch.prototype._bindEvents = function () {
        var self = this;

        // Global Cmd+K / Ctrl+K
        document.addEventListener('keydown', function (e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                e.stopPropagation();
                self.toggle();
            }
        });

        // Trigger button click
        var trigger = document.getElementById(this.triggerId);
        if (trigger) {
            trigger.addEventListener('click', function (e) {
                e.preventDefault();
                self.open();
            });
        }

        // Backdrop click
        this._backdrop.addEventListener('click', function () {
            self.close();
        });

        // Input events
        this._input.addEventListener('input', function () {
            self._onInput();
        });

        // Keyboard navigation
        this._input.addEventListener('keydown', function (e) {
            switch (e.key) {
                case 'Escape':
                    e.preventDefault();
                    self.close();
                    break;
                case 'ArrowDown':
                    e.preventDefault();
                    self._moveActive(1);
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    self._moveActive(-1);
                    break;
                case 'Enter':
                    e.preventDefault();
                    self._selectActive();
                    break;
            }
        });
    };

    // ================================================================
    // OS-aware shortcut labels (⌘K on Mac, Ctrl+K on Windows/Linux)
    // ================================================================

    PowerSearch.prototype._fixShortcutLabels = function () {
        // Update topbar badge(s)
        var badges = document.querySelectorAll('.js-search-shortcut');
        for (var i = 0; i < badges.length; i++) {
            badges[i].textContent = SHORTCUT_LABEL;
        }
    };

    // ================================================================
    // Open / Close / Toggle
    // ================================================================

    PowerSearch.prototype.open = function () {
        if (this._isOpen) return;
        this._isOpen = true;

        this._backdrop.classList.add('is-open');
        this._container.classList.add('is-open');
        document.body.style.overflow = 'hidden';

        this._input.value = '';
        this._input.focus();
        this._showDefault();
    };

    PowerSearch.prototype.close = function () {
        if (!this._isOpen) return;
        this._isOpen = false;

        this._backdrop.classList.remove('is-open');
        this._container.classList.remove('is-open');
        document.body.style.overflow = '';

        if (this._abortController) {
            this._abortController.abort();
            this._abortController = null;
        }
        clearTimeout(this._debounceTimer);
    };

    PowerSearch.prototype.toggle = function () {
        if (this._isOpen) this.close(); else this.open();
    };

    // ================================================================
    // Search logic
    // ================================================================

    PowerSearch.prototype._onInput = function () {
        var self = this;
        var q = this._input.value.trim();

        clearTimeout(this._debounceTimer);

        if (!q) {
            this._showDefault();
            return;
        }

        // Client-side instant filter of pages + actions
        var clientCategories = this._filterClientSide(q);

        // Render client results immediately
        this._render(clientCategories, true);

        // Debounce server search
        this._debounceTimer = setTimeout(function () {
            self._fetchServer(q, clientCategories);
        }, 200);
    };

    PowerSearch.prototype._filterClientSide = function (q) {
        var categories = [];
        var qLower = q.toLowerCase();
        var qWords = qLower.split(/\s+/).filter(Boolean);

        // Filter pages — search title + description + keywords (supports any language in title, English keywords always available)
        var matchedPages = this.pages.filter(function (p) {
            var haystack = (p.title + ' ' + (p.description || '') + ' ' + (p.keywords || '')).toLowerCase();
            return qWords.every(function (w) { return haystack.indexOf(w) !== -1; });
        });
        if (matchedPages.length > 0) {
            categories.push({
                key: 'pages',
                label: this._t('section.pages', 'Pages'),
                icon: 'article',
                items: matchedPages.slice(0, 6).map(function (p) {
                    return { type: 'page', title: p.title, description: p.description || '', url: p.url, icon: p.icon || 'article' };
                }),
            });
        }

        // Filter actions — search title + description
        var matchedActions = this.actions.filter(function (a) {
            var haystack = (a.title + ' ' + (a.description || '')).toLowerCase();
            return qWords.every(function (w) { return haystack.indexOf(w) !== -1; });
        });
        if (matchedActions.length > 0) {
            categories.push({
                key: 'actions',
                label: this._t('section.quick_actions', 'Quick Actions'),
                icon: 'flash_on',
                items: matchedActions.slice(0, 5).map(function (a) {
                    return { type: 'action', title: a.title, description: a.description || '', url: a.url, icon: a.icon || 'add_circle' };
                }),
            });
        }

        return categories;
    };

    PowerSearch.prototype._fetchServer = function (q, clientCategories) {
        var self = this;

        if (this._abortController) {
            this._abortController.abort();
        }
        this._abortController = new AbortController();

        var url = this.endpoint + '?keyword=' + encodeURIComponent(q);

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            signal: this._abortController.signal,
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            // Merge client-side categories + server categories
            var merged = clientCategories.concat(data.categories || []);
            self._render(merged, false);
        })
        .catch(function (err) {
            if (err.name !== 'AbortError') {
                console.error('[PowerSearch]', err);
            }
        });
    };

    // ================================================================
    // Show default state (recent + actions + pages)
    // ================================================================

    PowerSearch.prototype._showDefault = function () {
        var categories = [];

        // Recent searches
        var recents = this._getRecents();
        if (recents.length > 0) {
            categories.push({
                key: 'recent',
                label: this._t('section.recent', 'Recent'),
                icon: 'history',
                items: recents.map(function (r) {
                    return { type: r.type || 'page', title: r.title, subtitle: r.subtitle || '', url: r.url, icon: r.icon || 'history' };
                }),
            });
        }

        // Quick actions (show all)
        if (this.actions.length > 0) {
            categories.push({
                key: 'actions',
                label: this._t('section.quick_actions', 'Quick Actions'),
                icon: 'flash_on',
                items: this.actions.map(function (a) {
                    return { type: 'action', title: a.title, description: a.description || '', url: a.url, icon: a.icon || 'add_circle' };
                }),
            });
        }

        // Pages (show top 8)
        if (this.pages.length > 0) {
            categories.push({
                key: 'pages',
                label: this._t('section.pages', 'Pages'),
                icon: 'article',
                items: this.pages.slice(0, 8).map(function (p) {
                    return { type: 'page', title: p.title, description: p.description || '', url: p.url, icon: p.icon || 'article' };
                }),
            });
        }

        this._render(categories, false);
    };

    // ================================================================
    // Rendering
    // ================================================================

    PowerSearch.prototype._render = function (categories, isPartial) {
        var html = '';
        this._flatItems = [];

        if (!categories || categories.length === 0) {
            if (isPartial) {
                // No client-side matches yet but server search is pending — show spinner
                html = '<div class="mc-power-search-loading"><div class="mc-spinner"></div></div>';
            } else {
                html = this._renderEmpty();
            }
            this._body.innerHTML = html;
            this._activeIndex = -1;
            return;
        }

        for (var i = 0; i < categories.length; i++) {
            var cat = categories[i];
            if (!cat.items || cat.items.length === 0) continue;

            // Section header
            html += '<div class="mc-power-search-section-label">' +
                this._esc(cat.label);
            if (cat.total) {
                html += ' <span class="mc-power-search-section-count">(' + cat.total + ')</span>';
            }
            html += '</div>';

            // Items
            for (var j = 0; j < cat.items.length; j++) {
                var item = cat.items[j];
                var idx = this._flatItems.length;
                this._flatItems.push(item);
                html += this._renderItem(item, idx);
            }
        }

        if (isPartial) {
            html += '<div class="mc-power-search-loading"><div class="mc-spinner"></div></div>';
        }

        this._body.innerHTML = html;
        this._activeIndex = -1;

        // Bind click on items
        var self = this;
        var links = this._body.querySelectorAll('[data-ps-index]');
        for (var k = 0; k < links.length; k++) {
            links[k].addEventListener('click', function (e) {
                e.preventDefault();
                var idx = parseInt(this.getAttribute('data-ps-index'), 10);
                self._navigateTo(idx);
            });
        }
    };

    PowerSearch.prototype._renderItem = function (item, idx) {
        // Dispatch to type-specific renderers for rich cards
        switch (item.type) {
            case 'campaign':   return this._renderCampaign(item, idx);
            case 'template':   return this._renderTemplate(item, idx);
            case 'form':       return this._renderForm(item, idx);
            case 'subscriber': return this._renderSubscriber(item, idx);
            case 'customer':   return this._renderCustomer(item, idx);
            case 'segment':    return this._renderSegment(item, idx);
            default:           return this._renderDefault(item, idx);
        }
    };

    // ---- Campaign: rich card with stats row ----
    PowerSearch.prototype._renderCampaign = function (item, idx) {
        var badgeClass = BADGE_COLORS[item.badge ? item.badge.color : 'default'] || BADGE_COLORS['default'];
        var m = item.meta || {};

        var html = '<a href="' + this._esc(item.url) + '" class="mc-power-search-item mc-power-search-item--rich" data-ps-index="' + idx + '">';
        html += '<span class="mc-power-search-item-icon"><span class="material-symbols-rounded">campaign</span></span>';
        html += '<span class="mc-power-search-item-detail">';
        html += '<span class="mc-power-search-item-title">' + this._esc(item.title) + '</span>';
        if (item.subtitle) {
            html += '<span class="mc-power-search-item-sub">' + this._esc(item.subtitle) + '</span>';
        }
        // Stats row
        html += '<span class="mc-power-search-stats">';
        if (m.recipients) html += '<span class="mc-power-search-stat"><span class="material-symbols-rounded">group</span>' + this._esc(m.recipients) + '</span>';
        if (m.open_rate) html += '<span class="mc-power-search-stat"><span class="material-symbols-rounded">visibility</span>' + this._esc(m.open_rate) + '</span>';
        if (m.click_rate) html += '<span class="mc-power-search-stat"><span class="material-symbols-rounded">ads_click</span>' + this._esc(m.click_rate) + '</span>';
        if (m.date) html += '<span class="mc-power-search-stat"><span class="material-symbols-rounded">schedule</span>' + this._esc(m.date) + '</span>';
        html += '</span>';
        html += '</span>';
        // Badge
        if (item.badge) {
            html += '<span class="mc-power-search-item-badge"><span class="mc-badge ' + badgeClass + ' mc-badge-dot" style="font-size:11px;padding:2px 8px">' +
                this._esc(item.badge.label) + '</span></span>';
        }
        html += '</a>';
        return html;
    };

    // ---- Template: large portrait thumbnail + meta + action buttons ----
    PowerSearch.prototype._renderTemplate = function (item, idx) {
        var m = item.meta || {};
        var html = '<a href="' + this._esc(item.url) + '" class="mc-power-search-item mc-power-search-item--template" data-ps-index="' + idx + '">';
        // Large portrait thumbnail
        if (item.thumb) {
            html += '<span class="mc-power-search-item-thumb mc-power-search-item-thumb--portrait"><img src="' + this._esc(item.thumb) + '" alt="" decoding="async"></span>';
        } else {
            html += '<span class="mc-power-search-item-thumb mc-power-search-item-thumb--portrait">' +
                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">' +
                '<rect x="3" y="2" width="18" height="20" rx="2"/><path d="M3 8h18M9 8v14"/></svg></span>';
        }
        html += '<span class="mc-power-search-item-detail">';
        html += '<span class="mc-power-search-item-title">' + this._esc(item.title) + '</span>';
        // Meta stats row
        html += '<span class="mc-power-search-stats">';
        if (m.category) html += '<span class="mc-power-search-stat"><span class="material-symbols-rounded">label</span>' + this._esc(m.category) + '</span>';
        if (m.created) html += '<span class="mc-power-search-stat"><span class="material-symbols-rounded">calendar_today</span>' + this._esc(m.created) + '</span>';
        if (m.updated && m.updated !== m.created) html += '<span class="mc-power-search-stat"><span class="material-symbols-rounded">update</span>' + this._esc(m.updated) + '</span>';
        html += '</span>';
        // Action buttons
        if (item.actions && item.actions.length > 0) {
            html += '<span class="mc-power-search-actions">';
            for (var i = 0; i < item.actions.length; i++) {
                var a = item.actions[i];
                html += '<span class="mc-power-search-action-btn" data-action-url="' + this._esc(a.url) + '"><span class="material-symbols-rounded">' + this._esc(a.icon) + '</span>' + this._esc(a.label) + '</span>';
            }
            html += '</span>';
        }
        html += '</span>';
        html += '</a>';
        return html;
    };

    // ---- Form: landscape thumbnail ----
    PowerSearch.prototype._renderForm = function (item, idx) {
        var badgeClass = BADGE_COLORS[item.badge ? item.badge.color : 'default'] || BADGE_COLORS['default'];
        var html = '<a href="' + this._esc(item.url) + '" class="mc-power-search-item mc-power-search-item--form" data-ps-index="' + idx + '">';
        if (item.thumb) {
            html += '<span class="mc-power-search-item-thumb mc-power-search-item-thumb--landscape"><img src="' + this._esc(item.thumb) + '" alt="" decoding="async"></span>';
        } else {
            html += '<span class="mc-power-search-item-icon"><span class="material-symbols-rounded">dynamic_form</span></span>';
        }
        html += '<span class="mc-power-search-item-detail">';
        html += '<span class="mc-power-search-item-title">' + this._esc(item.title) + '</span>';
        if (item.subtitle) html += '<span class="mc-power-search-item-sub">' + this._esc(item.subtitle) + '</span>';
        html += '</span>';
        if (item.badge) {
            html += '<span class="mc-power-search-item-badge"><span class="mc-badge ' + badgeClass + ' mc-badge-dot" style="font-size:11px;padding:2px 8px">' +
                this._esc(item.badge.label) + '</span></span>';
        }
        html += '</a>';
        return html;
    };

    // ---- Subscriber: avatar + email + list + status ----
    PowerSearch.prototype._renderSubscriber = function (item, idx) {
        var badgeClass = BADGE_COLORS[item.badge ? item.badge.color : 'default'] || BADGE_COLORS['default'];
        var m = item.meta || {};

        var html = '<a href="' + this._esc(item.url) + '" class="mc-power-search-item mc-power-search-item--rich" data-ps-index="' + idx + '">';
        if (item.avatar) {
            html += '<span class="mc-power-search-item-avatar mc-power-search-avatar-' + item.avatar.color + '">' +
                this._esc(item.avatar.initials) + '</span>';
        }
        html += '<span class="mc-power-search-item-detail">';
        html += '<span class="mc-power-search-item-title">' + this._esc(item.title) + '</span>';
        if (item.subtitle) html += '<span class="mc-power-search-item-sub">' + this._esc(item.subtitle) + '</span>';
        if (m.list || m.date) {
            html += '<span class="mc-power-search-stats">';
            if (m.list) html += '<span class="mc-power-search-stat"><span class="material-symbols-rounded">list</span>' + this._esc(m.list) + '</span>';
            if (m.date) html += '<span class="mc-power-search-stat"><span class="material-symbols-rounded">calendar_today</span>' + this._esc(m.date) + '</span>';
            html += '</span>';
        }
        html += '</span>';
        if (item.badge) {
            html += '<span class="mc-power-search-item-badge"><span class="mc-badge ' + badgeClass + ' mc-badge-dot" style="font-size:11px;padding:2px 8px">' +
                this._esc(item.badge.label) + '</span></span>';
        }
        html += '</a>';
        return html;
    };

    // ---- Customer (admin): avatar + email + plan info + subscription ----
    PowerSearch.prototype._renderCustomer = function (item, idx) {
        var badgeClass = BADGE_COLORS[item.badge ? item.badge.color : 'default'] || BADGE_COLORS['default'];
        var m = item.meta || {};

        var html = '<a href="' + this._esc(item.url) + '" class="mc-power-search-item mc-power-search-item--rich" data-ps-index="' + idx + '">';
        if (item.avatar) {
            html += '<span class="mc-power-search-item-avatar mc-power-search-avatar-' + item.avatar.color + '">' +
                this._esc(item.avatar.initials) + '</span>';
        }
        html += '<span class="mc-power-search-item-detail">';
        html += '<span class="mc-power-search-item-title">' + this._esc(item.title) + '</span>';
        if (item.subtitle) html += '<span class="mc-power-search-item-sub">' + this._esc(item.subtitle) + '</span>';
        if (m.plan || m.subscription || m.joined) {
            html += '<span class="mc-power-search-stats">';
            if (m.plan && m.plan !== '—') html += '<span class="mc-power-search-stat"><span class="material-symbols-rounded">payments</span>' + this._esc(m.plan) + '</span>';
            if (m.subscription) html += '<span class="mc-power-search-stat"><span class="material-symbols-rounded">card_membership</span>' + this._esc(m.subscription) + '</span>';
            if (m.joined) html += '<span class="mc-power-search-stat"><span class="material-symbols-rounded">calendar_today</span>' + this._esc(m.joined) + '</span>';
            html += '</span>';
        }
        html += '</span>';
        if (item.badge) {
            html += '<span class="mc-power-search-item-badge"><span class="mc-badge ' + badgeClass + ' mc-badge-dot" style="font-size:11px;padding:2px 8px">' +
                this._esc(item.badge.label) + '</span></span>';
        }
        html += '</a>';
        return html;
    };

    // ---- Segment: icon + name + list + subscriber count ----
    PowerSearch.prototype._renderSegment = function (item, idx) {
        var m = item.meta || {};
        var html = '<a href="' + this._esc(item.url) + '" class="mc-power-search-item mc-power-search-item--rich" data-ps-index="' + idx + '">';
        html += '<span class="mc-power-search-item-icon"><span class="material-symbols-rounded">filter_alt</span></span>';
        html += '<span class="mc-power-search-item-detail">';
        html += '<span class="mc-power-search-item-title">' + this._esc(item.title) + '</span>';
        if (item.subtitle) html += '<span class="mc-power-search-item-sub">' + this._esc(item.subtitle) + '</span>';
        if (m.subscribers || m.match) {
            html += '<span class="mc-power-search-stats">';
            if (m.subscribers) html += '<span class="mc-power-search-stat"><span class="material-symbols-rounded">group</span>' + this._esc(m.subscribers) + ' subscribers</span>';
            if (m.match) html += '<span class="mc-power-search-stat"><span class="material-symbols-rounded">tune</span>' + this._esc(m.match) + '</span>';
            html += '</span>';
        }
        html += '</span>';
        html += '</a>';
        return html;
    };

    // ---- Default: icon/avatar + title + description/subtitle + meta + badge ----
    PowerSearch.prototype._renderDefault = function (item, idx) {
        var typeClass = item.type === 'action' ? ' mc-power-search-item--action' : '';
        var hasMeta = item.meta && Object.keys(item.meta).length > 0;
        var hasDesc = item.description && item.description.length > 0;
        if (hasMeta || hasDesc) typeClass += ' mc-power-search-item--rich';
        var html = '<a href="' + this._esc(item.url) + '" class="mc-power-search-item' + typeClass + '" data-ps-index="' + idx + '">';

        if (item.avatar) {
            html += '<span class="mc-power-search-item-avatar mc-power-search-avatar-' + item.avatar.color + '">' +
                this._esc(item.avatar.initials) + '</span>';
        } else {
            html += '<span class="mc-power-search-item-icon">' +
                '<span class="material-symbols-rounded">' + this._esc(item.icon || 'article') + '</span></span>';
        }

        html += '<span class="mc-power-search-item-detail">';
        html += '<span class="mc-power-search-item-title">' + this._esc(item.title) + '</span>';
        // Show description (for pages/actions) or subtitle (for entities)
        if (hasDesc) {
            html += '<span class="mc-power-search-item-desc">' + this._esc(item.description) + '</span>';
        } else if (item.subtitle) {
            html += '<span class="mc-power-search-item-sub">' + this._esc(item.subtitle) + '</span>';
        }

        // Render meta stats row if available
        if (hasMeta) {
            var m = item.meta;
            html += '<span class="mc-power-search-stats">';
            if (m.subscribers) html += '<span class="mc-power-search-stat"><span class="material-symbols-rounded">group</span>' + this._esc(m.subscribers) + ' subscribers</span>';
            if (m.created) html += '<span class="mc-power-search-stat"><span class="material-symbols-rounded">calendar_today</span>' + this._esc(m.created) + '</span>';
            if (m.recipients) html += '<span class="mc-power-search-stat"><span class="material-symbols-rounded">group</span>' + this._esc(m.recipients) + '</span>';
            if (m.list) html += '<span class="mc-power-search-stat"><span class="material-symbols-rounded">list</span>' + this._esc(m.list) + '</span>';
            if (m.date) html += '<span class="mc-power-search-stat"><span class="material-symbols-rounded">schedule</span>' + this._esc(m.date) + '</span>';
            html += '</span>';
        }
        html += '</span>';

        if (item.badge) {
            var badgeClass = BADGE_COLORS[item.badge.color] || BADGE_COLORS['default'];
            html += '<span class="mc-power-search-item-badge"><span class="mc-badge ' + badgeClass + ' mc-badge-dot" style="font-size:11px;padding:2px 8px">' +
                this._esc(item.badge.label) + '</span></span>';
        }
        if (item.type === 'action') {
            html += '<span class="mc-power-search-item-type">Action</span>';
        }

        html += '</a>';
        return html;
    };

    PowerSearch.prototype._renderEmpty = function () {
        return '<div class="mc-power-search-empty">' +
            '<span class="material-symbols-rounded">search_off</span>' +
            '<div class="mc-power-search-empty-title">' + this._t('empty', 'No results found') + '</div>' +
            '<div class="mc-power-search-empty-hint">' + this._t('empty_hint', 'Try a different search term') + '</div>' +
            '</div>';
    };

    // ================================================================
    // Keyboard navigation
    // ================================================================

    PowerSearch.prototype._moveActive = function (dir) {
        if (this._flatItems.length === 0) return;

        var items = this._body.querySelectorAll('[data-ps-index]');
        if (items.length === 0) return;

        // Remove current active
        if (this._activeIndex >= 0 && items[this._activeIndex]) {
            items[this._activeIndex].classList.remove('is-active');
        }

        // Compute new index
        this._activeIndex += dir;
        if (this._activeIndex < 0) this._activeIndex = items.length - 1;
        if (this._activeIndex >= items.length) this._activeIndex = 0;

        // Set new active
        items[this._activeIndex].classList.add('is-active');
        items[this._activeIndex].scrollIntoView({ block: 'nearest' });
    };

    PowerSearch.prototype._selectActive = function () {
        if (this._activeIndex >= 0 && this._flatItems[this._activeIndex]) {
            this._navigateTo(this._activeIndex);
        }
    };

    PowerSearch.prototype._navigateTo = function (idx) {
        var item = this._flatItems[idx];
        if (!item) return;

        // Special: toggle theme
        if (item.url === '#toggle-theme') {
            if (window.McTheme && window.McTheme.toggle) {
                window.McTheme.toggle();
            }
            this.close();
            return;
        }

        // Save to recents
        this._saveRecent(item);

        // Navigate
        this.close();
        window.location.href = item.url;
    };

    // ================================================================
    // Recent searches (localStorage)
    // ================================================================

    PowerSearch.prototype._getRecents = function () {
        try {
            var data = localStorage.getItem(this._recentKey);
            return data ? JSON.parse(data) : [];
        } catch (e) {
            return [];
        }
    };

    PowerSearch.prototype._saveRecent = function (item) {
        try {
            var recents = this._getRecents();
            // Remove duplicate by URL
            recents = recents.filter(function (r) { return r.url !== item.url; });
            // Add to front
            recents.unshift({
                type: item.type,
                title: item.title,
                subtitle: item.subtitle || '',
                url: item.url,
                icon: item.icon,
            });
            // Keep max 8
            if (recents.length > 8) recents = recents.slice(0, 8);
            localStorage.setItem(this._recentKey, JSON.stringify(recents));
        } catch (e) {
            // Ignore storage errors
        }
    };

    // ================================================================
    // Helpers
    // ================================================================

    PowerSearch.prototype._t = function (key, fallback) {
        return this.translations[key] || fallback || key;
    };

    PowerSearch.prototype._esc = function (str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    };

    // ================================================================
    // Expose
    // ================================================================

    window.PowerSearch = PowerSearch;
})();
