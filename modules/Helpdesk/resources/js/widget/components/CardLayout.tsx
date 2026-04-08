import React from 'react';

interface CardLayoutProps {
    children: React.ReactNode;
    className?: string;
}

export function CardLayout({ children, className = '' }: CardLayoutProps) {
    return (
        <div className={`rounded-lg bg-white shadow-sm overflow-hidden transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 ${className}`}>
            {children}
        </div>
    );
}
