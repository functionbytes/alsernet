/**
 * HelpdeskContacts — Contact 360 tab-shell controller.
 *
 * Self-invoking IIFE, jQuery only. Lazy-loads each tab pane once on first show.
 * DOM contract (show.blade.php):
 *   - Root:  #contact360[data-customer-id][data-base-url][data-cart-base-url?]
 *   - Tabs:  button.nav-link[data-contact-tab="resumen|conversaciones|erp|prestashop|tienda|actividad|tickets|carrito"]
 *   - Panes: .tab-pane#pane-{tab}[data-loaded="0|1"]
 *   - Chats: #chats-section inside the conversaciones pane (loaded with conversaciones)
 *   - Sync:  #contact-sync-btn
 *   - Hero slots (filled from resumen): #contact-integrations, #contact-sentiment
 *
 * Endpoints (relative to data-base-url):
 *   GET  {base}/tab/{tab}   → { success, data, available? }
 *   POST {base}/sync        → { success, message, integrations }
 *   POST {base}/tickets     → { success, message, ticket }
 *
 * Assisted cart (shared helpdesk route, or data-cart-base-url override):
 *   GET    {cartBase}                    → cart state
 *   POST   {cartBase}/items              → add item { product_id, quantity }
 *   DELETE {cartBase}/items/{item}       → remove item
 *   POST   {cartBase}/discount           → apply { code }
 *   POST   {cartBase}/generate-order     → generate order
 *   POST   {cartBase}/send-payment-link  → send payment link
 *   cartBase defaults to /panel/helpdesk/customers/{id}/cart
 *
 * Bootstrap 5.3 + Font Awesome 6 only. No inline styles; toggle Bootstrap utility classes.
 */
(function ($) {
    'use strict';

    var $root = $('#contact360');
    if (!$root.length) {
        return;
    }

    var customerId = $root.data('customer-id');
    var baseUrl = String($root.data('base-url') || '').replace(/\/+$/, '');
    var csrf = $('meta[name="csrf-token"]').attr('content');

    // Assisted-cart base URL. The backend may expose a contacts proxy via
    // data-cart-base-url; otherwise we reuse the shared helpdesk manager route
    // (panel/helpdesk/customers/{id}/cart), derived from the page origin.
    var cartBaseUrl = String($root.data('cart-base-url') || '').replace(/\/+$/, '');
    if (!cartBaseUrl) {
        cartBaseUrl = window.location.origin + '/panel/helpdesk/customers/' + encodeURIComponent(customerId) + '/cart';
    }

    // Tabs that own a dedicated pane. 'chats' is rendered inside the conversaciones pane.
    var TABS = ['resumen', 'conversaciones', 'erp', 'prestashop', 'tienda', 'actividad', 'tickets', 'carrito'];

    // ─────────────────────────────────────────────────────────── helpers ──

    function esc(str) {
        if (str == null) {
            return '';
        }
        return String(str).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function notifyError(message) {
        if (window.toastr) {
            toastr.error(message);
        }
    }

    function notifySuccess(message) {
        if (window.toastr) {
            toastr.success(message);
        }
    }

    function faIcon(icon, fallback) {
        var value = String(icon || '').trim();
        if (/^(fas|far|fab|fa-solid|fa-regular|fa-brands)\s/.test(value)) {
            return value;
        }
        if (value && /^fa-/.test(value)) {
            return 'fas ' + value;
        }
        return 'fas fa-' + (fallback || 'circle');
    }

    function fmtDate(iso) {
        if (!iso) {
            return '';
        }
        var d = new Date(iso);
        if (isNaN(d.getTime())) {
            return String(iso);
        }
        return d.toLocaleString();
    }

    function money(amount, currency) {
        var value = parseFloat(amount);
        if (isNaN(value)) {
            value = 0;
        }
        var symbol = currency ? (' ' + esc(currency)) : ' €';
        return value.toFixed(2) + symbol;
    }

    function skeleton(rows) {
        var count = rows || 4;
        var html = '<div class="placeholder-glow">';
        for (var i = 0; i < count; i++) {
            html += '<p class="mb-2"><span class="placeholder col-' + (i % 2 ? 8 : 5) + ' me-2"></span>' +
                '<span class="placeholder col-' + (i % 2 ? 3 : 6) + '"></span></p>';
        }
        return html + '</div>';
    }

    function emptyState(icon, title, sub) {
        return '<div class="text-center text-muted py-5">' +
            '<i class="' + faIcon(icon, 'inbox') + ' fa-2x mb-3 d-block"></i>' +
            '<div class="fw-semibold">' + esc(title) + '</div>' +
            (sub ? '<div class="small">' + esc(sub) + '</div>' : '') +
            '</div>';
    }

    function errorState(retryTab) {
        var retry = retryTab
            ? '<button type="button" class="btn btn-sm btn-outline-secondary mt-3" data-contact-retry="' + esc(retryTab) + '">' +
                '<i class="fas fa-rotate-right me-1"></i>Reintentar</button>'
            : '';
        return '<div class="text-center text-muted py-5">' +
            '<i class="fas fa-triangle-exclamation fa-2x mb-3 d-block text-warning"></i>' +
            '<div class="fw-semibold">Error al cargar</div>' +
            '<div class="small">No se pudo obtener la informacion. Intentalo de nuevo.</div>' +
            retry +
            '</div>';
    }

    function spinnerLine(label) {
        return '<div class="d-flex align-items-center text-muted small py-3">' +
            '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' +
            esc(label || 'Cargando...') +
            '</div>';
    }

    function healthBadgeClass(score) {
        var value = parseInt(score, 10) || 0;
        if (value >= 75) {
            return 'bg-success';
        }
        if (value >= 50) {
            return 'bg-warning text-dark';
        }
        if (value >= 25) {
            return 'bg-warning text-dark';
        }
        return 'bg-danger';
    }

    // Normaliza una clase de color "bare" (success, danger, warning…) a una utility Bootstrap válida.
    function badgeClass(value, fallback) {
        var c = String(value || '').trim();
        if (!c) {
            return fallback || 'bg-secondary';
        }
        if (c.indexOf('bg-') === 0 || c.indexOf('text-bg-') === 0) {
            return c;
        }
        return 'bg-' + c;
    }

    function sentimentBadgeClass(label) {
        switch (String(label || '').toLowerCase()) {
            case 'positive':
                return 'bg-success';
            case 'negative':
                return 'bg-danger';
            default:
                return 'bg-secondary';
        }
    }

    function sentimentLabelText(label) {
        switch (String(label || '').toLowerCase()) {
            case 'positive':
                return 'Sentimiento positivo';
            case 'negative':
                return 'Sentimiento negativo';
            default:
                return 'Sentimiento neutro';
        }
    }

    function sentimentIcon(label) {
        switch (String(label || '').toLowerCase()) {
            case 'positive':
                return 'fa-face-smile';
            case 'negative':
                return 'fa-face-frown';
            default:
                return 'fa-face-meh';
        }
    }

    // Fills the hero-header slots (outside the resumen pane) from resumen payload.
    function fillResumenHero(data) {
        data = data || {};

        var $integrations = $('#contact-integrations');
        if ($integrations.length) {
            var integrations = Array.isArray(data.integrations) ? data.integrations : [];
            if (!integrations.length) {
                $integrations.html('<span class="text-muted small">Sin integraciones</span>');
            } else {
                var pills = '';
                integrations.forEach(function (it) {
                    var connected = !!it.connected;
                    var cls = connected ? 'bg-success' : 'bg-secondary';
                    var icon = connected ? 'fa-link' : 'fa-link-slash';
                    var tooltip = connected
                        ? (it.externalId ? ('ID externo: ' + it.externalId) : 'Conectado')
                        : 'Sin vincular';
                    pills += '<span class="badge ' + cls + ' d-inline-flex align-items-center" ' +
                        'title="' + esc(tooltip) + '">' +
                        '<i class="fas ' + icon + ' me-1"></i>' + esc(it.platform || '') +
                        (connected && it.externalId ? ' <span class="ms-1 opacity-75">#' + esc(it.externalId) + '</span>' : '') +
                        '</span>';
                });
                var anyDisconnected = integrations.some(function (it) { return !it.connected; });
                if (anyDisconnected) {
                    pills += '<button type="button" class="btn btn-sm btn-outline-secondary" data-contact-link-trigger>' +
                        '<i class="fas fa-link me-1"></i>Vincular</button>';
                }
                $integrations.html(pills);
            }
        }

        var $sentiment = $('#contact-sentiment');
        if ($sentiment.length) {
            var sentiment = data.sentiment || {};
            var label = sentiment.label || 'neutral';
            var counts = '';
            if (sentiment.positive != null || sentiment.negative != null) {
                counts = ' <span class="ms-1 opacity-75">+' + esc(sentiment.positive != null ? sentiment.positive : 0) +
                    ' / -' + esc(sentiment.negative != null ? sentiment.negative : 0) + '</span>';
            }
            $sentiment.html(
                '<span class="badge ' + sentimentBadgeClass(label) + ' d-inline-flex align-items-center" ' +
                'title="' + esc(sentimentLabelText(label)) + '">' +
                '<i class="fas ' + sentimentIcon(label) + ' me-1"></i>' + esc(sentimentLabelText(label)) + counts +
                '</span>'
            );
        }
    }

    // ──────────────────────────────────────────────────────── renderers ──

    function renderResumen(data) {
        data = data || {};
        var stats = data.stats || {};
        var lifetime = stats.lifetime || {};
        var location = data.location || {};
        var integrations = Array.isArray(data.integrations) ? data.integrations : [];
        var custom = data.customAttributes || {};

        var avatar = data.avatarUrl
            ? '<img src="' + esc(data.avatarUrl) + '" alt="' + esc(data.name || '') + '" ' +
                'class="rounded-circle me-3" width="64" height="64" loading="lazy">'
            : '<span class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center me-3 text-muted" ' +
                'data-avatar-fallback><i class="fas fa-user fa-lg"></i></span>';

        var flags = '';
        if (data.isVerified) {
            flags += '<span class="badge bg-success ms-2"><i class="fas fa-circle-check me-1"></i>Verificado</span>';
        }
        if (data.isBanned) {
            flags += '<span class="badge bg-danger ms-2" title="' + esc(data.banReason || '') + '">' +
                '<i class="fas fa-ban me-1"></i>Bloqueado</span>';
        }

        var html = '<div class="d-flex align-items-center mb-4">' + avatar +
            '<div>' +
                '<h5 class="mb-1">' + esc(data.name || 'Sin nombre') + flags + '</h5>' +
                '<div class="text-muted small">' + esc(data.email || '') + '</div>' +
            '</div></div>';

        // Hero stats row
        html += '<div class="row g-3 mb-4">';
        html += '<div class="col-6 col-md-3"><div class="border rounded p-3 h-100">' +
            '<div class="text-muted small mb-1">Health score</div>' +
            '<span class="badge ' + healthBadgeClass(stats.healthScore) + ' fs-6">' +
                esc(stats.healthScore != null ? stats.healthScore : '—') + '</span>' +
            '</div></div>';
        html += '<div class="col-6 col-md-3"><div class="border rounded p-3 h-100">' +
            '<div class="text-muted small mb-1">CSAT medio</div>' +
            '<div class="fs-5 fw-semibold">' + esc(stats.avgCsat != null ? stats.avgCsat : '—') + '</div>' +
            '</div></div>';
        html += '<div class="col-6 col-md-3"><div class="border rounded p-3 h-100">' +
            '<div class="text-muted small mb-1">Conversaciones</div>' +
            '<div class="fs-5 fw-semibold">' + esc(stats.totalConversations != null ? stats.totalConversations : 0) + '</div>' +
            '</div></div>';
        html += '<div class="col-6 col-md-3"><div class="border rounded p-3 h-100">' +
            '<div class="text-muted small mb-1">Gasto total</div>' +
            '<div class="fs-5 fw-semibold">' + esc(money(lifetime.totalSpent, lifetime.currency)) + '</div>' +
            '<div class="text-muted small">' + esc(lifetime.ordersCount != null ? lifetime.ordersCount : 0) + ' pedidos</div>' +
            '</div></div>';
        html += '</div>';

        // Contact info list
        var rows = [
            ['fa-envelope', 'Email', data.email],
            ['fa-phone', 'Telefono', data.phone],
            ['fa-brands fa-whatsapp', 'WhatsApp', data.whatsapp],
            ['fa-location-dot', 'Ubicacion', [location.city, location.state, location.country, location.postalCode].filter(Boolean).join(', ')],
            ['fa-language', 'Idioma', data.language],
            ['fa-clock', 'Zona horaria', data.timezone],
            ['fa-eye', 'Ultima actividad', data.lastSeenAt ? fmtDate(data.lastSeenAt) : '']
        ];
        html += '<h6 class="mb-3">Informacion de contacto</h6>';
        html += '<ul class="list-group list-group-flush mb-4">';
        rows.forEach(function (row) {
            if (!row[2]) {
                return;
            }
            html += '<li class="list-group-item d-flex justify-content-between align-items-center px-0">' +
                '<span class="text-muted"><i class="' + faIcon(row[0], 'circle') + ' me-2"></i>' + esc(row[1]) + '</span>' +
                '<span class="text-end">' + esc(row[2]) + '</span>' +
                '</li>';
        });
        html += '</ul>';

        // Integrations
        html += '<h6 class="mb-3">Integraciones</h6>';
        if (integrations.length) {
            html += '<div class="d-flex flex-wrap gap-2 mb-4">';
            integrations.forEach(function (it) {
                var cls = it.connected ? 'bg-success' : 'bg-secondary';
                var icon = it.connected ? 'fa-link' : 'fa-link-slash';
                html += '<span class="badge ' + cls + ' d-inline-flex align-items-center">' +
                    '<i class="fas ' + icon + ' me-1"></i>' + esc(it.platform || '') +
                    (it.externalId ? ' <span class="ms-1 opacity-75">#' + esc(it.externalId) + '</span>' : '') +
                    '</span>';
            });
            html += '</div>';
        } else {
            html += '<p class="text-muted small mb-4">Sin integraciones conectadas.</p>';
        }

        // Custom attributes
        var customKeys = Object.keys(custom);
        if (customKeys.length) {
            html += '<h6 class="mb-3">Atributos personalizados</h6>';
            html += '<ul class="list-group list-group-flush">';
            customKeys.forEach(function (key) {
                html += '<li class="list-group-item d-flex justify-content-between px-0">' +
                    '<span class="text-muted">' + esc(key) + '</span>' +
                    '<span class="text-end">' + esc(custom[key]) + '</span>' +
                    '</li>';
            });
            html += '</ul>';
        }

        // Notes
        if (data.internal_notes) {
            html += '<h6 class="mb-2 mt-4"><i class="fas fa-note-sticky me-1"></i> Notas internas</h6>' +
                '<p class="text-muted small mb-0">' + esc(data.internal_notes) + '</p>';
        }

        return html;
    }

    function renderConversaciones(data) {
        data = data || {};
        var conversations = Array.isArray(data.conversations) ? data.conversations : [];

        var html = '<h6 class="mb-3">Conversaciones</h6>';
        if (!conversations.length) {
            html += emptyState('fa-comments', 'Sin conversaciones', 'Este contacto no tiene conversaciones registradas.');
        } else {
            html += '<div class="list-group mb-4">';
            conversations.forEach(function (c) {
                var link = c.url || '#';
                html += '<a href="' + esc(link) + '" class="list-group-item list-group-item-action">' +
                    '<div class="d-flex justify-content-between align-items-start">' +
                        '<div class="me-2">' +
                            '<div class="fw-semibold">' +
                                '<i class="' + faIcon(c.channelIcon, 'comment') + ' me-2 text-muted"></i>' +
                                esc(c.subject || 'Sin asunto') +
                            '</div>' +
                            (c.preview ? '<div class="text-muted small text-truncate">' + esc(c.preview) + '</div>' : '') +
                        '</div>' +
                        '<div class="text-end flex-shrink-0">' +
                            '<span class="badge ' + esc(badgeClass(c.statusClass, 'bg-secondary')) + '">' + esc(c.statusLabel || '') + '</span>' +
                            (c.lastAt ? '<div class="text-muted small mt-1">' + esc(fmtDate(c.lastAt)) + '</div>' : '') +
                        '</div>' +
                    '</div>' +
                    '</a>';
            });
            html += '</div>';
        }

        // Sub-container for chats (filled by a separate request).
        html += '<div id="chats-section">' + skeleton(3) + '</div>';
        return html;
    }

    function chatMetaText(meta) {
        if (!meta || typeof meta !== 'object') {
            return esc(meta || '');
        }
        return Object.keys(meta)
            .filter(function (key) { return meta[key] != null && meta[key] !== ''; })
            .map(function (key) { return esc(meta[key]); })
            .join(' · ');
    }

    function renderChats(data) {
        data = data || {};
        var available = data.available !== false;
        var chats = Array.isArray(data.chats) ? data.chats : [];

        var html = '<h6 class="mb-3 mt-2">Chats</h6>';
        if (!available) {
            return html + emptyState('fa-comment-slash', 'Chats no disponibles', 'El modulo de chats esta desactivado.');
        }
        if (!chats.length) {
            return html + emptyState('fa-comment-dots', 'Sin chats', 'No hay chats de livechat ni redes sociales.');
        }

        html += '<div class="list-group">';
        chats.forEach(function (chat) {
            var metaText = chatMetaText(chat.meta);
            var inner = '<div class="d-flex justify-content-between align-items-start">' +
                '<div class="me-2">' +
                    '<div class="fw-semibold text-capitalize">' +
                        '<i class="' + faIcon(chat.icon, 'comment') + ' me-2"></i>' + esc(chat.source || 'chat') +
                    '</div>' +
                    (chat.preview ? '<div class="text-muted small text-truncate">' + esc(chat.preview) + '</div>' : '') +
                    (metaText ? '<div class="text-muted small">' + metaText + '</div>' : '') +
                '</div>' +
                (chat.at ? '<div class="text-muted small text-end flex-shrink-0">' + esc(fmtDate(chat.at)) + '</div>' : '') +
            '</div>';
            if (chat.url) {
                html += '<a href="' + esc(chat.url) + '" class="list-group-item list-group-item-action">' + inner + '</a>';
            } else {
                html += '<div class="list-group-item">' + inner + '</div>';
            }
        });
        html += '</div>';
        return html;
    }

    // ERP numeric status label map (mirrors helpdesk conversations.js).
    var ERP_STATUS_LABEL = { 0: 'Pendiente', 1: 'Confirmado', 2: 'En preparación', 3: 'Enviado', 5: 'Entregado', 7: 'Servido', 9: 'Cancelado' };

    function erpStatusLabel(status) {
        if (typeof status === 'number') {
            return ERP_STATUS_LABEL[status] || ('Estado ' + status);
        }
        return status || 'Pedido';
    }

    function orderStatusPill(status) {
        var cls = (typeof window.bvOrderStatusClass === 'function')
            ? window.bvOrderStatusClass(String(status))
            : '';
        var bs = 'bg-secondary';
        if (cls === 'is-completed') {
            bs = 'bg-success';
        } else if (cls === 'is-shipped') {
            bs = 'bg-info text-dark';
        } else if (cls === 'is-cancelled') {
            bs = 'bg-danger';
        } else if (cls === 'is-pending') {
            bs = 'bg-warning text-dark';
        }
        return '<span class="badge ' + bs + '">' + esc(status) + '</span>';
    }

    function renderErp(data) {
        data = data || {};
        if (data.available === false) {
            return emptyState('fa-database', 'ERP no disponible', 'El modulo ERP esta desactivado o sin conexion.');
        }

        var cust = data.customer || {};
        var orders = Array.isArray(data.orders) ? data.orders : [];
        var invoices = Array.isArray(data.invoices) ? data.invoices : [];

        var html = '';

        // Customer summary
        if (cust && (cust.found || cust.name || cust.id)) {
            html += '<h6 class="mb-3">Cliente ERP</h6>';
            html += '<ul class="list-group list-group-flush mb-4">';
            if (cust.name) {
                html += '<li class="list-group-item d-flex justify-content-between px-0"><span class="text-muted">Nombre</span><span>' + esc(cust.name) + '</span></li>';
            }
            if (cust.id != null) {
                html += '<li class="list-group-item d-flex justify-content-between px-0"><span class="text-muted">ID</span><span>#' + esc(cust.id) + '</span></li>';
            }
            if (cust.balance != null) {
                html += '<li class="list-group-item d-flex justify-content-between px-0"><span class="text-muted">Saldo</span><span>' + esc(money(cust.balance)) + '</span></li>';
            }
            if (cust.credit_limit != null) {
                html += '<li class="list-group-item d-flex justify-content-between px-0"><span class="text-muted">Limite credito</span><span>' + esc(money(cust.credit_limit)) + '</span></li>';
            }
            html += '</ul>';
        }

        // Orders (with loading state handling)
        html += '<h6 class="mb-3">Pedidos ERP' + (orders.length ? ' <span class="text-muted">· ' + orders.length + '</span>' : '') + '</h6>';
        if (data.orders_loading) {
            html += '<div data-erp-orders-pending>' + spinnerLine('Cargando pedidos del ERP...') + '</div>';
        } else if (!orders.length) {
            html += emptyState('fa-clipboard-list', 'Sin pedidos ERP', 'No hay pedidos en gestion.');
        } else {
            html += '<div class="list-group mb-4">';
            orders.slice(0, 20).forEach(function (o) {
                var ref = o.number ? ('#' + o.number) : (o.id ? ('#' + o.id) : '—');
                var label = erpStatusLabel(o.status);
                var date = o.date ? String(o.date).substring(0, 10) : '';
                html += '<div class="list-group-item">' +
                    '<div class="d-flex justify-content-between align-items-center">' +
                        '<span class="fw-semibold"><i class="fas fa-clipboard-list me-2"></i>' + esc(ref) + '</span>' +
                        orderStatusPill(label) +
                    '</div>' +
                    (date ? '<div class="text-muted small mt-1">' + esc(date) + '</div>' : '') +
                    '</div>';
            });
            html += '</div>';
        }

        // Invoices
        if (invoices.length) {
            html += '<h6 class="mb-3">Facturas <span class="text-muted">· ' + invoices.length + '</span></h6>';
            html += '<div class="list-group">';
            invoices.slice(0, 15).forEach(function (inv) {
                var ref = inv.number ? ('#' + inv.number) : (inv.id ? ('#' + inv.id) : '—');
                var date = inv.date ? String(inv.date).substring(0, 10) : '';
                html += '<div class="list-group-item">' +
                    '<div class="d-flex justify-content-between align-items-center">' +
                        '<span class="fw-semibold"><i class="fas fa-file-invoice me-2"></i>' + esc(ref) + '</span>' +
                        (inv.status ? orderStatusPill(inv.status) : '') +
                    '</div>' +
                    (date ? '<div class="text-muted small mt-1">' + esc(date) +
                        (inv.payment_method ? ' · ' + esc(inv.payment_method) : '') + '</div>' : '') +
                    '</div>';
            });
            html += '</div>';
        }

        return html;
    }

    function renderPrestashop(data) {
        data = data || {};
        if (data.available === false) {
            return emptyState('fa-bag-shopping', 'PrestaShop no disponible', 'El modulo PrestaShop esta desactivado.');
        }

        var orders = Array.isArray(data.orders) ? data.orders : [];
        var carts = Array.isArray(data.carts) ? data.carts : [];
        var vouchers = Array.isArray(data.vouchers) ? data.vouchers : [];

        if (!orders.length && !carts.length && !vouchers.length) {
            return emptyState('fa-bag-shopping', 'Sin datos de PrestaShop', 'No hay pedidos, carritos ni cupones.');
        }

        var html = '';

        if (orders.length) {
            html += '<h6 class="mb-3">Pedidos PrestaShop <span class="text-muted">· ' + orders.length + '</span></h6>';
            html += '<div class="list-group mb-4">';
            orders.slice(0, 20).forEach(function (o) {
                var ref = o.reference || (o.number ? ('#' + o.number) : (o.id ? ('#' + o.id) : '—'));
                var date = o.date ? String(o.date).substring(0, 10) : '';
                html += '<div class="list-group-item">' +
                    '<div class="d-flex justify-content-between align-items-center">' +
                        '<span class="fw-semibold"><i class="fas fa-bag-shopping me-2"></i>' + esc(ref) + '</span>' +
                        (o.status ? orderStatusPill(o.status) : '') +
                    '</div>' +
                    '<div class="text-muted small mt-1">' + esc(date) +
                        (o.total != null ? ' · ' + esc(money(o.total, o.currency)) : '') + '</div>' +
                    '</div>';
            });
            html += '</div>';
        }

        if (carts.length) {
            html += '<h6 class="mb-3">Carritos <span class="text-muted">· ' + carts.length + '</span></h6>';
            html += '<div class="list-group mb-4">';
            carts.forEach(function (cart) {
                html += '<div class="list-group-item d-flex justify-content-between align-items-center">' +
                    '<span><i class="fas fa-cart-shopping me-2"></i>' +
                        esc((cart.items != null ? cart.items : 0)) + ' articulos</span>' +
                    '<span class="text-muted small">' + esc(cart.updatedAt ? fmtDate(cart.updatedAt) : '') + '</span>' +
                    '</div>';
            });
            html += '</div>';
        }

        if (vouchers.length) {
            html += '<h6 class="mb-3">Cupones <span class="text-muted">· ' + vouchers.length + '</span></h6>';
            html += '<div class="list-group">';
            vouchers.forEach(function (v) {
                html += '<div class="list-group-item d-flex justify-content-between align-items-center">' +
                    '<span><i class="fas fa-ticket me-2"></i>' + esc(v.code || v.name || 'Cupon') + '</span>' +
                    '<span class="text-muted small">' + esc(v.value != null ? v.value : '') + '</span>' +
                    '</div>';
            });
            html += '</div>';
        }

        return html;
    }

    function renderTienda(data) {
        data = data || {};
        if (data.available === false) {
            return emptyState('fa-store', 'Tienda local no disponible', 'El modulo Remarketing esta desactivado.');
        }

        var orders = Array.isArray(data.orders) ? data.orders : [];
        var carts = Array.isArray(data.carts) ? data.carts : [];
        var stats = data.stats || {};

        var html = '<div class="alert alert-light border d-flex align-items-center mb-4" role="alert">' +
            '<i class="fas fa-store me-2"></i>' +
            '<span class="small">Datos de la <strong>tienda local</strong> (espejo Remarketing por email).</span>' +
            '</div>';

        // Stats
        if (stats.ordersCount != null || stats.totalSpent != null) {
            html += '<div class="row g-3 mb-4">';
            html += '<div class="col-6"><div class="border rounded p-3 h-100">' +
                '<div class="text-muted small mb-1">Pedidos</div>' +
                '<div class="fs-5 fw-semibold">' + esc(stats.ordersCount != null ? stats.ordersCount : 0) + '</div>' +
                '</div></div>';
            html += '<div class="col-6"><div class="border rounded p-3 h-100">' +
                '<div class="text-muted small mb-1">Gasto total</div>' +
                '<div class="fs-5 fw-semibold">' + esc(money(stats.totalSpent)) + '</div>' +
                '</div></div>';
            html += '</div>';
        }

        if (!orders.length && !carts.length) {
            html += emptyState('fa-store', 'Sin actividad en tienda local', 'No hay pedidos ni carritos.');
            return html;
        }

        if (orders.length) {
            html += '<h6 class="mb-3">Pedidos tienda local <span class="text-muted">· ' + orders.length + '</span></h6>';
            html += '<div class="accordion mb-4" id="tiendaOrders">';
            orders.forEach(function (o, idx) {
                var heading = 'tiendaOrderHead' + idx;
                var collapse = 'tiendaOrderBody' + idx;
                var items = Array.isArray(o.items) ? o.items : [];
                var itemsHtml = '';
                if (items.length) {
                    itemsHtml += '<ul class="list-group list-group-flush">';
                    items.forEach(function (it) {
                        itemsHtml += '<li class="list-group-item d-flex justify-content-between px-0">' +
                            '<span>' + esc(it.name || 'Producto') + ' <span class="text-muted">×' + esc(it.qty != null ? it.qty : 1) + '</span></span>' +
                            '<span>' + esc(money(it.price, o.currency)) + '</span>' +
                            '</li>';
                    });
                    itemsHtml += '</ul>';
                } else {
                    itemsHtml = '<div class="text-muted small">Sin articulos</div>';
                }
                html += '<div class="accordion-item">' +
                    '<h2 class="accordion-header" id="' + heading + '">' +
                        '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" ' +
                            'data-bs-target="#' + collapse + '" aria-expanded="false" aria-controls="' + collapse + '">' +
                            '<span class="me-auto">' +
                                '<i class="fas fa-receipt me-2"></i>' + esc(o.number || '—') +
                            '</span>' +
                            (o.status ? '<span class="me-3">' + orderStatusPill(o.status) + '</span>' : '') +
                            '<span class="fw-semibold">' + esc(money(o.total, o.currency)) + '</span>' +
                        '</button>' +
                    '</h2>' +
                    '<div id="' + collapse + '" class="accordion-collapse collapse" aria-labelledby="' + heading + '" data-bs-parent="#tiendaOrders">' +
                        '<div class="accordion-body">' +
                            (o.placedAt ? '<div class="text-muted small mb-2">' + esc(fmtDate(o.placedAt)) + '</div>' : '') +
                            itemsHtml +
                        '</div>' +
                    '</div>' +
                    '</div>';
            });
            html += '</div>';
        }

        if (carts.length) {
            html += '<h6 class="mb-3">Carritos abandonados <span class="text-muted">· ' + carts.length + '</span></h6>';
            html += '<div class="list-group">';
            carts.forEach(function (cart) {
                var itemCount = cart.itemsCount != null ? cart.itemsCount : 0;
                var lineItems = Array.isArray(cart.lines) ? cart.lines : [];
                var recoverable = !!cart.recoverable && lineItems.length > 0;
                var recoverBtn = recoverable
                    ? '<button type="button" class="btn btn-sm btn-outline-primary ms-2" ' +
                        'data-cart-recover="' + esc(encodeURIComponent(JSON.stringify(lineItems))) + '">' +
                        '<i class="fas fa-rotate-left me-1"></i>Recuperar</button>'
                    : '';
                html += '<div class="list-group-item d-flex justify-content-between align-items-center">' +
                    '<span><i class="fas fa-cart-shopping me-2"></i>' +
                        esc(itemCount) + ' articulos</span>' +
                    '<span class="d-flex align-items-center">' +
                        '<span class="text-muted small me-2">' + esc(cart.updatedAt ? fmtDate(cart.updatedAt) : '') + '</span>' +
                        '<span class="fw-semibold">' + esc(money(cart.total)) + '</span>' +
                        recoverBtn +
                    '</span>' +
                    '</div>';
            });
            html += '</div>';
        }

        return html;
    }

    function renderActividad(data) {
        data = data || {};
        var timeline = Array.isArray(data.timeline) ? data.timeline : [];
        var csat = Array.isArray(data.csat) ? data.csat : [];
        var pageVisits = Array.isArray(data.pageVisits) ? data.pageVisits : [];
        var emails = Array.isArray(data.emails) ? data.emails : [];
        var tickets = Array.isArray(data.tickets) ? data.tickets : [];
        var company = data.company || null;

        var hasAny = timeline.length || csat.length || pageVisits.length || emails.length || tickets.length || company;
        if (!hasAny) {
            return emptyState('fa-timeline', 'Sin actividad', 'No hay eventos registrados para este contacto.');
        }

        var html = '';

        // Company rollup
        if (company) {
            html += '<div class="card mb-4"><div class="card-body">' +
                '<h6 class="card-title"><i class="fas fa-building me-2"></i>' + esc(company.name || 'Empresa') + '</h6>' +
                '<div class="row g-2 small text-muted">';
            if (company.domain) {
                html += '<div class="col-6 col-md-3"><span class="fw-semibold d-block text-body">Dominio</span>' + esc(company.domain) + '</div>';
            }
            if (company.industry) {
                html += '<div class="col-6 col-md-3"><span class="fw-semibold d-block text-body">Sector</span>' + esc(company.industry) + '</div>';
            }
            if (company.size) {
                html += '<div class="col-6 col-md-3"><span class="fw-semibold d-block text-body">Tamano</span>' + esc(company.size) + '</div>';
            }
            if (company.healthScore != null) {
                html += '<div class="col-6 col-md-3"><span class="fw-semibold d-block text-body">Health</span>' +
                    '<span class="badge ' + healthBadgeClass(company.healthScore) + '">' + esc(company.healthScore) + '</span></div>';
            }
            if (company.contactsCount != null) {
                html += '<div class="col-6 col-md-3"><span class="fw-semibold d-block text-body">Contactos</span>' + esc(company.contactsCount) + '</div>';
            }
            html += '</div></div></div>';
        }

        // Timeline feed
        if (timeline.length) {
            html += '<h6 class="mb-3">Actividad reciente</h6>';
            html += '<ul class="list-group list-group-flush mb-4">';
            timeline.forEach(function (ev) {
                html += '<li class="list-group-item d-flex px-0">' +
                    '<span class="me-3 text-muted"><i class="' + faIcon(ev.icon, 'circle-dot') + '"></i></span>' +
                    '<div class="flex-grow-1">' +
                        '<div class="fw-semibold">' + esc(ev.title || '') + '</div>' +
                        (ev.detail ? '<div class="text-muted small">' + esc(ev.detail) + '</div>' : '') +
                    '</div>' +
                    (ev.at ? '<span class="text-muted small text-end flex-shrink-0">' + esc(fmtDate(ev.at)) + '</span>' : '') +
                    '</li>';
            });
            html += '</ul>';
        }

        // Tickets
        if (tickets.length) {
            html += '<h6 class="mb-3">Tickets</h6>';
            html += '<div class="list-group mb-4">';
            tickets.forEach(function (t) {
                var inner = '<div class="d-flex justify-content-between align-items-center">' +
                    '<span class="fw-semibold"><i class="fas fa-ticket me-2"></i>' + esc(t.number || '') + ' · ' + esc(t.subject || '') + '</span>' +
                    '<span class="text-muted small">' + esc(t.status || '') + (t.priority ? ' · ' + esc(t.priority) : '') + '</span>' +
                    '</div>';
                if (t.url) {
                    html += '<a href="' + esc(t.url) + '" class="list-group-item list-group-item-action">' + inner + '</a>';
                } else {
                    html += '<div class="list-group-item">' + inner + '</div>';
                }
            });
            html += '</div>';
        }

        // Emails
        if (emails.length) {
            html += '<h6 class="mb-3">Emails</h6>';
            html += '<div class="list-group mb-4">';
            emails.forEach(function (m) {
                var inner = '<div class="d-flex justify-content-between align-items-center">' +
                    '<span class="fw-semibold"><i class="fas fa-envelope me-2"></i>' + esc(m.subject || 'Sin asunto') + '</span>' +
                    '<span class="badge ' + esc(badgeClass(m.statusClass, 'bg-secondary')) + '">' + esc(m.status || '') + '</span>' +
                    '</div>' +
                    (m.at ? '<div class="text-muted small mt-1">' + esc(fmtDate(m.at)) + '</div>' : '');
                if (m.url) {
                    html += '<a href="' + esc(m.url) + '" class="list-group-item list-group-item-action">' + inner + '</a>';
                } else {
                    html += '<div class="list-group-item">' + inner + '</div>';
                }
            });
            html += '</div>';
        }

        // CSAT
        if (csat.length) {
            html += '<h6 class="mb-3">Valoraciones CSAT</h6>';
            html += '<div class="list-group mb-4">';
            csat.forEach(function (c) {
                html += '<div class="list-group-item">' +
                    '<div class="d-flex justify-content-between align-items-center">' +
                        '<span class="fw-semibold"><i class="fas fa-star me-2 text-warning"></i>' + esc(c.score != null ? c.score : '—') + '</span>' +
                        '<span class="text-muted small">' + esc(c.agent || '') + (c.at ? ' · ' + esc(fmtDate(c.at)) : '') + '</span>' +
                    '</div>' +
                    (c.comment ? '<div class="text-muted small mt-1">' + esc(c.comment) + '</div>' : '') +
                    '</div>';
            });
            html += '</div>';
        }

        // Web navigation (page visits with device/browser context)
        if (pageVisits.length) {
            html += '<h6 class="mb-3"><i class="fas fa-compass me-2"></i>Navegación</h6>';
            html += '<div class="list-group">';
            pageVisits.forEach(function (v) {
                var deviceBits = [v.device, v.browser, v.platform, v.os]
                    .filter(function (x) { return x != null && x !== ''; })
                    .map(function (x) { return esc(x); })
                    .join(' · ');
                var inner = '<div class="d-flex justify-content-between align-items-start">' +
                    '<div class="me-2 text-truncate">' +
                        '<div class="text-truncate"><i class="fas fa-link me-2"></i>' + esc(v.title || v.url || '') + '</div>' +
                        (v.url && (v.title) ? '<div class="text-muted small text-truncate">' + esc(v.url) + '</div>' : '') +
                        (deviceBits ? '<div class="text-muted small"><i class="fas fa-display me-1"></i>' + deviceBits + '</div>' : '') +
                    '</div>' +
                    '<span class="text-muted small text-end flex-shrink-0">' +
                        (v.timeSpent != null ? esc(v.timeSpent) + 's' : '') +
                        (v.at ? ' · ' + esc(fmtDate(v.at)) : '') +
                    '</span>' +
                    '</div>';
                if (v.url) {
                    html += '<a href="' + esc(v.url) + '" class="list-group-item list-group-item-action">' + inner + '</a>';
                } else {
                    html += '<div class="list-group-item">' + inner + '</div>';
                }
            });
            html += '</div>';
        }

        return html;
    }

    // ──────────────────────────────────────────────────────── tickets ──

    function renderTickets(data) {
        data = data || {};
        if (data.available === false) {
            return emptyState('fa-ticket', 'Tickets no disponibles', 'El modulo de tickets esta desactivado.');
        }

        var tickets = Array.isArray(data.tickets) ? data.tickets : [];
        var categories = Array.isArray(data.categories) ? data.categories : [];

        var html = '<div class="d-flex justify-content-between align-items-center mb-3">' +
            '<h6 class="mb-0">Tickets' + (tickets.length ? ' <span class="text-muted">· ' + tickets.length + '</span>' : '') + '</h6>' +
            '<button type="button" class="btn btn-sm btn-primary" data-ticket-toggle-form>' +
                '<i class="fas fa-plus me-1"></i>Crear ticket</button>' +
            '</div>';

        // Inline create form (hidden by default).
        var categoryOptions = '<option value="">Sin categoria</option>';
        categories.forEach(function (cat) {
            categoryOptions += '<option value="' + esc(cat.id) + '">' + esc(cat.name || '') + '</option>';
        });
        html += '<form id="ticket-create-form" class="border rounded p-3 mb-4 d-none">' +
            '<div class="mb-3">' +
                '<label class="form-label" for="ticket-subject">Asunto</label>' +
                '<input type="text" class="form-control" id="ticket-subject" name="subject" required>' +
            '</div>' +
            '<div class="mb-3">' +
                '<label class="form-label" for="ticket-category">Categoria</label>' +
                '<select class="form-select" id="ticket-category" name="category_id">' + categoryOptions + '</select>' +
            '</div>' +
            '<div class="mb-3">' +
                '<label class="form-label" for="ticket-message">Mensaje</label>' +
                '<textarea class="form-control" id="ticket-message" name="message" rows="4" required></textarea>' +
            '</div>' +
            '<div class="d-flex gap-2">' +
                '<button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i>Crear</button>' +
                '<button type="button" class="btn btn-outline-secondary" data-ticket-cancel-form>Cancelar</button>' +
            '</div>' +
            '</form>';

        if (!tickets.length) {
            html += emptyState('fa-ticket', 'Sin tickets', 'Este contacto no tiene tickets registrados.');
            return html;
        }

        html += '<div class="list-group">';
        tickets.forEach(function (t) {
            var inner = '<div class="d-flex justify-content-between align-items-start">' +
                '<div class="me-2">' +
                    '<div class="fw-semibold"><i class="fas fa-ticket me-2 text-muted"></i>' +
                        (t.number ? esc(t.number) + ' · ' : '') + esc(t.subject || 'Sin asunto') +
                    '</div>' +
                    (t.createdAt ? '<div class="text-muted small">' + esc(fmtDate(t.createdAt)) + '</div>' : '') +
                '</div>' +
                '<div class="text-end flex-shrink-0 d-flex flex-column align-items-end gap-1">' +
                    '<span class="badge ' + esc(badgeClass(t.statusClass, 'bg-secondary')) + '">' + esc(t.status || '') + '</span>' +
                    (t.priority ? '<span class="badge ' + esc(badgeClass(t.priorityClass, 'bg-secondary')) + '">' + esc(t.priority) + '</span>' : '') +
                    (t.slaBadge ? '<span class="badge bg-light text-dark border">' + esc(t.slaBadge) + '</span>' : '') +
                '</div>' +
            '</div>';
            if (t.url) {
                html += '<a href="' + esc(t.url) + '" class="list-group-item list-group-item-action">' + inner + '</a>';
            } else {
                html += '<div class="list-group-item">' + inner + '</div>';
            }
        });
        html += '</div>';

        return html;
    }

    // ──────────────────────────────────────────────────────── carrito ──

    function renderCartLines(cart) {
        cart = cart || {};
        var items = Array.isArray(cart.items) ? cart.items : [];
        if (!items.length) {
            return emptyState('fa-cart-shopping', 'Carrito vacio', 'Agrega productos por su ID para empezar.');
        }
        var html = '<ul class="list-group mb-3">';
        items.forEach(function (it) {
            html += '<li class="list-group-item d-flex justify-content-between align-items-center" data-cart-item="' + esc(it.id) + '">' +
                '<div class="me-2">' +
                    '<div class="fw-semibold">' + esc(it.name || ('Producto #' + (it.product_id != null ? it.product_id : ''))) + '</div>' +
                    '<div class="text-muted small">' + esc(money(it.unit_price, cart.currency)) + ' × ' + esc(it.quantity != null ? it.quantity : 1) + '</div>' +
                '</div>' +
                '<div class="d-flex align-items-center gap-2">' +
                    '<span class="fw-semibold">' + esc(money(it.line_total != null ? it.line_total : 0, cart.currency)) + '</span>' +
                    '<button type="button" class="btn btn-sm btn-outline-danger" data-cart-remove-item="' + esc(it.id) + '" ' +
                        'title="Quitar"><i class="fas fa-trash"></i></button>' +
                '</div>' +
            '</li>';
        });
        html += '</ul>';
        return html;
    }

    function renderCartTotals(cart) {
        cart = cart || {};
        var rows = [
            ['Subtotal', cart.subtotal],
            ['Descuento', cart.discount_amount],
            ['Envio', cart.shipping_amount],
            ['Total', cart.total]
        ];
        var html = '<ul class="list-group mb-3">';
        rows.forEach(function (row) {
            if (row[1] == null) {
                return;
            }
            var bold = row[0] === 'Total' ? ' fw-bold' : '';
            html += '<li class="list-group-item d-flex justify-content-between' + bold + '">' +
                '<span>' + esc(row[0]) + '</span>' +
                '<span>' + esc(money(row[1], cart.currency)) + '</span>' +
                '</li>';
        });
        html += '</ul>';
        if (cart.discount_code) {
            html += '<div class="text-muted small mb-2"><i class="fas fa-tag me-1"></i>Cupon: ' + esc(cart.discount_code) + '</div>';
        }
        return html;
    }

    function renderCarrito(cart) {
        cart = cart || {};
        if (cart.available === false) {
            return emptyState('fa-cart-shopping', 'Carrito no disponible', 'El carrito asistido esta desactivado.');
        }

        var html = '<div class="row g-4">';

        // Left: lines + add form.
        html += '<div class="col-12 col-lg-7">';
        html += '<h6 class="mb-3">Lineas del carrito</h6>';
        html += '<div id="cart-lines">' + renderCartLines(cart) + '</div>';
        html += '<form id="cart-add-form" class="border rounded p-3">' +
            '<div class="row g-2 align-items-end">' +
                '<div class="col-6">' +
                    '<label class="form-label" for="cart-product-id">ID producto</label>' +
                    '<input type="number" class="form-control" id="cart-product-id" name="product_id" min="1" required>' +
                '</div>' +
                '<div class="col-3">' +
                    '<label class="form-label" for="cart-quantity">Cantidad</label>' +
                    '<input type="number" class="form-control" id="cart-quantity" name="quantity" min="1" value="1" required>' +
                '</div>' +
                '<div class="col-3">' +
                    '<button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus me-1"></i>Anadir</button>' +
                '</div>' +
            '</div>' +
            '</form>';
        html += '</div>';

        // Right: totals + actions.
        html += '<div class="col-12 col-lg-5">';
        html += '<h6 class="mb-3">Totales</h6>';
        html += '<div id="cart-totals">' + renderCartTotals(cart) + '</div>';
        html += '<form id="cart-discount-form" class="mb-3">' +
            '<label class="form-label" for="cart-discount-code">Cupon de descuento</label>' +
            '<div class="input-group">' +
                '<input type="text" class="form-control" id="cart-discount-code" name="code" placeholder="Codigo">' +
                '<button type="submit" class="btn btn-outline-secondary"><i class="fas fa-tag me-1"></i>Aplicar</button>' +
            '</div>' +
            '</form>';
        html += '<div class="d-grid gap-2">' +
            '<button type="button" id="cart-generate-order" class="btn btn-success">' +
                '<i class="fas fa-receipt me-1"></i>Generar pedido</button>' +
            '<button type="button" id="cart-send-link" class="btn btn-outline-primary">' +
                '<i class="fas fa-paper-plane me-1"></i>Enviar link de pago</button>' +
        '</div>';
        html += '</div>';

        html += '</div>';
        return html;
    }

    var RENDERERS = {
        resumen: renderResumen,
        conversaciones: renderConversaciones,
        erp: renderErp,
        prestashop: renderPrestashop,
        tienda: renderTienda,
        actividad: renderActividad,
        tickets: renderTickets,
        carrito: renderCarrito
    };

    // ───────────────────────────────────────────────────────── loading ──

    function loadChats() {
        var $section = $('#chats-section');
        if (!$section.length) {
            return;
        }
        $.ajax({
            url: baseUrl + '/tab/chats',
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        }).done(function (resp) {
            var payload = (resp && resp.data) ? resp.data : {};
            $section.html(renderChats(payload));
        }).fail(function () {
            $section.html(errorState());
        });
    }

    function loadTab(tab, force) {
        var $pane = $('#pane-' + tab);
        if (!$pane.length) {
            return;
        }
        if (!force && String($pane.data('loaded')) === '1') {
            return;
        }
        if (force) {
            $pane.html(skeleton());
        }
        $pane.data('loaded', '1');

        // The assisted cart lives on a shared helpdesk route, not {base}/tab/carrito.
        if (tab === 'carrito') {
            loadCart(true);
            return;
        }

        $.ajax({
            url: baseUrl + '/tab/' + tab,
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        }).done(function (resp) {
            var payload = (resp && resp.data) ? resp.data : {};
            var renderer = RENDERERS[tab];
            $pane.html(renderer ? renderer(payload) : emptyState('fa-circle-info', 'Sin contenido', ''));

            if (tab === 'resumen') {
                fillResumenHero(payload);
                var stats = payload.stats || {};
                setTabBadge('conversaciones', stats.totalConversations);
                setTabBadge('tickets', stats.ticketsCount);
            }
            if (tab === 'conversaciones') {
                loadChats();
            }
            if (tab === 'erp' && payload && payload.orders_loading) {
                scheduleErpRetry();
            }
        }).fail(function () {
            $pane.data('loaded', '0');
            $pane.html(errorState(tab));
        });
    }

    // ──────────────────────────────────────────────────────────── cart ──

    // Extracts the cart payload from the assisted-cart endpoint envelope.
    // Accepts { success, data:{...} }, { cart:{...} } or a bare cart object.
    function unwrapCart(resp) {
        if (!resp || typeof resp !== 'object') {
            return {};
        }
        if (resp.data && typeof resp.data === 'object') {
            return resp.data.cart && typeof resp.data.cart === 'object' ? resp.data.cart : resp.data;
        }
        if (resp.cart && typeof resp.cart === 'object') {
            return resp.cart;
        }
        return resp;
    }

    function renderCartInto(cart) {
        var $pane = $('#pane-carrito');
        if ($pane.length) {
            $pane.html(renderCarrito(cart));
        }
    }

    function loadCart(full) {
        var $pane = $('#pane-carrito');
        if (!$pane.length) {
            return;
        }
        if (full) {
            $pane.html(skeleton());
        }
        $.ajax({
            url: cartBaseUrl,
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        }).done(function (resp) {
            renderCartInto(unwrapCart(resp));
        }).fail(function (xhr) {
            if (xhr.status === 403) {
                $pane.html(emptyState('fa-lock', 'Sin acceso al carrito', 'No tienes permiso para gestionar el carrito asistido.'));
                return;
            }
            $pane.data('loaded', '0');
            $pane.html(errorState('carrito'));
        });
    }

    // ERP orders_loading: retry once after 2.5s.
    var erpRetryDone = false;

    function scheduleErpRetry() {
        if (erpRetryDone) {
            return;
        }
        erpRetryDone = true;
        setTimeout(function () {
            $.ajax({
                url: baseUrl + '/tab/erp',
                method: 'GET',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            }).done(function (resp) {
                var payload = (resp && resp.data) ? resp.data : {};
                $('#pane-erp').html(renderErp(payload));
            }).fail(function () {
                $('#pane-erp [data-erp-orders-pending]').html(
                    emptyState('fa-clipboard-list', 'Sin pedidos ERP', 'No se pudieron cargar los pedidos.')
                );
            });
        }, 2500);
    }

    // ──────────────────────────────────────────────────────────── sync ──

    function runSync() {
        var $btn = $('#contact-sync-btn');
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Sincronizando...');

        $.ajax({
            url: baseUrl + '/sync',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        }).done(function (resp) {
            notifySuccess((resp && resp.message) || 'Sincronizacion completada');
            loadTab('resumen', true);
        }).fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                var errors = xhr.responseJSON.errors;
                Object.keys(errors).forEach(function (field) {
                    var list = errors[field];
                    notifyError(Array.isArray(list) ? list[0] : list);
                });
            } else {
                notifyError((xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo sincronizar el contacto');
            }
        }).always(function () {
            $btn.prop('disabled', false).html(originalHtml);
        });
    }

    // ──────────────────────────────────────────────────── write helper ──

    // Reports an AJAX failure: per-field 422 messages, otherwise the message body.
    function reportFailure(xhr, fallback) {
        if (xhr && xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
            var errors = xhr.responseJSON.errors;
            Object.keys(errors).forEach(function (field) {
                var list = errors[field];
                notifyError(Array.isArray(list) ? list[0] : list);
            });
            return;
        }
        notifyError((xhr && xhr.responseJSON && xhr.responseJSON.message) || fallback || 'Ocurrio un error');
    }

    // Fires a write request (POST/PATCH/DELETE) with CSRF + JSON headers.
    function writeRequest(method, url, data) {
        return $.ajax({
            url: url,
            method: method,
            data: data || {},
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        });
    }

    function activateTab(tab) {
        var $btn = $root.find('[data-contact-tab="' + tab + '"]').first();
        if ($btn.length && window.bootstrap && window.bootstrap.Tab) {
            window.bootstrap.Tab.getOrCreateInstance($btn[0]).show();
        } else if ($btn.length) {
            $btn.trigger('click');
        }
    }

    // ──────────────────────────────────────────────────── ticket actions ──

    function submitTicket($form) {
        var $btn = $form.find('button[type="submit"]');
        var original = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Creando...');

        writeRequest('POST', baseUrl + '/tickets', {
            subject: $form.find('[name="subject"]').val(),
            category_id: $form.find('[name="category_id"]').val(),
            message: $form.find('[name="message"]').val()
        }).done(function (resp) {
            notifySuccess((resp && resp.message) || 'Ticket creado');
            loadTab('tickets', true);
        }).fail(function (xhr) {
            reportFailure(xhr, 'No se pudo crear el ticket');
        }).always(function () {
            $btn.prop('disabled', false).html(original);
        });
    }

    // ────────────────────────────────────────────────────── cart actions ──

    function cartWrite(method, path, data, fallback) {
        return writeRequest(method, cartBaseUrl + (path || ''), data)
            .done(function (resp) {
                if (resp && resp.message) {
                    notifySuccess(resp.message);
                }
                renderCartInto(unwrapCart(resp));
            })
            .fail(function (xhr) {
                reportFailure(xhr, fallback || 'No se pudo actualizar el carrito');
            });
    }

    function generateOrder($btn) {
        var original = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Generando...');
        writeRequest('POST', cartBaseUrl + '/generate-order', {})
            .done(function (resp) {
                notifySuccess((resp && resp.message) || 'Pedido generado');
                loadCart(true);
            })
            .fail(function (xhr) {
                reportFailure(xhr, 'No se pudo generar el pedido');
            })
            .always(function () {
                $btn.prop('disabled', false).html(original);
            });
    }

    function sendPaymentLink($btn) {
        var original = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Enviando...');
        writeRequest('POST', cartBaseUrl + '/send-payment-link', {})
            .done(function (resp) {
                notifySuccess((resp && resp.message) || 'Link de pago enviado');
            })
            .fail(function (xhr) {
                reportFailure(xhr, 'No se pudo enviar el link de pago');
            })
            .always(function () {
                $btn.prop('disabled', false).html(original);
            });
    }

    // Adds a batch of {productId/product_id, qty/quantity} lines, then opens the cart.
    function recoverCart(lineItems) {
        if (!Array.isArray(lineItems) || !lineItems.length) {
            return;
        }
        var chain = $.Deferred().resolve();
        lineItems.forEach(function (li) {
            chain = chain.then(function () {
                var productId = li.product_id != null ? li.product_id : li.productId;
                var qty = li.qty != null ? li.qty : (li.quantity != null ? li.quantity : 1);
                return writeRequest('POST', cartBaseUrl + '/items', { product_id: productId, quantity: qty });
            });
        });
        chain.done(function () {
            notifySuccess('Carrito recuperado');
            $('#pane-carrito').data('loaded', '0');
            activateTab('carrito');
            loadCart(true);
        }).fail(function (xhr) {
            reportFailure(xhr, 'No se pudieron recuperar todos los articulos');
        });
    }

    // ─────────────────────────────────────────────── tab badge helpers ──

    function setTabBadge(tab, count) {
        if (count == null || count <= 0) {
            return;
        }
        var $btn = $root.find('[data-contact-tab="' + tab + '"]');
        if (!$btn.length || $btn.find('.contact-tab-badge').length) {
            return;
        }
        $btn.append(' <span class="badge bg-secondary contact-tab-badge">' + parseInt(count, 10) + '</span>');
    }

    // ─────────────────────────────────────────────── URL hash helpers ──

    function hashTab() {
        var h = (location.hash || '').replace(/^#/, '').trim();
        return (h && TABS.indexOf(h) !== -1) ? h : null;
    }

    function pushHash(tab) {
        if (history.replaceState) {
            history.replaceState(null, '', '#' + tab);
        }
    }

    // ───────────────────────────────────────────────────────── wiring ──

    $(function () {
        // Lazy-load + persist URL hash when Bootstrap activates a tab.
        // MutationObserver on the nav so we catch Bootstrap's own class toggle
        // regardless of whether the trigger is a click, keyboard, or JS call.
        var tabNav = $root.find('.contacts-tab-nav')[0] || $root.find('[role="tablist"]')[0];
        if (tabNav) {
            new MutationObserver(function (mutations) {
                mutations.forEach(function (m) {
                    if (m.type !== 'attributes' || m.attributeName !== 'class') { return; }
                    var btn = m.target;
                    if (!btn.dataset || !btn.dataset.contactTab) { return; }
                    if (!btn.classList.contains('active')) { return; }
                    var tab = btn.dataset.contactTab;
                    pushHash(tab);
                    loadTab(tab);
                });
            }).observe(tabNav, { subtree: true, attributes: true, attributeFilter: ['class'] });
        }

        // Retry button (delegated, inside panes).
        $root.on('click', '[data-contact-retry]', function () {
            loadTab($(this).data('contact-retry'), true);
        });

        // Sync button.
        $root.on('click', '#contact-sync-btn', function (e) {
            e.preventDefault();
            runSync();
        });

        // Hero "Vincular" trigger reuses the sync flow.
        $root.on('click', '[data-contact-link-trigger]', function (e) {
            e.preventDefault();
            $('#contact-sync-btn').trigger('click');
        });

        // Ticket: toggle / cancel inline form.
        $root.on('click', '[data-ticket-toggle-form]', function () {
            $('#ticket-create-form').removeClass('d-none').find('[name="subject"]').trigger('focus');
        });
        $root.on('click', '[data-ticket-cancel-form]', function () {
            $('#ticket-create-form').addClass('d-none')[0].reset();
        });
        $root.on('submit', '#ticket-create-form', function (e) {
            e.preventDefault();
            submitTicket($(this));
        });

        // Cart: add item.
        $root.on('submit', '#cart-add-form', function (e) {
            e.preventDefault();
            cartWrite('POST', '/items', {
                product_id: $(this).find('[name="product_id"]').val(),
                quantity: $(this).find('[name="quantity"]').val()
            }, 'No se pudo anadir el producto').done(function () {
                $('#cart-add-form')[0].reset();
                $('#cart-quantity').val(1);
            });
        });

        // Cart: remove item.
        $root.on('click', '[data-cart-remove-item]', function () {
            var itemId = $(this).data('cart-remove-item');
            cartWrite('DELETE', '/items/' + encodeURIComponent(itemId), {}, 'No se pudo quitar el producto');
        });

        // Cart: apply discount.
        $root.on('submit', '#cart-discount-form', function (e) {
            e.preventDefault();
            cartWrite('POST', '/discount', { code: $(this).find('[name="code"]').val() }, 'No se pudo aplicar el cupon');
        });

        // Cart: generate order / send link.
        $root.on('click', '#cart-generate-order', function () {
            generateOrder($(this));
        });
        $root.on('click', '#cart-send-link', function () {
            sendPaymentLink($(this));
        });

        // Tienda: recover an abandoned cart into the assisted cart.
        $root.on('click', '[data-cart-recover]', function () {
            var raw = $(this).data('cart-recover');
            var lineItems = [];
            try {
                lineItems = JSON.parse(decodeURIComponent(raw));
            } catch (err) {
                lineItems = [];
            }
            recoverCart(lineItems);
        });

        // Always load resumen to populate the hero header (integrations, sentiment, badges).
        loadTab('resumen');

        // If URL hash points to a different tab, switch to it after Bootstrap initialises.
        var fromHash = hashTab();
        if (fromHash && fromHash !== 'resumen') {
            setTimeout(function () { activateTab(fromHash); }, 50);
        }

        // ── Edit contact form ──────────────────────────────────────────
        $root.on('click', '#contact-edit-btn', function () {
            $('#contact-edit-modal').modal('show');
        });

        $('#contact-edit-modal').on('submit', '#contact-edit-form', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('[type="submit"]').prop('disabled', true).text('Guardando...');
            $.ajax({
                url: $root.data('update-url'),
                method: 'PUT',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                contentType: 'application/json',
                data: JSON.stringify({
                    name: $form.find('[name="name"]').val(),
                    email: $form.find('[name="email"]').val(),
                    phone: $form.find('[name="phone"]').val(),
                    language: $form.find('[name="language"]').val(),
                    timezone: $form.find('[name="timezone"]').val(),
                    internal_notes: $form.find('[name="internal_notes"]').val(),
                }),
            }).done(function () {
                toastr.success('Contacto actualizado');
                $('#contact-edit-modal').modal('hide');
                var newName = $form.find('[name="name"]').val();
                $root.find('.contact-hero-name').text(newName);
            }).fail(function (xhr) {
                var errors = (xhr.responseJSON && xhr.responseJSON.errors) ? xhr.responseJSON.errors : {};
                var first = (Object.values(errors)[0] || [])[0] || 'Error al guardar';
                toastr.error(first);
            }).always(function () {
                $btn.prop('disabled', false).text('Guardar cambios');
            });
        });

        // ── Merge contacts ─────────────────────────────────────────────
        var $mergeModal = $('#contact-merge-modal');
        var selectedLoserId = null;

        $root.on('click', '#contact-merge-btn', function () {
            selectedLoserId = null;
            $('#merge-search-input').val('');
            $('#merge-search-results').empty();
            $('#merge-preview').addClass('d-none');
            $('#merge-footer').addClass('d-none');
            $mergeModal.modal('show');
        });

        var mergeSearchTimer;
        $mergeModal.on('input', '#merge-search-input', function () {
            clearTimeout(mergeSearchTimer);
            var q = $(this).val().trim();
            if (q.length < 2) {
                $('#merge-search-results').empty();
                return;
            }
            mergeSearchTimer = setTimeout(function () {
                $.get($root.data('merge-search-url'), { q: q, exclude_id: customerId })
                    .done(function (resp) {
                        var items = Array.isArray(resp) ? resp : (resp.data || []);
                        var html = '';
                        items.forEach(function (c) {
                            html += '<div class="d-flex justify-content-between align-items-center border-bottom py-2">'
                                + '<div><div class="fw-semibold">' + esc(c.name || '—') + '</div>'
                                + '<div class="small text-muted">' + esc(c.email || '—') + ' · ' + (c.total_conversations || 0) + ' conv.</div></div>'
                                + '<button class="btn btn-sm btn-outline-primary merge-select-btn" data-loser-id="' + c.id + '">Seleccionar</button>'
                                + '</div>';
                        });
                        $('#merge-search-results').html(html || '<p class="text-muted small mt-2">Sin resultados</p>');
                    });
            }, 400);
        });

        $mergeModal.on('click', '.merge-select-btn', function () {
            selectedLoserId = $(this).data('loser-id');
            $.get($root.data('merge-preview-url'), { loser_id: selectedLoserId })
                .done(function (resp) {
                    var w = resp.data.winner;
                    var l = resp.data.loser;
                    var html = '<div class="row g-3">'
                        + '<div class="col-6"><h6 class="text-success">Conservar (este)</h6>'
                        + '<p class="mb-1"><strong>' + esc(w.name || '—') + '</strong></p>'
                        + '<p class="small mb-0">' + esc(w.email || '—') + '</p>'
                        + '<p class="small mb-0">' + (w.total_conversations || 0) + ' conversaciones</p></div>'
                        + '<div class="col-6"><h6 class="text-danger">Eliminar</h6>'
                        + '<p class="mb-1"><strong>' + esc(l.name || '—') + '</strong></p>'
                        + '<p class="small mb-0">' + esc(l.email || '—') + '</p>'
                        + '<p class="small mb-0">' + (l.total_conversations || 0) + ' conversaciones</p></div>'
                        + '</div>';
                    $('#merge-preview-content').html(html);
                    $('#merge-preview').removeClass('d-none');
                    $('#merge-footer').removeClass('d-none');
                });
        });

        $mergeModal.on('click', '#merge-execute-btn', function () {
            if (!selectedLoserId) { return; }
            if (!confirm('¿Confirmas la fusión? Esta acción no se puede deshacer.')) { return; }
            var $btn = $(this).prop('disabled', true).text('Fusionando...');
            $.ajax({
                url: $root.data('merge-execute-url'),
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                contentType: 'application/json',
                data: JSON.stringify({ loser_id: selectedLoserId }),
            }).done(function () {
                toastr.success('Contactos fusionados correctamente');
                $mergeModal.modal('hide');
                setTimeout(function () { location.reload(); }, 1000);
            }).fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error al fusionar';
                toastr.error(msg);
                $btn.prop('disabled', false).text('Fusionar ahora');
            });
        });

        // ── Ban / Unban ─────────────────────────────────────────────
        $root.on('click', '#contact-ban-btn', function () {
            if (!confirm('¿Suspender este contacto? No podrá recibir nuevas conversaciones.')) { return; }
            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: $root.data('ban-url'),
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            }).done(function () {
                toastr.success('Contacto suspendido');
                setTimeout(function () { location.reload(); }, 800);
            }).fail(function () {
                toastr.error('Error al suspender el contacto');
                $btn.prop('disabled', false);
            });
        });

        $root.on('click', '#contact-unban-btn', function () {
            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: $root.data('unban-url'),
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            }).done(function () {
                toastr.success('Contacto reactivado');
                setTimeout(function () { location.reload(); }, 800);
            }).fail(function () {
                toastr.error('Error al reactivar el contacto');
                $btn.prop('disabled', false);
            });
        });
    });

})(window.jQuery);
