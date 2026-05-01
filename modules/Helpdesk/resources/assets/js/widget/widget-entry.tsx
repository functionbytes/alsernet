import React from 'react';
import ReactDOM from 'react-dom/client';
import { WidgetApp } from './WidgetApp';
import { WidgetContainer } from './WidgetContainer';
import './widget.css';

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
