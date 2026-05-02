/**
 * Widget Embed Script - Loader that gets included in external websites
 * This script loads the main widget bundle and provides the initialization API
 */

(function (window, document) {
    'use strict';

    // Prevent double initialization
    if (window.chatWidget) {
        console.warn('Chat widget already loaded');
        return;
    }

    // Queue for storing commands before widget loads
    const commandQueue: any[] = [];

    // Stub function that queues commands
    const chatWidget: any = function () {
        commandQueue.push(arguments);
    };

    chatWidget.q = commandQueue;

    // Expose to window
    window.chatWidget = chatWidget;
    window.ChatWidget = chatWidget;

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

        // Load Pusher.js for WebSocket support
        if (!window.Pusher) {
            const pusherScript = document.createElement('script');
            pusherScript.src = 'https://js.pusher.com/8.2.0/pusher.min.js';
            pusherScript.async = true;
            document.head.appendChild(pusherScript);
        }

        // Load Laravel Echo
        if (!window.Echo) {
            const echoScript = document.createElement('script');
            echoScript.src = 'https://cdn.jsdelivr.net/npm/laravel-echo@1.15.3/dist/echo.iife.js';
            echoScript.async = true;
            document.head.appendChild(echoScript);
        }

        // Load main widget bundle
        const widgetScript = document.createElement('script');
        widgetScript.src = `${baseUrl}/build-chat/widget/main.js`;
        widgetScript.async = true;
        widgetScript.type = 'module';

        widgetScript.onerror = () => {
            console.error('Failed to load Chat widget');
        };

        document.head.appendChild(widgetScript);

        // Load widget CSS
        const widgetStyles = document.createElement('link');
        widgetStyles.rel = 'stylesheet';
        widgetStyles.href = `${baseUrl}/build-chat/widget/main.css`;
        document.head.appendChild(widgetStyles);
    };

    // Load when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadWidget);
    } else {
        loadWidget();
    }
})(window, document);
