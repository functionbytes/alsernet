import React from 'react';
import ReactDOM from 'react-dom/client';
import { WidgetApp } from './WidgetApp';
import { WidgetContainer } from './WidgetContainer';
import './widget.css';

// Get root element
const rootElement = document.getElementById('widget-root');

if (rootElement) {
    const isPreview = rootElement.dataset.preview === 'true';
    const isInline = rootElement.dataset.inline === 'true';
    const isLauncher = rootElement.dataset.launcher === 'true';
    const conversationId = rootElement.dataset.conversationId || undefined;

    const root = ReactDOM.createRoot(rootElement);

    // Use WidgetContainer for launcher mode (shows chat bubble)
    // Use WidgetApp directly for preview and inline modes
    if (isLauncher && !isPreview && !isInline) {
        root.render(
            <React.StrictMode>
                <WidgetContainer isPreview={false} />
            </React.StrictMode>
        );
    } else {
        root.render(
            <React.StrictMode>
                <WidgetApp
                    isPreview={isPreview}
                    isInline={isInline}
                    conversationId={conversationId}
                />
            </React.StrictMode>
        );
    }
} else {
    console.error('❌ Root element "widget-root" not found!');
}
