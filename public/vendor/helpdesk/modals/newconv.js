/*!
 * Helpdesk · modal "newconv" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/newconv.blade.php.
 *
 * OJO: los marcadores {{1}}/{{nombre}} de las plantillas HSM van aqui con
 * llaves normales. En el Blade habia que escribirlos @{{ para que Blade no se
 * los comiera como echo; en un .js no hay Blade, asi que el escape sobra y de
 * hecho romperia el marcador (saldria un @ literal).
 */
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
        $('#ncSummaryChannel').text(meta.label).attr('data-channel', selectedChannel);
    }

    function toggleHsmBlock() {
        var isWhatsapp = selectedChannel === 'whatsapp';
        $('#ncHsmBlock').toggle(isWhatsapp);
        // Por WhatsApp el mensaje inicial siempre sale de la plantilla HSM (ver
        // validacion en #ncBtnNext) — el textarea libre solo aplica a canales sin
        // ventana de servicio obligatoria.
        $('#ncFirstMessageField').toggle(!isWhatsapp);
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
        // Algunas plantillas quedaron guardadas con la secuencia literal "\n" (dos
        // caracteres) en vez de un salto de linea real — se veian tal cual en la
        // vista previa. Normalizamos ambos casos antes de insertar las variables.
        var body = (tpl.body || '').replace(/\\n/g, '\n').replace(/\n/g, '<br>');
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

        $('#ncStep1Dot').toggleClass('on', n === 1).toggleClass('done', n > 1);
        $('#ncStep1Dot .bv-wiz-step__num').html(n > 1 ? '<i class="fas fa-check bv-x42"></i>' : '1');
        $('#ncLine').toggleClass('on', n > 1);
        $('#ncStep2Dot').toggleClass('on', n === 2);

        $('#ncBtnBack').toggle(n === 2);
        if (n === 2) {
            $('#ncBtnNext').text('Iniciar conversación');
            renderChannelSummary();
            toggleHsmBlock();
        } else {
            $('#ncBtnNext').text('Continuar');
        }
    }

    $(document).on('click', '.bv-ch-pick', function() {
        // Canales sin data-channel (Messenger/Instagram/Chat web/Email/SMS) estan
        // deshabilitados visualmente (.disabled, pointer-events:none en CSS) porque
        // aun no estan activos en el sistema — este chequeo es defensa adicional.
        if ($(this).hasClass('disabled') || !$(this).data('channel')) { return; }
        selectedChannel = $(this).data('channel');
        $('.bv-ch-pick').removeClass('on');
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
        $('#ncNewContactForm').show();
        selectedContact = null;
        $('.nc-contact-card').removeClass('on');
    });

    $(document).on('click', '#ncBtnNext', function() {
        if (step === 1) {
            goToStep(2);
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true);

        function goToConversation(convId) {
            $('[data-bv-modal-name="newconv"]').removeClass('on');
            $('body').css('overflow', '');
            if (convId) {
                window.location.href = '/panel/helpdesk/conversations?selected=' + convId;
            }
        }

        // Envia la plantilla HSM elegida (obligatoria fuera de la ventana de 24h de
        // WhatsApp) contra la conversacion recien creada, reusando el mismo endpoint
        // que ya usa el composer del inbox (conversations.js → #bv-hsm-insert).
        function sendInitialHsm(convId, tpl) {
            var variables = [];
            $('.nc-hsm-var').each(function() {
                variables.push($(this).val() || '');
            });
            $.ajax({
                url: '/panel/helpdesk/conversations/' + convId + '/send-hsm',
                method: 'POST',
                dataType: 'json',
                // external_id es el nombre tecnico registrado en Meta (tpl.name es solo
                // la etiqueta amigable para la UI) — mandar tpl.name aqui hace que la
                // Cloud API rechace el envio con "Template name does not exist" (132001).
                data: { template_name: tpl.external_id, variables: variables },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' },
            })
            .fail(function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message || 'No se pudo enviar la plantilla';
                toastr.error(msg);
            })
            .always(function() {
                goToConversation(convId);
            });
        }

        function createConversation(customerId) {
            var csrfToken = $('meta[name="csrf-token"]').attr('content');
            var tpl = selectedChannel === 'whatsapp' ? selectedHsm() : null;
            var firstMessage = $('#ncFirstMessage').val().trim();
            $.ajax({
                url: '/panel/helpdesk/conversations',
                method: 'POST',
                dataType: 'json',
                data: {
                    customer_id: customerId,
                    channel: selectedChannel,
                    subject: 'Conversación nueva por ' + selectedChannel,
                    priority: 'normal',
                    // Si hay plantilla HSM se envia aparte tras crear la conversacion
                    // (obligatoria fuera de 24h); mandar tambien el texto libre duplicaria
                    // el primer mensaje.
                    first_message: (!tpl && firstMessage) ? firstMessage : undefined,
                    assign_self: $('#ncAssignSelf').is(':checked') ? 1 : 0,
                },
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            })
            .done(function(resp) {
                var convId = resp.conversation && resp.conversation.id;
                if (!convId) {
                    return;
                }
                if (tpl) {
                    sendInitialHsm(convId, tpl);
                } else {
                    goToConversation(convId);
                }
            })
            .fail(function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message || 'Error al crear la conversación';
                toastr.error(msg);
                $btn.prop('disabled', false);
            });
        }

        // Una conversacion nueva por WhatsApp nunca tiene ventana de 24h abierta
        // (no hay mensaje previo del cliente), asi que la plantilla HSM es
        // obligatoria de verdad, no solo el asterisco visual del label — si se
        // deja mandar solo el "Primer mensaje" libre, la Cloud API de Meta lo
        // rechaza y el agente ni se entera (la conversacion ya quedo creada).
        if (selectedChannel === 'whatsapp' && !selectedHsm()) {
            toastr.warning('Selecciona una plantilla aprobada (HSM) para iniciar la conversación por WhatsApp');
            $btn.prop('disabled', false);
            return;
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
                data: {
                    name: name,
                    email: email || undefined,
                    phone: phone || undefined,
                    // Los mensajes salientes de WhatsApp se resuelven por whatsapp_phone
                    // (mismo campo que usan los webhooks entrantes), no por phone.
                    whatsapp_phone: (selectedChannel === 'whatsapp' && phone) ? phone : undefined,
                },
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
        $('.nc-contact-card').removeClass('on');
        $(this).addClass('on');
    });

    var ncSearchTimer = null;

    // Fila "Crear nuevo contacto" al final de los resultados (mockup #31B
    // "ve-new-conv-wizard-2") — antes era un botón dashed siempre visible fuera
    // de la lista; ahora vive dentro, igual que en el diseño de referencia.
    function addContactRowHtml() {
        return '<button type="button" class="bv-list-item" id="ncBtnAddContact">' +
            '<div class="bv-list-item__ico"><i class="fas fa-user-plus"></i></div>' +
            '<div class="bv-list-item__body">' +
            '<span class="bv-list-item__t">Crear nuevo contacto</span>' +
            '<span class="bv-list-item__s">Añadir teléfono o email manualmente</span>' +
            '</div></button>';
    }

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
                var html = '';
                $.each(data, function(i, c) {
                    var initials = (c.name || '?').split(' ').slice(0,2).map(function(w){ return w[0] || ''; }).join('').toUpperCase() || '?';
                    var sub = c.email || c.phone || '';
                    html += '<button type="button" class="bv-list-item nc-contact-card" data-contact-id="' + c.id + '">' +
                        '<div class="bv-av bv-av--sm">' + $('<span>').text(initials).html() + '</div>' +
                        '<div class="bv-list-item__body">' +
                        '<span class="bv-list-item__t">' + $('<span>').text(c.name || 'Sin nombre').html() + '</span>' +
                        (sub ? '<span class="bv-list-item__s">' + $('<span>').text(sub).html() + '</span>' : '') +
                        '</div><div class="bv-list-item__check"></div></button>';
                });
                html += addContactRowHtml();
                $('#ncContactList').html(html);
            }).fail(function() {
                $('#ncContactList').html('<div class="nc-empty-hint"><i class="fas fa-circle-exclamation"></i><span>Error al buscar. Intenta de nuevo.</span></div>' + addContactRowHtml());
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
        $('.bv-ch-pick').removeClass('on');
        $('.bv-ch-pick[data-channel="whatsapp"]').addClass('on');
        $('#ncHsmWarning').show();
        $('#ncNewContactForm').hide();
        $('#ncContactSearch').val('');
        $('#ncContactList').show().html('<div class="nc-empty-hint" id="ncContactHint"><i class="fas fa-magnifying-glass"></i><span>Escribe para buscar contactos</span></div>');
        $('#ncBtnNext').prop('disabled', false);
        // hsmLoaded=false fuerza recargar la lista en cada apertura del modal (no solo
        // la primera vez de la sesión) — evita que el <select> quede con residuos de
        // una apertura anterior (ej. una plantilla ya elegida) tras repoblarlo.
        hsmLoaded = false;
        $('#ncHsmSelect').html('<option value="">— Sin plantilla —</option>');
        $('#ncHsmVarsGrid').empty();
        $('#ncHsmVars').hide();
        $('#ncHsmPreview').hide();
        $('#ncHsmBlock').hide();
    });
}());
