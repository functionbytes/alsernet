import React, { useState } from 'react';
import { useWidgetStore } from '../widget-store';

interface ChatBubbleLauncherProps {
    onToggle: () => void;
    isOpen: boolean;
}

export function ChatBubbleLauncher({ onToggle, isOpen }: ChatBubbleLauncherProps) {
    const settings = useWidgetStore(state => state.settings);
    const [isHovered, setIsHovered] = useState(false);

    // Get position from backups
    const position = settings.position || 'bottom-right';
    const sideSpacing = settings.side_spacing || 16;
    const bottomSpacing = settings.bottom_spacing || 16;

    // Calculate position styles
    const positionStyles: React.CSSProperties = {
        position: 'fixed',
        zIndex: 9999,
        bottom: `${bottomSpacing}px`,
    };

    if (position === 'bottom-right') {
        positionStyles.right = `${sideSpacing}px`;
    } else if (position === 'bottom-left') {
        positionStyles.left = `${sideSpacing}px`;
    }

    return (
        <div style={positionStyles} className="flex flex-col items-end gap-3">
            {/* Unread badge - placeholder for future implementation */}
            {/* <div className="absolute -right-1 -top-1 h-4 w-4 rounded-full bg-red-500" /> */}

            {/* Main launcher button */}
            <button
                onClick={onToggle}
                onMouseEnter={() => setIsHovered(true)}
                onMouseLeave={() => setIsHovered(false)}
                className="relative flex items-center justify-center rounded-full shadow-lg transition-all duration-200 hover:shadow-xl"
                style={{
                    width: '60px',
                    height: '60px',
                    backgroundColor: settings.primary_color || '#90bb13',
                    transform: isHovered ? 'scale(1.05)' : 'scale(1)',
                }}
                aria-label={isOpen ? 'Close chat' : 'Open chat'}
            >
                {/* Animated icon transition */}
                <div
                    className="transition-all duration-200"
                    style={{
                        transform: isOpen ? 'rotate(90deg)' : 'rotate(0deg)',
                        opacity: 1,
                    }}
                >
                    {isOpen ? (
                        // Close icon (X)
                        <svg
                            className="text-white"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"
                                fill="currentColor"
                            />
                        </svg>
                    ) : (
                        // Chat bubble icon
                        <svg
                            className="text-white"
                            width="28"
                            height="28"
                            viewBox="0 0 24 24"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"
                                fill="currentColor"
                            />
                        </svg>
                    )}
                </div>
            </button>
        </div>
    );
}
