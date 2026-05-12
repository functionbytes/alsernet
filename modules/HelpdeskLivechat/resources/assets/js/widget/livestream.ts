/**
 * Livestream — pushes rrweb DOM mutation batches to the agent panel.
 *
 * Activation requires:
 *   1. inbox config flag `enable_live_view === true`
 *   2. visitor consent (localStorage `hd_liveview_consent_<websiteToken>` === 'yes')
 *
 * On consent, rrweb is dynamically imported (~200KB chunk only loaded when needed).
 * Events are buffered and POSTed every 2s or once batch ≥ 50 events.
 */

const CONSENT_PREFIX = 'hd_liveview_consent_';
// Flush window — kept small for near-real-time agent view. 500ms means a
// click/mouse-move appears in the agent panel within ~500ms of happening.
// Lower than this and the request rate overwhelms Reverb on busy pages.
const FLUSH_INTERVAL_MS = 500;
// Threshold to flush early during activity bursts (clicks, scrolls).
const FLUSH_THRESHOLD = 20;

type LivestreamHandle = {
    stop: () => void;
};

declare global {
    interface Window {
        HELPDESK_WIDGET_CONFIG?: {
            baseUrl?: string;
            websiteToken?: string;
            enable_live_view?: boolean;
        };
    }
}

export function hasLiveViewConsent(websiteToken: string): boolean {
    try {
        return localStorage.getItem(CONSENT_PREFIX + websiteToken) === 'yes';
    } catch {
        return false;
    }
}

export function setLiveViewConsent(websiteToken: string, granted: boolean): void {
    try {
        localStorage.setItem(CONSENT_PREFIX + websiteToken, granted ? 'yes' : 'no');
    } catch {
        // ignore storage errors (private mode etc.)
    }
}

export async function startLivestream(
    conversationId: number,
    websiteToken: string,
): Promise<LivestreamHandle | null> {
    const cfg = window.HELPDESK_WIDGET_CONFIG ?? {};

    if (!cfg.enable_live_view) {
        return null;
    }

    if (!hasLiveViewConsent(websiteToken)) {
        return null;
    }

    if (!cfg.baseUrl) {
        return null;
    }

    let rrweb: typeof import('rrweb');
    try {
        // Dynamic import keeps rrweb out of the main bundle.
        rrweb = await import('rrweb');
    } catch {
        // rrweb failed to load — silently disable.
        return null;
    }

    const buffer: any[] = [];
    let batchIndex = 0;
    let flushTimer: ReturnType<typeof setInterval> | null = null;
    let stopRecording: (() => void) | null = null;

    const flush = async (): Promise<void> => {
        if (buffer.length === 0) {
            return;
        }
        const events = buffer.splice(0, buffer.length);
        const idx = batchIndex++;

        try {
            await fetch(
                `${cfg.baseUrl}/hd/api/livestream/${conversationId}/events`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Website-Token': websiteToken,
                    },
                    body: JSON.stringify({ events, batch_index: idx }),
                    credentials: 'omit',
                    keepalive: true,
                },
            );
        } catch {
            // Network failure — drop batch silently rather than break the chat.
        }
    };

    stopRecording = rrweb.record({
        emit: (event: any) => {
            buffer.push(event);
            if (buffer.length >= FLUSH_THRESHOLD) {
                void flush();
            }
        },
        maskAllInputs: true,
        maskTextSelector: '[data-private]',
        recordCanvas: false,
        collectFonts: false,
        // Take a fresh full DOM snapshot every 10s so agents that join late
        // never wait more than that to see the visitor's actual page. Without
        // this rrweb only emits one Meta+FullSnapshot at the very start.
        checkoutEveryNms: 10_000,
    } as any) as unknown as () => void;

    // Force an immediate full snapshot so the very first batch already
    // contains a renderable initial frame (otherwise the panel sees only
    // mouse-move incrementals until the visitor changes the DOM).
    if (typeof (rrweb as any).takeFullSnapshot === 'function') {
        try {
            (rrweb as any).takeFullSnapshot(true);
        } catch {
            // ignore — rrweb not yet mounted root
        }
    }

    flushTimer = setInterval(() => void flush(), FLUSH_INTERVAL_MS);

    return {
        stop: () => {
            if (flushTimer !== null) {
                clearInterval(flushTimer);
                flushTimer = null;
            }
            if (stopRecording) {
                stopRecording();
                stopRecording = null;
            }
            void flush();
        },
    };
}
