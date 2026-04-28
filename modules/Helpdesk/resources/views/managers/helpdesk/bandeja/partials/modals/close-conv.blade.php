{{-- Modal: Cerrar conversación --}}
<div class="bv-modal" data-bv-modal-name="close-conv">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title">Cerrar conversación</div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Reason header --}}
            <div class="bv-right-section-title" style="margin-bottom:8px">Motivo de cierre</div>

            {{-- Radio cards --}}
            <div style="display:flex;flex-direction:column;gap:6px" id="close-reasons">
                <label class="bv-modal-radio-card on" data-reason="resolved">
                    <input type="radio" name="close_reason" value="resolved" checked style="display:none">
                    <span class="bv-modal-radio-icon" style="color:#10b981"><i class="fas fa-circle-check"></i></span>
                    <div class="body">
                        <div class="name">Resuelto</div>
                        <div class="sub">El cliente quedó satisfecho con la solución</div>
                    </div>
                    <span class="bv-modal-radio-dot"></span>
                </label>
                <label class="bv-modal-radio-card" data-reason="duplicated">
                    <input type="radio" name="close_reason" value="duplicated" style="display:none">
                    <span class="bv-modal-radio-icon" style="color:#6b7280"><i class="fas fa-clone"></i></span>
                    <div class="body">
                        <div class="name">Duplicado</div>
                        <div class="sub">Ya hay otra conversación abierta para este caso</div>
                    </div>
                    <span class="bv-modal-radio-dot"></span>
                </label>
                <label class="bv-modal-radio-card" data-reason="spam">
                    <input type="radio" name="close_reason" value="spam" style="display:none">
                    <span class="bv-modal-radio-icon" style="color:#ef4444"><i class="fas fa-ban"></i></span>
                    <div class="body">
                        <div class="name">Spam / no procede</div>
                        <div class="sub">Mensaje no solicitado o fuera de contexto</div>
                    </div>
                    <span class="bv-modal-radio-dot"></span>
                </label>
                <label class="bv-modal-radio-card" data-reason="unresponsive">
                    <input type="radio" name="close_reason" value="unresponsive" style="display:none">
                    <span class="bv-modal-radio-icon" style="color:#f59e0b"><i class="fas fa-user-slash"></i></span>
                    <div class="body">
                        <div class="name">Sin respuesta del cliente</div>
                        <div class="sub">Cerrar por inactividad prolongada</div>
                    </div>
                    <span class="bv-modal-radio-dot"></span>
                </label>
                <label class="bv-modal-radio-card" data-reason="other">
                    <input type="radio" name="close_reason" value="other" style="display:none">
                    <span class="bv-modal-radio-icon" style="color:#6b7280"><i class="fas fa-ellipsis"></i></span>
                    <div class="body">
                        <div class="name">Otro motivo</div>
                        <div class="sub">
                            <input type="text" id="close-other-input" placeholder="Describe el motivo…"
                                   style="width:100%;margin-top:4px;padding:5px 8px;border:1px solid var(--bv-border);border-radius:7px;font-size:12px;font-family:inherit;display:none">
                        </div>
                    </div>
                    <span class="bv-modal-radio-dot"></span>
                </label>
            </div>

            {{-- Toggles --}}
            <div class="bv-modal-divider" style="margin:14px 0"></div>

            <label class="bv-modal-check">
                <input type="checkbox" id="close-csat" checked>
                <span>Enviar encuesta CSAT al cliente</span>
            </label>

            {{-- CSAT star preview --}}
            <div id="close-csat-preview" style="display:flex;gap:4px;margin:8px 0 4px 20px">
                <i class="fas fa-star" style="color:#fbbf24;font-size:16px"></i>
                <i class="fas fa-star" style="color:#fbbf24;font-size:16px"></i>
                <i class="fas fa-star" style="color:#fbbf24;font-size:16px"></i>
                <i class="fas fa-star" style="color:#fbbf24;font-size:16px"></i>
                <i class="fas fa-star" style="color:#d1d5db;font-size:16px"></i>
            </div>

            <label class="bv-modal-check" style="margin-top:4px">
                <input type="checkbox" id="close-notify" checked>
                <span>Notificar al cliente con plantilla de cierre</span>
            </label>

            {{-- Internal note --}}
            <div class="bv-right-section-title" style="margin-top:14px;margin-bottom:6px">Nota final (interna, opcional)</div>
            <textarea class="bv-modal-ta" rows="3"
                      placeholder="Ej: Cliente confirmó recepción del pedido reenviado el 18/04 a las 16:30."></textarea>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary"><i class="fas fa-check"></i> Cerrar conversación</button>
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
