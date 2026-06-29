import { useCallback } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';

interface SessionMessage {
    id: number;
    role: 'user' | 'assistant' | 'system';
    content: string;
    created_at: string;
}

interface AiSession {
    id: number;
    ai_agent_id: number;
    conversation_id: number;
    customer_id: number;
    status: 'active' | 'paused' | 'completed';
    context: Record<string, any>;
    started_at: string;
    ended_at: string | null;
    messages: SessionMessage[];
}

interface UseAiSessionProps {
    sessionId?: number;
    conversationId?: number;
    flowId?: number;
}

export function useAiSession({
    sessionId,
    conversationId,
    flowId,
}: UseAiSessionProps = {}) {
    const queryClient = useQueryClient();

    // Fetch session
    const { data: session, isLoading: isLoadingSession } = useQuery({
        queryKey: ['ai-session', sessionId],
        queryFn: () =>
            axios
                .get(`/manager/helpdesk/ai-agent/sessions/${sessionId}`)
                .then((r) => r.data),
        enabled: !!sessionId,
    });

    // Fetch messages for session
    const { data: messages, isLoading: isLoadingMessages } = useQuery({
        queryKey: ['ai-session-messages', sessionId],
        queryFn: () =>
            axios
                .get(`/manager/helpdesk/ai-agent/sessions/${sessionId}/messages`)
                .then((r) => r.data),
        enabled: !!sessionId,
    });

    // Start new session
    const startSessionMutation = useMutation({
        mutationFn: (data: {
            flow_id: number;
            conversation_id: number;
            customer_id: number;
        }) =>
            axios
                .post('/manager/helpdesk/ai-agent/sessions', data)
                .then((r) => r.data),
        onSuccess: (newSession) => {
            queryClient.invalidateQueries({ queryKey: ['ai-sessions'] });
            return newSession;
        },
    });

    // Send message to AI
    const sendMessageMutation = useMutation({
        mutationFn: (data: { content: string; session_id: number }) =>
            axios
                .post(
                    `/manager/helpdesk/ai-agent/sessions/${data.session_id}/messages`,
                    { content: data.content }
                )
                .then((r) => r.data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['ai-session-messages', sessionId] });
        },
    });

    // End session
    const endSessionMutation = useMutation({
        mutationFn: () =>
            axios
                .post(`/manager/helpdesk/ai-agent/sessions/${sessionId}/end`)
                .then((r) => r.data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['ai-session', sessionId] });
        },
    });

    // Update session context
    const updateContextMutation = useMutation({
        mutationFn: (context: Record<string, any>) =>
            axios
                .put(`/manager/helpdesk/ai-agent/sessions/${sessionId}/context`, {
                    context,
                })
                .then((r) => r.data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['ai-session', sessionId] });
        },
    });

    // Helper to start a new session
    const startSession = useCallback(
        async (flowId: number, conversationId: number, customerId: number) => {
            return startSessionMutation.mutateAsync({
                flow_id: flowId,
                conversation_id: conversationId,
                customer_id: customerId,
            });
        },
        [startSessionMutation]
    );

    // Helper to send message
    const sendMessage = useCallback(
        async (content: string) => {
            if (!sessionId) {
                console.error('No active session');
                return;
            }

            return sendMessageMutation.mutateAsync({
                content,
                session_id: sessionId,
            });
        },
        [sessionId, sendMessageMutation]
    );

    // Helper to end session
    const endSession = useCallback(async () => {
        return endSessionMutation.mutateAsync();
    }, [endSessionMutation]);

    // Helper to update context
    const updateContext = useCallback(
        async (context: Record<string, any>) => {
            return updateContextMutation.mutateAsync(context);
        },
        [updateContextMutation]
    );

    return {
        // State
        session,
        messages,
        isLoading: isLoadingSession || isLoadingMessages,
        isLoadingSession,
        isLoadingMessages,

        // Mutations
        isStarting: startSessionMutation.isPending,
        isSending: sendMessageMutation.isPending,
        isEnding: endSessionMutation.isPending,
        isUpdating: updateContextMutation.isPending,

        // Methods
        startSession,
        sendMessage,
        endSession,
        updateContext,

        // Errors
        errors: {
            startError: startSessionMutation.error,
            sendError: sendMessageMutation.error,
            endError: endSessionMutation.error,
            updateError: updateContextMutation.error,
        },
    };
}
