/**
 * Listado de vistas guardadas de tickets — settings/views/index.blade.php
 * Requiere: window.ViewsIndexConfig = { flash: {success, error}, bulkActionUrl, reorderUrl }
 */
$(document).ready(function () {
    var cfg = window.ViewsIndexConfig || {};

    HDSettingsCommon.flashToastr(cfg.flash);
    HDSettingsCommon.wireDeleteButtonModal();

    // ── Filter modal ─────────────────────────────────────────────────
    $('.select2-filter-modal').select2({ dropdownParent: $('#views-filter-modal'), width: '100%' });

    // ── Bulk actions ──────────────────────────────────────────────────
    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#views-filter-apply-btn').on('click', function () {
        $('#filter-scope').val($('#modal-scope').val());
        $('#views-filter-modal').modal('hide');
        $('#views-filter-form').submit();
    });

    $('#views-filter-clear-btn').on('click', function () {
        $('#modal-scope').val(null).trigger('change');
    });

    // ── Bulk actions ──────────────────────────────────────────────────
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        var action = $('#bulk-action-select').val();
        var ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos una vista.'); return; }

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
            window.__confirm('¿Eliminar ' + ids.length + ' vista(s)? Esta acción no se puede deshacer.', applyBulkAction);
        } else {
            applyBulkAction();
        }
    });

    // ── Drag-drop reorder (jQuery UI Sortable) ────────────────────────
    if ($('#views-sortable').length) {
        $('#views-sortable').sortable({
            // Ver ticket-statuses: la fila entera es el asidero de arrastre.
            handle: 'tr',
            cancel: 'input,textarea,button,select,option,a',
            axis: 'y',
            cursor: 'grabbing',
            start: function (e, ui) {
                ui.item.addClass('table-active');
            },
            stop: function (e, ui) {
                ui.item.removeClass('table-active');
            },
            update: function () {
                const ids = $('#views-sortable tr').map(function () {
                    return $(this).data('id');
                }).get();

                $.ajax({
                    url: cfg.reorderUrl,
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ ids }),
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (res) {
                    },
                    error: function () {
                        toastr.error('Error al actualizar el orden.');
                        $('#views-sortable').sortable('cancel');
                    },
                });
            },
        });
    }
});
