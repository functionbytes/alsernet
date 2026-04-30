(function ($) {
    'use strict';

    var activeConvId = null;
    var connectionWarningShown = false;
    var reverbWaitRetries = 0;
    var maxReverbWaitRetries = 100; // 5 seconds at 50ms intervals
    var renderedMessageIds = new Set(); // OPTIMIZATION: O(1) deduplication instead of O(n) querySelector

    /**
     * Suscribe al canal privado de la conversación vía ReverbManager.
     * Abandona el canal anterior antes de suscribirse al nuevo para evitar
     * suscripciones obsoletas al cambiar de conversación.
     *
     * @param {string|number} convId
     */
    function initEchoSubscription(convId) {
        // Wait for ReverbManager to be available
        if (!window.ReverbManager) {
            if (reverbWaitRetries < maxReverbWaitRetries) {
                reverbWaitRetries++;
                console.log('[ChatRealtime] Waiting for ReverbManager... (retry ' + reverbWaitRetries + ')');
                setTimeout(function() {
                    initEchoSubscription(convId);
                }, 50);
                return;
            }

            console.error('[ChatRealtime] ReverbManager not available after retries — real-time disabled');
            if (!connectionWarningShown && window.toastr) {
                toastr.warning('Mensajes en tiempo real desactivados. Recarga la página para actualizar.', 'Conexión limitada', {
                    timeOut: 5000,
                    extendedTimeOut: 2000
                });
                connectionWarningShown = true;
            }
            return;
        }

        // Leave previous conversation channel before subscribing to new one
        if (activeConvId && activeConvId !== convId) {
            window.ReverbManager.unsubscribe('conversation.' + activeConvId);
            renderedMessageIds.clear(); // Clear Set on conversation switch
        }

        activeConvId = convId;

        try {
            window.ReverbManager.subscribe('private', 'conversation.' + convId, {
                'message.sent': function (event) {
                    var receiveTime = new Date().getTime();
                    console.log('[ChatRealtime] message.sent received:', event);
                    console.log('[TIMING] Frontend received event at:', receiveTime, 'message_id:', event.id);

                    // OPTIMIZED: O(1) Set lookup instead of O(n) DOM query
                    if (event.id && renderedMessageIds.has(event.id)) {
                        console.log('[TIMING] Message already rendered, skipping (Set check)');
                        return;
                    }

                    if (event.id) {
                        renderedMessageIds.add(event.id);
                    }

                    window.ChatUtils && window.ChatUtils.appendMessageToChat(event, convId);

                    var renderTime = new Date().getTime();
                    console.log('[TIMING] Message rendered at:', renderTime, 'delay_ms:', (renderTime - receiveTime));
                },
                'conversation.updated': function (event) {
                    console.log('[ChatRealtime] conversation.updated received:', event);
                    if (window.ChatActions && window.ChatActions.loadConversations) {
                        window.ChatActions.loadConversations();
                    }
                },
            });
            connectionWarningShown = false; // Reset warning if subscription succeeds
            console.log('[ChatRealtime] Successfully subscribed to conversation channel: ' + convId);
        } catch (error) {
            console.error('[ChatRealtime] Error subscribing to conversation channel:', error);
            if (window.toastr) {
                toastr.error('Error al conectar con el servidor de mensajes en tiempo real.', 'Error de conexión');
            }
        }
    }

    // Public API
    window.ChatRealtime = {
        initEchoSubscription: initEchoSubscription,
    };

})(jQuery);
