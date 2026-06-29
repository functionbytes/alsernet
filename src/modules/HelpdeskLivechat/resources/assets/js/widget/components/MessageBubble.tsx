import React from 'react';
import { AudioPlayer } from './AudioPlayer';
import { type LightboxImage } from './ImageLightbox';
import type { Message, MessageAttachment } from '../hooks/useConversationMessages';

interface MessageBubbleProps {
    message: Message;
    primaryColor?: string;
    showAvatar?: boolean;
    avatarInitial?: string;
    onOpenLightbox: (url: string) => void;
    animationDelay?: string;
}

function isImageAttachment(a: MessageAttachment): boolean {
    const mime = (a.type ?? '').toLowerCase();
    const ext = (a.name ?? '').toLowerCase().split('.').pop() ?? '';
    return mime.startsWith('image/') || ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
}

function isAudioAttachment(a: MessageAttachment): boolean {
    const mime = (a.type ?? '').toLowerCase();
    const name = (a.name ?? '').toLowerCase();
    const ext = name.split('.').pop() ?? '';
    const isVoiceRecording = name.startsWith('voz-') && (ext === 'webm' || ext === 'ogg');
    return isVoiceRecording
        || mime.startsWith('audio/')
        || ['mp3', 'ogg', 'wav', 'oga', 'm4a', 'aac'].includes(ext)
        || (ext === 'webm' && (!mime || mime.startsWith('audio/')));
}

function isVideoAttachment(a: MessageAttachment): boolean {
    const mime = (a.type ?? '').toLowerCase();
    const ext = (a.name ?? '').toLowerCase().split('.').pop() ?? '';
    return !isAudioAttachment(a) && (mime.startsWith('video/') || ['mp4', 'mov', 'avi', 'mkv'].includes(ext));
}

function AttachmentItem({
    attachment,
    isUser,
    onOpenLightbox,
}: {
    attachment: MessageAttachment;
    isUser: boolean;
    onOpenLightbox: (url: string) => void;
}) {
    const mime = (attachment.type ?? '').toLowerCase();
    const sizeKb = (attachment.size / 1024).toFixed(1);

    if (isImageAttachment(attachment)) {
        return (
            <button
                type="button"
                className="wgt-attachment-image"
                title={attachment.name}
                onClick={() => onOpenLightbox(attachment.url)}
            >
                <img src={attachment.url} alt={attachment.name} loading="lazy" />
            </button>
        );
    }

    if (isAudioAttachment(attachment)) {
        return (
            <AudioPlayer
                src={attachment.url}
                mime={mime}
                title={attachment.name}
                isUser={isUser}
            />
        );
    }

    if (isVideoAttachment(attachment)) {
        return (
            <video controls preload="metadata" className="wgt-attachment-video" title={attachment.name}>
                <source src={attachment.url} type={mime || undefined} />
            </video>
        );
    }

    return (
        <a
            href={attachment.url}
            target="_blank"
            rel="noopener noreferrer"
            download={attachment.name}
            className={`wgt-attachment-link${isUser ? ' is-user-bubble' : ' is-agent-bubble'}`}
        >
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11zM8 15.01l1.41 1.41L11 14.84V19h2v-4.16l1.59 1.59L16 15.01 12.01 11 8 15.01z" />
            </svg>
            <div className="wgt-attachment-meta">
                <p className="wgt-attachment-name">{attachment.name}</p>
                <p className="wgt-attachment-size">{sizeKb} KB</p>
            </div>
            <svg className="wgt-attachment-dl" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z" />
            </svg>
        </a>
    );
}

function BubbleBody({ message, onOpenLightbox }: { message: Message; onOpenLightbox: (url: string) => void }) {
    const isUser = message.author === 'user';
    const trimmed = (message.content ?? '').trim();
    const previewUrl = message.linkPreview?.url ?? '';
    const isJustTheUrl = !!previewUrl && (
        trimmed === previewUrl ||
        trimmed.replace(/\/$/, '') === previewUrl.replace(/\/$/, '')
    );

    return (
        <>
            {trimmed && !isJustTheUrl ? <p>{message.content}</p> : null}

            {message.linkPreview && (
                <a
                    href={message.linkPreview.url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="wgt-link-preview"
                >
                    {message.linkPreview.image && (
                        <img
                            src={message.linkPreview.image}
                            alt={message.linkPreview.title || ''}
                            loading="lazy"
                            className="wgt-link-preview-img"
                        />
                    )}
                    <div className="wgt-link-preview-body">
                        {message.linkPreview.title && (
                            <p className="wgt-link-preview-title">{message.linkPreview.title}</p>
                        )}
                        {message.linkPreview.description && (
                            <p className="wgt-link-preview-desc">{message.linkPreview.description}</p>
                        )}
                        <div className="wgt-link-preview-meta">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z" />
                            </svg>
                            <span>
                                {message.linkPreview.site || (() => {
                                    try { return new URL(message.linkPreview!.url).hostname; } catch { return message.linkPreview!.url; }
                                })()}
                            </span>
                        </div>
                    </div>
                </a>
            )}

            {message.attachments && message.attachments.length > 0 && (
                <div className="wgt-attachment-list">
                    {message.attachments.map((attachment, idx) => (
                        <AttachmentItem
                            key={idx}
                            attachment={attachment}
                            isUser={isUser}
                            onOpenLightbox={onOpenLightbox}
                        />
                    ))}
                </div>
            )}
        </>
    );
}

export function MessageBubble({
    message,
    primaryColor,
    showAvatar,
    avatarInitial,
    onOpenLightbox,
    animationDelay,
}: MessageBubbleProps) {
    const isUser = message.author === 'user';
    const isAgent = message.author === 'agent';

    return (
        <div
            className={`wgt-message-row${isUser ? ' is-user' : ''}${message.author === 'bot' ? ' is-bot' : ''} wgt-fade-in`}
            style={animationDelay ? { animationDelay } : undefined}
        >
            {!isUser && showAvatar && (
                <div className="wgt-avatar" style={{ backgroundColor: primaryColor }}>
                    {avatarInitial}
                </div>
            )}

            <div className={`wgt-bubble-wrap${isUser ? ' is-user' : ' is-agent'}`}>
                <div
                    className={`wgt-bubble${isUser ? ' is-user' : ' is-agent'}`}
                    style={isUser ? { backgroundColor: primaryColor } : undefined}
                >
                    <BubbleBody message={message} onOpenLightbox={onOpenLightbox} />
                </div>

                <div className="wgt-row wgt-gap-1 wgt-bubble-time">
                    <span>
                        {message.timestamp.toLocaleTimeString('en-US', {
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true,
                        })}
                    </span>
                    {isUser && message.status && (
                        <span>
                            {message.status === 'sending' && '⏳'}
                            {message.status === 'sent' && '✓'}
                            {message.status === 'delivered' && '✓✓'}
                        </span>
                    )}
                </div>
            </div>

            {isUser && showAvatar && (
                <div className="wgt-avatar is-user">U</div>
            )}
        </div>
    );
}

/**
 * Extracts gallery-eligible images from message list for the lightbox.
 */
export function buildLightboxImages(messages: Message[]): LightboxImage[] {
    const list: LightboxImage[] = [];
    for (const m of messages) {
        for (const a of m.attachments ?? []) {
            if (isImageAttachment(a)) {
                list.push({ url: a.url, name: a.name });
            }
        }
    }
    return list;
}
