import React, { useEffect } from 'react';
import { MemoryRouter, Routes, Route, useNavigate } from 'react-router-dom';
import { SettingsPreviewListener } from './SettingsPreviewListener';
import { ErrorBoundary } from './components/ErrorBoundary';
import { HomeScreen } from './screens/HomeScreen';
import { ConversationScreen } from './screens/ConversationScreen';
import { HelpScreen } from './screens/HelpScreen';
import { ArticleDetailScreen } from './screens/ArticleDetailScreen';
import { NewTicketScreen } from './screens/NewTicketScreen';
import { TicketListScreen } from './screens/TicketListScreen';
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

/**
 * The routed screens, wrapped in an ErrorBoundary so a crash in one screen
 * shows a recoverable fallback (with the bottom nav still usable) instead of
 * tearing down the whole widget. Lives inside the router so the boundary can
 * navigate home on reset.
 */
function WidgetScreens({ conversationId }: { conversationId?: string }) {
    const navigate = useNavigate();

    return (
        <ErrorBoundary onReset={() => navigate('/')}>
            <Routes>
                <Route path="/" element={<HomeScreen />} />
                <Route path="/conversation" element={<ConversationScreen />} />
                <Route path="/help" element={<HelpScreen />} />
                <Route path="/help/category/:categoryId" element={<HelpScreen />} />
                <Route path="/help/article/:articleId" element={<ArticleDetailScreen />} />
                <Route path="/tickets/new" element={<NewTicketScreen />} />
                <Route path="/tickets" element={<TicketListScreen />} />
                <Route path="/pre-chat" element={<PreChatFormScreen />} />
                <Route path="/post-chat" element={<PostChatFormScreen />} />
                <Route path="/messages" element={<MessagesScreen />} />
                <Route path="/chat-page" element={<ChatPageScreen conversationId={conversationId} />} />
            </Routes>
        </ErrorBoundary>
    );
}

export function WidgetApp({ isPreview, isInline, conversationId }: WidgetAppProps) {
    const fetchSettings = useWidgetStore(state => state.fetchSettings);

    useEffect(() => {
        if (!isPreview) {
            fetchSettings();
        }
    }, [isPreview, fetchSettings]);

    const containerStyle = isInline
        ? { width: '100%', height: '100vh', maxWidth: 'none', maxHeight: 'none' }
        : { width: '100%', height: '100%' };

    return (
        <>
            {isPreview && <SettingsPreviewListener />}
            <MemoryRouter>
                <div className="wgt-container" style={containerStyle}>
                    <div className="wgt-flex-1 wgt-min-w-0 wgt-overflow-y">
                        <WidgetScreens conversationId={conversationId} />
                    </div>
                    <WidgetNavigation />
                </div>
            </MemoryRouter>
        </>
    );
}
