import React from 'react';
import { Link } from 'react-router-dom';
import { useWidgetStore } from '../widget-store';
import { useTranslation } from '../i18n/useLanguage';
import { Icon } from '../components/Icon';

export function MessagesScreen() {
    const settings = useWidgetStore(state => state.settings);
    const t = useTranslation();

    return (
        <div className="messages-screen">
            <div className="widget-header" style={{ backgroundColor: settings.primary_color }}>
                <div className="header-title">{t('messages.title')}</div>
                <button className="new-button" aria-label={t('messages.new_conversation')}>
                    <Icon name="plus-circle" />
                </button>
            </div>

            <div className="conversations-list">
                <div className="conversation-item active">
                    {settings.show_avatars && <div className="avatar"></div>}
                    <div className="conversation-info">
                        <div className="conversation-header">
                            <strong>{t('messages.support_team')}</strong>
                            <small>{t('messages.time_2m')}</small>
                        </div>
                        <p className="last-message">{t('messages.support_team_preview')}</p>
                    </div>
                </div>

                <div className="conversation-item">
                    {settings.show_avatars && <div className="avatar"></div>}
                    <div className="conversation-info">
                        <div className="conversation-header">
                            <strong>{t('messages.technical_support')}</strong>
                            <small>{t('messages.time_1h')}</small>
                        </div>
                        <p className="last-message">{t('messages.technical_support_preview')}</p>
                    </div>
                </div>
            </div>

            <div className="bottom-nav">
                <Link to="/" className="nav-button">
                    <Icon name="home" />
                    <small>{t('nav.home')}</small>
                </Link>
                <button className="nav-button active" style={{ color: settings.primary_color }}>
                    <Icon name="comments" />
                    <small>{t('nav.messages')}</small>
                </button>
                <button className="nav-button">
                    <Icon name="question-circle" />
                    <small>{t('nav.help')}</small>
                </button>
            </div>
        </div>
    );
}
