{{-- Modal: Vista previa conversación histórica (solo lectura) --}}
<div class="bv-modal" data-bv-modal-name="preview-conv">
    <div class="bv-modal-dialog lg">
        <div class="bv-modal-head">
            <div class="bv-modal-title"><i class="far fa-clock-rotate-left bv-modal-title-icon"></i> Seguimiento pedido #8400</div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Meta header --}}
            <div class="mv4-prev-head">
                <div class="mv4-prev-meta">
                    <span class="mv4-pill">
                        <span class="d bv-d-wa"></span>WhatsApp
                    </span>
                    <span class="mv4-pill">
                        <span class="d bv-d-success"></span>Cerrada
                    </span>
                    <span class="mv4-pill">Carlos R.</span>
                    <span class="mv4-pill mono">14 mar</span>
                    <span class="mv4-pill">Julia García</span>
                </div>
            </div>

            {{-- Hilo de mensajes (solo lectura) --}}
            <div class="mv4-prev-thread">

                <div class="mv4-pmsg in">
                    <div class="bubble">
                        <div class="body">Hola, quería saber el estado de mi pedido #8400</div>
                        <div class="t">14 mar 09:24</div>
                    </div>
                </div>

                <div class="mv4-pmsg out">
                    <div class="bubble">
                        <div class="who">Carlos R.</div>
                        <div class="body">¡Hola Julia! Tu pedido salió esta mañana, llega entre hoy y mañana 📦</div>
                        <div class="t">14 mar 09:31</div>
                    </div>
                </div>

                <div class="mv4-pmsg in">
                    <div class="bubble">
                        <div class="body">Perfecto, gracias!</div>
                        <div class="t">14 mar 09:32</div>
                    </div>
                </div>

                <div class="mv4-pmsg in">
                    <div class="bubble">
                        <div class="body">Ya llegó, todo correcto 👍</div>
                        <div class="t">14 mar 18:02</div>
                    </div>
                </div>

                <div class="mv4-pmsg out">
                    <div class="bubble">
                        <div class="who">Carlos R.</div>
                        <div class="body">¡Genial! Cualquier cosa nos avisas. Que disfrutes 😊</div>
                        <div class="t">14 mar 18:15</div>
                    </div>
                </div>

            </div>

            {{-- Nota read-only --}}
            <div class="alert info">
                <i class="fas fa-lock lead"></i>
                <div>Esta conversación está cerrada. No se puede responder desde esta vista previa.</div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-secondary">Abrir conversación</button>
            <button class="btn-secondary">Exportar</button>
            <button class="btn-secondary" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>
