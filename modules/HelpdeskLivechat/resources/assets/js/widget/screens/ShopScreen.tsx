import React, { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useWidgetStore } from '../widget-store';
import { useTranslation } from '../i18n/useLanguage';
import {
    addToCart,
    applyCartVoucher,
    canAddToCart,
    CartLine,
    CartSnapshot,
    findVariantOption,
    getCart,
    getProductVariants,
    getShop,
    getViewedProducts,
    ProductVariants,
    refreshCart,
    removeCartLine,
    removeCartVoucher,
    updateCartQuantity,
    VariantGroup,
    VariantOption,
    ViewedProduct,
} from '../widget-commerce';
import { touchChatSession } from '../widget-attribution';
import { VariantPicker } from '../components/VariantPicker';

/**
 * Panel "Productos" del visitante (equivalente al coviewer de Oct8ne):
 * productos vistos, su cesta (editable, en sincronía con el minicarrito de la
 * tienda) y la ficha de un producto con Añadir / Pagar. Datos de la propia
 * tienda (mismo origen): precio, stock y cesta los decide siempre PrestaShop.
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
    groups?: VariantGroup[];
    options?: VariantOption[];
}

type Tab = 'viewed' | 'cart';

/** Segundos que dura el "Deshacer" tras quitar una línea de la cesta. */
const UNDO_REMOVE_MS = 5000;

function money(value: number | null | undefined, currency?: string | null): string {
    if (typeof value !== 'number') return '';
    try {
        return new Intl.NumberFormat(document.documentElement.lang || 'es', { style: 'currency', currency: currency || 'EUR' }).format(value);
    } catch {
        return value.toFixed(2);
    }
}

function lineKey(line: Pick<CartLine, 'id_product' | 'id_product_attribute'>): string {
    return `${line.id_product}-${line.id_product_attribute}`;
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

function TrashIcon() {
    return (
        <svg viewBox="0 0 24 24" width={17} height={17} fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            <path d="M4 7h16M9 7V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V7m2 0-.7 12.1A2 2 0 0 1 14.3 21H9.7a2 2 0 0 1-2-1.9L7 7" />
        </svg>
    );
}

interface CartLineRowProps {
    line: CartLine;
    currency?: string | null;
    primaryColor: string;
    busy: boolean;
    error?: string | null;
    hasCombinations: boolean;
    variantEditing: boolean;
    variantLoading: boolean;
    variantData: ProductVariants | null;
    variantSelection: Record<string, string>;
    t: (key: string, vars?: Record<string, string>) => string;
    onOpenDetail: () => void;
    onQtyDelta: (delta: number) => void;
    onRemove: () => void;
    onToggleVariantEdit: () => void;
    onSelectVariant: (group: string, value: string) => void;
    onConfirmVariant: () => void;
    onCancelVariantEdit: () => void;
}

function CartLineRow({
    line, currency, primaryColor, busy, error, hasCombinations,
    variantEditing, variantLoading, variantData, variantSelection, t,
    onOpenDetail, onQtyDelta, onRemove, onToggleVariantEdit, onSelectVariant, onConfirmVariant, onCancelVariantEdit,
}: CartLineRowProps) {
    const atMin = line.qty <= 1;
    const atMax = typeof line.max_qty === 'number' && line.qty >= line.max_qty;
    const selectedMatch = variantData ? findVariantOption(variantData, variantSelection) : null;
    const title = line.name || `#${line.id_product}`;

    return (
        <li className={`wgt-cart-line${busy ? ' is-busy' : ''}`}>
            <div className="wgt-cart-line-row">
                <button type="button" className="wgt-cart-line-thumb" onClick={onOpenDetail} aria-label={title}>
                    <Thumb src={line.image_url} alt="" className="wgt-shop-thumb" />
                </button>
                <div className="wgt-cart-line-body">
                    <button type="button" className="wgt-cart-line-title" onClick={onOpenDetail}>{title}</button>
                    {line.attributes && <span className="wgt-cart-line-attrs">{line.attributes}</span>}
                    {hasCombinations && (
                        <button type="button" className="wgt-cart-line-change" onClick={onToggleVariantEdit} disabled={busy}>
                            {t('shop.change_variant')}
                        </button>
                    )}
                </div>
                <span className="wgt-cart-line-price">{money(line.total ?? line.price, currency)}</span>
            </div>
            <div className="wgt-cart-line-row wgt-cart-line-actions">
                <div className="wgt-cart-stepper" role="group" aria-label={title}>
                    <button type="button" className="wgt-cart-step-btn" aria-label={t('shop.qty_decrease')} onClick={() => onQtyDelta(-1)} disabled={busy || atMin}>−</button>
                    <span className="wgt-cart-step-qty" aria-live="polite">{line.qty}</span>
                    <button type="button" className="wgt-cart-step-btn" aria-label={t('shop.qty_increase')} onClick={() => onQtyDelta(1)} disabled={busy || atMax}>+</button>
                </div>
                <button type="button" className="wgt-cart-remove-btn" aria-label={t('shop.remove_line')} onClick={onRemove} disabled={busy}>
                    <TrashIcon />
                </button>
            </div>
            {atMax && !busy && <p className="wgt-cart-line-hint">{line.max_qty === 1 ? t('shop.max_reached_one') : t('shop.max_reached', { count: String(line.max_qty) })}</p>}
            {error && <p className="wgt-cart-line-error" role="alert">{error}</p>}
            {variantEditing && (
                <div className="wgt-cart-variant-edit">
                    {variantLoading ? (
                        <p className="wgt-shop-loading">{t('shop.loading')}</p>
                    ) : variantData ? (
                        <>
                            <VariantPicker
                                groups={variantData.groups}
                                options={variantData.options}
                                selection={variantSelection}
                                onSelect={onSelectVariant}
                                primaryColor={primaryColor}
                            />
                            <div className="wgt-cart-variant-actions">
                                <button type="button" className="wgt-pd-primary" style={{ background: primaryColor }}
                                    onClick={onConfirmVariant} disabled={busy || !selectedMatch || !selectedMatch.available}>
                                    {t('shop.use_option')}
                                </button>
                                <button type="button" className="wgt-pd-secondary" onClick={onCancelVariantEdit}>{t('ui.close')}</button>
                            </div>
                        </>
                    ) : (
                        <p className="wgt-shop-error" role="alert">{t('shop.line_error')}</p>
                    )}
                </div>
            )}
        </li>
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
    const [variantSelection, setVariantSelection] = useState<Record<string, string>>({});

    // Cesta editable: estado por línea (cantidad/eliminar/cambiar opción).
    const [lineBusy, setLineBusy] = useState<Record<string, boolean>>({});
    const [lineError, setLineError] = useState<Record<string, string | null>>({});
    const [pendingRemoval, setPendingRemoval] = useState<CartLine | null>(null);
    const removalTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
    const pendingRemovalRef = useRef<CartLine | null>(null);
    const [variantEditKey, setVariantEditKey] = useState<string | null>(null);
    const [variantEditData, setVariantEditData] = useState<ProductVariants | null>(null);
    const [variantEditLoading, setVariantEditLoading] = useState(false);
    const [variantEditSelection, setVariantEditSelection] = useState<Record<string, string>>({});
    const [voucherOpen, setVoucherOpen] = useState(false);
    const [voucherCode, setVoucherCode] = useState('');
    const [voucherBusy, setVoucherBusy] = useState(false);
    const [voucherMessage, setVoucherMessage] = useState<{ ok: boolean; text: string } | null>(null);

    // La cesta puede cambiar en otra parte (tema, agente, otra pestaña).
    useEffect(() => {
        const sync = () => { setCart(getCart()); setViewed(getViewedProducts()); };
        refreshCart().finally(sync);
        window.addEventListener('helpdesk:cart-changed', sync);
        return () => window.removeEventListener('helpdesk:cart-changed', sync);
    }, []);

    useEffect(() => {
        pendingRemovalRef.current = pendingRemoval;
    }, [pendingRemoval]);

    // Si el widget se cierra con un "Deshacer" pendiente, se confirma la
    // eliminación (ya se le dijo al visitante que se había eliminado).
    useEffect(() => () => {
        if (removalTimer.current) {
            clearTimeout(removalTimer.current);
            const line = pendingRemovalRef.current;
            if (line) removeCartLine(line.id_product, line.id_product_attribute);
        }
    }, []);

    useEffect(() => {
        if (!detailId || !shop?.product_url) return;
        let cancelled = false;
        setLoading(true);
        setDetail(null);
        setImageIndex(0);
        setAddState('idle');
        setVariantSelection({});
        const url = new URL(shop.product_url, window.location.href);
        url.searchParams.set('id', detailId);
        fetch(url.toString(), { credentials: 'same-origin', headers: { Accept: 'application/json' }, cache: 'no-store' })
            .then(r => (r.ok ? r.json() : null))
            .then((json) => { if (!cancelled) setDetail(json?.product ?? null); })
            .catch(() => { if (!cancelled) setDetail(null); })
            .finally(() => { if (!cancelled) setLoading(false); });
        return () => { cancelled = true; };
    }, [detailId, shop?.product_url]);

    const allLines = cart?.lines ?? [];
    const cartLines = pendingRemoval ? allLines.filter(l => lineKey(l) !== lineKey(pendingRemoval)) : allLines;
    const cartCount = cart?.products_count ?? 0;

    const variantMatch = detail?.has_combinations && detail.groups?.length && detail.options?.length
        ? findVariantOption({ groups: detail.groups, options: detail.options }, variantSelection)
        : null;

    const handleAdd = async () => {
        if (!detail) return;
        const idProductAttribute = variantMatch ? variantMatch.id_product_attribute : detail.id_product_attribute ?? 0;
        setAddState('adding');
        const res = await addToCart(Number(detail.id), idProductAttribute, 1);
        setAddState(res.ok ? 'added' : 'error');
        if (res.ok) {
            touchChatSession('cart');
            setCart(getCart());
        }
    };

    // ── Cesta editable ──────────────────────────────────────────────
    const setBusy = (key: string, value: boolean) => setLineBusy(b => ({ ...b, [key]: value }));
    const setError = (key: string, message: string | null) => setLineError(e => ({ ...e, [key]: message }));

    const handleQtyDelta = async (line: CartLine, delta: number) => {
        const key = lineKey(line);
        if (lineBusy[key]) return;
        const qty = line.qty + delta;
        if (qty < 1 || (typeof line.max_qty === 'number' && qty > line.max_qty)) return;
        setBusy(key, true);
        setError(key, null);
        const res = await updateCartQuantity(line.id_product, line.id_product_attribute, qty);
        if (!res.ok) setError(key, res.message || t('shop.line_error'));
        setCart(getCart());
        setBusy(key, false);
    };

    const commitRemoval = async (line: CartLine) => {
        // pendingRemoval sigue ocultando la línea mientras la baja está en
        // vuelo, para que no reaparezca un instante antes de desaparecer de
        // verdad. Solo se limpia si sigue siendo la línea pendiente (otra
        // pudo empezar a eliminarse mientras tanto).
        removalTimer.current = null;
        await removeCartLine(line.id_product, line.id_product_attribute);
        setCart(getCart());
        if (pendingRemovalRef.current && lineKey(pendingRemovalRef.current) === lineKey(line)) {
            setPendingRemoval(null);
        }
    };

    const handleRemoveClick = (line: CartLine) => {
        if (removalTimer.current && pendingRemovalRef.current) {
            clearTimeout(removalTimer.current);
            commitRemoval(pendingRemovalRef.current);
        }
        setPendingRemoval(line);
        removalTimer.current = setTimeout(() => { commitRemoval(line); }, UNDO_REMOVE_MS);
    };

    const handleUndoRemoval = () => {
        if (removalTimer.current) {
            clearTimeout(removalTimer.current);
            removalTimer.current = null;
        }
        setPendingRemoval(null);
    };

    const handleToggleVariantEdit = async (line: CartLine) => {
        const key = lineKey(line);
        if (variantEditKey === key) {
            setVariantEditKey(null);
            return;
        }
        setVariantEditKey(key);
        setVariantEditData(null);
        setVariantEditSelection({});
        setVariantEditLoading(true);
        const data = await getProductVariants(line.id_product);
        setVariantEditLoading(false);
        setVariantEditData(data);
        if (data) {
            const current = data.options.find(o => o.id_product_attribute === line.id_product_attribute);
            setVariantEditSelection(current ? current.groups : {});
        }
    };

    const handleConfirmVariant = async (line: CartLine) => {
        if (!variantEditData) return;
        const match = findVariantOption(variantEditData, variantEditSelection);
        if (!match || match.id_product_attribute === line.id_product_attribute) {
            setVariantEditKey(null);
            return;
        }
        const key = lineKey(line);
        setBusy(key, true);
        setError(key, null);
        const addRes = await addToCart(line.id_product, match.id_product_attribute, line.qty);
        if (!addRes.ok) {
            setError(key, addRes.message || t('shop.line_error'));
            setBusy(key, false);
            return;
        }
        const removeRes = await removeCartLine(line.id_product, line.id_product_attribute);
        if (!removeRes.ok) setError(key, removeRes.message || t('shop.line_error'));
        setVariantEditKey(null);
        setCart(getCart());
        setBusy(key, false);
    };

    const handleApplyVoucher = async (e: React.FormEvent) => {
        e.preventDefault();
        const code = voucherCode.trim();
        if (!code || voucherBusy) return;
        setVoucherBusy(true);
        setVoucherMessage(null);
        const res = await applyCartVoucher(code);
        setVoucherBusy(false);
        if (res.ok) {
            setVoucherCode('');
            setCart(getCart());
        } else {
            // PrestaShop responde en el idioma de su contexto (a veces inglés):
            // mensaje propio traducido.
            setVoucherMessage({ ok: false, text: t('shop.voucher_invalid') });
        }
    };

    const handleRemoveVoucher = async (id: number) => {
        setVoucherBusy(true);
        await removeCartVoucher(id);
        setCart(getCart());
        setVoucherBusy(false);
    };

    const tabs = useMemo(() => ([
        { key: 'viewed' as Tab, label: t('shop.viewed'), badge: viewed.length || null },
        { key: 'cart' as Tab, label: t('shop.cart'), badge: cartCount || null },
    ]), [t, viewed.length, cartCount]);

    // ── Ficha de producto ─────────────────────────────────────────────
    if (detailId) {
        const images = detail?.images?.length ? detail.images : [];
        const showVariantPicker = !!detail?.has_combinations && !!detail.groups?.length && !!detail.options?.length;
        const canAdd = !!detail && canAddToCart() && (showVariantPicker ? !!variantMatch?.available : detail.available && !detail.has_combinations);
        const effectiveAvailable = variantMatch ? variantMatch.available : detail?.available;
        const mainImage = imageIndex === 0 && variantMatch?.image ? variantMatch.image : images[imageIndex];
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
                                <Thumb src={mainImage} alt={detail.title} className="wgt-pd-image" />
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
                                <span className="wgt-pd-price-now">{money(variantMatch ? variantMatch.price : detail.price, detail.currency)}</span>
                                {(variantMatch ? variantMatch.price_original : detail.price_original) != null && (
                                    <span className="wgt-pd-price-was">{money(variantMatch ? variantMatch.price_original : detail.price_original, detail.currency)}</span>
                                )}
                                {!effectiveAvailable && <span className="wgt-pd-badge">{t('shop.out_of_stock')}</span>}
                            </div>
                            {detail.description && (
                                <details className="wgt-pd-desc">
                                    <summary>{t('shop.description')}</summary>
                                    <p>{detail.description}</p>
                                </details>
                            )}
                            <div className="wgt-pd-actions">
                                {showVariantPicker && (
                                    <VariantPicker
                                        groups={detail.groups!}
                                        options={detail.options!}
                                        selection={variantSelection}
                                        onSelect={(group, value) => setVariantSelection((s) => ({ ...s, [group]: value }))}
                                        primaryColor={primaryColor}
                                    />
                                )}
                                {canAdd ? (
                                    <button type="button" className={`wgt-pd-primary is-${addState}`} style={addState === 'added' ? undefined : { background: primaryColor }}
                                        onClick={handleAdd} disabled={addState === 'adding'} aria-live="polite">
                                        {addState === 'adding' ? t('shop.adding') : addState === 'added' ? `✓ ${t('shop.added')}` : t('shop.add_to_cart')}
                                    </button>
                                ) : showVariantPicker ? (
                                    <p className="wgt-variant-hint">{variantMatch ? t('shop.out_of_stock') : t('shop.select_all_options')}</p>
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
                ) : cartLines.length === 0 && !pendingRemoval ? (
                    <EmptyState icon="cart" title={t('shop.empty_cart')} sub={t('shop.empty_cart_sub')} />
                ) : (
                    <>
                        {cart?.free_shipping && (
                            <div className="wgt-cart-shipping" role="status">
                                {cart.free_shipping.remaining <= 0 ? (
                                    <p className="wgt-cart-shipping-done">✓ {t('shop.free_shipping_done')}</p>
                                ) : (
                                    <>
                                        <p className="wgt-cart-shipping-text">
                                            {t('shop.free_shipping_remaining', { amount: money(cart.free_shipping.remaining, cart.currency) })}
                                        </p>
                                        <div className="wgt-cart-shipping-bar">
                                            <div className="wgt-cart-shipping-fill" style={{
                                                width: `${Math.min(100, Math.round(((cart.free_shipping.threshold - cart.free_shipping.remaining) / cart.free_shipping.threshold) * 100))}%`,
                                                background: primaryColor,
                                            }} />
                                        </div>
                                    </>
                                )}
                            </div>
                        )}
                        <ul className="wgt-shop-list wgt-cart-lines">
                            {cartLines.map(line => {
                                const key = lineKey(line);
                                return (
                                    <CartLineRow
                                        key={key}
                                        line={line}
                                        currency={cart?.currency}
                                        primaryColor={primaryColor}
                                        busy={!!lineBusy[key]}
                                        error={lineError[key]}
                                        hasCombinations={!!line.attributes && line.id_product_attribute > 0}
                                        variantEditing={variantEditKey === key}
                                        variantLoading={variantEditLoading}
                                        variantData={variantEditData}
                                        variantSelection={variantEditSelection}
                                        t={t}
                                        onOpenDetail={() => setDetailId(String(line.id_product))}
                                        onQtyDelta={(delta) => handleQtyDelta(line, delta)}
                                        onRemove={() => handleRemoveClick(line)}
                                        onToggleVariantEdit={() => handleToggleVariantEdit(line)}
                                        onSelectVariant={(group, value) => setVariantEditSelection(s => ({ ...s, [group]: value }))}
                                        onConfirmVariant={() => handleConfirmVariant(line)}
                                        onCancelVariantEdit={() => setVariantEditKey(null)}
                                    />
                                );
                            })}
                        </ul>
                        <details className="wgt-cart-voucher" open={voucherOpen} onToggle={(e) => setVoucherOpen(e.currentTarget.open)}>
                            <summary>{t('shop.voucher_title')}</summary>
                            {!!cart?.vouchers?.length && (
                                <ul className="wgt-cart-voucher-list">
                                    {cart.vouchers.map(v => (
                                        <li key={v.id}>
                                            <span className="wgt-cart-voucher-code">{v.code}</span>
                                            <span className="wgt-cart-voucher-amount">-{money(v.amount, cart.currency)}</span>
                                            <button type="button" onClick={() => handleRemoveVoucher(v.id)} disabled={voucherBusy} aria-label={`${t('shop.voucher_remove')} ${v.code}`}>
                                                {t('shop.voucher_remove')}
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            )}
                            <form className="wgt-cart-voucher-form" onSubmit={handleApplyVoucher}>
                                <label className="wgt-sr-only" htmlFor="wgt-voucher-code">{t('shop.voucher_placeholder')}</label>
                                <input id="wgt-voucher-code" type="text" value={voucherCode}
                                    onChange={(e) => setVoucherCode(e.target.value)}
                                    placeholder={t('shop.voucher_placeholder')} disabled={voucherBusy} />
                                <button type="submit" style={{ background: primaryColor }} disabled={voucherBusy || !voucherCode.trim()}>
                                    {voucherBusy ? t('shop.voucher_applying') : t('shop.voucher_apply')}
                                </button>
                            </form>
                            {voucherMessage && (
                                <p className={`wgt-cart-voucher-message${voucherMessage.ok ? ' is-ok' : ' is-error'}`} role="alert">
                                    {voucherMessage.text}
                                </p>
                            )}
                        </details>
                        <div className="wgt-shop-summary" aria-live="polite">
                            <div className="wgt-shop-line">
                                <span>{t('shop.subtotal')}</span>
                                <span>{money(cart?.total_products, cart?.currency)}</span>
                            </div>
                            {typeof cart?.total_discounts === 'number' && cart.total_discounts > 0.005 && (
                                <div className="wgt-shop-line wgt-cart-discount-line">
                                    <span>{t('shop.discounts')}</span>
                                    <span>-{money(cart.total_discounts, cart.currency)}</span>
                                </div>
                            )}
                            <div className="wgt-shop-line">
                                <span>{t('shop.shipping')}</span>
                                <span>{money(cart?.total_shipping, cart?.currency)}</span>
                            </div>
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
            {pendingRemoval && (
                <div className="wgt-cart-toast" role="status">
                    <span>{t('shop.line_removed')}</span>
                    <button type="button" onClick={handleUndoRemoval}>{t('shop.undo')}</button>
                </div>
            )}
        </div>
    );
}
