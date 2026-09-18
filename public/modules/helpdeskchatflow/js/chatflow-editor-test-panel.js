/**
 * chatflow-editor-test-panel.js — HelpdeskChatFlow module
 *
 * Drives the "Probar flow" side panel on the flow editor page: starts a test
 * session, sends customer messages, renders bot replies/quick
 * replies/document-upload chips, and uploads test files.
 *
 * The panel is opened by the React canvas (ChatFlowEditor.tsx) via
 * window.__chatflowOpenTestPanel, and reads the current (unsaved) node list
 * from window.__chatflowNodes so tests always run against what's on screen.
 *
 * Reads endpoint URLs and session flash messages from
 * window.HelpdeskChatFlowEditor, emitted by editor.blade.php.
 */
(function ($) {
    'use strict';

    var config = window.HelpdeskChatFlowEditor || {};
    var csrf = $('meta[name="csrf-token"]').attr('content');

    var testSessionKey = null;
    var pendingDocChips = [];
    var pendingQrOptions = [];
    var pendingDocKey = null;

    function escHtml(value) {
        return $('<div>').text(String(value ?? '')).html();
    }

    function getNodes() {
        return window.__chatflowNodes || [];
    }

    function scrollToBottom() {
        var $el = $('#test-messages');
        $el.scrollTop($el[0].scrollHeight);
    }

    function appendTyping() {
        $('#test-messages').append(
            '<div class="tchat-bot tchat-typing-wrap">'
            + '<div class="tchat-avatar"><i class="fas fa-robot"></i></div>'
            + '<div class="tchat-bubble"><span class="tchat-typing"><span class="dot"></span><span class="dot"></span><span class="dot"></span></span></div>'
            + '</div>'
        );
        scrollToBottom();
    }

    function removeTyping() {
        $('.tchat-typing-wrap').remove();
    }

    function appendBotMsg(text, isSystem) {
        var systemClass = isSystem ? ' tchat-system' : '';
        $('#test-messages').append(
            '<div class="tchat-bot">'
            + '<div class="tchat-avatar"><i class="fas fa-robot"></i></div>'
            + '<div class="tchat-bubble' + systemClass + '">' + text + '</div>'
            + '</div>'
        );
    }

    function appendUserMsg(text) {
        $('#test-messages').append('<div class="tchat-user"><div class="tchat-bubble">' + escHtml(text) + '</div></div>');
    }

    function appendSystemMsg(text) {
        appendBotMsg(text, true);
        scrollToBottom();
    }

    function appendStatusMsg(status) {
        var labels = {
            completed: '✅ Flow completado',
            transferred: '🔀 Transferido a agente',
            abandoned: '⚠️ Flow abandonado',
            failed: '❌ Flow con error',
        };
        appendBotMsg(labels[status] || '[' + status + ']', true);
        scrollToBottom();
    }

    function appendQuickReplies(options) {
        pendingQrOptions = options;
        var numbered = options.map(function (o, i) { return '<div>' + (i + 1) + '. ' + escHtml(o) + '</div>'; }).join('');
        appendBotMsg(numbered + '<small class="text-muted d-block mt-1">Escribe el número de tu opción.</small>');
    }

    function appendDocUploadChips(chips) {
        pendingDocChips = chips;
        var numbered = chips.map(function (c, i) {
            return '<div>' + (i + 1) + '. ' + escHtml(c.label.replace('📎 ', '')) + '</div>';
        }).join('');
        appendBotMsg(numbered + '<small class="text-muted d-block mt-1">Escribe el número y adjunta el archivo.</small>');
    }

    function renderMessages(messages) {
        if (!messages || !messages.length) { return; }

        var hasDocChips = false;
        var hasQrOptions = false;
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

        if (!hasDocChips) { pendingDocChips = []; }
        if (!hasQrOptions) { pendingQrOptions = []; }
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

    function testStart() {
        testSessionKey = null;
        pendingDocChips = [];
        pendingQrOptions = [];
        $('#test-messages').empty();
        $('#test-input').val('').prop('disabled', true);
        $('#btn-test-send').prop('disabled', true);

        appendTyping();

        $.ajax({
            url: config.testStartUrl,
            method: 'POST',
            data: { nodes: JSON.stringify(getNodes()) },
            headers: { 'X-CSRF-TOKEN': csrf },
            success: function (res) {
                removeTyping();
                if (!res.success) {
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
        if (!testSessionKey || !message.trim()) { return; }

        var numInput = parseInt(message.trim(), 10);
        if (pendingQrOptions.length > 0 && numInput >= 1 && numInput <= pendingQrOptions.length) {
            testSend(pendingQrOptions[numInput - 1]);
            return;
        }

        if (pendingDocChips.length > 0 && numInput >= 1 && numInput <= pendingDocChips.length) {
            var chip = pendingDocChips[numInput - 1];
            pendingDocKey = chip.key;
            appendUserMsg(message.trim());
            appendBotMsg('Selecciona el archivo para <strong>' + escHtml(chip.label.replace('📎 ', '')) + '</strong>:');
            $('#test-file-input').val('').trigger('click');
            return;
        }

        disableInput();
        appendUserMsg(message);
        $('.tchat-qr-btn').prop('disabled', true);
        appendTyping();

        $.ajax({
            url: config.testSendUrl,
            method: 'POST',
            data: { session_key: testSessionKey, message: message.trim() },
            headers: { 'X-CSRF-TOKEN': csrf },
            success: function (res) {
                removeTyping();
                if (!res.success) {
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

    function testUpload(docKey, file) {
        if (!testSessionKey || !file) { return; }

        disableInput();
        appendUserMsg('📎 ' + file.name);
        appendTyping();

        var formData = new FormData;
        formData.append('session_key', testSessionKey);
        formData.append('doc_key', docKey);
        formData.append('file', file);
        formData.append('_token', csrf);

        $.ajax({
            url: config.testUploadUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                removeTyping();
                if (!res.success) {
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
                var msg = xhr.responseJSON?.errors?.file?.[0] || xhr.responseJSON?.message || 'Error al subir archivo.';
                appendSystemMsg(msg);
                enableInput();
            },
        });
    }

    function testPanelOpen() {
        $('#test-panel').removeClass('d-none');
        testStart();
    }

    function testPanelClose() {
        $('#test-panel').addClass('d-none');
        testSessionKey = null;
    }

    $(function () {
        if (config.successMessage) { toastr.success(config.successMessage); }
        if (config.errorMessage) { toastr.error(config.errorMessage); }

        // React exposes the button click via this global hook.
        window.__chatflowOpenTestPanel = testPanelOpen;

        // Fallback: also listen to a click on any element with id btn-test-flow.
        $(document).on('click', '#btn-test-flow', testPanelOpen);

        $('#btn-test-close').on('click', testPanelClose);
        $('#btn-test-restart').on('click', testStart);

        $('#btn-test-send').on('click', function () {
            var msg = $('#test-input').val().trim();
            if (msg) { $('#test-input').val(''); testSend(msg); }
        });

        $('#test-input').on('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                var msg = $(this).val().trim();
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
            var file = this.files[0];
            if (file && pendingDocKey) {
                testUpload(pendingDocKey, file);
                pendingDocKey = null;
            }
        });
    });
})(jQuery);
