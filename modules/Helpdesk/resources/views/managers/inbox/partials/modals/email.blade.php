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
                    <div class="chip">julia.g@gmail.com <i class="fas fa-xmark"></i></div>
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
                    <input type="text" id="emailSubject" name="subject" value="Re: Tu pedido #8421">
                </div>

                {{-- Plantilla --}}
                <div class="row">
                    <span class="lbl">Plantilla</span>
                    <select id="emailTemplate">
                        <option value="">— Sin plantilla —</option>
                        <option value="confirm">Confirmación de envío</option>
                        <option value="delay">Disculpa por retraso</option>
                        <option value="feedback">Solicitud de feedback</option>
                        <option value="welcome">Bienvenida</option>
                    </select>
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
            <textarea id="emailBody" rows="8" class="bv-email-body">Hola Julia,

Gracias por tu paciencia. Te confirmo que tu pedido #8421 está siendo gestionado con prioridad.

Un saludo,
María López — Soporte Functionbytes</textarea>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-secondary">
                <i class="fas fa-paperclip"></i> Adjuntar
            </button>
            <button class="btn-secondary">
                <i class="far fa-floppy-disk"></i> Borrador
            </button>
            <div class="bv-email-foot-actions">
                <button class="btn-secondary" data-bv-close>Cancelar</button>
                <button class="btn-primary" id="bv-email-send">
                    <i class="far fa-paper-plane"></i> Enviar
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
@verbatim
<script>
(function() {
    var templates = {
        confirm: "Hola {{nombre}},\n\nTe confirmamos que tu pedido #{{numero}} ha sido enviado y llegará en los próximos días.\n\nUn saludo,\nEquipo de Soporte",
        delay: "Hola {{nombre}},\n\nLamentamos los inconvenientes causados por el retraso en tu pedido. Estamos trabajando para resolverlo lo antes posible.\n\nDisculpa las molestias,\nEquipo de Soporte",
        feedback: "Hola {{nombre}},\n\n¿Cómo fue tu experiencia con nosotros? Tu opinión es muy importante para mejorar nuestro servicio.\n\nGracias de antemano,\nEquipo de Soporte",
        welcome: "Hola {{nombre}},\n\nBienvenido/a a Functionbytes. Estamos encantados de tenerte con nosotros.\n\n¿En qué podemos ayudarte?\nEquipo de Soporte"
    };

    $(document).on('change', '#emailTemplate', function() {
        var val = $(this).val();
        if (val && templates[val]) {
            $('#emailBody').val(templates[val]);
        }
    });

    $(document).on('click', '#emailToggleCc', function() {
        var row = $('#emailCcRow');
        var visible = row.toggle().is(':visible');
        $(this).html(visible
            ? '<i class="fas fa-minus bv-cc-toggle-icon"></i> Ocultar CC/BCC'
            : '<i class="fas fa-plus bv-cc-toggle-icon"></i> Mostrar CC/BCC'
        );
    });

    $(document).on('click', '#emailScheduleToggle', function() {
        $('#emailScheduleRow').toggle();
    });

    $(document).on('click', '.mv4-email .chip i', function() {
        $(this).closest('.chip').remove();
    });

    $(document).on('click', '#bv-email-send', function () {
        var $btn = $(this);
        var convId = $('.bv-composer').data('bv-conversation-id');
        if (!convId) { toastr.error('No hay conversación seleccionada'); return; }
        var to = ($('#emailTo').val() || '').trim();
        var subject = ($('#emailSubject').val() || '').trim();
        var body = ($('#emailBody').val() || '').trim();
        if (!to || !subject || !body) {
            toastr.warning('Completa destinatario, asunto y cuerpo');
            return;
        }
        $btn.prop('disabled', true);
        var ccVal = ($('#emailCc').val() || '').trim();
        var ccList = ccVal ? ccVal.split(/[,;]\s*/).filter(Boolean) : [];
        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/send-email',
            method: 'POST',
            dataType: 'json',
            data: {
                subject: subject,
                body: body,
                cc: ccList,
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json',
            },
        }).done(function (resp) {
            toastr.success(resp?.message || 'Correo enviado');
            $('[data-bv-modal-name="email"]').removeClass('on');
            $('body').css('overflow', '');
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
@endverbatim
@endpush
