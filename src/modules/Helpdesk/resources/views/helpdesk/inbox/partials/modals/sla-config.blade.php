{{-- Modal: SLA por canal (#76 ve-sla-config) --}}
<div class="bv-modal" data-bv-modal-name="sla-config">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="far fa-clock"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">CONFIG · SLA</span>
                <div class="bv-modal-title">Tiempos de respuesta por canal</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div id="slaConfigLoading" class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>

            <div id="slaConfigContent" style="display:none">
                <div class="bv-sla-rows" id="slaConfigRows"></div>

                <label class="bv-check" style="margin-top:12px">
                    <input type="checkbox" id="slaPauseOffHours" checked>
                    Pausar SLA fuera de horario de atención
                </label>
                <label class="bv-check">
                    <input type="checkbox" id="slaNotifySupervisor" checked>
                    Notificar al supervisor al 80% del tiempo
                </label>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-sla-save" disabled>Guardar cambios</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    function escHtml(s) {
        return $('<span>').text(s || '').html();
    }

    var _slaData = [];

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'sla-config') { return; }
        _slaData = [];
        $('#slaConfigContent').hide();
        $('#slaConfigLoading').show();
        $('#bv-sla-save').prop('disabled', true);

        $.ajax({
            url: '/panel/helpdesk/settings/sla',
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (resp) {
            _slaData = resp.data || resp || [];
            var html = _slaData.map(function (row, i) {
                return '<div class="bv-lp-row">' +
                    '<span class="bv-lp-row__k">' + escHtml(row.label) + '</span>' +
                    '<input type="text" class="bv-form-input bv-sla-input" data-sla-idx="' + i + '" value="' + escHtml(row.value) + '" style="width:100px;text-align:center;font-family:monospace">' +
                    '</div>';
            }).join('');
            $('#slaConfigRows').html(html);
            $('#slaPauseOffHours').prop('checked', resp.pause_off_hours !== false);
            $('#slaNotifySupervisor').prop('checked', resp.notify_supervisor !== false);
            $('#slaConfigLoading').hide();
            $('#slaConfigContent').show();
            $('#bv-sla-save').prop('disabled', false);
        }).fail(function () {
            $('#slaConfigLoading').hide();
        });
    });

    $(document).on('click', '#bv-sla-save', function () {
        $('.bv-sla-input').each(function () {
            var idx = parseInt($(this).data('sla-idx'), 10);
            if (_slaData[idx]) { _slaData[idx].value = $(this).val().trim(); }
        });

        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando…');

        $.ajax({
            url: '/panel/helpdesk/settings/sla',
            method: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify({
                sla:               _slaData,
                pause_off_hours:   $('#slaPauseOffHours').is(':checked'),
                notify_supervisor: $('#slaNotifySupervisor').is(':checked'),
            }),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('sla-config');
            if (window.toastr) { toastr.success('Configuración SLA guardada'); }
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al guardar SLA';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Guardar cambios');
        });
    });

}(window.jQuery));
</script>
@endpush
@endonce
