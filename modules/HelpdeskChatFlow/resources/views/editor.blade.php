@extends('layouts.theme')

@section('title', isset($chatFlow) ? 'Editar: ' . $chatFlow->name : 'Nuevo flow')

@section('page_header')
    @include('core::components.card', ['title' => isset($chatFlow) ? 'Editar flow' : 'Nuevo flow'])
@endsection

@section('content')

<div class="d-flex flex-column editor-full-height">

    {{-- Canvas + Config panel --}}
    <div class="d-flex flex-grow-1 overflow-hidden">

        {{-- React editor root --}}
        <div
            id="chatflow-editor-root"
            class="flex-grow-1"
            data-props="{{ json_encode([
                'chatFlowId'     => $chatFlow->id,
                'chatFlowName'   => $chatFlow->name,
                'chatFlowStatus' => $chatFlow->status,
                'nodes'          => $chatFlow->nodes ?? [],
                'settings'       => $chatFlow->trigger_conditions ?? [],
                'agents'         => $agents ?? [],
                'groups'         => $groups ?? [],
                'saveUrl'        => route('chatflow.update', $chatFlow),
                'publishUrl'     => route('chatflow.publish', $chatFlow),
                'indexUrl'       => route('chatflow.index'),
                'csrfToken'      => csrf_token(),
            ]) }}"
        ></div>

        {{-- Test / Preview panel --}}
        <div id="test-panel" class="test-panel d-none">
            <div class="test-panel-header d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                <span class="fw-semibold"><i class="fas fa-flask me-2 text-primary"></i>Probar flow</span>
                <div class="d-flex gap-2">
                    <button id="btn-test-restart" class="btn btn-sm btn-outline-secondary" title="Reiniciar">
                        <i class="fas fa-rotate-right"></i>
                    </button>
                    <button id="btn-test-close" class="btn btn-sm btn-outline-secondary" title="Cerrar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div id="test-messages" class="test-messages"></div>
            <div class="test-input-bar border-top p-2">
                <div class="input-group input-group-sm">
                    <input type="text" id="test-input" class="form-control" placeholder="Escribe un mensaje…" autocomplete="off" disabled>
                    <button id="btn-test-send" class="btn btn-test-send" disabled>
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
            <input type="file" id="test-file-input" class="d-none" accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.webp">
        </div>
    </div>

</div>

@endsection

@push('css')
<style>
.editor-full-height { height: calc(100vh - 70px); }

/* Test button in the React top bar is handled by React inline styles */
.btn-test-flow { background: #fff; color: #90bb13; border: 1.5px solid #90bb13; font-weight: 500; }
.btn-test-flow:hover { background: #90bb13; color: #fff; }

/* Test panel */
.test-panel {
    width: 340px;
    min-width: 340px;
    border-left: 1px solid #dee2e6;
    background: #fff;
    display: flex;
    flex-direction: column;
}
.test-panel-header { background: #f5f6f8; flex-shrink: 0; }

/* Chat messages area */
.test-messages {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    background: #f4f6fb;
}

/* Bot bubble */
.tchat-bot {
    display: flex;
    align-items: flex-end;
    gap: 6px;
}
.tchat-bot .tchat-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #90bb13;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    flex-shrink: 0;
}
.tchat-bot .tchat-bubble {
    background: #fff;
    border: 1px solid #e0e4ee;
    border-radius: 0 12px 12px 12px;
    padding: 8px 12px;
    max-width: 260px;
    font-size: .86rem;
    line-height: 1.45;
    color: #212529;
    box-shadow: 0 1px 2px rgba(0,0,0,.05);
}
.tchat-bot .tchat-bubble.tchat-system {
    background: #f0f3ff;
    color: #5b7a0c;
    font-size: .78rem;
    border-color: #d0d8f5;
}

/* User bubble */
.tchat-user {
    display: flex;
    justify-content: flex-end;
}
.tchat-user .tchat-bubble {
    background: #90bb13;
    color: #fff;
    border-radius: 12px 12px 0 12px;
    padding: 8px 12px;
    max-width: 240px;
    font-size: .86rem;
    line-height: 1.45;
}

/* Quick reply chips */
.tchat-qr {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    padding-left: 34px;
}
.tchat-qr-btn {
    background: #fff;
    border: 1.5px solid #90bb13;
    color: #90bb13;
    border-radius: 20px;
    padding: 4px 14px;
    font-size: .82rem;
    cursor: pointer;
    transition: background .15s, color .15s;
    white-space: nowrap;
}
.tchat-qr-btn:hover { background: #90bb13; color: #fff; }
.tchat-qr-btn:disabled { opacity: .5; pointer-events: none; }

/* Loading dots */
.tchat-typing .dot { display: inline-block; width: 6px; height: 6px; background: #adb5bd; border-radius: 50%; margin: 0 2px; animation: bounce .9s infinite; }
.tchat-typing .dot:nth-child(2) { animation-delay: .15s; }
.tchat-typing .dot:nth-child(3) { animation-delay: .3s; }
@keyframes bounce { 0%,80%,100%{transform:translateY(0)} 40%{transform:translateY(-5px)} }

/* Send button */
.btn-test-send { background: #90bb13; color: #fff; border-color: #90bb13; border-radius: 0 4px 4px 0; }
.btn-test-send:hover { background: #7aa00f; border-color: #7aa00f; color: #fff; }
.btn-test-send:disabled { opacity: .5; }

/* Input bar */
.test-input-bar { flex-shrink: 0; background: #fff; }
</style>
@endpush

@push('scripts')
{{-- ChatFlow React editor --}}
@vite('modules/HelpdeskChatFlow/resources/js/chatflow-editor.tsx')

<script>
$(document).ready(function () {

    @if(session('success'))
        toastr.success(@json(session('success')));
    @endif
    @if(session('error'))
        toastr.error(@json(session('error')));
    @endif

    // ── Test / Preview panel ───────────────────────────────────────────────────
    const TEST_START_URL  = @json(route('chatflow.test.start', $chatFlow));
    const TEST_SEND_URL   = @json(route('chatflow.test.send'));
    const TEST_UPLOAD_URL = @json(route('chatflow.test.upload', $chatFlow));
    const CSRF_TOKEN      = $('meta[name="csrf-token"]').attr('content');

    let testSessionKey   = null;
    let qrEnabled        = true;
    let pendingDocChips  = [];
    let pendingQrOptions = [];

    function escHtml(s) { return $('<div>').text(String(s ?? '')).html(); }

    function getNodes() {
        return window.__chatflowNodes || [];
    }

    function testPanelOpen() {
        $('#test-panel').removeClass('d-none');
        testStart();
    }

    function testPanelClose() {
        $('#test-panel').addClass('d-none');
        testSessionKey = null;
    }

    function testStart() {
        testSessionKey   = null;
        qrEnabled        = true;
        pendingDocChips  = [];
        pendingQrOptions = [];
        $('#test-messages').empty();
        $('#test-input').val('').prop('disabled', true);
        $('#btn-test-send').prop('disabled', true);

        appendTyping();

        $.ajax({
            url:    TEST_START_URL,
            method: 'POST',
            data:   { nodes: JSON.stringify(getNodes()) },
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            success: function (res) {
                removeTyping();
                if (! res.success) {
                    appendSystemMsg(res.message || 'Error al iniciar el flow.');
                    return;
                }
                testSessionKey = res.data.session_key;
                renderMessages(res.data.messages);
                if (res.data.status === 'active') {
                    enableInput();
                } else {
                    appendStatusMsg(res.data.status);
                }
            },
            error: function (xhr) {
                removeTyping();
                appendSystemMsg(xhr.responseJSON?.message || 'Error al conectar.');
            },
        });
    }

    function testSend(message) {
        if (! testSessionKey || ! message.trim()) { return; }

        const numInput = parseInt(message.trim(), 10);
        if (pendingQrOptions.length > 0 && numInput >= 1 && numInput <= pendingQrOptions.length) {
            testSend(pendingQrOptions[numInput - 1]);
            return;
        }

        if (pendingDocChips.length > 0 && numInput >= 1 && numInput <= pendingDocChips.length) {
            const chip = pendingDocChips[numInput - 1];
            pendingDocKey = chip.key;
            appendUserMsg(message.trim());
            appendBotMsg(`Selecciona el archivo para <strong>${escHtml(chip.label.replace('📎 ', ''))}</strong>:`);
            $('#test-file-input').val('').trigger('click');
            return;
        }

        disableInput();
        appendUserMsg(message);
        $('.tchat-qr-btn').prop('disabled', true);
        appendTyping();

        $.ajax({
            url:    TEST_SEND_URL,
            method: 'POST',
            data:   { session_key: testSessionKey, message: message.trim() },
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            success: function (res) {
                removeTyping();
                if (! res.success) {
                    appendSystemMsg(res.message || 'Error.');
                    enableInput();
                    return;
                }
                renderMessages(res.data.messages);
                if (res.data.status === 'active') {
                    enableInput();
                } else {
                    appendStatusMsg(res.data.status);
                }
            },
            error: function (xhr) {
                removeTyping();
                appendSystemMsg(xhr.responseJSON?.message || 'Error al enviar.');
                enableInput();
            },
        });
    }

    // ── Message renderers ──────────────────────────────────────────────────

    function renderMessages(messages) {
        if (! messages || ! messages.length) { return; }
        let hasDocChips  = false;
        let hasQrOptions = false;
        pendingQrOptions = [];
        messages.forEach(function (msg) {
            if (msg.type === 'bot') {
                appendBotMsg(msg.text, msg.system || false);
            } else if (msg.type === 'quick_replies') {
                hasQrOptions = true;
                pendingDocChips = [];
                appendQuickReplies(msg.options || []);
            } else if (msg.type === 'doc_upload_chips') {
                hasDocChips = true;
                appendDocUploadChips(msg.chips || []);
            }
        });
        if (! hasDocChips)  { pendingDocChips  = []; }
        if (! hasQrOptions) { pendingQrOptions = []; }
        scrollToBottom();
    }

    function appendBotMsg(text, isSystem) {
        const systemClass = isSystem ? ' tchat-system' : '';
        $('#test-messages').append(`
            <div class="tchat-bot">
                <div class="tchat-avatar"><i class="fas fa-robot"></i></div>
                <div class="tchat-bubble${systemClass}">${text}</div>
            </div>`);
    }

    function appendUserMsg(text) {
        $('#test-messages').append(`<div class="tchat-user"><div class="tchat-bubble">${escHtml(text)}</div></div>`);
    }

    function appendQuickReplies(options) {
        pendingQrOptions = options;
        const numbered = options.map((o, i) => `<div>${i + 1}. ${escHtml(o)}</div>`).join('');
        appendBotMsg(`${numbered}<small class="text-muted d-block mt-1">Escribe el número de tu opción.</small>`);
    }

    let pendingDocKey = null;

    function appendDocUploadChips(chips) {
        pendingDocChips = chips;
        const numbered = chips.map((c, i) => `<div>${i + 1}. ${escHtml(c.label.replace('📎 ', ''))}</div>`).join('');
        appendBotMsg(`${numbered}<small class="text-muted d-block mt-1">Escribe el número y adjunta el archivo.</small>`);
    }

    function testUpload(docKey, file) {
        if (! testSessionKey || ! file) { return; }

        disableInput();
        appendUserMsg('📎 ' + file.name);
        appendTyping();

        const formData = new FormData;
        formData.append('session_key', testSessionKey);
        formData.append('doc_key', docKey);
        formData.append('file', file);
        formData.append('_token', CSRF_TOKEN);

        $.ajax({
            url:         TEST_UPLOAD_URL,
            method:      'POST',
            data:        formData,
            processData: false,
            contentType: false,
            success: function (res) {
                removeTyping();
                if (! res.success) {
                    appendSystemMsg(res.message || 'Error al subir archivo.');
                    enableInput();
                    return;
                }
                renderMessages(res.data.messages);
                if (res.data.status === 'active') {
                    enableInput();
                } else {
                    appendStatusMsg(res.data.status);
                }
            },
            error: function (xhr) {
                removeTyping();
                const msg = xhr.responseJSON?.errors?.file?.[0] || xhr.responseJSON?.message || 'Error al subir archivo.';
                appendSystemMsg(msg);
                enableInput();
            },
        });
    }

    function appendTyping() {
        $('#test-messages').append(`<div class="tchat-bot tchat-typing-wrap">
            <div class="tchat-avatar"><i class="fas fa-robot"></i></div>
            <div class="tchat-bubble"><span class="tchat-typing"><span class="dot"></span><span class="dot"></span><span class="dot"></span></span></div>
        </div>`);
        scrollToBottom();
    }

    function removeTyping() { $('.tchat-typing-wrap').remove(); }

    function appendSystemMsg(text) { appendBotMsg(text, true); scrollToBottom(); }

    function appendStatusMsg(status) {
        const labels = {
            completed:   '✅ Flow completado',
            transferred: '🔀 Transferido a agente',
            abandoned:   '⚠️ Flow abandonado',
            failed:      '❌ Flow con error',
        };
        appendBotMsg(labels[status] || `[${status}]`, true);
        scrollToBottom();
    }

    function enableInput() {
        $('#test-input').prop('disabled', false).focus();
        $('#btn-test-send').prop('disabled', false);
    }

    function disableInput() {
        $('#test-input').prop('disabled', true);
        $('#btn-test-send').prop('disabled', true);
    }

    function scrollToBottom() {
        const $el = $('#test-messages');
        $el.scrollTop($el[0].scrollHeight);
    }

    // ── "Probar flow" button — injected by React into top bar via custom event ──
    // React exposes window.__chatflowOpenTestPanel for the test button
    window.__chatflowOpenTestPanel = testPanelOpen;

    // Fallback: also listen to a click on any element with id btn-test-flow
    $(document).on('click', '#btn-test-flow', testPanelOpen);

    $('#btn-test-close').on('click', testPanelClose);
    $('#btn-test-restart').on('click', testStart);

    $('#btn-test-send').on('click', function () {
        const msg = $('#test-input').val().trim();
        if (msg) { $('#test-input').val(''); testSend(msg); }
    });

    $('#test-input').on('keydown', function (e) {
        if (e.key === 'Enter' && ! e.shiftKey) {
            e.preventDefault();
            const msg = $(this).val().trim();
            if (msg) { $(this).val(''); testSend(msg); }
        }
    });

    $(document).on('click', '.tchat-qr-btn:not(.tchat-doc-chip)', function () {
        testSend($(this).data('label'));
    });

    $(document).on('click', '.tchat-doc-chip', function () {
        pendingDocKey = $(this).data('doc-key');
        $('#test-file-input').val('').trigger('click');
    });

    $('#test-file-input').on('change', function () {
        const file = this.files[0];
        if (file && pendingDocKey) {
            testUpload(pendingDocKey, file);
            pendingDocKey = null;
        }
    });
});
</script>
@endpush
