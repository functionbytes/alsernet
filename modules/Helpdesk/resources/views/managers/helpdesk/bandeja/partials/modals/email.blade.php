{{-- Modal: Enviar email --}}
<div class="bv-modal" data-bv-modal-name="email">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title">Enviar email</div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">
            <div class="mv4-email">

                {{-- Para --}}
                <div class="row">
                    <span class="lbl">Para</span>
                    <div class="chip">julia.g@gmail.com <i class="fas fa-xmark"></i></div>
                    <input type="email" placeholder="Añadir destinatario…">
                </div>

                {{-- CC (toggle) --}}
                <div class="row" id="emailCcRow" style="display:none">
                    <span class="lbl">CC</span>
                    <input type="email" placeholder="Añadir en copia…">
                </div>

                {{-- Asunto --}}
                <div class="row">
                    <span class="lbl">Asunto</span>
                    <input type="text" value="Re: Tu pedido #8421">
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
            <div style="padding:4px 0 8px">
                <button id="emailToggleCc" style="font-size:11px;color:var(--bv-text-muted);background:transparent;border:0;cursor:pointer;padding:0">
                    <i class="fas fa-plus" style="font-size:9px"></i> Mostrar CC/BCC
                </button>
            </div>

            {{-- Barra de formato simple --}}
            <div style="display:flex;gap:4px;padding:6px 0;border-top:1px solid var(--bv-border);border-bottom:1px solid var(--bv-border);margin-bottom:8px">
                <button class="tt" data-tt="Negrita" style="width:28px;height:28px;border:0;background:transparent;border-radius:5px;font-weight:700;font-size:13px;cursor:pointer;color:var(--bv-text)">B</button>
                <button class="tt" data-tt="Itálica" style="width:28px;height:28px;border:0;background:transparent;border-radius:5px;font-style:italic;font-size:13px;cursor:pointer;color:var(--bv-text)">I</button>
                <button class="tt" data-tt="Enlace" style="width:28px;height:28px;border:0;background:transparent;border-radius:5px;cursor:pointer;color:var(--bv-text-muted)"><i class="fas fa-link" style="font-size:11px"></i></button>
                <button class="tt" data-tt="Lista" style="width:28px;height:28px;border:0;background:transparent;border-radius:5px;cursor:pointer;color:var(--bv-text-muted)"><i class="fas fa-list" style="font-size:11px"></i></button>
                <div style="flex:1"></div>
                <button id="emailScheduleToggle" class="tt" data-tt="Programar envío" style="padding:4px 10px;border:1px solid var(--bv-border);background:transparent;border-radius:5px;font-size:11px;cursor:pointer;color:var(--bv-text-muted);display:inline-flex;align-items:center;gap:4px">
                    <i class="fas fa-clock"></i> Programar
                </button>
            </div>

            {{-- Programar envío --}}
            <div id="emailScheduleRow" style="display:none;margin-bottom:8px;padding:8px 10px;background:var(--bv-bg-subtle);border-radius:8px;font-size:12px">
                <label style="font-size:11px;font-weight:600;color:var(--bv-text-muted);text-transform:uppercase;letter-spacing:.08em">Enviar el</label>
                <input type="datetime-local" value="{{ date('Y-m-d') }}T10:00" style="width:100%;margin-top:4px;padding:6px 10px;border:1px solid var(--bv-border);border-radius:7px;font-size:12px;font-family:inherit">
            </div>

            {{-- Body --}}
            <textarea id="emailBody" rows="8" style="width:100%;padding:12px;border:1px solid var(--bv-border);border-radius:9px;font-family:inherit;font-size:13px;line-height:1.55;resize:vertical;background:var(--bv-bg-panel);color:var(--bv-text)">Hola Julia,

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
            <div style="margin-left:auto;display:flex;gap:8px">
                <button class="btn-secondary" data-bv-close>Cancelar</button>
                <button class="btn-primary">
                    <i class="far fa-paper-plane"></i> Enviar
                </button>
            </div>
        </div>
    </div>
</div>

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
            ? '<i class="fas fa-minus" style="font-size:9px"></i> Ocultar CC/BCC'
            : '<i class="fas fa-plus" style="font-size:9px"></i> Mostrar CC/BCC'
        );
    });

    $(document).on('click', '#emailScheduleToggle', function() {
        $('#emailScheduleRow').toggle();
    });

    $(document).on('click', '.mv4-email .chip i', function() {
        $(this).closest('.chip').remove();
    });
}());
</script>
@endverbatim
