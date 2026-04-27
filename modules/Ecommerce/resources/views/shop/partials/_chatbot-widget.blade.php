<div id="chatbot-widget" class="chatbot-widget">
    <button id="chatbot-toggle" class="chatbot-toggle" aria-label="Abrir chat de ayuda">
        <i class="fas fa-comments"></i>
    </button>
    <div id="chatbot-panel" class="chatbot-panel" style="display:none">
        <div class="chatbot-header">
            <strong><i class="fas fa-robot me-2"></i>Asistente</strong>
            <button id="chatbot-close" class="chatbot-close-btn" aria-label="Cerrar chat">&times;</button>
        </div>
        <div id="chatbot-messages" class="chatbot-messages">
            <div class="chatbot-msg bot">
                ¡Hola! Soy el asistente virtual. ¿En qué puedo ayudarte?
                <div class="chatbot-quick mt-2">
                    <button class="chatbot-quick-btn" data-q="¿Cuáles son los métodos de pago?">Métodos de pago</button>
                    <button class="chatbot-quick-btn" data-q="¿Cómo es el envío?">Envíos</button>
                    <button class="chatbot-quick-btn" data-q="¿Cómo devuelvo un producto?">Devoluciones</button>
                </div>
            </div>
        </div>
        <form id="chatbot-form" class="chatbot-input-wrap">
            @csrf
            <input type="text" id="chatbot-input" placeholder="Escribe tu mensaje..." aria-label="Tu mensaje" maxlength="500" required>
            <button type="submit" aria-label="Enviar"><i class="fas fa-paper-plane"></i></button>
        </form>
    </div>
</div>

<style>
.chatbot-widget { position: fixed; bottom: 90px; left: 24px; z-index: 9990; }
.chatbot-toggle {
    width: 56px; height: 56px;
    background: #1a2030; color: #fff;
    border: none; border-radius: 50%;
    font-size: 24px; cursor: pointer;
    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
}
.chatbot-toggle:hover { background: #2d3447; }
.chatbot-panel {
    position: absolute; bottom: 70px; left: 0;
    width: 360px; max-width: 90vw;
    height: 500px; max-height: 70vh;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    display: flex; flex-direction: column;
    overflow: hidden;
}
.chatbot-header {
    background: #90bb13; color: #fff;
    padding: 12px 16px;
    display: flex; justify-content: space-between; align-items: center;
}
.chatbot-close-btn { background: none; border: none; color: #fff; font-size: 24px; cursor: pointer; }
.chatbot-messages { flex: 1; overflow-y: auto; padding: 12px; }
.chatbot-msg { margin-bottom: 12px; padding: 8px 12px; border-radius: 12px; max-width: 85%; }
.chatbot-msg.bot { background: #f1f3f5; color: #212529; }
.chatbot-msg.user { background: #90bb13; color: #fff; margin-left: auto; }
.chatbot-msg a { color: inherit; text-decoration: underline; }
.chatbot-quick { display: flex; flex-wrap: wrap; gap: 6px; }
.chatbot-quick-btn {
    background: #fff; border: 1px solid #dee2e6;
    padding: 4px 10px; border-radius: 16px;
    font-size: 12px; cursor: pointer;
}
.chatbot-quick-btn:hover { border-color: #90bb13; color: #90bb13; }
.chatbot-input-wrap {
    border-top: 1px solid #dee2e6;
    padding: 8px;
    display: flex; gap: 6px;
}
.chatbot-input-wrap input { flex: 1; padding: 6px 12px; border: 1px solid #dee2e6; border-radius: 20px; }
.chatbot-input-wrap button { background: #90bb13; color: #fff; border: none; padding: 6px 12px; border-radius: 20px; cursor: pointer; }
.chatbot-product-card { background: #fff; border: 1px solid #e9ecef; padding: 6px; border-radius: 6px; margin-top: 4px; font-size: 13px; }
.chatbot-product-card strong { display: block; }
.chatbot-product-card .price { color: #90bb13; font-weight: 600; }
@media (max-width: 575px) {
    .chatbot-widget { left: 16px; bottom: 76px; }
    .chatbot-toggle { width: 48px; height: 48px; font-size: 20px; }
}
</style>

<script>
$(function() {
    const $panel = $('#chatbot-panel');

    $('#chatbot-toggle').on('click', function() {
        $panel.toggle();
        if ($panel.is(':visible')) {
            $('#chatbot-input').focus();
        }
    });

    $('#chatbot-close').on('click', function() {
        $panel.hide();
    });

    function escapeHtml(text) {
        return $('<div>').text(text).html();
    }

    function sendMessage(message) {
        if (!message.trim()) { return; }

        const $messages = $('#chatbot-messages');
        $messages.append('<div class="chatbot-msg user">' + escapeHtml(message) + '</div>');
        $messages.append('<div class="chatbot-msg bot" id="chatbot-typing">...</div>');
        $messages.scrollTop($messages[0].scrollHeight);

        $.ajax({
            url: '{{ route('shop.chatbot.ask') }}',
            method: 'POST',
            data: { message },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(r) {
                $('#chatbot-typing').remove();
                let html = '<div class="chatbot-msg bot">' + r.response.text;
                if (r.response.products) {
                    html += '<div class="mt-2">';
                    r.response.products.forEach(function(p) {
                        html += '<a href="' + p.url + '" class="chatbot-product-card text-decoration-none text-dark d-block">'
                            + '<strong>' + escapeHtml(p.name) + '</strong>'
                            + '<span class="price">' + p.price + '</span>'
                            + '</a>';
                    });
                    html += '</div>';
                }
                html += '</div>';
                $messages.append(html);
                $messages.scrollTop($messages[0].scrollHeight);
            },
            error: function() {
                $('#chatbot-typing').remove();
                $messages.append('<div class="chatbot-msg bot">Disculpa, hubo un error. Intenta de nuevo.</div>');
            }
        });
    }

    $('#chatbot-form').on('submit', function(e) {
        e.preventDefault();
        const $input = $('#chatbot-input');
        sendMessage($input.val());
        $input.val('');
    });

    $(document).on('click', '.chatbot-quick-btn', function() {
        sendMessage($(this).data('q'));
    });
});
</script>
