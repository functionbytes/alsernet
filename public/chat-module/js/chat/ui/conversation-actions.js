(function ($) {
    'use strict';

    function init() {
        // Quick action forms con AJAX
        $(document).on('submit', '.quick-action-form', function (e) {
            e.preventDefault();
            quickActionSubmit(this);
        });
    }

    /**
     * Actualiza la conversación vía AJAX (PATCH)
     */
    function updateConversation(url, data, successMessage) {
        var config    = window.ChatConfig || {};
        var csrfToken = config.csrfToken || $('meta[name="csrf-token"]').attr('content');

        data._token = csrfToken;

        return $.ajax({
            url:    url,
            method: 'PATCH',
            data:   data,
            success: function () {
                window.ChatUtils && window.ChatUtils.showToast(successMessage || 'Conversación actualizada correctamente');
                if (window.ChatConversations && config.conversationId) {
                    var $active = $('.inbox-chat-item[data-conversation-id="' + config.conversationId + '"]');
                    setTimeout(function () { window.ChatConversations.loadConversation(config.conversationId, $active); }, 1000);
                } else {
                    setTimeout(function () { location.reload(); }, 1000);
                }
            },
            error: function (xhr) {
                var error = 'Error al actualizar la conversación';

                if (xhr.status === 0) {
                    error = 'Sin conexión. Comprueba tu red e inténtalo de nuevo.';
                } else if (xhr.status === 403) {
                    error = 'No tienes permiso para realizar esta acción';
                } else if (xhr.status === 404) {
                    error = 'Conversación no encontrada';
                } else if (xhr.status === 422) {
                    var errors = xhr.responseJSON && xhr.responseJSON.errors;
                    if (errors) {
                        var firstError = Object.values(errors)[0];
                        error = firstError && firstError[0] ? firstError[0] : error;
                    }
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    error = xhr.responseJSON.message;
                }

                window.ChatUtils && window.ChatUtils.showToast(error, 'error');
                console.error('Error updating conversation:', xhr);
            }
        });
    }

    /**
     * Ejecuta una acción rápida desde un formulario
     */
    function quickActionSubmit(form) {
        var $form  = $(form);
        var config = window.ChatConfig || {};
        $.ajax({
            url:    $form.attr('action'),
            method: 'POST',
            data:   $form.serialize(),
            headers: { 'X-CSRF-TOKEN': config.csrfToken || $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                window.ChatUtils && window.ChatUtils.showToast('Acción ejecutada correctamente');
                if (window.ChatConversations && config.conversationId) {
                    var $active = $('.inbox-chat-item[data-conversation-id="' + config.conversationId + '"]');
                    setTimeout(function () { window.ChatConversations.loadConversation(config.conversationId, $active); }, 800);
                } else {
                    setTimeout(function () { location.reload(); }, 800);
                }
            },
            error: function (xhr) {
                var error = 'Error al ejecutar la acción';

                if (xhr.status === 0) {
                    error = 'Sin conexión. Comprueba tu red e inténtalo de nuevo.';
                } else if (xhr.status === 403) {
                    error = 'No tienes permiso para realizar esta acción';
                } else if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    var firstError = Object.values(xhr.responseJSON.errors)[0];
                    error = firstError && firstError[0] ? firstError[0] : error;
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    error = xhr.responseJSON.message;
                }

                window.ChatUtils && window.ChatUtils.showToast(error, 'error');
                console.error('Error executing quick action:', xhr);
            }
        });
    }

    /**
     * Ejecuta una macro sobre la conversación
     */
    function executeMacro() {
        var config    = window.ChatConfig || {};
        var convId    = config.conversationId;
        var csrfToken = config.csrfToken || $('meta[name="csrf-token"]').attr('content');
        var macroId   = $('#macro-selector').val();

        if (!macroId) {
            window.ChatUtils && window.ChatUtils.showToast('Por favor selecciona una macro', 'error');
            return;
        }

        $.ajax({
            url:    (window.ChatConfig && window.ChatConfig.baseUrl || '/chat/conversations') + '/' + convId + '/macros/' + macroId + '/execute',
            method: 'POST',
            data:   { _token: csrfToken },
            success: function () {
                window.ChatUtils && window.ChatUtils.showToast('Macro ejecutada correctamente');
                if (window.ChatConversations && convId) {
                    var $active = $('.inbox-chat-item[data-conversation-id="' + convId + '"]');
                    setTimeout(function () { window.ChatConversations.loadConversation(convId, $active); }, 1000);
                } else {
                    setTimeout(function () { location.reload(); }, 1000);
                }
            },
            error: function (xhr) {
                var error = 'Error al ejecutar la macro';

                if (xhr.status === 0) {
                    error = 'Sin conexión. Comprueba tu red e inténtalo de nuevo.';
                } else if (xhr.status === 403) {
                    error = 'No tienes permiso para ejecutar macros';
                } else if (xhr.status === 404) {
                    error = 'Macro no encontrada';
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    error = xhr.responseJSON.message;
                }

                window.ChatUtils && window.ChatUtils.showToast(error, 'error');
                console.error('Error executing macro:', xhr);
            }
        });
    }

    /**
     * Inicializa los selects de la barra lateral con Select2
     */
    function initSidebarSelects() {
        if (typeof $.fn.select2 === 'undefined') {
            console.warn('Select2 not loaded, using native selects');
            return;
        }

        var config = window.ChatConfig || {};
        var convId = config.conversationId;

        function formatLabelOption(option) {
            if (!option.id) { return option.text; }
            var color = $(option.element).data('color') || '#6c757d';
            return $('<span><i class="fas fa-circle me-2" style="color: ' + color + '"></i>' + option.text + '</span>');
        }

        function formatLabelSelection(option) {
            if (!option.id) { return option.text; }
            var color = $(option.element).data('color') || '#6c757d';
            return $('<span class="badge me-1" style="background-color: ' + color + '; color: white;">' + option.text + '</span>');
        }

        $('select[name="status"]').select2({
            width: '100%',
            minimumResultsForSearch: Infinity,
            placeholder: 'Seleccionar estado...'
        }).on('select2:select', function () {
            updateConversation(
                (window.ChatConfig && window.ChatConfig.baseUrl || '/chat/conversations') + '/' + convId + '/status',
                { status: $(this).val() },
                'Estado actualizado correctamente'
            );
        });

        $('select[name="team_id"]').select2({
            width: '100%',
            placeholder: 'Seleccionar equipo...',
            allowClear: true
        }).on('select2:select select2:unselect', function () {
            updateConversation(
                (window.ChatConfig && window.ChatConfig.baseUrl || '/chat/conversations') + '/' + convId + '/team',
                { team_id: $(this).val() || '' },
                'Equipo actualizado correctamente'
            );
        });

        $('select[name="assignee_id"]').select2({
            width: '100%',
            placeholder: 'Sin asignar...',
            allowClear: true
        }).on('select2:select select2:unselect', function () {
            updateConversation(
                (window.ChatConfig && window.ChatConfig.baseUrl || '/chat/conversations') + '/' + convId + '/assign',
                { assignee_id: $(this).val() || '' },
                'Asignación actualizada correctamente'
            );
        });

        $('select[name="priority"]').select2({
            width: '100%',
            minimumResultsForSearch: Infinity,
            placeholder: 'Seleccionar prioridad...'
        }).on('select2:select', function () {
            updateConversation(
                (window.ChatConfig && window.ChatConfig.baseUrl || '/chat/conversations') + '/' + convId + '/priority',
                { priority: $(this).val() },
                'Prioridad actualizada correctamente'
            );
        });

        $('select[name="labels[]"]').select2({
            width: '100%',
            placeholder: 'Seleccionar etiquetas...',
            closeOnSelect: false,
            templateResult:    formatLabelOption,
            templateSelection: formatLabelSelection
        }).on('change', function () {
            updateConversation(
                (window.ChatConfig && window.ChatConfig.baseUrl || '/chat/conversations') + '/' + convId + '/labels',
                { labels: $(this).val() || [] },
                'Etiquetas actualizadas correctamente'
            );
        });

        var $macroSelector = $('#macro-selector').select2({
            width: '100%',
            placeholder: 'Seleccionar macro...',
            allowClear: true
        });

        // Auto-execute macro on selection using a polling approach for Select2
        // Store previous value to detect changes
        var prevMacroValue = '';
        setInterval(function () {
            var currentValue = $macroSelector.val();
            // If value changed and is not empty, execute macro
            if (currentValue && currentValue !== '' && currentValue !== prevMacroValue) {
                console.log('[ChatActions] Macro selected:', currentValue);
                executeMacro();
                // Reset value after execution
                setTimeout(function () {
                    console.log('[ChatActions] Resetting macro selector');
                    $macroSelector.val('').trigger('change');
                    prevMacroValue = '';
                }, 1200);
            }
            prevMacroValue = currentValue;
        }, 300);
    }

    /**
     * Inicializa el emoji picker
     */
    function initEmojiPicker() {
        $(document).on('click', '.emoji-picker-toggle', function (e) {
            e.stopPropagation();
            var picker = $(this).siblings('.emoji-picker');
            $('.emoji-picker').not(picker).addClass('d-none');
            picker.toggleClass('d-none');
        });

        $(document).on('click', '.emoji-item', function () {
            var emoji   = $(this).text();
            var picker  = $(this).closest('.emoji-picker');
            var inputId = picker.siblings('.emoji-picker-toggle').data('input');
            var input   = document.getElementById(inputId);

            if (!input) { return; }

            var start = input.selectionStart !== undefined ? input.selectionStart : input.value.length;
            var end   = input.selectionEnd   !== undefined ? input.selectionEnd   : input.value.length;
            input.value = input.value.slice(0, start) + emoji + input.value.slice(end);
            input.selectionStart = input.selectionEnd = start + emoji.length;
            input.focus();
            picker.addClass('d-none');
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('.emoji-picker, .emoji-picker-toggle').length) {
                $('.emoji-picker').addClass('d-none');
            }
        });

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') { $('.emoji-picker').addClass('d-none'); }
        });
    }

    // Exponer API pública
    window.ChatActions = {
        init:               init,
        updateConversation: updateConversation,
        executeMacro:       executeMacro,
        initSidebarSelects: initSidebarSelects,
        initEmojiPicker:    initEmojiPicker
    };

    $(function () { init(); });

})(jQuery);
