{{-- Modal: Fusionar conversación --}}
<div class="bv-modal" data-bv-modal-name="merge">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div>
                <span class="bv-modal-label">Chat · Acción irreversible</span>
                <div class="bv-modal-title">
                    <i class="fas fa-code-merge bv-modal-title-icon"></i>
                    Fusionar conversación
                    @if(!empty($selectedConversation))<span class="bv-chip-id">#{{ $selectedConversation->id }}</span>@endif
                </div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            @include('helpdesk::managers.inbox.partials.modals._context-card')

            {{-- Warning --}}
            <div class="alert warning">
                <i class="fas fa-triangle-exclamation lead"></i>
                <div>Esta acción <b>no se puede deshacer</b>. La conversación secundaria se archivará y sus mensajes aparecerán en la principal ordenados cronológicamente.</div>
            </div>

            {{-- Search --}}
            <div class="bv-modal-search bv-mb-12">
                <i class="fas fa-magnifying-glass"></i>
                <input id="merge-search" type="text" placeholder="Buscar conversación destino…" autocomplete="off">
            </div>

            {{-- Conversation list --}}
            <div class="bv-right-section-title bv-mb-8">Conversaciones del mismo contacto</div>
            <div class="bv-opt-list" id="merge-list">
                <div class="bv-opt on" data-conv-id="7801">
                    <div class="bv-av c5 bv-av-rounded bv-merge-av"><i class="fab fa-facebook-messenger"></i></div>
                    <div class="body">
                        <div class="name">#7801 · Consulta de pedido</div>
                        <div class="sub">Hola, quería saber si llegó ya mi pedido… · hace 4 días</div>
                    </div>
                    <span class="bv-modal-radio-dot flex-shrink-0"></span>
                </div>
                <div class="bv-opt" data-conv-id="7654">
                    <div class="bv-av c2 bv-av-rounded bv-merge-av"><i class="far fa-comment"></i></div>
                    <div class="body">
                        <div class="name">#7654 · Formulario web</div>
                        <div class="sub">Buenas tardes, tengo una duda sobre… · 12 mar</div>
                    </div>
                    <span class="bv-modal-radio-dot flex-shrink-0"></span>
                </div>
                <div class="bv-opt" data-conv-id="7512">
                    <div class="bv-av c4 bv-av-rounded bv-merge-av bv-merge-av--sm"><i class="fas fa-envelope"></i></div>
                    <div class="body">
                        <div class="name">#7512 · Seguimiento devolución</div>
                        <div class="sub">Hola, quería saber en qué estado está mi… · 2 mar</div>
                    </div>
                    <span class="bv-modal-radio-dot flex-shrink-0"></span>
                </div>
            </div>

            {{-- Merge preview --}}
            <div class="bv-right-section-title bv-mt-16 bv-mb-8">Vista previa de fusión</div>
            <div class="bv-merge-preview-grid">
                <div class="bv-merge-conv-panel">
                    <div class="bv-merge-panel-label">Conversación actual</div>
                    <div class="bv-bubble bv-merge-bubble">¡Hola! ¿Me podéis ayudar con mi pedido #1234?</div>
                    <div class="bv-bubble bv-merge-bubble">Hola Carmen, claro que sí. ¿Qué ha pasado?</div>
                    <div class="bv-bubble bv-merge-bubble-last">El tracking dice entregado pero no lo tengo.</div>
                </div>
                <div class="bv-merge-conv-panel">
                    <div class="bv-merge-panel-label">Conversación destino</div>
                    <div class="bv-bubble bv-merge-bubble">Hola, quería saber si llegó ya mi pedido…</div>
                    <div class="bv-bubble bv-merge-bubble">Estamos revisando, te confirmamos en breve.</div>
                    <div class="bv-bubble bv-merge-bubble-last">Perfecto, muchas gracias.</div>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-merge-apply">Fusionar conversaciones</button>
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

// Carga real de candidatos al abrir el modal
$(document).on('click', '[data-bv-modal="merge"]', function () {
    var convId = $('.bv-composer').data('bv-conversation-id');
    if (!convId) return;
    $.get('/panel/helpdesk/conversations/' + convId + '/merge-candidates').done(function (resp) {
        var $list = $('#merge-list');
        if (!Array.isArray(resp?.candidates) || !resp.candidates.length) return;
        $list.empty();
        resp.candidates.forEach(function (c, i) {
            var icon = c.channel === 'email' ? 'fas fa-envelope'
                : c.channel === 'whatsapp' ? 'fab fa-whatsapp'
                : c.channel === 'facebook' ? 'fab fa-facebook-messenger'
                : c.channel === 'instagram' ? 'fab fa-instagram'
                : 'far fa-comment';
            var html = '<div class="bv-opt' + (i === 0 ? ' on' : '') + '" data-conv-id="' + c.id + '">' +
                '<div class="bv-av c' + ((c.id % 8) + 1) + ' bv-av-rounded bv-merge-av"><i class="' + icon + '"></i></div>' +
                '<div class="body">' +
                    '<div class="name">#' + c.id + ' · ' + (c.subject || 'Sin asunto') + '</div>' +
                    '<div class="sub">' + (c.last_preview || '—') + ' · ' + (c.last_at || '') + '</div>' +
                '</div>' +
                '<span class="bv-modal-radio-dot flex-shrink-0"></span>' +
            '</div>';
            $list.append(html);
        });
    });
});

// Aplicar fusión
$(document).on('click', '#bv-merge-apply', function () {
    var $btn = $(this);
    var convId = $('.bv-composer').data('bv-conversation-id');
    var targetId = $('#merge-list .bv-opt.on').data('conv-id');
    if (!convId || !targetId) {
        toastr.warning('Selecciona la conversación destino');
        return;
    }
    if (!confirm('¿Fusionar esta conversación con #' + targetId + '? Esta acción es irreversible.')) return;
    $btn.prop('disabled', true);
    $.ajax({
        url: '/panel/helpdesk/conversations/' + convId + '/merge',
        method: 'POST',
        dataType: 'json',
        data: { target_id: targetId },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json',
        },
    }).done(function (resp) {
        var redirect = '/panel/helpdesk/conversations?selected=' + targetId;
        setTimeout(function () { window.location.href = redirect; }, 600);
    }).fail(function (xhr) {
        toastr.error(xhr?.responseJSON?.message || 'No se pudo fusionar');
    }).always(function () { $btn.prop('disabled', false); });
});
</script>
@endpush
@endonce
