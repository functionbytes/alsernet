(function ($) {
    'use strict';

    var sendingByConv = {};

    function autoResize(el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 120) + 'px';
        el.style.overflowY = el.scrollHeight > 120 ? 'auto' : 'hidden';
    }

    function init() {
        // Enter para enviar (sin Shift), Shift+Enter para nueva linea
        $(document).on('keydown', '.message-type-box', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                var convId = $(this).attr('id').replace('message-input-', '');
                sendMessage(convId);
            }
        });

        // Auto-resize al escribir
        $(document).on('input', '.message-type-box', function () {
            autoResize(this);
        });

        // Cambio en file input — validar y renderizar previsualizaciones
        $(document).on('change', '[id^="file-input-"]', function () {
            var convId = $(this).attr('id').replace('file-input-', '');
            handleFileInputChange(this, convId);
        });
    }

    function handleFileInputChange(input, convId) {
        var config    = window.ChatConfig || {};
        var enabled   = config.attachmentEnabled !== false;
        var allowed   = config.allowedTypes      || [];
        var maxSize   = config.maxFileSize        || 10485760;
        var maxCount  = config.maxAttachCount     || 5;

        if (!enabled) {
            window.ChatUtils && window.ChatUtils.showToast('Los adjuntos están desactivados.', 'error');
            input.value = '';
            return;
        }

        var validFiles = new DataTransfer();
        var errors     = [];
        var maxMb      = maxSize / 1024 / 1024;

        $.each(Array.from(input.files), function (i, file) {
            if (allowed.length && allowed.indexOf(file.type) === -1) {
                errors.push('"' + file.name + '" tiene un tipo no permitido.');
            } else if (file.size > maxSize) {
                errors.push('"' + file.name + '" supera el límite de ' + maxMb + ' MB.');
            } else {
                validFiles.items.add(file);
            }
        });

        if (validFiles.files.length > maxCount) {
            errors.push('Solo se permiten hasta ' + maxCount + ' archivos adjuntos.');
            var trimmed = new DataTransfer();
            $.each(Array.from(validFiles.files).slice(0, maxCount), function (i, f) {
                trimmed.items.add(f);
            });
            input.files = trimmed.files;
        } else {
            input.files = validFiles.files;
        }

        if (errors.length) {
            window.ChatUtils && window.ChatUtils.showToast(errors.join(' '), 'error');
        }

        window.ChatViewer && window.ChatViewer.renderPreviews(convId);
    }

    function setSendingState(convId, loading) {
        var input   = $('#message-input-' + convId);
        var sendBtn = $('#send-btn-' + convId);
        var icon    = sendBtn.find('i');

        if (loading) {
            input.prop('disabled', true).addClass('chat-input-sending');
            sendBtn.addClass('disabled').attr('aria-disabled', 'true');
            icon.removeClass('fa-paper-plane').addClass('fa-spinner fa-spin');
        } else {
            input.prop('disabled', false).removeClass('chat-input-sending');
            sendBtn.removeClass('disabled').removeAttr('aria-disabled');
            icon.removeClass('fa-spinner fa-spin').addClass('fa-paper-plane');
        }
    }

    function sendMessage(convId) {
        if (sendingByConv[convId]) { return; }

        var config    = window.ChatConfig || {};
        var enabled   = config.attachmentEnabled !== false;
        var csrfToken = config.csrfToken || $('meta[name="csrf-token"]').attr('content');
        var input     = $('#message-input-' + convId);
        var fileInput = document.getElementById('file-input-' + convId);
        var message   = input.val().trim();
        var hasFiles  = fileInput && fileInput.files.length > 0;

        if (hasFiles && !enabled) {
            window.ChatUtils && window.ChatUtils.showToast('Los adjuntos están desactivados.', 'error');
            return;
        }

        if (!message && !hasFiles) {
            window.ChatUtils && window.ChatUtils.showToast('Escribe un mensaje o adjunta un archivo antes de enviar.', 'error');
            input.trigger('focus');
            return;
        }

        var formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('content', message);
        formData.append('message_type', 'outgoing');

        if (hasFiles) {
            $.each(Array.from(fileInput.files), function (i, file) {
                formData.append('attachments[]', file);
            });
        }

        sendingByConv[convId] = true;
        setSendingState(convId, true);

        $.ajax({
            url: (window.ChatConfig && window.ChatConfig.baseUrl || '/chat/conversations') + '/' + convId + '/messages',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'Accept': 'application/json' },
            success: function (response) {
                input.val('');
                autoResize(input[0]);
                if (fileInput) { fileInput.value = ''; }
                $('#attachment-previews-' + convId).empty().addClass('d-none');
                setSendingState(convId, false);
                sendingByConv[convId] = false;

                if (response && response.message) {
                    window.ChatUtils && window.ChatUtils.appendMessageToChat(response.message, convId);
                }
                input.trigger('focus');
            },
            error: function (xhr) {
                setSendingState(convId, false);
                sendingByConv[convId] = false;
                handleSendError(xhr);
            }
        });
    }

    function handleSendError(xhr) {
        var showToast = window.ChatUtils && window.ChatUtils.showToast;
        if (!showToast) { return; }

        if (xhr.status === 0) {
            showToast('Sin conexión. Comprueba tu red e inténtalo de nuevo.', 'error');
        } else if (xhr.status === 413) {
            showToast('Los archivos adjuntos superan el tamaño máximo permitido.', 'error');
        } else if (xhr.status === 422) {
            var errors      = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : {};
            var firstErrors = Object.values(errors)[0];
            showToast((firstErrors && firstErrors[0]) || 'Datos inválidos. Revisa el mensaje.', 'error');
        } else {
            var serverMsg = xhr.responseJSON && xhr.responseJSON.message;
            showToast(serverMsg || 'Error al enviar el mensaje. Inténtalo de nuevo.', 'error');
        }
    }

    // Exponer para uso externo (botón enviar en blade)
    window.ChatSender = {
        init:        init,
        sendMessage: sendMessage
    };

    $(function () { init(); });

})(jQuery);
