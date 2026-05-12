/**
 * WebRTC screen sharing — visitor side.
 *
 * Flow:
 *   1. visitor clicks "Share screen" → getDisplayMedia()
 *   2. create RTCPeerConnection with Google STUN
 *   3. createOffer() → POST /hd/api/webrtc/{id}/offer
 *   4. agent answers → received via Echo on the existing widget channel
 *      (helpdesk-widget-conversation.{id}.{pubsubToken}) as event `webrtc.answer`
 *   5. ICE candidates exchanged via /hd/api/webrtc/{id}/ice and the same Echo channel
 *
 * Activation requires inbox config flag `enable_screen_share === true` AND
 * `navigator.mediaDevices.getDisplayMedia` to be available (Safari iOS does not).
 */

const STUN_SERVERS: RTCIceServer[] = [
    { urls: 'stun:stun.l.google.com:19302' },
    { urls: 'stun:stun1.l.google.com:19302' },
];

type ScreenShareHandle = {
    stop: () => void;
};

type EchoLikeChannel = {
    listen: (event: string, callback: (data: any) => void) => EchoLikeChannel;
    stopListening: (event: string) => EchoLikeChannel;
};

type EchoLike = {
    channel: (name: string) => EchoLikeChannel;
    leave: (name: string) => void;
};

declare global {
    interface Window {
        HELPDESK_WIDGET_CONFIG?: {
            baseUrl?: string;
            websiteToken?: string;
            enable_screen_share?: boolean;
        };
    }
}

export function isScreenShareAvailable(): boolean {
    return typeof navigator !== 'undefined'
        && !!navigator.mediaDevices
        && typeof navigator.mediaDevices.getDisplayMedia === 'function';
}

export async function startScreenShare(
    conversationId: number,
    echo: EchoLike,
): Promise<ScreenShareHandle | null> {
    const cfg = window.HELPDESK_WIDGET_CONFIG ?? {};

    if (!cfg.enable_screen_share || !cfg.baseUrl || !cfg.websiteToken) {
        return null;
    }

    if (!isScreenShareAvailable()) {
        return null;
    }

    let stream: MediaStream;
    try {
        // Restrict the picker to the current browser tab only:
        //   - preferCurrentTab (Chrome/Edge): bypasses the source picker and
        //     auto-selects the current tab.
        //   - displaySurface: 'browser' / monitorTypeSurfaces: 'exclude':
        //     hides the "Entire screen" and "Application window" options on
        //     browsers that respect the hint, so the visitor cannot leak
        //     desktop or other apps to the agent.
        const constraints: MediaStreamConstraints & {
            preferCurrentTab?: boolean;
            selfBrowserSurface?: string;
            monitorTypeSurfaces?: string;
            surfaceSwitching?: string;
        } = {
            video: {
                displaySurface: 'browser',
            } as MediaTrackConstraints,
            audio: false,
            preferCurrentTab: true,
            selfBrowserSurface: 'include',
            monitorTypeSurfaces: 'exclude',
            surfaceSwitching: 'exclude',
        };

        stream = await navigator.mediaDevices.getDisplayMedia(constraints);

        // Defensive check — if the visitor's browser ignored the constraints
        // and they shared something other than a browser tab, abort to avoid
        // exposing their desktop to the agent.
        const settings = stream.getVideoTracks()[0]?.getSettings() as { displaySurface?: string };
        if (settings?.displaySurface && settings.displaySurface !== 'browser') {
            stream.getTracks().forEach((t) => t.stop());
            console.warn('[webrtc] visitor selected a non-tab surface — aborted', settings.displaySurface);
            return null;
        }
    } catch {
        // user cancelled or browser refused
        return null;
    }

    const peer = new RTCPeerConnection({ iceServers: STUN_SERVERS });

    stream.getTracks().forEach((track) => peer.addTrack(track, stream));

    // Reuse the existing public chat channel — the widget already subscribes
    // to it. The agent sends answer/ICE here via WebRtcSignal direction='to-widget'.
    const channelName = `helpdesk-widget-conversation.${conversationId}`;
    const channel = echo.channel(channelName);

    const post = async (path: string, body: object): Promise<void> => {
        try {
            await fetch(`${cfg.baseUrl}/hd/api/webrtc/${conversationId}/${path}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Website-Token': cfg.websiteToken!,
                },
                body: JSON.stringify(body),
                credentials: 'omit',
                keepalive: true,
            });
        } catch {
            // ignore signaling failures
        }
    };

    peer.onicecandidate = (event) => {
        if (event.candidate) {
            void post('ice', { candidate: event.candidate.toJSON() });
        }
    };

    channel.listen('.webrtc.answer', (data: any) => {
        if (data?.payload?.sdp && peer.signalingState !== 'closed') {
            void peer.setRemoteDescription({ type: 'answer', sdp: data.payload.sdp });
        }
    });

    channel.listen('.webrtc.ice', (data: any) => {
        if (data?.payload?.candidate && peer.signalingState !== 'closed') {
            void peer.addIceCandidate(new RTCIceCandidate(data.payload.candidate));
        }
    });

    const offer = await peer.createOffer();
    await peer.setLocalDescription(offer);
    await post('offer', { sdp: offer.sdp ?? '', type: 'offer' });

    const cleanup = () => {
        try { channel.stopListening('.webrtc.answer'); } catch { /* noop */ }
        try { channel.stopListening('.webrtc.ice'); } catch { /* noop */ }
        try { peer.close(); } catch { /* noop */ }
        stream.getTracks().forEach((t) => t.stop());
        void post('end', {});
    };

    // Auto-cleanup if user stops sharing via browser UI
    stream.getVideoTracks().forEach((track) => {
        track.addEventListener('ended', cleanup);
    });

    return {
        stop: cleanup,
    };
}
