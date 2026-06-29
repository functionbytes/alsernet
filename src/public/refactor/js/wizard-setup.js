/**
 * Campaign wizard — step 2 (Setup) interactions.
 *
 *  • Per-server "Include" switches drive the live weight-percentage display
 *    on every row (recomputed every time a switch flips).
 *  • An "Adjust weights" button opens an McPopup with a slider per included
 *    server (parity with the admin Load-Balance modal). Sliders are bound
 *    to the original row's hidden fitness inputs and to the displayed %.
 */
(function () {
    'use strict';

    var LIST_SELECTOR = '[data-mc-sending-servers]';
    var ROW_SELECTOR = '.mc-sending-server-row';
    var TOGGLE_SELECTOR = '.mc-sending-server-toggle';
    var HIDDEN_FITNESS = '[data-mc-fitness-value]';
    var WEIGHT_PCT = '[data-mc-weight-pct]';
    var OPEN_BUTTON = '[data-mc-fitness-open]';
    var MASTER_TOGGLE = '[data-mc-use-all-toggle]';
    var NARROW_BLOCK = '[data-mc-sending-servers-narrow]';

    function init() {
        var list = document.querySelector(LIST_SELECTOR);
        if (!list) return;

        list.addEventListener('change', function (e) {
            if (e.target.matches(TOGGLE_SELECTOR)) {
                recomputePercentages();
                refreshOpenButton();
            }
        });

        document.querySelectorAll(OPEN_BUTTON).forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                openFitnessModal(btn);
            });
        });

        // Master toggle "Use all servers randomly" — when ON, hide the narrow
        // block AND disable every <input> inside it so the form payload is
        // empty (Campaign::syncSendingServers([]) then wipes any prior pivot).
        var master = document.querySelector(MASTER_TOGGLE);
        if (master) {
            master.addEventListener('change', function () {
                applyMasterMode(master.checked);
            });
            applyMasterMode(master.checked);
        }

        recomputePercentages();
        refreshOpenButton();
    }

    function applyMasterMode(useAll) {
        var narrow = document.querySelector(NARROW_BLOCK);
        if (!narrow) return;
        if (useAll) {
            narrow.setAttribute('hidden', '');
        } else {
            narrow.removeAttribute('hidden');
        }
        // Disabled inputs don't submit — the canonical way to opt the narrow
        // block out of the form payload without unmounting the DOM.
        narrow.querySelectorAll('input').forEach(function (input) {
            input.disabled = useAll;
        });
    }

    function rows() {
        return Array.from(document.querySelectorAll(ROW_SELECTOR));
    }

    function selectedRows() {
        return rows().filter(function (r) {
            var toggle = r.querySelector(TOGGLE_SELECTOR);
            return toggle && toggle.checked;
        });
    }

    function fitnessOf(row) {
        var hidden = row.querySelector(HIDDEN_FITNESS);
        return hidden ? Math.max(1, parseInt(hidden.value, 10) || 100) : 100;
    }

    function setFitness(row, value) {
        var v = Math.max(1, Math.min(100, parseInt(value, 10) || 100));
        var hidden = row.querySelector(HIDDEN_FITNESS);
        if (hidden) hidden.value = v;
    }

    function recomputePercentages() {
        var allRows = rows();
        var selected = selectedRows();
        var totalSelected = selected.reduce(function (sum, r) { return sum + fitnessOf(r); }, 0);

        allRows.forEach(function (r) {
            r.classList.toggle('is-selected', r.querySelector(TOGGLE_SELECTOR)?.checked === true);
            var label = r.querySelector(WEIGHT_PCT);
            if (!label) return;
            var toggle = r.querySelector(TOGGLE_SELECTOR);
            if (toggle && toggle.checked && totalSelected > 0) {
                var pct = Math.round((fitnessOf(r) / totalSelected) * 100);
                label.textContent = pct + '%';
            } else {
                label.textContent = '—';
            }
        });
    }

    function refreshOpenButton() {
        var hasSelected = selectedRows().length > 0;
        document.querySelectorAll(OPEN_BUTTON).forEach(function (btn) {
            btn.disabled = !hasSelected;
        });
    }

    function openFitnessModal(btn) {
        var selected = selectedRows();
        if (selected.length === 0 || !window.McPopup) return;

        var title = btn.getAttribute('data-modal-title') || 'Adjust weights';
        var saveLabel = btn.getAttribute('data-modal-done') || 'Done';
        var helpText = btn.getAttribute('data-modal-help') || '';

        var html = '';
        html += '<div class="mc-alert mc-alert-teal" style="margin-bottom:var(--space-5)">';
        html += '<div class="mc-alert-icon"><span class="material-symbols-rounded" style="font-size:18px;">balance</span></div>';
        html += '<div class="mc-alert-content"><div class="mc-alert-text">';
        html += escapeHtml(helpText);
        html += '</div></div></div>';
        html += '<div class="mc-fitness-modal-list">';
        selected.forEach(function (r, idx) {
            var name = r.getAttribute('data-server-name') || '';
            var type = r.getAttribute('data-server-type') || '';
            var serverId = r.getAttribute('data-server-id') || '';
            var fitness = fitnessOf(r);
            html += '<div class="mc-fitness-modal-row" data-mc-fitness-modal-row data-server-id="' + escapeAttr(serverId) + '">';
            html +=   '<div class="mc-fitness-modal-row-meta">';
            html +=     '<div class="mc-fitness-modal-row-name">' + escapeHtml(name) + '</div>';
            html +=     '<div class="mc-fitness-modal-row-type">' + escapeHtml(type) + '</div>';
            html +=   '</div>';
            html +=   '<div class="mc-fitness-modal-row-slider">';
            html +=     '<input type="range" min="1" max="100" value="' + fitness + '" data-mc-fitness-slider>';
            html +=     '<span class="mc-fitness-modal-row-pct" data-mc-fitness-pct>0%</span>';
            html +=   '</div>';
            html += '</div>';
        });
        html += '</div>';
        html += '<div class="mc-popup-footer">';
        html += '<button type="button" class="mc-btn mc-btn-primary" data-popup-close>';
        html += '<span class="material-symbols-rounded">check</span> ' + escapeHtml(saveLabel);
        html += '</button>';
        html += '</div>';

        window.McPopup.open({ content: html, title: title, size: 'lg' });
        // McPopup is async — wait a tick for it to inject content, then bind sliders.
        requestAnimationFrame(bindModalSliders);
    }

    function bindModalSliders() {
        var modalRows = document.querySelectorAll('[data-mc-fitness-modal-row]');
        if (modalRows.length === 0) {
            // McPopup not done injecting yet — try once more next frame.
            requestAnimationFrame(bindModalSliders);
            return;
        }

        function recalc() {
            var total = 0;
            modalRows.forEach(function (mr) {
                total += parseInt(mr.querySelector('[data-mc-fitness-slider]').value, 10);
            });
            modalRows.forEach(function (mr) {
                var slider = mr.querySelector('[data-mc-fitness-slider]');
                var pct = total > 0 ? Math.round((parseInt(slider.value, 10) / total) * 100) : 0;
                mr.querySelector('[data-mc-fitness-pct]').textContent = pct + '%';
            });
        }

        modalRows.forEach(function (mr) {
            var slider = mr.querySelector('[data-mc-fitness-slider]');
            var serverId = mr.getAttribute('data-server-id');
            slider.addEventListener('input', function () {
                var mainRow = document.querySelector(ROW_SELECTOR + '[data-server-id="' + serverId + '"]');
                if (mainRow) setFitness(mainRow, slider.value);
                recalc();
                recomputePercentages();
            });
        });

        recalc();
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    function escapeAttr(s) {
        return escapeHtml(s);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
