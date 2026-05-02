import React, { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom';

interface Article {
    id: string;
    title: string;
    excerpt: string;
    category: string;
    section?: string;
}

interface Category {
    id: string;
    name: string;
    icon: string;
    count: number;
}

interface HelpCenterData {
    categories: Category[];
    articles: Article[];
}

export function HelpScreen() {
    const navigate = useNavigate();
    const { categoryId } = useParams<{ categoryId?: string }>();
    const [searchParams] = useSearchParams();
    const initialQuery = searchParams.get('q') ?? '';

    const [searchQuery, setSearchQuery] = useState(initialQuery);
    const [categories, setCategories] = useState<Category[]>([]);
    const [articles, setArticles] = useState<Article[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        let cancelled = false;
        fetch('/hd/api/helpcenter')
            .then(r => r.ok ? r.json() : null)
            .then((data: HelpCenterData | null) => {
                if (cancelled || !data) return;
                setCategories(data.categories ?? []);
                setArticles(data.articles ?? []);
            })
            .catch(() => { /* silent */ })
            .finally(() => { if (!cancelled) setLoading(false); });
        return () => { cancelled = true; };
    }, []);

    const selectedCategory = useMemo(
        () => categories.find(c => c.id === categoryId) ?? null,
        [categories, categoryId]
    );

    const visibleArticles = useMemo(() => {
        const q = searchQuery.trim().toLowerCase();
        return articles.filter(a => {
            if (selectedCategory && a.category !== selectedCategory.name) return false;
            if (!q) return true;
            return (
                a.title.toLowerCase().includes(q) ||
                (a.excerpt ?? '').toLowerCase().includes(q)
            );
        });
    }, [articles, searchQuery, selectedCategory]);

    const isSearching = searchQuery.trim().length > 0;
    const showCategoriesList = !selectedCategory && !isSearching;

    return (
        <div className="wgt-help">
            <div className="wgt-help-topbar">
                {(selectedCategory || isSearching) && (
                    <button
                        type="button"
                        onClick={() => navigate('/help')}
                        className="wgt-help-back"
                        aria-label="Back"
                    >
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M15.41 16.59 10.83 12l4.58-4.59L14 6l-6 6 6 6 1.41-1.41z" />
                        </svg>
                    </button>
                )}
                <h2 className="wgt-help-title">Help</h2>
            </div>

            <div className="wgt-help-search-wrap">
                <div className="wgt-help-search">
                    <input
                        type="search"
                        className="wgt-help-search-input"
                        placeholder="Search for answers"
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        autoFocus={isSearching}
                    />
                    <span className="wgt-help-search-lupa" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" />
                        </svg>
                    </span>
                </div>
            </div>

            <div className="wgt-help-list">
                {loading && (
                    <div className="wgt-help-empty">Cargando…</div>
                )}

                {!loading && showCategoriesList && categories.length === 0 && (
                    <div className="wgt-help-empty">No hay categorías disponibles.</div>
                )}

                {!loading && showCategoriesList && categories.map(cat => (
                    <Link
                        key={cat.id}
                        to={`/help/category/${cat.id}`}
                        className="wgt-help-row"
                    >
                        <div className="wgt-help-row-text">
                            <div className="wgt-help-row-title">{cat.name}</div>
                            <div className="wgt-help-row-meta">
                                {cat.count} {cat.count === 1 ? 'article' : 'articles'}
                            </div>
                        </div>
                        <svg className="wgt-help-row-chevron" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M8.59 16.59 13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z" />
                        </svg>
                    </Link>
                ))}

                {!loading && (selectedCategory || isSearching) && (
                    <>
                        {selectedCategory && (
                            <div className="wgt-help-section-head">
                                <div className="wgt-help-section-title">{selectedCategory.name}</div>
                                <div className="wgt-help-section-meta">
                                    {selectedCategory.count} {selectedCategory.count === 1 ? 'article' : 'articles'}
                                </div>
                            </div>
                        )}

                        {visibleArticles.length === 0 && (
                            <div className="wgt-help-empty">
                                {isSearching ? `Sin resultados para "${searchQuery}"` : 'Sin artículos en esta categoría.'}
                            </div>
                        )}

                        {visibleArticles.map(article => (
                            <Link
                                key={article.id}
                                to={`/help/article/${article.id}`}
                                className="wgt-help-row"
                            >
                                <div className="wgt-help-row-title">{article.title}</div>
                                <svg className="wgt-help-row-chevron" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M8.59 16.59 13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z" />
                                </svg>
                            </Link>
                        ))}
                    </>
                )}
            </div>
        </div>
    );
}
