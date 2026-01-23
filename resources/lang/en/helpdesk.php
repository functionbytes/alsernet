<?php

return [
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
