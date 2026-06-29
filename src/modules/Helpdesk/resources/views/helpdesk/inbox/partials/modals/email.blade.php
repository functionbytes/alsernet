{{-- Modal: Enviar email --}}
<div class="bv-modal" data-bv-modal-name="email">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="far fa-envelope"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">CHAT · COMPOSE</span>
                <div class="bv-modal-title">Enviar email</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">
            <div class="mv4-email">

                {{-- Para --}}
                <div class="row">
                    <span class="lbl">Para</span>
                    <input type="email" id="emailTo" placeholder="Añadir destinatario…" value="{{ $selectedConversation?->customer?->email ?? '' }}">
                </div>

                {{-- CC (toggle) --}}
                <div class="row bv-hidden" id="emailCcRow">
                    <span class="lbl">CC</span>
                    <input type="email" id="emailCc" placeholder="Añadir en copia…">
                </div>

                {{-- Asunto --}}
                <div class="row">
                    <span class="lbl">Asunto</span>
                    <input type="text" id="emailSubject" name="subject" placeholder="Asunto del correo…">
                </div>

                {{-- Plantilla --}}
                <div class="row">
                    <span class="lbl">Plantilla</span>
                    <div class="bv-email-tpl-wrap">
                        <select id="emailTemplate">
                            <option value="">— Sin plantilla —</option>
                        </select>
                        <span id="emailTemplateLoading" class="bv-hidden bv-email-tpl-loading">
                            <i class="fas fa-spinner fa-spin"></i>
                        </span>
                    </div>
                </div>

            </div>

            {{-- Toggle CC --}}
            <button id="emailToggleCc" class="bv-email-ccbcc">
                <i class="fas fa-plus bv-icon-xs"></i> CC / BCC
            </button>

            {{-- Barra de formato simple --}}
            <div class="bv-email-toolbar">
                <button class="tt bv-fmt-btn bv-fmt-btn-bold" data-tt="Negrita">B</button>
                <button class="tt bv-fmt-btn bv-fmt-btn-italic" data-tt="Itálica"><i>I</i></button>
                <div class="bv-toolbar-sep"></div>
                <button class="tt bv-fmt-btn bv-fmt-btn-muted" data-tt="Insertar enlace"><i class="fas fa-link bv-icon-sm"></i></button>
                <button class="tt bv-fmt-btn bv-fmt-btn-muted" data-tt="Lista"><i class="fas fa-list bv-icon-sm"></i></button>
                <div class="bv-spacer"></div>
                <button id="emailScheduleToggle" class="bv-schedule-btn" data-tt="Programar envío">
                    <i class="fas fa-clock"></i> Programar
                </button>
            </div>

            {{-- Programar envío --}}
            <div id="emailScheduleRow" class="bv-email-schedule bv-hidden">
                <label class="bv-email-schedule-label">Enviar el</label>
                <input type="datetime-local" value="{{ date('Y-m-d') }}T10:00" class="bv-datetime-input">
            </div>

            {{-- Body --}}
            <textarea id="emailBody" rows="8" class="bv-email-body" placeholder="Escribe el mensaje o selecciona una plantilla…"></textarea>

            {{-- Preview HTML (cuando se usa plantilla) --}}
            <div id="emailHtmlPreviewWrap" class="bv-hidden bv-email-preview-wrap">
                <div class="bv-email-preview-head">
                    <span class="bv-email-preview-label">Vista previa HTML de la plantilla</span>
                    <button type="button" id="emailTogglePreview" class="bv-email-toggle-btn">Ocultar</button>
                </div>
                <iframe id="emailHtmlPreview" sandbox="allow-same-origin" class="bv-email-preview-frame"></iframe>
            </div>

            <input type="hidden" id="emailTemplateId" value="">

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-email-send">Enviar</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
            <button class="btn-secondary">Adjuntar</button>
            <button class="btn-secondary">Borrador</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    var _templatesLoaded = false;
    var _templateMap = {};     // id -> {name, subject, key}
    var _previewXhr = null;

    // Cargar plantillas cuando el DOM esté listo (una sola vez).
    $(function () { loadEmailTemplates(); });

    function loadEmailTemplates() {
        $('#emailTemplateLoading').removeClass('bv-hidden');
        $.ajax({
            url: '/panel/helpdesk/conversations/email-templates',
            method: 'GET',
            dataType: 'json',
            headers: { 'Accept': 'application/json' },
        }).done(function (resp) {
            var $sel = $('#emailTemplate');
            var templates = resp.templates || [];
            templates.forEach(function (t) {
                _templateMap[t.id] = t;
                $sel.append($('<option>').val(t.id).text(t.name));
            });
        }).fail(function () {
            // Si falla silenciosamente, el select queda solo con "Sin plantilla".
        }).always(function () {
            $('#emailTemplateLoading').addClass('bv-hidden');
        });
    }

    // Al cambiar plantilla → obtener preview del servidor.
    $(document).on('change', '#emailTemplate', function () {
        var templateId = $(this).val();

        $('#emailTemplateId').val(templateId);
        $('#emailHtmlPreviewWrap').addClass('bv-hidden');

        if (!templateId) {
            $('#emailSubject').val('');
            $('#emailBody').val('');
            return;
        }

        var convId = $('.bv-composer').data('bv-conversation-id');
        if (!convId) {
            // Sin conversación activa: usar datos del map para rellenar subject vacío.
            var t = _templateMap[templateId];
            if (t) { $('#emailSubject').val(t.subject || ''); }
            return;
        }

        if (_previewXhr) { _previewXhr.abort(); }

        $('#emailBody').prop('disabled', true).val('Cargando plantilla…');

        _previewXhr = $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/email-templates/preview',
            method: 'GET',
            dataType: 'json',
            data: { template_id: templateId },
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
        }).done(function (resp) {
            $('#emailSubject').val(resp.subject || '');
            $('#emailBody').val(resp.body || '');

            if (resp.html_body) {
                var iframe = document.getElementById('emailHtmlPreview');
                if (iframe) {
                    iframe.srcdoc = resp.html_body;
                }
                $('#emailHtmlPreviewWrap').removeClass('bv-hidden');
            }
        }).fail(function (xhr) {
            if (xhr.statusText === 'abort') { return; }
            $('#emailBody').val('');
            toastr.warning('No se pudo cargar la plantilla.');
        }).always(function () {
            $('#emailBody').prop('disabled', false);
        });
    });

    // Ocultar/mostrar iframe de preview.
    $(document).on('click', '#emailTogglePreview', function () {
        var $iframe = $('#emailHtmlPreview');
        var wasHidden = $iframe.hasClass('bv-hidden');
        $iframe.toggleClass('bv-hidden');
        $(this).text(wasHidden ? 'Ocultar' : 'Mostrar');
    });

    $(document).on('click', '#emailToggleCc', function () {
        var row = $('#emailCcRow');
        row.toggleClass('bv-hidden');
        var visible = !row.hasClass('bv-hidden');
        $(this)
            .toggleClass('on', visible)
            .html(visible
                ? '<i class="fas fa-minus bv-cc-toggle-icon"></i> Ocultar CC/BCC'
                : '<i class="fas fa-plus bv-cc-toggle-icon"></i> CC / BCC'
            );
        if (visible) { $('#emailCc').focus(); }
    });

    $(document).on('click', '#emailScheduleToggle', function () {
        var row = $('#emailScheduleRow');
        row.toggleClass('bv-hidden');
        var active = !row.hasClass('bv-hidden');
        $(this).toggleClass('bv-schedule-btn--active', active);
    });

    // Botones de formato (negrita, itálica, enlace, lista)
    $(document).on('click', '.bv-fmt-btn-bold', function () {
        fmtWrap($('#emailBody')[0], '**', '**');
    });
    $(document).on('click', '.bv-fmt-btn-italic', function () {
        fmtWrap($('#emailBody')[0], '_', '_');
    });
    $(document).on('click', '.bv-fmt-btn[data-tt="Enlace"]', function () {
        var url = prompt('URL del enlace:');
        if (!url) { return; }
        var ta = $('#emailBody')[0];
        var sel = ta.value.substring(ta.selectionStart, ta.selectionEnd) || 'texto';
        fmtInsert(ta, '[' + sel + '](' + url + ')');
    });
    $(document).on('click', '.bv-fmt-btn[data-tt="Lista"]', function () {
        var ta = $('#emailBody')[0];
        var sel = ta.value.substring(ta.selectionStart, ta.selectionEnd);
        var lines = sel
            ? sel.split('\n').map(function (l) { return '- ' + l; }).join('\n')
            : '- ';
        fmtInsert(ta, lines);
    });

    function fmtWrap(ta, before, after) {
        var s = ta.selectionStart, e = ta.selectionEnd;
        var sel = ta.value.substring(s, e) || 'texto';
        ta.value = ta.value.substring(0, s) + before + sel + after + ta.value.substring(e);
        ta.setSelectionRange(s + before.length, s + before.length + sel.length);
        ta.focus();
    }

    function fmtInsert(ta, text) {
        var s = ta.selectionStart;
        ta.value = ta.value.substring(0, s) + text + ta.value.substring(ta.selectionEnd);
        ta.setSelectionRange(s + text.length, s + text.length);
        ta.focus();
    }

    $(document).on('click', '#bv-email-send', function () {
        var $btn = $(this);
        var convId = $('.bv-composer').data('bv-conversation-id');
        if (!convId) { toastr.error('No hay conversación seleccionada'); return; }

        var to = ($('#emailTo').val() || '').trim();
        var subject = ($('#emailSubject').val() || '').trim();
        var body = ($('#emailBody').val() || '').trim();
        var templateId = $('#emailTemplateId').val() || null;

        if (!to || !subject) {
            toastr.warning('Completa el destinatario y el asunto');
            return;
        }

        if (!templateId && !body) {
            toastr.warning('Escribe un mensaje o selecciona una plantilla');
            return;
        }

        $btn.prop('disabled', true);

        var ccVal = ($('#emailCc').val() || '').trim();
        var ccList = ccVal ? ccVal.split(/[,;]\s*/).filter(Boolean) : [];

        var payload = {
            subject: subject,
            cc: ccList,
        };

        if (templateId) {
            payload.template_id = templateId;
        } else {
            payload.body = body;
        }

        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/send-email',
            method: 'POST',
            dataType: 'json',
            data: payload,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json',
            },
        }).done(function (resp) {
            $('[data-bv-modal-name="email"]').removeClass('on');
            $('body').css('overflow', '');
            $('#emailTemplate').val('').trigger('change-silent');
            $('#emailSubject, #emailBody').val('');
            $('#emailTemplateId').val('');
            $('#emailHtmlPreviewWrap').addClass('bv-hidden');
            // Reset CC toggle state
            $('#emailCcRow').addClass('bv-hidden');
            $('#emailToggleCc').removeClass('on')
                .html('<i class="fas fa-plus bv-cc-toggle-icon"></i> CC / BCC');
            // Reset schedule toggle state
            $('#emailScheduleRow').addClass('bv-hidden');
            $('#emailScheduleToggle').removeClass('bv-schedule-btn--active');
            // Refresh emails tab (do NOT add to chat thread)
            if (typeof window.rpEmReload === 'function') {
                window.rpEmReload();
            }
            toastr.success('Email enviado correctamente.');
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.errors
                ? Object.values(xhr.responseJSON.errors)[0]?.[0]
                : (xhr?.responseJSON?.message || 'No se pudo enviar el correo');
            toastr.error(msg);
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });
}());
</script>
@endpush
@endonce
