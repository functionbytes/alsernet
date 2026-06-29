import React from 'react';
import { RecommendationProduct } from '../widget-store';

interface RecommendationsCardProps {
    products: RecommendationProduct[];
    primaryColor?: string;
}

export function RecommendationsCard({ products, primaryColor = '#90bb13' }: RecommendationsCardProps) {
    if (products.length === 0) {
        return null;
    }

    const handleProductClick = (product: RecommendationProduct) => {
        if (product.url) {
            window.open(product.url, '_blank', 'noopener,noreferrer');
        }
    };

    const formatPrice = (price?: number): string | null => {
        if (price == null) return null;
        return new Intl.NumberFormat('es', { style: 'currency', currency: 'EUR', minimumFractionDigits: 2 }).format(price);
    };

    return (
        <div className="wgt-rec-card" role="region" aria-label="Productos recomendados">
            <p className="wgt-rec-label">
                <svg viewBox="0 0 24 24" fill="currentColor" width={14} height={14} aria-hidden="true">
                    <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zm4.24 16L12 15.45 7.77 18l1.12-4.81-3.73-3.23 4.92-.42L12 5l1.92 4.53 4.92.42-3.73 3.23L16.23 18z" />
                </svg>
                Recomendados para ti
            </p>
            <div className="wgt-rec-scroll">
                {products.map((product) => (
                    <button
                        key={product.id}
                        type="button"
                        className="wgt-rec-item"
                        onClick={() => handleProductClick(product)}
                        aria-label={product.url ? `Ver ${product.name}` : product.name}
                    >
                        <div className="wgt-rec-img-wrap">
                            {product.image_url ? (
                                <img
                                    src={product.image_url}
                                    alt={product.name}
                                    loading="lazy"
                                    className="wgt-rec-img"
                                />
                            ) : (
                                <div className="wgt-rec-img-placeholder" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="currentColor" width={24} height={24}>
                                        <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z" />
                                    </svg>
                                </div>
                            )}
                        </div>
                        <div className="wgt-rec-info">
                            <p className="wgt-rec-name">{product.name}</p>
                            {product.price != null && (
                                <p className="wgt-rec-price" style={{ color: primaryColor }}>
                                    {formatPrice(product.price)}
                                </p>
                            )}
                            {product.url && (
                                <span className="wgt-rec-link">
                                    Ver producto
                                    <svg viewBox="0 0 24 24" fill="currentColor" width={10} height={10} aria-hidden="true">
                                        <path d="M19 19H5V5h7V3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z" />
                                    </svg>
                                </span>
                            )}
                        </div>
                    </button>
                ))}
            </div>
        </div>
    );
}
