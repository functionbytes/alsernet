/*!
 * HelpdeskPrestashop · pantalla "Avisos de cambio de estado": búsqueda y
 * filtros (todos / envían correo / con aviso) en cliente. Solo cambia clases.
 */
(function () {
    function norm(text) {
        return String(text || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    }

    function init() {
        var list = document.getElementById('psNoticesList');
        if (!list) { return; }
        var rows = Array.prototype.slice.call(list.querySelectorAll('[data-notices-row]'));
        var filter = document.getElementById('psNoticesFilter');
        var noMatch = document.getElementById('psNoticesNoMatch');
        var chips = Array.prototype.slice.call(document.querySelectorAll('[data-notices-show]'));
        var show = 'all';

        function apply() {
            var term = norm(filter ? filter.value : '');
            var visible = 0;
            rows.forEach(function (row) {
                var ok = (!term || row.getAttribute('data-name').indexOf(term) !== -1) &&
                    (show === 'all' || (show === 'email' && row.getAttribute('data-email') === '1') ||
                        (show === 'notice' && row.getAttribute('data-notice') === '1'));
                row.classList.toggle('psc-opsmap-hidden', !ok);
                if (ok) { visible++; }
            });
            if (noMatch) { noMatch.classList.toggle('psc-opsmap-hidden', visible > 0); }
        }

        rows.forEach(function (row) {
            var input = row.querySelector('.psc-notice-text input');
            if (!input) { return; }
            input.addEventListener('input', function () {
                row.setAttribute('data-notice', input.value.trim() ? '1' : '0');
            });
        });

        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                show = chip.getAttribute('data-notices-show') || 'all';
                chips.forEach(function (c) { c.classList.toggle('is-on', c === chip); });
                apply();
            });
        });

        if (filter) {
            filter.addEventListener('input', apply);
            // Enter en el buscador no debe enviar el formulario.
            filter.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); } });
        }
    }

    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
