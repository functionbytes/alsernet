{{-- Modal: Enviar email --}}
<div class="bv-modal" data-bv-modal-name="email">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title"><i class="fas fa-envelope bv-modal-title-icon"></i> Enviar email</div>
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
                    <select id="emailTemplate">
                        <option value="">— Sin plantilla —</option>
                    </select>
                    <span id="emailTemplateLoading" class="bv-hidden" style="font-size:12px;color:#6b7280;margin-left:8px;">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </div>

            </div>

            {{-- Toggle CC --}}
            <div class="bv-email-tools-row">
                <button id="emailToggleCc" class="bv-toggle-cc">
                    <i class="fas fa-plus bv-icon-xs"></i> Mostrar CC/BCC
                </button>
            </div>

            {{-- Barra de formato simple --}}
            <div class="bv-email-toolbar">
                <button class="tt bv-fmt-btn bv-fmt-btn-bold" data-tt="Negrita">B</button>
                <button class="tt bv-fmt-btn bv-fmt-btn-italic" data-tt="Itálica">I</button>
                <button class="tt bv-fmt-btn bv-fmt-btn-muted" data-tt="Enlace"><i class="fas fa-link bv-icon-sm"></i></button>
                <button class="tt bv-fmt-btn bv-fmt-btn-muted" data-tt="Lista"><i class="fas fa-list bv-icon-sm"></i></button>
                <div class="bv-spacer"></div>
                <button id="emailScheduleToggle" class="tt bv-schedule-btn" data-tt="Programar envío">
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
            <div id="emailHtmlPreviewWrap" class="bv-hidden" style="margin-top:8px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <span style="font-size:12px;color:#6b7280;font-weight:600;">Vista previa HTML de la plantilla</span>
                    <button type="button" id="emailTogglePreview" style="font-size:11px;color:#b10100;background:none;border:none;cursor:pointer;padding:0;">
                        Ocultar
                    </button>
                </div>
                <iframe id="emailHtmlPreview" sandbox="allow-same-origin"
                    style="width:100%;min-height:220px;border:1px solid #e5e7eb;border-radius:4px;background:#fff;"></iframe>
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
        var hidden = $iframe.is(':hidden');
        $iframe.toggle();
        $(this).text(hidden ? 'Ocultar' : 'Mostrar');
    });

    $(document).on('click', '#emailToggleCc', function () {
        var row = $('#emailCcRow');
        var visible = row.toggle().is(':visible');
        $(this).html(visible
            ? '<i class="fas fa-minus bv-cc-toggle-icon"></i> Ocultar CC/BCC'
            : '<i class="fas fa-plus bv-cc-toggle-icon"></i> Mostrar CC/BCC'
        );
    });

    $(document).on('click', '#emailScheduleToggle', function () {
        $('#emailScheduleRow').toggle();
    });

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
            if (resp?.item && typeof window.appendBubbleToThread === 'function') {
                window.appendBubbleToThread(resp.item, false);
            }
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
