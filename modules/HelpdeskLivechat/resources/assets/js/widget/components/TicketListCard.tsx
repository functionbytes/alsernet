import React from 'react';
import { Link } from 'react-router-dom';

export function TicketListCard() {
    return (
        <div className="wgt-bedesk-card">
            <Link to="/tickets" className="wgt-bedesk-send-link">
                <div>
                    <div className="wgt-bedesk-send-title">Mis tickets</div>
                    <div className="wgt-bedesk-send-subtitle">Consulta el estado de tus solicitudes</div>
                </div>
                <svg className="wgt-bedesk-send-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M20 3H4c-1.103 0-2 .897-2 2v14c0 1.103.897 2 2 2h16c1.103 0 2-.897 2-2V5c0-1.103-.897-2-2-2zM4 19V7h16l.001 12H4zM6 9h12v2H6zm0 4h6v2H6z" />
                </svg>
            </Link>
        </div>
    );
}
