import React from 'react';
import { useWidgetStore } from '../widget-store';

export function PostChatFormScreen() {
    const settings = useWidgetStore(state => state.settings);

    return (
        <div className="post-chat-screen">
            <div className="widget-header" style={{ backgroundColor: settings.primary_color }}>
                <button className="back-button">
                    <i className="fa fa-arrow-left"></i>
                </button>
                <h5 className="header-title">Rate your experience</h5>
                <button className="close-button">
                    <i className="fa fa-times"></i>
                </button>
            </div>

            <div className="form-content">
                <div className="rating-section">
                    <p>How would you rate this conversation?</p>
                    <div className="rating-buttons">
                        <button className="rating-btn">
                            <i className="fa fa-frown"></i>
                        </button>
                        <button className="rating-btn">
                            <i className="fa fa-meh"></i>
                        </button>
                        <button className="rating-btn active" style={{ backgroundColor: settings.primary_color }}>
                            <i className="fa fa-smile"></i>
                        </button>
                    </div>
                </div>
                <div className="form-group">
                    <label>Additional feedback (optional)</label>
                    <textarea className="form-control" rows={4} placeholder="Tell us more..."></textarea>
                </div>
            </div>

            <div className="form-footer">
                <button className="submit-button" style={{ backgroundColor: settings.primary_color }}>
                    Submit Feedback
                </button>
            </div>
        </div>
    );
}
