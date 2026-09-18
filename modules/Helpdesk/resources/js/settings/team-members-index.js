/**
 * Listado de miembros del equipo — settings/team/members.blade.php
 * Requiere: window.TeamMembersIndexConfig = { flash: {success, error}, clearFilterUrl, bulkActionUrl }
 */
$(document).ready(function() {
    var cfg = window.TeamMembersIndexConfig || {};

    var select2Lang = {
        noResults: function () { return 'Sin resultados'; },
        searching: function () { return 'Buscando...'; },
    };

    // ── Filtros avanzados ────────────────────────────────────────────────
    $('.select2-filter-modal').select2({
        dropdownParent: $('#members-filter-modal'),
        width: '100%',
        language: select2Lang,
    });

    $('#members-filter-apply-btn').on('click', function () {
        $('#filter-role').val($('#modal-role').val());
        $('#filter-group').val($('#modal-group').val());
        $('#filter-availability').val($('#modal-availability').val());
        $('#members-filter-modal').modal('hide');
        $('#filterForm').submit();
    });

    $('#members-filter-clear-btn').on('click', function () {
        window.location = cfg.clearFilterUrl;
    });

    // ── Acciones masivas ─────────────────────────────────────────────────
    $('.select2-bulk').select2({ dropdownParent: $('#bulk-modal'), width: '100%', language: select2Lang });

    var bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    // Cada accion pide un dato distinto: se muestra solo el que toca.
    $('#bulk-action-select').on('change', function () {
        var action = $(this).val();
        $('#bulk-availability-wrap').toggleClass('d-none', action !== 'availability');
        $('#bulk-group-wrap').toggleClass('d-none', action !== 'add_group' && action !== 'remove_group');
    });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        var action = $('#bulk-action-select').val();
        var ids = bulk.getIds();

        if (! action) { toastr.warning('Selecciona una accion.'); return; }
        if (! ids.length) { toastr.warning('Selecciona al menos un miembro.'); return; }

        var payload = { action: action, ids: ids };

        if (action === 'availability') {
            payload.availability = $('#bulk-availability').val();
        } else {
            payload.group_id = $('#bulk-group').val();
        }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: cfg.bulkActionUrl,
            method: 'POST',
            data: JSON.stringify(payload),
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

    HDSettingsCommon.flashToastr(cfg.flash);
});
