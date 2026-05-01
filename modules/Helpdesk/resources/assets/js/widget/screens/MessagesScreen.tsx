import React from 'react';
import { Link } from 'react-router-dom';
import { useWidgetStore } from '../widget-store';

export function MessagesScreen() {
    const settings = useWidgetStore(state => state.settings);

    return (
        <div className="messages-screen">
            <div className="widget-header" style={{ backgroundColor: settings.primary_color }}>
                <div className="header-title">Messages</div>
                <button className="new-button">
                    <i className="fa fa-plus-circle"></i>
                </button>
            </div>

            <div className="conversations-list">
                <div className="conversation-item active">
                    {settings.show_avatars && <div className="avatar"></div>}
                    <div className="conversation-info">
                        <div className="conversation-header">
                            <strong>Support Team</strong>
                            <small>2m ago</small>
                        </div>
                        <p className="last-message">How can we help you today?</p>
                    </div>
                </div>

                <div className="conversation-item">
                    {settings.show_avatars && <div className="avatar"></div>}
                    <div className="conversation-info">
                        <div className="conversation-header">
                            <strong>Technical Support</strong>
                            <small>1h ago</small>
                        </div>
                        <p className="last-message">Thank you for contacting us</p>
                    </div>
                </div>
            </div>

            <div className="bottom-nav">
                <Link to="/" className="nav-button">
                    <i className="fa fa-home"></i>
                    <small>Home</small>
                </Link>
                <button className="nav-button active" style={{ color: settings.primary_color }}>
                    <i className="fa fa-comments"></i>
                    <small>Conversations</small>
                </button>
                <button className="nav-button">
                    <i className="fa fa-question-circle"></i>
                    <small>Help</small>
                </button>
            </div>
        </div>
    );
}
