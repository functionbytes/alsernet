import React, { useState, useEffect } from 'react';
import { WidgetApp } from './WidgetApp';
import { ChatBubbleLauncher } from './components/ChatBubbleLauncher';
import { useWidgetStore } from './widget-store';

interface WidgetContainerProps {
    isPreview?: boolean;
}

export function WidgetContainer({ isPreview = false }: WidgetContainerProps) {
    const [isOpen, setIsOpen] = useState(false);
    const settings = useWidgetStore(state => state.settings);

    // Listen for messages from parent window to open/close widget
    useEffect(() => {
        const handleMessage = (event: MessageEvent) => {
            if (event.data.type === 'OPEN_WIDGET') {
                setIsOpen(true);
            } else if (event.data.type === 'CLOSE_WIDGET') {
                setIsOpen(false);
            }
        };

        window.addEventListener('message', handleMessage);
        return () => window.removeEventListener('message', handleMessage);
    }, []);

    const handleToggle = () => {
        setIsOpen(!isOpen);
    };

    return (
        <div className="widget-root">
            {/* Widget content - shown when open */}
            <div
                className="widget-content transition-all duration-300"
                style={{
                    position: 'fixed',
                    bottom: '80px',
                    right: '16px',
                    width: isOpen ? '400px' : '0',
                    height: isOpen ? 'min(650px, calc(100vh - 100px))' : '0',
                    maxHeight: 'calc(100vh - 52px)',
                    maxWidth: '100%',
                    opacity: isOpen ? 1 : 0,
                    visibility: isOpen ? 'visible' : 'hidden',
                    zIndex: 9998,
                    overflow: 'hidden',
                    borderRadius: '12px',
                }}
            >
                {isOpen && (
                    <WidgetApp isPreview={isPreview} isInline={false} />
                )}
            </div>

            {/* Launcher button - shown when widget is not in preview mode */}
            {!isPreview && (
                <ChatBubbleLauncher onToggle={handleToggle} isOpen={isOpen} />
            )}
        </div>
    );
}
