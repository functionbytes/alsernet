import { useState, useRef, useEffect } from 'react';
import { startLivestream, hasLiveViewConsent, setLiveViewConsent } from '../livestream';

interface UseLivestreamRecorderOptions {
    conversationId: string | null;
    liveViewEnabled: boolean;
    websiteToken: string;
}

interface UseLivestreamRecorderReturn {
    liveViewConsent: boolean;
    handleToggleLiveViewConsent: () => void;
}

export function useLivestreamRecorder({
    conversationId,
    liveViewEnabled,
    websiteToken,
}: UseLivestreamRecorderOptions): UseLivestreamRecorderReturn {
    const [liveViewConsent, setLiveViewConsentState] = useState<boolean>(
        () => hasLiveViewConsent(websiteToken)
    );
    const livestreamHandleRef = useRef<{ stop: () => void } | null>(null);

    useEffect(() => {
        if (!liveViewEnabled || !liveViewConsent || !conversationId) {
            if (livestreamHandleRef.current) {
                livestreamHandleRef.current.stop();
                livestreamHandleRef.current = null;
            }
            return;
        }

        let cancelled = false;
        startLivestream(parseInt(conversationId, 10), websiteToken).then((handle) => {
            if (cancelled) {
                handle?.stop();
                return;
            }
            livestreamHandleRef.current = handle;
        });

        return () => {
            cancelled = true;
            if (livestreamHandleRef.current) {
                livestreamHandleRef.current.stop();
                livestreamHandleRef.current = null;
            }
        };
    }, [liveViewEnabled, liveViewConsent, conversationId, websiteToken]);

    const handleToggleLiveViewConsent = () => {
        const next = !liveViewConsent;
        setLiveViewConsent(websiteToken, next);
        setLiveViewConsentState(next);
    };

    return { liveViewConsent, handleToggleLiveViewConsent };
}
