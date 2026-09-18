import React from 'react';
import { Link } from 'react-router-dom';
import type { ConnectionStatus } from '../hooks/useConversationMessages';

const CONNECTION_LABELS: Record<ConnectionStatus, string> = {
    online: 'En línea',
    connecting: 'Conectando…',
    reconnecting: 'Reconectando…',
    offline: 'Sin conexión',
};

interface ConversationHeaderProps {
    title?: string;
    primaryColor?: string;
    connectionStatus?: ConnectionStatus;
    showMenu: boolean;
    onToggleMenu: () => void;
    onCloseConversation: () => void;
    isClosing: boolean;
    soundEnabled: boolean;
    onToggleSound: () => void;
    liveViewEnabled?: boolean;
    liveViewConsent?: boolean;
    onToggleLiveViewConsent?: () => void;
    screenShareEnabled?: boolean;
    screenShareActive?: boolean;
    onToggleScreenShare?: () => void;
}

export function ConversationHeader({
    title,
    primaryColor,
    connectionStatus = 'online',
    showMenu,
    onToggleMenu,
    onCloseConversation,
    isClosing,
    soundEnabled,
    onToggleSound,
    liveViewEnabled,
    liveViewConsent,
    onToggleLiveViewConsent,
    screenShareEnabled,
    screenShareActive,
    onToggleScreenShare,
}: ConversationHeaderProps) {
    return (
        <div className="wgt-screen-header">
            <Link to="/" className="wgt-icon-btn">
                <svg className="wgt-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                </svg>
            </Link>

            <div className="wgt-flex-1 wgt-row-center wgt-gap-3">
                <div
                    className="wgt-avatar wgt-fw-semibold wgt-text-sm"
                    style={{ backgroundColor: primaryColor }}
                >
                    {title?.charAt(0) || 'A'}
                </div>
                <div className="wgt-text-center">
                    <div className="wgt-fw-semibold wgt-text-body wgt-text-sm">
                        {title || 'Support Team'}
                    </div>
                    <div className="wgt-row-center wgt-gap-1 wgt-text-xs wgt-text-muted">
                        <span className={`wgt-online-dot is-${connectionStatus}`} />
                        <span>{CONNECTION_LABELS[connectionStatus]}</span>
                    </div>
                </div>
            </div>

            <div className="wgt-conv-menu-wrap">
                <button
                    type="button"
                    onClick={onToggleMenu}
                    className="wgt-conv-menu-trigger"
                    aria-label="Menu"
                >
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4z" />
                    </svg>
                </button>

                {showMenu && (
                    <div className="wgt-conv-menu">
                        <button
                            type="button"
                            onClick={onCloseConversation}
                            disabled={isClosing}
                            className="wgt-conv-menu-item"
                        >
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z" />
                            </svg>
                            <span>{isClosing ? 'Closing…' : 'Close conversation'}</span>
                        </button>

                        <button type="button" onClick={onToggleSound} className="wgt-conv-menu-item">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                {soundEnabled ? (
                                    <path d="M3 10v4h4l5 5V5L7 10H3zm13.5 2A4.5 4.5 0 0 0 14 7.97v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z" />
                                ) : (
                                    <path d="M16.5 12A4.5 4.5 0 0 0 14 7.97v2.21l2.45 2.45c.03-.21.05-.42.05-.63zM19 12c0 .94-.2 1.82-.54 2.64l1.51 1.51A8.796 8.796 0 0 0 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3 3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06a8.99 8.99 0 0 0 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4 9.91 6.09 12 8.18V4z" />
                                )}
                            </svg>
                            <span className="wgt-flex-1 wgt-text-left">Sounds</span>
                            <span
                                className={`wgt-toggle${soundEnabled ? ' is-on' : ''}`}
                                aria-hidden="true"
                                style={soundEnabled ? { backgroundColor: primaryColor } : undefined}
                            >
                                <span className="wgt-toggle-knob" />
                            </span>
                        </button>

                        {liveViewEnabled && (
                            <button
                                type="button"
                                onClick={onToggleLiveViewConsent}
                                className="wgt-conv-menu-item"
                                title="El agente verá la actividad de tu navegador (con campos sensibles enmascarados)"
                            >
                                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5C21.27 7.61 17 4.5 12 4.5zM12 17a5 5 0 1 1 0-10 5 5 0 0 1 0 10zm0-8a3 3 0 1 0 0 6 3 3 0 0 0 0-6z" />
                                </svg>
                                <span className="wgt-flex-1 wgt-text-left">Compartir actividad</span>
                                <span
                                    className={`wgt-toggle${liveViewConsent ? ' is-on' : ''}`}
                                    aria-hidden="true"
                                    style={liveViewConsent ? { backgroundColor: primaryColor } : undefined}
                                >
                                    <span className="wgt-toggle-knob" />
                                </span>
                            </button>
                        )}

                        {screenShareEnabled && (
                            <button
                                type="button"
                                onClick={onToggleScreenShare}
                                className="wgt-conv-menu-item"
                                title={screenShareActive ? 'Detener pantalla compartida' : 'Compartir pantalla con el agente'}
                            >
                                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M20 18c1.1 0 1.99-.9 1.99-2L22 6c0-1.11-.9-2-2-2H4c-1.11 0-2 .89-2 2v10c0 1.1.89 2 2 2H0v2h24v-2h-4zM4 6h16v10H4V6z" />
                                    {screenShareActive && <circle cx="20" cy="7" r="3" fill="#dc3545" />}
                                </svg>
                                <span className="wgt-flex-1 wgt-text-left">
                                    {screenShareActive ? 'Detener pantalla' : 'Compartir pantalla'}
                                </span>
                            </button>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
