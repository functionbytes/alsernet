/**
 * SidePanel — Reusable slide-out panel component.
 *
 * Usage:
 *   McSidePanel.open({
 *     title: 'Edit Variant',
 *     subtitle: 'Variant A — Control',
 *     icon: 'tune',
 *     iconColor: 'teal',
 *     size: 'default',      // sm | default | lg | xl
 *     url: '/some/url',      // AJAX content URL (optional)
 *     content: '<div>…</div>', // static HTML (optional)
 *     onClose: function() {},
 *     onSave: function() {},
 *   });
 *
 *   McSidePanel.close();
 *
 * Trigger via data attributes:
 *   <button data-side-panel="/url" data-side-panel-title="Edit">
 */
(function () {
    'use strict';

    var backdrop = null;
    var panel = null;
    var currentOptions = null;
    var isOpen = false;

    function createDOM() {
        if (backdrop) return;

        backdrop = document.createElement('div');
        backdrop.className = 'mc-side-panel-backdrop';
        backdrop.addEventListener('click', close);
        document.body.appendChild(backdrop);

        panel = document.createElement('div');
        panel.className = 'mc-side-panel';
        panel.innerHTML =
            '<div class="mc-side-panel-header">' +
                '<div class="mc-side-panel-header-icon mc-side-panel-header-icon--teal" id="mc-sp-icon">' +
                    '<span class="material-symbols-rounded" id="mc-sp-icon-glyph">tune</span>' +
                '</div>' +
                '<div class="mc-side-panel-title-group">' +
                    '<div class="mc-side-panel-title" id="mc-sp-title"></div>' +
                    '<div class="mc-side-panel-subtitle" id="mc-sp-subtitle"></div>' +
                '</div>' +
                '<button type="button" class="mc-side-panel-close" id="mc-sp-close">' +
                    '<span class="material-symbols-rounded">close</span>' +
                '</button>' +
            '</div>' +
            '<div class="mc-side-panel-body" id="mc-sp-body"></div>';
        document.body.appendChild(panel);

        document.getElementById('mc-sp-close').addEventListener('click', close);
    }

    function open(opts) {
        createDOM();
        currentOptions = opts || {};

        // Set header
        var iconEl = document.getElementById('mc-sp-icon');
        var iconGlyph = document.getElementById('mc-sp-icon-glyph');
        var titleEl = document.getElementById('mc-sp-title');
        var subtitleEl = document.getElementById('mc-sp-subtitle');
        var bodyEl = document.getElementById('mc-sp-body');

        iconGlyph.textContent = opts.icon || 'tune';
        iconEl.className = 'mc-side-panel-header-icon mc-side-panel-header-icon--' + (opts.iconColor || 'teal');
        titleEl.textContent = opts.title || '';
        subtitleEl.textContent = opts.subtitle || '';
        subtitleEl.style.display = opts.subtitle ? '' : 'none';

        // Size
        panel.className = 'mc-side-panel' + (opts.size && opts.size !== 'default' ? ' mc-side-panel--' + opts.size : '');

        // Remove old footer if exists
        var oldFooter = panel.querySelector('.mc-side-panel-footer');
        if (oldFooter) oldFooter.remove();

        // Content
        if (opts.url) {
            bodyEl.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;min-height:200px;color:var(--color-text-muted)">' +
                '<span class="material-symbols-rounded" style="animation:spin 1s linear infinite;font-size:24px">progress_activity</span></div>';
            fetch(opts.url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                bodyEl.innerHTML = html;
                executeScripts(bodyEl);
                if (opts.onLoad) opts.onLoad(bodyEl);
            })
            .catch(function () {
                bodyEl.innerHTML = '<div style="padding:var(--space-6);text-align:center;color:var(--color-error)">Failed to load content.</div>';
            });
        } else if (opts.content) {
            bodyEl.innerHTML = opts.content;
            executeScripts(bodyEl);
            if (opts.onLoad) opts.onLoad(bodyEl);
        }

        // Animate open
        requestAnimationFrame(function () {
            backdrop.classList.add('active');
            panel.classList.add('active');
            document.body.classList.add('mc-side-panel-open');
            isOpen = true;
        });
    }

    function close() {
        if (!isOpen) return;
        isOpen = false;
        backdrop.classList.remove('active');
        panel.classList.remove('active');
        document.body.classList.remove('mc-side-panel-open');

        if (currentOptions && currentOptions.onClose) {
            currentOptions.onClose();
        }
        currentOptions = null;
    }

    function executeScripts(container) {
        var scripts = container.querySelectorAll('script');
        scripts.forEach(function (old) {
            var s = document.createElement('script');
            if (old.src) {
                s.src = old.src;
            } else {
                s.textContent = old.textContent;
            }
            old.parentNode.replaceChild(s, old);
        });
    }

    // Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isOpen) {
            close();
        }
    });

    // Delegated click handler for data-side-panel triggers
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-side-panel]');
        if (!trigger) return;
        // If the click happened on (or inside) an inner anchor/button that
        // lives between the click target and the trigger, the inner element
        // wins. This lets nested action buttons (Edit, Delete, Preview) on a
        // card-as-trigger handle their own clicks without opening the side
        // panel. Without this, a card with `data-side-panel` would swallow
        // every inner button click.
        var inner = e.target.closest('a, button');
        if (inner && inner !== trigger && trigger.contains(inner)) return;
        e.preventDefault();

        open({
            url: trigger.getAttribute('data-side-panel'),
            title: trigger.getAttribute('data-side-panel-title') || '',
            subtitle: trigger.getAttribute('data-side-panel-subtitle') || '',
            icon: trigger.getAttribute('data-side-panel-icon') || 'tune',
            iconColor: trigger.getAttribute('data-side-panel-icon-color') || 'teal',
            size: trigger.getAttribute('data-side-panel-size') || 'default',
        });
    });

    /**
     * Reload panel body content from a URL (AJAX partial update).
     * Executes scripts after loading — no full page refresh needed.
     */
    function reloadContent(url) {
        var bodyEl = document.getElementById('mc-sp-body');
        if (!bodyEl || !url) return;

        bodyEl.style.opacity = '0.5';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                bodyEl.innerHTML = html;
                bodyEl.style.opacity = '';
                executeScripts(bodyEl);
            })
            .catch(function () {
                bodyEl.style.opacity = '';
            });
    }

    // Public API
    window.McSidePanel = {
        open: open,
        close: close,
        isOpen: function () { return isOpen; },
        getBody: function () { return document.getElementById('mc-sp-body'); },
        reloadContent: reloadContent,
    };
})();
