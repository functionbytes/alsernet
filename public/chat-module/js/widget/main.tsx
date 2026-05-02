/**
 * Widget Entry Point - Initialize and mount React widget
 */

import React from 'react';
import { createRoot } from 'react-dom/client';
import { Widget } from './Widget';
import type { WidgetConfig } from './types';

// Global widget interface
interface ChatWidgetGlobal {
    (command: 'init', config: WidgetConfig): void;
    (command: 'toggle'): void;
    (command: 'open'): void;
    (command: 'close'): void;
    q?: any[];
}

declare global {
    interface Window {
        chatWidget: ChatWidgetGlobal;
        ChatWidget: ChatWidgetGlobal;
    }
}

class ChatWidget {
    private root: any = null;
    private container: HTMLElement | null = null;
    private config: WidgetConfig | null = null;

    /**
     * Initialize the widget
     */
    init(config: WidgetConfig): void {
        if (this.root) {
            console.warn('Widget already initialized');
            return;
        }

        this.config = config;

        // Create container
        this.container = document.createElement('div');
        this.container.id = 'helpdesk-chat-widget-container';
        document.body.appendChild(this.container);

        // Mount React app
        this.root = createRoot(this.container);
        this.root.render(<Widget config={config} />);

        console.log('Chat Widget initialized');
    }

    /**
     * Toggle widget open/close
     */
    toggle(): void {
        // This will be handled via widget internal state
        console.log('Toggle widget');
    }

    /**
     * Open widget
     */
    open(): void {
        console.log('Open widget');
    }

    /**
     * Close widget
     */
    close(): void {
        console.log('Close widget');
    }

    /**
     * Destroy widget
     */
    destroy(): void {
        if (this.root) {
            this.root.unmount();
            this.root = null;
        }

        if (this.container && this.container.parentNode) {
            this.container.parentNode.removeChild(this.container);
            this.container = null;
        }

        console.log('Widget destroyed');
    }
}

// Initialize global interface
const widgetInstance = new ChatWidget();

const chatWidget: ChatWidgetGlobal = function (command: string, ...args: any[]) {
    switch (command) {
        case 'init':
            widgetInstance.init(args[0]);
            break;
        case 'toggle':
            widgetInstance.toggle();
            break;
        case 'open':
            widgetInstance.open();
            break;
        case 'close':
            widgetInstance.close();
            break;
        default:
            console.warn(`Unknown command: ${command}`);
    }
};

// Process queued commands
if (window.chatWidget && window.chatWidget.q) {
    window.chatWidget.q.forEach((args: any[]) => {
        chatWidget.apply(null, args as [any]);
    });
}

// Expose global interface
window.chatWidget = chatWidget;
window.ChatWidget = chatWidget;

export { ChatWidget };
