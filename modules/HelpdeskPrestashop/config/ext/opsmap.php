<?php

use Illuminate\Foundation\Http\Events\RequestHandled;
use Modules\HelpdeskPrestashop\Events\PsOrderStatusChanged;
use Modules\HelpdeskPrestashop\Listeners\Ext\OpsmapApplyOrderStateMapping;
use Modules\HelpdeskPrestashop\Listeners\Ext\OpsmapAuditStoreWrite;
use Modules\HelpdeskPrestashop\Listeners\Ext\OpsmapMarkAuditedRequest;

/*
 | Extensión "opsmap" — piezas 39 (Mapeo de estados) y 40 (Auditoría de
 | acciones) del documento "Alvarez PrestaShop en el Chat".
 |
 | Ninguna de las dos escribe en la tienda: el mapeo cambia estados del
 | helpdesk cuando PrestaShop avisa de un cambio de estado de pedido, y la
 | auditoría solo registra lo que ya hicieron otras rutas. Por eso no hay
 | write_actions.
 */
return [
    'write_actions' => [],

    'permissions' => [
        'helpdeskprestashop.statemap.manage' => 'Configurar qué pasa en el helpdesk cuando un pedido cambia de estado en PrestaShop',
        // Mismo nombre y descripción que declara la extensión "opslog": un
        // único permiso para las pantallas de operación de la integración.
        'helpdeskprestashop.ops.view' => 'Ver el registro del puente y los eventos recibidos de PrestaShop',
    ],

    // Mismo criterio que opslog: pantallas de operación y configuración,
    // solo para responsables y administradores del helpdesk.
    'role_permissions' => [
        'helpdesk-manager' => ['helpdeskprestashop.ops.view', 'helpdeskprestashop.statemap.manage'],
        'helpdesk-admin' => ['helpdeskprestashop.ops.view', 'helpdeskprestashop.statemap.manage'],
    ],

    'listeners' => [
        PsOrderStatusChanged::class => [OpsmapApplyOrderStateMapping::class],
        RequestHandled::class => [OpsmapAuditStoreWrite::class],
        // Cualquier controlador que ya registre su propia actividad
        // (p. ej. el vale de compensación) marca la petición para que la
        // auditoría genérica no la duplique.
        'eloquent.created: Spatie\Activitylog\Models\Activity' => [OpsmapMarkAuditedRequest::class],
    ],

    /*
     | Mapeo de estados: solo se toca la conversación más reciente del
     | cliente con actividad en esta ventana. Un pedido que cambia de estado
     | meses después no debe cerrar ni reabrir una conversación olvidada.
     */
    'window_days' => (int) env('HELPDESK_PS_STATE_MAP_WINDOW_DAYS', 30),

    /*
     | Auditoría: rutas que se registran (patrones de Str::is sobre el
     | nombre de la ruta) y las que no. Se excluyen las que ya se auditan
     | por su cuenta y las pantallas de configuración, que no escriben en la
     | tienda.
     */
    'audit_routes' => [
        'manager.helpdesk.ps.*',
        'manager.helpdesk.customers.ps.*',
    ],
    'audit_exclude_routes' => [
        'manager.helpdesk.customers.ps.vouchers.store',
        'manager.helpdesk.ps.ext.opsmap.*',
        // Operación de la integración (reencolar webhooks, calentar caché,
        // reprocesar eventos): no escribe en la tienda y opslog ya la registra.
        'manager.helpdesk.ps.ext.opslog.*',
        // POST de solo lectura (comparar productos del catálogo).
        'manager.helpdesk.ps.ext.catalog.compare',
        // Vincular un pedido a la conversación: dato interno del helpdesk.
        'manager.helpdesk.ps.ext.orderlink.*',
        // Ajustes del chat: se registran en su propio controlador.
        'manager.helpdesk.ps.ext.settings.*',
    ],

    /*
     | Descripciones del log 'helpdeskprestashop' que NO son acciones contra
     | la tienda y no se muestran en «Auditoría de acciones»: las de
     | operación de opslog y la descarga de documentos del pedido (lectura).
     */
    'audit_hidden_descriptions' => [
        'ps.ops.*',
        'ps.order_document_download',
    ],

    // Claves del cuerpo que se guardan con su valor (escalares, recortados).
    // Del resto solo se anota el nombre del campo: direcciones, notas,
    // teléfonos o motivos en texto libre no deben acabar copiados en el log
    // de actividad.
    'audit_keep_keys' => [
        'state_id', 'notify', 'tracking_number', 'carrier_id', 'address_id', 'type',
        'product_id', 'attribute_id', 'quantity', 'code', 'amount',
        'validity_days', 'id_state',
    ],

    'audit_periods' => [7, 30],
];
