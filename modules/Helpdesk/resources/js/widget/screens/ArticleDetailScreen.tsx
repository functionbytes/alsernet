import React, { useState, useEffect } from 'react';
import { Link, useParams, useNavigate } from 'react-router-dom';
import { useWidgetStore } from '../widget-store';

interface Article {
    id: string;
    title: string;
    body: string;
    description: string;
    category: string;
    section?: string;
}

export function ArticleDetailScreen() {
    const { articleId } = useParams<{ articleId: string }>();
    const navigate = useNavigate();
    const settings = useWidgetStore(state => state.settings);
    const [article, setArticle] = useState<Article | null>(null);
    const [loading, setLoading] = useState(true);
    const [helpful, setHelpful] = useState<boolean | null>(null);

    useEffect(() => {
        const fetchArticle = async () => {
            try {
                const response = await fetch(`/lc/api/helpcenter/articles/${articleId}`);
                if (!response.ok) throw new Error('Failed to fetch article');

                const data: Article = await response.json();
                setArticle(data);
                setLoading(false);
                console.log('✅ Article loaded:', data);
            } catch (error) {
                console.error('❌ Failed to load article:', error);
                setLoading(false);
            }
        };

        if (articleId) {
            fetchArticle();
        }
    }, [articleId]);

    const handleFeedback = (isHelpful: boolean) => {
        setHelpful(isHelpful);
        // TODO: Send feedback to backend
        console.log(`Article ${articleId} marked as ${isHelpful ? 'helpful' : 'not helpful'}`);
    };

    if (loading) {
        return (
            <div className="flex flex-col h-full bg-gray-50">
                <div className="flex items-center gap-3 px-4 py-3 bg-white border-b border-gray-200 shadow-sm">
                    <button
                        onClick={() => navigate(-1)}
                        className="flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-100 transition-colors"
                    >
                        <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <h1 className="flex-1 text-lg font-semibold text-gray-900">Loading...</h1>
                </div>
                <div className="flex-1 flex items-center justify-center">
                    <div className="w-10 h-10 border-4 border-gray-200 border-t-primary-500 rounded-full animate-spin" style={{ borderTopColor: settings.primary_color }}></div>
                </div>
            </div>
        );
    }

    if (!article) {
        return (
            <div className="flex flex-col h-full bg-gray-50">
                <div className="flex items-center gap-3 px-4 py-3 bg-white border-b border-gray-200 shadow-sm">
                    <button
                        onClick={() => navigate(-1)}
                        className="flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-100 transition-colors"
                    >
                        <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <h1 className="flex-1 text-lg font-semibold text-gray-900">Article Not Found</h1>
                </div>
                <div className="flex-1 flex items-center justify-center">
                    <div className="text-center">
                        <svg className="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p className="text-gray-500 text-sm">Article not found</p>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="flex flex-col h-full bg-gray-50 opacity-0" style={{ animation: 'fadeIn 0.3s ease-out forwards' }}>
            {/* Header */}
            <div className="flex items-center gap-3 px-4 py-3 bg-white border-b border-gray-200 shadow-sm">
                <button
                    onClick={() => navigate(-1)}
                    className="flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-100 transition-colors"
                >
                    <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <h1 className="flex-1 text-lg font-semibold text-gray-900 line-clamp-1">
                    {article.title}
                </h1>
            </div>

            {/* Breadcrumb */}
            <div className="px-4 py-2 bg-white border-b border-gray-200">
                <div className="flex items-center gap-2 text-xs text-gray-600">
                    <Link to="/help" className="hover:underline hover:text-gray-900">
                        Help Center
                    </Link>
                    <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                    </svg>
                    <span className="hover:underline hover:text-gray-900">{article.category}</span>
                    {article.section && (
                        <>
                            <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                            </svg>
                            <span className="hover:underline hover:text-gray-900">{article.section}</span>
                        </>
                    )}
                </div>
            </div>

            {/* Article Content */}
            <div className="flex-1 overflow-y-auto px-4 py-6">
                <article className="max-w-3xl mx-auto">
                    <h1 className="text-2xl font-bold text-gray-900 mb-4">
                        {article.title}
                    </h1>

                    {article.description && (
                        <p className="text-lg text-gray-600 mb-6 leading-relaxed">
                            {article.description}
                        </p>
                    )}

                    <div
                        className="prose prose-sm max-w-none
                                   prose-headings:text-gray-900
                                   prose-p:text-gray-700
                                   prose-a:no-underline hover:prose-a:underline
                                   prose-strong:text-gray-900
                                   prose-code:text-gray-900
                                   prose-code:bg-gray-100
                                   prose-pre:bg-gray-100
                                   prose-img:rounded-lg prose-img:shadow-md"
                        style={{
                            '--tw-prose-links': settings.primary_color,
                        } as React.CSSProperties}
                        dangerouslySetInnerHTML={{ __html: article.body }}
                    />
                </article>

                {/* Feedback Section */}
                <div className="max-w-3xl mx-auto mt-12 pt-8 border-t border-gray-200">
                    <div className="text-center">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">
                            Was this article helpful?
                        </h3>

                        {helpful === null ? (
                            <div className="flex items-center justify-center gap-4">
                                <button
                                    onClick={() => handleFeedback(true)}
                                    className="flex items-center gap-2 px-6 py-3 bg-white border-2 border-gray-300 rounded-lg hover:border-green-500 hover:bg-green-50 transition-all"
                                >
                                    <svg className="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                                    </svg>
                                    <span className="font-medium text-gray-900">Yes, it helped!</span>
                                </button>
                                <button
                                    onClick={() => handleFeedback(false)}
                                    className="flex items-center gap-2 px-6 py-3 bg-white border-2 border-gray-300 rounded-lg hover:border-red-500 hover:bg-red-50 transition-all"
                                >
                                    <svg className="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018a2 2 0 01.485.06l3.76.94m-7 10v5a2 2 0 002 2h.096c.5 0 .905-.405.905-.904 0-.715.211-1.413.608-2.008L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5" />
                                    </svg>
                                    <span className="font-medium text-gray-900">No, not really</span>
                                </button>
                            </div>
                        ) : (
                            <div className={`inline-flex items-center gap-2 px-6 py-3 rounded-lg ${
                                helpful
                                    ? 'bg-green-100 text-green-800'
                                    : 'bg-red-100 text-red-800'
                            }`}>
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                </svg>
                                <span className="font-medium">
                                    {helpful ? 'Thanks for your feedback!' : "We'll work on improving this article."}
                                </span>
                            </div>
                        )}
                    </div>

                    {/* Still need help */}
                    <div className="mt-8 pt-6 border-t border-gray-200 text-center">
                        <p className="text-sm text-gray-600 mb-3">
                            Still need help?
                        </p>
                        <Link
                            to="/conversation"
                            className="inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium text-white transition-all hover:shadow-md"
                            style={{ backgroundColor: settings.primary_color }}
                        >
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            Contact Support
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    );
}
