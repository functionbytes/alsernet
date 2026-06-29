/**
 * ChatWindow - Main chat interface window
 */

import React from 'react';
import type { WidgetState } from '../types';
import { MessageList } from './MessageList';
import { MessageInput } from './MessageInput';
import { PreChatForm } from './PreChatForm';

interface ChatWindowProps {
    state: WidgetState;
    color: string;
    position: 'bottom-right' | 'bottom-left';
    welcomeTitle?: string;
    welcomeTagline?: string;
    onClose: () => void;
    onSendMessage: (content: string) => void;
    onUploadFile: (file: File) => void;
    onTyping: (isTyping: boolean) => void;
    onSubmitPreChat: (data: any) => void;
}

export function ChatWindow({
    state,
    color,
    position,
    welcomeTitle,
    welcomeTagline,
    onClose,
    onSendMessage,
    onUploadFile,
    onTyping,
    onSubmitPreChat,
}: ChatWindowProps) {
    const positionClasses =
        position === 'bottom-left'
            ? 'left-6 bottom-24'
            : 'right-6 bottom-24';

    return (
        <div
            className={`fixed ${positionClasses} z-[9999] w-[380px] h-[600px] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden transition-all duration-300`}
            style={{
                maxHeight: 'calc(100vh - 120px)',
            }}
        >
            {/* Header */}
            <div
                className="flex items-center justify-between p-4 text-white"
                style={{ backgroundColor: color }}
            >
                <div className="flex items-center space-x-3">
                    <div className="w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i className="fas fa-headset text-lg"></i>
                    </div>
                    <div>
                        <h3 className="font-semibold text-sm">Customer Support</h3>
                        <p className="text-xs opacity-90">
                            {state.agentsOnline ? 'Online' : 'We\'ll reply soon'}
                        </p>
                    </div>
                </div>

                <button
                    onClick={onClose}
                    className="w-8 h-8 flex items-center justify-center hover:bg-white hover:bg-opacity-20 rounded-full transition-colors"
                    aria-label="Close chat"
                >
                    <i className="fas fa-times"></i>
                </button>
            </div>

            {/* Content */}
            {state.showPreChatForm ? (
                <PreChatForm
                    onSubmit={onSubmitPreChat}
                    color={color}
                    welcomeTitle={welcomeTitle}
                    welcomeTagline={welcomeTagline}
                />
            ) : (
                <>
                    <MessageList
                        messages={state.messages}
                        customer={state.customer}
                        isTyping={state.isTyping}
                        color={color}
                    />

                    <MessageInput
                        onSendMessage={onSendMessage}
                        onUploadFile={onUploadFile}
                        onTyping={onTyping}
                        color={color}
                        disabled={!state.conversationId}
                    />
                </>
            )}

            {/* Footer branding */}
            <div className="px-4 py-2 text-center text-xs text-gray-500 border-t border-gray-100">
                Powered by{' '}
                <span className="font-semibold" style={{ color }}>
                    Chat
                </span>
            </div>
        </div>
    );
}
