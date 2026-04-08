import React, { useEffect } from 'react';
import { BrowserRouter, MemoryRouter, Routes, Route } from 'react-router-dom';
import { SettingsPreviewListener } from './SettingsPreviewListener';
import { HomeScreen } from './screens/HomeScreen';
import { ConversationScreen } from './screens/ConversationScreen';
import { HelpScreen } from './screens/HelpScreen';
import { ArticleDetailScreen } from './screens/ArticleDetailScreen';
import { NewTicketScreen } from './screens/NewTicketScreen';
import { PreChatFormScreen } from './screens/PreChatFormScreen';
import { PostChatFormScreen } from './screens/PostChatFormScreen';
import { MessagesScreen } from './screens/MessagesScreen';
import { ChatPageScreen } from './screens/ChatPageScreen';
import { WidgetNavigation } from './components/WidgetNavigation';
import { useWidgetStore } from './widget-store';

interface WidgetAppProps {
    isPreview: boolean;
    isInline?: boolean;
    conversationId?: string;
}

export function WidgetApp({ isPreview, isInline, conversationId }: WidgetAppProps) {
    console.log('🚀 WidgetApp rendering', { isPreview, isInline, conversationId });

    const fetchSettings = useWidgetStore(state => state.fetchSettings);

    // Fetch backups from backend on mount (but not in preview mode)
    useEffect(() => {
        if (!isPreview) {
            console.log('📡 Fetching widget backups from backend...');
            fetchSettings();
        } else {
            console.log('👁️ Preview mode: Settings will be received via postMessage');
        }
    }, [isPreview, fetchSettings]);

    // Apply appropriate styling based on mode
    const containerStyle = isInline
        ? { width: '100%', height: '100vh', maxWidth: 'none', maxHeight: 'none' }
        : { width: '100%', height: '100%' }; // Use 100% height for launcher/preview mode

    console.log(isInline ? '📱 Rendering inline mode with BrowserRouter' : '🏠 Rendering launcher/preview mode with MemoryRouter');

    // Use MemoryRouter for launcher/preview mode (widget in a div, independent routing)
    // Use BrowserRouter for inline mode (full page widget, uses browser URL)
    const Router = isInline ? BrowserRouter : MemoryRouter;

    // Only use basename when in inline mode on /lc/widget path
    const isOnWidgetPath = isInline && window.location.pathname.startsWith('/lc/widget');
    const routerProps = isOnWidgetPath ? { basename: '/lc/widget' } : {};

    const routes = (
        <div className="widget-container flex flex-col" style={containerStyle}>
            <div className="flex-1 min-h-0 overflow-auto">
                <Routes>
                    <Route path="/" element={<HomeScreen />} />
                    <Route path="/conversation" element={<ConversationScreen />} />
                    <Route path="/help" element={<HelpScreen />} />
                    <Route path="/help/article/:articleId" element={<ArticleDetailScreen />} />
                    <Route path="/tickets/new" element={<NewTicketScreen />} />
                    <Route path="/pre-chat" element={<PreChatFormScreen />} />
                    <Route path="/post-chat" element={<PostChatFormScreen />} />
                    <Route path="/messages" element={<MessagesScreen />} />
                    <Route path="/chat-page" element={<ChatPageScreen conversationId={conversationId} />} />
                </Routes>
            </div>
            <WidgetNavigation />
        </div>
    );

    return (
        <>
            {isPreview && <SettingsPreviewListener />}
            <Router {...routerProps}>
                {routes}
            </Router>
        </>
    );
}
