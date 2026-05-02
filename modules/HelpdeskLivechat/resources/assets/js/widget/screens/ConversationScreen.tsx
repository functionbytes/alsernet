import React, { useState, useRef, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useWidgetStore } from '../widget-store';
import { echo } from '../echo';
import { apiUrl } from '../api';
import { EmojiPicker } from '../components/EmojiPicker';
import { getVisitorIdentity } from '../widget-identity';

interface LinkPreview {
    url: string;
    site?: string | null;
    title?: string | null;
    description?: string | null;
    image?: string | null;
    favicon?: string | null;
}

interface Message {
    id: string;
    content: string;
    author: 'user' | 'agent' | 'bot';
    timestamp: Date;
    status?: 'sending' | 'sent' | 'delivered';
    attachments?: Array<{
        url: string;
        name: string;
        size: number;
        type: string;
    }>;
    linkPreview?: LinkPreview | null;
}

export function ConversationScreen() {
    const settings = useWidgetStore(state => state.settings);
    const navigate = useNavigate();

    const notificationSound = useRef<HTMLAudioElement | null>(null);

    const [messages, setMessages] = useState<Message[]>([
        {
            id: '1',
            content: settings.welcome_message || 'Hello! How can we help you today?',
            author: 'agent',
            timestamp: new Date(),
            status: 'delivered'
        }
    ]);

    const [inputValue, setInputValue] = useState('');
    const [isSending, setIsSending] = useState(false);
    const [attachedFiles, setAttachedFiles] = useState<File[]>([]);
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const inputRef = useRef<HTMLInputElement>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [showMenu, setShowMenu] = useState(false);
    const [isClosing, setIsClosing] = useState(false);
    const [showEmojiPicker, setShowEmojiPicker] = useState(false);
    const [soundEnabled, setSoundEnabled] = useState<boolean>(() => {
        const stored = localStorage.getItem('livechat_sound_enabled');
        return stored === null ? true : stored === '1';
    });

    useEffect(() => {
        localStorage.setItem('livechat_sound_enabled', soundEnabled ? '1' : '0');
    }, [soundEnabled]);

    useEffect(() => {
        if (!showMenu) return;
        const handler = (e: MouseEvent) => {
            const target = e.target as HTMLElement;
            if (!target.closest('.wgt-conv-menu-wrap')) setShowMenu(false);
        };
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, [showMenu]);

    const [conversationId, setConversationId] = useState<string | null>(() => {
        const stored = localStorage.getItem('livechat_conversation_id');
        console.log('📦 Loading conversation from localStorage:', stored);
        return stored;
    });

    const [customerEmail, setCustomerEmail] = useState<string>(() => {
        return localStorage.getItem('livechat_customer_email') || '';
    });

    const [customerName, setCustomerName] = useState<string>(() => {
        return localStorage.getItem('livechat_customer_name') || '';
    });

    const [customerId, setCustomerId] = useState<number | null>(() => {
        const stored = localStorage.getItem('livechat_customer_id');
        return stored ? parseInt(stored, 10) : null;
    });

    useEffect(() => {
        if (conversationId) {
            localStorage.setItem('livechat_conversation_id', conversationId);
        }
    }, [conversationId]);

    useEffect(() => {
        if (customerEmail) {
            localStorage.setItem('livechat_customer_email', customerEmail);
        }
    }, [customerEmail]);

    useEffect(() => {
        if (customerName) {
            localStorage.setItem('livechat_customer_name', customerName);
        }
    }, [customerName]);

    useEffect(() => {
        if (customerId) {
            localStorage.setItem('livechat_customer_id', String(customerId));
        }
    }, [customerId]);

    // Listen for identity updates from the host site (e.g. PrestaShop login).
    useEffect(() => {
        const handler = (e: any) => {
            const identity = e.detail;
            if (identity?.email) setCustomerEmail(identity.email);
            if (identity?.name) setCustomerName(identity.name);
            const id = identity?.customer_id ?? identity?.userId;
            if (id != null) setCustomerId(Number(id));
        };
        window.addEventListener('helpdesk:identity:changed', handler);
        return () => window.removeEventListener('helpdesk:identity:changed', handler);
    }, []);

    useEffect(() => {
        if (!conversationId) return;

        // Public channel — widget visitors are not authenticated.
        // Backend dispatches Modules\Chat\Events\MessageSent on
        // 'helpdesk-widget-conversation.{id}' with name 'message.sent'.
        // We leave the channel first to drop any stale listeners (StrictMode
        // remount or HMR can leave subscriptions behind).
        echo.leaveChannel(`helpdesk-widget-conversation.${conversationId}`);

        const channel = echo.channel(`helpdesk-widget-conversation.${conversationId}`)
            .listen('.message.received', (event: any) => {
                // Skip echoes of messages sent by the visitor itself
                if (event?.sender?.type && /Customer/i.test(event.sender.type)) {
                    return;
                }
                const incomingId = String(event.id);
                const incomingAttachments = (event.attachments || []).map((a: any) => ({
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
                        // Same id arriving again — happens when the link-preview observer
                        // re-broadcasts the message after enriching metadata. Merge the
                        // new data (especially linkPreview) instead of dropping it.
                        const existing = prev[existingIdx];
                        const merged: Message = {
                            ...existing,
                            content: event.content ?? existing.content,
                            attachments: incomingAttachments.length ? incomingAttachments : existing.attachments,
                            linkPreview: incomingLinkPreview ?? existing.linkPreview,
                        };
                        // No-op if nothing actually changed.
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

                    return [...prev, {
                        id: incomingId,
                        content: event.content || '',
                        author: 'agent',
                        timestamp: event.created_at ? new Date(event.created_at) : new Date(),
                        status: 'delivered',
                        attachments: incomingAttachments,
                        linkPreview: incomingLinkPreview,
                    }];
                });
                if (!isUpdateOnly) {
                    playNotificationSound();
                }
                // Visitor is currently viewing the thread → mark this incoming
                // agent message as read for the home screen badge logic.
                if (conversationId && event.created_at) {
                    localStorage.setItem(
                        `livechat_last_read_${conversationId}`,
                        String(new Date(event.created_at).getTime())
                    );
                }
            });

        return () => {
            echo.leaveChannel(`helpdesk-widget-conversation.${conversationId}`);
        };
    }, [conversationId]);

    // Always pin the thread at the bottom — both on initial mount (so the
    // visitor sees the latest message immediately) and whenever a new one
    // arrives. We scroll the actual scrollable parent (.wgt-messages-area)
    // rather than relying on scrollIntoView, which fails when the layout
    // isn't fully painted yet, and we use rAF + setTimeout fallbacks so the
    // scroll lands after React has flushed the DOM.
    useEffect(() => {
        const end = messagesEndRef.current;
        if (!end) return;

        // Find the nearest scrollable ancestor.
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

        // First pin instantly so the user never sees the top, then a smooth
        // tick once images/attachments may have laid out.
        scrollToBottom(false);
        const t1 = window.requestAnimationFrame(() => scrollToBottom(false));
        const t2 = window.setTimeout(() => scrollToBottom(true), 80);

        return () => {
            window.cancelAnimationFrame(t1);
            window.clearTimeout(t2);
        };
    }, [messages.length, conversationId]);

    useEffect(() => {
        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBjOP1vLTeSsFIHDE8N+XRwoRXLDn66xWFApDnuDyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+Dyxmw=');
        audio.volume = 0.5;
        notificationSound.current = audio;
    }, []);

    const playNotificationSound = () => {
        if (soundEnabled && settings.sound_notifications && notificationSound.current) {
            notificationSound.current.play().catch(() => {});
        }
    };

    useEffect(() => {
        const loadExistingConversation = async () => {
            if (!conversationId) return;

            try {
                const url = new URL(apiUrl(`/hd/api/conversation/${conversationId}/messages`));
                if (customerId) {
                    url.searchParams.append('customer_id', String(customerId));
                } else if (customerEmail) {
                    url.searchParams.append('customer_email', customerEmail);
                }

                const response = await fetch(url.toString());
                const data = await response.json();

                if (data.success && data.data.messages) {
                    const loadedMessages: Message[] = data.data.messages.map((msg: any) => ({
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
                    }));
                    setMessages(loadedMessages);

                    // Mark as read so the home screen "continue conversation" card
                    // stops showing the unread dot.
                    if (loadedMessages.length > 0 && conversationId) {
                        const lastTs = loadedMessages[loadedMessages.length - 1].timestamp.getTime();
                        localStorage.setItem(`livechat_last_read_${conversationId}`, String(lastTs));
                    }
                } else {
                    localStorage.removeItem('livechat_conversation_id');
                    setConversationId(null);
                    setMessages([{
                        id: '1',
                        content: settings.welcome_message || 'Hello! How can we help you today?',
                        author: 'agent',
                        timestamp: new Date(),
                        status: 'delivered'
                    }]);
                }
            } catch {
                localStorage.removeItem('livechat_conversation_id');
                setConversationId(null);
            }
        };

        loadExistingConversation();
    }, []);

    const handleSendMessage = async () => {
        if ((!inputValue.trim() && attachedFiles.length === 0) || isSending) return;

        const messageContent = inputValue.trim() || `📎 ${attachedFiles.length} archivo(s) adjunto(s)`;
        const userMessage: Message = {
            id: Date.now().toString(),
            content: messageContent,
            author: 'user',
            timestamp: new Date(),
            status: 'sending'
        };

        setMessages(prev => [...prev, userMessage]);
        setInputValue('');
        setAttachedFiles([]);
        setIsSending(true);

        const websiteToken =
            (window as any).HELPDESK_WIDGET_CONFIG?.websiteToken
            ?? new URLSearchParams(window.location.search).get('website_token');
        const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
        const baseHeaders: Record<string, string> = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
        };

        const identity = getVisitorIdentity();
        const customAttributes = identity
            ? Object.fromEntries(
                Object.entries(identity).filter(
                    ([k]) => !['email', 'name', 'phone', 'userId'].includes(k)
                )
            )
            : null;

        try {
            if (!conversationId) {
                const response = await fetch(apiUrl('/hd/api/conversation'), {
                    method: 'POST',
                    headers: { ...baseHeaders, 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        website_token: websiteToken,
                        email: customerEmail || null,
                        name: customerName || (customerEmail ? customerEmail.split('@')[0] : 'Guest'),
                        message: messageContent,
                        customer_id: customerId ?? null,
                        custom_attributes: customAttributes,
                    }),
                });

                const data = await response.json();

                if (data?.success) {
                    const newConvId = data.data?.conversation_id ?? data.data?.conversation?.id;
                    if (newConvId) setConversationId(String(newConvId));
                    if (data.data?.customer?.email) {
                        setCustomerEmail(data.data.customer.email);
                    }
                    if (data.data?.customer?.name) {
                        setCustomerName(data.data.customer.name);
                    }
                    if (data.data?.customer?.id) {
                        setCustomerId(data.data.customer.id);
                    }
                    setMessages(prev => prev.map(msg =>
                        msg.id === userMessage.id ? { ...msg, status: 'sent' as const } : msg
                    ));
                }
            } else {
                const formData = new FormData();
                formData.append('website_token', websiteToken ?? '');
                if (customerId) formData.append('customer_id', String(customerId));
                if (customerEmail) formData.append('customer_email', customerEmail);
                if (customerName) formData.append('name', customerName);
                if (messageContent) formData.append('message', messageContent);
                if (customAttributes && Object.keys(customAttributes).length > 0) {
                    formData.append('custom_attributes', JSON.stringify(customAttributes));
                }
                attachedFiles.forEach((file, index) => {
                    formData.append(`attachments[${index}]`, file);
                });

                const response = await fetch(apiUrl(`/hd/api/conversation/${conversationId}/messages`), {
                    method: 'POST',
                    headers: baseHeaders,
                    body: formData,
                });

                const data = await response.json();

                if (data?.success) {
                    setMessages(prev => prev.map(msg =>
                        msg.id === userMessage.id ? { ...msg, status: 'sent' as const } : msg
                    ));
                }
            }
        } catch (e) {
            console.error('Error sending message:', e);
        } finally {
            setIsSending(false);
        }
    };

    const handleKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSendMessage();
        }
    };

    const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files) {
            setAttachedFiles(prev => [...prev, ...Array.from(e.target.files!)]);
        }
    };

    const removeAttachment = (index: number) => {
        setAttachedFiles(prev => prev.filter((_, i) => i !== index));
    };

    const handleCloseConversation = async () => {
        if (!conversationId || !customerEmail) return;
        if (!confirm('¿Estás seguro de que deseas cerrar esta conversación?')) return;

        setIsClosing(true);

        try {
            const response = await fetch(apiUrl(`/hd/api/conversation/${conversationId}/close`), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    customer_id: customerId ?? null,
                    customer_email: customerEmail,
                }),
            });

            const data = await response.json();

            if (data.success) {
                alert('Conversación cerrada. ¡Gracias por contactarnos!');
                localStorage.removeItem('livechat_conversation_id');
                navigate('/');
            } else {
                alert('No se pudo cerrar la conversación. Por favor, inténtalo de nuevo.');
            }
        } catch {
            alert('Error al cerrar la conversación.');
        } finally {
            setIsClosing(false);
            setShowMenu(false);
        }
    };

    return (
        <div className="wgt-stack-y wgt-h-full wgt-bg-light wgt-fade-in">
            {/* Header */}
            <div className="wgt-screen-header">
                <Link to="/" className="wgt-icon-btn">
                    <svg className="wgt-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>

                <div className="wgt-flex-1 wgt-row-center wgt-gap-3">
                    <div
                        className="wgt-avatar wgt-fw-semibold wgt-text-sm"
                        style={{ backgroundColor: settings.primary_color }}
                    >
                        {settings.header_title?.charAt(0) || 'A'}
                    </div>
                    <div className="wgt-text-center">
                        <div className="wgt-fw-semibold wgt-text-body wgt-text-sm">
                            {settings.header_title || 'Support Team'}
                        </div>
                        <div className="wgt-row-center wgt-gap-1 wgt-text-xs wgt-text-muted">
                            <span className="wgt-online-dot"></span>
                            <span>Online</span>
                        </div>
                    </div>
                </div>

                <div className="wgt-conv-menu-wrap">
                    <button
                        type="button"
                        onClick={() => setShowMenu(!showMenu)}
                        className="wgt-conv-menu-trigger"
                        aria-label="Menu"
                    >
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4z" />
                        </svg>
                    </button>

                    {showMenu && (
                        <div className="wgt-conv-menu">
                            <button
                                type="button"
                                onClick={handleCloseConversation}
                                disabled={isClosing}
                                className="wgt-conv-menu-item"
                            >
                                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z" />
                                </svg>
                                <span>{isClosing ? 'Closing…' : 'Close conversation'}</span>
                            </button>

                            <button
                                type="button"
                                onClick={() => setSoundEnabled(s => !s)}
                                className="wgt-conv-menu-item"
                            >
                                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    {soundEnabled ? (
                                        <path d="M3 10v4h4l5 5V5L7 10H3zm13.5 2A4.5 4.5 0 0 0 14 7.97v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z" />
                                    ) : (
                                        <path d="M16.5 12A4.5 4.5 0 0 0 14 7.97v2.21l2.45 2.45c.03-.21.05-.42.05-.63zM19 12c0 .94-.2 1.82-.54 2.64l1.51 1.51A8.796 8.796 0 0 0 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3 3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06a8.99 8.99 0 0 0 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4 9.91 6.09 12 8.18V4z" />
                                    )}
                                </svg>
                                <span className="wgt-flex-1 wgt-text-left">Sounds</span>
                                <span
                                    className={`wgt-toggle${soundEnabled ? ' is-on' : ''}`}
                                    aria-hidden="true"
                                    style={soundEnabled ? { backgroundColor: settings.primary_color } : undefined}
                                >
                                    <span className="wgt-toggle-knob" />
                                </span>
                            </button>
                        </div>
                    )}
                </div>
            </div>

            {/* Messages */}
            <div className="wgt-messages-area">
                {settings.offline_message_enabled && settings.offline_message && (
                    <div className="wgt-offline-banner" role="status">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 1a11 11 0 1 0 11 11A11.013 11.013 0 0 0 12 1zm0 20a9 9 0 1 1 9-9 9.011 9.011 0 0 1-9 9zm0-13a1 1 0 0 0-1 1v5a1 1 0 0 0 2 0V9a1 1 0 0 0-1-1zm0 8a1 1 0 1 0 1 1 1 1 0 0 0-1-1z" />
                        </svg>
                        <span>{settings.offline_message}</span>
                    </div>
                )}
                {messages.map((message, index) => (
                    <div
                        key={message.id}
                        className={`wgt-message-row${message.author === 'user' ? ' is-user' : ''} wgt-fade-in`}
                        style={{ animationDelay: `${Math.min(index * 50, 500)}ms` }}
                    >
                        {message.author !== 'user' && settings.show_avatars && (
                            <div className="wgt-avatar" style={{ backgroundColor: settings.primary_color }}>
                                {settings.header_title?.charAt(0) || 'A'}
                            </div>
                        )}

                        <div className={`wgt-bubble-wrap${message.author === 'user' ? ' is-user' : ' is-agent'}`}>
                            <div
                                className={`wgt-bubble${message.author === 'user' ? ' is-user' : ' is-agent'}`}
                                style={message.author === 'user' ? { backgroundColor: settings.primary_color } : {}}
                            >
                                {(() => {
                                    // Hide the body text when it is *only* the URL that
                                    // has been previewed — the preview card already lets
                                    // the user click through. Keep it when the message
                                    // mixes prose with the URL.
                                    const trimmed = (message.content ?? '').trim();
                                    const previewUrl = message.linkPreview?.url ?? '';
                                    const isJustTheUrl = !!previewUrl && (
                                        trimmed === previewUrl ||
                                        trimmed === previewUrl.replace(/\/$/, '') ||
                                        trimmed.replace(/\/$/, '') === previewUrl.replace(/\/$/, '')
                                    );
                                    return trimmed && !isJustTheUrl ? <p>{message.content}</p> : null;
                                })()}

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
                                                <span>{message.linkPreview.site || new URL(message.linkPreview.url).hostname}</span>
                                            </div>
                                        </div>
                                    </a>
                                )}

                                {message.attachments && message.attachments.length > 0 && (
                                    <div className="wgt-attachment-list">
                                        {message.attachments.map((attachment, idx) => {
                                            const mime = (attachment.type ?? '').toLowerCase();
                                            const name = (attachment.name ?? '').toLowerCase();
                                            const ext = name.split('.').pop() ?? '';
                                            // Voice recordings from the helpdesk recorder are saved as voz-{ts}.webm
                                            // and may arrive without a mime type — treat them as audio.
                                            const isVoiceRecording = name.startsWith('voz-') && (ext === 'webm' || ext === 'ogg');
                                            const isImage = mime.startsWith('image/') || ['jpg','jpeg','png','gif','webp'].includes(ext);
                                            const isAudio = isVoiceRecording
                                                || mime.startsWith('audio/')
                                                || ['mp3','ogg','wav','oga','m4a','aac'].includes(ext)
                                                || (ext === 'webm' && (!mime || mime.startsWith('audio/')));
                                            const isVideo = !isAudio && (mime.startsWith('video/') || ['mp4','mov','avi','mkv'].includes(ext));
                                            const sizeKb = (attachment.size / 1024).toFixed(1);

                                            if (isImage) {
                                                return (
                                                    <a
                                                        key={idx}
                                                        href={attachment.url}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="wgt-attachment-image"
                                                        title={attachment.name}
                                                    >
                                                        <img src={attachment.url} alt={attachment.name} loading="lazy" />
                                                    </a>
                                                );
                                            }

                                            if (isAudio) {
                                                return (
                                                    <audio
                                                        key={idx}
                                                        controls
                                                        preload="metadata"
                                                        className="wgt-attachment-audio"
                                                        title={attachment.name}
                                                    >
                                                        <source src={attachment.url} type={mime || undefined} />
                                                    </audio>
                                                );
                                            }

                                            if (isVideo) {
                                                return (
                                                    <video
                                                        key={idx}
                                                        controls
                                                        preload="metadata"
                                                        className="wgt-attachment-video"
                                                        title={attachment.name}
                                                    >
                                                        <source src={attachment.url} type={mime || undefined} />
                                                    </video>
                                                );
                                            }

                                            return (
                                                <a
                                                    key={idx}
                                                    href={attachment.url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    download={attachment.name}
                                                    className={`wgt-attachment-link${message.author === 'user' ? ' is-user-bubble' : ' is-agent-bubble'}`}
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
                                        })}
                                    </div>
                                )}
                            </div>

                            <div className="wgt-row wgt-gap-1 wgt-bubble-time">
                                <span>
                                    {message.timestamp.toLocaleTimeString('en-US', {
                                        hour: 'numeric',
                                        minute: '2-digit',
                                        hour12: true
                                    })}
                                </span>
                                {message.author === 'user' && message.status && (
                                    <span>
                                        {message.status === 'sending' && '⏳'}
                                        {message.status === 'sent' && '✓'}
                                        {message.status === 'delivered' && '✓✓'}
                                    </span>
                                )}
                            </div>
                        </div>

                        {message.author === 'user' && settings.show_avatars && (
                            <div className="wgt-avatar is-user">U</div>
                        )}
                    </div>
                ))}
                <div ref={messagesEndRef} />
            </div>

            {/* Input Area (bedesk-style pill) */}
            <div className="wgt-composer">
                {attachedFiles.length > 0 && (
                    <div className="wgt-composer-files">
                        {attachedFiles.map((file, index) => (
                            <div key={index} className="wgt-file-chip">
                                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M16.5 6v11.5a4 4 0 1 1-8 0V5a2.5 2.5 0 0 1 5 0v10.5a1 1 0 0 1-2 0V6H10v9.5a2.5 2.5 0 0 0 5 0V5a4 4 0 0 0-8 0v12.5a5.5 5.5 0 0 0 11 0V6h-1.5z" />
                                </svg>
                                <span className="wgt-file-chip-name">{file.name}</span>
                                <button
                                    type="button"
                                    onClick={() => removeAttachment(index)}
                                    className="wgt-file-chip-x"
                                    aria-label="Remove"
                                >
                                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z" />
                                    </svg>
                                </button>
                            </div>
                        ))}
                    </div>
                )}

                <div className="wgt-composer-bar">
                    <input
                        ref={fileInputRef}
                        type="file"
                        multiple
                        onChange={handleFileSelect}
                        className="wgt-hidden"
                        accept="image/*,.pdf,.doc,.docx,.txt"
                    />

                    <input
                        ref={inputRef}
                        type="text"
                        value={inputValue}
                        onChange={(e) => setInputValue(e.target.value)}
                        onKeyPress={handleKeyPress}
                        placeholder={settings.input_placeholder || 'Enter your message here…'}
                        disabled={isSending}
                        className="wgt-composer-input"
                    />

                    <div className="wgt-composer-icon-wrap">
                        <button
                            type="button"
                            className="wgt-composer-icon wgt-tip"
                            data-tooltip="Emoji"
                            aria-label="Emoji"
                            onClick={() => setShowEmojiPicker(s => !s)}
                        >
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-3.5-9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zm7 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zm-3.5 5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z" />
                            </svg>
                        </button>
                        {showEmojiPicker && (
                            <EmojiPicker
                                onSelect={(emoji) => {
                                    setInputValue(v => v + emoji);
                                    inputRef.current?.focus();
                                }}
                                onClose={() => setShowEmojiPicker(false)}
                            />
                        )}
                    </div>

                    <button
                        type="button"
                        className="wgt-composer-icon wgt-tip"
                        data-tooltip="Attach file"
                        aria-label="Attach file"
                        onClick={() => fileInputRef.current?.click()}
                    >
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M16.5 6v11.5a4 4 0 1 1-8 0V5a2.5 2.5 0 0 1 5 0v10.5a1 1 0 0 1-2 0V6H10v9.5a2.5 2.5 0 0 0 5 0V5a4 4 0 0 0-8 0v12.5a5.5 5.5 0 0 0 11 0V6h-1.5z" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        onClick={handleSendMessage}
                        disabled={!inputValue.trim() || isSending}
                        className="wgt-composer-send wgt-tip"
                        data-tooltip="Send"
                        style={{ color: settings.primary_color }}
                        aria-label="Send"
                    >
                        {isSending ? (
                            <svg viewBox="0 0 24 24" fill="none" style={{ animation: 'wgt-spin 0.8s linear infinite' }} aria-hidden="true">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="3" strokeOpacity="0.25" />
                                <path d="M4 12a8 8 0 0 1 8-8" stroke="currentColor" strokeWidth="3" strokeLinecap="round" />
                            </svg>
                        ) : (
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M2.01 21 23 12 2.01 3 2 10l15 2-15 2z" />
                            </svg>
                        )}
                    </button>
                </div>
            </div>
        </div>
    );
}
