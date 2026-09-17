/**
 * Listado de templates de WhatsApp — settings/whatsapp-templates/index.blade.php
 * Requiere: window.WhatsappTemplatesIndexConfig = { flash: {success, error}, bulkActionUrl }
 */
$(document).ready(function () {
    var cfg = window.WhatsappTemplatesIndexConfig || {};

    HDSettingsCommon.flashToastr(cfg.flash);

    // El contenedor de filtros puede ser modal o panel lateral segun el .env:
    // se localiza y se cierra a traves de FilterShell.
    $('.select2-filter-modal').select2({
        dropdownParent: window.FilterShell.el('whatsapp-templates-filter-modal'),
        width: '100%',
    });

    $('#whatsapp-templates-filter-apply-btn').on('click', function () {
        $('#filter-status').val($('#modal-status').val());
        $('#filter-category').val($('#modal-category').val());
        $('#filter-language').val($('#modal-language').val());
        window.FilterShell.close('whatsapp-templates-filter-modal');
        $('#whatsapp-templates-filter-form').submit();
    });

    $('#whatsapp-templates-filter-clear-btn').on('click', function () {
        $('#modal-status, #modal-category, #modal-language').val(null).trigger('change');
    });

    // ── Acciones masivas ─────────────────────────────────────────────────
    if (document.querySelector('.bulk-checkbox')) {
        $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

        var bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

        $('#bulk-modal').on('hide.bs.modal', function () {
            $('#bulk-action-select').val('').trigger('change');
            $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            bulk.reset();
        });

        $('#bulk-apply-btn').on('click', function () {
            var action = $('#bulk-action-select').val();
            var ids = bulk.getIds();

            if (! action) { toastr.warning('Selecciona una accion.'); return; }
            if (! ids.length) { toastr.warning('Selecciona al menos una plantilla.'); return; }

            $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

            $.ajax({
                url: cfg.bulkActionUrl,
                method: 'POST',
                data: JSON.stringify({ action: action, ids: ids }),
                contentType: 'application/json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    $('#bulk-modal').modal('hide');
                    toastr.success(res.message);
                    setTimeout(function () { location.reload(); }, 800);
                },
                error: function (xhr) {
                    toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error al procesar.');
                    $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
                },
            });
        });
    }
});
