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
                // 200 OK + { credits: { key: { remaining } }, last_synced_at, cache_queued? }
                // Credits arrive inline (DB-snapshot sync runs on the request thread —
                // it's a few row UPDATEs, fast). When the endpoint also fans out a
                // cache refresh (admin row button), it sets `cache_queued: true` so we
                // surface a toast pointing the admin at "reload to see cache columns".
                var credits = data.credits || {};
                Object.keys(credits).forEach(function (key) {
                    var remaining = credits[key].remaining;

                    var valueEl = scope.querySelector('[data-credit-value="' + key + '"]');
                    if (valueEl) valueEl.textContent = formatRemaining(remaining);

                    // Optional: progress bar + pct text. Caller renders the cap on the
                    // bar wrapper as data-credit-limit so we can recompute "used %" here
                    // without another round-trip.
                    var barWrap = scope.querySelector('[data-credit-bar="' + key + '"]');
                    if (barWrap) {
                        var limit = parseInt(barWrap.dataset.creditLimit, 10);
                        var pctText = scope.querySelector('[data-credit-pct="' + key + '"]');
                        var fill = barWrap.querySelector('[data-credit-bar-fill]');
                        if (remaining === null || !isFinite(limit) || limit <= 0) {
                            if (fill) fill.style.width = '0%';
                            if (pctText) pctText.textContent = '0%';
                        } else {
                            var used = Math.max(0, limit - Number(remaining));
                            var pct = Math.min(100, Math.round((used / limit) * 100));
                            if (fill) fill.style.width = pct + '%';
                            if (pctText) pctText.textContent = pct + '%';
                        }
                    }
                });
                btn.dataset.tooltip = getJustNowLabel();

                if (data.cache_queued && data.message) {
                    if (window.McNotify && typeof window.McNotify.info === 'function') {
                        window.McNotify.info(data.message);
                    }
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
