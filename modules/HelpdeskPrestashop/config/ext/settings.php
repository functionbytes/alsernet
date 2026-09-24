<?php

/*
 | Extensión "settings": pantalla «Ajustes del chat» (Ajustes → Helpdesk ·
 | PrestaShop). Guarda en helpdesk_ps_settings los ajustes que antes solo se
 | cambiaban en config/.env con un despliegue, y SettingsOverrides::apply()
 | los aplica sobre config() en el boot del provider:
 |
 |   vouchers.*                 → config('helpdeskprestashop.vouchers.*')
 |   refunds.*                  → config('helpdeskprestashop.ext.refunds.*')
 |   quick_replies              → config('helpdeskprestashop.ext.settings.quick_replies')
 |
 | Solo se guardan las claves que difieren del valor por defecto: lo que no
 | se toca en la pantalla sigue viniendo de config/.env. No escribe en la
 | tienda, así que no hay write_actions.
 */
return [
    'write_actions' => [],

    'permissions' => [
        'helpdeskprestashop.settings.manage' => 'Editar los ajustes del chat de PrestaShop (límites de vales y reembolsos, instrucciones de retorno, respuestas rápidas)',
    ],

    // Configuración de negocio (límites de dinero): solo responsables y
    // administradores del helpdesk.
    'role_permissions' => [
        'helpdesk-manager' => ['helpdeskprestashop.settings.manage'],
        'helpdesk-admin' => ['helpdeskprestashop.settings.manage'],
    ],

    'listeners' => [],

    /*
     | Respuestas rápidas con datos reales del panel derecho del inbox. Estas
     | son las de serie (las mismas que pintaba right-panel-prestashop-tabs.js);
     | la pantalla de ajustes las sustituye. Variables admitidas: ver
     | SettingsService::REPLY_VARIABLES. Si una variable no tiene dato para
     | el cliente abierto, esa plantilla no se ofrece.
     */
    'quick_replies' => [
        [
            't' => 'Estado del pedido',
            's' => '“Tu pedido #{pedido} está {estado}…”',
            'text' => 'Tu pedido #{pedido} está en estado «{estado}».',
        ],
        [
            't' => 'Seguimiento del envío',
            's' => '{transportista} · {seguimiento}',
            'text' => 'Tu pedido #{pedido} lo lleva {transportista} con el número de seguimiento {seguimiento}: {enlace_seguimiento}',
        ],
        [
            't' => 'Instrucciones de devolución',
            's' => 'Incluye {rma} y el pedido',
            'text' => 'Hemos registrado tu devolución {rma}. Prepara el paquete con los artículos y el número de devolución visible; te avisaremos en cuanto lo recibamos.',
        ],
        [
            't' => 'Confirmación de reembolso',
            's' => 'Importe y plazo del banco',
            'text' => 'Te confirmamos el reembolso de {importe_reembolso}. Según tu banco puede tardar de 3 a 5 días hábiles en verse en tu cuenta.',
        ],
    ],

    // Segundos que se guarda en caché la lectura de la tabla (se invalida
    // al guardar desde la pantalla, así que puede ser largo).
    'cache_ttl' => 3600,
];
