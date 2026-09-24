<?php

/**
 * Extensión "rever" — cambios de producto gestionados por REVER.
 *
 * Solo lecturas en el bridge (rever.order_exchanges) con el permiso existente
 * helpdeskprestashop.orders.view: no añade permisos ni escrituras. El estado
 * del paquete de la devolución, su etiqueta o el motivo viven en la API de
 * REVER, que esta extensión no llama.
 */
return [
    'write_actions' => [],
    'permissions' => [],
    'role_permissions' => [],
    'listeners' => [],
];
