{{-- Modal: Cerrar conversación --}}
<div class="bv-modal" data-bv-modal-name="close-conv">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div>
                <span class="bv-modal-label">Chat · Acción irreversible</span>
                <div class="bv-modal-title">
                    <i class="fas fa-circle-check bv-modal-title-icon bv-modal-title-icon--success"></i>
                    Cerrar conversación
                    @if(!empty($selectedConversation))<span class="bv-chip-id">#{{ $selectedConversation->id }}</span>@endif
                </div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            @include('helpdesk::managers.inbox.partials.modals._context-card')

            {{-- Reason header --}}
            <div class="bv-right-section-title bv-mb-8">Motivo de cierre</div>

            {{-- Radio cards --}}
            <div class="bv-close-reasons" id="close-reasons">
                <label class="bv-modal-radio-card on" data-reason="resolved">
                    <input type="radio" name="close_reason" value="resolved" checked class="d-none">
                    <span class="bv-modal-radio-icon bv-modal-radio-icon--success"><i class="fas fa-circle-check"></i></span>
                    <div class="body">
                        <div class="name">Resuelto</div>
                        <div class="sub">El cliente quedó satisfecho con la solución</div>
                    </div>
                    <span class="bv-modal-radio-dot"></span>
                </label>
                <label class="bv-modal-radio-card" data-reason="duplicated">
                    <input type="radio" name="close_reason" value="duplicated" class="d-none">
                    <span class="bv-modal-radio-icon bv-modal-radio-icon--muted"><i class="fas fa-clone"></i></span>
                    <div class="body">
                        <div class="name">Duplicado</div>
                        <div class="sub">Ya hay otra conversación abierta para este caso</div>
                    </div>
                    <span class="bv-modal-radio-dot"></span>
                </label>
                <label class="bv-modal-radio-card" data-reason="spam">
                    <input type="radio" name="close_reason" value="spam" class="d-none">
                    <span class="bv-modal-radio-icon bv-modal-radio-icon--danger"><i class="fas fa-ban"></i></span>
                    <div class="body">
                        <div class="name">Spam / no procede</div>
                        <div class="sub">Mensaje no solicitado o fuera de contexto</div>
                    </div>
                    <span class="bv-modal-radio-dot"></span>
                </label>
                <label class="bv-modal-radio-card" data-reason="unresponsive">
                    <input type="radio" name="close_reason" value="unresponsive" class="d-none">
                    <span class="bv-modal-radio-icon bv-modal-radio-icon--warning"><i class="fas fa-user-slash"></i></span>
                    <div class="body">
                        <div class="name">Sin respuesta del cliente</div>
                        <div class="sub">Cerrar por inactividad prolongada</div>
                    </div>
                    <span class="bv-modal-radio-dot"></span>
                </label>
                <label class="bv-modal-radio-card" data-reason="other">
                    <input type="radio" name="close_reason" value="other" class="d-none">
                    <span class="bv-modal-radio-icon bv-modal-radio-icon--muted"><i class="fas fa-ellipsis"></i></span>
                    <div class="body">
                        <div class="name">Otro motivo</div>
                        <div class="sub">
                            <input type="text" id="close-other-input" placeholder="Describe el motivo…"
                                   class="bv-close-other-input d-none">
                        </div>
                    </div>
                    <span class="bv-modal-radio-dot"></span>
                </label>
            </div>

            {{-- Toggles --}}
            <div class="bv-modal-divider bv-modal-divider--my-14"></div>

            <label class="bv-modal-check">
                <input type="checkbox" id="close-csat" checked>
                <span>Enviar encuesta CSAT al cliente</span>
            </label>

            {{-- CSAT star preview --}}
            <div id="close-csat-preview" class="bv-csat-stars">
                <i class="fas fa-star bv-star-active"></i>
                <i class="fas fa-star bv-star-active"></i>
                <i class="fas fa-star bv-star-active"></i>
                <i class="fas fa-star bv-star-active"></i>
                <i class="fas fa-star bv-star-inactive"></i>
            </div>

            <label class="bv-modal-check bv-modal-check-mt">
                <input type="checkbox" id="close-notify" checked>
                <span>Notificar al cliente con plantilla de cierre</span>
            </label>

            {{-- Internal note --}}
            <div class="bv-right-section-title bv-mt-14 bv-mb-6">Nota final (interna, opcional)</div>
            <textarea class="bv-modal-ta" rows="3"
                      placeholder="Ej: Cliente confirmó recepción del pedido reenviado el 18/04 a las 16:30."></textarea>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-success" id="bv-close-apply">Resolver y cerrar</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
$(document).on('click', '[data-bv-modal-name="close-conv"] .bv-modal-radio-card', function () {
    $('[data-bv-modal-name="close-conv"] .bv-modal-radio-card').removeClass('on');
    $(this).addClass('on');
    $(this).find('input[type="radio"]').prop('checked', true);
    $('#close-other-input').hide();
    if ($(this).data('reason') === 'other') {
        $('#close-other-input').show().focus();
    }
});

$(document).on('change', '#close-csat', function () {
    $('#close-csat-preview').toggle($(this).is(':checked'));
});
</script>
@endpush
@endonce
