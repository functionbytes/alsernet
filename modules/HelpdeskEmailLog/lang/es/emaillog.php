<?php

return [
    'title' => 'Log de emails',
    'subtitle' => 'Registro centralizado de los emails enviados por el sistema',

    'stats' => [
        'total' => 'Total registrados',
        'total_hint' => 'Todos los registros',
        'sent' => 'Entregados al servidor',
        'sent_hint' => 'Aceptados por el transporte',
        'failed' => 'Fallidos',
        'failed_hint' => 'Con error de envío',
        'queued' => 'En cola',
        'queued_hint' => 'Pendientes de confirmación',
        'today' => 'Hoy',
        'today_hint' => 'Registrados hoy',
    ],

    'filters' => [
        'heading' => 'Filtros de búsqueda',
        'description' => 'Encuentra emails usando múltiples criterios de filtrado',
        'search_placeholder' => 'Buscar por asunto, destinatario o remitente...',
        'all_modules' => 'Todos los módulos',
        'all_statuses' => 'Todos los estados',
        'date_range' => 'Rango de fechas',
        'per_page' => 'Registros por página',
        'per_page_option' => ':n / pág.',
        'search' => 'Buscar',
        'clear' => 'Limpiar filtros',
    ],

    'table' => [
        'subject' => 'Asunto',
        'recipient' => 'Destinatario',
        'module' => 'Módulo',
        'status' => 'Estado',
        'date' => 'Fecha',
        'actions' => 'Acciones',
        'empty' => 'No se encontraron emails registrados',
        'select_all' => 'Seleccionar todo',
    ],

    'actions' => [
        'view' => 'Ver contenido',
        'resend' => 'Reenviar',
        'delete' => 'Eliminar',
        'export' => 'Exportar CSV',
        'bulk_delete' => 'Eliminar seleccionados',
        'back_to_list' => 'Volver al listado',
        'print' => 'Imprimir',
    ],

    'pagination' => [
        'showing' => 'Mostrando :first–:last de :total registros',
    ],

    'bulk' => [
        'label' => 'seleccionados',
        'confirm' => '¿Eliminar :count registros? Esta acción no se puede deshacer.',
        'none_selected' => 'Selecciona al menos un registro.',
    ],

    'confirm' => [
        'title' => 'Confirmar acción',
        'delete_title' => 'Confirmar eliminación',
        'delete_one' => '¿Eliminar este registro de email? Esta acción no se puede deshacer.',
        'accept' => 'Aceptar',
        'cancel' => 'Cancelar',
    ],

    'deleted' => [
        'one' => 'Registro de email eliminado.',
        'many' => ':count registros eliminados.',
    ],

    'status' => [
        'queued' => 'En cola',
        'sent' => 'Enviado',
        'failed' => 'Fallido',
    ],

    'preview' => [
        'title' => 'Vista previa del email',
        'heading' => 'Contenido del email',
        'desktop' => 'Escritorio',
        'mobile' => 'Móvil',
        'footer_note' => 'Este es el contenido exacto del email registrado por el sistema.',
        'no_content' => 'El contenido del email no está disponible.',
        'detail' => 'Detalle del email',
        'detail_hint' => 'Información del correo registrado',
        'field' => [
            'subject' => 'Asunto',
            'from' => 'De',
            'to' => 'Para',
            'cc' => 'CC',
            'reply_to' => 'Responder a',
            'status' => 'Estado',
            'module' => 'Módulo',
            'entity' => 'Entidad',
            'message_id' => 'Message-ID',
            'attachments' => 'Adjuntos',
            'sent_at' => 'Fecha de envío',
            'created_at' => 'Registrado',
            'causer' => 'Originado por',
            'error' => 'Error',
        ],
        'quick_actions' => 'Acciones rápidas',
        'quick_actions_hint' => 'Opciones disponibles',
    ],

    'resend' => [
        'success' => 'Email reenviado correctamente.',
        'queued' => 'Reenvío encolado. El email se enviará en segundo plano.',
        'failed' => 'No se pudo reenviar el email: :error',
        'no_recipients' => 'Este registro no tiene destinatarios.',
        'confirm_title' => 'Confirmar reenvío',
        'confirm' => '¿Reenviar este email a sus destinatarios originales?',
    ],

    'settings' => [
        'title' => 'Log de emails — Configuración',
        'saved' => 'Configuración del log de emails actualizada.',
    ],
];
