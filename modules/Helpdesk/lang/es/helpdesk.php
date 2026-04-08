<?php

return [
    'messages' => [
        // Tickets
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
        'message_sent' => 'Mensaje enviado exitosamente.',
        'message_updated' => 'Mensaje actualizado exitosamente.',
        'message_deleted' => 'Mensaje eliminado exitosamente.',
        'ticket_assigned' => 'Ticket asignado exitosamente.',
        'ticket_unassigned' => 'Asignación removida exitosamente.',

        // Conversations
        'conversation_created' => 'Conversación creada exitosamente',
        'conversation_updated' => 'Conversación actualizada',
        'conversation_deleted' => 'Conversación eliminada',
        'conversation_restored' => 'Conversación restaurada',
        'conversation_force_deleted' => 'Conversación eliminada permanentemente',
        'conversation_closed' => 'Conversación cerrada',
        'conversation_reopened' => 'Conversación reabierta',
        'conversation_archived' => 'Conversación archivada',
        'conversation_unarchived' => 'Conversación desarchivada',
        'conversation_message_sent' => 'Mensaje enviado correctamente',
        'conversation_message_sent_and_closed' => 'Mensaje enviado y conversación cerrada',

        // Tags
        'tag_added' => 'Etiqueta agregada',
        'tag_removed' => 'Etiqueta eliminada',

        // AJAX field updates
        'priority_updated' => 'Prioridad actualizada',
        'assignment_updated' => 'Asignación actualizada',

        // Customers
        'customer_banned' => "Cliente ':name' suspendido correctamente.",
        'customer_unbanned' => "Cliente ':name' reactivado correctamente.",
        'customer_deleted' => "Cliente ':name' eliminado correctamente.",
        'customer_created' => "Cliente ':name' creado correctamente.",
        'customer_updated' => "Cliente ':name' actualizado correctamente.",

        // Ticket merge, watch, SLA
        'ticket_merged' => 'Ticket #:source fusionado en #:target correctamente.',
        'ticket_watched' => 'Ahora estás siguiendo el ticket.',
        'ticket_unwatched' => 'Has dejado de seguir el ticket.',
        'sla_paused' => 'SLA pausado correctamente.',
        'sla_resumed' => 'SLA reanudado correctamente.',
        'rating_submitted' => '¡Gracias por tu valoración!',
        'recurring_ticket_created' => 'Ticket recurrente creado correctamente.',

        // Ticket templates
        'template_applied' => 'Plantilla aplicada correctamente.',
        'template_created' => 'Plantilla creada correctamente.',
        'template_updated' => 'Plantilla actualizada correctamente.',
        'template_deleted' => 'Plantilla eliminada correctamente.',

        // Recurring tickets
        'recurring_created' => 'Tarea recurrente creada correctamente.',
        'recurring_updated' => 'Tarea recurrente actualizada correctamente.',
        'recurring_deleted' => 'Tarea recurrente eliminada correctamente.',
        'recurring_toggled' => 'Estado de tarea recurrente actualizado.',

        // Escalation
        'ticket_escalated' => 'Ticket escalado a prioridad :priority.',
    ],
];
