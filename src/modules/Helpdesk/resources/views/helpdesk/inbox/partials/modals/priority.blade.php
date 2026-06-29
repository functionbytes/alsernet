{{-- Modal: Cambiar prioridad --}}
<div class="bv-modal" data-bv-modal-name="priority">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-flag"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">BANDEJA · CONVERSACIÓN</span>
                <div class="bv-modal-title">
                    Cambiar prioridad
                    @if(!empty($selectedConversation))<span class="bv-chip-id">#{{ $selectedConversation->id }}</span>@endif
                </div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="prio-list">

                <button class="prio-opt" data-bv-value="low" data-bv-label="Baja" data-bv-color="muted" data-prio-sla="24" data-prio-desc="Sin impacto operativo">
                    <div class="prio-ico prio-ico--low"><i class="fas fa-chevron-down"></i></div>
                    <div class="prio-body">
                        <span class="prio-t">Baja</span>
                        <span class="prio-s">Sin impacto operativo · SLA 24h</span>
                    </div>
                    <span class="prio-meta">SLA 24h</span>
                    <div class="prio-radio"><i class="fas fa-check"></i></div>
                </button>

                <button class="prio-opt" data-bv-value="normal" data-bv-label="Normal" data-bv-color="info" data-prio-sla="8" data-prio-desc="Atención estándar">
                    <div class="prio-ico prio-ico--normal"><i class="fas fa-minus"></i></div>
                    <div class="prio-body">
                        <span class="prio-t">Normal</span>
                        <span class="prio-s">Atención estándar · SLA 8h</span>
                    </div>
                    <span class="prio-meta">SLA 8h</span>
                    <div class="prio-radio"><i class="fas fa-check"></i></div>
                </button>

                <button class="prio-opt" data-bv-value="high" data-bv-label="Alta" data-bv-color="warning" data-prio-sla="4" data-prio-desc="Impacto significativo">
                    <div class="prio-ico prio-ico--high"><i class="fas fa-chevron-up"></i></div>
                    <div class="prio-body">
                        <span class="prio-t">Alta</span>
                        <span class="prio-s">Impacto significativo · SLA 4h</span>
                    </div>
                    <span class="prio-meta">SLA 4h</span>
                    <div class="prio-radio"><i class="fas fa-check"></i></div>
                </button>

                <button class="prio-opt" data-bv-value="urgent" data-bv-label="Urgente" data-bv-color="danger" data-prio-sla="1" data-prio-desc="Operaciones críticas">
                    <div class="prio-ico prio-ico--urgent"><i class="fas fa-angles-up"></i></div>
                    <div class="prio-body">
                        <span class="prio-t">Urgente</span>
                        <span class="prio-s">Operaciones críticas · SLA 1h</span>
                    </div>
                    <span class="prio-meta">SLA 1h</span>
                    <div class="prio-radio"><i class="fas fa-check"></i></div>
                </button>

            </div>

            <div id="prio-callout" class="prio-callout">
                <i class="fas fa-circle-info"></i>
                <span id="prio-callout-text"></span>
            </div>

            <div class="prio-reason-wrap">
                <label class="prio-reason-lbl">Razón del cambio <span class="prio-reason-opt">(opcional)</span></label>
                <textarea id="prio-reason" class="bv-finput" rows="2" placeholder="Explica brevemente por qué cambias la prioridad…"></textarea>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" data-bv-apply="priority">Aplicar prioridad</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    var slaText = {
        low:    'Al cambiar a Baja se ajustará el SLA a 24 horas. El cliente recibirá atención en el siguiente ciclo.',
        normal: 'Al cambiar a Normal se ajustará el SLA a 8 horas. Atención estándar dentro del horario laboral.',
        high:   'Al cambiar a Alta se ajustará el SLA a 4 horas. Se notificará al agente asignado inmediatamente.',
        urgent: 'Al cambiar a Urgente se ajustará el SLA a 1 hora. Se escalará y notificará al equipo de guardia.'
    };

    function updateCallout(val) {
        var txt = slaText[val] || '';
        $('#prio-callout-text').text(txt);
        $('#prio-callout').toggleClass('show', !!txt);
    }

    $(document).on('click', '[data-bv-modal-name="priority"] .prio-opt', function () {
        $(this).closest('.prio-list').find('.prio-opt').removeClass('on');
        $(this).addClass('on');
        updateCallout($(this).data('bv-value'));
    });

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'priority') { return; }
        var cur = $('.bv-th-pill[data-bv-modal="priority"]').attr('data-bv-value') || 'normal';
        $('[data-bv-modal-name="priority"] .prio-opt').removeClass('on');
        $('[data-bv-modal-name="priority"] .prio-opt[data-bv-value="' + cur + '"]').addClass('on');
        $('#prio-reason').val('');
        updateCallout(cur);
    });
}());
</script>
@endpush
@endonce
