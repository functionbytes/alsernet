import React, { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { MessageList } from './MessageList';
import { MessageComposer } from './MessageComposer';
import { useConversationWebSocket } from '../../hooks/useConversationWebSocket';

interface ConversationDetailProps {
  conversationId: number;
  currentUserId: number;
}

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
  attachment_urls?: string[];
}

interface CannedReply {
  id: number;
  title: string;
  body: string;
  html_body: string;
}

/**
 * ConversationDetail - Componente principal para chat en tiempo real
 * Integra MessageList + MessageComposer + WebSocket
 */
export function ConversationDetail({ conversationId, currentUserId }: ConversationDetailProps) {
  const [messages, setMessages] = useState<Message[]>([]);
  const [typingUsers, setTypingUsers] = useState<Set<number>>(new Set());
  const [isInternal, setIsInternal] = useState(false);
  const queryClient = useQueryClient();

  // Fetch initial messages
  const messagesQuery = useQuery({
    queryKey: ['conversation-messages', conversationId],
    queryFn: async () => {
      const response = await fetch(
        `/api/helpdesk/conversations/${conversationId}/messages`
      );
      if (!response.ok) throw new Error('Failed to fetch messages');
      return response.json();
    },
  });

  // Fetch canned replies
  const cannedRepliesQuery = useQuery({
    queryKey: ['canned-replies'],
    queryFn: async () => {
      const response = await fetch('/api/helpdesk/canned-replies');
      if (!response.ok) throw new Error('Failed to fetch canned replies');
      return response.json();
    },
  });

  // Mutation for sending messages
  const sendMessageMutation = useMutation({
    mutationFn: async (data: {
      body: string;
      html_body: string;
      attachments: File[];
      is_internal: boolean;
    }) => {
      const formData = new FormData();
      formData.append('body', data.body);
      formData.append('html_body', data.html_body);
      formData.append('is_internal', String(data.is_internal));

      data.attachments.forEach((file, idx) => {
        formData.append(`attachments[${idx}]`, file);
      });

      const response = await fetch(`/api/helpdesk/conversations/${conversationId}/messages`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute(
            'content'
          ) || '',
        },
        body: formData,
      });

      if (!response.ok) throw new Error('Failed to send message');
      return response.json();
    },
    onSuccess: newMessage => {
      // Add message to list
      setMessages(prev => [...prev, newMessage]);
      // Invalidate query to refresh
      queryClient.invalidateQueries({ queryKey: ['conversation-messages', conversationId] });
      setIsInternal(false);
    },
  });

  // Setup WebSocket
  useConversationWebSocket(
    conversationId,
    // onMessage
    newMessage => {
      setMessages(prev => [...prev, newMessage]);
    },
    // onTyping
    (userId, isTyping) => {
      setTypingUsers(prev => {
        const updated = new Set(prev);
        if (isTyping) {
          updated.add(userId);
        } else {
          updated.delete(userId);
        }
        return updated;
      });
    },
    // onStatusChange
    status => {
      queryClient.invalidateQueries({ queryKey: ['conversation', conversationId] });
    }
  );

  // Load initial messages
  useEffect(() => {
    if (messagesQuery.data) {
      setMessages(messagesQuery.data);
    }
  }, [messagesQuery.data]);

  const handleMessageSubmit = async (
    body: string,
    htmlBody: string,
    attachments: File[]
  ) => {
    await sendMessageMutation.mutateAsync({
      body,
      html_body: htmlBody,
      attachments,
      is_internal: isInternal,
    });
  };

  const handleMessageRead = async (messageId: number) => {
    // Mark as read via API (fire and forget)
    fetch(`/api/helpdesk/messages/${messageId}/read`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute(
          'content'
        ) || '',
      },
    }).catch(err => console.error('Error marking as read:', err));
  };

  return (
    <div className="conversation-detail">
      {/* Loading state */}
      {messagesQuery.isLoading && (
        <div className="text-center py-4">
          <div className="spinner-border text-primary" role="status">
            <span className="visually-hidden">Cargando conversación...</span>
          </div>
        </div>
      )}

      {/* Error state */}
      {messagesQuery.isError && (
        <div className="alert alert-danger">
          <strong>Error:</strong> No se pudo cargar la conversación. Por favor, intenta de nuevo.
        </div>
      )}

      {/* Messages list */}
      {messagesQuery.isSuccess && (
        <div className="mb-3">
          <MessageList
            messages={messages}
            currentUserId={currentUserId}
            isLoading={messagesQuery.isLoading}
            onMessageRead={handleMessageRead}
          />

          {/* Typing indicator */}
          {typingUsers.size > 0 && (
            <div className="mb-2">
              <small className="text-muted">
                <i className="fa fa-ellipsis"></i> Escribiendo...
              </small>
            </div>
          )}
        </div>
      )}

      {/* Message composer */}
      <MessageComposer
        conversationId={conversationId}
        onSubmit={handleMessageSubmit}
        cannedReplies={cannedRepliesQuery.data || []}
        isSubmitting={sendMessageMutation.isPending}
        isInternal={isInternal}
        onInternalChange={setIsInternal}
      />
    </div>
  );
}
