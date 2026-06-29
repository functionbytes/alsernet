{{-- Modal: Nueva conversación (wizard 2 pasos) --}}
<div class="bv-modal" data-bv-modal-name="newconv">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title"><i class="fas fa-comment-medical bv-modal-title-icon"></i> Nueva conversación</div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Step indicator --}}
            <div class="nc-steps">
                <span class="nc-step active" data-step="1">
                    <span class="nc-step-num">1</span> Canal
                </span>
                <span class="nc-step-line"></span>
                <span class="nc-step" data-step="2">
                    <span class="nc-step-num">2</span> Destinatario
                </span>
            </div>

            {{-- Step 1: Canal --}}
            <div id="ncStep1">
                <div class="mv4-sec-title bv-sec-title-mb10">Elige un canal</div>
                <div class="nc-grid">
                    <div class="nc-channel wa on" data-channel="whatsapp">
                        <div class="logo"><i class="fab fa-whatsapp"></i></div>
                        <div>
                            <div class="t">WhatsApp</div>
                            <div class="s">Plantilla HSM requerida</div>
                        </div>
                    </div>
                    <div class="nc-channel fb" data-channel="facebook">
                        <div class="logo"><i class="fab fa-facebook-messenger"></i></div>
                        <div>
                            <div class="t">Messenger</div>
                            <div class="s">Ventana de 24h</div>
                        </div>
                    </div>
                    <div class="nc-channel ig" data-channel="instagram">
                        <div class="logo"><i class="fab fa-instagram"></i></div>
                        <div>
                            <div class="t">Instagram</div>
                            <div class="s">DM directo</div>
                        </div>
                    </div>
                    <div class="nc-channel widget" data-channel="widget">
                        <div class="logo"><i class="far fa-comment"></i></div>
                        <div>
                            <div class="t">Chat web</div>
                            <div class="s">Widget integrado</div>
                        </div>
                    </div>
                    <div class="nc-channel email" data-channel="email">
                        <div class="logo"><i class="far fa-envelope"></i></div>
                        <div>
                            <div class="t">Email</div>
                            <div class="s">Bandeja SMTP</div>
                        </div>
                    </div>
                    <div class="nc-channel sms" data-channel="sms">
                        <div class="logo"><i class="fas fa-mobile-screen"></i></div>
                        <div>
                            <div class="t">SMS</div>
                            <div class="s">Operador MasVoz</div>
                        </div>
                    </div>
                </div>

                <div id="ncHsmWarning" class="alert warning">
                    <i class="fas fa-triangle-exclamation lead"></i>
                    <div>Al iniciar desde WhatsApp deberás usar una <b>plantilla aprobada (HSM)</b> si han pasado más de 24h desde el último mensaje del cliente.</div>
                </div>
            </div>

            {{-- Step 2: Destinatario --}}
            <div id="ncStep2" class="bv-step-hidden">

                {{-- Resumen del canal elegido en el paso 1 --}}
                <div class="info-table nc-summary">
                    <div class="lbl">Canal</div>
                    <div class="val">
                        <span class="nc-chan-tag" id="ncSummaryChannel">
                            <i class="fab fa-whatsapp ic"></i>
                            <span class="nm">WhatsApp</span>
                        </span>
                    </div>
                </div>

                <div class="nc-field">
                    <div class="lbl">Destinatario</div>
                    <div class="mv4-search bv-search-mb0">
                        <i class="fas fa-magnifying-glass"></i>
                        <input type="text" id="ncContactSearch" placeholder="Nombre, teléfono o email…">
                    </div>
                </div>

                <div id="ncContactList">
                    <div class="nc-empty-hint" id="ncContactHint">
                        <i class="fas fa-magnifying-glass"></i>
                        <span>Escribe para buscar contactos</span>
                    </div>
                </div>

                <button class="bv-add-contact-btn" id="ncBtnAddContact">
                    <i class="fas fa-user-plus"></i>Añadir contacto nuevo
                </button>

                {{-- New contact inline form (hidden by default) --}}
                <div id="ncNewContactForm" class="nc-field bv-step-hidden">
                    <div class="lbl">Nuevo contacto</div>
                    <input type="text" id="ncNewName" placeholder="Nombre completo" class="nc-input">
                    <input type="email" id="ncNewEmail" placeholder="Email (opcional)" class="nc-input">
                    <input type="tel" id="ncNewPhone" placeholder="Teléfono (opcional)" class="nc-input">
                </div>

                {{-- Plantilla HSM (solo WhatsApp) — opcional, no bloquea la creación --}}
                <div id="ncHsmBlock" class="bv-step-hidden">
                    <div class="nc-field">
                        <div class="lbl">Plantilla aprobada (HSM) <span class="nc-lbl-hint">obligatoria fuera de 24h</span></div>
                        <select id="ncHsmSelect">
                            <option value="">— Sin plantilla —</option>
                        </select>
                    </div>

                    <div id="ncHsmVars" class="nc-field bv-step-hidden">
                        <div class="lbl">Variables de la plantilla</div>
                        <div id="ncHsmVarsGrid" class="nc-vars-grid"></div>
                    </div>

                    <div id="ncHsmPreview" class="nc-field bv-step-hidden">
                        <div class="lbl">Vista previa</div>
                        <div class="nc-preview" id="ncHsmPreviewBox"></div>
                    </div>
                </div>

                <div class="nc-field">
                    <div class="lbl">Primer mensaje</div>
                    <textarea id="ncFirstMessage" placeholder="Hola, soy del equipo de soporte. ¿En qué puedo ayudarte?"></textarea>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="ncBtnNext">Continuar</button>
            <button class="btn-secondary bv-step-hidden" id="ncBtnBack">Atrás</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function() {
    var selectedChannel = 'whatsapp';
    var selectedContact = null;
    var step = 1;
    var hsmTemplates = [];
    var hsmLoaded = false;

    var CHANNEL_META = {
        whatsapp:  { label: 'WhatsApp',  icon: 'fab fa-whatsapp' },
        facebook:  { label: 'Messenger', icon: 'fab fa-facebook-messenger' },
        instagram: { label: 'Instagram', icon: 'fab fa-instagram' },
        widget:    { label: 'Chat web',  icon: 'far fa-comment' },
        email:     { label: 'Email',     icon: 'far fa-envelope' },
        sms:       { label: 'SMS',        icon: 'fas fa-mobile-screen' }
    };

    function renderChannelSummary() {
        var meta = CHANNEL_META[selectedChannel] || CHANNEL_META.whatsapp;
        $('#ncSummaryChannel').html('<i class="' + meta.icon + ' ic"></i><span class="nm">' + meta.label + '</span>');
        $('#ncSummaryChannel').attr('data-channel', selectedChannel);
    }

    function toggleHsmBlock() {
        var isWhatsapp = selectedChannel === 'whatsapp';
        $('#ncHsmBlock').toggle(isWhatsapp);
        if (isWhatsapp) {
            loadHsmTemplates();
        }
    }

    function loadHsmTemplates() {
        if (hsmLoaded) { return; }
        hsmLoaded = true;
        $.ajax({
            url: '/panel/helpdesk/hsm-templates',
            method: 'GET',
            dataType: 'json',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function(resp) {
            hsmTemplates = (resp && resp.templates) || [];
            var opts = '<option value="">— Sin plantilla —</option>';
            $.each(hsmTemplates, function(i, t) {
                opts += '<option value="' + t.id + '">' + $('<span>').text(t.name || ('Plantilla ' + t.id)).html() + '</option>';
            });
            $('#ncHsmSelect').html(opts);
        }).fail(function() {
            hsmLoaded = false;
        });
    }

    function selectedHsm() {
        var id = $('#ncHsmSelect').val();
        if (!id) { return null; }
        for (var i = 0; i < hsmTemplates.length; i++) {
            if (String(hsmTemplates[i].id) === String(id)) { return hsmTemplates[i]; }
        }
        return null;
    }

    function hsmVarCount(tpl) {
        if (tpl.param_count) { return parseInt(tpl.param_count, 10) || 0; }
        var matches = (tpl.body || '').match(/\{\{\s*\d+\s*\}\}/g) || [];
        var max = 0;
        $.each(matches, function(i, m) {
            var n = parseInt(m.replace(/[^\d]/g, ''), 10);
            if (n > max) { max = n; }
        });
        return max;
    }

    function renderHsmVars() {
        var tpl = selectedHsm();
        if (!tpl) {
            $('#ncHsmVars').hide();
            $('#ncHsmPreview').hide();
            return;
        }
        var count = hsmVarCount(tpl);
        if (!count) {
            $('#ncHsmVarsGrid').empty();
            $('#ncHsmVars').hide();
            renderHsmPreview();
            return;
        }
        var grid = '';
        for (var i = 1; i <= count; i++) {
            grid += '<div class="nc-var-field">' +
                '<label class="nc-var-lbl">{{' + i + '}}</label>' +
                '<input type="text" class="nc-input nc-hsm-var" data-var="' + i + '" placeholder="Valor {{' + i + '}}">' +
                '</div>';
        }
        $('#ncHsmVarsGrid').html(grid);
        $('#ncHsmVars').show();
        renderHsmPreview();
    }

    function renderHsmPreview() {
        var tpl = selectedHsm();
        if (!tpl) {
            $('#ncHsmPreview').hide();
            return;
        }
        var body = tpl.body || '';
        $('.nc-hsm-var').each(function() {
            var n = $(this).data('var');
            var val = $(this).val().trim();
            var token = new RegExp('\\{\\{\\s*' + n + '\\s*\\}\\}', 'g');
            body = body.replace(token, val ? '<b>' + $('<span>').text(val).html() + '</b>' : '<b>{{' + n + '}}</b>');
        });
        $('#ncHsmPreviewBox').html(body);
        $('#ncHsmPreview').show();
    }

    function goToStep(n) {
        step = n;
        $('#ncStep1').toggle(n === 1);
        $('#ncStep2').toggle(n === 2);
        $('[data-step]').each(function() {
            $(this).toggleClass('active', parseInt($(this).data('step')) <= n);
        });
        $('#ncBtnBack').toggle(n === 2);
        if (n === 2) {
            $('#ncBtnNext').text('Iniciar conversación');
            renderChannelSummary();
            toggleHsmBlock();
        } else {
            $('#ncBtnNext').text('Continuar');
        }
    }

    $(document).on('click', '.nc-channel', function() {
        selectedChannel = $(this).data('channel');
        $('.nc-channel').removeClass('on');
        $(this).addClass('on');
        $('#ncHsmWarning').toggle(selectedChannel === 'whatsapp');
    });

    $(document).on('change', '#ncHsmSelect', function() {
        renderHsmVars();
    });

    $(document).on('input', '.nc-hsm-var', function() {
        renderHsmPreview();
    });

    var creatingNewContact = false;

    $(document).on('click', '#ncBtnAddContact', function() {
        creatingNewContact = true;
        $('#ncContactList').hide();
        $('#ncBtnAddContact').hide();
        $('#ncNewContactForm').show();
        selectedContact = null;
        $('.nc-contact-card').removeClass('nc-contact-selected');
    });

    $(document).on('click', '#ncBtnNext', function() {
        if (step === 1) {
            goToStep(2);
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true);

        function createConversation(customerId) {
            var csrfToken = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: '/panel/helpdesk/conversations',
                method: 'POST',
                dataType: 'json',
                data: {
                    customer_id: customerId,
                    channel: selectedChannel,
                    subject: 'Conversación nueva por ' + selectedChannel,
                    priority: 'normal',
                },
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            })
            .done(function(resp) {
                var convId = resp.conversation && resp.conversation.id;
                $('[data-bv-modal-name="newconv"]').removeClass('on');
                $('body').css('overflow', '');
                if (convId) {
                    window.location.href = '/panel/helpdesk/conversations?selected=' + convId;
                } else {
                }
            })
            .fail(function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message || 'Error al crear la conversación';
                toastr.error(msg);
                $btn.prop('disabled', false);
            });
        }

        if (creatingNewContact) {
            var name = $('#ncNewName').val().trim();
            var email = $('#ncNewEmail').val().trim();
            var phone = $('#ncNewPhone').val().trim();
            if (!name) {
                toastr.warning('El nombre del contacto es obligatorio');
                $btn.prop('disabled', false);
                return;
            }
            if (!email && !phone) {
                toastr.warning('Introduce al menos un email o teléfono');
                $btn.prop('disabled', false);
                return;
            }
            var csrfToken = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: '/panel/helpdesk/customers',
                method: 'POST',
                dataType: 'json',
                data: { name: name, email: email || undefined, phone: phone || undefined },
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            })
            .done(function(resp) {
                var customerId = resp.customer && resp.customer.id;
                if (!customerId) {
                    toastr.error('No se pudo obtener el ID del contacto');
                    $btn.prop('disabled', false);
                    return;
                }
                createConversation(customerId);
            })
            .fail(function(xhr) {
                var errors = xhr.responseJSON && xhr.responseJSON.errors;
                var msg = errors && Object.values(errors)[0] && Object.values(errors)[0][0]
                    || (xhr.responseJSON && xhr.responseJSON.message)
                    || 'Error al crear el contacto';
                toastr.error(msg);
                $btn.prop('disabled', false);
            });
            return;
        }

        if (!selectedContact) {
            toastr.warning('Selecciona un destinatario');
            $btn.prop('disabled', false);
            return;
        }

        createConversation(selectedContact);
    });

    $(document).on('click', '#ncBtnBack', function() {
        goToStep(1);
    });

    $(document).on('click', '.nc-contact-card', function() {
        selectedContact = $(this).data('contact-id');
        creatingNewContact = false;
        $('.nc-contact-card').removeClass('nc-contact-selected');
        $(this).addClass('nc-contact-selected');
    });

    var ncSearchTimer = null;

    $(document).on('input', '#ncContactSearch', function() {
        var q = $(this).val().trim();
        clearTimeout(ncSearchTimer);
        if (!q) {
            $('#ncContactList').html('<div class="nc-empty-hint" id="ncContactHint"><i class="fas fa-magnifying-glass"></i><span>Escribe para buscar contactos</span></div>');
            return;
        }
        $('#ncContactList').html('<div class="nc-empty-hint"><i class="fas fa-spinner fa-spin"></i><span>Buscando…</span></div>');
        ncSearchTimer = setTimeout(function() {
            $.ajax({
                url: '/panel/helpdesk/customers/search',
                method: 'GET',
                dataType: 'json',
                data: { q: q },
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            }).done(function(data) {
                if (!data.length) {
                    $('#ncContactList').html('<div class="nc-empty-hint"><i class="fas fa-user-slash"></i><span>Sin resultados para "' + $('<span>').text(q).html() + '"</span></div>');
                    return;
                }
                var html = '';
                $.each(data, function(i, c) {
                    var initials = (c.name || '?').split(' ').slice(0,2).map(function(w){ return w[0] || ''; }).join('').toUpperCase() || '?';
                    var sub = c.email || c.phone || '';
                    html += '<div class="nc-contact-card" data-contact-id="' + c.id + '">' +
                        '<div class="av c' + ((i % 8) + 1) + '">' + $('<span>').text(initials).html() + '</div>' +
                        '<div class="info">' +
                        '<div class="nm">' + $('<span>').text(c.name || 'Sin nombre').html() + '</div>' +
                        (sub ? '<div class="s">' + $('<span>').text(sub).html() + '</div>' : '') +
                        '</div></div>';
                });
                $('#ncContactList').html(html);
            }).fail(function() {
                $('#ncContactList').html('<div class="nc-empty-hint"><i class="fas fa-circle-exclamation"></i><span>Error al buscar. Intenta de nuevo.</span></div>');
            });
        }, 300);
    });

    $(document).on('bv:modal:open', function(e, name) {
        if (name !== 'newconv') { return; }
        goToStep(1);
        selectedContact = null;
        creatingNewContact = false;
        selectedChannel = 'whatsapp';
        clearTimeout(ncSearchTimer);
        $('.nc-channel').removeClass('on');
        $('[data-channel="whatsapp"]').addClass('on');
        $('#ncHsmWarning').show();
        $('#ncNewContactForm').hide();
        $('#ncContactSearch').val('');
        $('#ncContactList').html('<div class="nc-empty-hint" id="ncContactHint"><i class="fas fa-magnifying-glass"></i><span>Escribe para buscar contactos</span></div>');
        $('#ncBtnAddContact').show();
        $('#ncBtnNext').prop('disabled', false);
        $('#ncHsmSelect').val('');
        $('#ncHsmVarsGrid').empty();
        $('#ncHsmVars').hide();
        $('#ncHsmPreview').hide();
        $('#ncHsmBlock').hide();
    });
}());
</script>
@endpush
@endonce
