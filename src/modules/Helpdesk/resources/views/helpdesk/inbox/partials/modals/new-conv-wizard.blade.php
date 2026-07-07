{{-- Modal: Nueva conversación · wizard 2 pasos (#31 ve-new-conv-wizard) --}}
<div class="bv-modal" data-bv-modal-name="new-conv-wizard">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="far fa-comment-dots"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.new_conv_wizard_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.newconv_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Stepper --}}
            <div class="bv-wiz-steps">
                <div class="bv-wiz-step on" id="ncwStep1Dot"><span class="bv-wiz-step__num">1</span><span class="bv-wiz-step__lbl">{{ __('helpdesk::helpdesk.inbox.modals.newconv_step_channel') }}</span></div>
                <div class="bv-wiz-line" id="ncwLine"></div>
                <div class="bv-wiz-step" id="ncwStep2Dot"><span class="bv-wiz-step__num">2</span><span class="bv-wiz-step__lbl">{{ __('helpdesk::helpdesk.inbox.modals.newconv_step_recipient') }}</span></div>
            </div>

            {{-- Paso 1: Canal --}}
            <div id="ncwStep1">
                <div class="bv-form-label bv-x39">{{ __('helpdesk::helpdesk.inbox.modals.newconv_choose_channel') }}</div>
                <div class="bv-ch-pick-grid" id="ncwChannelGrid">
                    <button type="button" class="bv-ch-pick on" data-channel="whatsapp">
                        <div class="bv-ch-pick__ic"><i class="fab fa-whatsapp"></i></div>
                        <div class="bv-ch-pick__body">
                            <span class="bv-ch-pick__nm">WhatsApp</span>
                            <span class="bv-ch-pick__ds">{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_whatsapp_desc') }}</span>
                        </div>
                    </button>
                    <button type="button" class="bv-ch-pick" data-channel="messenger">
                        <div class="bv-ch-pick__ic"><i class="fab fa-facebook-messenger"></i></div>
                        <div class="bv-ch-pick__body">
                            <span class="bv-ch-pick__nm">Messenger</span>
                            <span class="bv-ch-pick__ds">{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_messenger_desc') }}</span>
                        </div>
                    </button>
                    <button type="button" class="bv-ch-pick" data-channel="instagram">
                        <div class="bv-ch-pick__ic"><i class="fab fa-instagram"></i></div>
                        <div class="bv-ch-pick__body">
                            <span class="bv-ch-pick__nm">Instagram</span>
                            <span class="bv-ch-pick__ds">{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_instagram_desc') }}</span>
                        </div>
                    </button>
                    <button type="button" class="bv-ch-pick" data-channel="webchat">
                        <div class="bv-ch-pick__ic"><i class="far fa-comment"></i></div>
                        <div class="bv-ch-pick__body">
                            <span class="bv-ch-pick__nm">{{ __('helpdesk::helpdesk.inbox.modals.filter_channel_web') }}</span>
                            <span class="bv-ch-pick__ds">{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_web_desc') }}</span>
                        </div>
                    </button>
                    <button type="button" class="bv-ch-pick" data-channel="email">
                        <div class="bv-ch-pick__ic"><i class="far fa-envelope"></i></div>
                        <div class="bv-ch-pick__body">
                            <span class="bv-ch-pick__nm">Email</span>
                            <span class="bv-ch-pick__ds">{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_email_desc') }}</span>
                        </div>
                    </button>
                    <button type="button" class="bv-ch-pick" data-channel="sms">
                        <div class="bv-ch-pick__ic"><i class="fas fa-mobile-screen-button"></i></div>
                        <div class="bv-ch-pick__body">
                            <span class="bv-ch-pick__nm">SMS</span>
                            <span class="bv-ch-pick__ds">{{ __('helpdesk::helpdesk.inbox.modals.new_conv_wizard_sms_desc') }}</span>
                        </div>
                    </button>
                </div>

                <div class="bv-warn-callout" id="ncwChannelWarning" style="display:none">
                    <i class="fas fa-triangle-exclamation"></i>
                    <div id="ncwChannelWarningText"></div>
                </div>
            </div>

            {{-- Paso 2: Destinatario --}}
            <div id="ncwStep2" style="display:none">
                <div class="bv-info-table">
                    <div class="bv-info-table__row">
                        <span class="bv-info-table__k">{{ __('helpdesk::helpdesk.inbox.modals.context_card_channel') }}</span>
                        <span class="bv-info-table__v" id="ncwSelectedChannel"></span>
                    </div>
                </div>

                <div class="bv-form-field">
                    <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.new_conv_wizard_search_contact') }} <span class="bv-req">*</span></label>
                    <div class="bv-modal-search">
                        <i class="fas fa-magnifying-glass"></i>
                        <input id="ncwContactSearch" type="text" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.newconv_contact_search_placeholder') }}" autocomplete="off">
                    </div>
                    <div class="bv-x40" id="ncwContactResults"></div>
                </div>

                <div id="ncwHsmSection" style="display:none">
                    <div class="bv-form-field">
                        <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.newconv_hsm_template_label') }} <span class="bv-req">*</span> <span class="bv-form-hint">{{ __('helpdesk::helpdesk.inbox.modals.newconv_hsm_required_hint') }}</span></label>
                        <select id="ncwHsmTemplate" class="bv-form-input">
                            <option value="">{{ __('helpdesk::helpdesk.inbox.modals.newconv_hsm_no_template') }}</option>
                        </select>
                    </div>
                    <div class="bv-form-field" id="ncwHsmPreview" style="display:none">
                        <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.newconv_hsm_preview_label') }}</label>
                        <div class="bv-x41" id="ncwHsmPreviewText"></div>
                    </div>
                </div>

                <label class="bv-check">
                    <input type="checkbox" id="ncwAssignSelf" checked>
                    {{ __('helpdesk::helpdesk.inbox.modals.new_conv_wizard_assign_self') }}
                </label>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-ncw-next">{{ __('helpdesk::helpdesk.inbox.modals.newconv_continue') }}</button>
            <button class="btn-secondary" id="bv-ncw-back" style="display:none">{{ __('helpdesk::helpdesk.inbox.modals.newconv_back') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
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
</script>
@endpush
@endonce
