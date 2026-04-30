{{-- Modal: Plantillas WhatsApp HSM --}}
<div class="bv-modal" data-bv-modal-name="hsm">
    <div class="bv-modal-dialog lg">
        <div class="bv-modal-head">
            <div class="bv-modal-title"><i class="fab fa-whatsapp bv-modal-title-icon bv-modal-title-icon--whatsapp"></i> Plantillas WhatsApp (HSM)</div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-hsm-grid">

                {{-- Left: list --}}
                <div>
                    <div class="bv-modal-search bv-modal-search-mb10">
                        <i class="fas fa-magnifying-glass"></i>
                        <input id="hsm-search" type="text" placeholder="Buscar plantilla…" autocomplete="off">
                    </div>

                    {{-- Category pills --}}
                    <div class="bv-hsm-cats">
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
                                <div class="name bv-tpl-name">Confirmación de pedido</div>
                                <div class="sub bv-tpl-sub">
                                    <span class="bv-tpl-badge bv-tpl-badge-approved">Aprobada</span>
                                    <span>Utilidad</span>
                                    <span>ES</span>
                                </div>
                            </div>
                        </div>
                        <div class="bv-opt" data-tpl-id="2" data-cat="Utilidad"
                             data-body="Tu pedido #{{1}} está en camino. Seguimiento: {{2}}. Llegará aprox. el {{3}}."
                             data-vars="Número de pedido,Código de tracking,Fecha estimada">
                            <div class="body">
                                <div class="name bv-tpl-name">Envío en camino</div>
                                <div class="sub bv-tpl-sub">
                                    <span class="bv-tpl-badge bv-tpl-badge-approved">Aprobada</span>
                                    <span>Utilidad</span>
                                    <span>ES</span>
                                </div>
                            </div>
                        </div>
                        <div class="bv-opt" data-tpl-id="3" data-cat="Utilidad"
                             data-body="Hola {{1}}, te recordamos tu cita el {{2}} a las {{3}}. Responde CONFIRMAR o CANCELAR."
                             data-vars="Nombre del cliente,Fecha de cita,Hora de cita">
                            <div class="body">
                                <div class="name bv-tpl-name">Recordatorio de cita</div>
                                <div class="sub bv-tpl-sub">
                                    <span class="bv-tpl-badge bv-tpl-badge-approved">Aprobada</span>
                                    <span>Utilidad</span>
                                    <span>ES</span>
                                </div>
                            </div>
                        </div>
                        <div class="bv-opt" data-tpl-id="4" data-cat="Marketing"
                             data-body="¡Gracias {{1}}! ¿Cómo valorarías tu experiencia del 1 al 5?"
                             data-vars="Nombre del cliente">
                            <div class="body">
                                <div class="name bv-tpl-name">Encuesta CSAT</div>
                                <div class="sub bv-tpl-sub">
                                    <span class="bv-tpl-badge bv-tpl-badge-review">En revisión</span>
                                    <span>Marketing</span>
                                    <span>ES</span>
                                </div>
                            </div>
                        </div>
                        <div class="bv-opt" data-tpl-id="5" data-cat="Autenticación"
                             data-body="Tu código de verificación es {{1}}. Válido durante {{2}} minutos."
                             data-vars="Código OTP,Minutos de validez">
                            <div class="body">
                                <div class="name bv-tpl-name">Código de verificación</div>
                                <div class="sub bv-tpl-sub">
                                    <span class="bv-tpl-badge bv-tpl-badge-approved">Aprobada</span>
                                    <span>Autenticación</span>
                                    <span>ES</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: preview + variables --}}
                <div>
                    <div class="bv-hsm-preview-head">
                        <span>Vista previa</span>
                        <span id="hsm-status-badge" class="bv-hsm-status-badge bv-tpl-badge-approved">Aprobada</span>
                    </div>

                    {{-- WhatsApp chat bubble --}}
                    <div class="bv-hsm-preview-bg">
                        <div class="bv-hsm-date-label">Hoy</div>
                        <div class="bv-hsm-bubble-wrap">
                            <div class="bv-bubble bv-hsm-preview-bubble" id="hsm-preview-bubble">
                                Hola <strong>{{1}}</strong>, tu pedido #<strong>{{2}}</strong> ha sido confirmado. Total: <strong>{{3}}</strong>. Gracias por tu compra.
                            </div>
                        </div>
                    </div>

                    {{-- Variables form --}}
                    <div class="bv-right-section-title bv-rst-mb8">Variables</div>
                    <div id="hsm-vars-form" class="bv-hsm-vars">
                        <div class="bv-hsm-var-row">
                            <span class="bv-var-label">{{1}}</span>
                            <input type="text" placeholder="Nombre del cliente" data-var="1" class="bv-var-input">
                        </div>
                        <div class="bv-hsm-var-row">
                            <span class="bv-var-label">{{2}}</span>
                            <input type="text" placeholder="Número de pedido" data-var="2" class="bv-var-input">
                        </div>
                        <div class="bv-hsm-var-row">
                            <span class="bv-var-label">{{3}}</span>
                            <input type="text" placeholder="Total del pedido" data-var="3" class="bv-var-input">
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-hsm-send"><i class="far fa-paper-plane"></i> Enviar plantilla</button>
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

    // Update preview
    var preview = body.replace(/\{\{(\d+)\}\}/g, function (match, n) {
        return '<strong>' + match + '</strong>';
    });
    $('#hsm-preview-bubble').html(preview);

    // Rebuild vars form
    var matches = [...body.matchAll(/\{\{(\d+)\}\}/g)];
    var form = '';
    matches.forEach(function (m, i) {
        form += '<div class="bv-hsm-var-row">' +
            '<span class="bv-var-label">{{' + m[1] + '}}</span>' +
            '<input type="text" placeholder="' + (vars[i] ? vars[i].trim() : 'Variable ' + m[1]) + '" data-var="' + m[1] + '" class="bv-var-input">' +
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
