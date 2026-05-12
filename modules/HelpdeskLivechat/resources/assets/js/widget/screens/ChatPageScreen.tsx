import React from 'react';
import { useWidgetStore } from '../widget-store';
import { Icon } from '../components/Icon';

interface ChatPageScreenProps {
    conversationId?: string;
}

export function ChatPageScreen({ conversationId }: ChatPageScreenProps = {}) {
    const settings = useWidgetStore(state => state.settings);

    return (
        <div className="chat-page-screen">
            <div className="widget-header" style={{ backgroundColor: settings.primary_color }}>
                <div className="header-info">
                    <div className="header-title">{settings.header_title}</div>
                    <small className="header-status">We'll reply as soon as we can</small>
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
                        <small className="message-time">Bot · Just now</small>
                    </div>
                </div>

                <div className="quick-replies">
                    <button className="quick-reply-btn">Track my order</button>
                    <button className="quick-reply-btn">Contact support</button>
                    <button className="quick-reply-btn">FAQs</button>
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
                <small>Powered by AlserNet</small>
            </div>
        </div>
    );
}
