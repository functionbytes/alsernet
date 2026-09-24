/* HelpdeskPrestashop · extensión "metrics" — «Métricas del chat · PrestaShop».
   El desplegable de agente envía el filtro al cambiar y los chips de la
   evolución semanal cambian la serie visible (las barras ya vienen pintadas
   del servidor con .psc-h-N; aquí solo se muestran u ocultan). */
(function () {
    'use strict';

    function initFilters() {
        var form = document.getElementById('psMetricsFilters');
        if (!form) { return; }
        Array.prototype.forEach.call(form.querySelectorAll('[data-metrics-autosubmit]'), function (el) {
            el.addEventListener('change', function () { form.submit(); });
        });
    }

    function initSeries() {
        var chips = Array.prototype.slice.call(document.querySelectorAll('[data-metrics-series]'));
        var panels = Array.prototype.slice.call(document.querySelectorAll('[data-metrics-panel]'));
        if (!chips.length) { return; }

        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                var key = chip.getAttribute('data-metrics-series');
                chips.forEach(function (c) {
                    var on = c === chip;
                    c.classList.toggle('is-on', on);
                    c.setAttribute('aria-selected', on ? 'true' : 'false');
                });
                panels.forEach(function (p) {
                    p.classList.toggle('psc-metrics-hidden', p.getAttribute('data-metrics-panel') !== key);
                });
            });
        });
    }

    function init() {
        initFilters();
        initSeries();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
