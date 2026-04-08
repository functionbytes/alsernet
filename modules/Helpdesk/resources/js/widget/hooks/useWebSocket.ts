import { useEffect, useRef } from 'react';
import { getEcho, initializeEcho } from '../echo';

interface UseWebSocketOptions {
    conversationId?: string;
    onMessageReceived?: (message: any) => void;
    onTyping?: (data: { isTyping: boolean; user: string }) => void;
    onAgentJoined?: (agent: any) => void;
    enabled?: boolean;
}

/**
 * Hook for managing WebSocket connections for live chat conversations
 *
 * Usage:
 * ```tsx
 * const { sendMessage, typing } = useWebSocket({
 *     conversationId: '123',
 *     onMessageReceived: (msg) => setMessages(prev => [...prev, msg]),
 *     enabled: true // Set to false to use mock data instead
 * });
 * ```
 */
export function useWebSocket(options: UseWebSocketOptions) {
    const {
        conversationId,
        onMessageReceived,
        onTyping,
        onAgentJoined,
        enabled = false // Disabled by default until backend is configured
    } = options;

    const echoRef = useRef(getEcho());

    useEffect(() => {
        if (!enabled || !conversationId) {
            console.log('⏸️  WebSocket disabled or no conversation ID');
            return;
        }

        // Initialize Echo if not already done
        if (!echoRef.current) {
            try {
                echoRef.current = initializeEcho();
            } catch (error) {
                console.error('❌ Failed to initialize Echo:', error);
                return;
            }
        }

        const channelName = `conversation.${conversationId}`;
        console.log(`🔌 Subscribing to channel: ${channelName}`);

        // Join the private conversation channel
        const channel = echoRef.current
            .private(channelName)
            .listen('.message.sent', (data: any) => {
                console.log('📩 New message received:', data);
                onMessageReceived?.(data.message);
            })
            .listenForWhisper('typing', (data: any) => {
                console.log('⌨️  Typing event:', data);
                onTyping?.(data);
            })
            .listen('.agent.joined', (data: any) => {
                console.log('👤 Agent joined:', data);
                onAgentJoined?.(data.agent);
            });

        // Cleanup on unmount
        return () => {
            console.log(`🔌 Unsubscribing from channel: ${channelName}`);
            echoRef.current?.leave(channelName);
        };
    }, [conversationId, enabled, onMessageReceived, onTyping, onAgentJoined]);

    /**
     * Send a typing indicator to other participants
     */
    const sendTyping = (isTyping: boolean) => {
        if (!enabled || !conversationId || !echoRef.current) return;

        const channelName = `conversation.${conversationId}`;
        echoRef.current.private(channelName).whisper('typing', {
            isTyping,
            user: 'customer'
        });
    };

    return {
        sendTyping,
        isConnected: !!echoRef.current && enabled
    };
}
