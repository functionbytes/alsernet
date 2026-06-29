import React from 'react';

interface CardLayoutProps {
    children: React.ReactNode;
    className?: string;
}

export function CardLayout({ children, className = '' }: CardLayoutProps) {
    return (
        <div className={`wgt-card ${className}`}>
            {children}
        </div>
    );
}
