<?php

return [
    'payment_methods' => 'Metodos de pago',
    'wompi' => [
        'title' => 'Wompi',
        'description' => 'Paga de forma segura con Wompi. Acepta tarjeta de credito, debito, PSE, Nequi y mas.',
    ],
    'status' => [
        'pending' => 'Pendiente',
        'completed' => 'Completado',
        'failed' => 'Fallido',
        'refunding' => 'Reembolsando',
        'refunded' => 'Reembolsado',
        'canceled' => 'Cancelado',
    ],
    'messages' => [
        'payment_completed' => 'Pago completado exitosamente.',
        'payment_failed' => 'El pago no pudo ser procesado.',
        'payment_pending' => 'El pago esta pendiente de confirmacion.',
        'order_not_found' => 'No se encontro la orden asociada.',
        'invalid_signature' => 'Firma de webhook invalida.',
        'gateway_not_enabled' => 'El metodo de pago no esta habilitado.',
    ],
];
