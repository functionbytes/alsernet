import React, { useState, useRef, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useWidgetStore } from '../widget-store';
import { echo } from '../echo';

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
}

export function ConversationScreen() {
    const settings = useWidgetStore(state => state.settings);
    const navigate = useNavigate();

    // Audio for notifications
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

    // Load conversationId and customerEmail from localStorage on mount
    const [conversationId, setConversationId] = useState<string | null>(() => {
        const stored = localStorage.getItem('livechat_conversation_id');
        console.log('📦 Loading conversation from localStorage:', stored);
        return stored;
    });

    const [customerEmail, setCustomerEmail] = useState<string>(() => {
        const stored = localStorage.getItem('livechat_customer_email');
        return stored || '';
    });

    // Save conversationId to localStorage whenever it changes
    useEffect(() => {
        if (conversationId) {
            localStorage.setItem('livechat_conversation_id', conversationId);
            console.log('💾 Saved conversation ID to localStorage:', conversationId);
        }
    }, [conversationId]);

    // Save customerEmail to localStorage whenever it changes
    useEffect(() => {
        if (customerEmail) {
            localStorage.setItem('livechat_customer_email', customerEmail);
            console.log('💾 Saved customer email to localStorage:', customerEmail);
        }
    }, [customerEmail]);

    // Laravel Echo - Real-time messaging
    useEffect(() => {
        if (!conversationId) return;

        console.log('🔌 Subscribing to conversation channel:', `conversation.${conversationId}`);

        // Subscribe to conversation channel
        const channel = echo.channel(`conversation.${conversationId}`)
            .listen('.message.received', (event: any) => {
                console.log('🔴 Real-time message received:', event);

                const newMessage: Message = {
                    id: event.message.id.toString(),
                    content: event.message.body || '',
                    author: event.message.is_from_agent ? 'agent' : 'user',
                    timestamp: new Date(event.message.created_at),
                    status: 'delivered',
                    attachments: event.message.attachments || []
                };

                setMessages(prev => [...prev, newMessage]);

                // Play notification sound if message is from agent
                if (newMessage.author === 'agent') {
                    playNotificationSound();
                }
            });

        // Cleanup: leave channel when component unmounts or conversationId changes
        return () => {
            console.log('🔌 Leaving conversation channel:', `conversation.${conversationId}`);
            echo.leaveChannel(`conversation.${conversationId}`);
        };
    }, [conversationId]);

    // Auto-scroll to bottom when new messages arrive
    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    // Initialize notification sound
    useEffect(() => {
        // Create audio element with a simple notification sound (data URL)
        // This is a simple beep sound encoded as base64
        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBjOP1vLTeSsFIHDE8N+XRwoRXLDn66xWFApDnuDyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+DyxmwhBjGP1vLTeSsFIHDE8N+XRwoRXK/n66xWFApCn+Dyxmw=');
        audio.volume = 0.5; // Set volume to 50%
        notificationSound.current = audio;
    }, []);

    // Function to play notification sound
    const playNotificationSound = () => {
        if (settings.sound_notifications && notificationSound.current) {
            notificationSound.current.play().catch(err => {
                console.log('Could not play notification sound:', err);
            });
        }
    };

    // Load existing conversation messages on mount
    useEffect(() => {
        const loadExistingConversation = async () => {
            if (!conversationId) return;

            console.log('🔄 Loading existing conversation:', conversationId);

            try {
                // Add customer_email as query parameter for verification
                const url = new URL(`/lc/api/conversations/${conversationId}/messages`, window.location.origin);
                if (customerEmail) {
                    url.searchParams.append('customer_email', customerEmail);
                }

                const response = await fetch(url.toString());
                const data = await response.json();

                if (data.success && data.data.messages) {
                    console.log('✅ Loaded existing messages:', data.data.messages);

                    // Convert API messages to component message format
                    const loadedMessages: Message[] = data.data.messages.map((msg: any) => ({
                        id: msg.id.toString(),
                        content: msg.body || msg.content,
                        author: msg.is_from_agent ? 'agent' : 'user',
                        timestamp: new Date(msg.created_at),
                        status: 'delivered',
                        attachments: msg.attachments || []
                    }));

                    setMessages(loadedMessages);
                } else {
                    console.warn('⚠️  Could not load conversation, starting fresh');
                    // Clear invalid conversation ID
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
            } catch (error) {
                console.error('❌ Error loading conversation:', error);
                // Clear invalid conversation ID
                localStorage.removeItem('livechat_conversation_id');
                setConversationId(null);
            }
        };

        loadExistingConversation();
    }, []); // Only run on mount

    const handleSendMessage = async () => {
        if ((!inputValue.trim() && attachedFiles.length === 0) || isSending) return;

        // Log attached files
        if (attachedFiles.length > 0) {
            console.log('📎 Enviando mensaje con archivos:', attachedFiles);
        }

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
        setAttachedFiles([]); // Clear attachments
        setIsSending(true);

        try {
            if (!conversationId) {
                // First message - create new conversation
                console.log('🆕 Creating new conversation...');

                // Use stored email or generate temporary one
                const email = customerEmail || null;

                const response = await fetch('/lc/api/conversations', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        customer: {
                            name: 'Guest User', // TODO: Get from form or localStorage
                            email: email,
                        },
                        message: messageContent,
                        subject: 'Nueva conversación desde widget'
                    })
                });

                const data = await response.json();

                if (data.success) {
                    console.log('✅ Conversation created:', data.data.conversation);
                    setConversationId(data.data.conversation.id);

                    // Save customer email (API generates temporary email if none provided)
                    if (data.data.customer && data.data.customer.email) {
                        setCustomerEmail(data.data.customer.email);
                        console.log('💾 Saved customer email:', data.data.customer.email);
                    }

                    // Update message status
                    setMessages(prev =>
                        prev.map(msg =>
                            msg.id === userMessage.id
                                ? { ...msg, status: 'sent' as const }
                                : msg
                        )
                    );
                } else {
                    console.error('❌ Error creating conversation:', data);
                    // Update message status to failed
                    setMessages(prev =>
                        prev.map(msg =>
                            msg.id === userMessage.id
                                ? { ...msg, status: 'sent' as const }
                                : msg
                        )
                    );
                }
            } else {
                // Subsequent messages - send to existing conversation
                console.log('💬 Sending message to conversation:', conversationId);

                // Use FormData if there are attachments, otherwise JSON
                const formData = new FormData();
                formData.append('customer_email', customerEmail);
                if (inputValue.trim()) {
                    formData.append('message', inputValue.trim());
                }

                // Append files
                attachedFiles.forEach((file, index) => {
                    formData.append(`attachments[${index}]`, file);
                });

                const response = await fetch(`/lc/api/conversations/${conversationId}/messages`, {
                    method: 'POST',
                    // Don't set Content-Type header - browser will set it with boundary for FormData
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    console.log('✅ Message sent:', data.data.message);

                    // Update message status
                    setMessages(prev =>
                        prev.map(msg =>
                            msg.id === userMessage.id
                                ? { ...msg, status: 'sent' as const }
                                : msg
                        )
                    );
                } else {
                    console.error('❌ Error sending message:', data);
                }
            }
        } catch (error) {
            console.error('❌ Network error:', error);
            // Keep message as sending state or show error
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
            const newFiles = Array.from(e.target.files);
            setAttachedFiles(prev => [...prev, ...newFiles]);
            console.log('📎 Archivos adjuntados:', newFiles);
        }
    };

    const removeAttachment = (index: number) => {
        setAttachedFiles(prev => prev.filter((_, i) => i !== index));
    };

    const triggerFileInput = () => {
        fileInputRef.current?.click();
    };

    const handleCloseConversation = async () => {
        if (!conversationId || !customerEmail) {
            console.error('❌ Cannot close conversation: missing ID or email');
            return;
        }

        if (!confirm('¿Estás seguro de que deseas cerrar esta conversación?')) {
            return;
        }

        setIsClosing(true);

        try {
            const response = await fetch(`/lc/api/conversations/${conversationId}/close`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    customer_email: customerEmail,
                }),
            });

            const data = await response.json();

            if (data.success) {
                console.log('✅ Conversation closed successfully');
                alert('Conversación cerrada. ¡Gracias por contactarnos!');
                // Clear conversation from storage and navigate home
                localStorage.removeItem('livechat_conversation_id');
                navigate('/');
            } else {
                console.error('❌ Failed to close conversation:', data);
                alert('No se pudo cerrar la conversación. Por favor, inténtalo de nuevo.');
            }
        } catch (error) {
            console.error('❌ Error closing conversation:', error);
            alert('Error al cerrar la conversación.');
        } finally {
            setIsClosing(false);
            setShowMenu(false);
        }
    };

    return (
        <div className="flex flex-col h-full bg-gray-50 opacity-0" style={{ animation: 'fadeIn 0.3s ease-out forwards' }}>
            {/* Header */}
            <div className="flex items-center justify-between px-4 py-3 bg-white border-b border-gray-200 shadow-sm">
                <Link to="/" className="flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-100 transition-colors">
                    <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>

                <div className="flex-1 flex items-center justify-center gap-3">
                    <div
                        className="w-8 h-8 rounded-full flex items-center justify-center text-white font-semibold text-sm"
                        style={{ backgroundColor: settings.primary_color }}
                    >
                        {settings.header_title?.charAt(0) || 'A'}
                    </div>
                    <div className="text-center">
                        <div className="font-semibold text-gray-900 text-sm">
                            {settings.header_title || 'Support Team'}
                        </div>
                        <div className="flex items-center justify-center gap-1 text-xs text-gray-500">
                            <span className="w-2 h-2 rounded-full bg-green-500"></span>
                            <span>Online</span>
                        </div>
                    </div>
                </div>

                <div className="relative">
                    <button
                        onClick={() => setShowMenu(!showMenu)}
                        className="flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-100 transition-colors"
                    >
                        <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                        </svg>
                    </button>

                    {/* Dropdown Menu */}
                    {showMenu && (
                        <div className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-10">
                            <button
                                onClick={handleCloseConversation}
                                disabled={isClosing}
                                className="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                            >
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                {isClosing ? 'Cerrando...' : 'Cerrar Conversación'}
                            </button>
                        </div>
                    )}
                </div>
            </div>

            {/* Messages Area */}
            <div className="flex-1 overflow-y-auto px-4 py-4 space-y-4">
                {messages.map((message, index) => (
                    <div
                        key={message.id}
                        className={`flex gap-2 ${message.author === 'user' ? 'flex-row-reverse' : 'flex-row'} opacity-0 animate-fade-in`}
                        style={{
                            animation: 'fadeIn 0.3s ease-out forwards',
                            animationDelay: `${Math.min(index * 50, 500)}ms`
                        }}
                    >
                        {/* Avatar - Agent */}
                        {message.author !== 'user' && settings.show_avatars && (
                            <div
                                className="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-white font-semibold text-xs"
                                style={{ backgroundColor: settings.primary_color }}
                            >
                                {settings.header_title?.charAt(0) || 'A'}
                            </div>
                        )}

                        {/* Message Content */}
                        <div className={`flex flex-col ${message.author === 'user' ? 'items-end' : 'items-start'} max-w-[75%]`}>
                            <div
                                className={`px-4 py-2 rounded-2xl ${
                                    message.author === 'user'
                                        ? 'text-white rounded-br-sm'
                                        : 'bg-white text-gray-900 rounded-bl-sm shadow-sm'
                                }`}
                                style={message.author === 'user' ? { backgroundColor: settings.primary_color } : {}}
                            >
                                {message.content && <p className="text-sm leading-relaxed">{message.content}</p>}

                                {/* Attachments */}
                                {message.attachments && message.attachments.length > 0 && (
                                    <div className={`space-y-2 ${message.content ? 'mt-2' : ''}`}>
                                        {message.attachments.map((attachment, idx) => (
                                            <a
                                                key={idx}
                                                href={attachment.url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className={`flex items-center gap-2 px-3 py-2 rounded-lg ${
                                                    message.author === 'user'
                                                        ? 'bg-white bg-opacity-20 hover:bg-opacity-30'
                                                        : 'bg-gray-100 hover:bg-gray-200'
                                                } transition-colors`}
                                            >
                                                <svg className="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-xs font-medium truncate">{attachment.name}</p>
                                                    <p className="text-xs opacity-75">{(attachment.size / 1024).toFixed(1)} KB</p>
                                                </div>
                                                <svg className="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </a>
                                        ))}
                                    </div>
                                )}
                            </div>

                            {/* Timestamp & Status */}
                            <div className="flex items-center gap-1 mt-1 px-1">
                                <span className="text-xs text-gray-500">
                                    {message.timestamp.toLocaleTimeString('en-US', {
                                        hour: 'numeric',
                                        minute: '2-digit',
                                        hour12: true
                                    })}
                                </span>
                                {message.author === 'user' && message.status && (
                                    <span className="text-xs text-gray-400">
                                        {message.status === 'sending' && '⏳'}
                                        {message.status === 'sent' && '✓'}
                                        {message.status === 'delivered' && '✓✓'}
                                    </span>
                                )}
                            </div>
                        </div>

                        {/* Avatar - User */}
                        {message.author === 'user' && settings.show_avatars && (
                            <div className="flex-shrink-0 w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-gray-600 font-semibold text-xs">
                                U
                            </div>
                        )}
                    </div>
                ))}
                <div ref={messagesEndRef} />
            </div>

            {/* Input Area */}
            <div className="px-4 py-3 bg-white border-t border-gray-200">
                {/* Attached Files Preview */}
                {attachedFiles.length > 0 && (
                    <div className="mb-3 space-y-1">
                        {attachedFiles.map((file, index) => (
                            <div key={index} className="flex items-center justify-between px-3 py-2 bg-gray-100 rounded-lg">
                                <div className="flex items-center gap-2 flex-1 min-w-0">
                                    <svg className="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span className="text-xs text-gray-700 truncate">
                                        {file.name}
                                    </span>
                                    <span className="text-xs text-gray-500 flex-shrink-0">
                                        ({(file.size / 1024).toFixed(1)} KB)
                                    </span>
                                </div>
                                <button
                                    onClick={() => removeAttachment(index)}
                                    className="ml-2 p-1 hover:bg-gray-200 rounded transition-colors"
                                >
                                    <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        ))}
                    </div>
                )}

                <div className="flex items-center gap-2">
                    {/* Hidden File Input */}
                    <input
                        ref={fileInputRef}
                        type="file"
                        multiple
                        onChange={handleFileSelect}
                        className="hidden"
                        accept="image/*,.pdf,.doc,.docx,.txt"
                    />

                    {/* Attachment Button */}
                    <button
                        onClick={triggerFileInput}
                        className="flex items-center justify-center w-9 h-9 rounded-full hover:bg-gray-100 transition-colors flex-shrink-0"
                    >
                        <svg className="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                    </button>

                    <input
                        ref={inputRef}
                        type="text"
                        value={inputValue}
                        onChange={(e) => {
                            setInputValue(e.target.value);
                        }}
                        onKeyPress={handleKeyPress}
                        placeholder={settings.input_placeholder || 'Type a message...'}
                        disabled={isSending}
                        className="flex-1 px-4 py-2 bg-gray-100 text-gray-900 rounded-full focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed text-sm"
                    />

                    <button
                        onClick={handleSendMessage}
                        disabled={!inputValue.trim() || isSending}
                        className="flex items-center justify-center w-9 h-9 rounded-full disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex-shrink-0"
                        style={{ backgroundColor: settings.primary_color }}
                        onMouseEnter={(e) => e.currentTarget.style.opacity = '0.9'}
                        onMouseLeave={(e) => e.currentTarget.style.opacity = '1'}
                    >
                        {isSending ? (
                            <svg className="animate-spin w-5 h-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        ) : (
                            <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        )}
                    </button>
                </div>
            </div>
        </div>
    );
}
