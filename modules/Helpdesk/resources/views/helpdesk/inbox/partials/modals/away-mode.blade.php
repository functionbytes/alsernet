{{-- Modal: Modo ausente / disponibilidad (#60 ve-away-mode) --}}
<div class="bv-modal" data-bv-modal-name="away-mode">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="far fa-moon"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">AGENTE · ESTADO</span>
                <div class="bv-modal-title">Cambiar disponibilidad</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-form-field">
                <label class="bv-form-label">Tu estado actual</label>
                <div class="bv-opt-list" id="awayModeList">
                    <button type="button" class="bv-opt" data-bv-value="available">
                        <div class="bv-opt__ic"><i class="fas fa-circle" style="color:var(--success,#13c672);font-size:9px"></i></div>
                        <div class="bv-opt__body">
                            <span class="bv-opt__t">Disponible</span>
                            <span class="bv-opt__s">Recibes nuevas conversaciones</span>
                        </div>
                        <div class="bv-opt__radio"></div>
                    </button>
                    <button type="button" class="bv-opt" data-bv-value="away">
                        <div class="bv-opt__ic"><i class="far fa-moon"></i></div>
                        <div class="bv-opt__body">
                            <span class="bv-opt__t">Ausente</span>
                            <span class="bv-opt__s">No recibes nuevas asignaciones</span>
                        </div>
                        <div class="bv-opt__radio"></div>
                    </button>
                    <button type="button" class="bv-opt" data-bv-value="busy">
                        <div class="bv-opt__ic"><i class="fas fa-mug-hot"></i></div>
                        <div class="bv-opt__body">
                            <span class="bv-opt__t">Ocupado</span>
                            <span class="bv-opt__s">Pausa breve · vuelves en 15-30 min</span>
                        </div>
                        <div class="bv-opt__radio"></div>
                    </button>
                    <button type="button" class="bv-opt" data-bv-value="offline">
                        <div class="bv-opt__ic"><i class="fas fa-circle-xmark" style="color:var(--text-muted)"></i></div>
                        <div class="bv-opt__body">
                            <span class="bv-opt__t">No disponible</span>
                            <span class="bv-opt__s">Fuera de horario</span>
                        </div>
                        <div class="bv-opt__radio"></div>
                    </button>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-away-mode-confirm">Guardar estado</button>
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

    function setPresenceDot(state) {
        var map = { available: 'online', away: 'away', busy: 'busy', offline: 'offline' };
        var dotClass = map[state] || 'offline';
        $('.bv-agent-presence-dot').attr('class', 'bv-agent-presence-dot bv-av-dot ' + dotClass);
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'away-mode') { return; }
        var current = $('body').data('agent-state') || 'available';
        $('#awayModeList .bv-opt').removeClass('on');
        $('#awayModeList .bv-opt[data-bv-value="' + current + '"]').addClass('on');
    });

    $(document).on('click', '#awayModeList .bv-opt', function () {
        $('#awayModeList .bv-opt').removeClass('on');
        $(this).addClass('on');
    });

    $(document).on('click', '#bv-away-mode-confirm', function () {
        var state = $('#awayModeList .bv-opt.on').data('bv-value') || 'available';
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/panel/helpdesk/presence/state',
            method: 'POST',
            data: { state: state },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            $('body').data('agent-state', state);
            setPresenceDot(state);
            closeBvModal('away-mode');
            var labels = { available: 'Disponible', away: 'Ausente', busy: 'Ocupado', offline: 'No disponible' };
            if (window.toastr) { toastr.success('Estado actualizado: ' + (labels[state] || state)); }
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al actualizar estado';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () { $btn.prop('disabled', false); });
    });

}(window.jQuery));
</script>
@endpush
@endonce
