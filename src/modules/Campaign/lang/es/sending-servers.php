<?php

return [
    // Títulos de página
    'page.title' => 'Servidores de envío',
    'domain.title' => 'Dominios de envío',
    'tracking.title' => 'Dominios de tracking',
    'blacklist.title' => 'Lista negra',

    // Stats
    'stat.total' => 'Total',
    'stat.active' => 'Activos',
    'stat.inactive' => 'Inactivos',

    // Columnas
    'col.name' => 'Nombre',
    'col.type' => 'Tipo',
    'col.status' => 'Estado',
    'col.quota' => 'Cuota',

    // Acciones
    'action.test' => 'Probar conexión',
    'action.verify' => 'Verificar',
    'action.edit' => 'Editar',
    'action.delete' => 'Eliminar',

    // Mensajes flash
    'flash.created' => 'Servidor de envío creado.',
    'flash.updated' => 'Servidor de envío actualizado.',
    'flash.deleted' => 'Servidor de envío eliminado.',
    'flash.tested' => 'Conexión exitosa.',
    'flash.verified' => 'Verificado correctamente.',

    // Estado
    'status.active' => 'Activo',
    'status.inactive' => 'Inactivo',

    // Tipos de proveedor
    'type.amazon-api' => 'Amazon SES (API)',
    'type.amazon-smtp' => 'Amazon SES (SMTP)',
    'type.sendgrid-api' => 'SendGrid (API)',
    'type.sendgrid-smtp' => 'SendGrid (SMTP)',
    'type.mailgun-api' => 'Mailgun (API)',
    'type.mailgun-smtp' => 'Mailgun (SMTP)',
    'type.sparkpost-api' => 'SparkPost (API)',
    'type.sparkpost-smtp' => 'SparkPost (SMTP)',
    'type.elasticemail-api' => 'ElasticEmail (API)',
    'type.elasticemail-smtp' => 'ElasticEmail (SMTP)',
    'type.brevo-api' => 'Brevo (API)',
    'type.smtp' => 'SMTP genérico',
    'type.sendmail' => 'Sendmail (local)',

    // Campos de dominio
    'domain.email' => 'Email',
    'domain.reason' => 'Motivo',

    // Blacklist
    'blacklist.email' => 'Email',
    'blacklist.reason' => 'Motivo',

    // Paginación (compartida con campaign::page-templates)
    'pagination.showing' => 'Mostrando :from–:to de :total',
    'pagination.prev' => 'Anterior',
    'pagination.next' => 'Siguiente',
];
