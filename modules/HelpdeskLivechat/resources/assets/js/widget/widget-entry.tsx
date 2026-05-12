import React from 'react';
import ReactDOM from 'react-dom/client';
import { WidgetApp } from './WidgetApp';
import { WidgetContainer } from './WidgetContainer';
import { registerGlobalApi } from './widget-identity';
import { useWidgetStore, RecommendationProduct } from './widget-store';
import { startHeartbeat } from './widget-session';
import './widget.css';

// Register window.helpdeskWidgetIdentify() so the host site can update
// visitor data (name, email, cart, orders) at any time — e.g. after login.
registerGlobalApi();

// Expose window.HelpdeskWidget so the Engagement bridge (spa.blade.php)
// and any host-site code can programmatically open/close/toggle the widget.
declare global {
    interface Window {
        HelpdeskWidget?: {
            open: () => void;
            close: () => void;
            toggle: () => void;
            botMessage: (text: string) => void;
            prefill: (fields: Record<string, string>) => void;
            requestRating: () => void;
            showRecommendations: (products: RecommendationProduct[]) => void;
        };
        chat?: (...args: any[]) => any;
    }
}

window.HelpdeskWidget = {
    open: () => useWidgetStore.getState().setOpen(true),
    close: () => useWidgetStore.getState().setOpen(false),
    toggle: () => useWidgetStore.getState().setOpen(!useWidgetStore.getState().isOpen),
    botMessage: (text) => useWidgetStore.getState().pushBotMessage(text),
    prefill: (fields) => useWidgetStore.getState().setPrefillData(fields),
    requestRating: () => useWidgetStore.getState().setShowPostChat(true),
    showRecommendations: (products) => useWidgetStore.getState().pushRecommendations(products),
};

/**
 * Attach listeners to the Engagement SDK (window.chat).
 * Retries every 500ms if the SDK has not loaded yet — covers async load order.
 * Safe to call when Engagement is disabled: the timeout is cleared on first
 * successful attach, and the store simply stays at its default values.
 */
let engagementBridgeRetries = 0;
const ENGAGEMENT_BRIDGE_MAX_RETRIES = 20;

function attachEngagementBridge(): void {
    if (typeof window.chat !== 'function') {
        if (engagementBridgeRetries++ < ENGAGEMENT_BRIDGE_MAX_RETRIES) {
            setTimeout(attachEngagementBridge, 500);
        }
        return;
    }

    window.chat('on', 'init:done', (data: any) => {
        const s = useWidgetStore.getState();
        if (data?.score !== undefined) s.setEngagementScore(data.score);
        if (data?.segment) s.setEngagementSegment(data.segment);
        if (data?.context) s.setEngagementContext(data.context);
    });

    window.chat('on', 'score:updated', (e: any) => {
        if (e?.score !== undefined) useWidgetStore.getState().setEngagementScore(e.score);
    });

    window.chat('on', 'segment:changed', (e: any) => {
        if (e?.segment) useWidgetStore.getState().setEngagementSegment(e.segment);
    });

    window.chat('on', 'recommendations:updated', (e: any) => {
        if (Array.isArray(e?.products)) {
            useWidgetStore.getState().setEngagementRecommendations(e.products);
        }
    });

    window.chat('on', 'identify:done', () => {
        useWidgetStore.getState().setEngagementIdentified(true);
    });
}

if (document.readyState === 'complete') {
    attachEngagementBridge();
} else {
    window.addEventListener('load', attachEngagementBridge);
}

// Find existing root or create one for embed mode (launcher)
let rootElement = document.getElementById('widget-root');

if (!rootElement) {
    rootElement = document.createElement('div');
    rootElement.id = 'widget-root';
    rootElement.dataset.launcher = 'true';
    document.body.appendChild(rootElement);
}

const isPreview = rootElement.dataset.preview === 'true';
const isInline = rootElement.dataset.inline === 'true';
const isLauncher = rootElement.dataset.launcher === 'true';
const conversationId = rootElement.dataset.conversationId || undefined;

const root = ReactDOM.createRoot(rootElement);

// Start visitor heartbeat immediately for external embed mode so page views
// and session data are tracked regardless of whether the chat panel is open.
if (isLauncher && !isPreview) {
    startHeartbeat();
}

// NOTE: StrictMode is intentionally disabled. It double-mounts effects on
// purpose to surface bugs, but in production builds it caused duplicate Echo
// channel subscriptions, so each broadcast was rendered twice.
if (isLauncher && !isPreview && !isInline) {
    root.render(<WidgetContainer isPreview={false} />);
} else {
    root.render(
        <WidgetApp
            isPreview={isPreview}
            isInline={isInline}
            conversationId={conversationId}
        />
    );
}
