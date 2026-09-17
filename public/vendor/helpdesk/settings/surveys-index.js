/**
 * Listado de encuestas — modules/Helpdesk/resources/views/settings/surveys/index.blade.php
 * Requiere: window.SurveysIndexConfig = { flash: {success, error}, bulkActionUrl: string }
 */
$(document).ready(function () {
    var cfg = window.SurveysIndexConfig || {};

    HDSettingsCommon.flashToastr(cfg.flash);
    HDSettingsCommon.wireDeleteButtonModal();

    // ── Filtros avanzados ────────────────────────────────────────────
    $('.select2-filter-modal').select2({ dropdownParent: $('#surveys-filter-modal'), width: '100%' });

    $('#surveys-filter-apply-btn').on('click', function () {
        $('#filter-status').val($('#modal-status').val());
        $('#surveys-filter-modal').modal('hide');
        $('#surveys-filter-form').submit();
    });

    $('#surveys-filter-clear-btn').on('click', function () {
        $('#modal-status').val(null).trigger('change');
    });

    // ── Bulk actions ──────────────────────────────────────────────────
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        var action = $('#bulk-action-select').val();
        var ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos una encuesta.'); return; }

        var applyBulkAction = function () {
            var $btn = $('#bulk-apply-btn');
            $btn.prop('disabled', true).text('Procesando...');

            $.ajax({
                url: cfg.bulkActionUrl,
                method: 'POST',
                data: JSON.stringify({ action: action, ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
                contentType: 'application/json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    $('#bulk-modal').modal('hide');
                    toastr.success(res.message);
                    setTimeout(function () { location.reload(); }, 800);
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON?.message ?? 'Error al procesar la acción.');
                    $btn.prop('disabled', false).text('Aplicar');
                },
            });
        };

        if (action === 'delete') {
            window.__confirm('¿Eliminar ' + ids.length + ' encuesta(s)? Esta acción no se puede deshacer.', applyBulkAction);
        } else {
            applyBulkAction();
        }
    });
});
