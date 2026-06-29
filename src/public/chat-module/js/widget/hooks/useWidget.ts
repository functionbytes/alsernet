/**
 * useWidget - Main widget state management hook
 */

import { useState, useEffect, useCallback, useRef } from 'react';
import { ApiService } from '../services/ApiService';
import { StorageService } from '../services/StorageService';
import { WebSocketService } from '../services/WebSocketService';
import type { WidgetConfig, WidgetState, Customer, Message } from '../types';

export function useWidget(config: WidgetConfig) {
    const [state, setState] = useState<WidgetState>({
        isOpen: false,
        isMinimized: false,
        conversationId: null,
        customer: null,
        messages: [],
        unreadCount: 0,
        isTyping: false,
        agentsOnline: false,
        showPreChatForm: false,
    });

    const apiService = useRef<ApiService | null>(null);
    const wsService = useRef<WebSocketService | null>(null);
    const typingTimeout = useRef<NodeJS.Timeout | null>(null);

    /**
     * Initialize services
     */
    useEffect(() => {
        apiService.current = new ApiService(config.baseUrl, config.websiteToken);

        // Initialize WebSocket if Reverb config is available
        if (window.Pusher) {
            wsService.current = new WebSocketService({
                key: (window as any).REVERB_APP_KEY || 'default',
                host: (window as any).REVERB_HOST || window.location.hostname,
                port: (window as any).REVERB_PORT || 8080,
                forceTLS: (window as any).REVERB_SCHEME === 'https',
            });
            wsService.current.connect();
        }

        // Load existing session from storage
        const existingConversationId = StorageService.getConversationId();
        const existingCustomer = StorageService.getCustomer();

        if (existingConversationId && existingCustomer) {
            setState((prev) => ({
                ...prev,
                conversationId: existingConversationId,
                customer: existingCustomer,
            }));

            // Load messages
            loadMessages(existingConversationId);

            // Subscribe to WebSocket
            if (wsService.current) {
                wsService.current.subscribeToConversation(existingConversationId);
            }
        }

        // Cleanup
        return () => {
            if (wsService.current) {
                wsService.current.disconnect();
            }
        };
    }, [config]);

    /**
     * Load messages for conversation
     */
    const loadMessages = useCallback(async (conversationId: number) => {
        if (!apiService.current) return;

        const response = await apiService.current.getMessages(conversationId);
        if (response.success && response.data) {
            setState((prev) => ({
                ...prev,
                messages: response.data!.messages,
            }));
        }
    }, []);

    /**
     * Initialize conversation
     */
    const initConversation = useCallback(async (customerData?: Partial<Customer>) => {
        if (!apiService.current) return;

        const response = await apiService.current.initConversation(customerData);

        if (response.success && response.data) {
            const { conversation_id, customer_id, session_token } = response.data;

            const customer: Customer = {
                id: customer_id,
                name: customerData?.name || 'Guest',
                email: customerData?.email,
                phone_number: customerData?.phone_number,
            };

            setState((prev) => ({
                ...prev,
                conversationId: conversation_id,
                customer,
                showPreChatForm: false,
            }));

            // Save to storage
            StorageService.setConversationId(conversation_id);
            StorageService.setCustomer(customer);
            if (session_token) {
                StorageService.setSessionToken(session_token);
            }

            // Subscribe to WebSocket
            if (wsService.current) {
                wsService.current.subscribeToConversation(conversation_id);
            }

            // Load messages
            await loadMessages(conversation_id);
        }
    }, [loadMessages]);

    /**
     * Send message
     */
    const sendMessage = useCallback(async (content: string) => {
        if (!apiService.current || !state.conversationId) return;

        // Optimistically add message to UI
        const tempMessage: Message = {
            id: Date.now(),
            content,
            message_type: 'outgoing',
            content_type: 'text',
            created_at: new Date().toISOString(),
            sender: {
                id: state.customer?.id || 0,
                type: 'Customer',
                name: state.customer?.name || 'You',
            },
        };

        setState((prev) => ({
            ...prev,
            messages: [...prev.messages, tempMessage],
        }));

        // Send to server
        const response = await apiService.current.sendMessage(
            state.conversationId,
            content
        );

        if (!response.success) {
            // Remove optimistic message on failure
            setState((prev) => ({
                ...prev,
                messages: prev.messages.filter((m) => m.id !== tempMessage.id),
            }));
        }
    }, [state.conversationId, state.customer]);

    /**
     * Upload file
     */
    const uploadFile = useCallback(async (file: File) => {
        if (!apiService.current || !state.conversationId) return;

        const response = await apiService.current.uploadFile(state.conversationId, file);

        if (response.success) {
            // Message will arrive via WebSocket
            console.log('File uploaded successfully');
        }
    }, [state.conversationId]);

    /**
     * Toggle widget open/close
     */
    const toggleWidget = useCallback(() => {
        setState((prev) => {
            const newIsOpen = !prev.isOpen;

            // Reset unread count when opening
            if (newIsOpen) {
                return { ...prev, isOpen: newIsOpen, unreadCount: 0 };
            }

            return { ...prev, isOpen: newIsOpen };
        });
    }, []);

    /**
     * Show pre-chat form
     */
    const showPreChatForm = useCallback(() => {
        setState((prev) => ({ ...prev, showPreChatForm: true }));
    }, []);

    /**
     * Handle typing indicator
     */
    const setTyping = useCallback((isTyping: boolean) => {
        if (wsService.current) {
            wsService.current.sendTyping(isTyping);
        }

        // Auto-stop typing after 3 seconds
        if (isTyping) {
            if (typingTimeout.current) {
                clearTimeout(typingTimeout.current);
            }
            typingTimeout.current = setTimeout(() => {
                if (wsService.current) {
                    wsService.current.sendTyping(false);
                }
            }, 3000);
        }
    }, []);

    /**
     * WebSocket event handlers
     */
    useEffect(() => {
        if (!wsService.current) return;

        const handleNewMessage = (message: Message) => {
            setState((prev) => {
                // Don't add if already exists
                if (prev.messages.some((m) => m.id === message.id)) {
                    return prev;
                }

                // Increment unread if widget is closed
                const newUnreadCount = prev.isOpen
                    ? prev.unreadCount
                    : prev.unreadCount + 1;

                return {
                    ...prev,
                    messages: [...prev.messages, message],
                    unreadCount: newUnreadCount,
                };
            });
        };

        const handleTypingOn = () => {
            setState((prev) => ({ ...prev, isTyping: true }));
        };

        const handleTypingOff = () => {
            setState((prev) => ({ ...prev, isTyping: false }));
        };

        wsService.current.on('new_message', handleNewMessage);
        wsService.current.on('typing_on', handleTypingOn);
        wsService.current.on('typing_off', handleTypingOff);

        return () => {
            if (wsService.current) {
                wsService.current.off('new_message', handleNewMessage);
                wsService.current.off('typing_on', handleTypingOn);
                wsService.current.off('typing_off', handleTypingOff);
            }
        };
    }, []);

    return {
        state,
        initConversation,
        sendMessage,
        uploadFile,
        toggleWidget,
        showPreChatForm,
        setTyping,
    };
}
