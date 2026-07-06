<?php

return [
    'messages' => [
        'message_from_support' => 'Message from support',

        // Tickets
        'ticket_created' => 'Ticket created successfully. Number: :number',
        'ticket_updated' => 'Ticket updated successfully.',
        'ticket_deleted' => 'Ticket deleted successfully.',
        'ticket_closed' => 'Ticket closed successfully.',
        'ticket_resolved' => 'Ticket marked as resolved.',
        'ticket_reopened' => 'Ticket reopened successfully.',
        'ticket_archived' => 'Ticket archived successfully.',
        'ticket_unarchived' => 'Ticket unarchived successfully.',
        'ticket_restored' => 'Ticket restored successfully.',
        'ticket_permanently_deleted' => 'Ticket permanently deleted.',
        'message_sent' => 'Message sent successfully.',
        'message_updated' => 'Message updated successfully.',
        'message_deleted' => 'Message deleted successfully.',
        'ticket_assigned' => 'Ticket assigned successfully.',
        'ticket_unassigned' => 'Assignment removed successfully.',

        // Conversations
        'conversation_created' => 'Conversation created successfully',
        'conversation_updated' => 'Conversation updated',
        'conversation_deleted' => 'Conversation deleted',
        'conversation_restored' => 'Conversation restored',
        'conversation_force_deleted' => 'Conversation permanently deleted',
        'conversation_closed' => 'Conversation closed',
        'conversation_reopened' => 'Conversation reopened',
        'conversation_archived' => 'Conversation archived',
        'conversation_unarchived' => 'Conversation unarchived',
        'conversation_message_sent' => 'Message sent successfully',
        'conversation_message_sent_and_closed' => 'Message sent and conversation closed',

        // Merge
        'merge_self' => 'You cannot merge a conversation with itself.',
        'merge_different_customer' => 'The conversations do not belong to the same contact.',
        'merge_success' => 'Conversations merged successfully.',

        // Tags
        'tag_added' => 'Tag added',
        'tag_removed' => 'Tag removed',
        'tags_updated' => 'Tags updated',

        // AJAX field updates
        'priority_updated' => 'Priority updated',
        'assignment_updated' => 'Assignment updated',

        // Customers
        'customer_banned' => "Customer ':name' suspended successfully.",
        'customer_unbanned' => "Customer ':name' reactivated successfully.",
        'customer_deleted' => "Customer ':name' deleted successfully.",
        'customer_created' => "Customer ':name' created successfully.",
        'customer_updated' => "Customer ':name' updated successfully.",

        // Ticket merge, watch, SLA
        'ticket_merged' => 'Ticket #:source merged into #:target successfully.',
        'ticket_watched' => 'You are now watching this ticket.',
        'ticket_unwatched' => 'You have stopped watching this ticket.',
        'sla_paused' => 'SLA paused successfully.',
        'sla_resumed' => 'SLA resumed successfully.',
        'rating_submitted' => 'Thank you for your rating!',
        'recurring_ticket_created' => 'Recurring ticket created successfully.',

        // Ticket templates
        'template_applied' => 'Template applied successfully.',
        'template_created' => 'Template created successfully.',
        'template_updated' => 'Template updated successfully.',
        'template_deleted' => 'Template deleted successfully.',

        // Recurring tickets
        'recurring_created' => 'Recurring task created successfully.',
        'recurring_updated' => 'Recurring task updated successfully.',
        'recurring_deleted' => 'Recurring task deleted successfully.',
        'recurring_toggled' => 'Recurring task status updated.',

        // Escalation
        'ticket_escalated' => 'Ticket escalated to :priority priority.',
    ],

    'labels' => [
        'support_team' => 'Support Team',
        'conversation_ref' => 'Conversation reference',
    ],

    'email' => [
        'footer_note' => 'This email was sent by :app. If you have questions, just reply to this message.',
    ],

    // Inbox UI — Phase 1: left panel (sidebar + list toolbar).
    'inbox' => [
        'title' => 'Conversations',
        'team_inbox' => 'Team inbox',
        'online_active' => 'Online · :count active',
        'new_conversation' => 'New conversation',
        'change_availability' => 'Change availability',
        'views' => 'Views',
        'kanban_view' => 'Kanban view',
        'save_current_view' => 'Save current view',

        // Quick view navigation
        'unread' => 'Unread',
        'all' => 'All',
        'mine' => 'Mine',
        'urgent' => 'Urgent',
        'in_bot' => 'In bot',
        'in_bot_title' => 'Conversations the chatbot is handling',
        'pending' => 'On hold',
        'closed' => 'Closed',
        'blocked' => 'Blocked',
        'blocked_title' => 'Blocked contacts',
        'spam' => 'Spam',
        'spam_title' => 'Marked as spam',
        'deleted' => 'Deleted',
        'deleted_title' => 'Deleted conversations (trash)',

        // Sidebar sections
        'inboxes' => 'Inboxes',
        'teams' => 'Teams',
        'no_teams' => 'No teams',
        'tags' => 'Tags',
        'manage_tags' => 'Manage tags',
        'no_tags' => 'No tags',
        'my_views' => 'My views',
        'delete_view' => 'Delete view',
        'desktop_notifications' => 'Desktop notifications',

        // List toolbar
        'search_conversations' => 'Search conversations…',
        'filter_conversations' => 'Filter conversations',
        'sort_conversations' => 'Sort conversations',
        'filters' => 'Filters',
        'sort' => 'Sort',
        'more_options' => 'More options',
        'sort_by' => 'Sort by',
        'sort_newest' => 'Newest',
        'sort_oldest' => 'Oldest',
        'sort_priority' => 'Priority',
        'sort_unassigned' => 'Unassigned',
        'sort_unread' => 'Unread',
    ],

];
