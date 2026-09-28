import React, { useState } from 'react';
import { RecommendationProduct } from '../widget-store';
import { addToCart, canAddToCart, getShop } from '../widget-commerce';
import { touchChatSession } from '../widget-attribution';
import { useTranslation } from '../i18n/useLanguage';

/**
 * Producto de una tarjeta. Los del catálogo (carrusel enviado por el agente o
 * el bot) traen además combinaciones y disponibilidad; los de Engagement no,
 * y en ese caso solo se ofrece "Ver producto".
 */
type CardProduct = RecommendationProduct & {
    currency?: string;
    id_product_attribute?: number;
    has_combinations?: boolean;
    available?: boolean;
    price_original?: number;
};

interface RecommendationsCardProps {
    products: CardProduct[];
    primaryColor?: string;
}

type AddState = 'idle' | 'adding' | 'added' | 'error';

const ProductPlaceholder = () => (
    <div className="wgt-rec-img-placeholder" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="currentColor" width={24} height={24}>
            <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z" />
        </svg>
    </div>
);

export function RecommendationsCard({ products, primaryColor = '#90bb13' }: RecommendationsCardProps) {
    const t = useTranslation();
    // Imágenes que fallan al cargar (p. ej. ficheros ausentes) → placeholder,
    // en vez del icono de imagen rota del navegador.
    const [failed, setFailed] = useState<Record<string, boolean>>({});
    const [addState, setAddState] = useState<Record<string, AddState>>({});

    if (products.length === 0) {
        return null;
    }

    const shopCanAdd = canAddToCart();
    const checkoutUrl = getShop()?.checkout_url;
    const anyAdded = Object.values(addState).includes('added');

    const openProduct = (product: CardProduct, sameTab = false) => {
        if (!product.url) return;
        if (sameTab) {
            // Elegir talla/color en la ficha: misma pestaña, el chat se recupera.
            window.location.href = product.url;
        } else {
            window.open(product.url, '_blank', 'noopener,noreferrer');
        }
    };

    const handleAdd = async (product: CardProduct) => {
        const key = String(product.id);
        if (addState[key] === 'adding') return;
        setAddState((s) => ({ ...s, [key]: 'adding' }));
        const res = await addToCart(Number(product.id), product.id_product_attribute ?? 0, 1);
        setAddState((s) => ({ ...s, [key]: res.ok ? 'added' : 'error' }));
        if (res.ok) {
            // Añadido desde el chat: la venta se atribuye a esta conversación.
            touchChatSession('cart');
        }
    };

    const formatPrice = (price?: number, currency?: string): string | null => {
        if (price == null) return null;
        try {
            return new Intl.NumberFormat('es', { style: 'currency', currency: currency || 'EUR', minimumFractionDigits: 2 }).format(price);
        } catch {
            return price.toFixed(2);
        }
    };

    return (
        <div className="wgt-rec-card" role="region" aria-label={t('shop.recommended')}>
            <p className="wgt-rec-label">
                <svg viewBox="0 0 24 24" fill="currentColor" width={14} height={14} aria-hidden="true">
                    <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zm4.24 16L12 15.45 7.77 18l1.12-4.81-3.73-3.23 4.92-.42L12 5l1.92 4.53 4.92.42-3.73 3.23L16.23 18z" />
                </svg>
                {t('shop.recommended')}
            </p>
            <div className="wgt-rec-scroll">
                {products.map((product) => {
                    const key = String(product.id);
                    const state = addState[key] ?? 'idle';
                    // Solo productos del catálogo (saben si tienen combinaciones).
                    const fromCatalog = typeof product.has_combinations === 'boolean';
                    const unavailable = product.available === false;
                    const canAdd = shopCanAdd && fromCatalog && !product.has_combinations && !unavailable;
                    const needsOptions = fromCatalog && product.has_combinations && !unavailable && !!product.url;

                    return (
                        <div key={key} className="wgt-rec-item">
                            <button
                                type="button"
                                className="wgt-rec-open"
                                onClick={() => openProduct(product)}
                                aria-label={`${t('shop.view_product')}: ${product.name}`}
                                disabled={!product.url}
                            >
                                <div className="wgt-rec-img-wrap">
                                    {product.image_url && !failed[key] ? (
                                        <img
                                            src={product.image_url}
                                            alt=""
                                            loading="lazy"
                                            className="wgt-rec-img"
                                            onError={() => setFailed((f) => ({ ...f, [key]: true }))}
                                        />
                                    ) : (
                                        <ProductPlaceholder />
                                    )}
                                </div>
                                <div className="wgt-rec-info">
                                    <p className="wgt-rec-name">{product.name}</p>
                                    {product.price != null && (
                                        <p className="wgt-rec-price" style={{ color: primaryColor }}>
                                            {formatPrice(product.price, product.currency)}
                                            {product.price_original != null && product.price_original > product.price && (
                                                <span className="wgt-rec-price-was">{formatPrice(product.price_original, product.currency)}</span>
                                            )}
                                        </p>
                                    )}
                                    {unavailable && <span className="wgt-rec-stock">{t('shop.out_of_stock')}</span>}
                                </div>
                            </button>
                            <div className="wgt-rec-actions">
                                {canAdd ? (
                                    <button
                                        type="button"
                                        className={`wgt-rec-add is-${state}`}
                                        style={state === 'added' ? undefined : { background: primaryColor }}
                                        onClick={() => handleAdd(product)}
                                        disabled={state === 'adding'}
                                        aria-live="polite"
                                    >
                                        {state === 'adding' ? t('shop.adding') : state === 'added' ? `✓ ${t('shop.added')}` : t('shop.add_to_cart')}
                                    </button>
                                ) : needsOptions ? (
                                    <button type="button" className="wgt-rec-options" onClick={() => openProduct(product, true)}>
                                        {t('shop.choose_options')}
                                    </button>
                                ) : product.url ? (
                                    <button type="button" className="wgt-rec-options" onClick={() => openProduct(product)}>
                                        {t('shop.view_product')}
                                    </button>
                                ) : null}
                                {state === 'error' && <p className="wgt-rec-error" role="alert">{t('shop.add_error')}</p>}
                            </div>
                        </div>
                    );
                })}
            </div>
            {anyAdded && checkoutUrl && (
                <a className="wgt-rec-checkout" href={checkoutUrl} style={{ background: primaryColor }}>
                    {t('shop.checkout')} →
                </a>
            )}
        </div>
    );
}
