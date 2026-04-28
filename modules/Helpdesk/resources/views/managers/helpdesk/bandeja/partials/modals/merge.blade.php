{{-- Modal: Fusionar conversación --}}
<div class="bv-modal" data-bv-modal-name="merge">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title">Fusionar con otra conversación</div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Warning --}}
            <div style="padding:10px 12px;background:#fef3c7;border:1px solid #fde68a;border-radius:8px;font-size:11.5px;color:#78350f;display:flex;gap:8px;align-items:flex-start;margin-bottom:14px">
                <i class="fas fa-triangle-exclamation" style="margin-top:2px;flex-shrink:0"></i>
                <div>Esta acción <strong>no se puede deshacer</strong>. La conversación secundaria se archivará y sus mensajes aparecerán en la principal ordenados cronológicamente.</div>
            </div>

            {{-- Search --}}
            <div class="bv-modal-search" style="margin-bottom:12px">
                <i class="fas fa-magnifying-glass"></i>
                <input id="merge-search" type="text" placeholder="Buscar conversación destino…" autocomplete="off">
            </div>

            {{-- Conversation list --}}
            <div class="bv-right-section-title" style="margin-bottom:8px">Conversaciones del mismo contacto</div>
            <div class="bv-opt-list" id="merge-list">
                <div class="bv-opt on" data-conv-id="7801">
                    <div class="bv-av c5" style="border-radius:10px;font-size:12px"><i class="fab fa-facebook-messenger" style="font-size:14px"></i></div>
                    <div class="body">
                        <div class="name">#7801 · Consulta de pedido</div>
                        <div class="sub">Hola, quería saber si llegó ya mi pedido… · hace 4 días</div>
                    </div>
                    <span class="bv-modal-radio-dot" style="flex-shrink:0"></span>
                </div>
                <div class="bv-opt" data-conv-id="7654">
                    <div class="bv-av c2" style="border-radius:10px;font-size:12px"><i class="far fa-comment" style="font-size:14px"></i></div>
                    <div class="body">
                        <div class="name">#7654 · Formulario web</div>
                        <div class="sub">Buenas tardes, tengo una duda sobre… · 12 mar</div>
                    </div>
                    <span class="bv-modal-radio-dot" style="flex-shrink:0"></span>
                </div>
                <div class="bv-opt" data-conv-id="7512">
                    <div class="bv-av c4" style="border-radius:10px;font-size:12px"><i class="fas fa-envelope" style="font-size:12px"></i></div>
                    <div class="body">
                        <div class="name">#7512 · Seguimiento devolución</div>
                        <div class="sub">Hola, quería saber en qué estado está mi… · 2 mar</div>
                    </div>
                    <span class="bv-modal-radio-dot" style="flex-shrink:0"></span>
                </div>
            </div>

            {{-- Merge preview --}}
            <div class="bv-right-section-title" style="margin-top:16px;margin-bottom:8px">Vista previa de fusión</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <div style="background:var(--bv-bg-subtle);border-radius:10px;padding:12px">
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--bv-text-muted);margin-bottom:8px">Conversación actual</div>
                    <div class="bv-bubble" style="margin-bottom:6px;font-size:11.5px">¡Hola! ¿Me podéis ayudar con mi pedido #1234?</div>
                    <div class="bv-bubble" style="margin-bottom:6px;font-size:11.5px">Hola Carmen, claro que sí. ¿Qué ha pasado?</div>
                    <div class="bv-bubble" style="font-size:11.5px">El tracking dice entregado pero no lo tengo.</div>
                </div>
                <div style="background:var(--bv-bg-subtle);border-radius:10px;padding:12px">
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--bv-text-muted);margin-bottom:8px">Conversación destino</div>
                    <div class="bv-bubble" style="margin-bottom:6px;font-size:11.5px">Hola, quería saber si llegó ya mi pedido…</div>
                    <div class="bv-bubble" style="margin-bottom:6px;font-size:11.5px">Estamos revisando, te confirmamos en breve.</div>
                    <div class="bv-bubble" style="font-size:11.5px">Perfecto, muchas gracias.</div>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary"><i class="fas fa-code-merge"></i> Fusionar conversaciones</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
$(document).on('click', '[data-bv-modal-name="merge"] .bv-opt', function () {
    $('[data-bv-modal-name="merge"] .bv-opt').removeClass('on');
    $(this).addClass('on');
});

$(document).on('input', '#merge-search', function () {
    var q = $(this).val().toLowerCase();
    $('[data-bv-modal-name="merge"] .bv-opt').each(function () {
        var text = $(this).find('.name, .sub').text().toLowerCase();
        $(this).toggle(!q || text.includes(q));
    });
});
</script>
@endpush
@endonce
