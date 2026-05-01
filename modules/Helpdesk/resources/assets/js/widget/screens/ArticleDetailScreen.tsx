import React, { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

interface Article {
    id: string;
    title: string;
    body: string;
    description: string;
    category?: string;
    section?: string;
    slug?: string;
}

export function ArticleDetailScreen() {
    const { articleId } = useParams<{ articleId: string }>();
    const navigate = useNavigate();
    const [article, setArticle] = useState<Article | null>(null);
    const [loading, setLoading] = useState(true);
    const [feedback, setFeedback] = useState<'yes' | 'no' | null>(null);

    useEffect(() => {
        if (!articleId) {
            setLoading(false);
            return;
        }
        let cancelled = false;
        setFeedback(null);
        fetch(`/hd/api/helpcenter/articles/${articleId}`)
            .then(r => r.ok ? r.json() : null)
            .then((data: Article | null) => {
                if (cancelled) return;
                setArticle(data);
            })
            .catch(() => { /* silent */ })
            .finally(() => { if (!cancelled) setLoading(false); });
        return () => { cancelled = true; };
    }, [articleId]);

    const externalUrl = article
        ? `/hc/articles/${article.id}${article.slug ? `/${article.slug}` : ''}`
        : '#';

    const submitFeedback = (value: 'yes' | 'no') => {
        if (!article) return;
        setFeedback(value);
        const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
        fetch(`/hd/api/helpcenter/articles/${article.id}/feedback`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
            },
            body: JSON.stringify({ helpful: value === 'yes' }),
        }).catch(() => { /* silent: feedback UI already updated */ });
    };

    return (
        <div className="wgt-article-page">
            <div className="wgt-article-topbar">
                <button
                    type="button"
                    onClick={() => navigate(-1)}
                    className="wgt-article-back"
                >
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M15.41 16.59 10.83 12l4.58-4.59L14 6l-6 6 6 6 1.41-1.41z" />
                    </svg>
                    <span>Back</span>
                </button>
            </div>

            <div className="wgt-article-body-wrap">
                {loading && (
                    <div className="wgt-help-empty">Cargando…</div>
                )}

                {!loading && !article && (
                    <div className="wgt-help-empty">Artículo no encontrado.</div>
                )}

                {!loading && article && (
                    <article className="wgt-article">
                        <h1 className="wgt-article-title">{article.title}</h1>
                        {article.description && (
                            <p className="wgt-article-description">{article.description}</p>
                        )}
                        <div
                            className="wgt-article-content"
                            dangerouslySetInnerHTML={{ __html: article.body ?? '' }}
                        />

                        <div className="wgt-article-feedback">
                            {feedback === null ? (
                                <>
                                    <div className="wgt-article-feedback-label">
                                        Was this article helpful?
                                    </div>
                                    <div className="wgt-article-feedback-actions">
                                        <button
                                            type="button"
                                            onClick={() => submitFeedback('yes')}
                                            className="wgt-feedback-btn wgt-feedback-yes"
                                        >
                                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z" />
                                            </svg>
                                            <span>Yes</span>
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => submitFeedback('no')}
                                            className="wgt-feedback-btn wgt-feedback-no"
                                        >
                                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <path d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z" />
                                            </svg>
                                            <span>No</span>
                                        </button>
                                    </div>
                                </>
                            ) : (
                                <div
                                    className={`wgt-article-feedback-thanks wgt-feedback-thanks-${feedback}`}
                                    role="status"
                                >
                                    {feedback === 'yes' ? (
                                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z" />
                                        </svg>
                                    ) : (
                                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                                        </svg>
                                    )}
                                    <span>
                                        {feedback === 'yes'
                                            ? 'Thank you for the feedback!'
                                            : "Thanks — we'll work on improving this article."}
                                    </span>
                                </div>
                            )}

                            <a
                                href={externalUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="wgt-article-external"
                            >
                                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z" />
                                </svg>
                                <span>Open in help center</span>
                            </a>
                        </div>
                    </article>
                )}
            </div>
        </div>
    );
}
