import React from 'react';
import { Link } from 'react-router-dom';
import { CardLayout } from './CardLayout';
import { useTranslation } from '../i18n/useLanguage';

export function HelpCard() {
    const t = useTranslation();

    return (
        <CardLayout>
            <Link to="/help" className="wgt-card-link">
                <div className="wgt-flex-1">
                    <div className="wgt-card-body-title">{t('home.search_help')}</div>
                    <div className="wgt-card-body-desc">{t('home.search_help_desc')}</div>
                </div>
                <div className="wgt-card-chevron">
                    <svg className="wgt-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </Link>
        </CardLayout>
    );
}
