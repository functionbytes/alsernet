import React from 'react';
import { Link } from 'react-router-dom';
import { CardLayout } from './CardLayout';
import { useTranslation } from '../i18n/useLanguage';

export function NewChatCard() {
    const t = useTranslation();

    return (
        <CardLayout>
            <Link
                to="/conversation"
                className="flex items-center justify-between gap-3 px-5 py-4 transition-colors hover:bg-gray-50"
            >
                <div className="flex-1">
                    <div className="font-semibold text-gray-900 text-base">
                        {t('home.send_message')}
                    </div>
                    <div className="text-sm text-gray-600">
                        {t('home.send_message_desc')}
                    </div>
                </div>
                <div className="text-primary">
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </div>
            </Link>
        </CardLayout>
    );
}
