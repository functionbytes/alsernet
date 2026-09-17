/**
 * HelpdeskCampaigns — campaigns-list.js
 * Listado de campañas: flash toasts, modal de borrado y acciones masivas.
 * Espera `window.HcmCampaignsList = { bulkActionUrl, flashSuccess, flashError }`
 * inyectado desde managers/campaigns/index.blade.php.
 */
$(function () {
    const config = window.HcmCampaignsList || {};

    if (config.flashSuccess) {
        toastr.success(config.flashSuccess, 'Exito');
    }
    if (config.flashError) {
        toastr.error(config.flashError, 'Error');
    }

    $(document).on('click', '.delete-btn', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    function refreshBulkBar() {
        const checked = $('.bulk-row:checked').length;
        $('#bulk-count').text(checked);
        $('#bulk-bar').toggleClass('d-none', checked === 0);
        $('#bulk-apply').prop('disabled', checked === 0 || !$('#bulk-action').val());
    }

    $('#bulk-select-all').on('change', function () {
        $('.bulk-row').prop('checked', $(this).is(':checked'));
        refreshBulkBar();
    });

    $(document).on('change', '.bulk-row, #bulk-action', refreshBulkBar);

    $('#bulk-apply').on('click', function () {
        const action = $('#bulk-action').val();
        const ids = $('.bulk-row:checked').map((_, el) => parseInt(el.value, 10)).get();
        if (!action || ids.length === 0) return;

        if (action === 'delete' && !confirm(`¿Eliminar ${ids.length} campaña(s)?`)) return;

        $.ajax({
            url: config.bulkActionUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { action, ids },
            success: function (res) {
                toastr.success(res.message || 'Acción aplicada');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error en la acción masiva');
            }
        });
    });
});
