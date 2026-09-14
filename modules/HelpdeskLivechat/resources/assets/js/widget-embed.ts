/**
 * Widget Embed Script - Loader that gets included in external websites
 * This script loads the main widget bundle and provides the initialization API
 */

(function (window, document) {
    'use strict';

    // Prevent double bundle load (set after embed runs once on this page)
    if ((window as any).__helpdeskWidgetEmbedLoaded) {
        return;
    }
    (window as any).__helpdeskWidgetEmbedLoaded = true;

    // Reuse existing stub (created by inline IIFE on host page) or create one
    if (!window.chatWidget) {
        const commandQueue: any[] = [];
        const chatWidget: any = function () {
            commandQueue.push(arguments);
        };
        chatWidget.q = commandQueue;
        window.chatWidget = chatWidget;
        window.ChatWidget = chatWidget;
    }

    // Capture the script tag that loaded this embed BEFORE any async work,
    // so we still have a reference even if more scripts are added later.
    const embedScriptSrc =
        ((document as any).currentScript && (document as any).currentScript.src) ||
        // Last <script src=...> in the DOM matching widget.js (excludes inline scripts).
        (() => {
            const scripts = Array.from(document.getElementsByTagName('script'))
                .filter(s => s.src && s.src.includes('/build-helpdesklivechat/widget.js'));
            return scripts.length ? scripts[scripts.length - 1].src : '';
        })();

    // Load the main widget bundle
    const loadWidget = async () => {
        // Extract base URL from the embed script we captured at load time.
        let baseUrl = '';
        if (embedScriptSrc) {
            const url = new URL(embedScriptSrc);
            baseUrl = `${url.protocol}//${url.host}`;
        } else {
            // Last-resort fallback: current page (works when embed served from same origin).
            baseUrl = `${window.location.protocol}//${window.location.host}`;
        }

        // Fetch widget configuration BEFORE loading the bundle so that echo.ts
        // (which runs at import time) sees window.HELPDESK_WIDGET_CONFIG with the
        // correct reverbHost/Port/scheme/baseUrl values for this tenant.
        const websiteToken = (window as any).helpdeskSettings?.websiteToken;
        if (websiteToken) {
            try {
                const res = await fetch(
                    `${baseUrl}/widget/helpdesk/config/${encodeURIComponent(websiteToken)}`,
                    { credentials: 'omit' }
                );
                if (res.ok) {
                    const cfg = await res.json();
                    (window as any).HELPDESK_WIDGET_CONFIG = {
                        ...((window as any).HELPDESK_WIDGET_CONFIG || {}),
                        ...cfg,
                        baseUrl,
                        websiteToken,
                    };
                }
            } catch (e) {
                console.warn('Widget config fetch failed, using defaults', e);
            }
        }
        // Always ensure baseUrl is set (used by api.ts).
        (window as any).HELPDESK_WIDGET_CONFIG = {
            ...((window as any).HELPDESK_WIDGET_CONFIG || {}),
            baseUrl,
            websiteToken,
        };

        // Load main widget bundle through Laravel route (which adds CORS headers).
        // The static path /build-helpdesklivechat/widget/main.js is served directly
        // by nginx without CORS, blocking <script type="module"> from external origins.
        // __WIDGET_BUILD_VERSION__ is replaced at build time by Vite define — it changes
        // with every rebuild so browsers never serve a stale bundle from disk cache.
        const widgetScript = document.createElement('script');
        widgetScript.src = `${baseUrl}/hd/assets/main.js?v=${__WIDGET_BUILD_VERSION__}`;
        widgetScript.async = true;
        widgetScript.type = 'module';
        widgetScript.crossOrigin = 'anonymous';

        widgetScript.onerror = () => {
            console.error('Failed to load Chat widget');
        };

        document.head.appendChild(widgetScript);

        // Load widget CSS (also via Laravel route for CORS)
        const widgetStyles = document.createElement('link');
        widgetStyles.rel = 'stylesheet';
        widgetStyles.href = `${baseUrl}/hd/assets/main.css?v=${__WIDGET_BUILD_VERSION__}`;
        document.head.appendChild(widgetStyles);
    };

    // Load when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadWidget);
    } else {
        loadWidget();
    }
})(window, document);
