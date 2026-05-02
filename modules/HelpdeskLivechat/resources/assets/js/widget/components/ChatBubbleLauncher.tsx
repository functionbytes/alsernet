import React, { useState } from 'react';
import { useWidgetStore } from '../widget-store';

interface ChatBubbleLauncherProps {
    onToggle: () => void;
    isOpen: boolean;
}

export function ChatBubbleLauncher({ onToggle, isOpen }: ChatBubbleLauncherProps) {
    const settings = useWidgetStore(state => state.settings);
    const [isHovered, setIsHovered] = useState(false);

    const position = settings.position || 'bottom-right';
    const sideSpacing = settings.side_spacing || 16;
    const bottomSpacing = settings.bottom_spacing || 16;

    const positionStyles: React.CSSProperties = {
        position: 'fixed',
        zIndex: 9999,
        bottom: `${bottomSpacing}px`,
    };

    if (position === 'bottom-right') {
        positionStyles.right = `${sideSpacing}px`;
    } else {
        positionStyles.left = `${sideSpacing}px`;
    }

    return (
        <div style={positionStyles} className="wgt-launcher">
            <button
                onClick={onToggle}
                onMouseEnter={() => setIsHovered(true)}
                onMouseLeave={() => setIsHovered(false)}
                className="wgt-launcher-btn"
                style={{
                    backgroundColor: settings.primary_color || '#b10100',
                    transform: isHovered ? 'scale(1.05)' : 'scale(1)',
                }}
                aria-label={isOpen ? 'Close chat' : 'Open chat'}
            >
                <div
                    className="wgt-launcher-icon"
                    style={{ transform: isOpen ? 'rotate(90deg)' : 'rotate(0deg)' }}
                >
                    {isOpen ? (
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z" fill="white" />
                        </svg>
                    ) : (
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                            <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z" fill="white" />
                        </svg>
                    )}
                </div>
            </button>
        </div>
    );
}
