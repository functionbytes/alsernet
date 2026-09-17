/**
 * Inicializacion de Select2 para todos los .evx-select del modulo.
 * Extraido del <script> inline de partials/select2.blade.php — las 2 cadenas
 * traducidas (sin resultados / buscando) llegan por data-* en el propio
 * <script src="..."> que carga este fichero (ver ese partial), no hay forma
 * de tirar de __() desde un .js estatico.
 */
(function () {
    'use strict';

    $(function () {
        if (!$.fn.select2) return;

        var $script = $('script[data-evx-select2-i18n]');
        var noResults = $script.data('no-results') || '';
        var searching = $script.data('searching') || '';

        // Sin theme 'bootstrap-5': ese CSS no lo sirve este tema y deja los
        // controles rotos. El aspecto lo da .evx-select en emaillog.css, que
        // replica sobre el markup de Select2 el borde, radio y tipografia que
        // tenia el <select> nativo.

        // El buscador interno solo donde hay bastantes opciones que filtrar; en
        // un desplegable de tres entradas estorba mas de lo que ayuda.
        var conBuscador = ['module', 'causer_id', 'from_address'];

        $('.evx-select').each(function () {
            var $sel = $(this);
            if ($sel.data('select2')) return;

            // dropdownParent importa en dos sitios: dentro de un modal (si no, el
            // panel se pinta detras del backdrop y el buscador no recibe foco) y
            // dentro de #evx-filters-more, que nace con [hidden] y haria que
            // Select2 midiera mal el ancho al desplegarlo.
            var $modal = $sel.closest('.modal');
            var $plegable = $sel.closest('.evx-filters-more');
            var $padre = $modal.length ? $modal : ($plegable.length ? $plegable : $(document.body));

            $sel.select2({
                width: 'resolve',
                minimumResultsForSearch: conBuscador.includes($sel.attr('name')) ? 0 : Infinity,
                dropdownParent: $padre,
                language: {
                    noResults: function () { return noResults; },
                    searching: function () { return searching; },
                },
            });
        });
    });
})();
