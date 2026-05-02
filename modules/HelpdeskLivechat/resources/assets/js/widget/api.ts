/**
 * Build an absolute API URL for the widget.
 *
 * When the widget is embedded on a third-party site (e.g. PrestaShop),
 * window.location.origin is the *host* site, not the Laravel backend.
 * All fetch() calls must therefore be rooted at the backend baseUrl
 * injected via window.HELPDESK_WIDGET_CONFIG.
 */
export function apiUrl(path: string): string {
    const base =
        (window as any).HELPDESK_WIDGET_CONFIG?.baseUrl
        ?? (window as any).HELPDESK_WIDGET_CONFIG?.apiUrl
        ?? '';

    const cleanBase = base.replace(/\/$/, '');
    const cleanPath = path.replace(/^\//, '');

    return cleanBase ? `${cleanBase}/${cleanPath}` : `/${cleanPath}`;
}
