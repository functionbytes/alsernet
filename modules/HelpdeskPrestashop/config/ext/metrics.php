<?php

/*
 | Extensión "metrics" — «Métricas del chat · PrestaShop»: vales, reembolsos,
 | anulaciones, devoluciones, carritos, direcciones y avisos de reposición
 | que los agentes han hecho contra la tienda desde el panel. Solo lee el log
 | de actividad 'helpdeskprestashop' (auditoría de acciones), así que no
 | escribe en la tienda ni tiene write_actions.
 */
return [
    'write_actions' => [],

    'permissions' => [
        'helpdeskprestashop.metrics.view' => 'Ver las métricas de lo que los agentes hacen contra PrestaShop desde el chat',
    ],

    // Supervisión: quien reparte el trabajo y responde de los importes.
    'role_permissions' => [
        'helpdesk-supervisor' => ['helpdeskprestashop.metrics.view'],
        'helpdesk-manager' => ['helpdeskprestashop.metrics.view'],
        'helpdesk-admin' => ['helpdeskprestashop.metrics.view'],
    ],

    'listeners' => [],

    // Periodos del filtro (días hacia atrás desde hoy). El segundo es el de
    // por defecto.
    'periods' => [7, 30, 90],
];
