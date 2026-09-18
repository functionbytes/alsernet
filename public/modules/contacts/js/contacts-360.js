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

    // Health score (0-100) → { text, css } for the hero KPI sub-label.
    function healthLabel(score) {
        var value = parseInt(score, 10);
        if (isNaN(value)) {
            return null;
        }
        if (value >= 75) {
            return { text: 'Saludable', css: 'ct-c-success' };
        }
        if (value >= 50) {
            return { text: 'Estable', css: 'ct-c-muted' };
        }
        if (value >= 25) {
            return { text: 'En riesgo', css: 'ct-c-warning' };
        }
        return { text: 'Crítico', css: 'ct-c-danger' };
    }

    // Normaliza una clase de color "bare" (success, danger, warning…) a un modificador .ct-pill-*.
    function pillClass(value, fallback) {
        switch (String(value || fallback || '').toLowerCase()) {
            case 'success':
                return 'ct-pill-success';
            case 'warning':
                return 'ct-pill-warning';
            case 'danger':
                return 'ct-pill-danger';
            case 'dark':
                return 'ct-pill-dark';
            default:
                return 'ct-pill-muted';
        }
    }

    // Same color words (success/warning/danger/info/secondary) → dot/circle background.
    function dotClass(value) {
        switch (String(value || '').toLowerCase()) {
            case 'success':
                return 'ct-bg-success';
            case 'warning':
                return 'ct-bg-warning';
            case 'danger':
                return 'ct-bg-danger';
            case 'info':
                return 'ct-bg-info';
            default:
                return 'ct-bg-muted';
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

    // Estado visual de una integración: mismos significados que sync_status
    // en CustomerIntegrationService (ok/not_found/pending/error) — igual
    // criterio que el panel derecho del inbox, solo que aquí se resume en
    // una pill en vez de una fila con detalle.
    function integrationPillState(it) {
        if (!it.connected) {
            return { cls: 'is-disconnected', icon: 'fa-link-slash', label: 'Sin vincular' };
        }
        switch (it.syncStatus) {
            case 'not_found':
                return { cls: 'is-danger', icon: 'fa-triangle-exclamation', label: 'ID no encontrado en la plataforma' };
            case 'pending':
                return { cls: 'is-warning', icon: 'fa-clock', label: 'Sincronización pendiente' };
            case 'error':
                return { cls: 'is-warning', icon: 'fa-triangle-exclamation', label: 'No se pudo sincronizar' };
            default:
                var synced = it.lastSyncedAt ? new Date(it.lastSyncedAt).toLocaleString() : null;
                return { cls: '', icon: 'fa-link', label: synced ? ('Conectado · sincronizado ' + synced) : 'Conectado' };
        }
    }

    // Fills the hero-header slots (outside the resumen pane) from resumen payload:
    // integration pills, sentiment chip and the KPI row (health/CSAT/conversaciones/gasto).
    function fillResumenHero(data) {
        data = data || {};

        var $integrations = $('#contact-integrations');
        if ($integrations.length) {
            var integrations = Array.isArray(data.integrations) ? data.integrations : [];
            if (!integrations.length) {
                $integrations.html('<span class="ct-meta">Sin integraciones</span>');
            } else {
                var pills = '';
                integrations.forEach(function (it) {
                    var state = integrationPillState(it);
                    var name = it.label || it.platform || '';
                    pills += '<span class="ct-integration-pill' + (state.cls ? ' ' + state.cls : '') + '" ' +
                        'title="' + esc(state.label) + '">' +
                        '<i class="fas ' + state.icon + '"></i>' + esc(name) +
                        (it.connected && it.externalId ? ' <span class="ct-mono">' + esc(it.externalId) + '</span>' : '') +
                        '</span>';
                });
                var anyDisconnected = integrations.some(function (it) { return !it.connected; });
                if (anyDisconnected) {
                    pills += '<button type="button" class="btn btn-sm ct-btn-outline" data-contact-link-trigger>' +
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
                counts = ' <span class="ct-mono">+' + esc(sentiment.positive != null ? sentiment.positive : 0) +
                    ' / -' + esc(sentiment.negative != null ? sentiment.negative : 0) + '</span>';
            }
            $sentiment.html(
                '<span class="ct-chip ct-chip-sentiment" title="' + esc(sentimentLabelText(label)) + '">' +
                '<i class="fas ' + sentimentIcon(label) + '"></i>' + esc(sentimentLabelText(label)) + counts +
                '</span>'
            );
        }

        var $stats = $('#contact-hero-stats');
        if ($stats.length) {
            var stats = data.stats || {};
            var lifetime = stats.lifetime || {};
            var health = healthLabel(stats.healthScore);
            var kpis = [
                {
                    label: 'Health score',
                    value: stats.healthScore != null ? stats.healthScore : '—',
                    sub: health ? health.text : '',
                    subCss: health ? health.css : ''
                },
                {
                    label: 'CSAT medio',
                    value: stats.avgCsat != null ? parseFloat(stats.avgCsat).toFixed(1) : '—',
                    sub: '/ 5'
                },
                {
                    label: 'Conversaciones',
                    value: stats.totalConversations != null ? stats.totalConversations : 0,
                    sub: ''
                },
                {
                    label: 'Gasto total',
                    value: money(lifetime.totalSpent, lifetime.currency),
                    sub: lifetime.ordersCount != null ? (lifetime.ordersCount + ' pedidos') : ''
                }
            ];
            var kpiHtml = '';
            kpis.forEach(function (kpi) {
                kpiHtml += '<div class="ct-kpi">' +
                    '<div class="ct-kpi-label">' + esc(kpi.label) + '</div>' +
                    '<div class="d-flex align-items-baseline flex-wrap">' +
                        '<span class="ct-kpi-value ct-mono">' + esc(kpi.value) + '</span>' +
                        (kpi.sub ? '<span class="ct-kpi-sub ' + (kpi.subCss || '') + '">' + esc(kpi.sub) + '</span>' : '') +
                    '</div></div>';
            });
            $stats.html(kpiHtml);
        }
    }

    // ──────────────────────────────────────────────────────── renderers ──

    // Nota: el avatar, nombre, health/CSAT/conversaciones/gasto y sentimiento ya
    // se pintan en el hero (fillResumenHero); este pane solo cubre el detalle:
    // datos de contacto, atributos personalizados, etiquetas, notas y empresa.
    function renderResumen(data) {
        data = data || {};
        var location = data.location || {};
        var custom = data.customAttributes || {};
        var customEntries = Object.keys(custom).map(function (key) { return [key, custom[key]]; });

        var infoRows = [
            ['fa-envelope', 'Email', data.email],
            ['fa-phone', 'Teléfono', data.phone],
            ['fa-brands fa-whatsapp', 'WhatsApp', data.whatsapp],
            ['fa-location-dot', 'Ubicación', [location.city, location.state, location.country, location.postalCode].filter(Boolean).join(', ')],
            ['fa-language', 'Idioma', data.language],
            ['fa-clock', 'Zona horaria', data.timezone],
            ['fa-eye', 'Última actividad', data.lastSeenAt ? fmtDate(data.lastSeenAt) : '']
        ];

        var html = '<div class="ct-grid-2">';

        // Left column: datos de contacto + atributos personalizados.
        html += '<div class="ct-card">';
        html += '<div class="ct-card-title">Información de contacto</div>';
        html += '<div class="ct-info-table">';
        infoRows.forEach(function (row) {
            if (!row[2]) {
                return;
            }
            html += '<div class="ct-info-row">' +
                '<span class="ct-info-label"><i class="' + faIcon(row[0], 'circle') + '"></i>' + esc(row[1]) + '</span>' +
                '<span class="ct-info-value">' + esc(row[2]) + '</span>' +
                '</div>';
        });
        html += '</div>';
        if (customEntries.length) {
            html += '<div class="ct-card-title mt-4">Atributos personalizados</div>';
            html += '<div class="ct-attr-table">';
            customEntries.forEach(function (entry) {
                html += '<div class="ct-attr-key">' + esc(entry[0]) + '</div><div class="ct-attr-val">' + esc(entry[1]) + '</div>';
            });
            html += '</div>';
        }
        html += '</div>';

        // Right column: etiquetas, notas internas y empresa (si hay datos).
        html += '<div class="ct-stack">';

        // Etiquetas: no hay fuente de datos propia todavia, se muestran los
        // valores de customAttributes como chips; "+ Añadir" queda deshabilitado.
        html += '<div class="ct-card">';
        html += '<div class="ct-card-title">Etiquetas</div>';
        html += '<div class="d-flex flex-wrap gap-2">';
        if (customEntries.length) {
            customEntries.forEach(function (entry, idx) {
                html += '<span class="ct-tag' + (idx === 0 ? ' ct-tag-primary' : '') + '">' + esc(entry[1]) + '</span>';
            });
        } else {
            html += '<span class="ct-meta">Sin etiquetas</span>';
        }
        html += '<button type="button" class="ct-tag ct-tag-add" disabled title="Aun no disponible">' +
            '<i class="fas fa-plus"></i> Añadir</button>';
        html += '</div></div>';

        // Notas internas.
        html += '<div class="ct-card">';
        html += '<div class="ct-card-title">Notas internas</div>';
        html += '<div class="ct-note-box"><i class="fa-regular fa-note-sticky mt-1"></i><span>' +
            (data.internal_notes ? esc(data.internal_notes) : 'Sin notas internas registradas.') + '</span></div>';
        html += '</div>';

        // Empresa: solo si el backend la incluye (aun no la devuelve el tab resumen).
        if (data.company) {
            var company = data.company;
            html += '<div class="ct-card">';
            html += '<div class="ct-card-title">Empresa</div>';
            html += '<div class="ct-company-head">' +
                '<span class="ct-company-icon"><i class="fas fa-building"></i></span>' +
                '<div><div class="fw-semibold small">' + esc(company.name || 'Empresa') + '</div>' +
                (company.domain ? '<div class="ct-meta ct-mono">' + esc(company.domain) + '</div>' : '') + '</div>' +
                '</div>';
            html += '<div class="ct-mini-stats">' +
                '<div class="ct-mini-stat"><div class="ct-mini-stat-value ct-mono">' + esc(company.healthScore != null ? company.healthScore : '—') + '</div><div class="ct-mini-stat-label">Health</div></div>' +
                '<div class="ct-mini-stat"><div class="ct-mini-stat-value ct-mono">' + esc(company.size != null ? company.size : '—') + '</div><div class="ct-mini-stat-label">Tamaño</div></div>' +
                '<div class="ct-mini-stat"><div class="ct-mini-stat-value ct-mono">' + esc(company.contactsCount != null ? company.contactsCount : '—') + '</div><div class="ct-mini-stat-label">Contactos</div></div>' +
                '</div>';
            html += '</div>';
        }

        html += '</div>'; // ct-stack
        html += '</div>'; // ct-grid-2

        return html;
    }

    function renderConversaciones(data) {
        data = data || {};
        var conversations = Array.isArray(data.conversations) ? data.conversations : [];

        var html = '<div class="ct-card mb-3">';
        html += '<div class="ct-card-title">Conversaciones' +
            (conversations.length ? ' <span class="ct-mono">· ' + conversations.length + '</span>' : '') + '</div>';
        if (!conversations.length) {
            html += emptyState('fa-comments', 'Sin conversaciones', 'Este contacto no tiene conversaciones registradas.');
        } else {
            html += '<div class="ct-row-list">';
            conversations.forEach(function (c) {
                html += '<a href="' + esc(c.url || '#') + '" class="ct-row">' +
                    '<span class="ct-row-icon"><i class="' + faIcon(c.channelIcon, 'comment') + '"></i></span>' +
                    '<div class="ct-row-body">' +
                        '<div class="ct-row-title">' + esc(c.subject || 'Sin asunto') + '</div>' +
                        (c.preview ? '<div class="ct-row-sub">' + esc(c.preview) + '</div>' : '') +
                    '</div>' +
                    '<span class="ct-pill ' + pillClass(c.statusClass) + '">' + esc(c.statusLabel || '') + '</span>' +
                    (c.lastAt ? '<span class="ct-row-date">' + esc(fmtDate(c.lastAt)) + '</span>' : '') +
                    '</a>';
            });
            html += '</div>';
        }
        html += '</div>';

        // Sub-container for chats (filled by a separate request).
        html += '<div class="ct-card"><div class="ct-card-title">Chats</div>' +
            '<div id="chats-section">' + skeleton(3) + '</div></div>';
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

        if (!available) {
            return emptyState('fa-comment-slash', 'Chats no disponibles', 'El módulo de chats está desactivado.');
        }
        if (!chats.length) {
            return emptyState('fa-comment-dots', 'Sin chats', 'No hay chats de livechat ni redes sociales.');
        }

        var html = '<div class="ct-row-list">';
        chats.forEach(function (chat) {
            var metaText = chatMetaText(chat.meta);
            var inner = '<span class="ct-row-icon"><i class="' + faIcon(chat.icon, 'comment') + '"></i></span>' +
                '<div class="ct-row-body">' +
                    '<div class="ct-row-title text-capitalize">' + esc(chat.source || 'chat') + '</div>' +
                    (chat.preview ? '<div class="ct-row-sub">' + esc(chat.preview) + '</div>' : '') +
                    (metaText ? '<div class="ct-row-sub">' + metaText + '</div>' : '') +
                '</div>' +
                (chat.at ? '<span class="ct-row-date">' + esc(fmtDate(chat.at)) + '</span>' : '');
            html += chat.url
                ? ('<a href="' + esc(chat.url) + '" class="ct-row">' + inner + '</a>')
                : ('<div class="ct-row">' + inner + '</div>');
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

    // Maps the shared bvOrderStatusClass() helper (is-completed/-shipped/-cancelled/-pending) to a .ct-pill-*.
    function ctOrderPill(status) {
        var cls = (typeof window.bvOrderStatusClass === 'function')
            ? window.bvOrderStatusClass(String(status))
            : '';
        var map = {
            'is-completed': 'ct-pill-success',
            'is-shipped': 'ct-pill-muted',
            'is-cancelled': 'ct-pill-danger',
            'is-pending': 'ct-pill-warning'
        };
        return '<span class="ct-pill ' + (map[cls] || 'ct-pill-muted') + '">' + esc(status) + '</span>';
    }

    function renderErp(data) {
        data = data || {};
        if (data.available === false) {
            return emptyState('fa-database', 'ERP no disponible', 'El módulo ERP está desactivado o sin conexión.');
        }

        var cust = data.customer || {};
        var orders = Array.isArray(data.orders) ? data.orders : [];
        var invoices = Array.isArray(data.invoices) ? data.invoices : [];

        var html = '<div class="ct-grid-2">';

        // Left: cliente ERP.
        html += '<div class="ct-card">';
        html += '<div class="ct-card-title">Cliente ERP</div>';
        if (cust && (cust.found || cust.name || cust.id)) {
            html += '<div class="ct-attr-table">';
            if (cust.name) {
                html += '<div class="ct-attr-key">Nombre</div><div class="ct-attr-val">' + esc(cust.name) + '</div>';
            }
            if (cust.id != null) {
                html += '<div class="ct-attr-key">ID</div><div class="ct-attr-val ct-mono">#' + esc(cust.id) + '</div>';
            }
            if (cust.balance != null) {
                html += '<div class="ct-attr-key">Saldo</div><div class="ct-attr-val ct-mono">' + esc(money(cust.balance)) + '</div>';
            }
            if (cust.credit_limit != null) {
                html += '<div class="ct-attr-key">Límite crédito</div><div class="ct-attr-val ct-mono">' + esc(money(cust.credit_limit)) + '</div>';
            }
            html += '</div>';
        } else {
            html += emptyState('fa-id-card', 'Sin ficha ERP', 'Este contacto no tiene cliente vinculado en el ERP.');
        }
        html += '</div>';

        // Right: pedidos + facturas.
        html += '<div class="ct-stack">';

        html += '<div class="ct-card">';
        html += '<div class="ct-card-title">Pedidos ERP' + (orders.length ? ' <span class="ct-mono">· ' + orders.length + '</span>' : '') + '</div>';
        if (data.orders_loading) {
            html += '<div data-erp-orders-pending>' + spinnerLine('Cargando pedidos del ERP...') + '</div>';
        } else if (!orders.length) {
            html += emptyState('fa-clipboard-list', 'Sin pedidos ERP', 'No hay pedidos en gestión.');
        } else {
            html += '<div class="ct-row-list">';
            orders.slice(0, 20).forEach(function (o) {
                var ref = o.number ? ('#' + o.number) : (o.id ? ('#' + o.id) : '—');
                var label = erpStatusLabel(o.status);
                var date = o.date ? String(o.date).substring(0, 10) : '';
                html += '<div class="ct-row">' +
                    '<span class="ct-row-code ct-mono">' + esc(ref) + '</span>' +
                    '<span class="ct-row-body"></span>' +
                    ctOrderPill(label) +
                    (date ? '<span class="ct-row-date ct-mono">' + esc(date) + '</span>' : '') +
                    '</div>';
            });
            html += '</div>';
        }
        html += '</div>';

        if (invoices.length) {
            html += '<div class="ct-card">';
            html += '<div class="ct-card-title">Facturas <span class="ct-mono">· ' + invoices.length + '</span></div>';
            html += '<div class="ct-row-list">';
            invoices.slice(0, 15).forEach(function (inv) {
                var ref = inv.number ? ('#' + inv.number) : (inv.id ? ('#' + inv.id) : '—');
                var date = inv.date ? String(inv.date).substring(0, 10) : '';
                html += '<div class="ct-row">' +
                    '<span class="ct-row-code ct-mono">' + esc(ref) + '</span>' +
                    '<span class="ct-row-body ct-row-sub">' + (inv.payment_method ? esc(inv.payment_method) : '') + '</span>' +
                    (inv.status ? ctOrderPill(inv.status) : '') +
                    (date ? '<span class="ct-row-date ct-mono">' + esc(date) + '</span>' : '') +
                    '</div>';
            });
            html += '</div></div>';
        }

        html += '</div>'; // ct-stack
        html += '</div>'; // ct-grid-2

        return html;
    }

    function renderPrestashop(data) {
        data = data || {};
        if (data.available === false) {
            return emptyState('fa-bag-shopping', 'PrestaShop no disponible', 'El módulo PrestaShop está desactivado.');
        }

        var orders = Array.isArray(data.orders) ? data.orders : [];
        var carts = Array.isArray(data.carts) ? data.carts : [];
        var vouchers = Array.isArray(data.vouchers) ? data.vouchers : [];

        if (!orders.length && !carts.length && !vouchers.length) {
            return emptyState('fa-bag-shopping', 'Sin datos de PrestaShop', 'No hay pedidos, carritos ni cupones.');
        }

        var html = '<div class="ct-grid-2">';

        // Left: pedidos.
        html += '<div class="ct-card">';
        html += '<div class="ct-card-title">Pedidos PrestaShop' + (orders.length ? ' <span class="ct-mono">· ' + orders.length + '</span>' : '') + '</div>';
        if (!orders.length) {
            html += emptyState('fa-bag-shopping', 'Sin pedidos', 'No hay pedidos en PrestaShop.');
        } else {
            html += '<div class="ct-row-list">';
            orders.slice(0, 20).forEach(function (o) {
                var ref = o.reference || (o.number ? ('#' + o.number) : (o.id ? ('#' + o.id) : '—'));
                var date = o.date ? String(o.date).substring(0, 10) : '';
                html += '<div class="ct-row">' +
                    '<span class="ct-row-code ct-mono">' + esc(ref) + '</span>' +
                    (o.total != null ? '<span class="ct-row-price ct-mono">' + esc(money(o.total, o.currency)) + '</span>' : '<span class="ct-row-body"></span>') +
                    (o.status ? ctOrderPill(o.status) : '') +
                    (date ? '<span class="ct-row-date ct-mono">' + esc(date) + '</span>' : '') +
                    '</div>';
            });
            html += '</div>';
        }
        html += '</div>';

        // Right: carritos + cupones.
        html += '<div class="ct-stack">';
        html += '<div class="ct-card">';
        html += '<div class="ct-card-title">Carritos</div>';
        if (!carts.length) {
            html += emptyState('fa-cart-shopping', 'Sin carritos', 'No hay carritos activos.');
        } else {
            html += '<div class="ct-row-list">';
            carts.forEach(function (cart) {
                html += '<div class="ct-row">' +
                    '<i class="fas fa-cart-shopping ct-c-muted"></i>' +
                    '<span class="ct-row-body">' + esc((cart.items != null ? cart.items : 0)) + ' artículos</span>' +
                    '<span class="ct-row-date ct-mono">' + esc(cart.updatedAt ? fmtDate(cart.updatedAt) : '') + '</span>' +
                    '</div>';
            });
            html += '</div>';
        }
        html += '</div>';

        html += '<div class="ct-card">';
        html += '<div class="ct-card-title">Cupones</div>';
        if (!vouchers.length) {
            html += emptyState('fa-ticket', 'Sin cupones', 'No hay cupones asociados.');
        } else {
            html += '<div class="ct-row-list">';
            vouchers.forEach(function (v) {
                html += '<div class="ct-row">' +
                    '<i class="fas fa-ticket ct-c-muted"></i>' +
                    '<span class="ct-row-body ct-mono">' + esc(v.code || v.name || 'Cupón') + '</span>' +
                    '<span class="ct-pill ct-pill-muted">' + esc(v.value != null ? v.value : '') + '</span>' +
                    '</div>';
            });
            html += '</div>';
        }
        html += '</div>';
        html += '</div>'; // ct-stack

        html += '</div>'; // ct-grid-2
        return html;
    }

    function renderTienda(data) {
        data = data || {};
        if (data.available === false) {
            return emptyState('fa-store', 'Tienda local no disponible', 'El módulo Remarketing está desactivado.');
        }

        var orders = Array.isArray(data.orders) ? data.orders : [];
        var carts = Array.isArray(data.carts) ? data.carts : [];
        var stats = data.stats || {};

        var bannerBits = [];
        if (stats.ordersCount != null) {
            bannerBits.push(stats.ordersCount + ' pedidos');
        }
        if (stats.totalSpent != null) {
            bannerBits.push(money(stats.totalSpent) + ' de gasto acumulado');
        }

        var html = '<div class="ct-stack">';
        html += '<div class="ct-banner"><i class="fas fa-store"></i>' +
            '<span>Datos de la <strong>tienda local</strong> (espejo de Remarketing por email)' +
            (bannerBits.length ? '. ' + esc(bannerBits.join(' · ')) + '.' : '.') +
            '</span></div>';

        if (!orders.length && !carts.length) {
            html += emptyState('fa-store', 'Sin actividad en tienda local', 'No hay pedidos ni carritos.');
            html += '</div>';
            return html;
        }

        if (orders.length) {
            html += '<div class="ct-card">';
            html += '<div class="ct-card-title">Pedidos tienda local <span class="ct-mono">· ' + orders.length + '</span></div>';
            html += '<div class="accordion" id="tiendaOrders">';
            orders.forEach(function (o, idx) {
                var heading = 'tiendaOrderHead' + idx;
                var collapse = 'tiendaOrderBody' + idx;
                var items = Array.isArray(o.items) ? o.items : [];
                var itemsHtml = '';
                if (items.length) {
                    itemsHtml += '<ul class="list-group list-group-flush">';
                    items.forEach(function (it) {
                        itemsHtml += '<li class="list-group-item d-flex justify-content-between px-0">' +
                            '<span>' + esc(it.name || 'Producto') + ' <span class="ct-meta">×' + esc(it.qty != null ? it.qty : 1) + '</span></span>' +
                            '<span class="ct-mono">' + esc(money(it.price, o.currency)) + '</span>' +
                            '</li>';
                    });
                    itemsHtml += '</ul>';
                } else {
                    itemsHtml = '<div class="ct-meta">Sin artículos</div>';
                }
                html += '<div class="accordion-item">' +
                    '<h2 class="accordion-header" id="' + heading + '">' +
                        '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" ' +
                            'data-bs-target="#' + collapse + '" aria-expanded="false" aria-controls="' + collapse + '">' +
                            '<span class="ct-row-code ct-mono me-auto">' + esc(o.number || '—') + '</span>' +
                            (o.status ? ('<span class="me-3">' + ctOrderPill(o.status) + '</span>') : '') +
                            '<span class="ct-row-price ct-mono">' + esc(money(o.total, o.currency)) + '</span>' +
                        '</button>' +
                    '</h2>' +
                    '<div id="' + collapse + '" class="accordion-collapse collapse" aria-labelledby="' + heading + '" data-bs-parent="#tiendaOrders">' +
                        '<div class="accordion-body">' +
                            (o.placedAt ? '<div class="ct-meta mb-2">' + esc(fmtDate(o.placedAt)) + '</div>' : '') +
                            itemsHtml +
                        '</div>' +
                    '</div>' +
                    '</div>';
            });
            html += '</div></div>';
        }

        if (carts.length) {
            html += '<div class="ct-card">';
            html += '<div class="ct-card-title">Carritos abandonados <span class="ct-mono">· ' + carts.length + '</span></div>';
            html += '<div class="ct-row-list">';
            carts.forEach(function (cart) {
                var itemCount = cart.itemsCount != null ? cart.itemsCount : 0;
                var lineItems = Array.isArray(cart.lines) ? cart.lines : [];
                var recoverable = !!cart.recoverable && lineItems.length > 0;
                var recoverBtn = recoverable
                    ? '<button type="button" class="btn btn-sm ct-btn-outline" ' +
                        'data-cart-recover="' + esc(encodeURIComponent(JSON.stringify(lineItems))) + '">' +
                        '<i class="fas fa-rotate-left me-1"></i>Recuperar</button>'
                    : '';
                html += '<div class="ct-row">' +
                    '<i class="fas fa-cart-shopping ct-c-muted"></i>' +
                    '<span class="ct-row-body">' + esc(itemCount) + ' artículos</span>' +
                    '<span class="ct-row-date ct-mono">' + esc(cart.updatedAt ? fmtDate(cart.updatedAt) : '') + '</span>' +
                    '<span class="ct-row-price ct-mono">' + esc(money(cart.total)) + '</span>' +
                    recoverBtn +
                    '</div>';
            });
            html += '</div></div>';
        }

        html += '</div>'; // ct-stack
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

        var html = '<div class="ct-grid-2">';

        // Left column: actividad reciente + navegación.
        html += '<div class="ct-card">';
        if (timeline.length) {
            html += '<div class="ct-card-title">Actividad reciente</div>';
            html += '<div class="ct-timeline">';
            timeline.forEach(function (ev) {
                html += '<div class="ct-timeline-item">' +
                    '<span class="ct-timeline-icon"><i class="' + faIcon(ev.icon, 'circle-dot') + '"></i></span>' +
                    '<div class="ct-timeline-body">' +
                        '<div class="ct-timeline-title">' + esc(ev.title || '') + '</div>' +
                        (ev.detail ? '<div class="ct-timeline-sub">' + esc(ev.detail) + '</div>' : '') +
                    '</div>' +
                    (ev.at ? '<span class="ct-timeline-date">' + esc(fmtDate(ev.at)) + '</span>' : '') +
                    '</div>';
            });
            html += '</div>';
        }

        // Emails (parte del feed de actividad de comunicaciones).
        if (emails.length) {
            html += '<div class="ct-card-title mt-4">Emails</div>';
            html += '<div class="ct-row-list">';
            emails.forEach(function (m) {
                var inner = '<span class="ct-row-icon"><i class="fas fa-envelope"></i></span>' +
                    '<span class="ct-row-body ct-row-title">' + esc(m.subject || 'Sin asunto') + '</span>' +
                    '<span class="ct-pill ' + pillClass(m.statusClass) + '">' + esc(m.status || '') + '</span>' +
                    (m.at ? '<span class="ct-row-date">' + esc(fmtDate(m.at)) + '</span>' : '');
                html += m.url
                    ? ('<a href="' + esc(m.url) + '" class="ct-row">' + inner + '</a>')
                    : ('<div class="ct-row">' + inner + '</div>');
            });
            html += '</div>';
        }

        // Navegación web (page visits con contexto de dispositivo/navegador).
        if (pageVisits.length) {
            html += '<div class="ct-card-title mt-4"><i class="fas fa-compass"></i> Navegación</div>';
            html += '<div class="ct-row-list">';
            pageVisits.forEach(function (v) {
                var deviceBits = [v.device, v.browser, v.platform, v.os]
                    .filter(function (x) { return x != null && x !== ''; })
                    .map(function (x) { return esc(x); })
                    .join(' · ');
                var inner = '<i class="fas fa-compass mt-1 ct-c-muted"></i>' +
                    '<div class="ct-row-body">' +
                        '<div class="ct-row-title">' + esc(v.title || v.url || '') + '</div>' +
                        (v.url && v.title ? '<div class="ct-row-sub ct-mono">' + esc(v.url) + '</div>' : '') +
                        (deviceBits ? '<div class="ct-row-sub">' + deviceBits + '</div>' : '') +
                    '</div>' +
                    '<span class="ct-row-date ct-mono">' +
                        (v.timeSpent != null ? esc(v.timeSpent) + ' s · ' : '') +
                        (v.at ? esc(fmtDate(v.at)) : '') +
                    '</span>';
                html += v.url
                    ? ('<a href="' + esc(v.url) + '" class="ct-row">' + inner + '</a>')
                    : ('<div class="ct-row">' + inner + '</div>');
            });
            html += '</div>';
        }
        html += '</div>'; // left card

        // Right column: señales (tickets + CSAT) y empresa.
        html += '<div class="ct-stack">';

        if (tickets.length || csat.length) {
            html += '<div class="ct-card">';
            html += '<div class="ct-card-title">Señales</div>';
            html += '<div class="ct-row-list">';
            tickets.forEach(function (t) {
                var inner = '<i class="fas fa-ticket ct-c-muted"></i>' +
                    '<span class="ct-row-body ct-row-title">' + (t.number ? esc(t.number) + ' · ' : '') + esc(t.subject || '') + '</span>' +
                    (t.priority ? '<span class="ct-pill ' + pillClass(t.priorityClass || 'muted') + '">' + esc(t.priority) + '</span>' : '');
                html += t.url
                    ? ('<a href="' + esc(t.url) + '" class="ct-row">' + inner + '</a>')
                    : ('<div class="ct-row">' + inner + '</div>');
            });
            csat.forEach(function (c) {
                html += '<div class="ct-row">' +
                    '<i class="fas fa-star ct-c-warning"></i>' +
                    '<span class="ct-row-body ct-row-title">CSAT ' + esc(c.score != null ? c.score : '—') +
                        (c.comment ? ' · «' + esc(c.comment) + '»' : '') + '</span>' +
                    (c.at ? '<span class="ct-row-date ct-mono">' + esc(fmtDate(c.at)) + '</span>' : '') +
                    '</div>';
            });
            html += '</div></div>';
        }

        // Company rollup (mismos campos que devuelve ContactAggregatorService::actividad()).
        if (company) {
            html += '<div class="ct-card">';
            html += '<div class="ct-card-title">Empresa</div>';
            html += '<div class="ct-attr-table">';
            if (company.domain) {
                html += '<div class="ct-attr-key">Dominio</div><div class="ct-attr-val ct-mono">' + esc(company.domain) + '</div>';
            }
            if (company.industry) {
                html += '<div class="ct-attr-key">Sector</div><div class="ct-attr-val">' + esc(company.industry) + '</div>';
            }
            if (company.healthScore != null) {
                html += '<div class="ct-attr-key">Health</div><div class="ct-attr-val ct-mono">' + esc(company.healthScore) + '</div>';
            }
            html += '</div></div>';
        }

        html += '</div>'; // ct-stack
        html += '</div>'; // ct-grid-2

        return html;
    }

    // ──────────────────────────────────────────────────────── tickets ──

    // SLA badge ({label, class: success|warning|danger}) → circle chip with icon + label.
    function slaChip(sla) {
        if (!sla || !sla.label) {
            return '';
        }
        var icons = { success: 'fa-check', warning: 'fa-clock', danger: 'fa-triangle-exclamation' };
        var textCss = { success: 'ct-c-success', warning: 'ct-c-warning', danger: 'ct-c-danger' };
        var cls = String(sla.class || '').toLowerCase();
        return '<span class="ct-sla-chip ' + (textCss[cls] || 'ct-c-muted') + '">' +
            '<span class="ct-sla-dot ' + dotClass(cls) + '"><i class="fas ' + (icons[cls] || 'fa-clock') + '"></i></span>' +
            '<span class="ct-mono">' + esc(sla.label) + '</span>' +
            '</span>';
    }

    function renderTickets(data) {
        data = data || {};
        if (data.available === false) {
            return emptyState('fa-ticket', 'Tickets no disponibles', 'El módulo de tickets está desactivado.');
        }

        var tickets = Array.isArray(data.tickets) ? data.tickets : [];
        var categories = Array.isArray(data.categories) ? data.categories : [];

        var html = '<div class="ct-card">';
        html += '<div class="d-flex align-items-center gap-2 mb-3">' +
            '<div class="ct-card-title mb-0">Tickets del contacto' + (tickets.length ? ' <span class="ct-mono">· ' + tickets.length + '</span>' : '') + '</div>' +
            '<div class="flex-grow-1 border-top"></div>' +
            '<button type="button" class="btn btn-sm ct-btn-dark" data-ticket-toggle-form>' +
                '<i class="fas fa-plus me-1"></i>Crear ticket</button>' +
            '</div>';

        // Inline create form (hidden by default). Incluye "Prioridad" solo a nivel
        // visual/payload — el backend aún no persiste este campo (ver notas del módulo).
        var categoryOptions = '<option value="">Sin categoría</option>';
        categories.forEach(function (cat) {
            categoryOptions += '<option value="' + esc(cat.id) + '">' + esc(cat.name || '') + '</option>';
        });
        html += '<form id="ticket-create-form" class="ct-form-box mb-3 d-none">' +
            '<div class="mb-3">' +
                '<label class="form-label" for="ticket-subject">Asunto</label>' +
                '<input type="text" class="form-control" id="ticket-subject" name="subject" required>' +
            '</div>' +
            '<div class="row g-2 mb-3">' +
                '<div class="col-6">' +
                    '<label class="form-label" for="ticket-category">Categoría</label>' +
                    '<select class="form-select" id="ticket-category" name="category_id">' + categoryOptions + '</select>' +
                '</div>' +
                '<div class="col-6">' +
                    '<label class="form-label" for="ticket-priority">Prioridad</label>' +
                    '<select class="form-select" id="ticket-priority" name="priority">' +
                        '<option value="normal">Normal</option>' +
                        '<option value="high">Alta</option>' +
                        '<option value="urgent">Urgente</option>' +
                    '</select>' +
                '</div>' +
            '</div>' +
            '<div class="mb-3">' +
                '<label class="form-label" for="ticket-message">Mensaje</label>' +
                '<textarea class="form-control" id="ticket-message" name="message" rows="4" required></textarea>' +
            '</div>' +
            '<div class="d-flex gap-2">' +
                '<button type="submit" class="btn ct-btn-dark"><i class="fas fa-paper-plane me-1"></i>Crear ticket</button>' +
                '<button type="button" class="btn btn-outline-secondary" data-ticket-cancel-form>Cancelar</button>' +
            '</div>' +
            '</form>';

        if (!tickets.length) {
            html += emptyState('fa-ticket', 'Sin tickets', 'Este contacto no tiene tickets registrados.');
            html += '</div>';
            return html;
        }

        html += '<div class="ct-row-list">';
        tickets.forEach(function (t) {
            var inner = (t.number ? '<span class="ct-row-code ct-mono">' + esc(t.number) + '</span>' : '') +
                '<span class="ct-row-body ct-row-title">' + esc(t.subject || 'Sin asunto') + '</span>' +
                (t.priority ? ('<span class="ct-meta d-inline-flex align-items-center gap-1">' +
                    '<span class="ct-dot ' + dotClass(t.priorityClass) + '"></span>' + esc(t.priority) + '</span>') : '') +
                '<span class="ct-pill ' + (t.statusClass === 'success' ? 'ct-pill-dark' : 'ct-pill-muted') + '">' + esc(t.status || '') + '</span>' +
                slaChip(t.slaBadge) +
                (t.createdAt ? '<span class="ct-row-date ct-mono">' + esc(fmtDate(t.createdAt)) + '</span>' : '');
            html += t.url
                ? ('<a href="' + esc(t.url) + '" class="ct-row">' + inner + '</a>')
                : ('<div class="ct-row">' + inner + '</div>');
        });
        html += '</div>';
        html += '</div>';

        return html;
    }

    // ──────────────────────────────────────────────────────── carrito ──

    function renderCartLines(cart) {
        cart = cart || {};
        var items = Array.isArray(cart.items) ? cart.items : [];
        if (!items.length) {
            return emptyState('fa-cart-shopping', 'Carrito vacío', 'Agrega productos por su ID para empezar.');
        }
        var html = '<div class="ct-row-list mb-3">';
        items.forEach(function (it) {
            html += '<div class="ct-row" data-cart-item="' + esc(it.id) + '">' +
                '<div class="ct-row-body">' +
                    '<div class="ct-row-title">' + esc(it.name || ('Producto #' + (it.product_id != null ? it.product_id : ''))) + '</div>' +
                    '<div class="ct-row-sub ct-mono">' + esc(money(it.unit_price, cart.currency)) + ' × ' + esc(it.quantity != null ? it.quantity : 1) + '</div>' +
                '</div>' +
                '<span class="ct-row-price ct-mono">' + esc(money(it.line_total != null ? it.line_total : 0, cart.currency)) + '</span>' +
                '<button type="button" class="ct-icon-btn" data-cart-remove-item="' + esc(it.id) + '" title="Quitar">' +
                    '<i class="fas fa-trash"></i></button>' +
                '</div>';
        });
        html += '</div>';
        return html;
    }

    function renderCartTotals(cart) {
        cart = cart || {};
        var rows = [
            ['Subtotal', cart.subtotal, false],
            ['Descuento' + (cart.discount_code ? ' <span class="ct-meta">' + esc(cart.discount_code) + '</span>' : ''), cart.discount_amount, false],
            ['Envío', cart.shipping_amount, false],
            ['Total', cart.total, true]
        ];
        var html = '<div class="ct-info-table mb-3">';
        rows.forEach(function (row) {
            if (row[1] == null) {
                return;
            }
            html += '<div class="ct-info-row' + (row[2] ? ' fw-bold' : '') + '">' +
                '<span class="ct-info-label' + (row[2] ? ' text-body' : '') + '">' + row[0] + '</span>' +
                '<span class="ct-info-value ct-mono">' + esc(money(row[1], cart.currency)) + '</span>' +
                '</div>';
        });
        html += '</div>';
        return html;
    }

    function renderCarrito(cart) {
        cart = cart || {};
        if (cart.available === false) {
            return emptyState('fa-cart-shopping', 'Carrito no disponible', 'El carrito asistido está desactivado.');
        }

        var html = '<div class="ct-grid-2">';

        // Left: lines + add form.
        html += '<div class="ct-card">';
        html += '<div class="ct-card-title">Líneas del carrito</div>';
        html += '<div id="cart-lines">' + renderCartLines(cart) + '</div>';
        html += '<form id="cart-add-form" class="ct-form-box">' +
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
                    '<button type="submit" class="btn ct-btn-dark w-100"><i class="fas fa-plus me-1"></i>Añadir</button>' +
                '</div>' +
            '</div>' +
            '</form>';
        html += '</div>';

        // Right: totals + actions.
        html += '<div class="ct-card">';
        html += '<div class="ct-card-title">Totales</div>';
        html += '<div id="cart-totals">' + renderCartTotals(cart) + '</div>';
        html += '<form id="cart-discount-form" class="mb-3">' +
            '<label class="form-label" for="cart-discount-code">Código de cupón</label>' +
            '<div class="input-group">' +
                '<input type="text" class="form-control" id="cart-discount-code" name="code" placeholder="Código">' +
                '<button type="submit" class="btn ct-btn-outline"><i class="fas fa-tag"></i></button>' +
            '</div>' +
            '</form>';
        html += '<div class="d-grid gap-2">' +
            '<button type="button" id="cart-generate-order" class="btn ct-btn-dark">' +
                '<i class="fas fa-receipt me-1"></i>Generar pedido</button>' +
            '<button type="button" id="cart-send-link" class="btn ct-btn-outline">' +
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
            // Campo visual añadido por el rediseño; el backend aún no lo persiste.
            priority: $form.find('[name="priority"]').val(),
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
                            var initials = String(c.name || '?').trim().charAt(0).toUpperCase();
                            html += '<div class="ct-duplicate-item merge-select-btn" data-loser-id="' + esc(c.id) + '">'
                                + '<span class="ct-duplicate-avatar">' + esc(initials) + '</span>'
                                + '<div class="ct-row-body">'
                                +   '<div class="fw-semibold small">' + esc(c.name || '—') + '</div>'
                                +   '<div class="ct-meta ct-mono">' + esc(c.email || '—') + ' · ' + (c.total_conversations || 0) + ' conv.</div>'
                                + '</div>'
                                + '<span class="ct-duplicate-radio"></span>'
                                + '</div>';
                        });
                        $('#merge-search-results').html(
                            html
                                ? ('<div class="ct-row-list mt-2">' + html + '</div>')
                                : '<p class="ct-meta mt-2">Sin resultados</p>'
                        );
                    });
            }, 400);
        });

        $mergeModal.on('click', '.merge-select-btn', function () {
            selectedLoserId = $(this).data('loser-id');
            $mergeModal.find('.ct-duplicate-item').removeClass('is-selected');
            $(this).addClass('is-selected');
            $.get($root.data('merge-preview-url'), { loser_id: selectedLoserId })
                .done(function (resp) {
                    var w = resp.data.winner;
                    var l = resp.data.loser;
                    var html = '<div class="ct-attr-table">'
                        + '<div class="ct-attr-key">Se conserva</div>'
                        + '<div class="ct-attr-val">' + esc(w.name || '—') + ' · ' + esc(w.email || '—') + '</div>'
                        + '<div class="ct-attr-key">Se elimina</div>'
                        + '<div class="ct-attr-val">' + esc(l.name || '—') + ' · ' + esc(l.email || '—') + '</div>'
                        + '<div class="ct-attr-key">Se mueve</div>'
                        + '<div class="ct-attr-val">' + (l.total_conversations || 0) + ' conversaciones</div>'
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
