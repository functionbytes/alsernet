/* HelpdeskPrestashop · extensión "opsmap" — pantallas de administración.
   Mapeo de estados (pieza 39): búsqueda y chips Todos/Con acción/Sin acción,
   fila en gris cuando queda en «Sin acción». Auditoría (pieza 40): los
   desplegables de filtro envían el formulario al cambiar. */
(function () {
    'use strict';

    function norm(text) {
        return String(text || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    function initStateMap() {
        var list = document.getElementById('psOpsmapList');
        if (!list) { return; }

        var rows = Array.prototype.slice.call(list.querySelectorAll('[data-opsmap-row]'));
        var filter = document.getElementById('psOpsmapFilter');
        var noMatch = document.getElementById('psOpsmapNoMatch');
        var chips = Array.prototype.slice.call(document.querySelectorAll('[data-opsmap-show]'));
        var show = 'all';

        function isMapped(row) {
            var select = row.querySelector('[data-opsmap-select]');
            return select && select.value !== 'none';
        }

        function counts() {
            var mapped = rows.filter(isMapped).length;
            var set = function (key, n) {
                var el = document.querySelector('[data-opsmap-count="' + key + '"]');
                if (el) { el.textContent = n; }
            };
            set('all', rows.length);
            set('mapped', mapped);
            set('none', rows.length - mapped);
        }

        function apply() {
            var q = norm(filter ? filter.value.trim() : '');
            var visible = 0;
            rows.forEach(function (row) {
                var mapped = isMapped(row);
                var ok = (!q || (row.getAttribute('data-name') || '').indexOf(q) !== -1)
                    && (show === 'all' || (show === 'mapped' ? mapped : !mapped));
                row.classList.toggle('psc-opsmap-hidden', !ok);
                if (ok) { visible++; }
            });
            if (noMatch) { noMatch.classList.toggle('psc-opsmap-hidden', visible > 0 || rows.length === 0); }
        }

        rows.forEach(function (row) {
            var select = row.querySelector('[data-opsmap-select]');
            if (!select) { return; }
            select.addEventListener('change', function () {
                row.classList.toggle('is-muted', select.value === 'none');
                counts();
                apply();
            });
        });

        if (filter) { filter.addEventListener('input', apply); }

        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                show = chip.getAttribute('data-opsmap-show') || 'all';
                chips.forEach(function (c) { c.classList.toggle('is-on', c === chip); });
                apply();
            });
        });

        // Enter en el buscador no debe enviar el mapeo a medias.
        if (filter) {
            filter.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); }
            });
        }

        counts();
    }

    function initAudit() {
        var form = document.getElementById('psOpsmapAuditFilters');
        if (!form) { return; }

        Array.prototype.forEach.call(form.querySelectorAll('[data-opsmap-autosubmit]'), function (el) {
            el.addEventListener('change', function () { form.submit(); });
        });
    }

    function init() {
        initStateMap();
        initAudit();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
