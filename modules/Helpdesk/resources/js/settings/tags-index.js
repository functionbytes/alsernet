/**
 * Listado de etiquetas — modules/Helpdesk/resources/views/settings/tags/index.blade.php
 * Requiere: window.TagsIndexConfig = { flash: {success, error}, bulkActionUrl: string }
 */
$(document).ready(function () {
    var cfg = window.TagsIndexConfig || {};

    HDSettingsCommon.flashToastr(cfg.flash);
    HDSettingsCommon.wireDeleteButtonModal();

    // Filter modal
    $('.select2-filter-modal').select2({ dropdownParent: $('#tags-filter-modal'), width: '100%' });

    $('#tags-filter-apply-btn').on('click', function () {
        $('#filter-status').val($('#modal-status').val());
        $('#tags-filter-modal').modal('hide');
        $('#tags-filter-form').submit();
    });

    $('#tags-filter-clear-btn').on('click', function () {
        $('#modal-status').val(null).trigger('change');
    });

    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos una etiqueta.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar las ' + ids.length + ' etiqueta(s) seleccionadas?')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: cfg.bulkActionUrl,
            method: 'POST',
            data: JSON.stringify({ action: action, ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.message);
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });
});
