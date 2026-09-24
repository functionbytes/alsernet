<?php

/**
 * Extensión "invoice" de Gestión en el chat: copia informativa de factura
 * en PDF y mini-gráfico facturado/cobrado del Balance. SOLO LECTURA.
 *
 * No añade permisos: usa helpdeskerp.view + helpdeskerp.finance.view, que ya
 * siembra HelpdeskErpPermissionsSeeder. Ajustes accesibles (si el cargador
 * de config/ext los publica ahí) en config('helpdeskErp.ext.invoice.*'); el
 * código usa estos mismos valores por defecto si no están cargados.
 */
return [
    'permissions' => [],

    'role_permissions' => [],

    // Texto de la marca de agua y del pie de la copia en PDF.
    'watermark' => 'Copia informativa — no válida como factura',

    // Meses del mini-gráfico del Balance (1-12).
    'chart_months' => 6,

    // Detalles de factura que se piden como mucho por petición para sumar
    // lo facturado (la lista del manager no trae importes). Cacheados 30 min.
    'chart_max_details' => 12,
];
