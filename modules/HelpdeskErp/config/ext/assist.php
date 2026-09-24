<?php

use Modules\HelpdeskErp\Listeners\ErpAssistAiReplyContext;

/*
 * Extensión "assist" de HelpdeskErp: ayudas dentro del chat.
 *
 * - Pista de pedido de Gestión detectado en el mensaje del cliente, variables
 *   {{erp_*}} en respuestas rápidas y bloque "Respuestas rápidas" de la pestaña
 *   Gestión: todo en cliente (public/js/erp-assist.js), con los permisos que ya
 *   existen (helpdeskerp.orders.view / addresses / loyalty).
 * - Contexto ERP para las sugerencias de IA: listener del evento de cadena
 *   'helpdesk.ai.reply-context' (Modules\Helpdesk\Services\AI\SuggestReplyService
 *   ::CONTEXT_EVENT). Solo lee caché y exige helpdeskerp.orders.view.
 *
 * No añade permisos nuevos.
 */
return [
    'permissions' => [],

    'role_permissions' => [],

    'listeners' => [
        'helpdesk.ai.reply-context' => [ErpAssistAiReplyContext::class],
    ],

    // Variables que erp-assist.js sustituye al insertar una respuesta rápida.
    'canned_variables' => [
        '{{erp_ultimo_pedido}}' => 'Nº del último pedido en Gestión',
        '{{erp_estado_ultimo_pedido}}' => 'Estado del último pedido en Gestión',
        '{{erp_fecha_servido}}' => 'Fecha en que se sirvió el último pedido',
        '{{erp_puntos}}' => 'Puntos de fidelización',
        '{{erp_tarjeta}}' => 'Nº de la tarjeta de fidelización',
        '{{erp_direccion_envio}}' => 'Dirección de envío en Gestión',
    ],
];
