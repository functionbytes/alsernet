import { useEffect, useCallback } from 'react';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

interface Message {
  id: number;
  conversation_id: number;
  author_id?: number;
  user_id?: number;
  type: string;
  body: string;
  html_body?: string;
  is_internal: boolean;
  created_at: string;
  sender_name: string;
  sender_avatar?: string;
}

interface WebSocketEvent {
  type: 'message' | 'typing' | 'read' | 'status_change' | 'assigned';
  data: any;
}

/**
 * Hook para manejar WebSocket en conversaciones
 * Requiere Laravel Reverb o Pusher configurado
 */
export function useConversationWebSocket(
  conversationId: number,
  onMessage: (message: Message) => void,
  onTyping?: (userId: number, isTyping: boolean) => void,
  onStatusChange?: (status: string) => void
) {
  useEffect(() => {
    // Verificar si Echo está disponible
    if (!window.Echo) {
      console.warn('Laravel Echo no está configurado');
      return;
    }

    const echo = window.Echo as typeof Echo;

    // Canal privado para la conversación
    const channel = echo.private(`conversations.${conversationId}`);

    // Escuchar nuevo mensaje
    channel.listen('ConversationMessageCreated', (event: { message: Message }) => {
      onMessage(event.message);
    });

    // Escuchar indicador de escritura
    if (onTyping) {
      channel.listen('UserTyping', (event: { user_id: number; is_typing: boolean }) => {
        onTyping(event.user_id, event.is_typing);
      });
    }

    // Escuchar cambio de estado
    if (onStatusChange) {
      channel.listen('ConversationStatusChanged', (event: { status: string }) => {
        onStatusChange(event.status);
      });
    }

    // Escuchar confirmación de lectura
    channel.listen('MessageRead', (event: { message_id: number; user_id: number }) => {
      // Actualizar UI de recibido
    });

    // Cleanup
    return () => {
      echo.leaveChannel(`conversations.${conversationId}`);
    };
  }, [conversationId, onMessage, onTyping, onStatusChange]);

  /**
   * Enviar evento de escritura
   */
  const broadcastTyping = useCallback((isTyping: boolean) => {
    if (!window.Echo) return;

    fetch(`/api/helpdesk/conversations/${conversationId}/typing`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({ is_typing: isTyping }),
    }).catch(err => console.error('Error sending typing event:', err));
  }, [conversationId]);

  /**
   * Marcar mensaje como leído
   */
  const markAsRead = useCallback((messageId: number) => {
    if (!window.Echo) return;

    fetch(`/api/helpdesk/messages/${messageId}/read`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    }).catch(err => console.error('Error marking as read:', err));
  }, []);

  return { broadcastTyping, markAsRead };
}
