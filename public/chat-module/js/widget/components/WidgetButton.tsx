/**
 * WidgetButton - Floating button to open/close widget
 */

import React from 'react';

interface WidgetButtonProps {
    isOpen: boolean;
    unreadCount: number;
    color: string;
    position: 'bottom-right' | 'bottom-left';
    onClick: () => void;
}

export function WidgetButton({
    isOpen,
    unreadCount,
    color,
    position,
    onClick,
}: WidgetButtonProps) {
    const positionClasses =
        position === 'bottom-left'
            ? 'left-6 bottom-6'
            : 'right-6 bottom-6';

    return (
        <button
            onClick={onClick}
            className={`fixed ${positionClasses} z-[9998] w-14 h-14 rounded-full shadow-lg transition-all duration-300 hover:scale-110 focus:outline-none focus:ring-4 focus:ring-opacity-50`}
            style={{
                backgroundColor: color,
                boxShadow: '0 4px 12px rgba(0, 0, 0, 0.15)',
            }}
            aria-label={isOpen ? 'Close chat' : 'Open chat'}
        >
            {!isOpen && unreadCount > 0 && (
                <span className="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
                    {unreadCount > 9 ? '9+' : unreadCount}
                </span>
            )}

            <span className="flex items-center justify-center w-full h-full text-white text-2xl">
                {isOpen ? (
                    <i className="fas fa-times"></i>
                ) : (
                    <i className="fas fa-comments"></i>
                )}
            </span>
        </button>
    );
}
