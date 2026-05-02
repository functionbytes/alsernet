import React from 'react';
import { useWidgetStore } from '../widget-store';

export function PreChatFormScreen() {
    const settings = useWidgetStore(state => state.settings);

    return (
        <div className="pre-chat-screen">
            {/* Header */}
            <div className="widget-header" style={{ backgroundColor: settings.primary_color }}>
                <button className="back-button">
                    <i className="fa fa-arrow-left"></i>
                </button>
                <h5 className="header-title">Start a conversation</h5>
                <button className="close-button">
                    <i className="fa fa-times"></i>
                </button>
            </div>

            {/* Form */}
            <div className="form-content">
                <div className="form-group">
                    <label>Name</label>
                    <input type="text" className="form-control" placeholder="Your name" />
                </div>
                <div className="form-group">
                    <label>Email</label>
                    <input type="email" className="form-control" placeholder="your@email.com" />
                </div>
                <div className="form-group">
                    <label>Message</label>
                    <textarea className="form-control" rows={4} placeholder="How can we help?"></textarea>
                </div>
            </div>

            {/* Footer */}
            <div className="form-footer">
                <button className="submit-button" style={{ backgroundColor: settings.primary_color }}>
                    Send Message
                </button>
            </div>
        </div>
    );
}
