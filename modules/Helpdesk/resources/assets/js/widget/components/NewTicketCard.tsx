import React from 'react';
import { Link } from 'react-router-dom';
import { CardLayout } from './CardLayout';
import { useTranslation } from '../i18n/useLanguage';

export function NewTicketCard() {
    const t = useTranslation();

    return (
        <CardLayout>
            <Link to="/tickets/new" className="wgt-card-link">
                <div className="wgt-flex-1">
                    <div className="wgt-card-body-title">{t('home.create_ticket')}</div>
                    <div className="wgt-card-body-desc">{t('home.create_ticket_desc')}</div>
                </div>
                <div className="wgt-card-chevron">
                    <svg className="wgt-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                    </svg>
                </div>
            </Link>
        </CardLayout>
    );
}
