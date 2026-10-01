<?php

/*
 | Catálogo de acciones del asistente IA (módulo HelpdeskAiPrompts).
 |
 | bridge_allowlist es la ÚNICA lista de acciones del bridge de PrestaShop
 | (alsernetbridge) que se pueden configurar como herramienta de la IA. Todo lo
 | que no esté aquí se rechaza al guardar y en tiempo de ejecución.
 |
 |  - mode:     'read' (solo consulta) | 'write' (cambia algo; exige confirmación
 |              expresa del cliente y verificación de propiedad).
 |  - customer: true = la acción va sobre un cliente; el ejecutor inyecta SIEMPRE
 |              el lookup (email/external_id) y la propiedad nunca puede ser 'none'.
 |  - order:    true = exige un pedido ya comprobado como del cliente (order_id lo
 |              pone el ejecutor); 'optional' = lo añade si existe; false = no aplica.
 |
 | NUNCA añadir: order.change_status, order.set_tracking, order.set_address,
 | order.add_note, customer.create_voucher, cart.*, address.*, customer.address.*,
 | order.flag_for_erp_send, opslog.*, cartpay.*.
 */
return [
    'bridge_allowlist' => [
        // Lectura
        'order.detail' => ['mode' => 'read', 'customer' => true, 'order' => true],
        'order.documents' => ['mode' => 'read', 'customer' => true, 'order' => true],
        'customer.orders' => ['mode' => 'read', 'customer' => true, 'order' => false],
        'customer.returns' => ['mode' => 'read', 'customer' => true, 'order' => false],
        'customer.vouchers' => ['mode' => 'read', 'customer' => true, 'order' => false],
        'customer.refunds' => ['mode' => 'read', 'customer' => true, 'order' => false],
        'refunds.order_refundable' => ['mode' => 'read', 'customer' => true, 'order' => true],
        'rever.order_exchanges' => ['mode' => 'read', 'customer' => true, 'order' => 'optional'],
        'catalog.product_sheet' => ['mode' => 'read', 'customer' => false, 'order' => false],
        'product.search' => ['mode' => 'read', 'customer' => false, 'order' => false],

        // Escritura (siempre con customer_confirmed)
        'order.send_email' => ['mode' => 'write', 'customer' => true, 'order' => true],
        'order.start_return' => ['mode' => 'write', 'customer' => true, 'order' => true],
        'catalog.stock_alert' => ['mode' => 'write', 'customer' => true, 'order' => false],
    ],

    /*
     | Hosts permitidos para acciones de tipo http (coincidencia exacta,
     | separados por comas en AI_ACTIONS_ALLOWED_HOSTS). Vacío = ninguna.
     */
    'http_allowed_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('AI_ACTIONS_ALLOWED_HOSTS', ''))
    ))),

    'http_max_response_bytes' => 262144, // 256 KB

    'max_timeout' => 10,

    'default_timeout' => 8,

    'default_max_chars' => 1500,

    'default_max_per_conversation' => 5,

    /*
     | Claves que sobreviven cuando una acción no define response.fields
     | (lista blanca compacta, a cualquier profundidad).
     */
    'default_response_keys' => [
        'id', 'reference', 'name', 'title', 'status', 'state', 'state_name', 'date', 'created_at',
        'updated_at', 'total', 'currency', 'quantity', 'number', 'message', 'sent', 'subscribed',
        'already', 'error', 'price', 'available', 'description', 'brand', 'category',
    ],
];
