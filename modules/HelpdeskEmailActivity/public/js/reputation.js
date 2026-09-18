/**
 * Actividad de correo — Reputacion de dominios (emails/reputation.blade.php).
 * Extraido del <script> inline de esa vista: filtro de chips de dominio.
 */
(function () {
    'use strict';

    $(function () {
        // Filtra los chips de dominio (visibles y los del <details> colapsado)
        // por subcadena — abre el desplegable automaticamente si hay
        // coincidencias dentro de el, para no esconder resultados de la busqueda.
        var $filter = $('#evx-domain-filter');
        if (!$filter.length) return;

        var $chips = $('.evx-domain-chips .evx-tag');
        var $details = $('.evx-domain-more');

        $filter.on('input', function () {
            var query = this.value.trim().toLowerCase();
            $chips.each(function () {
                // .toggleClass('evx-step-hidden') en vez de jQuery .toggle():
                // evita fijar un style="display" inline en cada chip.
                var visible = !query || $(this).data('domain').includes(query);
                $(this).toggleClass('evx-step-hidden', !visible);
            });
            if (query) $details.prop('open', true);
        });
    });
})();
