import React from 'react';
import { useTranslation } from '../i18n/useLanguage';

interface ScreenShareRequestModalProps {
    agentName: string;
    primaryColor?: string;
    onAccept: () => void;
    onDecline: () => void;
}

export function ScreenShareRequestModal({
    agentName,
    primaryColor,
    onAccept,
    onDecline,
}: ScreenShareRequestModalProps) {
    const t = useTranslation();
    return (
        <div className="wgt-share-request-overlay" role="dialog" aria-modal="true" aria-labelledby="wgt-share-req-title">
            <div className="wgt-share-request-card">
                <div className="wgt-share-request-icon" style={{ backgroundColor: primaryColor }}>
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M20 18c1.1 0 1.99-.9 1.99-2L22 6c0-1.11-.9-2-2-2H4c-1.11 0-2 .89-2 2v10c0 1.1.89 2 2 2H0v2h24v-2h-4zM4 6h16v10H4V6z" />
                    </svg>
                </div>
                <h3 id="wgt-share-req-title" className="wgt-share-request-title">
                    {t('ui.share_request_title', { name: agentName })}
                </h3>
                <p className="wgt-share-request-desc">
                    {t('ui.share_request_desc')}
                </p>
                <div className="wgt-share-request-actions">
                    <button type="button" className="wgt-share-request-decline" onClick={onDecline}>
                        {t('ui.decline')}
                    </button>
                    <button
                        type="button"
                        className="wgt-share-request-accept"
                        onClick={onAccept}
                        style={{ backgroundColor: primaryColor }}
                    >
                        {t('ui.share_screen')}
                    </button>
                </div>
            </div>
        </div>
    );
}
