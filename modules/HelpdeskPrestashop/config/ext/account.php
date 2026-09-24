<?php

/*
 | Extensión "account": la cuenta del cliente en PrestaShop desde el chat
 | (ficha editable, grupo y descuento, acceso a la cuenta, RGPD). Acciones del
 | puente en alsernetbridge/helpers/ext/account.php.
 */

return [
    'write_actions' => [
        'account.update',
        'account.set_group',
        'account.password_reset',
    ],

    'permissions' => [
        'helpdeskprestashop.account.update' => 'Editar la ficha del cliente en PrestaShop (nombre, teléfono, idioma, suscripciones)',
        // Cambiar de grupo altera precios, impuestos y plazos de pago: permiso
        // aparte y reservado a supervisión.
        'helpdeskprestashop.account.group' => 'Cambiar el grupo de cliente en PrestaShop',
        'helpdeskprestashop.account.password_reset' => 'Enviar al cliente el correo de restablecer contraseña de la tienda',
        // Descarga TODOS los datos personales del cliente: no es una escritura
        // en la tienda, pero sí la acción más sensible de lectura.
        'helpdeskprestashop.account.gdpr_export' => 'Exportar los datos personales del cliente (RGPD)',
        'helpdeskprestashop.account.gdpr_request' => 'Registrar una solicitud de borrado de cuenta (RGPD) para que la confirme un responsable',
    ],

    // helpdesk-agent-restricted no aparece: nunca escribe en la tienda.
    'role_permissions' => [
        'helpdesk-agent' => [
            'helpdeskprestashop.account.update',
            'helpdeskprestashop.account.password_reset',
            'helpdeskprestashop.account.gdpr_request',
        ],
        'helpdesk-supervisor' => [
            'helpdeskprestashop.account.update',
            'helpdeskprestashop.account.group',
            'helpdeskprestashop.account.password_reset',
            'helpdeskprestashop.account.gdpr_export',
            'helpdeskprestashop.account.gdpr_request',
        ],
        'helpdesk-manager' => [
            'helpdeskprestashop.account.update',
            'helpdeskprestashop.account.group',
            'helpdeskprestashop.account.password_reset',
            'helpdeskprestashop.account.gdpr_export',
            'helpdeskprestashop.account.gdpr_request',
        ],
        'helpdesk-admin' => [
            'helpdeskprestashop.account.update',
            'helpdeskprestashop.account.group',
            'helpdeskprestashop.account.password_reset',
            'helpdeskprestashop.account.gdpr_export',
            'helpdeskprestashop.account.gdpr_request',
        ],
    ],

    'listeners' => [],

    // Una solicitud de borrado por cliente en esta ventana: la segunda solo
    // informa de que ya hay una pendiente (evita notas duplicadas).
    'erasure_request_window_hours' => 72,
];
