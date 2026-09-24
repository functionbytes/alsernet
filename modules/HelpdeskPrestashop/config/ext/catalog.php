<?php

/*
|--------------------------------------------------------------------------
| Extensión "catalog" — piezas 10 (Comparar productos), 16 (Disponibilidad
| y plazos), 25 (Stock por almacén) y 26 (Precio por grupo)
|--------------------------------------------------------------------------
| Acciones del puente en alsernetbridge/helpers/ext/catalog.php. Accesible en
| config('helpdeskprestashop.ext.catalog.*').
*/

return [
    'write_actions' => ['catalog.stock_alert'],

    'permissions' => [
        // Apunta al cliente al aviso de vuelta a stock de ps_emailalerts: es
        // una escritura en la tienda (el módulo le mandará un correo), por
        // eso lleva permiso propio aunque solo lea el resto de la ficha.
        'helpdeskprestashop.catalog.stock_alert' => 'Apuntar al cliente al aviso de vuelta a stock en PrestaShop',
    ],

    // El agente restringido no escribe en la tienda: no aparece aquí.
    'role_permissions' => [
        'helpdesk-agent' => ['helpdeskprestashop.catalog.stock_alert'],
        'helpdesk-supervisor' => ['helpdeskprestashop.catalog.stock_alert'],
        'helpdesk-manager' => ['helpdeskprestashop.catalog.stock_alert'],
        'helpdesk-admin' => ['helpdeskprestashop.catalog.stock_alert'],
    ],

    'listeners' => [],

    // Nombre de cada ubicación de repositorio_stock (clave que devuelve el
    // puente → etiqueta en pantalla). Salen de las tiendas de la página de
    // contacto de la tienda y de las tablas de traspaso del ERP con el mismo
    // sufijo; se pueden renombrar sin tocar PrestaShop.
    'locations' => [
        'pocomaco' => 'Almacén Pocomaco · A Coruña',
        'capthaya' => 'Tienda Capitán Haya · Madrid',
        'ddleon' => 'Tienda Diego de León · Madrid',
        'tpvcor' => 'Tienda A Coruña',
    ],

    // Segundos que se guarda la ficha (stock, plazo, precios) de un producto
    // para un cliente. Corto: el stock cambia con cada venta.
    'sheet_cache_ttl' => (int) env('HELPDESK_PS_CATALOG_SHEET_TTL', 60),

    // Productos por comparación (el puente también corta en 3).
    'compare_max' => 3,
];
