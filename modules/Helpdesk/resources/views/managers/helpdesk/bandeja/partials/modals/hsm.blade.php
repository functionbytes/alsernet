{{-- Modal: Plantillas WhatsApp HSM --}}
<div class="bv-modal" data-bv-modal-name="hsm">
    <div class="bv-modal-dialog lg">
        <div class="bv-modal-head">
            <div class="bv-modal-title">Plantillas WhatsApp (HSM)</div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div style="display:grid;grid-template-columns:260px 1fr;gap:14px">

                {{-- Left: list --}}
                <div>
                    <div class="bv-modal-search" style="margin-bottom:10px">
                        <i class="fas fa-magnifying-glass"></i>
                        <input id="hsm-search" type="text" placeholder="Buscar plantilla…" autocomplete="off">
                    </div>

                    {{-- Category pills --}}
                    <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:10px">
                        <button class="bv-chip on" data-cat="">Todas</button>
                        <button class="bv-chip" data-cat="Utilidad">Utilidad</button>
                        <button class="bv-chip" data-cat="Marketing">Marketing</button>
                        <button class="bv-chip" data-cat="Autenticación">Autenticación</button>
                    </div>

                    {{-- Template list --}}
                    <div class="bv-opt-list" id="hsm-list">
                        <div class="bv-opt on" data-tpl-id="1" data-cat="Utilidad"
                             data-body="Hola {{1}}, tu pedido #{{2}} ha sido confirmado. Total: {{3}}. Gracias por tu compra."
                             data-vars="Nombre del cliente,Número de pedido,Total del pedido">
                            <div class="body">
                                <div class="name" style="font-size:12px">Confirmación de pedido</div>
                                <div class="sub" style="display:flex;align-items:center;gap:5px">
                                    <span style="background:#d1fae5;color:#065f46;font-size:10px;padding:1px 6px;border-radius:10px;font-weight:600">Aprobada</span>
                                    <span>Utilidad</span>
                                    <span>ES</span>
                                </div>
                            </div>
                        </div>
                        <div class="bv-opt" data-tpl-id="2" data-cat="Utilidad"
                             data-body="Tu pedido #{{1}} está en camino. Seguimiento: {{2}}. Llegará aprox. el {{3}}."
                             data-vars="Número de pedido,Código de tracking,Fecha estimada">
                            <div class="body">
                                <div class="name" style="font-size:12px">Envío en camino</div>
                                <div class="sub" style="display:flex;align-items:center;gap:5px">
                                    <span style="background:#d1fae5;color:#065f46;font-size:10px;padding:1px 6px;border-radius:10px;font-weight:600">Aprobada</span>
                                    <span>Utilidad</span>
                                    <span>ES</span>
                                </div>
                            </div>
                        </div>
                        <div class="bv-opt" data-tpl-id="3" data-cat="Utilidad"
                             data-body="Hola {{1}}, te recordamos tu cita el {{2}} a las {{3}}. Responde CONFIRMAR o CANCELAR."
                             data-vars="Nombre del cliente,Fecha de cita,Hora de cita">
                            <div class="body">
                                <div class="name" style="font-size:12px">Recordatorio de cita</div>
                                <div class="sub" style="display:flex;align-items:center;gap:5px">
                                    <span style="background:#d1fae5;color:#065f46;font-size:10px;padding:1px 6px;border-radius:10px;font-weight:600">Aprobada</span>
                                    <span>Utilidad</span>
                                    <span>ES</span>
                                </div>
                            </div>
                        </div>
                        <div class="bv-opt" data-tpl-id="4" data-cat="Marketing"
                             data-body="¡Gracias {{1}}! ¿Cómo valorarías tu experiencia del 1 al 5?"
                             data-vars="Nombre del cliente">
                            <div class="body">
                                <div class="name" style="font-size:12px">Encuesta CSAT</div>
                                <div class="sub" style="display:flex;align-items:center;gap:5px">
                                    <span style="background:#fef3c7;color:#92400e;font-size:10px;padding:1px 6px;border-radius:10px;font-weight:600">En revisión</span>
                                    <span>Marketing</span>
                                    <span>ES</span>
                                </div>
                            </div>
                        </div>
                        <div class="bv-opt" data-tpl-id="5" data-cat="Autenticación"
                             data-body="Tu código de verificación es {{1}}. Válido durante {{2}} minutos."
                             data-vars="Código OTP,Minutos de validez">
                            <div class="body">
                                <div class="name" style="font-size:12px">Código de verificación</div>
                                <div class="sub" style="display:flex;align-items:center;gap:5px">
                                    <span style="background:#d1fae5;color:#065f46;font-size:10px;padding:1px 6px;border-radius:10px;font-weight:600">Aprobada</span>
                                    <span>Autenticación</span>
                                    <span>ES</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: preview + variables --}}
                <div>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--bv-text-muted);margin-bottom:8px;display:flex;align-items:center;justify-content:space-between">
                        <span>Vista previa</span>
                        <span id="hsm-status-badge" style="background:#d1fae5;color:#065f46;font-size:10px;padding:1px 8px;border-radius:10px;font-weight:600">Aprobada</span>
                    </div>

                    {{-- WhatsApp chat bubble --}}
                    <div style="background:#e5ddd5;border-radius:12px;padding:16px;min-height:80px;margin-bottom:12px;position:relative">
                        <div style="position:absolute;top:10px;left:50%;transform:translateX(-50%);font-size:10px;color:#667781;background:rgba(255,255,255,.6);padding:2px 10px;border-radius:10px">Hoy</div>
                        <div style="margin-top:22px">
                            <div class="bv-bubble" id="hsm-preview-bubble" style="max-width:90%;font-size:12.5px;line-height:1.5">
                                Hola <strong>{{1}}</strong>, tu pedido #<strong>{{2}}</strong> ha sido confirmado. Total: <strong>{{3}}</strong>. Gracias por tu compra.
                            </div>
                        </div>
                    </div>

                    {{-- Variables form --}}
                    <div class="bv-right-section-title" style="margin-bottom:8px">Variables</div>
                    <div id="hsm-vars-form" style="display:flex;flex-direction:column;gap:8px">
                        <div style="display:flex;align-items:center;gap:8px">
                            <span style="font-size:11px;font-weight:700;color:var(--bv-accent);min-width:36px;font-family:monospace">{{1}}</span>
                            <input type="text" placeholder="Nombre del cliente" data-var="1"
                                   style="flex:1;padding:7px 10px;border:1px solid var(--bv-border);border-radius:8px;font-size:12.5px;font-family:inherit">
                        </div>
                        <div style="display:flex;align-items:center;gap:8px">
                            <span style="font-size:11px;font-weight:700;color:var(--bv-accent);min-width:36px;font-family:monospace">{{2}}</span>
                            <input type="text" placeholder="Número de pedido" data-var="2"
                                   style="flex:1;padding:7px 10px;border:1px solid var(--bv-border);border-radius:8px;font-size:12.5px;font-family:inherit">
                        </div>
                        <div style="display:flex;align-items:center;gap:8px">
                            <span style="font-size:11px;font-weight:700;color:var(--bv-accent);min-width:36px;font-family:monospace">{{3}}</span>
                            <input type="text" placeholder="Total del pedido" data-var="3"
                                   style="flex:1;padding:7px 10px;border:1px solid var(--bv-border);border-radius:8px;font-size:12.5px;font-family:inherit">
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary"><i class="far fa-paper-plane"></i> Insertar en composer</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
$(document).on('click', '[data-bv-modal-name="hsm"] .bv-opt', function () {
    $('[data-bv-modal-name="hsm"] .bv-opt').removeClass('on');
    $(this).addClass('on');

    var body = $(this).data('body') || '';
    var vars = ($(this).data('vars') || '').split(',');
    var approved = $(this).find('span:first').hasClass('bg-green') || $(this).find('span').text().trim() === 'Aprobada';

    // Update preview
    var preview = body.replace(/\{\{(\d+)\}\}/g, function (match, n) {
        return '<strong>' + match + '</strong>';
    });
    $('#hsm-preview-bubble').html(preview);

    // Rebuild vars form
    var matches = [...body.matchAll(/\{\{(\d+)\}\}/g)];
    var form = '';
    matches.forEach(function (m, i) {
        form += '<div style="display:flex;align-items:center;gap:8px">' +
            '<span style="font-size:11px;font-weight:700;color:var(--bv-accent);min-width:36px;font-family:monospace">{{' + m[1] + '}}</span>' +
            '<input type="text" placeholder="' + (vars[i] ? vars[i].trim() : 'Variable ' + m[1]) + '" data-var="' + m[1] + '" ' +
            'style="flex:1;padding:7px 10px;border:1px solid var(--bv-border);border-radius:8px;font-size:12.5px;font-family:inherit">' +
            '</div>';
    });
    $('#hsm-vars-form').html(form);
});

// Category filter
$(document).on('click', '[data-bv-modal-name="hsm"] .bv-chip', function () {
    $('[data-bv-modal-name="hsm"] .bv-chip').removeClass('on');
    $(this).addClass('on');
    var cat = $(this).data('cat');
    $('[data-bv-modal-name="hsm"] .bv-opt').each(function () {
        $(this).toggle(!cat || $(this).data('cat') === cat);
    });
});

// Search
$(document).on('input', '#hsm-search', function () {
    var q = $(this).val().toLowerCase();
    $('[data-bv-modal-name="hsm"] .bv-opt').each(function () {
        var name = $(this).find('.name').text().toLowerCase();
        $(this).toggle(!q || name.includes(q));
    });
});

// Live variable preview
$(document).on('input', '#hsm-vars-form input', function () {
    var tpl = $('[data-bv-modal-name="hsm"] .bv-opt.on').data('body') || '';
    var updated = tpl;
    $('#hsm-vars-form input').each(function () {
        var n = $(this).data('var');
        var val = $(this).val() || '{{' + n + '}}';
        updated = updated.replace(new RegExp('\\{\\{' + n + '\\}\\}', 'g'), '<strong>' + $('<span>').text(val).html() + '</strong>');
    });
    $('#hsm-preview-bubble').html(updated);
});
</script>
@endpush
@endonce
