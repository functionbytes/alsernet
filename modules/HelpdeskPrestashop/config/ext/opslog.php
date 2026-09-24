<?php

use Modules\HelpdeskPrestashop\Events\PsBackInStock;
use Modules\HelpdeskPrestashop\Events\PsCartAbandoned;
use Modules\HelpdeskPrestashop\Events\PsCartUpdated;
use Modules\HelpdeskPrestashop\Events\PsCustomerCreated;
use Modules\HelpdeskPrestashop\Events\PsCustomerUpdated;
use Modules\HelpdeskPrestashop\Events\PsOrderCreated;
use Modules\HelpdeskPrestashop\Events\PsOrderReturned;
use Modules\HelpdeskPrestashop\Events\PsOrderStatusChanged;
use Modules\HelpdeskPrestashop\Events\PsPriceDropped;
use Modules\HelpdeskPrestashop\Listeners\Ext\OpslogRecordReceivedEvent;

/*
 | Extensión "opslog": pantallas de operación de la integración (piezas 37
 | "Registro del puente" y 38 "Eventos recibidos" del documento "PrestaShop
 | en el chat"). Son de administración, no del chat.
 */
return [
    // Única escritura en la tienda: devolver a la cola los webhooks muertos.
    'write_actions' => ['opslog.requeue_dead'],

    'permissions' => [
        'helpdeskprestashop.ops.view' => 'Ver el registro del puente y los eventos recibidos de PrestaShop',
        'helpdeskprestashop.ops.webhooks.retry' => 'Reintentar en PrestaShop los webhooks que agotaron sus intentos',
        'helpdeskprestashop.ops.cache.warm' => 'Lanzar el calentado de la caché de PrestaShop',
        'helpdeskprestashop.ops.events.reprocess' => 'Reprocesar eventos de PrestaShop que quedaron sin cliente vinculado',
    ],

    // Solo responsables y administradores del helpdesk: son pantallas de
    // operación, no de atención. Agentes y supervisores no las ven.
    'role_permissions' => [
        'helpdesk-manager' => [
            'helpdeskprestashop.ops.view',
            'helpdeskprestashop.ops.webhooks.retry',
            'helpdeskprestashop.ops.cache.warm',
            'helpdeskprestashop.ops.events.reprocess',
        ],
        'helpdesk-admin' => [
            'helpdeskprestashop.ops.view',
            'helpdeskprestashop.ops.webhooks.retry',
            'helpdeskprestashop.ops.cache.warm',
            'helpdeskprestashop.ops.events.reprocess',
        ],
    ],

    // PsEventReceiverController no guarda nada: este listener deja constancia
    // de cada evento que llega para poder verlo y reprocesarlo.
    'listeners' => [
        PsOrderCreated::class => [OpslogRecordReceivedEvent::class],
        PsOrderStatusChanged::class => [OpslogRecordReceivedEvent::class],
        PsOrderReturned::class => [OpslogRecordReceivedEvent::class],
        PsCartAbandoned::class => [OpslogRecordReceivedEvent::class],
        PsCartUpdated::class => [OpslogRecordReceivedEvent::class],
        PsCustomerCreated::class => [OpslogRecordReceivedEvent::class],
        PsCustomerUpdated::class => [OpslogRecordReceivedEvent::class],
        PsPriceDropped::class => [OpslogRecordReceivedEvent::class],
        PsBackInStock::class => [OpslogRecordReceivedEvent::class],
    ],

    'bridge_log' => [
        // Filas de la tabla de llamadas (el puente acepta hasta 200).
        'rows' => (int) env('HELPDESK_PS_OPSLOG_ROWS', 60),
        // Webhooks muertos que se devuelven a la cola por pulsación.
        'requeue_batch' => 50,
        // Un calentado cada tantos segundos como mucho: el comando encola
        // un job por cada 50 emails y no tiene sentido apilarlos.
        'warm_cooldown' => 300,
        'warm_limit' => 200,
    ],

    'events' => [
        // cart.updated llega en cada guardado de carrito: la tabla se poda
        // sola para no crecer sin límite.
        'retention_days' => (int) env('HELPDESK_PS_OPSLOG_RETENTION_DAYS', 30),
        // Eventos que no se guardan (p. ej. ['cart.updated'] si el volumen molesta).
        'ignore' => [],
        'per_page' => 30,
        // "Reprocesar" solo repite eventos de hasta estas horas: más viejos se
        // vinculan al cliente pero no se repiten (un cart.abandoned de hace
        // semanas lanzaría hoy un flujo saliente al cliente). 0 = sin límite.
        'replay_max_age_hours' => 48,
    ],
];
