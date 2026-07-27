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
