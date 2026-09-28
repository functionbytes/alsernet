import React from 'react';
import { useWidgetStore } from '../widget-store';
import { useTranslation } from '../i18n/useLanguage';
import { Icon } from '../components/Icon';

interface ChatPageScreenProps {
    conversationId?: string;
}

export function ChatPageScreen({ conversationId }: ChatPageScreenProps = {}) {
    const settings = useWidgetStore(state => state.settings);
    const t = useTranslation();

    return (
        <div className="chat-page-screen">
            <div className="widget-header" style={{ backgroundColor: settings.primary_color }}>
                <div className="header-info">
                    <div className="header-title">{settings.header_title}</div>
                    <small className="header-status">{t('home.we_will_reply')}</small>
                </div>
                <button className="minimize-button">
                    <Icon name="minus" />
                </button>
                <button className="close-button">
                    <Icon name="times" />
                </button>
            </div>

            <div className="messages-area">
                <div className="message bot-message">
                    {settings.show_avatars && (
                        <div className="bot-avatar" style={{ backgroundColor: settings.primary_color }}>
                            <Icon name="robot" />
                        </div>
                    )}
                    <div className="message-content">
                        <div className="message-bubble">
                            <p>{settings.welcome_message}</p>
                        </div>
                        <small className="message-time">{t('chat.bot_now')}</small>
                    </div>
                </div>

                <div className="quick-replies">
                    <button className="quick-reply-btn">{t('chat.quick_track_order')}</button>
                    <button className="quick-reply-btn">{t('chat.quick_contact_support')}</button>
                    <button className="quick-reply-btn">{t('chat.quick_faqs')}</button>
                </div>
            </div>

            <div className="input-area">
                <button className="attachment-button">
                    <Icon name="paperclip" />
                </button>
                <input
                    type="text"
                    className="message-input"
                    placeholder={settings.input_placeholder}
                />
                <button className="send-button" style={{ backgroundColor: settings.primary_color }}>
                    <Icon name="paper-plane" />
                </button>
            </div>
            <div className="powered-by">
                <small>{t('powered_by')}</small>
            </div>
        </div>
    );
}
