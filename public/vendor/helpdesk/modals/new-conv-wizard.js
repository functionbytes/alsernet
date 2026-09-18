/*!
 * Helpdesk · modal "new-conv-wizard" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/new-conv-wizard.blade.php,
 * donde vivia inline y se re-descargaba en cada carga del inbox (el modal se
 * incluye siempre, sin @if, desde partials/modals.blade.php). Sin interpolacion
 * Blade: la config llega por atributos data-* del markup.
 *
 * Convencion del modulo core: su JS se sirve desde public/vendor/helpdesk/ y no
 * tiene copia fuente aparte (igual que conversations.js y kb-suggestions.js).
 */
(function ($) {
    'use strict';

    var _ncwStep    = 1;
    var _ncwChannel = 'whatsapp';
    var _ncwContact = null;
    var _ncwTimer   = null;

    var CHANNEL_WARNINGS = {
        whatsapp: 'Al iniciar desde WhatsApp deberás usar una <b>plantilla aprobada (HSM)</b> si han pasado más de 24h desde el último mensaje del cliente.',
        messenger: 'Solo puedes iniciar una conversación en Messenger si el cliente te ha contactado en las últimas 24h.',
    };

    var CHANNEL_LABELS = {
        whatsapp: '<i class="fab fa-whatsapp me-1"></i> WhatsApp',
        messenger: '<i class="fab fa-facebook-messenger me-1"></i> Messenger',
        instagram: '<i class="fab fa-instagram me-1"></i> Instagram',
        webchat: '<i class="far fa-comment me-1"></i> Chat web',
        email: '<i class="far fa-envelope me-1"></i> Email',
        sms: '<i class="fas fa-mobile-screen-button me-1"></i> SMS',
    };

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    function escHtml(s) {
        return $('<span>').text(s || '').html();
    }

    function goToStep(step) {
        _ncwStep = step;
        $('#ncwStep1').toggle(step === 1);
        $('#ncwStep2').toggle(step === 2);
        $('#bv-ncw-next').text(step === 1 ? 'Continuar' : 'Iniciar conversación');
        $('#bv-ncw-back').toggle(step === 2);

        $('#ncwStep1Dot').toggleClass('on', step === 1).toggleClass('done', step > 1);
        $('#ncwStep1Dot .bv-wiz-step__num').html(step > 1 ? '<i class="fas fa-check bv-x42"></i>' : '1');
        $('#ncwLine').toggleClass('on', step > 1);
        $('#ncwStep2Dot').toggleClass('on', step === 2);

        if (step === 2) {
            $('#ncwSelectedChannel').html(CHANNEL_LABELS[_ncwChannel] || _ncwChannel);
            var needHsm = _ncwChannel === 'whatsapp';
            $('#ncwHsmSection').toggle(needHsm);
            if (needHsm) { loadHsmTemplates(); }
            $('#ncwContactSearch').val('').focus();
            $('#ncwContactResults').empty();
            _ncwContact = null;
        }
    }

    function loadHsmTemplates() {
        $('#ncwHsmTemplate').html('<option value="">Cargando…</option>');
        $.ajax({
            url: '/panel/helpdesk/hsm-templates',
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (resp) {
            var tpls = resp.data || resp || [];
            var opts = '<option value="">Selecciona una plantilla…</option>' + tpls.map(function (t) {
                return '<option value="' + escHtml(t.id) + '" data-body="' + escHtml(t.body || '') + '">' + escHtml(t.name) + '</option>';
            }).join('');
            $('#ncwHsmTemplate').html(opts);
        }).fail(function () {
            $('#ncwHsmTemplate').html('<option value="">Error al cargar plantillas</option>');
        });
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'new-conv-wizard') { return; }
        _ncwChannel = 'whatsapp';
        _ncwContact = null;
        $('#ncwChannelGrid .bv-ch-pick').removeClass('on');
        $('#ncwChannelGrid .bv-ch-pick[data-channel="whatsapp"]').addClass('on');
        $('#ncwChannelWarning').hide();
        goToStep(1);
    });

    $(document).on('click', '#ncwChannelGrid .bv-ch-pick', function () {
        $('#ncwChannelGrid .bv-ch-pick').removeClass('on');
        $(this).addClass('on');
        _ncwChannel = $(this).data('channel');
        var warning = CHANNEL_WARNINGS[_ncwChannel];
        if (warning) {
            $('#ncwChannelWarningText').html(warning);
            $('#ncwChannelWarning').show();
        } else {
            $('#ncwChannelWarning').hide();
        }
    });

    $(document).on('input', '#ncwContactSearch', function () {
        clearTimeout(_ncwTimer);
        var q = $(this).val().trim();
        if (q.length < 2) { $('#ncwContactResults').empty(); return; }
        _ncwTimer = setTimeout(function () {
            $.ajax({
                url: '/panel/helpdesk/customers/search',
                data: { q: q, per_page: 6 },
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            }).done(function (resp) {
                var items = resp.data || [];
                var html = items.map(function (c) {
                    return '<button type="button" class="bv-list-item bv-ncw-contact" data-id="' + c.id + '">' +
                        '<div class="bv-av bv-av--sm">' + escHtml((c.name || '?').slice(0, 2).toUpperCase()) + '</div>' +
                        '<div class="bv-list-item__body">' +
                            '<span class="bv-list-item__t">' + escHtml(c.name) + '</span>' +
                            '<span class="bv-list-item__s bv-x43">' + escHtml(c.phone || c.email || '') + '</span>' +
                        '</div>' +
                        '</button>';
                }).join('');
                html += '<button type="button" class="bv-list-item bv-ncw-new-contact">' +
                    '<div class="bv-list-item__ico"><i class="fas fa-user-plus"></i></div>' +
                    '<div class="bv-list-item__body"><span class="bv-list-item__t">Crear nuevo contacto</span></div>' +
                    '</button>';
                $('#ncwContactResults').html(html);
            });
        }, 220);
    });

    $(document).on('click', '.bv-ncw-contact', function () {
        $('.bv-ncw-contact').removeClass('on');
        $(this).addClass('on');
        _ncwContact = { id: $(this).data('id'), name: $(this).find('.bv-list-item__t').text() };
    });

    $(document).on('click', '.bv-ncw-new-contact', function () {
        closeBvModal('new-conv-wizard');
        $(document).trigger('bv:modal:open', ['edit-contact', { mode: 'create' }]);
    });

    $(document).on('change', '#ncwHsmTemplate', function () {
        var body = $(this).find(':selected').data('body') || '';
        if (body) {
            $('#ncwHsmPreviewText').html(escHtml(body));
            $('#ncwHsmPreview').show();
        } else {
            $('#ncwHsmPreview').hide();
        }
    });

    $(document).on('click', '#bv-ncw-next', function () {
        if (_ncwStep === 1) {
            goToStep(2);
            return;
        }
        if (!_ncwContact) {
            if (window.toastr) { toastr.warning('Selecciona un contacto'); }
            return;
        }
        if (_ncwChannel === 'whatsapp' && !$('#ncwHsmTemplate').val()) {
            if (window.toastr) { toastr.warning('Selecciona una plantilla HSM'); }
            return;
        }

        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Iniciando…');

        $.ajax({
            url: '/panel/helpdesk/conversations',
            method: 'POST',
            data: {
                channel:      _ncwChannel,
                customer_id:  _ncwContact.id,
                hsm_template: $('#ncwHsmTemplate').val() || null,
                assign_self:  $('#ncwAssignSelf').is(':checked') ? 1 : 0,
            },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function (resp) {
            closeBvModal('new-conv-wizard');
            if (window.toastr) { toastr.success('Conversación iniciada'); }
            var id = (resp.data && resp.data.id) || resp.id;
            if (id) { $(document).trigger('bv:conversation:created', [id]); }
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al iniciar conversación';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Iniciar conversación');
        });
    });

    $(document).on('click', '#bv-ncw-back', function () {
        goToStep(1);
    });

}(window.jQuery));
