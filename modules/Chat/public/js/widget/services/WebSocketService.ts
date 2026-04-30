/**
 * WebSocketService - Laravel Echo/Reverb WebSocket connection manager
 */

import type { WebSocketEvent, Message } from '../types';

// Laravel Echo will be loaded globally from CDN or build
declare global {
    interface Window {
        Echo?: any;
        Pusher?: any;
    }
}

type EventCallback = (data: any) => void;

export class WebSocketService {
    private echo: any = null;
    private conversationChannel: any = null;
    private presenceChannel: any = null;
    private connected: boolean = false;
    private eventHandlers: Map<WebSocketEvent, Set<EventCallback>> = new Map();

    constructor(
        private config: {
            key: string;
            host: string;
            port: number;
            forceTLS: boolean;
        }
    ) {}

    /**
     * Connect to WebSocket server
     */
    connect(): void {
        if (this.connected || !window.Pusher) {
            console.warn('WebSocket already connected or Pusher not loaded');
            return;
        }

        try {
            // Import Laravel Echo dynamically
            if (!window.Echo) {
                console.error('Laravel Echo not loaded');
                return;
            }

            this.echo = new window.Echo({
                broadcaster: 'reverb',
                key: this.config.key,
                wsHost: this.config.host,
                wsPort: this.config.port,
                wssPort: this.config.port,
                forceTLS: this.config.forceTLS,
                enabledTransports: ['ws', 'wss'],
                disableStats: true,
            });

            this.connected = true;
            console.log('WebSocket connected');
        } catch (error) {
            console.error('Failed to connect to WebSocket:', error);
        }
    }

    /**
     * Subscribe to conversation channel
     */
    subscribeToConversation(conversationId: number): void {
        if (!this.echo) {
            console.warn('WebSocket not initialized');
            return;
        }

        try {
            const channelName = `conversation.${conversationId}`;
            this.conversationChannel = this.echo.private(channelName);

            // Listen for new messages
            this.conversationChannel.listen('.message.sent', (event: any) => {
                this.emit('new_message', event.message);
            });

            // Listen for typing indicators
            this.conversationChannel.listenForWhisper('typing', (event: any) => {
                if (event.typing) {
                    this.emit('typing_on', event);
                } else {
                    this.emit('typing_off', event);
                }
            });

            // Listen for agent assignment
            this.conversationChannel.listen('.conversation.assigned', (event: any) => {
                this.emit('agent_assigned', event);
            });

            // Listen for status changes
            this.conversationChannel.listen('.conversation.status.changed', (event: any) => {
                this.emit('conversation_status_changed', event);
            });

            console.log(`Subscribed to conversation ${conversationId}`);
        } catch (error) {
            console.error('Failed to subscribe to conversation:', error);
        }
    }

    /**
     * Unsubscribe from conversation channel
     */
    unsubscribeFromConversation(): void {
        if (this.conversationChannel) {
            this.echo.leave(this.conversationChannel.name);
            this.conversationChannel = null;
            console.log('Unsubscribed from conversation');
        }
    }

    /**
     * Send typing indicator
     */
    sendTyping(isTyping: boolean): void {
        if (!this.conversationChannel) {
            return;
        }

        try {
            this.conversationChannel.whisper('typing', {
                typing: isTyping,
                timestamp: Date.now(),
            });
        } catch (error) {
            console.error('Failed to send typing indicator:', error);
        }
    }

    /**
     * Subscribe to presence channel for agent availability
     */
    subscribeToPresence(channelName: string = 'agents'): void {
        if (!this.echo) {
            console.warn('WebSocket not initialized');
            return;
        }

        try {
            this.presenceChannel = this.echo.join(channelName);

            this.presenceChannel
                .here((users: any[]) => {
                    this.emit('presence_update', { online: users });
                })
                .joining((user: any) => {
                    this.emit('presence_update', { joining: user });
                })
                .leaving((user: any) => {
                    this.emit('presence_update', { leaving: user });
                });

            console.log('Subscribed to presence channel');
        } catch (error) {
            console.error('Failed to subscribe to presence:', error);
        }
    }

    /**
     * Register event handler
     */
    on(event: WebSocketEvent, callback: EventCallback): void {
        if (!this.eventHandlers.has(event)) {
            this.eventHandlers.set(event, new Set());
        }
        this.eventHandlers.get(event)!.add(callback);
    }

    /**
     * Unregister event handler
     */
    off(event: WebSocketEvent, callback: EventCallback): void {
        if (this.eventHandlers.has(event)) {
            this.eventHandlers.get(event)!.delete(callback);
        }
    }

    /**
     * Emit event to registered handlers
     */
    private emit(event: WebSocketEvent, data: any): void {
        if (this.eventHandlers.has(event)) {
            this.eventHandlers.get(event)!.forEach((callback) => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`Error in ${event} handler:`, error);
                }
            });
        }
    }

    /**
     * Disconnect from WebSocket
     */
    disconnect(): void {
        if (this.echo) {
            this.unsubscribeFromConversation();
            this.echo.disconnect();
            this.echo = null;
            this.connected = false;
            this.eventHandlers.clear();
            console.log('WebSocket disconnected');
        }
    }

    /**
     * Check if connected
     */
    isConnected(): boolean {
        return this.connected;
    }
}
