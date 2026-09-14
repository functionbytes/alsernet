import { useState, useEffect } from 'react';
import { echo } from '../echo';
import { startScreenShare, isScreenShareAvailable } from '../webrtc';

interface UseWebRtcScreenShareOptions {
    conversationId: string | null;
    screenShareEnabled: boolean;
}

interface UseWebRtcScreenShareReturn {
    screenShareHandle: { stop: () => void } | null;
    screenShareRequest: { agentName: string } | null;
    handleToggleScreenShare: () => Promise<void>;
    handleAcceptScreenShareRequest: () => Promise<void>;
    handleDeclineScreenShareRequest: () => void;
}

export { isScreenShareAvailable };

export function useWebRtcScreenShare({
    conversationId,
    screenShareEnabled,
}: UseWebRtcScreenShareOptions): UseWebRtcScreenShareReturn {
    const [screenShareHandle, setScreenShareHandle] = useState<{ stop: () => void } | null>(null);
    const [screenShareRequest, setScreenShareRequest] = useState<{ agentName: string } | null>(null);

    // Listen for agent-initiated screen share requests
    useEffect(() => {
        if (!screenShareEnabled || !conversationId) return;

        const channelName = `helpdesk-widget-conversation.${conversationId}`;
        const channel = echo.channel(channelName);

        const onRequest = (data: any) => {
            if (screenShareHandle) return;
            const agentName = data?.payload?.agent_name ?? 'El agente';
            setScreenShareRequest({ agentName });
        };

        channel.listen('.webrtc.request', onRequest);

        return () => {
            try { channel.stopListening('.webrtc.request'); } catch { /* noop */ }
        };
    }, [screenShareEnabled, conversationId, screenShareHandle]);

    // Cleanup on unmount
    useEffect(() => {
        return () => {
            if (screenShareHandle) screenShareHandle.stop();
        };
    }, [screenShareHandle]);

    const handleToggleScreenShare = async () => {
        if (screenShareHandle) {
            screenShareHandle.stop();
            setScreenShareHandle(null);
            return;
        }
        if (!conversationId) return;
        const handle = await startScreenShare(parseInt(conversationId, 10), echo as any);
        if (handle) setScreenShareHandle(handle);
    };

    const handleAcceptScreenShareRequest = async () => {
        setScreenShareRequest(null);
        if (!conversationId) return;
        const handle = await startScreenShare(parseInt(conversationId, 10), echo as any);
        if (handle) setScreenShareHandle(handle);
    };

    const handleDeclineScreenShareRequest = () => {
        setScreenShareRequest(null);
    };

    return {
        screenShareHandle,
        screenShareRequest,
        handleToggleScreenShare,
        handleAcceptScreenShareRequest,
        handleDeclineScreenShareRequest,
    };
}
