<?php

use Modules\HelpdeskErp\Listeners\ErpTicketsViewBridge;

/*
 | Extensión "tickets": Tienda (PrestaShop) y Gestión (ERP) en la vista de
 | ticket (/panel/helpdesk/tickets). SOLO LECTURA en lo que toca a Gestión.
 |
 | No modifica HelpdeskTickets: el listener escucha el evento de vista
 | "composing: helpdesktickets::managers.tickets.index" (el mismo que usa
 | View::composer por dentro) y empuja al stack 'scripts' del layout el
 | puente resources/views/tickets/bridge.blade.php (modales de Gestión y de
 | la tienda, window.ErpChat, PscStore…) y erp-tickets.js, que añade la
 | pestaña "Tienda y Gestión" al panel lateral del ticket.
 |
 | Sin permisos nuevos: reutiliza helpdeskerp.view (+ los de cada sección)
 | y helpdeskprestashop.view.
 */
return [
    'permissions' => [],

    'role_permissions' => [],

    'listeners' => [
        'composing: helpdesktickets::managers.tickets.index' => [ErpTicketsViewBridge::class],
    ],
];
