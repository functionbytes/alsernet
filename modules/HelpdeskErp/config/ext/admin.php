<?php

/*
 | Extensión "admin": infraestructura de extensiones de HelpdeskErp,
 | pantalla «Ajustes de Gestión» y «Métricas de Gestión».
 |
 | Cada config/ext/<nombre>.php del módulo se fusiona en
 | config('helpdeskErp.ext.<nombre>') (ErpChatExtServiceProvider) y puede
 | declarar:
 |   'permissions'      => [nombre => descripción]   (los crea el seeder y
 |                                                    los da a los roles admin)
 |   'role_permissions' => [rol => [permisos]]
 |   'listeners'        => [Evento::class => [Listener::class, …]]
 |   + cualquier ajuste propio.
 */
return [
    'permissions' => [
        'helpdeskerp.settings.manage' => 'Editar los ajustes de Gestión (ERP) en el chat: cachés, avisos, vinculación y seguimiento',
        'helpdeskerp.metrics.view' => 'Ver las métricas de uso del panel de Gestión (ERP) en el chat',
    ],

    'role_permissions' => [
        'helpdesk-supervisor' => ['helpdeskerp.metrics.view'],
        'helpdesk-manager' => ['helpdeskerp.settings.manage', 'helpdeskerp.metrics.view'],
        'helpdesk-admin' => ['helpdeskerp.settings.manage', 'helpdeskerp.metrics.view'],
    ],

    'listeners' => [],

    /*
     | Avisos del resumen de Gestión (overview). true = se muestra. Los
     | valores en vigor viven en config('helpdeskErp.chat_alerts'); estos son
     | los de serie.
     |   risk     → risk_exceeded
     |   debt     → pending_debt
     |   inactive → inactive (baja)
     |   lopd     → no_commercial_consent
     |   expiry   → voucher_expiring, bonus_expiring
     |   served   → order_served (servido hoy/ayer)
     */
    'chat_alerts' => [
        'risk' => true,
        'debt' => true,
        'inactive' => true,
        'lopd' => true,
        'expiry' => true,
        'served' => true,
    ],

    /*
     | Vinculación automática con Gestión al entrar una conversación o un
     | ticket (config('helpdeskErp.auto_link')). El reintento manual del
     | agente (relink) no depende de esto.
     */
    'auto_link' => true,

    'metrics' => [
        // Registrar el uso del panel (aperturas, secciones, tiempos del manager).
        'enabled' => true,
        // Días que se guardan los eventos; los más antiguos los borra
        // helpdeskerp:purge-metrics (programado a diario).
        'retention_days' => 90,
        // Periodos del filtro de la pantalla (días hacia atrás). El segundo
        // es el de por defecto.
        'periods' => [7, 30, 90],
        // Tope de filas que se leen para calcular percentiles.
        'percentile_sample' => 50000,
    ],

    // Segundos que se guarda en caché la lectura de helpdesk_erp_settings
    // (se invalida al guardar, así que puede ser largo).
    'cache_ttl' => 3600,
];
