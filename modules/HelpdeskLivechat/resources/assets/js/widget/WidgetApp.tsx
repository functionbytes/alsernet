import React, { useEffect } from 'react';
import { MemoryRouter, Routes, Route } from 'react-router-dom';
import { SettingsPreviewListener } from './SettingsPreviewListener';
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
                    </div>
                    <WidgetNavigation />
                </div>
            </MemoryRouter>
        </>
    );
}
