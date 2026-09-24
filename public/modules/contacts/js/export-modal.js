/**
 * Modal "Exportar contactos" (contacts/partials/_export-modal.blade.php).
 *
 * Reconstruye el href de descarga a partir de data-export-base-url según los
 * checkboxes de grupo marcados — sin AJAX, es un enlace de descarga normal
 * (misma query de filtros que ya llevaba el enlace directo).
 *
 * Si se abre desde "Exportar selección" de la barra masiva
 * ([data-export-selection]) el enlace añade además ids[] con los contactos
 * marcados; abierto desde la barra de herramientas exporta la vista actual.
 */
(function () {
    'use strict';

    $(function () {
        var $modal = $('#contact-export-modal');
        if (!$modal.length) {
            return;
        }

        var baseUrl = $modal.data('export-base-url');
        var $scope = $('#export-scope-value');
        var selectedIds = [];

        function updateHref() {
            var url = new URL(baseUrl, window.location.origin);
            url.searchParams.delete('columns[]');
            url.searchParams.delete('ids[]');
            if ($('#export-col-health').is(':checked')) {
                url.searchParams.append('columns[]', 'health');
            }
            if ($('#export-col-external').is(':checked')) {
                url.searchParams.append('columns[]', 'external');
            }
            selectedIds.forEach(function (id) {
                url.searchParams.append('ids[]', id);
            });
            $('#contact-export-download-btn').attr('href', url.toString());
        }

        $modal.on('show.bs.modal', function (e) {
            var fromSelection = e.relatedTarget && $(e.relatedTarget).is('[data-export-selection]');

            selectedIds = fromSelection
                ? $('.contact-check:checked').map(function () { return this.value; }).get()
                : [];

            $scope.text(selectedIds.length
                ? 'la selección · ' + selectedIds.length + ' contacto' + (selectedIds.length === 1 ? '' : 's')
                : $scope.data('default'));

            updateHref();
        });
        $modal.on('change', '#export-col-health, #export-col-external', updateHref);

        $modal.on('click', '#contact-export-download-btn', function () {
            $modal.modal('hide');
        });
    });
})();
