{{-- Modal: Vista previa conversación histórica (solo lectura) --}}
<div class="bv-modal" data-bv-modal-name="preview-conv">
    <div class="bv-modal-dialog lg">
        <div class="bv-modal-head">
            <div class="bv-modal-title">Seguimiento pedido #8400</div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Meta header --}}
            <div class="mv4-prev-head">
                <div class="mv4-prev-meta">
                    <span class="mv4-pill">
                        <span class="d" style="background:var(--bv-wa)"></span>WhatsApp
                    </span>
                    <span class="mv4-pill">
                        <span class="d" style="background:var(--bv-success)"></span>Cerrada
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
            <div style="margin-top:12px;padding:8px 12px;background:var(--bv-bg-subtle);border-radius:8px;font-size:11.5px;color:var(--bv-text-muted);display:flex;gap:6px;align-items:center">
                <i class="fas fa-lock" style="font-size:10px"></i>
                <span>Esta conversación está cerrada. No se puede responder desde esta vista previa.</span>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-secondary">
                <i class="fas fa-print"></i> Exportar
            </button>
            <div style="margin-left:auto;display:flex;gap:8px">
                <button class="btn-secondary" data-bv-close>Cerrar</button>
                <button class="btn-secondary">
                    <i class="fas fa-arrow-up-right-from-square"></i> Abrir conversación
                </button>
            </div>
        </div>
    </div>
</div>
