<?php

return [
    'messages' => [
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

    'livechat' => [
        'title' => 'LiveChat Configuration - Helpdesk',
        'page_title' => 'Live chat widget configuration',
        'page_description' => 'Customize the appearance and behavior of the chat widget for your website',

        'tabs' => [
            'widget' => 'Widget',
            'timeouts' => 'Timeouts',
            'install' => 'Installation',
            'security' => 'Security',
        ],

        'sections' => [
            'home_screen' => 'Home Screen',
            'chat_screen' => 'Chat Screen',
            'launcher' => 'Launcher',
            'style' => 'Styles and Colors',
            'additional_options' => 'Additional Options',
            'feature_toggles' => 'Enable/Disable Features',
            'installation' => 'Widget Installation',
            'timeouts_config' => 'Timeout Configuration',
        ],

        'fields' => [
            'show_avatars' => 'Show Avatars',
            'show_avatars_help' => 'Active agent profile pictures will be visible on the home screen',
            'show_help_center' => 'Show Help Center',
            'show_help_center_help' => 'Display a direct link to the help center on the home screen',
            'hide_suggested_articles' => 'Hide Suggested Articles',
            'hide_suggested_articles_help' => 'Do not automatically show recommended articles on the home screen',
            'show_tickets_section' => 'Show Tickets Section',
            'show_tickets_section_help' => 'Allow customers to view their active tickets from the home screen',
            'enable_send_message' => 'Send Message',
            'enable_send_message_help' => 'Allow customers to send messages to support',
            'enable_create_ticket' => 'Create Ticket',
            'enable_create_ticket_help' => 'Allow customers to create support tickets',
            'enable_search_help' => 'Search Help Center',
            'enable_search_help_help' => 'Allow customers to search the help center',
            'welcome_message' => 'Welcome Message',
            'welcome_message_help' => 'First message customers see when starting the chat',
            'input_placeholder' => 'Input Placeholder',
            'input_placeholder_help' => 'Help text that appears in the message input field',
            'no_agents_message' => 'Message: No Agents Available',
            'no_agents_message_help' => 'Message when all agents are offline',
            'queue_message' => 'Message: Customer in Queue',
            'queue_message_help' => 'Message when the customer is waiting in queue (use :number and :minutes as variables)',
            'position' => 'Widget Position',
            'position_help' => 'Corner of the screen where the chat button appears',
            'side_spacing' => 'Side Spacing (px)',
            'side_spacing_help' => 'Distance from the side edge of the screen',
            'bottom_spacing' => 'Bottom Spacing (px)',
            'bottom_spacing_help' => 'Distance from the bottom edge of the screen',
            'hide_launcher' => 'Hide Launcher by Default',
            'hide_launcher_help' => 'The chat button will be hidden by default and must be shown manually via API',
            'primary_color' => 'Primary Color',
            'primary_color_help' => 'Color of the header and main buttons (used in light and dark mode)',
            'secondary_color' => 'Secondary Color',
            'secondary_color_help' => 'Color of text and secondary elements (adapts to dark:bg-gray-800)',
            'secondary_color_note' => 'Color Note: The secondary color should contrast well in dark mode (dark:bg-gray-800). Light colors like white (#ffffff) or light tones are recommended for better readability.',
            'secondary_color_preview' => 'Dark mode preview',
            'secondary_color_preview_help' => 'Toggle to show or hide the dark mode preview box with your current color configuration',
            'header_title' => 'Header Title',
            'header_title_help' => 'Title that appears at the top of the widget',
            'show_timestamps' => 'Show Timestamps',
            'show_timestamps_help' => 'Display the time sent with each chat message',
            'typing_indicator' => 'Typing Indicator',
            'typing_indicator_help' => 'Show "agent is typing..." when the agent responds',
            'sound_notifications' => 'Sound Notifications',
            'sound_notifications_help' => 'Play a sound when new messages arrive',
            'enable_email_transcripts' => 'Allow Download Transcript',
            'enable_email_transcripts_help' => 'Customers can receive the complete chat history by email',
            'enable_auto_transfer' => 'When agent does not respond for',
            'auto_transfer_minutes' => 'minutes',
            'auto_transfer_help' => 'Transfer the customer to another available agent. If the chat is in a group with manual assignment, the chat will be queued instead.',
            'enable_auto_inactive' => 'When there are no messages for',
            'auto_inactive_minutes' => 'minutes',
            'auto_inactive_help' => 'Mark the chat as inactive. Inactive chats do not count toward agent concurrent chat limits.',
            'enable_auto_close' => 'Auto-close after',
            'auto_close_minutes' => 'minutes',
            'auto_close_help' => 'Automatically close the chat. Customers can reopen closed chats by sending a new message to that chat.',
            'trusted_domains' => 'Trusted Domains',
            'trusted_domains_help' => 'Comma-separated list of allowed domains where the widget can be embedded',
            'enforce_identity_verification' => 'Require Identity Verification',
            'enforce_identity_verification_help' => 'Customers must verify their identity before sending messages',
        ],

        'buttons' => [
            'save_changes' => 'Save Changes',
            'back' => 'Back',
        ],

        'messages' => [
            'success' => 'Configuration updated successfully',
            'error' => 'Error updating configuration',
        ],

        'install' => [
            'title' => 'Widget Installation',
            'instructions' => 'Instructions: Copy and paste this code before the &lt;/body&gt; tag on each page where you want the widget to appear.',
            'basic_code' => 'Basic code',
        ],

        'positions' => [
            'bottom-right' => 'Bottom Right',
            'bottom-left' => 'Bottom Left',
            'top-right' => 'Top Right',
            'top-left' => 'Top Left',
        ],
    ],
];
