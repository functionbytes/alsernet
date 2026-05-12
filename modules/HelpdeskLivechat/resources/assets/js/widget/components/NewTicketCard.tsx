import React from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from '../i18n/useLanguage';

export function NewTicketCard() {
    const t = useTranslation();

    return (
        <div className="wgt-bedesk-card">
            <Link to="/tickets/new" className="wgt-bedesk-send-link">
                <div>
                    <div className="wgt-bedesk-send-title">{t('home.create_ticket')}</div>
                    <div className="wgt-bedesk-send-subtitle">{t('home.create_ticket_desc')}</div>
                </div>
                <svg className="wgt-bedesk-send-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z" />
                </svg>
            </Link>
        </div>
    );
}
