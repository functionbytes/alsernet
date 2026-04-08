import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useWidgetStore } from '../widget-store';

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
    const settings = useWidgetStore(state => state.settings);
    const [searchQuery, setSearchQuery] = useState('');
    const [categories, setCategories] = useState<Category[]>([]);
    const [articles, setArticles] = useState<Article[]>([]);
    const [loading, setLoading] = useState(true);
    const [selectedCategory, setSelectedCategory] = useState<string | null>(null);

    // Fetch help center data from backend
    useEffect(() => {
        const fetchHelpCenter = async () => {
            try {
                const response = await fetch('/lc/api/helpcenter');
                if (!response.ok) throw new Error('Failed to fetch help center data');

                const data: HelpCenterData = await response.json();
                setCategories(data.categories);
                setArticles(data.articles);
                setLoading(false);
                console.log('✅ Help center data loaded:', data);
            } catch (error) {
                console.error('❌ Failed to load help center data:', error);
                setLoading(false);
            }
        };

        fetchHelpCenter();
    }, []);

    // Filtrar artículos por búsqueda y categoría
    const filteredArticles = articles.filter(article => {
        const matchesSearch = searchQuery.trim() === '' ||
            article.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
            article.excerpt.toLowerCase().includes(searchQuery.toLowerCase());

        const matchesCategory = !selectedCategory || article.category === selectedCategory;

        return matchesSearch && matchesCategory;
    });

    const handleCategoryClick = (categoryName: string) => {
        if (selectedCategory === categoryName) {
            setSelectedCategory(null); // Deseleccionar si ya está seleccionado
        } else {
            setSelectedCategory(categoryName);
        }
    };

    return (
        <div className="flex flex-col h-full bg-gray-50 opacity-0" style={{ animation: 'fadeIn 0.3s ease-out forwards' }}>
            {/* Header */}
            <div className="flex items-center gap-3 px-4 py-3 bg-white border-b border-gray-200 shadow-sm">
                <Link to="/" className="flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-100 transition-colors">
                    <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>

                <h1 className="flex-1 text-lg font-semibold text-gray-900">
                    Help Center
                </h1>
            </div>

            {/* Search Bar */}
            <div className="px-4 py-3 bg-white border-b border-gray-200">
                <div className="relative">
                    <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        type="text"
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        placeholder="Search for help..."
                        className="w-full pl-10 pr-10 py-2 bg-gray-100 text-gray-900 rounded-full focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm"
                    />
                    {searchQuery && (
                        <button
                            onClick={() => setSearchQuery('')}
                            className="absolute inset-y-0 right-0 flex items-center pr-3"
                        >
                            <svg className="w-5 h-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    )}
                </div>
            </div>

            {/* Content */}
            <div className="flex-1 overflow-y-auto px-4 py-4 space-y-4">
                {/* Loading State */}
                {loading && (
                    <div className="flex items-center justify-center py-12">
                        <div className="flex flex-col items-center gap-3">
                            <div className="w-10 h-10 border-4 border-gray-200 border-t-primary-500 rounded-full animate-spin" style={{ borderTopColor: settings.primary_color }}></div>
                            <p className="text-sm text-gray-500">Loading help center...</p>
                        </div>
                    </div>
                )}

                {/* Categories - Solo mostrar si no hay búsqueda */}
                {!loading && !searchQuery && (
                    <div>
                        <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                            Browse by category
                        </h2>
                        <div className="grid grid-cols-2 gap-3">
                            {categories.map((category) => (
                                <button
                                    key={category.id}
                                    onClick={() => handleCategoryClick(category.name)}
                                    className={`group relative flex flex-col items-start gap-2 p-4 rounded-xl transition-all duration-200 ${
                                        selectedCategory === category.name
                                            ? 'text-white shadow-lg scale-105'
                                            : 'bg-white hover:bg-gray-50 hover:shadow-md hover:-translate-y-1 border border-gray-200'
                                    }`}
                                    style={selectedCategory === category.name ? {
                                        backgroundColor: settings.primary_color,
                                        borderColor: settings.primary_color
                                    } : {}}
                                >
                                    <div className={`w-12 h-12 rounded-lg flex items-center justify-center text-2xl transition-all ${
                                        selectedCategory === category.name
                                            ? 'bg-white/20'
                                            : 'bg-gray-100 group-hover:scale-110'
                                    }`}>
                                        {category.icon}
                                    </div>
                                    <div className="flex-1 w-full text-left">
                                        <div className={`font-semibold text-sm mb-1 ${
                                            selectedCategory === category.name
                                                ? 'text-white'
                                                : 'text-gray-900'
                                        }`}>
                                            {category.name}
                                        </div>
                                        <div className={`text-xs flex items-center gap-1 ${
                                            selectedCategory === category.name
                                                ? 'text-white/90'
                                                : 'text-gray-500'
                                        }`}>
                                            <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <span>{category.count} {category.count === 1 ? 'article' : 'articles'}</span>
                                        </div>
                                    </div>
                                </button>
                            ))}
                        </div>
                    </div>
                )}

                {/* Articles */}
                {!loading && (
                    <div>
                        <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                            {searchQuery
                                ? `Results for "${searchQuery}"`
                                : selectedCategory
                                ? selectedCategory
                                : 'Popular articles'}
                        </h2>

                        {filteredArticles.length > 0 ? (
                        <div className="space-y-3">
                            {filteredArticles.map((article) => (
                                <button
                                    key={article.id}
                                    onClick={() => navigate(`/help/article/${article.id}`)}
                                    className="group w-full text-left p-4 bg-white rounded-xl hover:shadow-lg transition-all duration-200 hover:-translate-y-1 border border-gray-200 hover:border-gray-300"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center gap-2 mb-2">
                                                <svg className="w-4 h-4 flex-shrink-0" style={{ color: settings.primary_color }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <h3 className="font-semibold text-gray-900 text-sm group-hover:text-gray-700 line-clamp-2">
                                                    {article.title}
                                                </h3>
                                            </div>
                                            <p className="text-xs text-gray-600 leading-relaxed mb-3 line-clamp-2">
                                                {article.excerpt}
                                            </p>
                                            <div className="flex items-center gap-2 flex-wrap">
                                                <span
                                                    className="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md"
                                                    style={{
                                                        color: settings.primary_color,
                                                        backgroundColor: `${settings.primary_color}15`
                                                    }}
                                                >
                                                    <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                    </svg>
                                                    {article.category}
                                                </span>
                                                {article.section && (
                                                    <span className="text-xs text-gray-500">
                                                        • {article.section}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                        <svg className="w-5 h-5 text-gray-400 flex-shrink-0 group-hover:text-gray-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </button>
                            ))}
                        </div>
                    ) : (
                        <div className="text-center py-12">
                            <svg className="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p className="text-gray-500 text-sm">
                                No articles found matching your search
                            </p>
                        </div>
                    )}
                    </div>
                )}

                {/* Contact Support Link */}
                {!loading && (
                    <div className="pt-4 pb-2 text-center">
                    <p className="text-sm text-gray-600 mb-2">
                        Can't find what you're looking for?
                    </p>
                    <Link
                        to="/conversation"
                        className="inline-flex items-center gap-2 text-sm font-medium"
                        style={{ color: settings.primary_color }}
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        Contact support
                    </Link>
                    </div>
                )}
            </div>
        </div>
    );
}
