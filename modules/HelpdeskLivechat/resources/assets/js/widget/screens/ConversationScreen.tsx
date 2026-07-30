import React, { useState, useRef, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { useWidgetStore } from '../widget-store';
import { apiUrl, conversationAuthHeaders, setConversationToken, clearConversationToken } from '../api';
import { ImageLightbox } from '../components/ImageLightbox';
import { getVisitorIdentity } from '../widget-identity';
import { isScreenShareAvailable } from '../webrtc';
import { useTranslation } from '../i18n/useLanguage';

import { useVisitorIdentity } from '../hooks/useVisitorIdentity';
import { useConversationMessages } from '../hooks/useConversationMessages';
import { useWebRtcScreenShare } from '../hooks/useWebRtcScreenShare';
import { useLivestreamRecorder } from '../hooks/useLivestreamRecorder';

import { ConversationHeader } from '../components/ConversationHeader';
import { MessageList } from '../components/MessageList';
import { MessageComposer } from '../components/MessageComposer';
import { ScreenShareRequestModal } from '../components/ScreenShareRequestModal';
import { buildLightboxImages } from '../components/MessageBubble';

export function ConversationScreen() {
    const settings = useWidgetStore(state => state.settings);
    const botMessages = useWidgetStore(state => state.botMessages);
    const showPostChat = useWidgetStore(state => state.showPostChat);
    const setShowPostChat = useWidgetStore(state => state.setShowPostChat);
    const recommendations = useWidgetStore(state => state.recommendations);
    const quickReplies = settings.quick_replies ?? [];
    const navigate = useNavigate();
    const t = useTranslation();

    // Navigate to post-chat when triggered externally
    useEffect(() => {
        if (showPostChat) {
            setShowPostChat(false);
            navigate('/post-chat');
        }
    }, [showPostChat, setShowPostChat, navigate]);

    const websiteToken = (window as any).HELPDESK_WIDGET_CONFIG?.websiteToken ?? '';
    const liveViewEnabled = !!settings.enable_live_view;
    const screenShareEnabled = !!settings.enable_screen_share && isScreenShareAvailable();

    // Sound
    const notificationSoundRef = useRef<HTMLAudioElement | null>(null);
    const [soundEnabled, setSoundEnabled] = useState<boolean>(() => {
        const stored = localStorage.getItem('livechat_sound_enabled');
        return stored === null ? true : stored === '1';
    });

    useEffect(() => {
        localStorage.setItem('livechat_sound_enabled', soundEnabled ? '1' : '0');
    }, [soundEnabled]);

    useEffect(() => {
        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBjOP1vLTeSsFIHDE8N+XRwoRXLDn66xWFApDnuDyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+Dyxmw=');
        audio.volume = 0.5;
        notificationSoundRef.current = audio;
    }, []);

    // Menu
    const [showMenu, setShowMenu] = useState(false);

    useEffect(() => {
        if (!showMenu) return;
        const handler = (e: MouseEvent) => {
            if (!(e.target as HTMLElement).closest('.wgt-conv-menu-wrap')) setShowMenu(false);
        };
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, [showMenu]);

    // Visitor identity
    const {
        conversationId,
        setConversationId,
        customerEmail,
        setCustomerEmail,
        customerName,
        setCustomerName,
        customerId,
        setCustomerId,
    } = useVisitorIdentity();

    // Messages
    const {
        messages,
        setMessages,
        hasMoreMessages,
        isLoadingMore,
        isReconnecting,
        agentTyping,
        connectionStatus,
        messagesEndRef,
        loadMoreMessages,
        scheduleMarkAsRead,
        typingTimerRef,
    } = useConversationMessages({
        conversationId,
        customerId,
        customerEmail,
        welcomeMessage: settings.welcome_message || '',
        soundEnabled,
        soundNotificationsEnabled: !!settings.sound_notifications,
        typingIndicatorEnabled: !!settings.typing_indicator,
        notificationSoundRef,
        onNewConversationId: (id) => setConversationId(id || null),
    });

    // Lightbox
    const [lightboxIndex, setLightboxIndex] = useState<number | null>(null);
    const lightboxImages = React.useMemo(() => buildLightboxImages(messages), [messages]);
    const openLightbox = (url: string) => {
        const idx = lightboxImages.findIndex(i => i.url === url);
        if (idx >= 0) setLightboxIndex(idx);
    };

    // Queue position — poll every 30s while the conversation is unassigned
    const [queuePosition, setQueuePosition] = useState<number | null>(null);
    const fetchQueuePosition = useCallback(async (convId: string) => {
        try {
            const res = await fetch(apiUrl(`/hd/api/conversation/${convId}/queue-position`), {
                headers: { 'Accept': 'application/json', ...conversationAuthHeaders() },
            });
            if (!res.ok) return;
            const json = await res.json();
            if (json?.success) {
                setQueuePosition(json.data.is_assigned ? null : (json.data.position ?? null));
            }
        } catch {
            // network error — ignore, will retry on next interval
        }
    }, []);

    useEffect(() => {
        if (!conversationId) return;
        fetchQueuePosition(conversationId);
        const interval = setInterval(() => fetchQueuePosition(conversationId), 30_000);
        return () => clearInterval(interval);
    }, [conversationId, fetchQueuePosition]);

    // Clear queue banner once the conversation gets assigned (new message from agent)
    useEffect(() => {
        if (messages.some(m => m.author === 'agent')) {
            setQueuePosition(null);
        }
    }, [messages]);

    // Livestream
    const { liveViewConsent, handleToggleLiveViewConsent } = useLivestreamRecorder({
        conversationId,
        liveViewEnabled,
        websiteToken,
    });

    // Screen share
    const {
        screenShareHandle,
        screenShareRequest,
        handleToggleScreenShare,
        handleAcceptScreenShareRequest,
        handleDeclineScreenShareRequest,
    } = useWebRtcScreenShare({ conversationId, screenShareEnabled });

    // Composer state
    const [inputValue, setInputValue] = useState('');
    const [isSending, setIsSending] = useState(false);
    const [attachedFiles, setAttachedFiles] = useState<File[]>([]);
    const [showEmojiPicker, setShowEmojiPicker] = useState(false);
    const [isClosing, setIsClosing] = useState(false);
    const inputRef = useRef<HTMLInputElement | null>(null);
    const fileInputRef = useRef<HTMLInputElement | null>(null);

    const handleInputChange = (value: string) => {
        setInputValue(value);

        if (!settings.typing_indicator || !conversationId) return;
        if (typingTimerRef.current) clearTimeout(typingTimerRef.current);
        typingTimerRef.current = setTimeout(() => {
            typingTimerRef.current = null;
            const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
            fetch(apiUrl(`/hd/api/conversation/${conversationId}/typing`), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                    ...conversationAuthHeaders(),
                },
                body: JSON.stringify({
                    ...(customerId ? { customer_id: customerId } : {}),
                    ...(customerEmail ? { customer_email: customerEmail } : {}),
                }),
            }).catch(() => {});
        }, 1000);
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

    const handleSendMessage = async () => {
        if ((!inputValue.trim() && attachedFiles.length === 0) || isSending) return;

        const messageContent = inputValue.trim() || `📎 ${attachedFiles.length} archivo(s) adjunto(s)`;
        const tempId = Date.now().toString();

        setMessages(prev => [...prev, {
            id: tempId,
            content: messageContent,
            author: 'user',
            timestamp: new Date(),
            status: 'sending',
        }]);
        setInputValue('');
        setAttachedFiles([]);
        setIsSending(true);

        const token = (window as any).HELPDESK_WIDGET_CONFIG?.websiteToken
            ?? new URLSearchParams(window.location.search).get('website_token');
        const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
        const baseHeaders: Record<string, string> = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
            // Sending an existing message requires the per-conversation token.
            // On the first message (conversation creation) it is empty and ignored.
            ...conversationAuthHeaders(),
        };

        const identity = getVisitorIdentity();
        const customAttributes = identity
            ? Object.fromEntries(
                Object.entries(identity).filter(([k]) => !['email', 'name', 'phone', 'userId'].includes(k))
            )
            : null;

        try {
            if (!conversationId) {
                const engagementState = useWidgetStore.getState().engagement;
                const { getSessionToken } = await import('../widget-session');
                const sessionToken = getSessionToken();

                const response = await fetch(apiUrl('/hd/api/conversation'), {
                    method: 'POST',
                    headers: { ...baseHeaders, 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        website_token: token,
                        email: customerEmail || null,
                        name: customerName || (customerEmail ? customerEmail.split('@')[0] : 'Guest'),
                        message: messageContent,
                        customer_id: customerId ?? null,
                        widget_session_token: sessionToken,
                        custom_attributes: customAttributes,
                        engagement_context: {
                            score: engagementState.score,
                            segment: engagementState.segment,
                            identified: engagementState.isIdentified,
                        },
                    }),
                });
                const data = await response.json();

                if (data?.success) {
                    // Persist the per-conversation secret so subsequent REST calls
                    // can authorize against this conversation (closes the IDOR).
                    if (data.data?.pubsub_token) setConversationToken(data.data.pubsub_token);
                    const newConvId = data.data?.conversation_id ?? data.data?.conversation?.id;
                    if (newConvId) setConversationId(String(newConvId));
                    if (data.data?.customer?.email) setCustomerEmail(data.data.customer.email);
                    if (data.data?.customer?.name) setCustomerName(data.data.customer.name);
                    if (data.data?.customer?.id) setCustomerId(data.data.customer.id);
                    // Reconcilia el id optimista con el id real del servidor. Sin
                    // esto el primer mensaje conserva su tempId (Date.now) y un
                    // resync/polling posterior lo trae de nuevo con su id real y
                    // lo duplica (la deduplicación es por id).
                    const firstRealId = data.data?.message_id ? String(data.data.message_id) : null;
                    setMessages(prev => prev.map(m =>
                        m.id === tempId
                            ? { ...m, id: firstRealId ?? m.id, status: 'sent' as const }
                            : m
                    ));
                }
            } else {
                const formData = new FormData();
                formData.append('website_token', token ?? '');
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
                    const sd = data.data ?? {};
                    const realId = sd.message_id ? String(sd.message_id) : tempId;
                    const serverAttachments = (sd.attachments ?? []).map((a: any) => ({
                        url: a.url ?? '',
                        name: a.name ?? '',
                        size: a.size ?? 0,
                        type: a.mime_type ?? '',
                    }));
                    setMessages(prev => prev.map(m =>
                        m.id === tempId
                            ? {
                                ...m,
                                id: realId,
                                status: 'sent' as const,
                                content: inputValue.trim() || '',
                                attachments: serverAttachments.length ? serverAttachments : m.attachments,
                            }
                            : m
                    ));
                }
            }
        } catch (e) {
            console.error('Error sending message:', e);
        } finally {
            setIsSending(false);
        }
    };

    const handleCloseConversation = async () => {
        if (!conversationId || !customerEmail) return;
        if (!confirm('¿Estás seguro de que deseas cerrar esta conversación?')) return;

        setIsClosing(true);
        try {
            const response = await fetch(apiUrl(`/hd/api/conversation/${conversationId}/close`), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', ...conversationAuthHeaders() },
                body: JSON.stringify({
                    customer_id: customerId ?? null,
                    customer_email: customerEmail,
                }),
            });
            const data = await response.json();

            if (data.success) {
                alert('Conversación cerrada. ¡Gracias por contactarnos!');
                localStorage.removeItem('livechat_conversation_id');
                clearConversationToken();
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
            <ConversationHeader
                title={settings.header_title}
                primaryColor={settings.primary_color}
                connectionStatus={connectionStatus}
                showMenu={showMenu}
                onToggleMenu={() => setShowMenu(s => !s)}
                onCloseConversation={handleCloseConversation}
                isClosing={isClosing}
                soundEnabled={soundEnabled}
                onToggleSound={() => setSoundEnabled(s => !s)}
                liveViewEnabled={liveViewEnabled}
                liveViewConsent={liveViewConsent}
                onToggleLiveViewConsent={handleToggleLiveViewConsent}
                screenShareEnabled={screenShareEnabled}
                screenShareActive={!!screenShareHandle}
                onToggleScreenShare={handleToggleScreenShare}
            />

            {isReconnecting && (
                <div className="wgt-reconnect-banner" role="status" aria-live="polite">
                    <svg viewBox="0 0 24 24" fill="none" style={{ animation: 'wgt-spin 1s linear infinite' }} aria-hidden="true">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="2.5" strokeOpacity="0.25" />
                        <path d="M4 12a8 8 0 0 1 8-8" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" />
                    </svg>
                    <span>Reconectando…</span>
                </div>
            )}

            {queuePosition !== null && queuePosition > 0 && (
                <div className="wgt-queue-banner" role="status" aria-live="polite">
                    {t('queue_message', { number: String(queuePosition) })}
                </div>
            )}

            {screenShareRequest && (
                <ScreenShareRequestModal
                    agentName={screenShareRequest.agentName}
                    primaryColor={settings.primary_color}
                    onAccept={handleAcceptScreenShareRequest}
                    onDecline={handleDeclineScreenShareRequest}
                />
            )}

            <MessageList
                messages={messages}
                botMessages={botMessages}
                recommendations={recommendations}
                hasMoreMessages={hasMoreMessages}
                isLoadingMore={isLoadingMore}
                offlineMessageEnabled={settings.offline_message_enabled}
                offlineMessage={settings.offline_message}
                showAvatars={settings.show_avatars}
                primaryColor={settings.primary_color}
                avatarInitial={settings.header_title?.charAt(0) || 'A'}
                agentTyping={agentTyping}
                messagesEndRef={messagesEndRef}
                onOpenLightbox={openLightbox}
                onLoadMore={loadMoreMessages}
            />

            {!conversationId && quickReplies.length > 0 && (
                <div className="wgt-quick-replies" role="group" aria-label="Respuestas rápidas">
                    {quickReplies.map((reply, i) => (
                        <button
                            key={i}
                            className="wgt-quick-reply-chip"
                            style={{ borderColor: settings.primary_color, color: settings.primary_color }}
                            onClick={() => setInputValue(reply)}
                        >
                            {reply}
                        </button>
                    ))}
                </div>
            )}

            <MessageComposer
                inputValue={inputValue}
                onInputChange={handleInputChange}
                onKeyPress={handleKeyPress}
                onSend={handleSendMessage}
                isSending={isSending}
                attachedFiles={attachedFiles}
                onFileSelect={handleFileSelect}
                onRemoveAttachment={(index) => setAttachedFiles(prev => prev.filter((_, i) => i !== index))}
                showEmojiPicker={showEmojiPicker}
                onToggleEmojiPicker={() => setShowEmojiPicker(s => !s)}
                onEmojiSelect={(emoji) => setInputValue(v => v + emoji)}
                inputRef={inputRef}
                fileInputRef={fileInputRef}
                primaryColor={settings.primary_color}
                placeholder={settings.input_placeholder}
                enableEmoji={settings.enableEmoji}
                enableFileUpload={settings.enableFileUpload}
                allowedFileTypes={settings.allowedFileTypes}
            />

            {lightboxIndex !== null && lightboxImages.length > 0 && (
                <ImageLightbox
                    images={lightboxImages}
                    initialIndex={lightboxIndex}
                    onClose={() => setLightboxIndex(null)}
                />
            )}
        </div>
    );
}
