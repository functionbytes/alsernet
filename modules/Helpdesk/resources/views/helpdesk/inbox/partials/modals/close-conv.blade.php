{{-- Modal: Cerrar conversación --}}
<div class="bv-modal" data-bv-modal-name="close-conv">
    <div class="modal w-md">
        <div class="modal-head">
            <div class="modal-icon"><i class="fa-solid fa-check"></i></div>
            <div class="modal-title-wrap">
                <div class="modal-label">CHAT · ACCIÓN IRREVERSIBLE</div>
                <div class="modal-title">
                    Cerrar conversación
                    @if(!empty($selectedConversation))<span class="chip">#{{ $selectedConversation->id }}</span>@endif
                </div>
            </div>
            <button class="modal-close" data-bv-close><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="modal-body">

            @include('helpdesk::helpdesk.inbox.partials.modals._context-card')

            <div class="field">
                <div class="flabel">Motivo de cierre <span class="req">*</span></div>
                <div class="reason-list" id="close-reasons">
                    <div class="reason on" data-reason="resolved">
                        <input type="radio" name="close_reason" value="resolved" checked class="d-none">
                        <div class="ic"><i class="fa-solid fa-check-double"></i></div>
                        <div class="body">
                            <span class="t">Resuelto</span>
                            <span class="s">El cliente quedó satisfecho con la solución</span>
                        </div>
                        <div class="radio"></div>
                    </div>
                    <div class="reason" data-reason="duplicated">
                        <input type="radio" name="close_reason" value="duplicated" class="d-none">
                        <div class="ic"><i class="fa-solid fa-copy"></i></div>
                        <div class="body">
                            <span class="t">Duplicado</span>
                            <span class="s">Ya hay otra conversación abierta para este caso</span>
                        </div>
                        <div class="radio"></div>
                    </div>
                    <div class="reason" data-reason="spam">
                        <input type="radio" name="close_reason" value="spam" class="d-none">
                        <div class="ic"><i class="fa-solid fa-ban"></i></div>
                        <div class="body">
                            <span class="t">Spam / no procede</span>
                            <span class="s">Mensaje no solicitado o fuera de contexto</span>
                        </div>
                        <div class="radio"></div>
                    </div>
                    <div class="reason" data-reason="unresponsive">
                        <input type="radio" name="close_reason" value="unresponsive" class="d-none">
                        <div class="ic"><i class="fa-regular fa-clock"></i></div>
                        <div class="body">
                            <span class="t">Sin respuesta del cliente</span>
                            <span class="s">Cerrar por inactividad prolongada</span>
                        </div>
                        <div class="radio"></div>
                    </div>
                    <div class="reason" data-reason="other">
                        <input type="radio" name="close_reason" value="other" class="d-none">
                        <div class="ic"><i class="fa-solid fa-ellipsis"></i></div>
                        <div class="body">
                            <span class="t">Otro motivo</span>
                            <input type="text" id="close-other-input"
                                   placeholder="Describe el motivo…"
                                   class="finput bv-close-other-input mt-1"
                                   style="display:none">
                        </div>
                        <div class="radio"></div>
                    </div>
                </div>
            </div>

            <div class="field">
                <label class="check">
                    <input type="checkbox" id="close-csat" checked>
                    Enviar encuesta CSAT al cliente
                </label>
                <label class="check">
                    <input type="checkbox" id="close-notify" checked>
                    Notificar al cliente con plantilla de cierre
                </label>
            </div>

            <div class="field">
                <div class="flabel">Nota final <span class="hint">interna, opcional</span></div>
                <textarea class="finput bv-modal-ta" rows="3"
                          placeholder="Ej: Cliente confirmó recepción del pedido reenviado el 18/04 a las 16:30."></textarea>
            </div>

        </div>

        <div class="modal-foot">
            <button class="btn btn-primary" id="bv-close-apply">Resolver y cerrar</button>
            <button class="btn btn-outline" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
$(document).on('click', '[data-bv-modal-name="close-conv"] .reason', function () {
    $('[data-bv-modal-name="close-conv"] .reason').removeClass('on');
    $(this).addClass('on');
    $(this).find('input[type="radio"]').prop('checked', true);
    $('#close-other-input').hide();
    if ($(this).data('reason') === 'other') {
        $('#close-other-input').show().focus();
    }
});


</script>
@endpush
@endonce
