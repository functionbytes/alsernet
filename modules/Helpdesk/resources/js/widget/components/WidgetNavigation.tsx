import React from 'react';
import { Link, useLocation } from 'react-router-dom';
import { useWidgetStore } from '../widget-store';
import { useTranslation } from '../i18n/useLanguage';

interface NavTab {
    route: string;
    labelKey: string;
    icon: (isActive: boolean) => JSX.Element;
}

const NAV_TABS: NavTab[] = [
    {
        route: '/',
        labelKey: 'nav.home',
        icon: (isActive) => (
            <svg className="w-6 h-6" fill={isActive ? 'currentColor' : 'none'} stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={isActive ? 0 : 2} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
        )
    },
    {
        route: '/conversation',
        labelKey: 'nav.messages',
        icon: (isActive) => (
            <svg className="w-6 h-6" fill={isActive ? 'currentColor' : 'none'} stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={isActive ? 0 : 2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
        )
    },
    {
        route: '/help',
        labelKey: 'nav.help',
        icon: (isActive) => (
            <svg className="w-6 h-6" fill={isActive ? 'currentColor' : 'none'} stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={isActive ? 0 : 2} d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        )
    }
];

export function WidgetNavigation() {
    const location = useLocation();
    const settings = useWidgetStore(state => state.settings);
    const t = useTranslation();

    const isTabActive = (route: string): boolean => {
        if (route === '/') {
            return location.pathname === '/';
        }
        return location.pathname.startsWith(route);
    };

    return (
        <nav className="flex items-center bg-white border-t border-gray-200 shadow-lg">
            {NAV_TABS.map((tab) => {
                const isActive = isTabActive(tab.route);

                return (
                    <Link
                        key={tab.route}
                        to={tab.route}
                        className={`flex-1 flex flex-col items-center justify-center py-3 px-2 transition-colors ${
                            isActive
                                ? ''
                                : 'text-gray-500 hover:text-gray-700'
                        }`}
                        style={isActive ? { color: settings.primary_color } : {}}
                    >
                        <div className="mb-1">
                            {tab.icon(isActive)}
                        </div>
                        <span className={`text-xs ${isActive ? 'font-semibold' : 'font-normal'}`}>
                            {t(tab.labelKey)}
                        </span>
                    </Link>
                );
            })}
        </nav>
    );
}
