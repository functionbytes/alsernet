import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { useWidgetStore } from '../widget-store';
import { apiUrl } from '../api';

interface TicketStatus {
    name: string;
    color: string;
    is_open: boolean;
}

interface TicketCategory {
    name: string;
    icon: string;
}

interface Ticket {
    id: number;
    ticket_number: string;
    subject: string;
    priority: string;
    created_at: string;
    closed_at: string | null;
    status: TicketStatus | null;
    category: TicketCategory | null;
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString('es', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function StatusBadge({ status }: { status: TicketStatus }) {
    const bg = status.color + '22';
    return (
        <span style={{
            backgroundColor: bg,
            color: status.color,
            fontSize: 11,
            fontWeight: 600,
            padding: '2px 8px',
            borderRadius: 99,
            whiteSpace: 'nowrap',
        }}>
            {status.name}
        </span>
    );
}

export function TicketListScreen() {
    const settings = useWidgetStore(state => state.settings);

    const [email, setEmail] = useState(
        localStorage.getItem('livechat_customer_email') || ''
    );
    const [tickets, setTickets] = useState<Ticket[]>([]);
    const [loading, setLoading] = useState(false);
    const [searched, setSearched] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const websiteToken =
        (window as any).HELPDESK_WIDGET_CONFIG?.websiteToken
        ?? (window as any).helpdeskSettings?.websiteToken
        ?? new URLSearchParams(window.location.search).get('website_token');

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        const trimmed = email.trim();
        if (!trimmed) return;

        setError(null);
        setLoading(true);

        try {
            const url = new URL(apiUrl('/hd/api/tickets'));
            url.searchParams.set('email', trimmed);
            if (websiteToken) {
                url.searchParams.set('website_token', websiteToken);
            }

            const response = await fetch(url.toString(), {
                headers: { Accept: 'application/json' },
            });

            const data = await response.json();

            if (!response.ok || !data?.success) {
                setError(data?.message || 'No se pudieron cargar los tickets.');
                setTickets([]);
            } else {
                setTickets(data.data || []);
            }
        } catch {
            setError('Error de conexión. Inténtalo de nuevo.');
            setTickets([]);
        } finally {
            setLoading(false);
            setSearched(true);
        }
    };

    return (
        <div className="wgt-stack-y wgt-h-full wgt-bg-light wgt-fade-in">
            <div className="wgt-screen-header wgt-gap-3">
                <Link to="/" className="wgt-icon-btn">
                    <svg className="wgt-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <span className="wgt-flex-1 wgt-screen-header-title">Mis tickets</span>
            </div>

            <div className="wgt-flex-1 wgt-overflow-y wgt-px-4 wgt-py-4">
                <form onSubmit={handleSubmit} className="wgt-stack-y wgt-gap-3" style={{ marginBottom: 20 }}>
                    <div className="wgt-form-group">
                        <label className="wgt-label">Correo electrónico</label>
                        <input
                            type="email"
                            className="wgt-input"
                            value={email}
                            onChange={e => setEmail(e.target.value)}
                            placeholder="tu@email.com"
                            required
                        />
                    </div>
                    <button
                        type="submit"
                        className="wgt-btn-primary"
                        disabled={loading || !email.trim()}
                        style={{ backgroundColor: settings.primary_color }}
                    >
                        {loading ? 'Buscando...' : 'Ver mis tickets'}
                    </button>
                </form>

                {error && (
                    <div role="alert" style={{
                        background: '#fde7e7',
                        color: '#a40000',
                        padding: '8px 12px',
                        borderRadius: 6,
                        marginBottom: 12,
                        fontSize: 13,
                    }}>
                        {error}
                    </div>
                )}

                {searched && !loading && !error && tickets.length === 0 && (
                    <p className="wgt-text-muted wgt-text-sm" style={{ textAlign: 'center', marginTop: 24 }}>
                        No encontramos tickets con ese correo.
                    </p>
                )}

                {tickets.length > 0 && (
                    <div className="wgt-stack-y wgt-gap-3">
                        {tickets.map(ticket => (
                            <div key={ticket.id} className="wgt-bedesk-card wgt-bedesk-card-padded">
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: 8 }}>
                                    <div style={{ flex: 1, minWidth: 0 }}>
                                        <div style={{ fontWeight: 600, fontSize: 13, marginBottom: 2 }}>
                                            {ticket.subject}
                                        </div>
                                        <div className="wgt-text-muted wgt-text-sm" style={{ marginBottom: 6 }}>
                                            {ticket.ticket_number}
                                            {ticket.category && (
                                                <span> · {ticket.category.name}</span>
                                            )}
                                        </div>
                                        <div className="wgt-text-muted" style={{ fontSize: 11 }}>
                                            {formatDate(ticket.created_at)}
                                        </div>
                                    </div>
                                    {ticket.status && (
                                        <StatusBadge status={ticket.status} />
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
