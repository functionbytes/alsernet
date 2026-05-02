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
            <svg viewBox="0 -960 960 960" fill="currentColor" aria-hidden="true">
                {isActive ? (
                    <path d="M160-120v-480l320-240 320 240v480H560v-280H400v280H160Z" />
                ) : (
                    <path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z" />
                )}
            </svg>
        ),
    },
    {
        route: '/conversation',
        labelKey: 'nav.messages',
        icon: (isActive) => (
            <svg viewBox="0 -960 960 960" fill="currentColor" aria-hidden="true">
                {isActive ? (
                    <path d="M240-400h320v-80H240v80Zm0-120h480v-80H240v80Zm0-120h480v-80H240v80ZM80-80v-720q0-33 23.5-56.5T160-880h640q33 0 56.5 23.5T880-800v480q0 33-23.5 56.5T800-240H240L80-80Z" />
                ) : (
                    <path d="M240-400h320v-80H240v80Zm0-120h480v-80H240v80Zm0-120h480v-80H240v80ZM80-80v-720q0-33 23.5-56.5T160-880h640q33 0 56.5 23.5T880-800v480q0 33-23.5 56.5T800-240H240L80-80Zm126-240h594v-480H160v525l46-45Zm-46 0v-480 480Z" />
                )}
            </svg>
        ),
    },
    {
        route: '/help',
        labelKey: 'nav.help',
        icon: (isActive) => (
            <svg viewBox="0 0 24 24" fill={isActive ? 'currentColor' : 'none'} stroke="currentColor" strokeWidth={isActive ? 0 : 2} aria-hidden="true">
                {isActive ? (
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z" />
                ) : (
                    <path strokeLinecap="round" strokeLinejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                )}
            </svg>
        ),
    },
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
        <nav className="wgt-bottom-nav">
            {NAV_TABS.map((tab) => {
                const isActive = isTabActive(tab.route);
                return (
                    <Link
                        key={tab.route}
                        to={tab.route}
                        className={`wgt-nav-tab${isActive ? ' is-active' : ''}`}
                        style={isActive ? { color: settings.primary_color } : undefined}
                    >
                        {tab.icon(isActive)}
                        <span className="wgt-nav-label">{t(tab.labelKey)}</span>
                    </Link>
                );
            })}
        </nav>
    );
}
