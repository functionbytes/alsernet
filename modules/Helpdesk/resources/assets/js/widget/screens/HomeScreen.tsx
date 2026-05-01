import React, { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useWidgetStore } from '../widget-store';
import { useTranslation } from '../i18n/useLanguage';

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

export function HomeScreen() {
    const settings = useWidgetStore(state => state.settings);
    const t = useTranslation();
    const navigate = useNavigate();
    const [articles, setArticles] = useState<Article[]>([]);
    const [loading, setLoading] = useState(true);
    const [searchQuery, setSearchQuery] = useState('');

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const q = searchQuery.trim();
        navigate(q ? `/help?q=${encodeURIComponent(q)}` : '/help');
    };

    useEffect(() => {
        let cancelled = false;
        fetch('/hd/api/helpcenter')
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
                {settings.enable_send_message && (
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
