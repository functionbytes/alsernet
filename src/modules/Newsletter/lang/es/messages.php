<?php

return [
    'module_name' => 'Newsletter',
    'breadcrumb' => 'Newsletter',

    'actions' => [
        'create' => 'Crear',
        'edit' => 'Editar',
        'update' => 'Actualizar',
        'delete' => 'Eliminar',
        'view' => 'Ver',
        'save' => 'Guardar',
        'cancel' => 'Cancelar',
        'back' => 'Volver',
        'export' => 'Exportar',
        'search' => 'Buscar',
        'filter' => 'Filtrar',
        'reset' => 'Restablecer',
    ],

    'messages' => [
        'created' => 'Creado correctamente.',
        'updated' => 'Actualizado correctamente.',
        'deleted' => 'Eliminado correctamente.',
        'error' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
        'not_found' => 'No encontrado.',
        'unauthorized' => 'No tienes permiso para realizar esta acción.',
    ],

    'subscribers' => [
        'title' => 'Suscriptores',
        'all' => 'Todos los suscriptores',
        'active' => 'Suscriptores activos',
        'total' => 'Total suscriptores',
        'new_this_month' => 'Nuevos este mes',
        'export' => 'Exportar suscriptores',
        'import' => 'Importar suscriptores',
        'email' => 'Correo electrónico',
        'name' => 'Nombre',
        'no_subscribers' => 'No hay suscriptores registrados.',
    ],

    'subscribed' => [
        'success' => '¡Te has suscrito correctamente!',
        'already' => 'Ya estás suscrito con este correo.',
        'confirm' => 'Por favor, confirma tu suscripción en tu correo.',
        'confirmed' => 'Suscripción confirmada correctamente.',
    ],

    'unsubscribed' => [
        'button' => 'Cancelar suscripción',
        'success' => 'Has cancelado tu suscripción correctamente.',
        'already' => 'Este correo ya no está suscrito.',
        'link' => 'Cancelar suscripción',
    ],

    'popup' => [
        'title' => 'Suscríbete a nuestro newsletter',
        'description' => 'Recibe las últimas novedades directamente en tu correo.',
        'placeholder' => 'Tu correo electrónico',
        'submit' => 'Suscribirme',
        'dismiss' => 'No, gracias',
        'enabled' => 'Popup activado.',
        'disabled' => 'Popup desactivado.',
    ],

    'mailjet' => [
        'title' => 'Configuración Mailjet',
        'api_key' => 'API Key',
        'api_secret' => 'API Secret',
        'list_id' => 'ID de lista',
        'connection_ok' => 'Conexión con Mailjet verificada correctamente.',
        'connection_error' => 'No se pudo conectar con Mailjet. Verifica tus credenciales.',
        'sync' => 'Sincronizar con Mailjet',
        'synced' => 'Suscriptores sincronizados correctamente.',
    ],

    'campaigns' => [
        'title' => 'Campañas',
        'create' => 'Nueva campaña',
        'send' => 'Enviar campaña',
        'sent' => 'Campaña enviada correctamente.',
        'draft' => 'Borrador',
        'scheduled' => 'Programada',
        'sent_status' => 'Enviada',
    ],
];
