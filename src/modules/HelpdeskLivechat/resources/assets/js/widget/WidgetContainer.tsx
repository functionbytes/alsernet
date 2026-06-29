import React, { useEffect } from 'react';
import { WidgetApp } from './WidgetApp';
import { ChatBubbleLauncher } from './components/ChatBubbleLauncher';
import { useWidgetStore } from './widget-store';

interface WidgetContainerProps {
    isPreview?: boolean;
}

export function WidgetContainer({ isPreview = false }: WidgetContainerProps) {
    const isOpen = useWidgetStore(state => state.isOpen);
    const setOpen = useWidgetStore(state => state.setOpen);
    const settings = useWidgetStore(state => state.settings);

    useEffect(() => {
        const handleMessage = (event: MessageEvent) => {
            if (event.data.type === 'OPEN_WIDGET') {
                setOpen(true);
            } else if (event.data.type === 'CLOSE_WIDGET') {
                setOpen(false);
            }
        };

        window.addEventListener('message', handleMessage);
        return () => window.removeEventListener('message', handleMessage);
    }, [setOpen]);

    const handleToggle = () => setOpen(!isOpen);

    return (
        <div className="widget-root">
            <div
                className="wgt-transition"
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
                    transition: 'all 0.3s ease',
                }}
            >
                {isOpen && (
                    <WidgetApp isPreview={isPreview} isInline={false} />
                )}
            </div>

            {!isPreview && (
                <ChatBubbleLauncher onToggle={handleToggle} isOpen={isOpen} />
            )}
        </div>
    );
}
