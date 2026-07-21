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
        'delivery_rate' => 'Tasa de entrega',
        'delivery_rate_hint' => 'Enviados sobre el total',
    ],

    'trend' => [
        'title' => 'Actividad de los últimos 14 días',
        'hint' => 'Emails registrados por día y estado',
        'sent' => 'Enviados',
        'failed' => 'Fallidos',
        'queued' => 'En cola',
    ],

    'stale' => [
        'warning' => ':count email(s) llevan más de :hours h en cola sin confirmarse.',
        'view' => 'Ver en cola',
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
        'has_attachments' => 'Con adjuntos',
    ],

    'actions' => [
        'view' => 'Ver contenido',
        'resend' => 'Reenviar',
        'delete' => 'Eliminar',
        'export' => 'Exportar CSV',
        'bulk_delete' => 'Eliminar seleccionados',
        'back_to_list' => 'Volver al listado',
        'print' => 'Imprimir',
        'bulk_resend' => 'Reenviar seleccionados',
        'download' => 'Descargar',
        'copy_id' => 'Copiar Message-ID',
        'resend_to' => 'Reenviar a otra dirección',
        'purge' => 'Purgar contenido',
    ],

    'purge' => [
        'confirm_title' => 'Purgar contenido del email',
        'confirm' => 'Se eliminará el cuerpo (HTML/texto) de este email de forma permanente. Los metadatos (asunto, destinatarios, estado) se conservan. ¿Continuar?',
        'done' => 'Contenido del email purgado.',
    ],

    'pagination' => [
        'showing' => 'Mostrando :first–:last de :total registros',
    ],

    'bulk' => [
        'label' => 'seleccionados',
        'confirm' => '¿Eliminar :count registros? Esta acción no se puede deshacer.',
        'none_selected' => 'Selecciona al menos un registro.',
        'resend_confirm' => '¿Reenviar :count emails a sus destinatarios originales?',
        'resend_title' => 'Confirmar reenvío masivo',
    ],

    'copy' => [
        'message_id_copied' => 'Message-ID copiado al portapapeles.',
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
            'bcc' => 'CCO',
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
        'related_entity' => 'Entidad relacionada',
        'related_entity_hint' => 'Registro vinculado a este email',
        'related_emails' => 'Emails relacionados',
        'related_emails_hint' => 'Mismo destinatario o entidad',
        'no_related' => 'Sin emails relacionados.',
        'purged_note' => 'El contenido de este email fue purgado; solo se conservan los metadatos.',
    ],

    'resend' => [
        'success' => 'Email reenviado correctamente.',
        'queued' => 'Reenvío encolado. El email se enviará en segundo plano.',
        'queued_to' => 'Reenvío a :email encolado.',
        'bulk_queued' => ':count emails encolados para reenvío.',
        'failed' => 'No se pudo reenviar el email: :error',
        'no_recipients' => 'Este registro no tiene destinatarios.',
        'blocked' => 'No se puede reenviar: el contenido almacenado de este email está redactado o truncado.',
        'bulk_skipped' => ':count omitidos (sin destinatarios o con contenido redactado/truncado).',
        'confirm_title' => 'Confirmar reenvío',
        'confirm' => '¿Reenviar este email a sus destinatarios originales?',
        'to_title' => 'Reenviar a otra dirección',
        'to_label' => 'Dirección de correo alternativa',
        'to_placeholder' => 'nombre@ejemplo.com',
        'to_hint' => 'El email se enviará solo a esta dirección (sin CC).',
        'to_send' => 'Enviar',
    ],

    'settings' => [
        'title' => 'Log de emails — Configuración',
        'saved' => 'Configuración del log de emails actualizada.',
    ],

    'csv' => [
        'uid' => 'UID',
        'date' => 'Fecha',
        'sent_at' => 'Enviado',
        'status' => 'Estado',
        'subject' => 'Asunto',
        'from' => 'De',
        'to' => 'Para',
        'cc' => 'CC',
        'module' => 'Módulo',
        'entity' => 'Entidad',
        'mailable' => 'Mailable',
        'error' => 'Error',
    ],
];
