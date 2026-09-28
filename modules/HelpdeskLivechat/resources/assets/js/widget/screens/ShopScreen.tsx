import React, { useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useWidgetStore } from '../widget-store';
import { useTranslation } from '../i18n/useLanguage';
import {
    addToCart,
    canAddToCart,
    CartSnapshot,
    getCart,
    getShop,
    getViewedProducts,
    refreshCart,
    ViewedProduct,
} from '../widget-commerce';
import { touchChatSession } from '../widget-attribution';

/**
 * Panel "Productos" del visitante (equivalente al coviewer de Oct8ne):
 * productos vistos, su cesta y la ficha de un producto con Añadir / Pagar.
 * Datos de la propia tienda (mismo origen): precio, stock y cesta los decide
 * siempre PrestaShop.
 */

interface ProductDetail {
    id: string;
    id_product_attribute: number;
    title: string;
    reference?: string;
    price: number;
    price_original: number | null;
    currency: string | null;
    description: string;
    images: string[];
    url: string;
    has_combinations: boolean;
    available: boolean;
}

type Tab = 'viewed' | 'cart';

function money(value: number | null | undefined, currency?: string | null): string {
    if (typeof value !== 'number') return '';
    try {
        return new Intl.NumberFormat(document.documentElement.lang || 'es', { style: 'currency', currency: currency || 'EUR' }).format(value);
    } catch {
        return value.toFixed(2);
    }
}

function Thumb({ src, alt, className }: { src?: string | null; alt: string; className: string }) {
    const [failed, setFailed] = useState(false);
    return (
        <span className={className}>
            {src && !failed ? (
                <img src={src} alt={alt} loading="lazy" onError={() => setFailed(true)} />
            ) : (
                <svg viewBox="0 0 24 24" fill="currentColor" width={22} height={22} aria-hidden="true">
                    <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z" />
                </svg>
            )}
        </span>
    );
}

function EmptyState({ title, sub, icon }: { title: string; sub: string; icon: 'eye' | 'cart' }) {
    return (
        <div className="wgt-shop-empty">
            <span className="wgt-shop-empty-icon" aria-hidden="true">
                {icon === 'cart' ? (
                    <svg viewBox="0 0 24 24" width={28} height={28} fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round">
                        <circle cx="9" cy="20" r="1.4" /><circle cx="18" cy="20" r="1.4" />
                        <path d="M2.5 3.5h2.6l2.4 11.2a1.6 1.6 0 0 0 1.6 1.3h8.6a1.6 1.6 0 0 0 1.6-1.2l1.7-7.3H6" />
                    </svg>
                ) : (
                    <svg viewBox="0 0 24 24" width={28} height={28} fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round">
                        <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" />
                    </svg>
                )}
            </span>
            <p className="wgt-shop-empty-title">{title}</p>
            <p className="wgt-shop-empty-sub">{sub}</p>
        </div>
    );
}

export function ShopScreen() {
    const t = useTranslation();
    const navigate = useNavigate();
    const primaryColor = useWidgetStore(state => state.settings.primary_color) || '#90bb13';
    const shop = getShop();

    const [tab, setTab] = useState<Tab>('viewed');
    const [viewed, setViewed] = useState<ViewedProduct[]>(() => getViewedProducts());
    const [cart, setCart] = useState<CartSnapshot | null | undefined>(() => getCart());
    const [detailId, setDetailId] = useState<string | null>(null);
    const [detail, setDetail] = useState<ProductDetail | null>(null);
    const [loading, setLoading] = useState(false);
    const [imageIndex, setImageIndex] = useState(0);
    const [addState, setAddState] = useState<'idle' | 'adding' | 'added' | 'error'>('idle');

    // La cesta puede cambiar en otra parte (tema, agente, otra pestaña).
    useEffect(() => {
        const sync = () => { setCart(getCart()); setViewed(getViewedProducts()); };
        refreshCart().finally(sync);
        window.addEventListener('helpdesk:cart-changed', sync);
        return () => window.removeEventListener('helpdesk:cart-changed', sync);
    }, []);

    useEffect(() => {
        if (!detailId || !shop?.product_url) return;
        let cancelled = false;
        setLoading(true);
        setDetail(null);
        setImageIndex(0);
        setAddState('idle');
        const url = new URL(shop.product_url, window.location.href);
        url.searchParams.set('id', detailId);
        fetch(url.toString(), { credentials: 'same-origin', headers: { Accept: 'application/json' }, cache: 'no-store' })
            .then(r => (r.ok ? r.json() : null))
            .then((json) => { if (!cancelled) setDetail(json?.product ?? null); })
            .catch(() => { if (!cancelled) setDetail(null); })
            .finally(() => { if (!cancelled) setLoading(false); });
        return () => { cancelled = true; };
    }, [detailId, shop?.product_url]);

    const cartLines = cart?.lines ?? [];
    const cartCount = cart?.products_count ?? 0;

    const handleAdd = async () => {
        if (!detail) return;
        setAddState('adding');
        const res = await addToCart(Number(detail.id), detail.id_product_attribute ?? 0, 1);
        setAddState(res.ok ? 'added' : 'error');
        if (res.ok) {
            touchChatSession('cart');
            setCart(getCart());
        }
    };

    const tabs = useMemo(() => ([
        { key: 'viewed' as Tab, label: t('shop.viewed'), badge: viewed.length || null },
        { key: 'cart' as Tab, label: t('shop.cart'), badge: cartCount || null },
    ]), [t, viewed.length, cartCount]);

    // ── Ficha de producto ─────────────────────────────────────────────
    if (detailId) {
        const images = detail?.images?.length ? detail.images : [];
        const canAdd = !!detail && detail.available && !detail.has_combinations && canAddToCart();
        return (
            <div className="wgt-shop">
                <header className="wgt-screen-header wgt-shop-header">
                    <button type="button" className="wgt-icon-btn wgt-shop-back" onClick={() => setDetailId(null)} aria-label={t('shop.back')}>
                        <svg viewBox="0 0 24 24" width={20} height={20} fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
                    </button>
                    <span className="wgt-screen-header-title">{t('shop.products_tab')}</span>
                </header>
                <div className="wgt-shop-body">
                    {loading || !detail ? (
                        <div className="wgt-shop-loading" aria-live="polite">{loading ? t('shop.loading') : t('shop.add_error')}</div>
                    ) : (
                        <article className="wgt-pd">
                            <div className="wgt-pd-media">
                                <Thumb src={images[imageIndex]} alt={detail.title} className="wgt-pd-image" />
                                {images.length > 1 && (
                                    <div className="wgt-pd-dots" role="tablist">
                                        {images.map((_, i) => (
                                            <button key={i} type="button" role="tab" aria-selected={i === imageIndex}
                                                aria-label={`${i + 1} / ${images.length}`}
                                                className={`wgt-pd-dot${i === imageIndex ? ' is-active' : ''}`}
                                                style={i === imageIndex ? { background: primaryColor } : undefined}
                                                onClick={() => setImageIndex(i)} />
                                        ))}
                                    </div>
                                )}
                            </div>
                            <h2 className="wgt-pd-title">{detail.title}</h2>
                            <div className="wgt-pd-price">
                                <span className="wgt-pd-price-now">{money(detail.price, detail.currency)}</span>
                                {detail.price_original && <span className="wgt-pd-price-was">{money(detail.price_original, detail.currency)}</span>}
                                {!detail.available && <span className="wgt-pd-badge">{t('shop.out_of_stock')}</span>}
                            </div>
                            {detail.description && (
                                <details className="wgt-pd-desc">
                                    <summary>{t('shop.description')}</summary>
                                    <p>{detail.description}</p>
                                </details>
                            )}
                            <div className="wgt-pd-actions">
                                {canAdd ? (
                                    <button type="button" className={`wgt-pd-primary is-${addState}`} style={addState === 'added' ? undefined : { background: primaryColor }}
                                        onClick={handleAdd} disabled={addState === 'adding'} aria-live="polite">
                                        {addState === 'adding' ? t('shop.adding') : addState === 'added' ? `✓ ${t('shop.added')}` : t('shop.add_to_cart')}
                                    </button>
                                ) : detail.has_combinations ? (
                                    <a className="wgt-pd-primary" style={{ background: primaryColor }} href={detail.url}>{t('shop.choose_options')}</a>
                                ) : null}
                                {(addState === 'added' || cartCount > 0) && shop?.checkout_url && (
                                    <a className="wgt-pd-secondary" href={shop.checkout_url}>{t('shop.checkout')}</a>
                                )}
                            </div>
                            {addState === 'error' && <p className="wgt-shop-error" role="alert">{t('shop.add_error')}</p>}
                            <div className="wgt-pd-links">
                                <a href={detail.url}>{t('shop.view_on_page')}</a>
                                <button type="button" onClick={() => navigate('/conversation')}>{t('shop.ask_about')}</button>
                            </div>
                        </article>
                    )}
                </div>
            </div>
        );
    }

    // ── Listas: vistos / cesta ────────────────────────────────────────
    return (
        <div className="wgt-shop">
            <header className="wgt-screen-header wgt-shop-header">
                <span className="wgt-screen-header-title">{t('shop.products_tab')}</span>
            </header>
            <div className="wgt-shop-tabs" role="tablist">
                {tabs.map(tb => (
                    <button key={tb.key} type="button" role="tab" aria-selected={tab === tb.key}
                        className={`wgt-shop-tab${tab === tb.key ? ' is-active' : ''}`}
                        style={tab === tb.key ? { color: primaryColor, borderColor: primaryColor } : undefined}
                        onClick={() => setTab(tb.key)}>
                        {tb.label}
                        {tb.badge ? <span className="wgt-shop-tab-badge">{tb.badge}</span> : null}
                    </button>
                ))}
            </div>
            <div className="wgt-shop-body">
                {tab === 'viewed' ? (
                    viewed.length === 0 ? (
                        <EmptyState icon="eye" title={t('shop.empty_viewed')} sub={t('shop.empty_viewed_sub')} />
                    ) : (
                        <ul className="wgt-shop-list">
                            {viewed.map(p => (
                                <li key={p.id}>
                                    <button type="button" className="wgt-shop-row" onClick={() => setDetailId(p.id)}>
                                        <Thumb src={p.image_url} alt="" className="wgt-shop-thumb" />
                                        <span className="wgt-shop-row-body">
                                            <span className="wgt-shop-row-title">{p.title || `#${p.id}`}</span>
                                            {typeof p.price === 'number' && <span className="wgt-shop-row-meta">{money(p.price, p.currency)}</span>}
                                        </span>
                                        <svg className="wgt-shop-chevron" viewBox="0 0 24 24" width={18} height={18} fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )
                ) : cartLines.length === 0 ? (
                    <EmptyState icon="cart" title={t('shop.empty_cart')} sub={t('shop.empty_cart_sub')} />
                ) : (
                    <>
                        <ul className="wgt-shop-list">
                            {cartLines.map(line => (
                                <li key={`${line.id_product}-${line.id_product_attribute}`}>
                                    <button type="button" className="wgt-shop-row" onClick={() => setDetailId(String(line.id_product))}>
                                        <Thumb src={line.image_url} alt="" className="wgt-shop-thumb" />
                                        <span className="wgt-shop-row-body">
                                            <span className="wgt-shop-row-title">{line.name || `#${line.id_product}`}</span>
                                            <span className="wgt-shop-row-meta">
                                                {line.qty} {t('shop.units')}{line.attributes ? ` · ${line.attributes}` : ''}
                                            </span>
                                        </span>
                                        <span className="wgt-shop-row-price">{money(line.total ?? line.price, cart?.currency)}</span>
                                    </button>
                                </li>
                            ))}
                        </ul>
                        <div className="wgt-shop-summary">
                            {typeof cart?.total_products === 'number' && typeof cart?.total === 'number' && cart.total - cart.total_products > 0.005 && (
                                <>
                                    <div className="wgt-shop-line">
                                        <span>{t('shop.subtotal')}</span>
                                        <span>{money(cart.total_products, cart.currency)}</span>
                                    </div>
                                    <div className="wgt-shop-line">
                                        <span>{t('shop.shipping')}</span>
                                        <span>{money(cart.total - cart.total_products, cart.currency)}</span>
                                    </div>
                                </>
                            )}
                            <div className="wgt-shop-total">
                                <span>{t('shop.total')}</span>
                                <strong>{money(cart?.total, cart?.currency)}</strong>
                            </div>
                            {shop?.checkout_url && (
                                <a className="wgt-pd-primary" style={{ background: primaryColor }} href={shop.checkout_url}>{t('shop.checkout')}</a>
                            )}
                            {shop?.cart_url && <a className="wgt-pd-secondary" href={shop.cart_url}>{t('shop.go_cart')}</a>}
                        </div>
                    </>
                )}
            </div>
        </div>
    );
}
