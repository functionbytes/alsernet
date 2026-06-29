/**
 * mc-rich-button-builder — Repeatable {label + type + value} row builder (C9 / W4).
 *
 * Mounts on any `<div data-mc-rich-button-builder>` and renders a list of rows
 * (each one is a label + type select + value input + reorder/remove controls)
 * plus an "Add row" CTA at the bottom. Used by S4.5 Telegram composer (inline
 * keyboard) + S4.8 WhatsApp template editor (template CTA buttons).
 *
 * Data attributes (declarative API):
 *   - data-mc-rich-button-builder   — mount marker (REQUIRED)
 *   - data-max-rows                 — soft cap (default 8)
 *   - data-allowed-types            — CSV of allowed types (default "url,callback")
 *                                       supported: url | callback | quick_reply | phone
 *   - data-channel                  — informational ("telegram" | "whatsapp" | "*")
 *   - data-rows                     — JSON array of initial rows
 *
 * Row shape:
 *   { label: string, type: "url" | "callback" | "quick_reply" | "phone", value: string }
 *
 * Emits:
 *   - mc-rich-button-builder:change   on any add/remove/edit/reorder
 *   - mc-rich-button-builder:invalid  when ≥1 row fails per-type validation
 *
 * Public read state on the builder root:
 *   root.dataset.mcRichButtonBuilderRowCount = "N"
 *   root.dataset.mcRichButtonBuilderInvalid   = "1" | ""
 *
 * Public helper:
 *   window.McRichButtonBuilder.getRows(root) → [{label,type,value}, …]
 *
 * Idempotent — re-mounting an already-bound element is a no-op.
 */
(function () {
    'use strict';

    if (window.McRichButtonBuilder && window.McRichButtonBuilder.__bound) {
        return;
    }

    var TYPE_LABELS = {
        url: 'URL',
        callback: 'Callback data',
        quick_reply: 'Quick reply',
        phone: 'Phone number',
    };

    var TYPE_PLACEHOLDERS = {
        url: 'https://example.com/path',
        callback: 'cb_action_abc',
        quick_reply: '(no value — sends the label)',
        phone: '+14155550100',
    };

    function escapeHtml(s) {
        return (s == null ? '' : String(s)).replace(/[&<>"']/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]);
        });
    }

    function parseRows(root) {
        var raw = root.getAttribute('data-rows');
        if (!raw) return [];
        try {
            var data = JSON.parse(raw);
            if (!Array.isArray(data)) return [];
            return data.map(function (r) {
                return {
                    label: String(r.label || ''),
                    type: String(r.type || 'url'),
                    value: String(r.value || ''),
                };
            });
        } catch (e) {
            // eslint-disable-next-line no-console
            console.error('McRichButtonBuilder: data-rows is not valid JSON', e);
            return [];
        }
    }

    function allowedTypes(root) {
        var raw = root.getAttribute('data-allowed-types') || 'url,callback';
        return raw.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
    }

    function maxRows(root) {
        var n = parseInt(root.getAttribute('data-max-rows') || '8', 10);
        return isFinite(n) && n > 0 ? n : 8;
    }

    function validateRow(row) {
        if (!row.label || row.label.trim().length === 0) return { ok: false, msg: 'Label is required.' };
        if (row.label.length > 64) return { ok: false, msg: 'Label must be 64 characters or fewer.' };
        switch (row.type) {
            case 'url':
                if (!row.value) return { ok: false, msg: 'URL is required for URL buttons.' };
                if (!/^https:\/\//i.test(row.value)) return { ok: false, msg: 'URL must start with https://' };
                return { ok: true };
            case 'callback':
                if (!row.value) return { ok: false, msg: 'Callback data is required.' };
                if (row.value.length > 64) return { ok: false, msg: 'Callback data must be 64 characters or fewer.' };
                return { ok: true };
            case 'phone':
                if (!row.value) return { ok: false, msg: 'Phone number is required.' };
                if (!/^\+[1-9]\d{6,14}$/.test(row.value)) return { ok: false, msg: 'Phone number must be E.164 (e.g. +14155550100).' };
                return { ok: true };
            case 'quick_reply':
                return { ok: true }; // value optional
            default:
                return { ok: false, msg: 'Unknown button type: ' + row.type };
        }
    }

    function buildRowHtml(row, idx, total, types, errMsg) {
        var typeOptions = types.map(function (t) {
            var selected = t === row.type ? ' selected' : '';
            return '<option value="' + t + '"' + selected + '>' + (TYPE_LABELS[t] || t) + '</option>';
        }).join('');
        var firstDisabled = idx === 0 ? ' disabled' : '';
        var lastDisabled = idx === total - 1 ? ' disabled' : '';
        var placeholder = TYPE_PLACEHOLDERS[row.type] || '';
        var valueDisabled = row.type === 'quick_reply' ? ' disabled' : '';
        var errCls = errMsg ? ' has-error' : '';
        return (
            '<fieldset class="mc-rich-button-builder__row' + errCls + '" data-row-index="' + idx + '">' +
                '<legend class="mc-rich-button-builder__row-legend">Row ' + (idx + 1) + ' of ' + total + '</legend>' +
                '<div class="mc-rich-button-builder__row-field mc-rich-button-builder__row-field--label">' +
                    '<label class="mc-rich-button-builder__row-label" for="mcrbb-' + idx + '-label">Label</label>' +
                    '<input type="text" class="mc-form-input mc-form-input-sm" id="mcrbb-' + idx + '-label" data-field="label" maxlength="64" value="' + escapeHtml(row.label) + '" placeholder="Visit our site">' +
                '</div>' +
                '<div class="mc-rich-button-builder__row-field mc-rich-button-builder__row-field--type">' +
                    '<label class="mc-rich-button-builder__row-label" for="mcrbb-' + idx + '-type">Type</label>' +
                    '<select class="mc-form-input mc-form-select mc-form-input-sm" id="mcrbb-' + idx + '-type" data-field="type">' + typeOptions + '</select>' +
                '</div>' +
                '<div class="mc-rich-button-builder__row-field mc-rich-button-builder__row-field--value">' +
                    '<label class="mc-rich-button-builder__row-label" for="mcrbb-' + idx + '-value">Value</label>' +
                    '<input type="text" class="mc-form-input mc-form-input-sm" id="mcrbb-' + idx + '-value" data-field="value" value="' + escapeHtml(row.value) + '" placeholder="' + escapeHtml(placeholder) + '"' + valueDisabled + '>' +
                '</div>' +
                '<div class="mc-rich-button-builder__row-actions" role="group" aria-label="Reorder + remove">' +
                    '<button type="button" class="mc-rich-button-builder__row-btn" data-action="move-up" aria-label="Move row up"' + firstDisabled + '>' +
                        '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 15l-6-6-6 6"/></svg>' +
                    '</button>' +
                    '<button type="button" class="mc-rich-button-builder__row-btn" data-action="move-down" aria-label="Move row down"' + lastDisabled + '>' +
                        '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>' +
                    '</button>' +
                    '<button type="button" class="mc-rich-button-builder__row-btn mc-rich-button-builder__row-btn--danger" data-action="remove" aria-label="Remove row">' +
                        '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14z"/></svg>' +
                    '</button>' +
                '</div>' +
                (errMsg ? '<div class="mc-rich-button-builder__row-error" role="alert">' + escapeHtml(errMsg) + '</div>' : '') +
            '</fieldset>'
        );
    }

    function buildEmptyState(channel) {
        var chanLabel = channel === 'whatsapp' ? 'CTA' : 'inline button';
        return (
            '<div class="mc-rich-button-builder__empty" role="status">' +
                '<div class="mc-rich-button-builder__empty-title">No ' + chanLabel + 's yet</div>' +
                '<div class="mc-rich-button-builder__empty-body">Add a row to compose your first ' + chanLabel + '.</div>' +
            '</div>'
        );
    }

    function buildAddCta(rowCount, max) {
        var capReached = rowCount >= max;
        var disabledAttr = capReached ? ' disabled' : '';
        var titleAttr = capReached ? ' title="Soft cap reached — max ' + max + ' rows."' : '';
        return (
            '<button type="button" class="mc-btn mc-btn-ghost mc-rich-button-builder__add" data-action="add-row"' + disabledAttr + titleAttr + '>' +
                '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>' +
                '<span>Add row</span>' +
                (capReached ? '<span class="mc-rich-button-builder__add-cap-hint"> · max ' + max + '</span>' : '') +
            '</button>'
        );
    }

    function render(root) {
        var rows = root.__mcrbbRows;
        var types = allowedTypes(root);
        var max = maxRows(root);
        var errs = root.__mcrbbErrs || {};
        var rowsHtml;
        if (rows.length === 0) {
            rowsHtml = buildEmptyState(root.getAttribute('data-channel') || '');
        } else {
            rowsHtml = '<div class="mc-rich-button-builder__rows">' +
                rows.map(function (r, i) { return buildRowHtml(r, i, rows.length, types, errs[i]); }).join('') +
                '</div>';
        }
        root.innerHTML = '<div class="mc-rich-button-builder">' +
            rowsHtml +
            buildAddCta(rows.length, max) +
        '</div>';
        root.dataset.mcRichButtonBuilderRowCount = String(rows.length);
        root.dataset.mcRichButtonBuilderInvalid = Object.keys(errs).length > 0 ? '1' : '';
    }

    function validateAll(root) {
        var rows = root.__mcrbbRows;
        var errs = {};
        rows.forEach(function (r, i) {
            var v = validateRow(r);
            if (!v.ok) errs[i] = v.msg;
        });
        root.__mcrbbErrs = errs;
        return errs;
    }

    function fire(root) {
        var rows = root.__mcrbbRows;
        validateAll(root);

        root.dispatchEvent(new CustomEvent('mc-rich-button-builder:change', {
            bubbles: true,
            detail: { rows: rows.map(function (r) { return { label: r.label, type: r.type, value: r.value }; }) },
        }));

        if (Object.keys(root.__mcrbbErrs).length > 0) {
            root.dispatchEvent(new CustomEvent('mc-rich-button-builder:invalid', {
                bubbles: true,
                detail: { errors: root.__mcrbbErrs },
            }));
        }
    }

    function mount(root) {
        if (!root || root.__mcRichButtonBuilderBound) return;
        root.__mcRichButtonBuilderBound = true;
        root.__mcrbbRows = parseRows(root);
        root.__mcrbbErrs = {};

        render(root);

        root.addEventListener('input', function (e) {
            var target = e.target;
            if (!target || !target.dataset || !target.dataset.field) return;
            var rowEl = target.closest('[data-row-index]');
            if (!rowEl) return;
            var idx = parseInt(rowEl.getAttribute('data-row-index'), 10);
            root.__mcrbbRows[idx][target.dataset.field] = target.value;
            // Lightweight emit — don't re-render on every keystroke (preserves focus + cursor).
            fire(root);
        });

        root.addEventListener('change', function (e) {
            var target = e.target;
            if (!target || !target.dataset || target.dataset.field !== 'type') return;
            var rowEl = target.closest('[data-row-index]');
            if (!rowEl) return;
            var idx = parseInt(rowEl.getAttribute('data-row-index'), 10);
            root.__mcrbbRows[idx].type = target.value;
            // Type change DOES re-render (placeholder + disabled-state changes per type).
            render(root);
            fire(root);
        });

        root.addEventListener('click', function (e) {
            var btn = e.target.closest && e.target.closest('[data-action]');
            if (!btn) return;
            e.preventDefault();
            var action = btn.getAttribute('data-action');

            if (action === 'add-row') {
                var max = maxRows(root);
                if (root.__mcrbbRows.length >= max) return;
                var types = allowedTypes(root);
                root.__mcrbbRows.push({ label: '', type: types[0] || 'url', value: '' });
                render(root);
                fire(root);
                // Focus the new row's label input
                var newLabel = root.querySelector('[data-row-index="' + (root.__mcrbbRows.length - 1) + '"] input[data-field="label"]');
                if (newLabel) newLabel.focus();
                return;
            }
            var rowEl = btn.closest('[data-row-index]');
            if (!rowEl) return;
            var idx = parseInt(rowEl.getAttribute('data-row-index'), 10);

            if (action === 'remove') {
                root.__mcrbbRows.splice(idx, 1);
                render(root);
                fire(root);
                return;
            }
            if (action === 'move-up' && idx > 0) {
                var tmpUp = root.__mcrbbRows[idx - 1];
                root.__mcrbbRows[idx - 1] = root.__mcrbbRows[idx];
                root.__mcrbbRows[idx] = tmpUp;
                render(root);
                fire(root);
                return;
            }
            if (action === 'move-down' && idx < root.__mcrbbRows.length - 1) {
                var tmpDown = root.__mcrbbRows[idx + 1];
                root.__mcrbbRows[idx + 1] = root.__mcrbbRows[idx];
                root.__mcrbbRows[idx] = tmpDown;
                render(root);
                fire(root);
                return;
            }
        });

        // Fire once on init so consumers can read initial state.
        fire(root);
    }

    function mountAll(scopeRoot) {
        var scope = scopeRoot || document;
        var roots = scope.querySelectorAll('[data-mc-rich-button-builder]:not([data-mc-rich-button-builder-init])');
        roots.forEach(function (r) {
            r.setAttribute('data-mc-rich-button-builder-init', '1');
            mount(r);
        });
    }

    function getRows(root) {
        return root && root.__mcrbbRows
            ? root.__mcrbbRows.map(function (r) { return { label: r.label, type: r.type, value: r.value }; })
            : [];
    }

    window.McRichButtonBuilder = {
        __bound: true,
        mount: mount,
        mountAll: mountAll,
        getRows: getRows,
        _validateRow: validateRow,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { mountAll(); });
    } else {
        mountAll();
    }
})();
