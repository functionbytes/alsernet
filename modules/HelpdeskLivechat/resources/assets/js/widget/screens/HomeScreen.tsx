import React, { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useWidgetStore, RecommendationProduct } from '../widget-store';
import { useTranslation } from '../i18n/useLanguage';
import { apiUrl } from '../api';
import { NewTicketCard } from '../components/NewTicketCard';
import { TicketListCard } from '../components/TicketListCard';

interface Article {
    id: number;
    title: string;
    slug?: string;
    category_id?: number;
    section_id?: number;
}

interface HelpcenterResponse {
    popular_articles?: Article[];
    articles?: Article[];
    categories?: Array<{ articles?: Article[] }>;
}

interface ActiveConversation {
    id: number;
    lastMessage: string;
    lastSender: 'agent' | 'visitor';
    lastSenderName: string;
    lastAt: Date;
    unread: boolean;
}

function RecommendationsCard({ products }: { products: RecommendationProduct[] }) {
    if (products.length === 0) return null;
    return (
        <div className="wgt-bedesk-card wgt-bedesk-card-padded">
            <div className="wgt-rec-list">
                {products.map((p) => (
                    <a
                        key={p.id}
                        href={p.url ?? '#'}
                        className="wgt-rec-item"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        {p.image_url && (
                            <img
                                className="wgt-rec-thumb"
                                src={p.image_url}
                                alt={p.name}
                                loading="lazy"
                                width="40"
                                height="40"
                            />
                        )}
                        <div className="wgt-rec-info">
                            <p className="wgt-rec-name">{p.name}</p>
                            {p.price !== undefined && (
                                <p className="wgt-rec-price">
                                    {p.price.toLocaleString(undefined, { style: 'currency', currency: 'USD', minimumFractionDigits: 2 })}
                                </p>
                            )}
                        </div>
                        <svg className="wgt-bedesk-send-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M8.59 16.59 13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z" />
                        </svg>
                    </a>
                ))}
            </div>
        </div>
    );
}

function timeAgo(d: Date): string {
    const sec = Math.max(0, Math.floor((Date.now() - d.getTime()) / 1000));
    if (sec < 60) return 'ahora';
    const min = Math.floor(sec / 60);
    if (min < 60) return `${min}m`;
    const hr = Math.floor(min / 60);
    if (hr < 24) return `${hr}h`;
    const day = Math.floor(hr / 24);
    if (day < 7) return `${day}d`;
    return d.toLocaleDateString();
}

export function HomeScreen() {
    const settings = useWidgetStore(state => state.settings);
    const segment = useWidgetStore(state => state.engagement.segment);
    const engagementRecs = useWidgetStore(state => state.engagement.recommendations);
    const t = useTranslation();
    const navigate = useNavigate();
    const [articles, setArticles] = useState<Article[]>([]);
    const [loading, setLoading] = useState(true);
    const [searchQuery, setSearchQuery] = useState('');
    const [activeConversation, setActiveConversation] = useState<ActiveConversation | null>(null);

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const q = searchQuery.trim();
        navigate(q ? `/help?q=${encodeURIComponent(q)}` : '/help');
    };

    // Load active conversation summary (if visitor already has one) so we can
    // show a "continue conversation" card on top — like Intercom/Crisp.
    useEffect(() => {
        let cancelled = false;
        const conversationId = localStorage.getItem('livechat_conversation_id');
        if (!conversationId) return;
        const customerId = localStorage.getItem('livechat_customer_id');
        const customerEmail = localStorage.getItem('livechat_customer_email') || '';
        const url = new URL(apiUrl(`/hd/api/conversation/${conversationId}/messages`));
        if (customerId) {
            url.searchParams.append('customer_id', customerId);
        } else if (customerEmail) {
            url.searchParams.append('customer_email', customerEmail);
        }

        fetch(url.toString())
            .then(r => r.ok ? r.json() : null)
            .then((data: any) => {
                if (cancelled || !data?.success || !Array.isArray(data?.data?.messages)) return;
                const messages = data.data.messages;
                if (messages.length === 0) return;
                const last = messages[messages.length - 1];
                const isAgent = last.message_type === 'outgoing';
                const lastReadAt = parseInt(localStorage.getItem(`livechat_last_read_${conversationId}`) || '0', 10);
                const lastTs = new Date(last.created_at).getTime();
                setActiveConversation({
                    id: parseInt(conversationId, 10),
                    lastMessage: last.body || last.content || (last.attachments?.length ? '📎 Archivo' : ''),
                    lastSender: isAgent ? 'agent' : 'visitor',
                    lastSenderName: last.sender?.name || (isAgent ? 'Soporte' : 'Tú'),
                    lastAt: new Date(last.created_at),
                    unread: isAgent && lastTs > lastReadAt,
                });
            })
            .catch(() => { /* keep null */ });

        return () => { cancelled = true; };
    }, []);

    useEffect(() => {
        let cancelled = false;
        fetch(apiUrl('/hd/api/helpcenter'))
            .then(r => r.ok ? r.json() : null)
            .then((data: HelpcenterResponse | null) => {
                if (cancelled || !data) return;
                let list: Article[] = [];
                if (Array.isArray(data.popular_articles)) {
                    list = data.popular_articles;
                } else if (Array.isArray(data.articles)) {
                    list = data.articles;
                } else if (Array.isArray(data.categories)) {
                    list = data.categories.flatMap(c => c.articles ?? []);
                }
                setArticles(list.slice(0, 4));
            })
            .catch(() => { /* keep articles empty */ })
            .finally(() => { if (!cancelled) setLoading(false); });
        return () => { cancelled = true; };
    }, []);

    const articleHref = (a: Article): string => {
        const slug = a.slug ? `/${a.slug}` : '';
        const cat = a.category_id ?? 0;
        const sec = a.section_id ?? 0;
        return `/help/article/${a.id}${slug ? `?cat=${cat}&sec=${sec}` : ''}`;
    };

    return (
        <div className="wgt-bedesk-home">
            <div className="wgt-bedesk-header-wrap">
                <div className="wgt-bedesk-header-bg" style={{ backgroundColor: '#eff5f5' }} />
                <div className="wgt-bedesk-header-fade" />
                <div className="wgt-bedesk-header-content">
                    <div className="wgt-bedesk-topbar">
                        {settings.logo_url && (
                            <img className="wgt-bedesk-logo" alt="logo" src={settings.logo_url} />
                        )}
                        {settings.team_avatars && settings.team_avatars.length > 0 && (
                            <div className="wgt-bedesk-avatars">
                                {settings.team_avatars.slice(0, 3).map((src, i) => (
                                    <div key={i} className="wgt-bedesk-avatar" style={{ zIndex: 5 - i }}>
                                        <img alt="agent" loading="lazy" src={src} />
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                    <div className="wgt-bedesk-greeting">
                        <h1>{t('home.greeting')}</h1>
                        <h2>{t('home.greeting_message')}</h2>
                    </div>
                </div>
            </div>

            <div className="wgt-bedesk-cards">
                {segment === 'hot' && (
                    <div className="hd-segment-banner hd-segment-hot" role="status">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" width="16" height="16">
                            <path d="M12 23a7.5 7.5 0 0 1-5.138-12.963C8.204 8.774 11.5 6.5 11 1.5c6 4 9 8 3 14 1 0 2.5 0 3-1.5.5.75 1 2.5 1 3.5a7 7 0 0 1-6 5.5z" />
                        </svg>
                        <span>¿Necesitas ayuda con tu compra?</span>
                        <Link to="/conversation" className="hd-segment-banner-cta">Chatear ahora</Link>
                    </div>
                )}

                {segment === 'warm' && engagementRecs.length > 0 && (
                    <RecommendationsCard products={engagementRecs.slice(0, 3)} />
                )}

                {activeConversation && (
                    <div className="wgt-bedesk-card">
                        <Link to="/conversation" className="wgt-resume-link">
                            <div className="wgt-resume-avatar" aria-hidden="true">
                                {activeConversation.lastSender === 'agent'
                                    ? activeConversation.lastSenderName.trim().charAt(0).toUpperCase() || 'S'
                                    : '👤'}
                            </div>
                            <div className="wgt-resume-body">
                                <div className="wgt-resume-top">
                                    <span className="wgt-resume-name">
                                        {activeConversation.lastSender === 'agent'
                                            ? activeConversation.lastSenderName
                                            : 'Continuar conversación'}
                                    </span>
                                    <span className="wgt-resume-time">{timeAgo(activeConversation.lastAt)}</span>
                                </div>
                                <div className="wgt-resume-msg">
                                    {activeConversation.lastSender === 'visitor' && (
                                        <span className="wgt-resume-prefix">Tú: </span>
                                    )}
                                    {activeConversation.lastMessage || '(sin texto)'}
                                </div>
                            </div>
                            {activeConversation.unread && <span className="wgt-resume-dot" aria-label="No leído" />}
                            <svg className="wgt-bedesk-send-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M8.59 16.59 13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z" />
                            </svg>
                        </Link>
                    </div>
                )}

                {/* Fix 5: offline notice — shown when outside business hours */}
                {settings.is_open === false && settings.offline_message_enabled && settings.offline_message && (
                    <div className="wgt-bedesk-card wgt-bedesk-card-padded wgt-offline-notice" role="status">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" className="wgt-offline-notice-icon">
                            <path d="M12 1a11 11 0 1 0 11 11A11.013 11.013 0 0 0 12 1zm0 20a9 9 0 1 1 9-9 9.011 9.011 0 0 1-9 9zm0-13a1 1 0 0 0-1 1v5a1 1 0 0 0 2 0V9a1 1 0 0 0-1-1zm0 8a1 1 0 1 0 1 1 1 1 0 0 0-1-1z" />
                        </svg>
                        <p className="wgt-offline-notice-text">{settings.offline_message}</p>
                    </div>
                )}

                {settings.enable_send_message && !activeConversation && settings.is_open !== false && (
                    <div className="wgt-bedesk-card">
                        <Link
                            to={settings.pre_chat_form_enabled
                                && !localStorage.getItem('livechat_customer_email')
                                ? '/pre-chat'
                                : '/conversation'}
                            className="wgt-bedesk-send-link"
                        >
                            <div>
                                <div className="wgt-bedesk-send-title">{t('home.send_us_a_message')}</div>
                                <div className="wgt-bedesk-send-subtitle">{t('home.we_will_reply')}</div>
                            </div>
                            <svg className="wgt-bedesk-send-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="m4.01 6.03 7.51 3.22-7.52-1 .01-2.22m7.5 8.72L4 17.97v-2.22l7.51-1M2.01 3 2 10l15 2-15 2 .01 7L23 12 2.01 3z" />
                            </svg>
                        </Link>
                    </div>
                )}

                {/* Fix 5: offline — show "leave your email" CTA when business is closed */}
                {settings.is_open === false && settings.offline_message_enabled && !activeConversation && (
                    <div className="wgt-bedesk-card">
                        <Link
                            to={settings.pre_chat_form_enabled
                                && !localStorage.getItem('livechat_customer_email')
                                ? '/pre-chat'
                                : '/conversation'}
                            className="wgt-bedesk-send-link"
                        >
                            <div>
                                <div className="wgt-bedesk-send-title">Déjanos tu mensaje</div>
                                <div className="wgt-bedesk-send-subtitle">Te responderemos cuando estemos disponibles</div>
                            </div>
                            <svg className="wgt-bedesk-send-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z" />
                            </svg>
                        </Link>
                    </div>
                )}

                {settings.show_tickets_section && settings.enable_create_ticket && (
                    <NewTicketCard />
                )}

                {settings.show_tickets_section && (
                    <TicketListCard />
                )}

                {settings.show_help_center && settings.enable_search_help && (
                    <div className="wgt-bedesk-card wgt-bedesk-card-padded">
                        <form onSubmit={handleSearchSubmit} className="wgt-bedesk-search-form">
                            <input
                                type="search"
                                className="wgt-bedesk-search-input"
                                placeholder={t('home.search_for_help')}
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                aria-label={t('home.search_for_help')}
                            />
                            <button type="submit" className="wgt-bedesk-search-icon" aria-label={t('home.search_for_help')}>
                                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" />
                                </svg>
                            </button>
                        </form>
                        <div className="wgt-bedesk-articles">
                            {loading && <div className="wgt-bedesk-article-loading">{t('help.loading')}</div>}
                            {!loading && articles.map(a => (
                                <Link key={a.id} to={articleHref(a)} className="wgt-bedesk-article-link">
                                    <span>{a.title}</span>
                                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M8.59 16.59 13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z" />
                                    </svg>
                                </Link>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
