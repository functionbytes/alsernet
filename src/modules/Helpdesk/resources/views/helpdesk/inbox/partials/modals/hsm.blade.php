{{-- Modal: Plantillas WhatsApp HSM --}}
<div class="bv-modal" data-bv-modal-name="hsm">
    <div class="bv-modal-dialog lg">
        <div class="bv-modal-head">
            <div class="bv-modal-title"><i class="fab fa-whatsapp bv-modal-title-icon bv-modal-title-icon--whatsapp"></i> {{ __('helpdesk::helpdesk.inbox.modals.hsm_title') }}</div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-hsm-grid">

                {{-- Left: list --}}
                <div>
                    <div class="bv-modal-search bv-modal-search-mb10">
                        <i class="fas fa-magnifying-glass"></i>
                        <input id="hsm-search" type="text" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.hsm_search_placeholder') }}" autocomplete="off">
                    </div>

                    {{-- Category pills --}}
                    <div class="bv-hsm-cats">
                        <button class="bv-chip on" data-cat="">{{ __('helpdesk::helpdesk.inbox.modals.hsm_cat_all') }}</button>
                        <button class="bv-chip" data-cat="Utilidad">{{ __('helpdesk::helpdesk.inbox.modals.hsm_cat_utility') }}</button>
                        <button class="bv-chip" data-cat="Marketing">{{ __('helpdesk::helpdesk.inbox.modals.hsm_cat_marketing') }}</button>
                        <button class="bv-chip" data-cat="Autenticación">{{ __('helpdesk::helpdesk.inbox.modals.hsm_cat_auth') }}</button>
                    </div>

                    {{-- Template list --}}
                    @verbatim
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
                        <div class="bv-opt" data-tpl-id="6" data-cat="Utilidad"
                             data-body="Tu pedido está en camino. Número de seguimiento: {{tracking}}. Puedes rastrearlo en: {{url_rastreo}}. Fecha estimada de entrega: {{fecha}}. ¡Esperamos que llegue pronto!"
                             data-vars="tracking:Número de seguimiento,url_rastreo:URL de rastreo,fecha:Fecha estimada">
                            <div class="body">
                                <div class="name bv-tpl-name">Pedido en camino</div>
                                <div class="sub bv-tpl-sub">
                                    <span class="bv-tpl-badge bv-tpl-badge-approved">Aprobada</span>
                                    <span>Utilidad</span>
                                    <span>ES</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endverbatim
                </div>

                {{-- Right: preview + variables --}}
                <div>
                    <div class="bv-hsm-preview-head">
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.hsm_preview_label') }}</span>
                        <span id="hsm-status-badge" class="bv-hsm-status-badge bv-tpl-badge-approved">{{ __('helpdesk::helpdesk.inbox.modals.hsm_status_approved') }}</span>
                    </div>

                    {{-- WhatsApp chat bubble --}}
                    <div class="bv-hsm-preview-bg">
                        <div class="bv-hsm-date-label">{{ __('helpdesk::helpdesk.inbox.modals.hsm_date_today') }}</div>
                        <div class="bv-hsm-bubble-wrap">
                            @verbatim
                            <div class="bv-bubble bv-hsm-preview-bubble" id="hsm-preview-bubble">
                                Hola <strong>{{1}}</strong>, tu pedido #<strong>{{2}}</strong> ha sido confirmado. Total: <strong>{{3}}</strong>. Gracias por tu compra.
                            </div>
                            @endverbatim
                        </div>
                    </div>

                    {{-- Variables form --}}
                    <div class="bv-right-section-title bv-rst-mb8">{{ __('helpdesk::helpdesk.inbox.modals.hsm_field_variables') }}</div>
                    @verbatim
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
                    @endverbatim
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-hsm-send">{{ __('helpdesk::helpdesk.inbox.modals.hsm_submit') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    var VAR_RE = /\{\{([a-zA-Z0-9_]+)\}\}/g;

    function hsmVarDescMap(varsStr) {
        var map = {};
        if (!varsStr) { return map; }
        varsStr.split(',').forEach(function (item) {
            var parts = item.trim().split(':');
            if (parts.length === 2) {
                map[parts[0].trim()] = parts[1].trim();
            }
        });
        return map;
    }

    function hsmUniqueVars(body) {
        var seen = {}, list = [];
        var m;
        VAR_RE.lastIndex = 0;
        while ((m = VAR_RE.exec(body)) !== null) {
            if (!seen[m[1]]) { seen[m[1]] = true; list.push(m[1]); }
        }
        return list;
    }

    function hsmRenderPreview(body) {
        VAR_RE.lastIndex = 0;
        var html = $('<span>').text(body).html();
        return html.replace(/\{\{([a-zA-Z0-9_]+)\}\}/g, function (match, n) {
            return '<strong>' + match + '</strong>';
        });
    }

    function hsmUpdatePreview() {
        var tpl = $('[data-bv-modal-name="hsm"] .bv-opt.on').data('body') || '';
        var html = $('<span>').text(tpl).html();
        html = html.replace(/\{\{([a-zA-Z0-9_]+)\}\}/g, function (match, n) {
            var $input = $('#hsm-vars-form input[data-var="' + n + '"]');
            var val = $input.val() ? $('<span>').text($input.val()).html() : match;
            return '<strong>' + val + '</strong>';
        });
        $('#hsm-preview-bubble').html(html);
    }

    $(document).on('click', '[data-bv-modal-name="hsm"] .bv-opt', function () {
        $('[data-bv-modal-name="hsm"] .bv-opt').removeClass('on');
        $(this).addClass('on');

        var body = $(this).data('body') || '';
        var descMap = hsmVarDescMap($(this).data('vars') || '');
        var vars = hsmUniqueVars(body);

        $('#hsm-preview-bubble').html(hsmRenderPreview(body));

        var form = '';
        vars.forEach(function (v) {
            var placeholder = descMap[v] || v.replace(/_/g, ' ');
            form += '<div class="bv-hsm-var-row">'
                {{-- @{{ escapa la llave: sin el @, Blade lo compilaba como echo y
                     el label salia como " + v + " en vez del marcador {{nombre}}. --}}
                + '<span class="bv-var-label">@{{' + v + '}}</span>'
                + '<input type="text" placeholder="' + placeholder + '" data-var="' + v + '" class="bv-var-input">'
                + '</div>';
        });
        $('#hsm-vars-form').html(form || '<p class="bv-text-muted bv-x28">Esta plantilla no tiene variables.</p>');
    });

    $(document).on('click', '[data-bv-modal-name="hsm"] .bv-chip', function () {
        $('[data-bv-modal-name="hsm"] .bv-chip').removeClass('on');
        $(this).addClass('on');
        var cat = $(this).data('cat');
        $('[data-bv-modal-name="hsm"] .bv-opt').each(function () {
            $(this).toggle(!cat || $(this).data('cat') === cat);
        });
    });

    $(document).on('input', '#hsm-search', function () {
        var q = $(this).val().toLowerCase();
        $('[data-bv-modal-name="hsm"] .bv-opt').each(function () {
            var name = $(this).find('.name').text().toLowerCase();
            $(this).toggle(!q || name.includes(q));
        });
    });

    $(document).on('input', '#hsm-vars-form .bv-var-input', hsmUpdatePreview);
}());
</script>
@endpush
@endonce
