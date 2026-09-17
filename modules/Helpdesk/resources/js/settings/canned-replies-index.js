/**
 * Pantalla settings/canned-replies/index.blade.php.
 * window.HdCannedRepliesConfig = { flashSuccess, flashError, bulkUrl } lo
 * imprime el Blade.
 *
 * Requiere settings-common-batch3.js (window.HdSettingsCommon) y core/js/bulk.js
 * (window.BulkActions) cargados antes.
 */
$(document).ready(function () {
    var cfg = window.HdCannedRepliesConfig || {};

    window.HdSettingsCommon.initDeleteModal();
    window.HdSettingsCommon.flashSession(cfg);

    // ── Filter modal ─────────────────────────────────────────────────
    window.HdSettingsCommon.initFilterModal({
        modalId: 'canned-replies-filter-modal',
        applyBtnId: 'canned-replies-filter-apply-btn',
        clearBtnId: 'canned-replies-filter-clear-btn',
        formId: 'canned-replies-filter-form',
        fields: [
            { modal: '#modal-category', hidden: '#filter-category' },
            { modal: '#modal-scope', hidden: '#filter-scope' },
        ],
    });

    // ── Bulk actions ─────────────────────────────────────────────────
    // Escrito a mano (en vez de HdSettingsCommon.initBulkActions) para
    // conservar el genero correcto en los mensajes ("una respuesta", no
    // "un respuesta" — el helper generico siempre antepone "un ").
    var bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        var action = $('#bulk-action-select').val();
        var ids = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos una respuesta.'); return; }

        var applyBulkAction = function () {
            $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

            $.ajax({
                url: cfg.bulkUrl,
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
                    toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error al procesar la acción.');
                    $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
                },
            });
        };

        if (action === 'delete') {
            window.__confirm('¿Eliminar ' + ids.length + ' respuesta(s) predefinida(s)? Esta acción no se puede deshacer.', applyBulkAction);
        } else {
            applyBulkAction();
        }
    });
});
