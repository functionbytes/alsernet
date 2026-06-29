import { useState, useRef, useCallback, useEffect } from 'react';
import { echo, onWsStateChange } from '../echo';
import { apiUrl, conversationAuthHeaders } from '../api';

export interface MessageAttachment {
    url: string;
    name: string;
    size: number;
    type: string;
}

interface LinkPreview {
    url: string;
    site?: string | null;
    title?: string | null;
    description?: string | null;
    image?: string | null;
    favicon?: string | null;
}

export interface Message {
    id: string;
    content: string;
    originalContent?: string;
    author: 'user' | 'agent' | 'bot';
    timestamp: Date;
    status?: 'sending' | 'sent' | 'delivered';
    attachments?: MessageAttachment[];
    linkPreview?: LinkPreview | null;
}

function parseApiMessage(msg: any): Message {
    return {
        id: msg.id.toString(),
        content: msg.body || msg.content,
        author: msg.message_type === 'outgoing' ? 'agent' : 'user',
        timestamp: new Date(msg.created_at),
        status: 'delivered',
        attachments: (msg.attachments || []).map((a: any) => ({
            url: a.url,
            name: a.name ?? a.file_name,
            size: a.size ?? 0,
            type: a.mime_type ?? a.type ?? '',
        })),
        linkPreview: msg.link_preview ?? null,
    };
}

function buildAuthParams(customerId: number | null, customerEmail: string): URLSearchParams {
    const p = new URLSearchParams();
    if (customerId) {
        p.append('customer_id', String(customerId));
    } else if (customerEmail) {
        p.append('customer_email', customerEmail);
    }
    return p;
}

function getCsrfToken(): string {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

interface UseConversationMessagesOptions {
    conversationId: string | null;
    customerId: number | null;
    customerEmail: string;
    welcomeMessage: string;
    soundEnabled: boolean;
    soundNotificationsEnabled: boolean;
    typingIndicatorEnabled: boolean;
    notificationSoundRef: React.RefObject<HTMLAudioElement | null>;
    onNewConversationId?: (id: string) => void;
}

interface UseConversationMessagesReturn {
    messages: Message[];
    setMessages: React.Dispatch<React.SetStateAction<Message[]>>;
    hasMoreMessages: boolean;
    isLoadingMore: boolean;
    isReconnecting: boolean;
    messagesEndRef: React.RefObject<HTMLDivElement | null>;
    loadMoreMessages: () => Promise<void>;
    scheduleMarkAsRead: (messageId: string) => void;
    typingTimerRef: React.RefObject<ReturnType<typeof setTimeout> | null>;
}

export function useConversationMessages({
    conversationId,
    customerId,
    customerEmail,
    welcomeMessage,
    soundEnabled,
    soundNotificationsEnabled,
    typingIndicatorEnabled,
    notificationSoundRef,
    onNewConversationId,
}: UseConversationMessagesOptions): UseConversationMessagesReturn {
    const [messages, setMessages] = useState<Message[]>([
        {
            id: '1',
            content: welcomeMessage || 'Hello! How can we help you today?',
            author: 'agent',
            timestamp: new Date(),
            status: 'delivered',
        },
    ]);
    const [hasMoreMessages, setHasMoreMessages] = useState(false);
    const [nextCursor, setNextCursor] = useState<number | null>(null);
    const [isLoadingMore, setIsLoadingMore] = useState(false);
    const [isReconnecting, setIsReconnecting] = useState(false);

    const messagesEndRef = useRef<HTMLDivElement | null>(null);
    const typingTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const readBatchTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const lastReadMessageIdRef = useRef<string | null>(null);

    const playNotificationSound = useCallback(() => {
        if (soundEnabled && soundNotificationsEnabled && notificationSoundRef.current) {
            notificationSoundRef.current.play().catch(() => {});
        }
    }, [soundEnabled, soundNotificationsEnabled, notificationSoundRef]);

    // Load initial messages on mount
    useEffect(() => {
        if (!conversationId) return;

        const load = async () => {
            const url = new URL(apiUrl(`/hd/api/conversation/${conversationId}/messages`));
            buildAuthParams(customerId, customerEmail).forEach((v, k) => url.searchParams.append(k, v));

            try {
                const response = await fetch(url.toString(), { headers: conversationAuthHeaders() });
                const data = await response.json();

                if (data.success && data.data.messages) {
                    const loaded: Message[] = data.data.messages.map(parseApiMessage);
                    setMessages(loaded);
                    setHasMoreMessages(Boolean(data.data.has_more));
                    setNextCursor(data.data.next_cursor ?? null);

                    if (loaded.length > 0) {
                        const lastTs = loaded[loaded.length - 1].timestamp.getTime();
                        localStorage.setItem(`livechat_last_read_${conversationId}`, String(lastTs));
                    }
                } else {
                    localStorage.removeItem('livechat_conversation_id');
                    onNewConversationId?.('');
                    setMessages([{
                        id: '1',
                        content: welcomeMessage || 'Hello! How can we help you today?',
                        author: 'agent',
                        timestamp: new Date(),
                        status: 'delivered',
                    }]);
                }
            } catch {
                localStorage.removeItem('livechat_conversation_id');
                onNewConversationId?.('');
            }
        };

        load();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    // Real-time Echo subscription
    useEffect(() => {
        if (!conversationId) return;

        echo.leaveChannel(`helpdesk-widget-conversation.${conversationId}`);

        const channel = echo.channel(`helpdesk-widget-conversation.${conversationId}`)
            .listen('.message.received', (event: any) => {
                if (event?.sender?.type && /Customer/i.test(event.sender.type)) return;

                const incomingId = String(event.id);
                const incomingAttachments: MessageAttachment[] = (event.attachments || []).map((a: any) => ({
                    url: a.url,
                    name: a.file_name ?? a.name,
                    size: a.size,
                    type: a.mime_type ?? a.type ?? '',
                }));
                const incomingLinkPreview = event.link_preview ?? null;
                let isUpdateOnly = false;

                setMessages(prev => {
                    const existingIdx = prev.findIndex(m => m.id === incomingId);
                    if (existingIdx >= 0) {
                        const existing = prev[existingIdx];
                        const merged: Message = {
                            ...existing,
                            content: event.content ?? existing.content,
                            attachments: incomingAttachments.length ? incomingAttachments : existing.attachments,
                            linkPreview: incomingLinkPreview ?? existing.linkPreview,
                        };
                        if (
                            merged.content === existing.content &&
                            (merged.linkPreview ?? null) === (existing.linkPreview ?? null) &&
                            JSON.stringify(merged.attachments) === JSON.stringify(existing.attachments)
                        ) {
                            isUpdateOnly = true;
                            return prev;
                        }
                        const next = prev.slice();
                        next[existingIdx] = merged;
                        isUpdateOnly = true;
                        return next;
                    }

                    const translatedContent = event.outgoing_translated_body || event.content || '';
                    const hasTranslation = !!event.outgoing_translated_body;

                    return [...prev, {
                        id: incomingId,
                        content: translatedContent,
                        originalContent: hasTranslation ? (event.content || '') : undefined,
                        author: 'agent' as const,
                        timestamp: event.created_at ? new Date(event.created_at) : new Date(),
                        status: 'delivered' as const,
                        attachments: incomingAttachments,
                        linkPreview: incomingLinkPreview,
                    }];
                });

                if (!isUpdateOnly) {
                    playNotificationSound();
                }

                if (conversationId && event.created_at) {
                    localStorage.setItem(
                        `livechat_last_read_${conversationId}`,
                        String(new Date(event.created_at).getTime())
                    );
                }

                if (!isUpdateOnly && event.id) {
                    scheduleMarkAsRead(String(event.id));
                }
            })
            .listen('.language.detected', (event: any) => {
                const lang = event?.language;
                if (lang && typeof lang === 'string') {
                    document.body.setAttribute('data-lang', lang);
                }
            });

        return () => {
            echo.leaveChannel(`helpdesk-widget-conversation.${conversationId}`);
        };
    // scheduleMarkAsRead is stable (useCallback with stable deps)
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [conversationId]);

    // Scroll to bottom when messages change
    useEffect(() => {
        const end = messagesEndRef.current;
        if (!end) return;

        let scrollEl: HTMLElement | null = end.parentElement;
        while (scrollEl && scrollEl !== document.body) {
            const cs = window.getComputedStyle(scrollEl);
            if ((cs.overflowY === 'auto' || cs.overflowY === 'scroll') && scrollEl.scrollHeight > scrollEl.clientHeight) {
                break;
            }
            scrollEl = scrollEl.parentElement;
        }

        const scrollToBottom = (smooth: boolean) => {
            if (scrollEl) {
                scrollEl.scrollTo({ top: scrollEl.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
            } else {
                end.scrollIntoView({ block: 'end', behavior: smooth ? 'smooth' : 'auto' });
            }
        };

        scrollToBottom(false);
        const t1 = window.requestAnimationFrame(() => scrollToBottom(false));
        const t2 = window.setTimeout(() => scrollToBottom(true), 80);

        return () => {
            window.cancelAnimationFrame(t1);
            window.clearTimeout(t2);
        };
    }, [messages.length, conversationId]);

    // WebSocket reconnect — resync missed messages
    const resyncMessages = useCallback(async (convId: string) => {
        const url = new URL(apiUrl(`/hd/api/conversation/${convId}/messages`));
        buildAuthParams(customerId, customerEmail).forEach((v, k) => url.searchParams.append(k, v));

        try {
            const response = await fetch(url.toString(), { headers: conversationAuthHeaders() });
            const data = await response.json();
            if (!data?.success || !Array.isArray(data?.data?.messages)) return;

            const incoming: Message[] = data.data.messages.map(parseApiMessage);

            setMessages(prev => {
                const existingIds = new Set(prev.map(m => m.id));
                const newOnes = incoming.filter(m => !existingIds.has(m.id));
                return newOnes.length > 0 ? [...prev, ...newOnes] : prev;
            });
        } catch {
            // Best-effort resync
        }
    }, [customerId, customerEmail]);

    useEffect(() => {
        const unsubscribe = onWsStateChange((current, previous) => {
            const wasDown = previous === 'disconnected' || previous === 'unavailable';
            if (current === 'connected') {
                setIsReconnecting(false);
                if (wasDown && conversationId) {
                    resyncMessages(conversationId);
                }
            } else if (current === 'disconnected' || current === 'unavailable') {
                setIsReconnecting(true);
            }
        });
        return unsubscribe;
    }, [conversationId, resyncMessages]);

    // Read receipts — batch-mark the last visible agent message every 2s
    const scheduleMarkAsRead = useCallback((messageId: string) => {
        lastReadMessageIdRef.current = messageId;
        if (readBatchTimerRef.current) return;
        readBatchTimerRef.current = setTimeout(() => {
            readBatchTimerRef.current = null;
            const id = lastReadMessageIdRef.current;
            if (!id || !conversationId) return;
            const csrf = getCsrfToken();
            fetch(apiUrl(`/hd/api/conversation/${conversationId}/read`), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                    ...conversationAuthHeaders(),
                },
                body: JSON.stringify({
                    up_to_message_id: id,
                    ...(customerId ? { customer_id: customerId } : {}),
                    ...(customerEmail ? { customer_email: customerEmail } : {}),
                }),
            }).catch(() => {});
        }, 2000);
    }, [conversationId, customerId, customerEmail]);

    // Cleanup timers on unmount
    useEffect(() => {
        return () => {
            if (typingTimerRef.current) clearTimeout(typingTimerRef.current);
            if (readBatchTimerRef.current) clearTimeout(readBatchTimerRef.current);
        };
    }, []);

    const loadMoreMessages = async () => {
        if (!conversationId || !hasMoreMessages || isLoadingMore || nextCursor === null) return;

        setIsLoadingMore(true);
        try {
            const url = new URL(apiUrl(`/hd/api/conversation/${conversationId}/messages`));
            buildAuthParams(customerId, customerEmail).forEach((v, k) => url.searchParams.append(k, v));
            url.searchParams.append('before_id', String(nextCursor));
            url.searchParams.append('limit', '50');

            const response = await fetch(url.toString(), { headers: conversationAuthHeaders() });
            const data = await response.json();

            if (data?.success && Array.isArray(data?.data?.messages)) {
                const olderMessages: Message[] = data.data.messages.map(parseApiMessage);
                setMessages(prev => {
                    const existingIds = new Set(prev.map(m => m.id));
                    const newOnes = olderMessages.filter(m => !existingIds.has(m.id));
                    return [...newOnes, ...prev];
                });
                setHasMoreMessages(Boolean(data.data.has_more));
                setNextCursor(data.data.next_cursor ?? null);
            }
        } catch {
            // Best-effort
        } finally {
            setIsLoadingMore(false);
        }
    };

    return {
        messages,
        setMessages,
        hasMoreMessages,
        isLoadingMore,
        isReconnecting,
        messagesEndRef,
        loadMoreMessages,
        scheduleMarkAsRead,
        typingTimerRef,
    };
}
