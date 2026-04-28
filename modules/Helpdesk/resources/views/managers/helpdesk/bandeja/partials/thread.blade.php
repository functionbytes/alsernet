{{-- Hilo de chat (Alvarez/Refined v4) --}}
<div class="bv-thread">
    <div class="bv-th-head">
        <div class="who">
            <div class="av">
                CP
                <span class="badge-ch wa" style="position:absolute;bottom:-4px;right:-4px;width:18px;height:18px;border-radius:6px;border:2.5px solid var(--bv-bg-panel);background:#25d366;display:grid;place-items:center;color:#fff;font-size:9px">
                    <i class="fab fa-whatsapp"></i>
                </span>
            </div>
            <div>
                <div class="nm">Carmen Pérez</div>
                <div class="sub">
                    <span class="online"></span>
                    En línea · WhatsApp · +34 612 345 678
                </div>
            </div>
        </div>
        <div class="actions">
            <button class="bv-th-pill" data-bv-modal="status">
                <span class="dot" style="background:#059669"></span>
                Abierta
                <i class="fas fa-chevron-down" style="font-size:9px"></i>
            </button>
            <button class="bv-th-pill" data-bv-modal="priority">
                <span class="dot" style="background:#d97706"></span>
                Alta
                <i class="fas fa-chevron-down" style="font-size:9px"></i>
            </button>
            <span style="width:1px;height:20px;background:var(--bv-border);margin:0 4px"></span>
            <button class="bv-th-action" data-bv-modal="email" title="Email">
                <i class="far fa-envelope"></i>
            </button>
            <button class="bv-th-action" data-bv-modal="schedule" title="Agendar">
                <i class="far fa-calendar-plus"></i>
            </button>
            <button class="bv-th-action" data-bv-modal="snooze" title="Snooze">
                <i class="far fa-clock"></i>
            </button>
            <button class="bv-th-action" data-bv-modal="assign" title="Asignar">
                <i class="far fa-user-plus"></i>
            </button>
            <button class="bv-th-action" data-bv-modal="tags" title="Etiquetar">
                <i class="fas fa-tag"></i>
            </button>
            <button class="bv-th-action" data-bv-modal="close-conv" title="Cerrar">
                <i class="fas fa-check"></i>
            </button>
            <button class="bv-th-action" title="Más">
                <i class="fas fa-ellipsis-vertical"></i>
            </button>
        </div>
    </div>

    <div class="bv-th-body">
        <div class="bv-th-inner">
            <div class="bv-day-sep"><span>Hoy</span></div>

            {{-- Mensaje entrante --}}
            <div class="bv-msg in">
                <div class="av-sm" style="background:linear-gradient(135deg,#f97316,#db2777)">CP</div>
                <div class="bv-bubble">
                    Hola, buenos días. Necesito información sobre el producto que vi en su tienda online. ¿Tienen disponibilidad?
                    <div class="meta">
                        <span>09:42</span>
                    </div>
                </div>
            </div>

            {{-- Mensaje entrante 2 --}}
            <div class="bv-msg in">
                <div class="av-sm" style="background:linear-gradient(135deg,#f97316,#db2777)">CP</div>
                <div class="bv-bubble">
                    El producto ID-1234, en color azul, talla M.
                    <div class="meta">
                        <span>09:43</span>
                    </div>
                </div>
            </div>

            {{-- Mensaje saliente --}}
            <div class="bv-msg out">
                <div class="bv-bubble">
                    ¡Hola Carmen! Sí, tenemos ese modelo en stock. Te paso el enlace directo a la página del producto. 🛍️
                    <div class="meta">
                        <span>María L · 09:45</span>
                        <span class="chk read">✓✓</span>
                    </div>
                </div>
            </div>

            {{-- Nota interna --}}
            <div class="bv-msg in">
                <div class="av-sm" style="background:linear-gradient(135deg,#f59e0b,#d97706)">ML</div>
                <div class="bv-bubble note">
                    <div class="note-badge">
                        <i class="fas fa-lock"></i> Nota interna
                    </div>
                    Cliente recurrente — VIP. Aplicar código de descuento <code>VIP10</code> si pregunta por descuentos.
                    <div class="meta">
                        <span>María L · 09:46</span>
                    </div>
                </div>
            </div>

            {{-- Mensaje entrante --}}
            <div class="bv-msg in">
                <div class="av-sm" style="background:linear-gradient(135deg,#f97316,#db2777)">CP</div>
                <div class="bv-bubble">
                    Perfecto, ¿tienen algún descuento aplicable? Soy cliente recurrente.
                    <div class="meta">
                        <span>09:48</span>
                    </div>
                </div>
            </div>

            {{-- Indicador escribiendo --}}
            <div class="bv-msg in">
                <div class="av-sm" style="background:linear-gradient(135deg,#f97316,#db2777)">CP</div>
                <div class="bv-bubble" style="padding:14px 16px">
                    <span style="display:inline-flex;gap:3px">
                        <span class="bv-typing-dot"></span>
                        <span class="bv-typing-dot"></span>
                        <span class="bv-typing-dot"></span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Composer --}}
    <div class="bv-composer">
        <div class="bv-composer-tabs">
            <button class="bv-composer-tab on" data-bv-tab="reply">
                <i class="far fa-reply" style="margin-right:4px;font-size:11px"></i>Respuesta
            </button>
            <button class="bv-composer-tab note" data-bv-tab="note">
                <i class="fas fa-lock" style="margin-right:4px;font-size:11px"></i>Nota interna
            </button>
            <button class="bv-composer-tab" data-bv-tab="hsm">
                <i class="fab fa-whatsapp" style="margin-right:4px;font-size:11px"></i>Plantillas HSM
            </button>
            <button class="bv-composer-tab" data-bv-tab="translate">
                <i class="fas fa-language" style="margin-right:4px;font-size:11px"></i>Traducir
            </button>
        </div>
        <div class="bv-composer-box" id="bv-composer-box">
            <textarea class="bv-composer-input" placeholder="Escribe tu respuesta… (use / para respuestas rápidas, @ para mencionar)" rows="2"></textarea>
            <div class="bv-composer-toolbar">
                <button class="btn-ico" title="Adjuntar">
                    <i class="fas fa-paperclip"></i>
                </button>
                <button class="btn-ico" title="Emoji">
                    <i class="far fa-face-smile"></i>
                </button>
                <button class="btn-ico" title="Mención">
                    <i class="fas fa-at"></i>
                </button>
                <button class="btn-ico" title="Respuesta rápida">
                    <i class="fas fa-bolt"></i>
                </button>
                <button class="btn-ico" title="Sugerencia IA">
                    <i class="fas fa-sparkles"></i>
                </button>
                <button class="btn-send">
                    <i class="far fa-paper-plane"></i>Enviar
                    <kbd style="background:rgba(255,255,255,.15);border-radius:3px;padding:1px 5px;font-size:10px;margin-left:4px">⌘↵</kbd>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.bv-typing-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--bv-text-muted);
    animation: bv-typing 1.4s infinite ease-in-out;
}
.bv-typing-dot:nth-child(2) { animation-delay: .2s; }
.bv-typing-dot:nth-child(3) { animation-delay: .4s; }
@keyframes bv-typing {
    0%, 60%, 100% { opacity: .3; transform: translateY(0); }
    30% { opacity: 1; transform: translateY(-3px); }
}
</style>
