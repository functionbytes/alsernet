import React from 'react';
import { Link } from 'react-router-dom';
import { CardLayout } from './CardLayout';
import { useTranslation } from '../i18n/useLanguage';

export function NewChatCard() {
    const t = useTranslation();

    return (
        <CardLayout>
            <Link to="/conversation" className="wgt-card-link">
                <div className="wgt-flex-1">
                    <div className="wgt-card-body-title">{t('home.send_message')}</div>
                    <div className="wgt-card-body-desc">{t('home.send_message_desc')}</div>
                </div>
                <div className="wgt-card-chevron">
                    <svg className="wgt-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </div>
            </Link>
        </CardLayout>
    );
}
