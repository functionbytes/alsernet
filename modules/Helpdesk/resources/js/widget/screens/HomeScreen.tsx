import React from 'react';
import { useWidgetStore } from '../widget-store';
import { useTranslation } from '../i18n/useLanguage';
import { NewChatCard } from '../components/NewChatCard';
import { NewTicketCard } from '../components/NewTicketCard';
import { HelpCard } from '../components/HelpCard';

export function HomeScreen() {
    const settings = useWidgetStore(state => state.settings);
    const t = useTranslation();

    console.log('🏠 HomeScreen rendering with backups:', {
        enable_send_message: settings.enable_send_message,
        enable_create_ticket: settings.enable_create_ticket,
        enable_search_help: settings.enable_search_help,
        show_help_center: settings.show_help_center,
        show_tickets_section: settings.show_tickets_section,
    });

    return (
        <div className="flex flex-col h-full min-h-0 overflow-y-auto overflow-x-hidden bg-gray-50 opacity-0" style={{ animation: 'fadeIn 0.3s ease-out forwards' }}>
            {/* Header with Greeting */}
            <div className="relative isolate">
                {/* Background Gradient */}
                <div
                    className="absolute left-0 right-0 top-0 h-80"
                    style={{
                        background: `linear-gradient(to bottom right, ${settings.primary_color}, ${settings.primary_color}dd)`
                    }}
                />

                {/* Content */}
                <div className="relative z-10 px-6 py-8">
                    {/* Top Bar with Logo */}
                    <div className="mb-8 flex items-center justify-between">
                        <div className="text-white font-bold text-lg">
                            {settings.header_title}
                        </div>
                    </div>

                    {/* Greeting */}
                    <div className="text-white">
                        <h1 className="text-3xl font-bold leading-tight mb-2">
                            {t('home.greeting')}
                        </h1>
                        <p className="text-white/90 text-lg">
                            {t('home.greeting_message')}
                        </p>
                    </div>
                </div>
            </div>

            {/* Cards Section */}
            <div className="relative z-20 px-6 pb-6 space-y-4 -mt-4">
                {/* New Chat Card */}
                {(() => {
                    const showNewChat = settings.enable_send_message;
                    console.log('🔍 NewChatCard condition:', { enable_send_message: settings.enable_send_message, showing: showNewChat });
                    return showNewChat && <NewChatCard />;
                })()}

                {/* New Ticket Card */}
                {(() => {
                    const showNewTicket = settings.enable_create_ticket && settings.show_tickets_section;
                    console.log('🔍 NewTicketCard condition:', { enable_create_ticket: settings.enable_create_ticket, show_tickets_section: settings.show_tickets_section, showing: showNewTicket });
                    return showNewTicket && <NewTicketCard />;
                })()}

                {/* Help Center Card */}
                {(() => {
                    const showHelp = settings.enable_search_help && settings.show_help_center;
                    console.log('🔍 HelpCard condition:', { enable_search_help: settings.enable_search_help, show_help_center: settings.show_help_center, showing: showHelp });
                    return showHelp && <HelpCard />;
                })()}

                {/* Custom Links */}
                <div className="pt-4 text-center">
                    <a
                        href="#"
                        className="text-sm text-gray-600 hover:text-primary-600 transition-colors"
                    >
                        {t('home.view_all_articles')}
                    </a>
                </div>
            </div>

            {/* Powered By Footer */}
            <div className="mt-auto px-6 py-4 text-center">
                <p className="text-xs text-gray-500">
                    {t('powered_by')}
                </p>
            </div>
        </div>
    );
}
