import React from 'react';
import { type BotMessage, type RecommendationProduct } from '../widget-store';
import { RecommendationsCard } from './RecommendationsCard';
import { MessageBubble } from './MessageBubble';
import type { Message } from '../hooks/useConversationMessages';

interface MessageListProps {
    messages: Message[];
    botMessages: BotMessage[];
    recommendations: RecommendationProduct[];
    hasMoreMessages: boolean;
    isLoadingMore: boolean;
    offlineMessageEnabled?: boolean;
    offlineMessage?: string;
    showAvatars?: boolean;
    primaryColor?: string;
    avatarInitial?: string;
    messagesEndRef: React.RefObject<HTMLDivElement | null>;
    onOpenLightbox: (url: string) => void;
    onLoadMore: () => void;
}

export function MessageList({
    messages,
    botMessages,
    recommendations,
    hasMoreMessages,
    isLoadingMore,
    offlineMessageEnabled,
    offlineMessage,
    showAvatars,
    primaryColor,
    avatarInitial,
    messagesEndRef,
    onOpenLightbox,
    onLoadMore,
}: MessageListProps) {
    return (
        <div className="wgt-messages-area">
            {offlineMessageEnabled && offlineMessage && (
                <div className="wgt-offline-banner" role="status">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 1a11 11 0 1 0 11 11A11.013 11.013 0 0 0 12 1zm0 20a9 9 0 1 1 9-9 9.011 9.011 0 0 1-9 9zm0-13a1 1 0 0 0-1 1v5a1 1 0 0 0 2 0V9a1 1 0 0 0-1-1zm0 8a1 1 0 1 0 1 1 1 1 0 0 0-1-1z" />
                    </svg>
                    <span>{offlineMessage}</span>
                </div>
            )}

            {hasMoreMessages && (
                <div className="wgt-load-more-row">
                    <button
                        type="button"
                        className="wgt-load-more-btn"
                        onClick={onLoadMore}
                        disabled={isLoadingMore}
                    >
                        {isLoadingMore ? 'Cargando...' : 'Cargar mensajes anteriores'}
                    </button>
                </div>
            )}

            {messages.map((message, index) => (
                <MessageBubble
                    key={message.id}
                    message={message}
                    primaryColor={primaryColor}
                    showAvatar={showAvatars}
                    avatarInitial={avatarInitial}
                    onOpenLightbox={onOpenLightbox}
                    animationDelay={`${Math.min(index * 50, 500)}ms`}
                />
            ))}

            {botMessages.map((bm) => (
                <div key={bm.id} className="wgt-message-row wgt-fade-in" role="status">
                    <div className="wgt-avatar wgt-bot-avatar" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="currentColor" width={14} height={14}>
                            <path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7H3a7 7 0 0 1 7-7h1V5.73A2 2 0 0 1 10 4a2 2 0 0 1 2-2zM7.5 13a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm9 0a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zM3 21h18v-2H3v2z" />
                        </svg>
                    </div>
                    <div className="wgt-bubble-wrap is-agent">
                        <p className="wgt-bot-label">Asistente automatico</p>
                        <div className="wgt-bubble is-agent wgt-bubble-bot">
                            <p>{bm.text}</p>
                        </div>
                        <div className="wgt-row wgt-gap-1 wgt-bubble-time">
                            <span>
                                {new Date(bm.timestamp).toLocaleTimeString('en-US', {
                                    hour: 'numeric',
                                    minute: '2-digit',
                                    hour12: true,
                                })}
                            </span>
                        </div>
                    </div>
                </div>
            ))}

            {recommendations.length > 0 && (
                <div className="wgt-message-row wgt-fade-in">
                    <RecommendationsCard products={recommendations} primaryColor={primaryColor} />
                </div>
            )}

            <div ref={messagesEndRef} />
        </div>
    );
}
