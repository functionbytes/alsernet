{{-- Modal: Cambiar prioridad --}}
<div class="bv-modal" data-bv-modal-name="priority">
    <div class="modal w-sm">
        <div class="modal-head">
            <div class="modal-icon"><i class="fa-solid fa-flag"></i></div>
            <div class="modal-title-wrap">
                <div class="modal-label">{{ __('helpdesk::helpdesk.inbox.modals.label_conversation') }}</div>
                <div class="modal-title">
                    {{ __('helpdesk::helpdesk.inbox.modals.priority_title') }}
                    @if(!empty($selectedConversation))<span class="chip">#{{ $selectedConversation->id }}</span>@endif
                </div>
            </div>
            <button class="modal-close" data-bv-close><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">

            <div class="prio-list">

                <button class="prio-opt" data-bv-value="low" data-bv-label="Baja" data-bv-color="muted" data-prio-sla="24" data-prio-desc="Sin impacto operativo">
                    <div class="prio-ico prio-ico--low"><i class="fas fa-chevron-down"></i></div>
                    <div class="prio-body">
                        <span class="prio-t">{{ __('helpdesk::helpdesk.inbox.modals.priority_low') }}</span>
                        <span class="prio-s">{{ __('helpdesk::helpdesk.inbox.modals.priority_low_desc') }}</span>
                    </div>
                    <span class="prio-meta">{{ __('helpdesk::helpdesk.inbox.modals.priority_low_sla') }}</span>
                    <div class="prio-radio"><i class="fas fa-check"></i></div>
                </button>

                <button class="prio-opt" data-bv-value="normal" data-bv-label="Normal" data-bv-color="info" data-prio-sla="8" data-prio-desc="Atención estándar">
                    <div class="prio-ico prio-ico--normal"><i class="fas fa-minus"></i></div>
                    <div class="prio-body">
                        <span class="prio-t">{{ __('helpdesk::helpdesk.inbox.modals.priority_normal') }}</span>
                        <span class="prio-s">{{ __('helpdesk::helpdesk.inbox.modals.priority_normal_desc') }}</span>
                    </div>
                    <span class="prio-meta">{{ __('helpdesk::helpdesk.inbox.modals.priority_normal_sla') }}</span>
                    <div class="prio-radio"><i class="fas fa-check"></i></div>
                </button>

                <button class="prio-opt" data-bv-value="high" data-bv-label="Alta" data-bv-color="warning" data-prio-sla="4" data-prio-desc="Impacto significativo">
                    <div class="prio-ico prio-ico--high"><i class="fas fa-chevron-up"></i></div>
                    <div class="prio-body">
                        <span class="prio-t">{{ __('helpdesk::helpdesk.inbox.modals.priority_high') }}</span>
                        <span class="prio-s">{{ __('helpdesk::helpdesk.inbox.modals.priority_high_desc') }}</span>
                    </div>
                    <span class="prio-meta">{{ __('helpdesk::helpdesk.inbox.modals.priority_high_sla') }}</span>
                    <div class="prio-radio"><i class="fas fa-check"></i></div>
                </button>

                <button class="prio-opt" data-bv-value="urgent" data-bv-label="Urgente" data-bv-color="danger" data-prio-sla="1" data-prio-desc="Operaciones críticas">
                    <div class="prio-ico prio-ico--urgent"><i class="fas fa-angles-up"></i></div>
                    <div class="prio-body">
                        <span class="prio-t">{{ __('helpdesk::helpdesk.inbox.modals.priority_urgent') }}</span>
                        <span class="prio-s">{{ __('helpdesk::helpdesk.inbox.modals.priority_urgent_desc') }}</span>
                    </div>
                    <span class="prio-meta">{{ __('helpdesk::helpdesk.inbox.modals.priority_urgent_sla') }}</span>
                    <div class="prio-radio"><i class="fas fa-check"></i></div>
                </button>

            </div>

            <div id="prio-callout" class="prio-callout">
                <i class="fas fa-circle-info"></i>
                <span id="prio-callout-text"></span>
            </div>

            <div class="prio-reason-wrap">
                <label class="prio-reason-lbl">{{ __('helpdesk::helpdesk.inbox.modals.priority_reason_label') }} <span class="prio-reason-opt">{{ __('helpdesk::helpdesk.inbox.modals.priority_reason_optional') }}</span></label>
                <textarea id="prio-reason" class="bv-finput" rows="2" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.priority_reason_placeholder') }}"></textarea>
            </div>

        </div>
        <div class="modal-foot">
            <button class="btn btn-primary" data-bv-apply="priority">{{ __('helpdesk::helpdesk.inbox.modals.priority_apply') }}</button>
            <button class="btn btn-outline" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
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
