/**
 * MessageList - Scrollable list of messages
 */

import React, { useEffect, useRef } from 'react';
import type { Message, Customer } from '../types';
import { MessageBubble } from './MessageBubble';

interface MessageListProps {
    messages: Message[];
    customer: Customer | null;
    isTyping: boolean;
    color: string;
}

export function MessageList({ messages, customer, isTyping, color }: MessageListProps) {
    const messagesEndRef = useRef<HTMLDivElement>(null);

    // Auto-scroll to bottom when new messages arrive
    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages, isTyping]);

    if (messages.length === 0 && !isTyping) {
        return (
            <div className="flex-1 flex items-center justify-center p-6">
                <div className="text-center text-gray-500">
                    <i className="fas fa-comments text-4xl mb-3 opacity-50"></i>
                    <p className="text-sm">No messages yet</p>
                    <p className="text-xs mt-1">Start the conversation!</p>
                </div>
            </div>
        );
    }

    return (
        <div className="flex-1 overflow-y-auto px-4 py-4">
            {messages.map((message) => (
                <MessageBubble
                    key={message.id}
                    message={message}
                    isOwnMessage={message.sender.type === 'Customer'}
                    color={color}
                />
            ))}

            {/* Typing indicator */}
            {isTyping && (
                <div className="flex mb-4">
                    <div className="bg-gray-100 rounded-2xl rounded-bl-none px-4 py-3">
                        <div className="flex space-x-1">
                            <div
                                className="w-2 h-2 bg-gray-400 rounded-full animate-bounce"
                                style={{ animationDelay: '0ms' }}
                            ></div>
                            <div
                                className="w-2 h-2 bg-gray-400 rounded-full animate-bounce"
                                style={{ animationDelay: '150ms' }}
                            ></div>
                            <div
                                className="w-2 h-2 bg-gray-400 rounded-full animate-bounce"
                                style={{ animationDelay: '300ms' }}
                            ></div>
                        </div>
                    </div>
                </div>
            )}

            <div ref={messagesEndRef} />
        </div>
    );
}
