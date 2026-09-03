<?php

return [
    'messages' => [
        'ticket_created' => 'Ticket creado exitosamente. Número: :number',
        'ticket_updated' => 'Ticket actualizado exitosamente.',
        'ticket_deleted' => 'Ticket eliminado exitosamente.',
        'ticket_closed' => 'Ticket cerrado exitosamente.',
        'ticket_resolved' => 'Ticket marcado como resuelto.',
        'ticket_reopened' => 'Ticket reabierto exitosamente.',
        'ticket_archived' => 'Ticket archivado exitosamente.',
        'ticket_unarchived' => 'Ticket desarchivado correctamente.',
        'ticket_restored' => 'Ticket restaurado correctamente.',
        'ticket_permanently_deleted' => 'Ticket eliminado permanentemente.',
        'ticket_assigned' => 'Ticket asignado exitosamente.',
        'ticket_unassigned' => 'Asignación removida exitosamente.',
        'ticket_merged' => 'Ticket #:source fusionado en #:target correctamente.',
        'ticket_watched' => 'Ahora estás siguiendo el ticket.',
        'ticket_unwatched' => 'Has dejado de seguir el ticket.',
        'ticket_escalated' => 'Ticket escalado a prioridad :priority.',
        'message_sent' => 'Mensaje enviado exitosamente.',
        'message_updated' => 'Mensaje actualizado exitosamente.',
        'message_deleted' => 'Mensaje eliminado exitosamente.',
        'note_added' => 'Nota interna agregada correctamente.',
        'note_updated' => 'Nota actualizada correctamente.',
        'note_deleted' => 'Nota eliminada correctamente.',
        'comment_added' => 'Comentario agregado correctamente.',
        'comment_updated' => 'Comentario actualizado correctamente.',
        'comment_deleted' => 'Comentario eliminado correctamente.',
        'template_applied' => 'Plantilla aplicada correctamente.',
        'template_created' => 'Plantilla creada correctamente.',
        'template_updated' => 'Plantilla actualizada correctamente.',
        'template_deleted' => 'Plantilla eliminada correctamente.',
        'recurring_created' => 'Tarea recurrente creada correctamente.',
        'recurring_updated' => 'Tarea recurrente actualizada correctamente.',
        'recurring_deleted' => 'Tarea recurrente eliminada correctamente.',
        'recurring_toggled' => 'Estado de tarea recurrente actualizado.',
        'recurring_ticket_created' => 'Ticket recurrente creado correctamente.',
        'sla_paused' => 'SLA pausado correctamente.',
        'sla_resumed' => 'SLA reanudado correctamente.',
        'rating_submitted' => '¡Gracias por tu valoración!',
        'priority_updated' => 'Prioridad actualizada.',
        'assignment_updated' => 'Asignación actualizada.',
        'status_updated' => 'Estado actualizado correctamente.',
        'time_entry_added' => 'Tiempo registrado correctamente.',
        'time_entry_deleted' => 'Registro de tiempo eliminado.',
        'bulk_deleted' => ':count tickets eliminados correctamente.',
        'bulk_closed' => ':count tickets cerrados correctamente.',
        'bulk_assigned' => ':count tickets asignados correctamente.',
        'macro_applied' => 'Macro aplicado correctamente.',
    ],

    'fields' => [
        'title' => 'Titulo',
        'subject' => 'Asunto',
        'description' => 'Descripcion',
        'category' => 'Categoria',
        'status' => 'Estado',
        'priority' => 'Prioridad',
        'customer' => 'Cliente',
        'assignee' => 'Agente asignado',
        'source' => 'Fuente',
        'ticket_number' => 'Numero de ticket',
        'created_at' => 'Creado',
        'closed_at' => 'Cerrado',
        'resolved_at' => 'Resuelto',
        'first_response_at' => 'Primera respuesta',
        'sla_resolution_due_at' => 'Vencimiento SLA',
        'rating' => 'Valoracion',
        'time_spent' => 'Tiempo invertido',
        'is_internal' => 'Nota interna',
        'frequency' => 'Frecuencia',
        'next_run_at' => 'Proxima ejecucion',
    ],

    'priority' => [
        'low' => 'Baja',
        'normal' => 'Normal',
        'high' => 'Alta',
        'urgent' => 'Urgente',
    ],

    'source' => [
        'web' => 'Portal web',
        'email' => 'Correo electronico',
        'api' => 'API',
        'widget' => 'Widget',
        'phone' => 'Telefono',
        'manual' => 'Manual',
        'formulario' => 'Formulario',
        'web_form' => 'Formulario',
        'conversation' => 'Conversacion',
        'social' => 'Redes sociales',
        'chatflow' => 'Chatbot',
        'contacts' => 'Contactos',
        'manager' => 'Panel interno',
        'portal' => 'Portal cliente',
    ],

    'actions' => [
        'create' => 'Crear ticket',
        'edit' => 'Editar',
        'delete' => 'Eliminar',
        'close' => 'Cerrar',
        'resolve' => 'Resolver',
        'reopen' => 'Reabrir',
        'assign' => 'Asignar',
        'merge' => 'Fusionar',
        'archive' => 'Archivar',
        'watch' => 'Seguir',
        'unwatch' => 'Dejar de seguir',
        'reply' => 'Responder',
        'add_note' => 'Agregar nota interna',
        'apply_template' => 'Aplicar plantilla',
        'apply_macro' => 'Aplicar macro',
        'export' => 'Exportar',
    ],

    'category' => [
        'created' => 'Categoria creada correctamente.',
        'updated' => 'Categoria actualizada correctamente.',
        'deleted' => 'Categoria eliminada correctamente.',
    ],

    'status' => [
        'created' => 'Estado creado correctamente.',
        'updated' => 'Estado actualizado correctamente.',
        'deleted' => 'Estado eliminado correctamente.',
    ],

    'sla' => [
        'created' => 'Politica SLA creada correctamente.',
        'updated' => 'Politica SLA actualizada correctamente.',
        'deleted' => 'Politica SLA eliminada correctamente.',
        'breached' => 'SLA incumplido',
        'at_risk' => 'SLA en riesgo',
        'on_track' => 'SLA en tiempo',
    ],
    /*
     * Portal del cliente. Estos mensajes los lee el cliente final, no un
     * agente: antes estaban escritos a mano en CustomerPortalController y la
     * mitad en inglés dentro de una aplicación en castellano.
     */
    'portal' => [
        'login_link_sent' => 'Si este correo está registrado, te hemos enviado un enlace de acceso.',
        'login_throttled' => 'Demasiados intentos. Vuelve a probar en :seconds segundos.',
        'auth_throttled' => 'Demasiados intentos de acceso. Espera unos minutos y vuelve a probar.',
        'link_expired' => 'El enlace de acceso ha caducado o no es válido.',
        'account_suspended' => 'Tu cuenta está suspendida. Ponte en contacto con nosotros.',
        'ticket_created' => 'Hemos recibido tu solicitud.',
        'reply_sent' => 'Hemos enviado tu respuesta.',
        'account_updated' => 'Hemos guardado tus datos.',
        'feedback_thanks' => 'Gracias por tu valoración.',
        'rating_invalid' => 'La puntuación no es válida.',
        'rating_thanks' => '¡Gracias por tu valoración! Tu opinión nos ayuda a mejorar.',
    ],
    /*
     * Mensajes de resultado de las pantallas de Ajustes y del ciclo de vida del
     * ticket. Estaban escritos a mano en los controladores, con el estilo
     * derivando ("creada exitosamente" junto a "actualizada correctamente") y
     * algún acento suelto ("Automatizacion"). Aquí quedan unificados y, sobre
     * todo, traducibles.
     */
    'settings' => [
        'feedback' => [
            'thanks' => 'Gracias por tu valoración.',
        ],
        'link' => [
            'created' => 'Ticket enlazado correctamente.',
            'deleted' => 'Enlace eliminado.',
        ],
        'view' => [
            'created' => 'Vista creada correctamente.',
            'updated' => 'Vista actualizada correctamente.',
            'deleted' => 'Vista eliminada correctamente.',
            'cannot_delete_system' => 'Las vistas del sistema no se pueden eliminar.',
        ],
        'macro' => [
            'created' => 'Macro creada correctamente.',
            'updated' => 'Macro actualizada correctamente.',
            'deleted' => 'Macro eliminada correctamente.',
        ],
        'blacklist' => [
            'sender_added' => 'Remitente añadido a la lista negra.',
            'rule_added' => 'Regla añadida a la lista negra.',
            'toggled' => 'Estado de la regla actualizado.',
            'deleted' => 'Regla eliminada de la lista negra.',
        ],
        'group' => [
            'created' => 'Grupo creado correctamente.',
            'updated' => 'Grupo actualizado correctamente.',
            'toggled' => 'Estado del grupo actualizado.',
            'deleted' => 'Grupo eliminado correctamente.',
            'cannot_delete_default' => 'El grupo predeterminado no se puede eliminar.',
            'cannot_delete_with_tickets' => 'No se puede eliminar un grupo con tickets asignados.',
        ],
        'channel' => [
            'created' => 'Canal de correo añadido correctamente.',
            'updated' => 'Canal de correo actualizado correctamente.',
            'deleted' => 'Canal de correo eliminado correctamente.',
        ],
        'canned_reply' => [
            'created' => 'Respuesta predefinida creada correctamente.',
            'updated' => 'Respuesta predefinida actualizada correctamente.',
            'deleted' => 'Respuesta predefinida eliminada correctamente.',
        ],
        'automation' => [
            'created' => 'Automatización creada correctamente.',
            'updated' => 'Automatización actualizada correctamente.',
            'deleted' => 'Automatización eliminada correctamente.',
        ],
        'status' => [
            'created' => 'Estado creado correctamente.',
            'updated' => 'Estado actualizado correctamente.',
            'deleted' => 'Estado eliminado correctamente.',
            'cannot_delete_default' => 'El estado predeterminado no se puede eliminar.',
            'cannot_delete_with_tickets' => 'No se puede eliminar un estado con tickets asociados.',
        ],
        'sla' => [
            'created' => 'Política de SLA creada correctamente.',
            'updated' => 'Política de SLA actualizada correctamente.',
            'deleted' => 'Política de SLA eliminada correctamente.',
            'toggled' => 'Estado de la política de SLA actualizado.',
            'cannot_delete_in_use' => 'No se puede eliminar una política de SLA en uso.',
        ],
        'priority' => [
            'created' => 'Prioridad creada correctamente.',
            'updated' => 'Prioridad actualizada correctamente.',
            'deleted' => 'Prioridad eliminada correctamente.',
            'at_least_one' => 'Debe quedar al menos una prioridad configurada.',
        ],
        'category' => [
            'created' => 'Categoría creada correctamente.',
            'updated' => 'Categoría actualizada correctamente.',
            'deleted' => 'Categoría eliminada correctamente.',
            'toggled' => 'Estado de la categoría actualizado.',
            'cannot_delete_with_tickets' => 'No se puede eliminar una categoría con tickets asociados.',
        ],
        'general' => [
            'updated' => 'Configuración de tickets actualizada correctamente.',
        ],
    ],
    'email_log_panel' => [
        'customer' => 'cliente',
        'assignee' => 'responsable',
    ],
    // Chips de rol en la pestaña "Hilo" del detalle.
    'thread' => [
        'role_customer' => 'Cliente',
        'role_agent' => 'Agente',
        'role_system' => 'Sistema',
    ],
];
