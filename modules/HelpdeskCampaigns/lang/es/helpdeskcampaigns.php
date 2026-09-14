<?php

return [
    'messages' => [
        'campaign_created' => 'Campaña creada correctamente.',
        'campaign_updated' => 'Campaña actualizada correctamente.',
        'campaign_deleted' => 'Campaña eliminada correctamente.',
        'campaign_activated' => 'Campaña activada correctamente.',
        'campaign_paused' => 'Campaña pausada correctamente.',
        'campaign_sent' => 'Campaña enviada correctamente.',
        'template_created' => 'Plantilla de campaña creada correctamente.',
        'template_updated' => 'Plantilla de campaña actualizada correctamente.',
        'template_deleted' => 'Plantilla de campaña eliminada correctamente.',
        'impression_tracked' => 'Impresion registrada.',
        'settings_saved' => 'Configuracion de campañas guardada correctamente.',
    ],

    'fields' => [
        'name' => 'Nombre',
        'subject' => 'Asunto',
        'body' => 'Contenido',
        'status' => 'Estado',
        'trigger' => 'Disparador',
        'target' => 'Audiencia objetivo',
        'sent_at' => 'Enviado',
        'scheduled_at' => 'Programado para',
        'open_rate' => 'Tasa de apertura',
        'click_rate' => 'Tasa de clics',
        'impressions' => 'Impresiones',
        'conversions' => 'Conversiones',
    ],

    'status' => [
        'draft' => 'Borrador',
        'active' => 'Activa',
        'paused' => 'Pausada',
        'sent' => 'Enviada',
        'archived' => 'Archivada',
    ],

    'trigger' => [
        'manual' => 'Manual',
        'on_new_conversation' => 'Al iniciar conversacion',
        'on_ticket_created' => 'Al crear ticket',
        'on_ticket_closed' => 'Al cerrar ticket',
        'scheduled' => 'Programada',
        'url_match' => 'Por URL visitada',
    ],

    'actions' => [
        'create' => 'Crear campaña',
        'edit' => 'Editar',
        'delete' => 'Eliminar',
        'activate' => 'Activar',
        'pause' => 'Pausar',
        'send' => 'Enviar ahora',
        'preview' => 'Vista previa',
        'duplicate' => 'Duplicar',
        'export_stats' => 'Exportar estadisticas',
    ],
];
