/**
 * Helpdesk · Misc — select2 generico + flash de sesion via toastr.
 *
 * Consolida el boilerplate identico que aparecia repetido en
 * customers/index, customers/create, customers/edit y conversations/create:
 * inicializar select2 sobre cualquier .select2 con placeholder tomado de la
 * primera opcion, i18n basico (Sin resultados / Buscando...), auto-mayuscula
 * del campo #country (si existe) y mostrar el flash de sesion via toastr si
 * la pagina definio `window.HdPageFlash` antes de cargar este script.
 *
 * Uso tipico en un Blade:
 *
 *   @push('scripts')
 *   <script>
 *   window.HdPageFlash = { success: @json(session('success')), error: @json(session('error')) };
 *   </script>
 *   <script src="{{ asset('vendor/helpdesk/misc/select2-flash.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/misc/select2-flash.js')) }}" defer></script>
 *   @endpush
 *
 * window.HdPageFlash es opcional: paginas que no muestran flash (p.ej.
 * conversations/create) simplemente no lo definen.
 */
(function ($) {
    'use strict';

    function onReady(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    function autoSelect2() {
        $('.select2').each(function () {
            const $el = $(this);

            if ($el.hasClass('select2-hidden-accessible')) {
                return;
            }

            $el.select2({
                allowClear: true,
                placeholder: function () {
                    return $(this).find('option:first').text();
                },
                language: {
                    noResults: function () {
                        return 'Sin resultados';
                    },
                    searching: function () {
                        return 'Buscando...';
                    },
                },
            });
        });
    }

    function autoUppercaseCountry() {
        $('#country').on('input', function () {
            $(this).val($(this).val().toUpperCase());
        });
    }

    function flash(cfg) {
        cfg = cfg || {};

        if (typeof toastr === 'undefined') {
            return;
        }

        if (cfg.success) toastr.success(cfg.success, cfg.successTitle || 'Exito');
        if (cfg.error) toastr.error(cfg.error, cfg.errorTitle || 'Error');
    }

    onReady(function () {
        autoSelect2();
        autoUppercaseCountry();

        if (window.HdPageFlash) {
            flash(window.HdPageFlash);
        }
    });
})(jQuery);
