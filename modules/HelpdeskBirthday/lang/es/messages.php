<?php

return [
    'module_name' => 'Cumpleaños',
    'campaigns_title' => 'Campañas de cumpleaños',
    'settings_title' => 'Ajustes de cumpleaños',

    'default_customer_name' => 'cliente',
    'mail_fallback_subject' => '¡Feliz cumpleaños!',

    'campaign_prepared' => 'Campaña preparada.',
    'campaign_paused' => 'Campaña pausada. No saldrá ningún correo más hasta que la reanudes.',
    'campaign_resumed' => 'Campaña reanudada.',
    'campaign_cancelled' => 'Campaña cancelada. Los envíos pendientes se han descartado.',
    'transition_not_allowed' => 'Esa acción no es posible en el estado actual de la campaña.',
    'settings_saved' => 'Ajustes guardados.',
    'coupon_missing' => 'Ese pedido no tiene ningún bono que marcar.',
    'recipient_unsubscribed' => 'Se dio de baja a :email de las felicitaciones de cumpleaños.',
    'recipient_requeued' => 'El envío vuelve a la cola.',
    'retry_not_allowed' => 'Solo se pueden reintentar los envíos que fallaron.',
    'test_send_ok' => 'Prueba enviada a :emails.',
    'test_send_failed' => 'No se pudo enviar la prueba. :errors',
    'test_send_too_many' => 'Máximo 5 direcciones por prueba.',
    'failed_requeued' => ':count envíos fallidos vuelven a la cola.',
    'no_failed_to_retry' => 'No hay envíos fallidos que reintentar.',
    'coupon_not_in_campaign' => 'Ese bono no es de esta campaña.',
    'bonos_retried' => 'Gestión emitió :generated bonos. Sin bono todavía: :failed.',
    'no_bonos_to_retry' => 'No hay destinatarios esperando un bono.',

    'status' => [
        'draft' => 'Borrador',
        'scheduled' => 'Programada',
        'sending' => 'Enviando',
        'paused' => 'Pausada',
        'completed' => 'Completada',
        'failed' => 'Fallida',
        'cancelled' => 'Cancelada',
    ],

    'recipient_status' => [
        'pending' => 'Pendiente',
        'sending' => 'En cola',
        'sent' => 'Enviado',
        'failed' => 'Fallido',
        'skipped' => 'Omitido',
    ],

    'skip_reason' => [
        'suppressed' => 'En lista de supresión',
        'invalid_email' => 'Email inválido',
        'duplicate' => 'Duplicado',
        'cancelled' => 'Campaña cancelada',
        'no_coupon' => 'Gestión no le emitió el bono',
        'expired' => 'Se le pasó el día',
    ],

    'source' => [
        'manual' => 'Configurado a mano',
        'erp' => 'Validado con gestión',
    ],
    'redemptions_unavailable' => 'No se puede consultar la tienda ahora mismo: revisa el bridge de PrestaShop.',
    'redemptions_synced' => 'Canjes actualizados: :count.',
    'reconcile_done' => 'Marcados en gestión: :ok. Rechazados: :failed.',
    'reconcile_nothing' => 'No hay ningún bono pendiente de marcar con código conocido.',
];
