<?php

// Ticket-related strings (ticket_*, recurring_*, template_*, sla_paused/resumed,
// rating_submitted) live in modules/HelpdeskTickets/lang/{lang}/helpdesktickets.php
// since they are owned by the HelpdeskTickets module.

return [
    'messages' => [
        'message_from_support' => 'Mensaje de soporte',

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

        // Merge
        'merge_self' => 'No puedes fusionar una conversación consigo misma.',
        'merge_different_customer' => 'Las conversaciones no pertenecen al mismo contacto.',
        'merge_success' => 'Conversaciones fusionadas correctamente.',

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

    'labels' => [
        'support_team' => 'Equipo de soporte',
        'conversation_ref' => 'Referencia de conversación',
    ],

    'email' => [
        'footer_note' => 'Este correo fue enviado por :app. Si tienes dudas, responde directamente a este mensaje.',
    ],

    // Inbox UI — Fase 1: panel izquierdo (sidebar + toolbar de la lista).
    'inbox' => [
        'title' => 'Conversaciones',
        'team_inbox' => 'Bandeja de equipo',
        'online_active' => 'En línea · :count activas',
        'new_conversation' => 'Nueva conversación',
        'change_availability' => 'Cambiar disponibilidad',
        'views' => 'Vistas',
        'kanban_view' => 'Vista kanban',
        'save_current_view' => 'Guardar vista actual',

        // Navegación de vistas rápidas
        'unread' => 'Sin leer',
        'all' => 'Todas',
        'mine' => 'Mías',
        'urgent' => 'Urgentes',
        'in_bot' => 'En bot',
        'in_bot_title' => 'Conversaciones que el chatbot está atendiendo',
        'pending' => 'En espera',
        'closed' => 'Cerradas',
        'blocked' => 'Bloqueados',
        'blocked_title' => 'Contactos bloqueados',
        'spam' => 'Spam',
        'spam_title' => 'Marcadas como spam',
        'deleted' => 'Eliminadas',
        'deleted_title' => 'Conversaciones eliminadas (papelera)',

        // Secciones del sidebar
        'inboxes' => 'Bandejas',
        'teams' => 'Equipos',
        'no_teams' => 'Sin equipos',
        'tags' => 'Etiquetas',
        'manage_tags' => 'Gestionar etiquetas',
        'no_tags' => 'Sin etiquetas',
        'my_views' => 'Mis vistas',
        'delete_view' => 'Eliminar vista',
        'desktop_notifications' => 'Notificaciones de escritorio',

        // Toolbar de la lista
        'search_conversations' => 'Buscar conversaciones…',
        'filter_conversations' => 'Filtrar conversaciones',
        'sort_conversations' => 'Ordenar conversaciones',
        'filters' => 'Filtros',
        'sort' => 'Ordenar',
        'more_options' => 'Más opciones',
        'sort_by' => 'Ordenar por',
        'sort_newest' => 'Más reciente',
        'sort_oldest' => 'Más antiguo',
        'sort_priority' => 'Prioridad',
        'sort_unassigned' => 'Sin asignar',
        'sort_unread' => 'Sin leer',
    ],

];
