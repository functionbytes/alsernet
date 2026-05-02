/**
 * Widget - Root component that orchestrates the entire widget
 */

import React, { useEffect } from 'react';
import type { WidgetConfig } from './types';
import { useWidget } from './hooks/useWidget';
import { WidgetButton } from './components/WidgetButton';
import { ChatWindow } from './components/ChatWindow';

interface WidgetProps {
    config: WidgetConfig;
}

export function Widget({ config }: WidgetProps) {
    const {
        state,
        initConversation,
        sendMessage,
        uploadFile,
        toggleWidget,
        showPreChatForm,
        setTyping,
    } = useWidget(config);

    const widgetColor = config.widgetColor || '#1f93ff';
    const position = config.position || 'bottom-right';

    // Auto-show pre-chat form if no active conversation
    useEffect(() => {
        if (!state.conversationId && !state.showPreChatForm) {
            // Optionally auto-show pre-chat form
            // showPreChatForm();
        }
    }, [state.conversationId, state.showPreChatForm]);

    const handlePreChatSubmit = async (customerData: any) => {
        await initConversation(customerData);
    };

    const handleToggle = () => {
        if (!state.conversationId && !state.isOpen) {
            // Show pre-chat form when opening for the first time
            showPreChatForm();
        }
        toggleWidget();
    };

    return (
        <>
            {/* Chat Window */}
            {state.isOpen && (
                <ChatWindow
                    state={state}
                    color={widgetColor}
                    position={position}
                    welcomeTitle={config.welcomeTitle}
                    welcomeTagline={config.welcomeTagline}
                    onClose={toggleWidget}
                    onSendMessage={sendMessage}
                    onUploadFile={uploadFile}
                    onTyping={setTyping}
                    onSubmitPreChat={handlePreChatSubmit}
                />
            )}

            {/* Widget Button */}
            <WidgetButton
                isOpen={state.isOpen}
                unreadCount={state.unreadCount}
                color={widgetColor}
                position={position}
                onClick={handleToggle}
            />
        </>
    );
}
