/**
 * CreditRefresh — user-triggered sync of credit DB snapshot from live tracker.
 *
 * Markup contract:
 *   <div data-credit-scope>
 *       <span data-credit-value="send_email">12,345</span>
 *       <span data-credit-value="verify_email">5,000</span>
 *       <button data-credit-refresh data-credit-refresh-url="/.../refresh-credits"
 *               data-tooltip="Updated 5m ago — click to refresh">↻</button>
 *   </div>
 *
 * On click: POST to data-credit-refresh-url with CSRF token. Server returns
 *   { credits: { credit_key: { remaining, last_synced_at } }, last_synced_at }
 * Handler updates each <span data-credit-value="{key}"> inside the same scope
 * with the new formatted value, and updates the button's tooltip to "just now".
 *
 * `null` remaining = unlimited → renders as ∞.
 */
(function () {
    'use strict';

    function getCsrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function formatRemaining(value) {
        if (value === null || value === undefined) return '∞';
        return Number(value).toLocaleString();
    }

    function getJustNowLabel() {
        // i18n string is rendered into window.McCreditsI18n by app.blade.php; fall back to English.
        return (window.McCreditsI18n && window.McCreditsI18n.refreshTooltipJustNow) || 'Updated just now';
    }

    function getFailedLabel() {
        return (window.McCreditsI18n && window.McCreditsI18n.refreshFailed) || 'Failed to refresh credits';
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-credit-refresh]');
        if (!btn) return;
        e.preventDefault();

        var scope = btn.closest('[data-credit-scope]');
        var url = btn.dataset.creditRefreshUrl;
        if (!scope || !url) {
            console.warn('CreditRefresh: missing data-credit-scope or data-credit-refresh-url');
            return;
        }

        if (btn.dataset.creditBusy === '1') return;
        btn.dataset.creditBusy = '1';
        btn.classList.add('mc-btn--loading');

        var icon = btn.querySelector('.material-symbols-rounded');
        if (icon) icon.style.animation = 'mc-spin 0.6s linear infinite';

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrf(),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(function (data) {
                var credits = data.credits || {};
                Object.keys(credits).forEach(function (key) {
                    var el = scope.querySelector('[data-credit-value="' + key + '"]');
                    if (el) el.textContent = formatRemaining(credits[key].remaining);
                });
                btn.dataset.tooltip = getJustNowLabel();
                if (window.McNotify && typeof window.McNotify.success === 'function') {
                    // light toast — only on explicit user action, not auto
                    // (skip for now; tooltip update is enough feedback)
                }
            })
            .catch(function (err) {
                console.warn('CreditRefresh failed:', err);
                if (window.McNotify && typeof window.McNotify.error === 'function') {
                    window.McNotify.error(getFailedLabel());
                }
            })
            .finally(function () {
                btn.dataset.creditBusy = '0';
                btn.classList.remove('mc-btn--loading');
                if (icon) icon.style.animation = '';
            });
    });
})();
