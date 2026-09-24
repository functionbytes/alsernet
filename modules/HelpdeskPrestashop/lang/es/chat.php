<?php

/*
 * HelpdeskPrestashop · textos de las piezas de PrestaShop en el chat.
 * Se usan desde los Blade con __('helpdeskprestashop::chat.<clave>').
 * El JS lee los suyos de window.PscChatTxt (ver el @push del tab).
 */

return [
    'tabs' => [
        'store' => 'Tienda',
        'returns' => 'Devoluciones',
        'vouchers' => 'Cupones',
        'addresses' => 'Direcciones',
    ],

    'states' => [
        'loading' => 'Cargando…',
        'retry' => 'Reintentar',
        'empty_returns' => 'Sin devoluciones',
        'empty_returns_sub' => 'No hay devoluciones registradas en PrestaShop',
        'empty_vouchers' => 'Sin cupones',
        'empty_vouchers_sub' => 'Este cliente no tiene cupones en PrestaShop',
        'empty_carts' => 'Sin carritos',
        'empty_carts_sub' => 'Este cliente no tiene carritos en PrestaShop',
        'empty_orders' => 'Sin pedidos',
        'empty_orders_sub' => 'Este cliente no ha comprado todavía',
        'no_customer' => 'Contacto sin cliente en PrestaShop',
        'no_customer_sub' => 'Ningún cliente con este email',
        'link_customer' => 'Buscar y vincular',
        'error_load' => 'No se ha podido consultar PrestaShop. Inténtalo de nuevo.',
        'error_save' => 'No se han podido guardar los cambios. Se conserva lo escrito.',
    ],

    'customer' => [
        'title' => 'Cliente en PrestaShop',
        'linked' => 'Vinculado',
        'email' => 'Email',
        'phone' => 'Teléfono',
        'orders' => 'Pedidos',
        'ltv' => 'LTV',
        'vouchers' => 'Vales',
        'open_sheet' => 'Abrir ficha del cliente',
    ],

    'addresses' => [
        'defaults' => 'Direcciones por defecto',
        'shipping' => 'Envío',
        'billing' => 'Facturación',
        'change' => 'Cambiar',
        'see_all' => 'Ver todas las direcciones',
        'search' => 'Buscar por alias, calle o código postal…',
        'in_use' => 'en uso',
        'new' => 'Crear dirección nueva',
        'save' => 'Guardar dirección',
    ],

    'cart' => [
        'live' => 'En vivo',
        'view' => 'Ver',
        'payment_link' => 'Link de pago',
        'convert' => 'Convertir',
        'avg_hint' => ':percent % del valor medio del cliente',
        'vouchers' => 'Cupones',
        'voucher_placeholder' => 'Código de cupón o buscar en los del cliente…',
        'apply' => 'Aplicar',
        'subtotal' => 'Subtotal',
        'shipping' => 'Envío',
        'total' => 'Total',
    ],

    'returns' => [
        'title' => 'Devoluciones',
        'start' => 'Iniciar devolución',
        'sheet_intro' => 'Marca las líneas que el cliente devuelve y ajusta las unidades. Se creará un RMA en estado Esperando confirmación.',
        'lines' => 'Líneas del pedido',
        'select_all' => 'Seleccionar todas',
        'reason' => 'Motivo de la devolución',
        'detail' => 'Detalle para el cliente (opcional)',
        'none_selected' => 'Ninguna línea seleccionada',
        'submit' => 'Solicitar devolución',
        'already' => 'Ya devuelto en :reference',
    ],

    'vouchers' => [
        'available' => 'Disponibles',
        'spent' => 'Usados y caducados',
        'state_available' => 'Disponible',
        'state_used' => 'Usado',
        'state_expired' => 'Caducado',
        'free_shipping' => 'Envío gratis',
        'apply_to_cart' => 'Aplicar al carrito',
        'send_to_chat' => 'Enviar al chat',
        'minimum' => 'mínimo :amount',
        'copy' => 'Copiar código',
    ],

    'refunds' => [
        'title' => 'Reembolsos',
        'sub' => 'Dinero ya devuelto al cliente',
        'total' => 'Total reembolsado',
        'empty' => 'Sin reembolsos',
    ],

    'wishlist' => [
        'title' => 'Enviar de la lista de deseos',
        'label' => 'Chat · Lista de deseos',
        'search' => 'Buscar en la lista de deseos…',
        'empty' => 'Lista de deseos vacía',
        'empty_sub' => 'Este cliente no tiene productos guardados',
        'preview' => 'Se insertará en el composer',
        'preview_sub' => 'Nombre, enlace e imagen · no se envía el mensaje',
        'insert' => 'Insertar en el chat',
    ],

    'actions' => [
        'recommend' => 'Recomendar producto',
        'wishlist' => 'Enviar de la lista de deseos',
        'backoffice' => 'Abrir en el back-office',
        'cancel' => 'Cancelar',
        'close' => 'Cerrar',
        'save' => 'Guardar cambios',
        'orders' => 'Ver pedidos del cliente',
    ],

    'health' => [
        'fresh' => 'Datos de hace :minutes min',
        'refresh' => 'Actualizar',
        'stale' => 'PrestaShop no responde · mostrando caché de :time',
        'down' => 'Sin conexión con el puente',
    ],
];
