<?php

// Ticket-related strings (ticket_*, recurring_*, template_*, sla_paused/resumed,
// rating_submitted) live in modules/HelpdeskTickets/lang/{lang}/helpdesktickets.php
// since they are owned by the HelpdeskTickets module.

return [
    'messages' => [
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
        'tags_updated' => 'Etiquetas actualizadas',

        // AJAX field updates
        'priority_updated' => 'Prioridad actualizada',
        'assignment_updated' => 'Asignación actualizada',

        // Customers
        'customer_banned' => "Cliente ':name' suspendido correctamente.",
        'customer_unbanned' => "Cliente ':name' reactivado correctamente.",
        'customer_deleted' => "Cliente ':name' eliminado correctamente.",
        'customer_created' => "Cliente ':name' creado correctamente.",
        'customer_updated' => "Cliente ':name' actualizado correctamente.",
    ],

];
