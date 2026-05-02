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

    // Load the main widget bundle
    const loadWidget = () => {
        // Get script tag that loaded this embed script
        const scripts = document.getElementsByTagName('script');
        const currentScript = scripts[scripts.length - 1];
        const scriptSrc = currentScript ? currentScript.src : '';

        // Extract base URL
        let baseUrl = '';
        if (scriptSrc) {
            const url = new URL(scriptSrc);
            baseUrl = `${url.protocol}//${url.host}`;
        } else {
            // Fallback: use current domain
            baseUrl = `${window.location.protocol}//${window.location.host}`;
        }

        // Load Font Awesome (if not already loaded)
        if (!document.querySelector('link[href*="font-awesome"]')) {
            const fontAwesome = document.createElement('link');
            fontAwesome.rel = 'stylesheet';
            fontAwesome.href =
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
            document.head.appendChild(fontAwesome);
        }

        // Load main widget bundle (Pusher + Echo are bundled inside)
        const widgetScript = document.createElement('script');
        widgetScript.src = `${baseUrl}/build-helpdesklivechat/widget/main.js`;
        widgetScript.async = true;
        widgetScript.type = 'module';

        widgetScript.onerror = () => {
            console.error('Failed to load Chat widget');
        };

        document.head.appendChild(widgetScript);

        // Load widget CSS (emitted alongside main.js by Vite)
        const widgetStyles = document.createElement('link');
        widgetStyles.rel = 'stylesheet';
        widgetStyles.href = `${baseUrl}/build-helpdesklivechat/widget/main.css`;
        document.head.appendChild(widgetStyles);
    };

    // Load when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadWidget);
    } else {
        loadWidget();
    }
})(window, document);
