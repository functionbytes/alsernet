/**
 * HelpdeskContacts — Contact 360 tab-shell controller.
 *
 * Self-invoking IIFE, jQuery only. Lazy-loads each tab pane once on first show.
 * DOM contract (show.blade.php):
 *   - Root:  #contact360[data-customer-id][data-base-url][data-cart-base-url?]
 *   - Tabs:  button.nav-link[data-contact-tab="resumen|conversaciones|erp|prestashop|tienda|actividad|tickets|carrito"]
 *   - Panes: .tab-pane#pane-{tab}[data-loaded="0|1"]
 *   - Web:   #chats-section al final del pane conversaciones (navegación del cliente)
 *   - Sync:  #contact-sync-modal (#contact-sync-confirm-btn triggers runSync())
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
    var customerEmail = String($root.data('customer-email') || '');
    var baseUrl = String($root.data('base-url') || '').replace(/\/+$/, '');
    var psCartBaseUrl = String($root.data('ps-cart-base-url') || '').replace(/\/+$/, '');
    var psAddressesUrl = String($root.data('ps-addresses-url') || '').replace(/\/+$/, '');
    var psCountryStatesUrl = String($root.data('ps-country-states-url') || '').replace(/\/+$/, '');
    var psReturnsUrl = String($root.data('ps-returns-url') || '').replace(/\/+$/, '');
    var psProductsUrl = String($root.data('ps-products-url') || '').replace(/\/+$/, '');
    var psActiveCartId = null;
    var psAddressPickerType = 'delivery';
    var psAddressesCache = [];
    var psAddressEditingId = null;
    var csrf = $('meta[name="csrf-token"]').attr('content');

    // Assisted-cart base URL. The backend may expose a contacts proxy via
    // data-cart-base-url; otherwise we reuse the shared helpdesk manager route
    // (panel/helpdesk/customers/{id}/cart), derived from the page origin.
    var cartBaseUrl = String($root.data('cart-base-url') || '').replace(/\/+$/, '');
    if (!cartBaseUrl) {
        cartBaseUrl = window.location.origin + '/panel/helpdesk/customers/' + encodeURIComponent(customerId) + '/cart';
    }

    // Tabs that own a dedicated pane.
    var TABS = ['resumen', 'conversaciones', 'erp', 'prestashop', 'tienda', 'actividad', 'tickets', 'carrito'];

    // Ficha 360 versión B (una columna, sin pestañas): fuentes que se piden
    // todas en paralelo al cargar la página en vez de bajo demanda al abrir
    // una pestaña — cada una pinta su propia sección con su propio esqueleto
    // (ver onCtfTabLoaded más abajo y CTF_SKELETON_IDS).
    var CTF_TABS = ['resumen', 'erp', 'prestashop', 'tienda', 'actividad', 'tickets'];
    var ctfData = {};
    var canUpdate = String($root.data('can-update')) === '1';
    var erpChatOn = String($root.data('erp-chat')) === '1';

    // ─────────────────────────────────────────────────────────── helpers ──

    // Referenciado desde onerror="" inline en <img> (miniaturas de producto
    // del carrito) — tiene que colgar de window porque un atributo onerror
    // se evalúa en el ámbito global, no dentro de este IIFE.
    window.__ctImgFallback = function (img) {
        var span = document.createElement('span');
        span.className = 'ct-row-thumb ct-row-thumb-empty';
        span.innerHTML = '<i class="fas fa-box"></i>';
        img.replaceWith(span);
    };

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

    // Formato es-ES (1.234,56) — antes usaba toFixed(2) con el código de
    // divisa crudo pegado detrás ("0.00 EUR"), inconsistente con el listado
    // (Fase 1, Blade number_format(...,',','.').' €') y con el resto del
    // sistema visual (--psc-*). "EUR" y null se muestran como €; cualquier
    // otra divisa real se añade como sufijo tras el símbolo.
    function money(amount, currency) {
        var value = parseFloat(amount);
        if (isNaN(value)) {
            value = 0;
        }
        var formatted = value.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        var isEur = !currency || String(currency).toUpperCase() === 'EUR';
        return formatted + (isEur ? ' €' : ' ' + esc(currency));
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
        return '<div class="ct-empty">' +
            '<span class="ct-empty-icon"><i class="' + faIcon(icon, 'inbox') + '"></i></span>' +
            '<div class="ct-empty-title">' + esc(title) + '</div>' +
            (sub ? '<div class="ct-empty-sub">' + esc(sub) + '</div>' : '') +
            '</div>';
    }

    var SOURCE_LABELS = {
        resumen: 'El resumen', conversaciones: 'Las conversaciones', chats: 'La navegación web',
        erp: 'El ERP', prestashop: 'PrestaShop', tienda: 'La tienda local',
        actividad: 'La actividad', tickets: 'El módulo de tickets'
    };

    // "Error de una fuente": solo esa pestaña falla y se reintenta sola.
    function errorState(retryTab) {
        var who = SOURCE_LABELS[retryTab] || 'Esta fuente';
        var retry = retryTab
            ? '<button type="button" class="psc-btn psc-btn--outline mt-2" data-contact-retry="' + esc(retryTab) + '">Reintentar solo esta pestaña</button>'
            : '';
        return '<div class="ct-empty">' +
            '<span class="ct-empty-icon is-warn"><i class="fas fa-triangle-exclamation"></i></span>' +
            '<div class="ct-empty-title">Error de una fuente</div>' +
            '<div class="ct-empty-sub">' + esc(who) + ' no responde. El resto de la ficha sigue disponible.</div>' +
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
        html += '<button type="button" class="ct-tag ct-tag-add" disabled title="Aún no disponible">' +
            '<i class="fas fa-plus"></i> Añadir</button>';
        html += '</div></div>';

        // Notas internas.
        html += '<div class="ct-card">';
        html += '<div class="ct-card-title">Notas internas</div>';
        html += '<div class="ct-note-box"><i class="fa-regular fa-note-sticky mt-1"></i><span>' +
            (data.internal_notes ? esc(data.internal_notes) : 'Sin notas internas registradas.') + '</span></div>';
        html += '</div>';

        // Empresa: solo si el backend la incluye (aún no la devuelve el tab resumen).
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



    function renderPsAddressesList(addresses) {
        if (!addresses.length) {
            return c3Empty('fa-location-dot', 'Sin direcciones', 'Este cliente no tiene direcciones guardadas.');
        }
        return addresses.map(function (a) {
            var fullName = [a.firstname, a.lastname].filter(Boolean).join(' ');
            var sub = [a.address, [a.postcode, a.city].filter(Boolean).join(' ')].filter(Boolean).join(' · ');
            return '<div class="c3-delivery">' +
                '<span class="c3-delivery-body"><span class="ref">' + esc(a.alias || 'Dirección') + '</span>' +
                '<span class="t">' + esc(fullName) + (sub ? ' <span class="muted">· ' + esc(sub) + '</span>' : '') + '</span></span>' +
                '<button type="button" class="c3-link" data-ps-address-edit="' + esc(a.id) + '">Editar</button>' +
                '</div>';
        }).join('');
    }

    // Detalle de un pedido PrestaShop (payload crudo del bridge, ver
    // alsernet_order_detail() en alsernetbridge/helpers/order.php).

    // Línea de artículo del carrito EN VIVO de PrestaShop (editable: cantidad
    // +/- y quitar). Distinto del carrito asistido (renderCarrito más abajo).
    function ctCartItemRowHtml(item) {
        var qty = parseInt(item.quantity || 1, 10);
        var unitPrice = parseFloat(item.unit_price || 0);
        var lineTotal = item.total_wt != null ? parseFloat(item.total_wt) : (unitPrice * qty);
        var original = parseFloat(item.unit_price_original || 0);
        var hasDiscount = !!item.has_discount && original > unitPrice;
        // onerror inline, no delegación: el evento 'error' de <img> no
        // burbujea, así que un listener delegado en document nunca lo vería.
        var thumb = item.image_url
            ? '<img src="' + esc(item.image_url) + '" alt="" class="c3-cart-thumb" onerror="window.__ctImgFallback(this)">'
            : '<span class="c3-cart-thumb ct-row-thumb-empty"><i class="fas fa-box"></i></span>';
        // Mockup pieza 11: nombre, en mono "REF · precio", cantidad y quitar.
        var meta = [item.reference, item.attributes_small, money(unitPrice)].filter(Boolean).join(' · ');

        return '<div class="c3-cart-line" data-cart-product-id="' + esc(item.product_id) + '" data-cart-attribute-id="' + esc(item.attribute_id || 0) + '" data-cart-qty="' + qty + '">' +
            thumb +
            '<span class="c3-cart-body"><span class="t">' + esc(item.name || 'Producto') + '</span>' +
                '<span class="s">' + esc(meta) + (hasDiscount ? ' · antes ' + esc(money(original)) : '') + '</span></span>' +
            '<span class="c3-cart-qty">' +
                '<button type="button" data-cart-action="qty-dec" aria-label="Quitar una unidad">−</button>' +
                '<span>×' + qty + '</span>' +
                '<button type="button" data-cart-action="qty-inc" aria-label="Añadir una unidad">+</button>' +
            '</span>' +
            '<span class="c3-cart-amt">' + esc(money(lineTotal)) + '</span>' +
            '<button type="button" class="c3-cart-remove" data-cart-action="remove" aria-label="Quitar del carrito">×</button>' +
            '</div>';
    }

    // Detalle editable del carrito EN VIVO de PrestaShop (payload crudo del
    // bridge, ver $formattedCarts en alsernet_customer_helpdesk_context()),
    // con el diseño del "Carrito asistido" del mockup (pieza 11).
    function renderPsCartDetail(cart) {
        cart = cart || {};
        var items = Array.isArray(cart.items) ? cart.items : [];
        var vouchers = Array.isArray(cart.vouchers) ? cart.vouchers : [];
        var totals = cart.totals || {};

        // Buscador: siempre visible, también con el carrito vacío. Intro busca.
        var html = '<div class="search-field-inline">' +
            '<i class="fas fa-magnifying-glass"></i>' +
            '<input type="text" class="ct-finput" id="ps-cart-product-search-input" placeholder="Añadir producto por nombre o referencia…">' +
            '</div>';
        html += '<div id="ps-cart-product-search-results"></div>';
        // Catálogo completo (ficha, stock por almacén, alternativas, comparar)
        // del chat, montado por _ps-chat-bridge; desde ahí se añade al carrito.
        if (typeof window.openProductRecommend === 'function') {
            html += '<button type="button" class="c3-link align-self-start" data-c3-open-catalog>Abrir el catálogo completo</button>';
        }

        if (!items.length) {
            html += c3Empty('fa-cart-shopping', 'Carrito vacío', 'Busca un producto para empezarlo.');
            return html;
        }

        html += '<div class="c3-cart-lines">' + items.map(ctCartItemRowHtml).join('') + '</div>';

        html += '<div><label class="ct-flabel" for="ps-cart-voucher-input">Descuento</label>' +
            '<div class="c3-inline-form"><input type="text" class="ct-finput" id="ps-cart-voucher-input" placeholder="Código de cupón">' +
            '<button type="button" class="psc-btn psc-btn--outline" data-cart-action="apply-voucher">Aplicar</button></div></div>';

        vouchers.forEach(function (v) {
            var valueLabel = v.free_shipping ? 'Envío gratis' : (v.value ? '−' + money(v.value, cart.currency) : '');
            var vCode = v.code || v.name || '';
            html += '<div class="ct-kv is-good"><span class="k">Cupón ' + esc(vCode) + '</span><span class="v">' + esc(valueLabel) +
                (vCode ? ' <button type="button" class="c3-link" data-cart-action="remove-voucher" data-voucher-code="' + esc(vCode) + '">Quitar</button>' : '') +
                '</span></div>';
        });

        // Envío y facturación pueden ser direcciones distintas: se editan por separado.
        html += ctCartAddressRow('Envío', cart.delivery_address, 'delivery');
        html += ctCartAddressRow('Facturación', cart.invoice_address, 'invoice');

        if (Array.isArray(cart.fitting_services) && cart.fitting_services.length) {
            cart.fitting_services.forEach(function (f) {
                var when = [f.day, f.hour].filter(Boolean).join(' · ');
                html += '<div class="ct-kv"><span class="k">Cita</span><span class="v">' + esc(f.name || 'Servicio') + (when ? ' · ' + esc(when) : '') + '</span></div>';
            });
        }

        html += '<div class="ct-kv"><span class="k">Envío</span><span class="v mono">' + esc(totals.shipping_label || money(totals.shipping, cart.currency)) + '</span></div>';
        html += '<div class="psc-row psc-row--total"><span class="k">Total</span><span class="v">' + esc(money(totals.total, cart.currency)) + '</span></div>';
        // "Generar pedido" (cartpay.convert del puente): comprobaciones,
        // estados permitidos y confirmación se pintan aquí (renderCartpay()).
        html += '<div id="c3-cartpay" class="d-flex flex-column gap-2"></div>';

        return html;
    }

    function ctCartAddressRow(label, address, type) {
        var value = address ? [address.name, address.line, address.city].filter(Boolean).join(' · ') : '—';
        return '<div class="ct-kv"><span class="k">' + esc(label) + '</span><span class="v c3-addr-v">' + esc(value) + '</span>' +
            '<button type="button" class="c3-link" data-cart-action="change-address" data-address-type="' + type + '">Cambiar</button></div>';
    }

    function renderPsProductSearchResults(products) {
        if (!products.length) {
            return c3Empty('fa-magnifying-glass', 'Sin resultados', 'No se encontraron productos.');
        }
        return '<div class="c3-cart-lines">' + products.map(function (p) {
            var thumb = p.image
                ? '<img src="' + esc(p.image) + '" alt="" class="c3-cart-thumb" onerror="window.__ctImgFallback(this)">'
                : '<span class="c3-cart-thumb ct-row-thumb-empty"><i class="fas fa-box"></i></span>';
            var price = p.price_with_tax != null ? p.price_with_tax : p.price;
            var meta = [p.sku, price != null ? money(price) : null, p.in_stock === false ? 'sin stock' : null].filter(Boolean).join(' · ');
            return '<div class="c3-cart-line">' + thumb +
                '<span class="c3-cart-body"><span class="t">' + esc(p.name || 'Producto') + '</span><span class="s">' + esc(meta) + '</span></span>' +
                '<button type="button" class="c3-link" data-ps-product-add="' + esc(p.id) + '">Añadir</button>' +
                '</div>';
        }).join('') + '</div>';
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

    // ═══════════ Pestañas de detalle con el marcado del mockup (Contactos 360) ═══════════
    // Reutilizan los componentes psc-* del panel PrestaShop del chat
    // (prestashop-chat.css, ya cargado en la ficha) más unas pocas clases c3-*.

    var C3_MONTHS = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    function c3Pad(n) {
        return (n < 10 ? '0' : '') + n;
    }

    // "12 min" · "hoy 10:22" · "ayer 18:04" · "14 sep" · "14 sep 2025"
    function c3When(iso, withTime) {
        if (!iso) {
            return '';
        }
        var d = new Date(String(iso).replace(' ', 'T'));
        if (isNaN(d.getTime())) {
            return String(iso);
        }
        var now = new Date();
        var mins = Math.round((now - d) / 60000);
        if (mins >= 0 && mins < 60) {
            return (mins < 1 ? 1 : mins) + ' min';
        }
        var time = c3Pad(d.getHours()) + ':' + c3Pad(d.getMinutes());
        var startToday = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        var dayDiff = Math.floor((startToday - new Date(d.getFullYear(), d.getMonth(), d.getDate())) / 86400000);
        if (dayDiff === 0) {
            return 'hoy ' + time;
        }
        if (dayDiff === 1) {
            return 'ayer ' + time;
        }
        var date = c3Pad(d.getDate()) + ' ' + C3_MONTHS[d.getMonth()];
        if (d.getFullYear() !== now.getFullYear()) {
            date += ' ' + d.getFullYear();
        }
        return withTime ? (date + ' ' + time) : date;
    }

    // countId: el contador lleva id cuando otra función lo refresca suelto
    // (loadPsAddresses()/loadPsReturns()).
    function c3Card(title, count, body, countId) {
        return '<div class="psc-card c3-card">' +
            '<div class="psc-card-head">' + title +
            (count != null && count !== '' ? '<span class="psc-count"' + (countId ? ' id="' + countId + '"' : '') + '>' + esc(count) + '</span>' : '') +
            '</div>' + body + '</div>';
    }

    function c3Empty(icon, title, sub) {
        return '<div class="psc-state psc-state--compact"><i class="fas ' + icon + '"></i>' +
            '<span class="t">' + esc(title) + '</span>' + (sub ? '<span class="s">' + esc(sub) + '</span>' : '') + '</div>';
    }

    function c3Row(k, v, opts) {
        opts = opts || {};
        return '<div class="psc-row' + (opts.good ? ' psc-row--good' : '') + '"><span class="k">' + esc(k) + '</span>' +
            '<span class="v' + (opts.mono ? ' mono' : '') + '">' + esc(v) + '</span></div>';
    }

    // Tipo de un pedido PS para su chip: pendiente de pago, en curso,
    // entregado/enviado o anulado (mismo criterio que el panel del chat).
    function c3OrderKind(o) {
        var st = o.state || {};
        var name = String(st.name || o.status || '').toLowerCase();
        if (/cancel|anulad|reembols|error/.test(name)) {
            return 'blocked';
        }
        // Los flags reales del estado (paid/shipped/delivery) mandan sobre el
        // nombre: "Pedido en ERP" está pagado y enviado aunque su nombre no lo diga.
        if (st.delivery || st.shipped || /entreg|envi/.test(name)) {
            return 'closed';
        }
        if (st.paid === false || (st.paid == null && ctfOrderIsUnpaid(st.name || o.status))) {
            return 'pending';
        }
        return 'progress';
    }

    // ── Conversaciones ─────────────────────────────────────────────────
    function renderConversaciones(data) {
        data = data || {};
        var conversations = Array.isArray(data.conversations) ? data.conversations : [];

        var html = '<div class="c3-pane">';
        if (!conversations.length) {
            html += c3Empty('fa-inbox', 'Sin conversaciones', 'Este contacto no ha escrito todavía');
        }
        conversations.forEach(function (c) {
            var open = c.statusClass === 'success';
            var meta = [
                c.channelLabel,
                c.agentName || 'sin asignar',
                c.messagesCount != null ? (c.messagesCount + (c.messagesCount === 1 ? ' mensaje' : ' mensajes')) : null
            ].filter(Boolean).join(' · ');
            html += '<a href="' + esc(c.url || '#') + '" class="c3-row" target="_blank" rel="noopener">' +
                '<span class="c3-row-ic' + (open ? ' is-live' : '') + '"><i class="' + faIcon(c.channelIcon, 'comment') + '"></i></span>' +
                '<span class="c3-row-body"><span class="t">' + esc(c.subject || 'Sin asunto') + '</span>' +
                    '<span class="s">' + esc(meta) + '</span></span>' +
                '<span class="psc-tag ' + (open ? 'psc-tag--pending' : 'psc-tag--closed') + '">' + esc(c.statusLabel || (open ? 'Abierta' : 'Cerrada')) + '</span>' +
                (c.lastAt ? '<span class="c3-when">' + esc(c3When(c.lastAt, false)) + '</span>' : '') +
                '</a>';
        });
        html += '</div>';

        // Navegación del cliente en la web (sin pestaña "Chats web": los chats
        // de la web y sociales ya son las conversaciones de arriba).
        html += '<div class="c3-pane mt-3" id="chats-section"></div>';
        return html;
    }

    // ── Navegación web ─────────────────────────────────────────────────
    // Sesiones y páginas vistas del cliente identificado; los chats en sí ya
    // salen como conversaciones. Sin visitas no se pinta nada.
    function renderWebVisits(visits) {
        visits = visits || {};
        if (!(visits.pageViews > 0)) {
            return '';
        }

        var html = c3Card('Navegación en la web', null,
            '<div class="psc-card-body"><div class="c3-stats">' +
                '<div class="c3-stat"><div class="l">Sesiones</div><div class="n">' + esc(visits.sessions) + '</div></div>' +
                '<div class="c3-stat"><div class="l">Páginas vistas</div><div class="n">' + esc(visits.pageViews) + '</div></div>' +
                '<div class="c3-stat"><div class="l">Última visita</div><div class="n is-text">' + esc(ctfRelativeTime(visits.lastVisitAt) || c3When(visits.lastVisitAt)) + '</div></div>' +
            '</div></div>' +
            (Array.isArray(visits.recent) && visits.recent.length
                ? '<div class="c3-list">' + visits.recent.map(function (v) {
                    return '<div class="c3-page"><span class="u" title="' + esc(v.title || '') + '">' + esc(v.url) + '</span>' +
                        '<span class="w">' + esc(ctfRelativeTime(v.at) || c3When(v.at, true)) + '</span></div>';
                }).join('') + '</div>'
                : ''));

        return html;
    }

    // ── ERP ────────────────────────────────────────────────────────────
    function renderErp(data) {
        data = data || {};
        if (data.available === false) {
            return c3Empty('fa-plug-circle-xmark', 'ERP no disponible', 'El módulo ERP está desactivado o sin conexión.');
        }

        var cust = data.customer || {};
        var orders = Array.isArray(data.orders) ? data.orders : [];
        var invoices = Array.isArray(data.invoices) ? data.invoices : [];

        if (!(cust && (cust.found || cust.id))) {
            // Por qué no está: la última búsqueda automática (email → teléfono
            // → email de PrestaShop) no lo encontró o el ERP no contestó.
            var lookup = data.lookup || {};
            var why = data.noIdentifiers
                ? 'El contacto no tiene email ni teléfono con los que buscarlo en Gestión.'
                : (lookup.status === 'error'
                    ? 'Gestión no contestó en la última búsqueda' + (lookup.at ? (' (' + c3When(lookup.at, true) + ')') : '') + '. Prueba otra vez.'
                    : (lookup.status === 'not_found'
                        ? 'Se buscó por email, teléfono y cuenta de la tienda' + (lookup.at ? (' ' + c3When(lookup.at, true)) : '') + ' sin coincidencias.'
                        : 'Este contacto no está vinculado a un cliente del ERP.'));
            var retry = (canUpdate && !data.noIdentifiers)
                ? '<button type="button" class="psc-btn psc-btn--outline c3-erp-retry" data-ctf-erp-retry>Buscar de nuevo en Gestión</button>'
                : '';
            return c3Empty('fa-id-card', 'Sin ficha en el ERP', why + ' También puedes vincularlo desde Buscar en ERP o tienda.') + retry;
        }

        var num = function (v) { var n = parseFloat(v); return isNaN(n) ? null : n; };
        var invoiced = num(cust.balance_invoiced);
        var pending = num(cust.balance_pending);
        var limit = num(cust.credit_limit);
        var owner = ctfData.resumen && ctfData.resumen.owner;

        var html = '<div class="c3-pane c3-pane--wide">';

        html += '<div class="c3-bar">' +
            '<span class="c3-bar-ic"><i class="fas fa-database"></i></span>' +
            '<span class="c3-bar-txt">Vinculado como <b>ERP-' + esc(cust.id) + '</b> · solo lectura · <span id="ctf-erp-age">datos de ahora</span></span>' +
            '<button type="button" class="c3-bar-btn" data-contact-retry="erp">Actualizar</button>' +
            '</div>';

        // Gestión (ERP) del chat montada en la ficha (partials/_ps-chat-bridge):
        // los mismos modales que usa el agente en la conversación.
        if (erpChatOn) {
            html += '<div class="c3-erp-acts">' +
                '<button type="button" class="psc-btn psc-btn--outline" data-erp-open="orders">Pedidos</button>' +
                '<button type="button" class="psc-btn psc-btn--outline" data-erp-open="finance" data-erp-pane="balance">Saldo y facturas</button>' +
                '<button type="button" class="psc-btn psc-btn--outline" data-erp-open="finance" data-erp-pane="payments">Pagos y deudas</button>' +
                '<button type="button" class="psc-btn psc-btn--outline" data-erp-open="customer">Ficha de cliente</button>' +
                '<button type="button" class="psc-btn psc-btn--outline" data-erp-open="loyalty" data-erp-pane="points">Puntos y vales</button>' +
                '</div>';
        }

        html += '<div class="c3-grid c3-grid--erp">';

        // Columna izquierda: cartera + facturas.
        html += '<div class="c3-col">';
        if (invoiced != null || pending != null) {
            var paid = (invoiced != null && pending != null) ? Math.max(0, invoiced - pending) : null;
            var paidPct = (invoiced && paid != null) ? Math.round((paid / invoiced) * 100) : null;
            var paidW = paidPct != null ? Math.min(100, Math.round(paidPct / 5) * 5) : 0;
            html += c3Card('Cartera del cliente', 'ejercicio ' + new Date().getFullYear(),
                '<div class="psc-card-body">' +
                    '<div class="c3-money">' +
                        (invoiced != null ? '<span><span class="l">Facturado</span><span class="n is-lg">' + esc(money(invoiced)) + '</span></span>' : '') +
                        (paid != null ? '<span><span class="l">Cobrado</span><span class="n is-good">' + esc(money(paid)) + '</span></span>' : '') +
                        (pending != null ? '<span><span class="l">Pendiente</span><span class="n">' + esc(money(pending)) + '</span></span>' : '') +
                    '</div>' +
                    (paidPct != null
                        ? '<div class="c3-split"><span class="psc-w-' + paidW + '"></span><span class="is-pending psc-w-' + (100 - paidW) + '"></span></div>' +
                          '<div class="c3-legend"><span><i class="is-paid"></i>cobrado ' + paidPct + ' %</span><span><i class="is-pending"></i>pendiente ' + (100 - paidPct) + ' %</span></div>'
                        : '') +
                '</div>');
        }

        var pendingInvoices = invoices.filter(function (inv) { return /pend|abiert|vencid/i.test(String(inv.status || '')); });
        var overdueInvoices = invoices.filter(function (inv) { return /vencid/i.test(String(inv.status || '')); });
        var invHead = 'Facturas<span class="ctf-filter-pills">' +
            '<button type="button" class="ctf-filter-pill is-active" data-erp-inv-filter="all">Todas ' + invoices.length + '</button>' +
            '<button type="button" class="ctf-filter-pill" data-erp-inv-filter="pending">Pendientes ' + pendingInvoices.length + '</button>' +
            '<button type="button" class="ctf-filter-pill" data-erp-inv-filter="overdue">Vencidas ' + overdueInvoices.length + '</button>' +
            '</span>';
        var invBody;
        if (data.orders_loading && !invoices.length) {
            invBody = '<div class="psc-card-body">' + spinnerLine('Cargando facturas del ERP...') + '</div>';
        } else if (!invoices.length) {
            invBody = c3Empty('fa-file-invoice', 'Sin facturas', 'El ERP no devuelve facturas para este cliente.');
        } else {
            invBody = '<div class="c3-table-hd"><span>Documento</span><span>Fecha</span><span>Estado</span><span>Forma de pago</span></div>';
            invoices.forEach(function (inv, i) {
                var ref = [inv.series, inv.year, inv.number].filter(function (p) { return p != null && p !== ''; }).join('-') || ('#' + (inv.id || '—'));
                var isPending = pendingInvoices.indexOf(inv) !== -1;
                var isOverdue = overdueInvoices.indexOf(inv) !== -1;
                var tag = isOverdue ? 'psc-tag--blocked' : (isPending ? 'psc-tag--pending' : 'psc-tag--done');
                invBody += '<div class="c3-table-row' + (i >= 5 ? ' d-none" data-c3-more="inv' : '') + '" data-erp-inv="' + (isOverdue ? 'overdue' : (isPending ? 'pending' : 'paid')) + '">' +
                    '<span class="mono">' + esc(ref) + '</span>' +
                    '<span class="muted">' + esc(c3When(inv.date)) + '</span>' +
                    '<span>' + (inv.status ? '<span class="psc-tag ' + tag + '">' + esc(inv.status) + '</span>' : '') + '</span>' +
                    '<span class="muted">' + esc(inv.payment_method || '—') + '</span>' +
                    '</div>';
            });
            if (invoices.length > 5) {
                invBody += '<button type="button" class="c3-card-foot" data-c3-show-more="inv">Ver las ' + invoices.length + ' facturas</button>';
            }
        }
        html += c3Card(invHead, null, invBody);
        html += '</div>';

        // Columna derecha: condiciones, albaranes y nota.
        html += '<div class="c3-col">';
        var rows = '';
        if (cust.name) { rows += c3Row('Razón social', cust.name); }
        if (cust.nif) { rows += c3Row('NIF', cust.nif, { mono: true }); }
        if (cust.payment_terms) { rows += c3Row('Forma de pago', cust.payment_terms); }
        if (limit != null) { rows += c3Row('Límite de crédito', money(limit), { mono: true }); }
        if (limit != null && pending != null) { rows += c3Row('Crédito disponible', money(Math.max(0, limit - pending)), { mono: true, good: true }); }
        if (owner && owner.name) { rows += c3Row('Comercial asignado', owner.name); }
        if (cust.phone) { rows += c3Row('Teléfono', cust.phone, { mono: true }); }
        if (cust.city || cust.province) { rows += c3Row('Población', [cust.city, cust.province].filter(Boolean).join(' · ')); }
        html += c3Card('Condiciones comerciales', null, '<div class="psc-card-body">' + rows + '</div>');

        var ordBody;
        if (data.orders_loading) {
            ordBody = '<div class="psc-card-body" data-erp-orders-pending>' + spinnerLine('Cargando pedidos del ERP...') + '</div>';
        } else if (!orders.length) {
            ordBody = c3Empty('fa-truck', 'Sin albaranes', 'No hay pedidos ni entregas en gestión.');
        } else {
            ordBody = '<div class="psc-card-body">' + orders.slice(0, 8).map(function (o) {
                var label = erpStatusLabel(o.status);
                var done = /entreg|servid|enviad/i.test(label);
                var cancelled = /cancel/i.test(label);
                var openAttr = (erpChatOn && o.id != null) ? (' role="button" tabindex="0" data-erp-order-open="' + esc(o.id) + '"') : '';
                return '<div class="c3-delivery' + (!done && !cancelled ? ' is-open' : '') + (openAttr ? ' is-clickable' : '') + '"' + openAttr + '>' +
                    '<span class="c3-delivery-body"><span class="ref">' + esc(o.number ? ('#' + o.number) : ('#' + (o.id || '—'))) + '</span>' +
                    '<span class="t">' + esc(label + (o.date ? ' · ' + c3When(o.date) : '')) + '</span></span>' +
                    '<span class="psc-tag ' + (cancelled ? 'psc-tag--blocked' : (done ? 'psc-tag--done' : 'psc-tag--pending')) + '">' + esc(done ? 'Cerrado' : (cancelled ? 'Anulado' : 'Abierto')) + '</span>' +
                    '</div>';
            }).join('') + '</div>';
        }
        html += c3Card('Albaranes y entregas', orders.length || null, ordBody);

        html += '<div class="c3-info">El ERP es la fuente de la verdad para importes y condiciones: aquí nada es editable.</div>';
        html += '</div>';

        html += '</div></div>';
        return html;
    }

    // ── PrestaShop ─────────────────────────────────────────────────────
    function renderPrestashop(data) {
        data = data || {};
        if (data.available === false) {
            return c3Empty('fa-plug-circle-xmark', 'PrestaShop no disponible', 'El módulo PrestaShop está desactivado.');
        }

        var orders = Array.isArray(data.orders) ? data.orders : [];
        var carts = Array.isArray(data.carts) ? data.carts : [];
        var vouchers = Array.isArray(data.vouchers) ? data.vouchers : [];
        var customer = data.customer || {};
        var addresses = Array.isArray(data.addresses) ? data.addresses : [];
        var returns = Array.isArray(data.returns) ? data.returns : [];
        var wishlist = Array.isArray(data.wishlist) ? data.wishlist : [];
        var refunds = Array.isArray(data.refunds) ? data.refunds : [];
        // openPsAddressForm() lee esta caché al editar una dirección.
        psAddressesCache = addresses;

        if (!orders.length && !carts.length && !vouchers.length && !customer.found) {
            return c3Empty('fa-bag-shopping', 'Sin ficha en PrestaShop', 'Este contacto no tiene pedidos, carritos ni cupones en la tienda.');
        }

        var html = '<div class="c3-pane c3-pane--wide"><div class="c3-grid">';

        // Izquierda: carrito en vivo + pedidos.
        html += '<div class="c3-col">';
        var cart = carts[0];
        var count = cart ? (cart.products_count || (Array.isArray(cart.items) ? cart.items.length : 0)) : 0;
        if (cart && count) {
            var units = (cart.items || []).reduce(function (sum, it) { return sum + (parseInt(it.quantity, 10) || 0); }, 0);
            var cartVouchers = Array.isArray(cart.vouchers) ? cart.vouchers : [];
            var sub = [count + (count === 1 ? ' artículo' : ' artículos')];
            if (units) { sub.push(units + (units === 1 ? ' unidad' : ' unidades')); }
            if (cartVouchers.length) { sub.push('cupón ' + cartVouchers.map(function (v) { return v.code || v.name; }).filter(Boolean).join(', ') + ' aplicado'); }
            var total = parseFloat(cart.totals && cart.totals.total) || 0;
            var avg = (customer.ltv && customer.orders_count) ? customer.ltv / customer.orders_count : 0;
            var pct = avg > 0 ? Math.round((total / avg) * 100) : null;
            html += '<div class="psc-live">' +
                '<div class="psc-live-hd"><span class="t">En vivo</span><span class="id">CART-#' + esc(cart.id || '') + '</span><span class="dot"></span></div>' +
                '<div class="psc-live-body">' +
                    '<span class="psc-live-amount">' + esc(money(total, cart.currency)) + '<span class="c3-live-when">' + esc(ctfRelativeTime(cart.updated_at) || '') + '</span></span>' +
                    '<span class="psc-live-sub">' + esc(sub.join(' · ')) + '</span>' +
                    (pct !== null
                        ? '<span class="psc-live-bar"><span class="psc-w-' + Math.min(100, Math.round(pct / 5) * 5) + '"></span></span>' +
                          '<span class="psc-live-cap">' + pct + ' % del valor medio del cliente</span>'
                        : '') +
                '</div>' +
                '<div class="psc-live-acts c3-live-acts-1"><button type="button" class="is-primary" data-ps-cart-id="' + esc(cart.id) + '">Ver</button></div>' +
                '</div>';
        } else {
            html += '<div class="psc-live-hint psc-live-hint--muted"><span class="dot"></span><span class="txt">Sin carrito activo en este momento</span></div>';
        }

        var ordersTotal = customer.orders_count || orders.length;
        var ordBody;
        if (!orders.length) {
            ordBody = c3Empty('fa-box', 'Sin pedidos', 'Este cliente no ha comprado todavía');
        } else {
            ordBody = orders.map(function (o, i) {
                var kind = c3OrderKind(o);
                var tag = { pending: 'psc-tag--pending', progress: 'psc-tag--progress', closed: 'psc-tag--closed', blocked: 'psc-tag--blocked' }[kind];
                var total = (o.totals && o.totals.total != null) ? o.totals.total : o.total;
                return '<div class="c3-ord' + (i >= 5 ? ' d-none" data-c3-more="ord' : '') + '" data-ps-order-id="' + esc(o.id) + '" role="button">' +
                    '<span class="c3-ord-body">' +
                        '<span class="c3-ord-top"><span class="ref">#' + esc(o.reference || o.id) + '</span>' +
                        '<span class="psc-tag ' + tag + '">' + esc((o.state && o.state.name) || o.status || 'Pedido') + '</span></span>' +
                        '<span class="t">' + esc(ctfOrderLinesSummary(o) || c3When(o.placed_at)) + '</span>' +
                    '</span>' +
                    '<span class="amt">' + esc(money(total, o.currency)) + '</span>' +
                    '</div>';
            }).join('');
            if (orders.length > 5) {
                ordBody += '<button type="button" class="c3-card-foot" data-c3-show-more="ord">Ver los ' + ordersTotal + ' pedidos</button>';
            }
        }
        html += c3Card('Pedidos', ordersTotal, ordBody);
        html += '</div>';

        // Derecha: devoluciones, cupones y reembolsos, direcciones.
        html += '<div class="c3-col">';
        html += c3Card('Devoluciones', returns.length,
            '<div class="psc-card-body" id="ps-returns-list">' + renderPsReturnsList(returns) + '</div>', 'ps-returns-count');

        var vchHtml = vouchers.map(function (v) {
            var value = v.reduction_percent > 0 ? ('−' + v.reduction_percent + ' %')
                : (v.reduction_amount > 0 ? ('−' + money(v.reduction_amount)) : (v.free_shipping ? 'Envío gratis' : ''));
            var cond = [];
            if (v.date_to) { cond.push('hasta ' + c3When(v.date_to)); }
            if (v.minimum_amount > 0) { cond.push('mínimo ' + money(v.minimum_amount)); }
            var usable = !v.expired && v.active !== false;
            return '<div class="psc-vch ' + (usable ? 'psc-vch--available' : 'psc-vch--spent') + '">' +
                '<div class="psc-vch-hd"><span class="c3-vch-code">' + esc(v.code || v.name || 'Cupón') + '</span>' +
                (value ? '<span class="c3-vch-val">' + esc(value) + '</span>' : '') + '</div>' +
                (cond.length ? '<div class="psc-vch-desc">' + esc(cond.join(' · ')) + '</div>' : '') +
                (v.id != null && String($root.data('ps-can-voucher-edit')) === '1' && typeof window.openPsVoucherEdit === 'function'
                    ? '<div class="psc-vch-acts"><button type="button" data-c3-voucher-edit="' + esc(v.id) + '">Editar cupón</button></div>'
                    : '') +
                '</div>';
        }).join('');
        var inUse = (cart && Array.isArray(cart.vouchers) ? cart.vouchers : []).map(function (v) { return v.code || v.name; }).filter(Boolean);
        var refunded = refunds.reduce(function (sum, r) { return sum + (parseFloat(r.amount) || 0); }, 0);
        var cvBody = vchHtml +
            (inUse.length ? c3Row('Cupón en uso', inUse.join(', '), { mono: true }) : '') +
            c3Row('Reembolsado', money(refunded), { mono: true, good: refunded > 0 }) +
            (wishlist.length && typeof window.openPsWishlistSend === 'function'
                ? '<button type="button" class="psc-row c3-row-btn" data-c3-ps-call="openPsWishlistSend"><span class="k">Lista de deseos</span><span class="v">' + wishlist.length + (wishlist.length === 1 ? ' producto' : ' productos') + ' ›</span></button>'
                : c3Row('Lista de deseos', wishlist.length + (wishlist.length === 1 ? ' producto' : ' productos')));
        var promoActs = (String($root.data('ps-can-voucher-create')) === '1' && typeof window.openPsVoucherCreate === 'function' ? '<button type="button" class="c3-link" data-c3-ps-call="openPsVoucherCreate">Crear vale</button>' : '') +
            (typeof window.openPsVouchersShop === 'function' ? '<button type="button" class="c3-link" data-c3-ps-call="openPsVouchersShop">Promociones</button>' : '');
        html += c3Card('Cupones y reembolsos' + (promoActs ? '<span class="c3-head-acts">' + promoActs + '</span>' : ''), null, '<div class="psc-card-body">' + cvBody + '</div>');

        // Direcciones: no está en el mockup, pero el carrito y los pedidos
        // cambian de dirección desde aquí (selector + alta/edición).
        html += c3Card('Direcciones', addresses.length,
            '<div class="psc-card-body"><div id="ps-addresses-list">' + renderPsAddressesList(addresses) + '</div>' +
            '<button type="button" class="psc-btn psc-btn--dashed" id="ps-address-add-btn">Añadir dirección</button></div>', 'ps-addresses-count');

        // Cambios de producto gestionados por REVER: se piden aparte
        // (loadPsRever) y la tarjeta solo aparece si hay alguno.
        html += '<div id="ps-rever-card"></div>';

        var messages = Array.isArray(data.messages) ? data.messages : [];
        if (messages.length) {
            html += c3Card('Mensajes en la tienda', messages.length, renderPsMessagesList(messages));
        }

        html += '</div>';
        html += '</div></div>';
        return html;
    }

    // ── Cambios con REVER (extensión rever de HelpdeskPrestashop) ──────
    var REVER_STATE_TAG = {
        exchange_hold: 'psc-tag--pending', paid: 'psc-tag--progress', shipped: 'psc-tag--done',
        delivered: 'psc-tag--done', canceled: 'psc-tag--closed', return_started: 'psc-tag--pending'
    };

    function loadPsRever() {
        var url = String($root.data('ps-rever-url') || '');
        if (!url || String($root.data('ps-can-orders')) !== '1') {
            return;
        }
        $.ajax({ url: url, method: 'GET', headers: { 'Accept': 'application/json' } }).done(function (r) {
            var d = (r && r.success && r.data) ? r.data : {};
            var ex = Array.isArray(d.exchanges) ? d.exchanges : [];
            var only = (Array.isArray(d.processes) ? d.processes : []).filter(function (p) { return !p.exchange_order_id; });
            if (!ex.length && !only.length) {
                return;
            }
            var rows = ex.map(function (e) {
                var eo = e.exchange_order || {};
                var oo = e.original_order || null;
                var lines = (eo.lines || []).map(function (l) { return (parseInt(l.quantity, 10) || 1) + '× ' + (l.name || 'Producto'); }).join(', ');
                var meta = [eo.date ? c3When(eo.date, false) : null, oo ? ('del pedido #' + (oo.reference || oo.id)) : null, lines || null].filter(Boolean).join(' · ');
                return '<div class="c3-row' + (eo.id ? ' is-clickable" role="button" tabindex="0" data-ps-order-id="' + esc(eo.id) + '"' : '"') + '>' +
                    '<span class="c3-row-body"><span class="t">Cambio #' + esc(eo.reference || eo.id || '—') + '</span>' +
                    (meta ? '<span class="s">' + esc(meta) + '</span>' : '') + '</span>' +
                    '<span class="psc-tag ' + (REVER_STATE_TAG[eo.state_kind] || 'psc-tag--closed') + '">' + esc(eo.state_name || '—') + '</span>' +
                    '</div>';
            }).concat(only.map(function (p) {
                return '<div class="c3-row"><span class="c3-row-body"><span class="t">Devolución sin cambio</span>' +
                    '<span class="s">' + esc([p.id, p.started_at ? ('iniciada ' + c3When(p.started_at, false)) : null].filter(Boolean).join(' · ')) + '</span></span>' +
                    '<span class="psc-tag ' + (p.status === 'finished' ? 'psc-tag--done' : 'psc-tag--pending') + '">' + (p.status === 'finished' ? 'Completada' : 'En curso') + '</span></div>';
            }));
            $('#ps-rever-card').html(c3Card('Cambios con Rever', rows.length, '<div class="c3-list">' + rows.join('') + '</div>'));
        });
    }

    function renderPsReturnsList(returns) {
        if (!returns.length) {
            return c3Empty('fa-rotate-left', 'Sin devoluciones', 'Este cliente no tiene devoluciones registradas.');
        }
        return returns.map(function (r) {
            var s = String(r.state_name || '').toLowerCase();
            var blocked = /denegad|cancelad|rechazad/.test(s);
            var done = /complet|recibid|reembols/.test(s);
            var items = Array.isArray(r.items) ? r.items : [];
            var units = items.reduce(function (sum, it) { return sum + (parseInt(it.quantity, 10) || 1); }, 0);
            var meta = [c3When(r.created_at), 'pedido #' + (r.order_reference || r.order_id || '—'), units ? (units + (units === 1 ? ' producto' : ' productos')) : null].filter(Boolean).join(' · ');
            return '<div class="psc-rma' + (blocked ? ' psc-rma--blocked' : '') + '">' +
                '<div class="psc-rma-hd"><span class="psc-rma-ref">RMA-' + esc(String(r.id).padStart(6, '0')) + '</span>' +
                '<span class="psc-tag ' + (blocked ? 'psc-tag--blocked' : (done ? 'psc-tag--progress' : 'psc-tag--pending')) + '">' + esc(r.state_name || 'Pendiente') + '</span></div>' +
                '<div class="psc-rma-meta">' + esc(meta) + '</div>' +
                (r.reason ? '<div class="psc-rma-reason"><span class="lbl">Motivo · </span>' + esc(r.reason) + '</div>' : '') +
                '<div class="psc-rma-acts">' +
                    '<button type="button" data-rma-id="' + esc(r.id) + '">Resolver</button>' +
                    (r.order_id ? '<button type="button" data-ps-order-id="' + esc(r.order_id) + '">Ver pedido</button>' : '') +
                '</div>' +
                '</div>';
        }).join('');
    }

    // ── Actividad ──────────────────────────────────────────────────────
    function renderActividad(data) {
        data = data || {};
        var timeline = Array.isArray(data.timeline) ? data.timeline : [];
        if (!timeline.length) {
            return c3Empty('fa-timeline', 'Sin actividad', 'No hay eventos registrados para este contacto.');
        }

        var group = function (ev) {
            if (ev.source === 'ficha') { return 'ficha'; }
            if (ev.source === 'erp' || ev.source === 'prestashop') { return 'pedidos'; }
            return 'mensajes';
        };

        var html = '<div class="c3-pane">' +
            '<div class="c3-pills">' +
                '<button type="button" class="ctf-filter-pill is-active" data-act-filter="all">Todo</button>' +
                '<button type="button" class="ctf-filter-pill" data-act-filter="mensajes">Mensajes</button>' +
                '<button type="button" class="ctf-filter-pill" data-act-filter="pedidos">Pedidos</button>' +
                '<button type="button" class="ctf-filter-pill" data-act-filter="ficha">Cambios de ficha</button>' +
            '</div>';
        timeline.forEach(function (ev) {
            var g = group(ev);
            var sub = [c3When(ev.at, true), ev.detail && String(ev.title || '').indexOf(ev.detail) === -1 ? ev.detail : null].filter(Boolean).join(' · ');
            html += '<div class="c3-act" data-act-group="' + g + '">' +
                '<span class="c3-act-ic' + (g === 'ficha' ? ' is-live' : '') + '"><i class="' + faIcon(ev.icon, 'circle') + '"></i></span>' +
                '<span class="c3-act-body"><span class="t">' + esc(ev.title || '') + '</span><span class="s">' + esc(sub) + '</span></span>' +
                '</div>';
        });
        html += '</div>';
        return html;
    }

    // ── Tickets ────────────────────────────────────────────────────────
    function renderTickets(data) {
        data = data || {};
        if (data.available === false) {
            return c3Empty('fa-ticket', 'Tickets no disponibles', 'El módulo de tickets está desactivado.');
        }
        var tickets = Array.isArray(data.tickets) ? data.tickets : [];
        var html = '<div class="c3-pane">';
        tickets.forEach(function (t) {
            var open = t.statusClass === 'success';
            var sub = [t.agentName || 'sin asignar'];
            if (open && t.slaBadge && t.slaBadge.label) { sub.push(t.slaBadge.label); }
            if (!open && t.closedAt) { sub.push('cerrado el ' + c3When(t.closedAt)); }
            if (open && !t.slaBadge && t.createdAt) { sub.push('abierto ' + c3When(t.createdAt)); }
            html += '<div class="c3-row">' +
                '<span class="c3-row-body">' +
                    '<span class="c3-ord-top"><span class="ref">' + esc(t.number || ('#' + t.id)) + '</span>' +
                    '<span class="psc-tag ' + (open ? 'psc-tag--pending' : 'psc-tag--closed') + '">' + esc(t.status || '') + '</span></span>' +
                    '<span class="t mt-1">' + esc(t.subject || 'Sin asunto') + '</span>' +
                    '<span class="s is-faint">' + esc(sub.join(' · ')) + '</span>' +
                '</span>' +
                (t.url ? '<a class="c3-link" href="' + esc(t.url) + '" target="_blank" rel="noopener">Abrir</a>' : '') +
                '</div>';
        });
        if (!tickets.length) {
            html += c3Empty('fa-ticket', 'Sin tickets', 'Este contacto no tiene tickets registrados.');
        }
        html += '<button type="button" class="c3-add" data-bs-toggle="modal" data-bs-target="#contact-ticket-modal">Crear ticket para este contacto</button>';
        html += '</div>';
        return html;
    }

    // ── Resumen (pestaña de la versión A) ──────────────────────────────
    // Se pinta con lo que ya trajeron resumen/actividad/erp/prestashop: no
    // hace petición propia y se repinta cada vez que llega una de esas fuentes.
    function renderCtfPerfil() {
        var $pane = $('#pane-perfil');
        if (!$pane.length) {
            return;
        }
        if (!ctfLoaded.resumen) {
            $pane.html(skeleton(3));
            return;
        }

        var resumen = ctfData.resumen || {};
        var stats = resumen.stats || {};
        var factors = Array.isArray(stats.healthFactors) ? stats.healthFactors : [];
        var hasScore = stats.healthScore != null && (stats.totalConversations || 0) > 0;
        var integrations = Array.isArray(resumen.integrations) ? resumen.integrations : [];
        var byPlatform = {};
        integrations.forEach(function (it) { byPlatform[it.platform] = it; });

        // Rail: desglose de la salud (reglas reales de CustomerInsightsService).
        var rail = '<div class="c3-rail"><div class="c3-eyebrow">Desglose de la salud</div>';
        if (hasScore) {
            rail += '<div class="c3-score"><span class="n">' + esc(stats.healthScore) + '</span><span class="s">de 100 · base 50</span></div>';
            rail += '<div class="c3-factors">' + factors.map(function (f) {
                var pts = parseInt(f.points, 10) || 0;
                var w = f.max ? Math.min(100, Math.round((Math.abs(pts) / f.max) * 100 / 5) * 5) : 0;
                return '<div class="c3-factor">' +
                    '<div class="c3-factor-hd"><span class="l">' + esc(f.label) + '</span>' +
                    '<span class="p' + (pts > 0 ? ' is-good' : '') + '">' + (pts > 0 ? '+' : (pts < 0 ? '−' : '')) + Math.abs(pts) + '</span></div>' +
                    '<div class="c3-factor-track"><span class="psc-w-' + w + (pts < 0 ? ' is-neg' : '') + '"></span></div>' +
                    '</div>';
            }).join('') + '</div>';
        } else {
            rail += '<div class="c3-rail-empty">Sin conversaciones todavía: la salud no se calcula sobre la nada.</div>';
        }
        rail += '</div>';

        // Recorrido del cliente: los 4 eventos más recientes del historial.
        var events = ctfHistoryEvents().slice(0, 4);
        var sourceLabels = { prestashop: 'PrestaShop', erp: 'ERP', ficha: 'Ficha', helpdesk: 'Conversación' };
        var journey = ctfLoaded.actividad
            ? (events.length
                ? '<div class="psc-card-body c3-journey">' + events.map(function (e, i) {
                    return '<div class="c3-journey-item' + (i === 0 ? ' is-fresh' : '') + '"><span class="dot"></span>' +
                        '<span class="b"><span class="t">' + esc(e.title) + '</span>' +
                        '<span class="s">' + esc([sourceLabels[e.source], c3When(e.at, false)].filter(Boolean).join(' · ')) + '</span></span></div>';
                }).join('') + '</div>'
                : c3Empty('fa-timeline', 'Sin actividad', 'No hay eventos registrados para este contacto.'))
            : '<div class="psc-card-body">' + skeleton(2) + '</div>';

        // Atributos y etiquetas.
        var erpCust = ctfData.erp && ctfData.erp.customer ? ctfData.erp.customer : null;
        var attrs = '';
        if (erpCust && erpCust.nif) { attrs += c3Row('NIF', erpCust.nif, { mono: true }); }
        if (byPlatform.erp && byPlatform.erp.externalId) { attrs += c3Row('Nº de cliente ERP', 'ERP-' + byPlatform.erp.externalId, { mono: true }); }
        if (byPlatform.prestashop && byPlatform.prestashop.externalId) { attrs += c3Row('Nº de cliente PrestaShop', 'PS-' + byPlatform.prestashop.externalId, { mono: true }); }
        (Array.isArray(resumen.secondaryEmails) ? resumen.secondaryEmails : []).forEach(function (e) {
            attrs += c3Row('Email secundario', e, { mono: true });
        });
                if (resumen.language || resumen.timezone) { attrs += c3Row('Idioma · zona', [resumen.language, resumen.timezone].filter(Boolean).join(' · ')); }
        if (resumen.owner && resumen.owner.name) { attrs += c3Row('Responsable', resumen.owner.name); }
        // customAttributes: mapa clave → valor (getAllCustomAttributes()).
        var custom = resumen.customAttributes && typeof resumen.customAttributes === 'object' ? resumen.customAttributes : {};
        Object.keys(custom).slice(0, 6).forEach(function (key) {
            var v = custom[key];
            if (v != null && v !== '' && typeof v !== 'object') {
                var label = String(key).replace(/[_-]+/g, ' ');
                attrs += c3Row(label.charAt(0).toUpperCase() + label.slice(1), String(v));
            }
        });
        var tags = Array.isArray(resumen.tags) ? resumen.tags : [];
        attrs += '<div class="c3-tags">' + tags.map(function (t, i) {
            return '<span class="c3-tag' + (i === 0 ? ' is-green' : '') + '">' + esc(t.name) + '</span>';
        }).join('') + (canUpdate ? '<button type="button" class="c3-tag is-add" data-contact-edit-trigger>+ etiqueta</button>' : '') + '</div>';

        // Integraciones: vinculada → Ver (abre su pestaña); si no → Vincular.
        var intHtml = [
            { key: 'prestashop', label: 'PrestaShop', icon: 'fa-cart-shopping', prefix: 'PS-', tab: 'prestashop' },
            { key: 'erp', label: 'ERP', icon: 'fa-database', prefix: 'ERP-', tab: 'erp' }
        ].map(function (d, i) {
            var it = byPlatform[d.key];
            var linked = it && it.connected;
            var cls = linked ? (i === 0 ? ' is-live' : '') : ' is-off';
            var action = linked
                ? ($('#pane-' + d.tab).length ? '<button type="button" class="c3-link" data-ctf-open-tab="' + d.tab + '">Ver</button>' : '')
                : (canUpdate && $('.external-link-trigger').length ? '<button type="button" class="c3-link is-muted" data-ctf-link-platform>Vincular</button>' : '');
            return '<div class="c3-int' + cls + '"><span class="ic"><i class="fas ' + d.icon + '"></i></span>' +
                '<span class="b"><span class="t">' + d.label + '</span>' +
                '<span class="s">' + (linked ? esc(it.externalId ? d.prefix + it.externalId : 'vinculado') : 'Sin vincular') + '</span></span>' +
                action + '</div>';
        }).join('');

        // Acciones: solo las que el agente puede ejecutar (se ocultan, no se deshabilitan).
        var acts = '';
        if (canUpdate && ctfData.tickets && ctfData.tickets.available) {
            acts += '<button type="button" class="c3-act-btn" data-bs-toggle="modal" data-bs-target="#contact-ticket-modal">Crear ticket</button>';
        }
        if (canUpdate) {
            acts += '<button type="button" class="c3-act-btn" data-contact-cart-trigger>Carrito asistido</button>';
        }
        if ($('#contact-merge-btn').length) {
            acts += '<button type="button" class="c3-act-btn" data-ctf-proxy="#contact-merge-btn">Fusionar duplicado</button>';
        }
        if ($('#contact-ban-btn').length) {
            acts += '<button type="button" class="c3-act-btn is-dark" data-ctf-proxy="#contact-ban-btn">Bloquear contacto</button>';
        } else if ($('#contact-unban-btn').length) {
            acts += '<button type="button" class="c3-act-btn is-dark" data-ctf-proxy="#contact-unban-btn">Desbloquear contacto</button>';
        }

        var html = '<div class="c3-resumen">' + rail +
            '<div class="c3-grid c3-grid--resumen">' +
                '<div class="c3-col">' +
                    c3Card('Recorrido del cliente', null, journey) +
                    c3Card('Atributos y etiquetas', null, '<div class="psc-card-body">' + attrs + '</div>') +
                '</div>' +
                '<div class="c3-col">' +
                    c3Card('Integraciones', null, '<div class="psc-card-body">' + intHtml + '</div>') +
                    (acts ? c3Card('Acciones', null, '<div class="psc-card-body">' + acts + '</div>') : '') +
                '</div>' +
            '</div></div>';

        $pane.html(html).data('loaded', '1');
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
            $section.html(renderWebVisits(payload.visits));
        }).fail(function () {
            $section.html(errorState());
        });
    }

    function loadTab(tab, force) {
        var $pane = $('#pane-' + tab);
        var hasPane = $pane.length > 0;

        // Ficha B (versión sin pestañas): las fuentes en CTF_TABS ya no tienen
        // un pane visible, pero el fetch se sigue disparando igual — el pane
        // sigue existiendo oculto como "data holder" (ps-carts/ps-external-id,
        // ver más abajo) y el hook onCtfTabLoaded() pinta las secciones reales.
        if (!hasPane && CTF_TABS.indexOf(tab) === -1) {
            return;
        }
        if (hasPane) {
            if (!force && String($pane.data('loaded')) === '1') {
                return;
            }
            if (force) {
                $pane.html(skeleton());
            }
            $pane.data('loaded', '1');
        }

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

            if (hasPane) {
                var renderer = RENDERERS[tab];
                $pane.html(renderer ? renderer(payload) : emptyState('fa-circle-info', 'Sin contenido', ''));
            }

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
                erpRetryCount = 0;
                scheduleErpRetry();
            }
            if (tab === 'prestashop' && hasPane) {
                loadPsRever();
            }
            if (tab === 'prestashop') {
                // El detalle de pedido/carrito necesita el id real del cliente
                // en PrestaShop (ver ContactAggregatorService::prestashop())
                // para que el bridge resuelva la propiedad aunque el email del
                // contacto no coincida con el de su cuenta PS. Se guarda en
                // #pane-prestashop (oculto en la ficha B) porque
                // findCachedCart() sigue leyendo de ahí.
                $('#pane-prestashop').data('ps-external-id', (payload && payload.external_id != null) ? payload.external_id : null);
                // Los carritos ya llegan completos (líneas, totales, cupones)
                // en el mismo payload — se guardan para abrir el modal de
                // edición sin una petición adicional.
                $('#pane-prestashop').data('ps-carts', (payload && Array.isArray(payload.carts)) ? payload.carts : []);
                // Direcciones/devoluciones/mensajes/lista de deseos/reembolsos
                // ya se pintaron síncronamente dentro de renderPrestashop() —
                // viajan en el mismo payload (ver ContactAggregatorService::prestashop()).
                // loadPsAddresses()/loadPsReturns()/etc. solo hacen falta para
                // refrescar UNA tarjeta suelta tras una escritura puntual.
            }

            onCtfTabLoaded(tab, payload);
        }).fail(function () {
            if (hasPane) {
                $pane.data('loaded', '0');
                $pane.html(errorState(tab));
            }
            onCtfTabLoaded(tab, null);
        });
    }

    // ─────────────────────────────────────────────────── direcciones PS ──

    function loadPsAddresses(skip) {
        var $container = $('#ps-addresses-list');
        if (!$container.length || !psAddressesUrl) {
            return;
        }
        if (skip) {
            $container.html(emptyState('fa-location-dot', 'Sin direcciones', ''));
            return;
        }

        $.ajax({
            url: psAddressesUrl,
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        }).done(function (resp) {
            psAddressesCache = (resp && (resp.addresses || resp.data)) || [];
            $container.html(renderPsAddressesList(psAddressesCache));
            // El bridge tope a 20 (ver alsernet_customer_addresses) — si vienen
            // exactamente 20, probablemente haya más sin mostrar.
            var count = psAddressesCache.length;
            $('#ps-addresses-count').text(count + (count >= 20 ? '+' : ''));
        }).fail(function () {
            $container.html(errorState());
        });
    }

    // ──────────────────────────────────────────────── devoluciones PS ──

    function loadPsReturns(skip) {
        var $container = $('#ps-returns-list');
        if (!$container.length || !psReturnsUrl) {
            return;
        }
        if (skip) {
            $container.html(emptyState('fa-rotate-left', 'Sin devoluciones', ''));
            return;
        }

        $.ajax({
            url: psReturnsUrl,
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        }).done(function (resp) {
            var returns = (resp && resp.returns) || [];
            $container.html(renderPsReturnsList(returns));
            $('#ps-returns-count').text(returns.length);
        }).fail(function () {
            $container.html(errorState());
        });
    }


    // ──────────────────────────────────────────────────── mensajes PS ──
    // Sin loadPsMessages(): los mensajes son de solo lectura y ya se pintan
    // síncronamente en renderPrestashop() — no hay ninguna escritura en esta
    // pantalla que necesite refrescar solo esta tarjeta (a diferencia de
    // Direcciones/Devoluciones, ver loadPsAddresses()/loadPsReturns()).

    // Mensajes que el cliente dejó en la tienda (formulario de contacto /
    // mensajes de pedido de PrestaShop): vienen en el contexto agregado.
    function psMessagePreview(m) {
        var preview = String(m.message || '').replace(/<[^>]*>/g, '').trim();
        return preview.length > 110 ? (preview.substring(0, 110) + '…') : preview;
    }

    function renderPsMessagesList(messages) {
        return '<div class="c3-list">' + messages.map(function (m) {
            var preview = psMessagePreview(m);
            return '<div class="c3-row">' +
                '<span class="c3-row-body"><span class="t">' + esc(m.subject || 'Mensaje en la tienda') + '</span>' +
                (preview ? '<span class="s">' + esc(preview) + '</span>' : '') + '</span>' +
                (m.status ? '<span class="psc-tag psc-tag--closed">' + esc(m.status) + '</span>' : '') +
                (m.created_at ? '<span class="c3-when">' + esc(c3When(m.created_at, false)) + '</span>' : '') +
                '</div>';
        }).join('') + '</div>';
    }

    // ─────────────────────────────────────────────── lista de deseos PS ──
    // Sin loadPsWishlist(): de solo lectura, se pinta síncronamente en
    // renderPrestashop() (ver comentario en la sección de mensajes).

    function renderPsWishlistList(items) {
        if (!items.length) {
            return emptyState('fa-heart', 'Sin lista de deseos', 'Este cliente no tiene productos guardados.');
        }

        var html = '<div class="ct-row-list">';
        items.forEach(function (p) {
            var thumb = p.image
                ? '<img src="' + esc(p.image) + '" alt="" class="ct-row-thumb" onerror="window.__ctImgFallback(this)">'
                : '<span class="ct-row-thumb ct-row-thumb-empty"><i class="fas fa-box"></i></span>';
            html += '<div class="ct-row">' +
                thumb +
                '<span class="ct-row-body">' + esc(p.name || 'Producto') +
                    (p.reference ? '<span class="ct-meta"> · Ref: ' + esc(p.reference) + '</span>' : '') +
                    (p.in_stock === false ? '<span class="ct-meta"> · Sin stock</span>' : '') +
                '</span>' +
                (p.has_discount && p.price_original != null ? '<span class="ct-row-price-original ct-mono">' + esc(money(p.price_original)) + '</span>' : '') +
                (p.price_with_tax != null ? '<span class="ct-row-price ct-mono">' + esc(money(p.price_with_tax)) + '</span>' : '') +
                (p.added_at ? '<span class="ct-row-date ct-mono">' + esc(fmtDate(p.added_at)) + '</span>' : '') +
                '</div>';
        });
        html += '</div>';

        return html;
    }

    // ────────────────────────────────────────────────── reembolsos PS ──
    // Sin loadPsRefunds(): de solo lectura, se pinta síncronamente en
    // renderPrestashop() (ver comentario en la sección de mensajes).

    function renderPsRefundsList(refunds) {
        if (!refunds.length) {
            return emptyState('fa-money-bill-transfer', 'Sin reembolsos', 'Este cliente no tiene reembolsos registrados.');
        }

        var html = '<div class="ct-row-list">';
        refunds.forEach(function (r) {
            html += '<div class="ct-row">' +
                '<div class="ct-row-body">' +
                    '<div class="ct-row-title">' + esc(r.order_reference || (r.order_id ? ('Pedido #' + r.order_id) : 'Reembolso')) + '</div>' +
                '</div>' +
                (r.partial ? '<span class="ct-pill ct-pill-muted">Parcial</span>' : '') +
                (r.amount != null ? '<span class="ct-row-price ct-mono">' + esc(money(r.amount)) + '</span>' : '') +
                (r.created_at ? '<span class="ct-row-date ct-mono">' + esc(fmtDate(r.created_at)) + '</span>' : '') +
                '</div>';
        });
        html += '</div>';

        return html;
    }

    function loadPsAddressStates(selectedId) {
        var $select = $('#ps-address-state-select');
        if (!psCountryStatesUrl) {
            return;
        }
        $select.html('<option value="">Cargando...</option>');
        $.ajax({
            url: psCountryStatesUrl,
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        }).done(function (resp) {
            var states = (resp && resp.states) || [];
            var html = '<option value="">Selecciona...</option>';
            states.forEach(function (s) {
                var selected = selectedId != null && String(selectedId) === String(s.id) ? ' selected' : '';
                html += '<option value="' + esc(s.id) + '"' + selected + '>' + esc(s.name) + '</option>';
            });
            $select.html(html);
        }).fail(function () {
            $select.html('<option value="">Error al cargar</option>');
        });
    }

    function openPsAddressForm(address) {
        psAddressEditingId = address ? address.id : null;
        var $form = $('#ps-address-form');
        $form[0].reset();
        $('#psAddressFormModalLabel').text(address ? 'Editar dirección' : 'Añadir dirección');

        if (address) {
            $form.find('[name="alias"]').val(address.alias || '');
            $form.find('[name="firstname"]').val(address.firstname || '');
            $form.find('[name="lastname"]').val(address.lastname || '');
            $form.find('[name="company"]').val(address.company || '');
            $form.find('[name="address1"]').val(address.address1 || '');
            $form.find('[name="address2"]').val(address.address2 || '');
            $form.find('[name="postcode"]').val(address.postcode || '');
            $form.find('[name="city"]').val(address.city || '');
            $form.find('[name="phone"]').val(address.phone || '');
        }

        loadPsAddressStates(address ? address.id_state : null);
        $('#ps-address-form-modal').modal('show');
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
    var erpRetryCount = 0;
    var ERP_RETRY_MAX = 6;

    // Los pedidos del ERP llegan en segundo plano (escaneo de Oracle): se
    // vuelve a pedir la pestaña hasta que estén, con espera creciente. Si
    // Gestión avisa antes (erp:orders-ready de ErpChat), se pide ya.
    function scheduleErpRetry(now) {
        if (erpRetryCount >= ERP_RETRY_MAX) {
            $('#pane-erp [data-erp-orders-pending]').html(
                emptyState('fa-clipboard-list', 'Pedidos aún en camino', 'Gestión está tardando. Pulsa Actualizar en unos minutos.')
            );
            return;
        }
        erpRetryCount++;
        setTimeout(function () {
            $.ajax({
                url: baseUrl + '/tab/erp',
                method: 'GET',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            }).done(function (resp) {
                var payload = (resp && resp.data) ? resp.data : {};
                ctfData.erp = payload;
                $('#pane-erp').html(renderErp(payload));
                if (payload.orders_loading) {
                    scheduleErpRetry();
                } else {
                    renderCtfPerfil();
                    renderCtfMetrics();
                }
            }).fail(function () {
                $('#pane-erp [data-erp-orders-pending]').html(
                    emptyState('fa-clipboard-list', 'Sin pedidos ERP', 'No se pudieron cargar los pedidos.')
                );
            });
        }, now ? 0 : Math.min(15000, 2500 * erpRetryCount));
    }

    $(document).on('erp:orders-ready', function () {
        if ($('#pane-erp [data-erp-orders-pending]').length) {
            scheduleErpRetry(true);
        }
    });

    // ──────────────────────────────────────────────────────────── sync ──

    // platform: 'erp'|'prestashop' sincroniza solo esa fuente (botón
    // "Reintentar" por fila); sin argumento, ambas ("Sincronizar todo").
    // $triggerBtn: el botón que disparó la acción — recibe el spinner en vez
    // del de confirmación cuando es un "Reintentar" de fila.
    function runSync(platform, $triggerBtn) {
        var $btn = $triggerBtn || $('#contact-sync-confirm-btn');
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Sincronizando...');

        $.ajax({
            url: baseUrl + '/sync' + (platform ? ('?platform=' + encodeURIComponent(platform)) : ''),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        }).done(function (resp) {
            notifySuccess((resp && resp.message) || 'Sincronización completada');
            // Las fuentes de la ficha se vuelven a pedir ya (alimentan las
            // secciones de arriba); las pestañas de carga diferida quedan
            // caducadas y se piden de nuevo solo cuando se abren.
            CTF_TABS.forEach(function (t) { loadTab(t, true); });
            $('#pane-conversaciones').each(function () {
                $(this).data('loaded', '0');
            });
            var $activeTab = $root.find('.ctf-tabs [data-contact-tab].active');
            if ($activeTab.length && CTF_TABS.indexOf($activeTab.data('contact-tab')) === -1) {
                $activeTab.trigger('shown.bs.tab');
            }
            $(document).trigger('contacts360:synced');
            if (!platform) {
                $('#contact-sync-modal').modal('hide');
            } else {
                renderCtfSyncModal();
            }
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
        notifyError((xhr && xhr.responseJSON && xhr.responseJSON.message) || fallback || 'Ocurrió un error');
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
            priority: $form.find('[name="priority"]').val(),
            message: $form.find('[name="message"]').val(),
            attach_context: $form.find('[name="attach_context"]').is(':checked') ? 1 : 0
        }).done(function (resp) {
            notifySuccess((resp && resp.message) || 'Ticket creado');
            loadTab('tickets', true);
            $('#contact-ticket-modal').modal('hide');
            $form[0].reset();
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
            reportFailure(xhr, 'No se pudieron recuperar todos los artículos');
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
        $btn.append(' <span class="ctf-tab-count contact-tab-badge">' + parseInt(count, 10) + '</span>');
    }

    // ══════════════════════════ Ficha 360 versión B (sin pestañas) ══════════
    // Las 6 fuentes de CTF_TABS se piden en paralelo al cargar (ver wiring más
    // abajo); cada llegada re-pinta las secciones que dependen de ella. Los
    // panes #pane-{tab} originales (ver TABS) se mantienen ocultos en el DOM
    // solo como "data holder" para el código de PrestaShop que sigue leyendo
    // de $('#pane-prestashop').data(...) (findCachedCart).

    var ctfLoaded = {};
    var ctfHistoryFilter = 'all';
    var ctfHistoryExpanded = false;

    function ctfRelativeTime(iso) {
        if (!iso) {
            return null;
        }
        var d = new Date(iso);
        if (isNaN(d.getTime())) {
            return null;
        }
        var diffSec = Math.round((Date.now() - d.getTime()) / 1000);
        if (diffSec < 60) {
            return 'hace un momento';
        }
        var mins = Math.round(diffSec / 60);
        if (mins < 60) {
            return 'hace ' + mins + ' min';
        }
        var hours = Math.round(mins / 60);
        if (hours < 24) {
            return 'hace ' + hours + (hours === 1 ? ' hora' : ' horas');
        }
        var days = Math.round(hours / 24);
        if (days < 30) {
            return 'hace ' + days + (days === 1 ? ' día' : ' días');
        }
        var months = Math.round(days / 30);
        if (months < 12) {
            return 'hace ' + months + (months === 1 ? ' mes' : ' meses');
        }
        var years = Math.round(months / 12);
        return 'hace ' + years + (years === 1 ? ' año' : ' años');
    }

    // Sin campo booleano "impagado" en el shape de pedido (ver mapOrder() en
    // PrestashopContextService): mismo criterio catch-all que
    // window.bvOrderStatusClass() (conversations-extras.js, no cargado aquí) —
    // cualquier estado que no sea entrega/envío/cancelación se asume pendiente.
    function ctfOrderIsUnpaid(stateName) {
        var t = String(stateName || '').toLowerCase();
        if (!t) {
            return false;
        }
        return !/entreg|pago acept|complet|pagad|envi|ship|tránsit|cancel|reembols|anulad/.test(t);
    }

    // Carrito real "ahora mismo": prefiere el carrito en vivo de PrestaShop
    // (payload.carts[], shape crudo del bridge) y cae a Remarketing/Tienda
    // (mapAbandonedCart(), shape propio y ya normalizado) cuando PS no está
    // disponible o no tiene carrito abierto.
    function ctfLiveCart() {
        var ps = ctfData.prestashop;
        if (ps && ps.available && Array.isArray(ps.carts) && ps.carts.length) {
            var c = ps.carts[0];
            var total = (c.totals && c.totals.total != null) ? c.totals.total : c.total;
            var itemsCount = c.products_count != null ? c.products_count : (Array.isArray(c.items) ? c.items.length : null);
            return {
                id: c.id,
                total: total,
                itemsCount: itemsCount,
                relTime: ctfRelativeTime(c.updated_at),
                source: 'prestashop'
            };
        }
        var tienda = ctfData.tienda;
        if (tienda && tienda.available && Array.isArray(tienda.carts) && tienda.carts.length) {
            var t = tienda.carts[0];
            return {
                id: null,
                total: t.total,
                itemsCount: t.itemsCount,
                relTime: ctfRelativeTime(t.updatedAt),
                source: 'tienda'
            };
        }
        return null;
    }

    var CTF_LIFETIME_SOURCES = { remarketing: 'Tienda', prestashop: 'PrestaShop', erp: 'ERP' };

    // "Valor de vida": lo que el servidor ya calculó (lifetimeOrders(), que solo
    // lee caché y por eso puede llegar en 0 si PrestaShop/ERP aún no estaban
    // cacheados cuando se pidió /tab/resumen) y, si viene vacío, lo que traen
    // los payloads de /tab/prestashop y /tab/erp de esta misma carga: customer.ltv
    // del bridge (o la suma de orders[].totals.total si no hay ltv) y, en último
    // lugar, el facturado total del ERP. Nada se inventa: sin dato queda en 0.
    function ctfLifetimeValue() {
        var lifetime = ((ctfData.resumen || {}).stats || {}).lifetime || {};
        var value = {
            total: parseFloat(lifetime.totalSpent) || 0,
            count: lifetime.ordersCount != null ? lifetime.ordersCount : null,
            currency: lifetime.currency,
            source: lifetime.source ? (CTF_LIFETIME_SOURCES[lifetime.source] || null) : null,
            partial: !!lifetime.partial
        };

        if (value.total > 0) {
            return value;
        }

        var ps = ctfData.prestashop;
        if (ps && ps.available && ps.customer && ps.customer.found !== false) {
            var orders = Array.isArray(ps.orders) ? ps.orders : [];
            var count = ps.customer.orders_count != null ? parseInt(ps.customer.orders_count, 10) : orders.length;
            var hasLtv = ps.customer.ltv != null && !isNaN(parseFloat(ps.customer.ltv));
            var psTotal = hasLtv
                ? parseFloat(ps.customer.ltv)
                : orders.reduce(function (sum, o) { return sum + (parseFloat(o.totals && o.totals.total) || 0); }, 0);

            if (psTotal > 0) {
                return {
                    total: psTotal,
                    count: count,
                    currency: orders[0] && orders[0].currency && String(orders[0].currency).length === 3 ? orders[0].currency : 'EUR',
                    source: CTF_LIFETIME_SOURCES.prestashop,
                    partial: !hasLtv && count > orders.length
                };
            }
        }

        var erp = ctfData.erp;
        var invoiced = erp && erp.available && erp.customer ? parseFloat(erp.customer.balance_invoiced) : NaN;
        if (invoiced > 0) {
            return {
                total: invoiced,
                count: Array.isArray(erp.orders) ? erp.orders.length : null,
                currency: 'EUR',
                source: CTF_LIFETIME_SOURCES.erp,
                partial: false
            };
        }

        return value;
    }

    function renderCtfMetrics() {
        var $el = $('#ctf-metrics');
        if (!$el.length) {
            return;
        }

        if (!ctfLoaded.resumen) {
            $el.html('<div class="ctf-metric"><div class="ctf-skel-line"></div></div>'.repeat(3));
            return;
        }

        var resumen = ctfData.resumen || {};
        var stats = resumen.stats || {};
        var lifetimeValue = ctfLifetimeValue();
        var metrics = [];

        var lifetimeSubParts = [];
        if (lifetimeValue.count != null) {
            lifetimeSubParts.push(lifetimeValue.count + (lifetimeValue.count === 1 ? ' pedido' : ' pedidos'));
        }
        if (lifetimeValue.source) {
            lifetimeSubParts.push(lifetimeValue.source + (lifetimeValue.partial ? ' (parcial)' : ''));
        }

        metrics.push({
            label: 'Valor de vida',
            value: money(lifetimeValue.total, lifetimeValue.currency),
            sub: lifetimeSubParts.join(' · ')
        });

        var erp = ctfData.erp;
        if (erp && erp.available && erp.customer && erp.customer.balance_pending != null) {
            metrics.push({
                label: 'Pendiente de cobro',
                value: money(erp.customer.balance_pending, null),
                cls: 'is-warn'
            });
        }

        var cart = ctfLiveCart();
        if (cart && cart.total) {
            var cartSubParts = [];
            if (cart.itemsCount != null) {
                cartSubParts.push(cart.itemsCount + (cart.itemsCount === 1 ? ' artículo' : ' artículos'));
            }
            if (cart.relTime) {
                cartSubParts.push(cart.relTime);
            }
            metrics.push({
                label: 'Carrito ahora',
                value: money(cart.total, null),
                sub: cartSubParts.join(' · '),
                cls: 'is-accent'
            });
        }

        metrics.push({
            label: 'Conversaciones',
            value: stats.totalConversations != null ? stats.totalConversations : 0,
            sub: [
                stats.openConversations != null ? (stats.openConversations + (stats.openConversations === 1 ? ' abierta' : ' abiertas')) : null,
                stats.ticketsCount ? (stats.ticketsCount + (stats.ticketsCount === 1 ? ' ticket' : ' tickets')) : null
            ].filter(Boolean).join(' · ')
        });

        $el.html(metrics.map(function (m) {
            return '<div class="ctf-metric">' +
                '<div class="ctf-metric-label">' + esc(m.label) + '</div>' +
                '<div class="ctf-metric-value' + (m.cls ? ' ' + m.cls : '') + '">' + esc(m.value) + '</div>' +
                (m.sub ? '<div class="ctf-metric-sub' + (m.cls === 'is-warn' ? ' is-warn' : '') + '">' + esc(m.sub) + '</div>' : '') +
                '</div>';
        }).join(''));
    }

    function renderCtfAttention() {
        var $el = $('#ctf-attn-body');
        var $count = $('#ctf-attn-count');
        if (!$el.length) {
            return;
        }

        if (!ctfLoaded.prestashop && !ctfLoaded.tienda) {
            $el.html(skeleton(2));
            return;
        }

        var items = [];
        var ps = ctfData.prestashop;

        var cart = ctfLiveCart();
        if (cart && cart.total) {
            items.push({
                icon: 'fa-cart-shopping',
                iconCls: '',
                itemCls: ' is-live',
                next: 'Abrir el carrito',
                title: 'Carrito abandonándose',
                sub: money(cart.total, null) + ' sin comprar' + (cart.relTime ? (' desde ' + cart.relTime) : ''),
                cta: 'Abrir',
                ctaOutline: false,
                ctaAttr: (cart.source === 'prestashop' && cart.id != null) ? (' data-ps-cart-id="' + esc(cart.id) + '"') : ''
            });
        }

        var unpaidOrder = (ps && ps.available && Array.isArray(ps.orders))
            ? ps.orders.filter(function (o) { return c3OrderKind(o) === 'pending'; })[0]
            : null;
        if (unpaidOrder) {
            var amount = unpaidOrder.totals && unpaidOrder.totals.total;
            var when = ctfRelativeTime(unpaidOrder.placed_at);
            items.push({
                icon: 'fa-file-invoice',
                iconCls: ' is-warn',
                itemCls: '',
                title: 'Pedido ' + (unpaidOrder.reference ? ('#' + unpaidOrder.reference) : ('#' + unpaidOrder.id)) + ' sin pagar',
                sub: (amount != null ? money(amount, null) : '') + (when ? (' · ' + when) : ''),
                // "Recordar" abre la plantilla de WhatsApp (mockup); sin WhatsApp
                // abre el pedido.
                next: 'Enviar recordatorio de pago',
                cta: 'Recordar',
                ctaOutline: true,
                ctaAttr: $('.ctf-hero-actions .send-hsm-trigger').length
                    ? ' data-ctf-remind'
                    : (unpaidOrder.id != null ? (' data-ps-order-id="' + esc(unpaidOrder.id) + '"') : '')
            });
        }

        var openReturn = (ps && ps.available && Array.isArray(ps.returns))
            ? ps.returns.filter(function (r) {
                var s = String(r.state_name || '').toLowerCase();
                return s !== '' && !/denegad|cancelad|rechazad|complet|recibid/.test(s);
            })[0]
            : null;
        if (openReturn) {
            var rWhen = ctfRelativeTime(openReturn.created_at);
            items.push({
                icon: 'fa-rotate-left',
                iconCls: ' is-muted',
                itemCls: '',
                title: 'RMA-' + (openReturn.id != null ? String(openReturn.id).padStart(6, '0') : '?') + ' esperando',
                sub: esc(openReturn.state_name || 'Abierta') + (rWhen ? (' · ' + rWhen) : ''),
                // "Resolver": abre el pedido de la devolución (el bridge no
                // tiene acción para resolver la RMA directamente).
                next: 'Resolver la devolución',
                cta: 'Resolver',
                ctaOutline: true,
                ctaAttr: ' data-rma-id="' + esc(openReturn.id) + '"'
            });
        }

        // "Siguiente acción": el primer aviso con acción, con su mismo disparador.
        var nextItem = items.filter(function (it) { return it.cta && it.next; })[0];
        $('#ctf-next-action').toggleClass('d-none', !nextItem).html(nextItem
            ? '<span class="lbl">Siguiente acción</span><button type="button" class="ctf-next-btn"' + nextItem.ctaAttr + '>' + esc(nextItem.next) + '</button>'
            : '');

        if (!items.length) {
            $el.html('<div class="ctf-attn-empty">Nada requiere atención ahora mismo.</div>');
            if ($count.length) {
                $count.text('0');
            }
            return;
        }

        if ($count.length) {
            $count.text(items.length);
        }

        $el.html(items.map(function (it) {
            return '<div class="ctf-attn-item' + it.itemCls + '">' +
                '<span class="ctf-attn-icon' + it.iconCls + '"><i class="fas ' + it.icon + '"></i></span>' +
                '<span class="ctf-attn-text"><span class="ctf-attn-title">' + esc(it.title) + '</span><span class="ctf-attn-sub">' + it.sub + '</span></span>' +
                (it.cta ? ('<button type="button" class="ctf-attn-cta' + (it.ctaOutline ? ' is-outline' : '') + '"' + it.ctaAttr + '>' + esc(it.cta) + '</button>') : '') +
                '</div>';
        }).join(''));
    }

    function ctfHistoryEvents() {
        var act = ctfData.actividad;
        var events = (act && Array.isArray(act.timeline)) ? act.timeline.slice() : [];

        // Mensajes que dejó en la tienda (PrestaShop): no pasan por la
        // actividad del helpdesk, se suman aquí como eventos de comercio.
        var ps = ctfData.prestashop;
        if (ps && ps.available && Array.isArray(ps.messages) && ps.messages.length) {
            ps.messages.forEach(function (m) {
                if (!m || !m.created_at) {
                    return;
                }
                events.push({
                    at: m.created_at,
                    title: 'Mensaje en la tienda' + (m.subject ? (': ' + m.subject) : ''),
                    detail: psMessagePreview(m),
                    source: 'prestashop'
                });
            });
            events.sort(function (a, b) { return (Date.parse(b.at) || 0) - (Date.parse(a.at) || 0); });
        }

        return events;
    }

    function renderCtfHistory() {
        var $el = $('#ctf-hist-body');
        var $filters = $('#ctf-hist-filters');
        if (!$el.length) {
            return;
        }

        if (!ctfLoaded.actividad) {
            $el.html(skeleton(4));
            return;
        }

        var events = ctfHistoryEvents();

        // Los filtros solo aportan cuando hay eventos de 2+ orígenes distintos
        // (Comercio/Mensajes/Ficha): con un único origen, cada pill daría lo
        // mismo que "Todo" o una lista vacía. Como los cambios de la propia
        // ficha (source 'ficha') casi siempre existen, basta con que haya otro.
        var sources = {};
        events.forEach(function (e) { if (e && e.source) { sources[e.source] = true; } });
        var showFilters = Object.keys(sources).length > 1;

        if ($filters.length) {
            $filters.toggleClass('d-none', !showFilters);
        }
        if (!showFilters && ctfHistoryFilter !== 'all') {
            // El filtro activo ya no tiene sentido si la barra se oculta.
            ctfHistoryFilter = 'all';
            $('#ctf-hist-filters [data-ctf-hist-filter]').removeClass('is-active').filter('[data-ctf-hist-filter="all"]').addClass('is-active');
        }

        var filtered = events;
        if (ctfHistoryFilter === 'comercio') {
            filtered = events.filter(function (e) { return e.source === 'erp' || e.source === 'prestashop'; });
        } else if (ctfHistoryFilter === 'mensajes') {
            filtered = events.filter(function (e) { return e.source === 'helpdesk'; });
        } else if (ctfHistoryFilter === 'ficha') {
            filtered = events.filter(function (e) { return e.source === 'ficha'; });
        }

        if (!filtered.length) {
            $el.html(emptyState('fa-timeline', 'Sin eventos', 'No hay actividad registrada todavía.'));
            return;
        }

        var limit = ctfHistoryExpanded ? filtered.length : 8;
        var shown = filtered.slice(0, limit);

        var sourceLabels = { prestashop: 'PrestaShop', erp: 'ERP' };
        var html = shown.map(function (e, idx) {
            var when = c3When(e.at, false).replace(/^ayer .*/, 'ayer');
            // Sin repetir en el detalle lo que ya dice el título ("Pedido PS X · X");
            // los eventos de comercio sin detalle llevan su fuente.
            var detail = (e.detail && String(e.title || '').indexOf(e.detail) === -1) ? e.detail : '';
            var suffix = [detail, sourceLabels[e.source]].filter(Boolean).join(' · ');
            return '<div class="ctf-hist-item">' +
                '<span class="ctf-hist-when">' + esc(when) + '</span>' +
                '<span class="ctf-hist-dot' + (idx === 0 ? ' is-fresh' : '') + '"></span>' +
                '<span class="ctf-hist-body">' + esc(e.title) + (suffix ? (' <span class="muted">· ' + esc(suffix) + '</span>') : '') + '</span>' +
                '</div>';
        }).join('');

        if (!ctfHistoryExpanded && filtered.length > limit) {
            html += '<button type="button" class="ctf-hist-more" id="ctf-hist-more-btn">Ver los ' + filtered.length + ' eventos</button>';
        }

        $el.html(html);
    }

    // "Taladro GSB 18V-55 +1": primer producto del pedido y cuántos más.
    function ctfOrderLinesSummary(o) {
        var items = Array.isArray(o.lines) ? o.lines : [];
        if (!items.length || !items[0].name) {
            return '';
        }
        return items[0].name + (items.length > 1 ? (' +' + (items.length - 1)) : '');
    }

    function renderCtfPurchases() {
        var $el = $('#ctf-purchases-body');
        if (!$el.length) {
            return;
        }

        if (!ctfLoaded.tienda && !ctfLoaded.prestashop) {
            $el.html(skeleton(3));
            return;
        }

        var tienda = ctfData.tienda;
        var ps = ctfData.prestashop;
        var lines = [];
        var moreCount = 0;

        if (tienda && tienda.available && Array.isArray(tienda.orders) && tienda.orders.length) {
            var shownT = tienda.orders.slice(0, 3);
            var totalT = (tienda.stats && tienda.stats.ordersCount != null) ? tienda.stats.ordersCount : tienda.orders.length;
            moreCount = Math.max(0, totalT - shownT.length);
            lines = shownT.map(function (o) {
                var firstItem = (o.items && o.items[0]) ? o.items[0] : null;
                var extra = (o.items && o.items.length > 1) ? (' +' + (o.items.length - 1)) : '';
                return {
                    ref: o.number,
                    name: (firstItem ? firstItem.name : 'Pedido') + extra,
                    amount: money(o.total, o.currency)
                };
            });
        } else if (ps && ps.available && Array.isArray(ps.orders) && ps.orders.length) {
            var shownPs = ps.orders.slice(0, 3);
            moreCount = Math.max(0, ps.orders.length - shownPs.length);
            // Primer producto del pedido "+N" (lines[] viaja en el contexto);
            // sin líneas, el estado del pedido en su lugar.
            lines = shownPs.map(function (o) {
                return {
                    ref: o.reference ? ('#' + o.reference) : ('#' + o.id),
                    name: ctfOrderLinesSummary(o) || ((o.state && o.state.name) ? o.state.name : 'Pedido'),
                    amount: money(o.totals && o.totals.total, null),
                    orderId: o.id
                };
            });
        }

        if (!lines.length) {
            $el.html(emptyState('fa-bag-shopping', 'Sin compras', 'Este cliente no tiene pedidos registrados.'));
            return;
        }

        var html = lines.map(function (l) {
            var clickAttr = l.orderId != null ? (' data-ps-order-id="' + esc(l.orderId) + '" role="button"') : '';
            return '<div class="ctf-line-row' + (l.orderId != null ? ' is-clickable' : '') + '"' + clickAttr + '>' +
                '<span class="ref">' + esc(l.ref) + '</span>' +
                '<span class="nm">' + esc(l.name) + '</span>' +
                '<span class="amt">' + esc(l.amount) + '</span>' +
                '</div>';
        }).join('');

        var totalOrders = lines.length + moreCount;
        var ordersTab = lines[0].orderId != null ? 'prestashop' : 'tienda';
        if ($('#pane-' + ordersTab).length) {
            html += '<button type="button" class="ctf-hist-more" data-ctf-open-tab="' + ordersTab + '">Ver ' +
                (totalOrders === 1 ? 'el pedido' : ('los ' + totalOrders + ' pedidos')) + '</button>';
        } else if (moreCount > 0) {
            html += '<div class="ctf-more-label">' + moreCount + ' más</div>';
        }

        $el.html(html);
    }

    function renderCtfAccount() {
        var $el = $('#ctf-account-body');
        var $section = $('#ctf-account-section');
        if (!$el.length) {
            return;
        }

        if (!ctfLoaded.resumen && !ctfLoaded.erp && !ctfLoaded.prestashop) {
            return;
        }

        var erp = ctfData.erp;
        var ps = ctfData.prestashop;
        var owner = ctfData.resumen && ctfData.resumen.owner;
        var rows = [];

        // El responsable sale de 'resumen' (columna owner_id), no del ERP: la
        // sección se muestra aunque el contacto no tenga ERP ni vales.
        if (owner && owner.name) {
            rows.push({ k: 'Responsable', v: owner.name, plain: true });
        }

        if (erp && erp.available && erp.customer) {
            var c = erp.customer;
            if (c.credit_limit != null) {
                rows.push({ k: 'Límite de crédito', v: money(c.credit_limit, null), accent: true });
            }
            if (c.balance_invoiced != null) {
                rows.push({ k: 'Facturado', v: money(c.balance_invoiced, null) });
            }
            if (c.loyalty_points != null) {
                rows.push({ k: 'Puntos de fidelidad', v: String(c.loyalty_points) });
            }
        }

        if (ps && ps.available && Array.isArray(ps.vouchers) && ps.vouchers.length) {
            var availableTotal = ps.vouchers.reduce(function (sum, v) {
                var isAvailable = !v.expired && v.active !== false;
                var amount = parseFloat(v.reduction_amount);
                return (isAvailable && !isNaN(amount)) ? sum + amount : sum;
            }, 0);
            if (availableTotal > 0) {
                rows.push({ k: 'Vales disponibles', v: money(availableTotal, null) });
            }
        }

        if (!rows.length) {
            $el.empty();
            if ($section.length) {
                $section.addClass('d-none');
            }
            return;
        }
        if ($section.length) {
            $section.removeClass('d-none');
        }

        $el.html(rows.map(function (r) {
            return '<div class="ctf-kv-row"><span class="k">' + esc(r.k) + '</span><span class="v' + (r.plain ? '' : ' mono') + (r.accent ? ' is-accent' : '') + '">' + esc(r.v) + '</span></div>';
        }).join(''));
    }

    // Compartida entre "Fuentes vinculadas" (columna derecha) y el modal
    // "Sincronizar fuentes" — misma fila punto+etiqueta+antigüedad, mismo
    // criterio de color vía integrationPillState() ya usado en el hero.
    // withRetry (solo el modal de sync): añade un "Reintentar" por fila para
    // ERP/PrestaShop cuando su estado es error/pendiente — sincroniza solo
    // esa plataforma (POST {base}/sync?platform=…). "Chat web" (webchat) no es
    // sincronizable: nunca lleva botón, y sin actividad se lee "sin actividad"
    // en vez de "sin vincular" (no hay nada que vincular).
    function ctfSourceRowsHtml(integrations, withRetry) {
        return integrations.map(function (it) {
            var state = integrationPillState(it);
            var isStale = state.cls === 'is-warning' || state.cls === 'is-danger';
            var dotCls = !it.connected ? 'is-off' : (isStale ? 'is-stale' : '');
            var disconnectedText = it.platform === 'webchat' ? 'sin actividad' : 'sin vincular';
            var age = it.lastSyncedAt ? (ctfRelativeTime(it.lastSyncedAt) || '') : (it.connected ? 'vinculado' : disconnectedText);
            var retryable = withRetry && isStale && (it.platform === 'erp' || it.platform === 'prestashop');
            return '<div class="ctf-source-row">' +
                '<span class="ctf-source-dot' + (dotCls ? (' ' + dotCls) : '') + '"></span>' +
                '<span class="lbl">' + esc(it.platform === 'erp' ? 'ERP' : (it.label || it.platform)) + '</span>' +
                '<span class="age' + (isStale ? ' is-stale' : '') + '">' + esc(age) + '</span>' +
                (retryable ? '<button type="button" class="ctf-source-retry" data-contact-sync-platform="' + esc(it.platform) + '">Reintentar</button>' : '') +
                '</div>';
        }).join('');
    }

    // Pinta la lista del modal "Sincronizar fuentes" al abrirlo (bv:modal
    // nativo de Bootstrap, evento show.bs.modal) desde ctfData.resumen.integrations
    // ya cargado — sin petición nueva. Si 'resumen' aún no llegó, deja el
    // skeleton estático del Blade tal cual hasta que onCtfTabLoaded lo repinte.
    function renderCtfSyncModal() {
        var $list = $('#contact-sync-list');
        if (!$list.length || !ctfLoaded.resumen) {
            return;
        }

        var integrations = (ctfData.resumen && Array.isArray(ctfData.resumen.integrations)) ? ctfData.resumen.integrations : [];

        if (!integrations.length) {
            $list.html('<div class="ctf-note-empty">Sin fuentes vinculadas.</div>');
        } else {
            $list.html(ctfSourceRowsHtml(integrations, true));
        }

        // "Solo la fuente con error" del mockup: cada fila con error/pendiente
        // lleva su propio "Reintentar" (sync por plataforma, ver
        // ctfSourceRowsHtml); el botón principal sigue siendo "Sincronizar
        // todo" y solo cambia de etiqueta cuando hay algo realmente roto.
        var hasIssue = integrations.some(function (it) {
            var state = integrationPillState(it);
            return state.cls === 'is-warning' || state.cls === 'is-danger';
        });
        $('#contact-sync-confirm-btn').text('Sincronizar todo');
        $('#contact-sync-failed-btn').toggleClass('d-none', !hasIssue);
    }

    function renderCtfSources() {
        var $el = $('#ctf-sources-body');
        if (!$el.length) {
            return;
        }

        if (!ctfLoaded.resumen) {
            $el.html(skeleton(2));
            return;
        }

        var integrations = (ctfData.resumen && Array.isArray(ctfData.resumen.integrations)) ? ctfData.resumen.integrations : [];

        if (!integrations.length) {
            $el.html('<div class="ctf-note-empty">Sin fuentes vinculadas.</div>');
            return;
        }

        var html = ctfSourceRowsHtml(integrations);

        html += '<button type="button" class="ctf-link-btn" data-contact-sync-trigger>Sincronizar</button>';

        $el.html(html);
    }

    // Badge "VIP · salud N" + anillo del avatar (clases is-hNN de contacts.css) — el mockup B también
    // muestra "VIP" ahí, pero no existe campo VIP en el backend (ver plan de
    // Contactos 360, fuera de alcance de esta fase), así que solo se pinta la
    // salud. Oculto cuando el cliente no tiene ninguna conversación: el score
    // siempre parte de una base neutra de 50 aunque nunca haya interactuado
    // (ver CustomerInsightsService::calculateHealthScore), así que mostrarlo
    // ahí sería un dato "calculado" sobre la nada — mismo criterio que ya
    // aplica el listado (columna Salud) para esos contactos.
    function renderCtfHeroBadge() {
        var $badge = $('#ctf-hero-badge');
        var $ring = $('.ctf-hero-ring-inner').length ? $('.ctf-hero-ring') : null;
        if (!$badge.length) {
            return;
        }
        var stats = ctfData.resumen && ctfData.resumen.stats ? ctfData.resumen.stats : {};
        var score = stats.healthScore;
        // Sin conversaciones el score es la base neutra de 50 calculada sobre
        // la nada (ver CustomerInsightsService): no se muestra.
        var showScore = score != null && (stats.totalConversations || 0) > 0;
        var isVip = String($badge.attr('data-vip')) === '1';

        var parts = [];
        if (isVip) { parts.push('VIP'); }
        if (showScore) { parts.push('salud ' + score); }
        $badge.text(parts.join(' · ')).toggleClass('d-none', !parts.length);

        if ($ring) {
            $ring.removeClass(function (i, cls) { return (cls.match(/(^|\s)is-h\d+/g) || []).join(' '); });
            if (showScore) {
                $ring.addClass('is-h' + Math.min(100, Math.max(0, Math.round(score / 5) * 5)));
            }
        }
    }

    // Badge "VIP" del hero — columna is_vip. El Blade ya lo pinta al cargar
    // (sin esperar al fetch); esto solo lo mantiene sincronizado con el
    // payload real de resumen (p.ej. tras un cambio hecho desde otra pestaña).
    // VIP del badge del hero — columna is_vip: el Blade ya lo pinta al cargar;
    // esto lo sincroniza con el payload real de resumen.
    function renderCtfHeroVip() {
        if (!ctfData.resumen) {
            return;
        }
        $('#ctf-hero-badge').attr('data-vip', ctfData.resumen.isVip ? '1' : '0');
        renderCtfHeroBadge();
    }

    // Etiquetas del contacto bajo la meta del hero, desde resumen.tags
    // ([{id,name,color}]). color es un token de diseño o null; solo se
    // reconocen tonos verdes/ámbar/oscuros (mismo criterio que el listado),
    // el resto queda en gris neutro — nunca rojo.
    function ctfTagVariant(color) {
        var c = String(color || '');
        if (c.indexOf('primary') !== -1 || c.indexOf('green') !== -1) { return ' is-green'; }
        if (c.indexOf('warn') !== -1 || c.indexOf('amber') !== -1) { return ' is-warn'; }
        if (c.indexOf('dark') !== -1) { return ' is-dark'; }
        return '';
    }

    function renderCtfHeroTags() {
        var $tags = $('#ctf-hero-tags');
        if (!$tags.length || !ctfData.resumen) {
            return;
        }
        var tags = Array.isArray(ctfData.resumen.tags) ? ctfData.resumen.tags : [];
        $tags.toggleClass('d-none', !tags.length);
        $tags.html(tags.map(function (t) {
            return '<span class="ctf-tag' + ctfTagVariant(t.color) + '">' + esc(t.name) + '</span>';
        }).join(''));
    }

    // Muestra "Crear ticket" en el dropdown solo si el módulo de tickets está
    // disponible (mismo criterio que la versión A: sin el módulo, la acción
    // no se muestra en absoluto, ver renderTickets()) y rellena el <select>
    // de categorías del modal estático con las ya cargadas.
    // ?action=ticket: el modal solo tiene sentido con el módulo de tickets
    // disponible, y eso lo dice la fuente 'tickets' — se difiere hasta que llega.
    var ctfPendingTicketAction = false;

    function openTicketModalFromAction() {
        if (ctfData.tickets && ctfData.tickets.available) {
            $('#contact-ticket-modal').modal('show');
        } else {
            toastr.warning('El módulo de tickets no está disponible para este contacto.');
        }
    }

    function renderCtfTicketTrigger(payload) {
        var available = !!(payload && payload.available);
        $('#contact-ticket-trigger-item').toggleClass('d-none', !available);

        var categories = (payload && Array.isArray(payload.categories)) ? payload.categories : [];
        var $select = $('#ticket-category');
        if ($select.length) {
            // .not(':first') conserva la opción "Sin categoría": este render
            // se repite cada vez que loadTab('tickets', true) se vuelve a
            // disparar (p.ej. tras crear un ticket), y sin limpiar antes las
            // categorías se duplicaban en cada recarga.
            $select.find('option').not(':first').remove();
            categories.forEach(function (cat) {
                $select.append('<option value="' + esc(cat.id) + '">' + esc(cat.name || '') + '</option>');
            });
        }
    }

    function onCtfTabLoaded(tab, payload) {
        if (CTF_TABS.indexOf(tab) === -1) {
            return;
        }
        ctfData[tab] = payload;
        ctfLoaded[tab] = true;

        if (tab === 'resumen') {
            renderCtfHeroBadge();
            renderCtfHeroVip();
            renderCtfHeroTags();
            // Repinta el modal de sincronizar siempre (está oculto, es barato):
            // comprobar .show fallaba con ?action=sync, donde 'resumen' llega
            // desde caché mientras el modal aún hace su fundido y no tiene
            // la clase — se quedaba en skeleton.
            renderCtfSyncModal();
        }
        if (tab === 'tickets') {
            renderCtfTicketTrigger(payload);
            if (ctfPendingTicketAction) {
                ctfPendingTicketAction = false;
                openTicketModalFromAction();
            }
        }

        renderCtfPerfil();
        renderCtfMetrics();
        renderCtfAttention();
        renderCtfHistory();
        renderCtfPurchases();
        renderCtfAccount();
        renderCtfSources();

        // contacts-360-layouts.js pinta los bloques propios de cada estilo.
        $(document).trigger('c360:data', [tab]);
    }

    // API de solo lectura para contacts-360-layouts.js (estilos de la ficha):
    // los datos ya pedidos y las utilidades de formato, sin repetir fetches.
    window.Contacts360 = {
        root: $root,
        baseUrl: baseUrl,
        data: ctfData,
        loaded: ctfLoaded,
        esc: esc,
        money: money,
        when: c3When,
        relativeTime: ctfRelativeTime,
        orderKind: c3OrderKind,
        orderLinesSummary: ctfOrderLinesSummary,
        erpStatusLabel: erpStatusLabel,
        erpChatOn: erpChatOn,
        liveCart: ctfLiveCart,
        lifetimeValue: ctfLifetimeValue,
        openTab: function (tab) {
            if (!$root.find('.ctf-tabs [data-contact-tab="' + tab + '"]').length) {
                return false;
            }
            activateTab(tab);
            $('#ctf-detail')[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
            return true;
        },
        reload: function (tab) {
            loadTab(tab, true);
        },
        expandHistory: function () {
            ctfHistoryExpanded = true;
            renderCtfHistory();
        }
    };

    // ══════════════════════════════════════════════════════════════════════

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
        // Retry button (delegated, inside panes).
        $root.on('click', '[data-contact-retry]', function () {
            loadTab($(this).data('contact-retry'), true);
        });

        // Fila "Fuentes vinculadas" abre el mismo modal que el botón del
        // dropdown (ese ya usa data-bs-toggle="modal" nativo, sin JS).
        $root.on('click', '[data-contact-sync-trigger]', function (e) {
            e.preventDefault();
            $('#contact-sync-modal').modal('show');
        });

        // Pinta la lista de fuentes cada vez que el modal se abre (Bootstrap
        // nativo, dispara aunque el trigger no pase por el handler de arriba).
        $('#contact-sync-modal').on('show.bs.modal', renderCtfSyncModal);
        $('#contact-sync-modal').on('click', '#contact-sync-confirm-btn', function () {
            runSync();
        });
        // "Solo la fuente con error": reintenta cada fuente ERP/PrestaShop con error o pendiente.
        $('#contact-sync-modal').on('click', '#contact-sync-failed-btn', function () {
            var $btn = $(this);
            var integrations = (ctfData.resumen && Array.isArray(ctfData.resumen.integrations)) ? ctfData.resumen.integrations : [];
            integrations.filter(function (it) {
                var cls = integrationPillState(it).cls;
                return (cls === 'is-warning' || cls === 'is-danger') && (it.platform === 'erp' || it.platform === 'prestashop');
            }).forEach(function (it) {
                runSync(it.platform, $btn);
            });
        });

        // "Reintentar" por fila: sincroniza solo esa plataforma (el modal vive
        // fuera de #contact360, por eso el binding es sobre el propio modal).
        $('#contact-sync-modal').on('click', '[data-contact-sync-platform]', function () {
            runSync($(this).data('contact-sync-platform'), $(this));
        });

        // Carrito asistido: abre el modal y (re)carga su contenido cada vez.
        $root.on('click', '[data-contact-cart-trigger]', function () {
            // Sin el módulo Ecommerce (carrito asistido local) el carrito
            // asistido es el carrito real del cliente en PrestaShop.
            if (String($root.data('assisted-cart')) !== '1') {
                var live = ctfLiveCart();
                if (live && live.source === 'prestashop' && live.id != null) {
                    openPsCartModal(live.id);
                } else {
                    toastr.info('Este contacto no tiene un carrito abierto en la tienda.');
                }
                return;
            }
            $('#contact-cart-modal').modal('show');
            loadCart(true);
        });

        // Historial: filtro Todo/Comercio/Mensajes (Fuentes vinculadas → CTF_TABS).
        $root.on('click', '#ctf-hist-filters [data-ctf-hist-filter]', function () {
            ctfHistoryFilter = $(this).data('ctf-hist-filter');
            ctfHistoryExpanded = false;
            $('#ctf-hist-filters [data-ctf-hist-filter]').removeClass('is-active');
            $(this).addClass('is-active');
            renderCtfHistory();
        });
        $root.on('click', '#ctf-hist-more-btn', function () {
            ctfHistoryExpanded = true;
            renderCtfHistory();
        });

        // Hero "Vincular" trigger reuses the sync flow.
        $root.on('click', '[data-contact-link-trigger]', function (e) {
            e.preventDefault();
            $('#contact-sync-modal').modal('show');
        });

        // Los formularios de ticket y carrito asistido viven en modales FUERA
        // de #contact360: la delegación tiene que colgar de document (sobre
        // $root el submit nunca llegaba y el navegador enviaba el form por GET).
        $(document).on('submit', '#ticket-create-form', function (e) {
            e.preventDefault();
            submitTicket($(this));
        });

        // Cart: add item.
        $(document).on('submit', '#cart-add-form', function (e) {
            e.preventDefault();
            cartWrite('POST', '/items', {
                product_id: $(this).find('[name="product_id"]').val(),
                quantity: $(this).find('[name="quantity"]').val()
            }, 'No se pudo añadir el producto').done(function () {
                $('#cart-add-form')[0].reset();
                $('#cart-quantity').val(1);
            });
        });

        // Cart: remove item.
        $(document).on('click', '[data-cart-remove-item]', function () {
            var itemId = $(this).data('cart-remove-item');
            cartWrite('DELETE', '/items/' + encodeURIComponent(itemId), {}, 'No se pudo quitar el producto');
        });

        // Cart: apply discount.
        $(document).on('submit', '#cart-discount-form', function (e) {
            e.preventDefault();
            cartWrite('POST', '/discount', { code: $(this).find('[name="code"]').val() }, 'No se pudo aplicar el cupón');
        });

        // Cart: generate order / send link.
        $(document).on('click', '#cart-generate-order', function () {
            generateOrder($(this));
        });
        $(document).on('click', '#cart-send-link', function () {
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

        // Ficha B: las 6 fuentes se piden todas en paralelo al cargar — cada
        // una pinta su(s) sección(es) al llegar (ver onCtfTabLoaded), sin
        // esperarse entre sí. 'resumen' además rellena la cabecera (hero,
        // integraciones, sentimiento) vía fillResumenHero() dentro de loadTab().
        CTF_TABS.forEach(function (tab) {
            loadTab(tab, true);
        });

        // Actividad: filtro por grupo de evento.
        $root.on('click', '[data-act-filter]', function () {
            var group = $(this).data('act-filter');
            $(this).addClass('is-active').siblings().removeClass('is-active');
            $('#pane-actividad [data-act-group]').each(function () {
                $(this).toggleClass('d-none', group !== 'all' && $(this).data('act-group') !== group);
            });
        });

        // Facturas ERP: filtro Todas/Pendientes/Vencidas (al filtrar se
        // muestran todas las que casan, no solo las 5 primeras).
        $root.on('click', '[data-erp-inv-filter]', function () {
            var filter = $(this).data('erp-inv-filter');
            $(this).addClass('is-active').siblings().removeClass('is-active');
            $('#pane-erp [data-erp-inv]').each(function () {
                var kind = $(this).data('erp-inv');
                var match = filter === 'all' || kind === filter || (filter === 'pending' && kind === 'overdue');
                $(this).toggleClass('d-none', !match || (filter === 'all' && $(this).is('[data-c3-more]') && !$(this).data('c3-shown')));
            });
        });

        // Botones de "Acciones" (Resumen) que reutilizan los del menú "···".
        $root.on('click', '[data-ctf-proxy]', function () {
            $($(this).data('ctf-proxy')).first().trigger('click');
        });
        $root.on('click', '[data-ctf-link-platform]', function () {
            $('.external-link-trigger').first().trigger('click');
        });

        // ── Resolver devolución (RMA) ───────────────────────────────────
        var rmaBase = String($root.data('ps-rma-base') || '').replace(/\/+$/, '');
        var rmaActive = null;
        var rmaDenied = parseInt($('#contact-rma-modal').data('denied-state'), 10) || 4;

        $root.on('click', '[data-rma-id]', function () {
            var id = $(this).data('rma-id');
            if (!id || !rmaBase) {
                return;
            }
            rmaActive = { id: id };
            $('#rmaModalLabel').text('RMA-' + String(id).padStart(6, '0'));
            $('#contact-rma-save').addClass('d-none');
            $('#contact-rma-body').html(skeleton(3));
            $('#contact-rma-modal').modal('show');
            $.ajax({ url: rmaBase + '/' + encodeURIComponent(id), method: 'GET', headers: { 'Accept': 'application/json' } })
                .done(function (r) {
                    var d = (r && r.data) || {};
                    rmaActive.data = d;
                    var items = Array.isArray(d.items) ? d.items : [];
                    var html = '<div class="ct-kv"><span class="k">Pedido</span><span class="v mono">#' + esc(d.order_reference || d.order_id) + '</span></div>' +
                        '<div class="ct-kv"><span class="k">Abierta</span><span class="v">' + esc(c3When(d.created_at, true)) + '</span></div>' +
                        items.map(function (it) {
                            return '<div class="ct-kv"><span class="k">' + esc(it.name) + '</span><span class="v mono">×' + esc(it.quantity) + '</span></div>';
                        }).join('') +
                        (d.reason ? '<div class="ct-note-box"><span>Motivo del cliente · ' + esc(d.reason) + '</span></div>' : '');
                    if (r.can_resolve) {
                        html += '<div><span class="ct-flabel">Nuevo estado</span><div class="d-flex flex-column gap-2">' +
                            (d.states || []).map(function (st) {
                                var current = parseInt(st.id, 10) === parseInt(d.state_id, 10);
                                return '<label class="ct-radio-opt' + (parseInt(st.id, 10) === rmaDenied ? ' is-danger' : '') + '">' +
                                    '<input type="radio" name="c3-rma-state" value="' + esc(st.id) + '"' + (current ? ' checked disabled' : '') + '>' +
                                    '<span>' + esc(st.name) + (current ? ' <span class="ct-meta">· estado actual</span>' : '') + '</span></label>';
                            }).join('') + '</div></div>' +
                            '<div><label class="ct-flabel" for="c3-rma-message">Mensaje para el cliente</label>' +
                            '<textarea class="ct-finput" id="c3-rma-message" rows="2" placeholder="Obligatorio al denegar"></textarea></div>' +
                            '<label class="ct-fcheck"><input type="checkbox" id="c3-rma-notify" checked><span class="ct-fcheck-grow">Avisar al cliente con el email de la tienda</span></label>';
                        $('#contact-rma-save').removeClass('d-none');
                    } else {
                        html += '<div class="ct-note-box"><i class="fas fa-lock mt-1"></i><span>Resolver devoluciones requiere el permiso de la tienda para hacerlo.</span></div>';
                    }
                    $('#contact-rma-body').html(html);
                })
                .fail(function (xhr) {
                    $('#contact-rma-body').html('<div class="ct-note-box"><i class="fas fa-triangle-exclamation mt-1"></i><span>' +
                        esc((xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo cargar la devolución.') + '</span></div>');
                });
        });

        $('#contact-rma-modal').on('click', '#contact-rma-save', function () {
            var stateId = $('input[name="c3-rma-state"]:checked:not(:disabled)').val();
            if (!rmaActive || !stateId) {
                toastr.info('Elige el nuevo estado de la devolución.');
                return;
            }
            var $btn = $(this).prop('disabled', true).text('Guardando…');
            $.ajax({
                url: rmaBase + '/' + encodeURIComponent(rmaActive.id) + '/state',
                method: 'POST',
                data: { state_id: stateId, notify: $('#c3-rma-notify').is(':checked') ? 1 : 0, message: $('#c3-rma-message').val() },
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
            }).done(function (r) {
                notifySuccess('Devolución actualizada: ' + ((r && r.data && r.data.state_name) || 'nuevo estado'));
                $('#contact-rma-modal').modal('hide');
                loadTab('prestashop', true);
            }).fail(function (xhr) {
                reportFailure(xhr, 'No se pudo cambiar el estado de la devolución.');
            }).always(function () {
                $btn.prop('disabled', false).text('Guardar estado');
            });
        });

        // Recursos del panel PrestaShop del chat montados por _ps-chat-bridge.
        $(document).on('click', '[data-c3-ps-call]', function () {
            var fn = window[$(this).data('c3-ps-call')];
            var arg = $(this).data('c3-ps-arg');
            if (typeof fn === 'function') {
                fn(arg);
            }
        });
        $(document).on('click', '[data-c3-open-catalog]', function () {
            $('#ps-cart-detail-modal').modal('hide');
            window.openProductRecommend();
        });
        $root.on('click', '[data-c3-voucher-edit]', function () {
            if (typeof window.openPsVoucherEdit === 'function') {
                window.openPsVoucherEdit($(this).data('c3-voucher-edit'));
            }
        });

        // "Recordar" (pedido sin pagar): la misma plantilla de WhatsApp del hero.
        $root.on('click', '[data-ctf-remind]', function () {
            $('.ctf-hero-actions .send-hsm-trigger').first().trigger('click');
        });

        // "Ver las N facturas" / "Ver los N pedidos": despliega el resto.
        $root.on('click', '[data-c3-show-more]', function () {
            var key = $(this).data('c3-show-more');
            $(this).closest('.c3-card').find('[data-c3-more="' + key + '"]').removeClass('d-none').data('c3-shown', true);
            $(this).remove();
        });

        // ── Detalle por fuente (pestañas de la versión A) ──────────────
        // Las fuentes de CTF_TABS ya se pidieron arriba y se pintan en su
        // pane al llegar; el resto (conversaciones, chats) se pide la
        // primera vez que se abre su pestaña. Se recuerda la última abierta.
        var CTF_TAB_STORAGE_KEY = 'contacts360.detailTab';
        var ctfLoadedAt = Date.now();

        function ctfShowDetailTab(tab, scroll) {
            var $btn = $root.find('.ctf-tabs [data-contact-tab="' + tab + '"]');
            if (!$btn.length) {
                return false;
            }
            activateTab(tab);
            if (scroll) {
                $('#ctf-detail')[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            return true;
        }

        function ctfLoadDetailTab(tab) {
            if (tab === 'perfil') {
                renderCtfPerfil();
                return;
            }
            if (CTF_TABS.indexOf(tab) === -1) {
                loadTab(tab);
            }
        }

        $root.on('shown.bs.tab', '.ctf-tabs [data-contact-tab]', function () {
            var tab = $(this).data('contact-tab');
            ctfLoadDetailTab(tab);
            try { localStorage.setItem(CTF_TAB_STORAGE_KEY, tab); } catch (err) { /* sin storage */ }
        });

        $root.on('click', '[data-ctf-erp-retry]', function () {
            runSync('erp', $(this));
        });

        $root.on('click', '[data-ctf-open-tab]', function () {
            ctfShowDetailTab($(this).data('ctf-open-tab'), true);
        });

        // Teclas 1-N: cambian de pestaña salvo que se esté escribiendo o
        // haya un modal abierto.
        $(document).on('keydown', function (e) {
            if (e.ctrlKey || e.metaKey || e.altKey || $('.modal.show').length) {
                return;
            }
            if ($(e.target).is('input, textarea, select, [contenteditable="true"]')) {
                return;
            }
            var n = parseInt(e.key, 10);
            var $tabs = $root.find('.ctf-tabs [data-contact-tab]');
            if (n >= 1 && n <= $tabs.length) {
                activateTab($tabs.eq(n - 1).data('contact-tab'));
            }
        });

        var ctfSavedTab = null;
        try { ctfSavedTab = localStorage.getItem(CTF_TAB_STORAGE_KEY); } catch (err) { ctfSavedTab = null; }
        if (!(ctfSavedTab && ctfShowDetailTab(ctfSavedTab, false))) {
            var $firstTab = $root.find('.ctf-tabs [data-contact-tab]').first();
            if ($firstTab.length) {
                ctfLoadDetailTab($firstTab.data('contact-tab'));
            }
        }

        // "datos de hace N min": antigüedad real de lo pintado (se pidió al
        // cargar la ficha o en la última sincronización).
        function ctfRenderDataAge() {
            var mins = Math.floor((Date.now() - ctfLoadedAt) / 60000);
            $('#ctf-data-age, #ctf-erp-age').text(mins < 1 ? 'datos de ahora' : ('datos de hace ' + mins + ' min'));
        }
        setInterval(ctfRenderDataAge, 30000);
        $(document).on('contacts360:synced', function () {
            ctfLoadedAt = Date.now();
            ctfRenderDataAge();
        });

        // ── Notas internas ─────────────────────────────────────────────
        var $noteForm = $('#ctf-note-form');

        function ctfNotesCountDelta() {
            $('#ctf-notes-empty').toggleClass('d-none', $('#ctf-notes-list .ctf-note-card').length > 0);
        }

        $root.on('click', '[data-ctf-note-add]', function () {
            $noteForm.removeClass('d-none').find('textarea').trigger('focus');
        });
        $root.on('click', '[data-ctf-note-cancel]', function () {
            $noteForm.addClass('d-none')[0].reset();
        });
        $root.on('submit', '#ctf-note-form', function (e) {
            e.preventDefault();
            var $submit = $noteForm.find('[type="submit"]').prop('disabled', true);
            writeRequest('POST', $noteForm.data('url'), { body: $noteForm.find('textarea').val() })
                .done(function (resp) {
                    var n = (resp && resp.data) || {};
                    var when = n.createdAt ? new Date(n.createdAt).toLocaleDateString('es-ES', { day: '2-digit', month: 'short' }) : '';
                    $('#ctf-notes-list').prepend(
                        '<div class="ctf-note-card" data-note-id="' + esc(n.id) + '">' +
                        '<div class="body">' + esc(n.body) + '</div>' +
                        '<div class="meta"><span>' + esc(n.author) + (when ? ' · ' + esc(when) : '') + '</span>' +
                        '<button type="button" class="ctf-note-del" data-ctf-note-delete="' + esc(n.id) + '">Borrar</button></div>' +
                        '</div>'
                    );
                    ctfNotesCountDelta(1);
                    $noteForm.addClass('d-none')[0].reset();
                    notifySuccess('Nota añadida');
                })
                .fail(function (xhr) { reportFailure(xhr, 'No se pudo guardar la nota'); })
                .always(function () { $submit.prop('disabled', false); });
        });
        $root.on('click', '[data-ctf-note-delete]', function () {
            var id = $(this).data('ctf-note-delete');
            var $card = $(this).closest('.ctf-note-card');
            writeRequest('DELETE', $('#ctf-notes-list').data('destroy-url') + '/' + encodeURIComponent(id), {})
                .done(function () {
                    $card.remove();
                    ctfNotesCountDelta(-1);
                })
                .fail(function (xhr) { reportFailure(xhr, 'No se pudo borrar la nota'); });
        });

        // ── Edit contact form ──────────────────────────────────────────
        $root.on('click', '#contact-edit-btn, [data-contact-edit-trigger]', function () {
            $('#contact-edit-modal').modal('show');
        });

        // Etiquetas del modal Editar: select2 en modo "tags" (creación libre).
        // Las ya asignadas vienen como <option selected> desde el Blade; las
        // sugerencias (etiquetas usadas por otros contactos) se piden una sola
        // vez al abrir el modal. Sin tema bootstrap-5 de select2 (CSS no cargado).
        var $editTags = $('#edit-tags');
        var editTagsLoaded = false;
        if ($editTags.length && $.fn.select2) {
            $editTags.select2({
                tags: true,
                tokenSeparators: [','],
                dropdownParent: $('#contact-edit-modal'),
                width: '100%',
                placeholder: $editTags.data('placeholder') || ''
            });

            $('#contact-edit-modal').on('show.bs.modal', function () {
                if (editTagsLoaded) {
                    return;
                }
                editTagsLoaded = true;
                $.ajax({
                    url: $editTags.data('tags-url'),
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                }).done(function (resp) {
                    var present = {};
                    $editTags.find('option').each(function () { present[this.value] = true; });
                    ((resp && resp.tags) || []).forEach(function (t) {
                        if (!present[t.name]) {
                            $editTags.append(new Option(t.name, t.name, false, false));
                        }
                    });
                }).fail(function () {
                    // Sin sugerencias sigue funcionando: la creación libre no depende de ellas.
                    editTagsLoaded = false;
                });
            });
        }

        // Responsable del modal Editar: el Blade solo renderiza el actual; el
        // resto de agentes asignables se piden una vez al abrir (también cuando
        // se abre por ?action=edit, que pasa por el mismo show.bs.modal). Si la
        // petición falla, el select conserva al responsable actual y guardar
        // no lo altera; se reintenta en la siguiente apertura.
        var $editOwner = $('#edit-owner');
        var editOwnerLoaded = false;
        if ($editOwner.length) {
            $('#contact-edit-modal').on('show.bs.modal', function () {
                if (editOwnerLoaded) {
                    return;
                }
                editOwnerLoaded = true;
                $.ajax({
                    url: $editOwner.data('owners-url'),
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                }).done(function (resp) {
                    var present = {};
                    $editOwner.find('option').each(function () { present[this.value] = true; });
                    ((resp && resp.agents) || []).forEach(function (a) {
                        if (!present[String(a.id)]) {
                            $editOwner.append(new Option(a.name, String(a.id), false, false));
                        }
                    });
                }).fail(function () {
                    editOwnerLoaded = false;
                });
            });
        }

        $('#contact-edit-modal').on('submit', '#contact-edit-form', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('[type="submit"]').prop('disabled', true).text('Guardando...');
            // owner_id viaja siempre que el select exista: null = quitar el
            // responsable ("Sin responsable"); si el backend no lo recibiera,
            // interpretaría "sin cambios" y no se podría desasignar.
            var payloadOwner = null;
            if ($editOwner.length) {
                var ownerRaw = $editOwner.val();
                payloadOwner = ownerRaw ? parseInt(ownerRaw, 10) : null;
            }
            // Un checkbox sin marcar no viaja en el body, así que is_vip se
            // envía siempre explícito (1/0); tags también (array vacío = quitar
            // todas), o el backend no sabría distinguir "sin cambios" de "vaciar".
            var isVip = $form.find('[name="is_vip"]').is(':checked') ? 1 : 0;
            var tagNames = ($form.find('[name="tags[]"]').val() || []).map(function (n) { return $.trim(n); }).filter(Boolean);
            $.ajax({
                url: $root.data('update-url'),
                method: 'POST',
                // PUT real por AJAX da 405 en el Docker de este proyecto (gotcha
                // conocido). Como el body va como JSON, un campo _method no lo lee
                // Laravel (solo mira form/multipart o query string), asi que se
                // spoofea con la cabecera, igual que en public/vendor/helpdesk/*.js.
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-HTTP-Method-Override': 'PUT',
                },
                contentType: 'application/json',
                data: JSON.stringify({
                    // Nombre y Apellidos (mockup) se guardan juntos: la ficha solo tiene "name".
                    name: $.trim([$form.find('[name="first_name"]').val(), $form.find('[name="last_name"]').val()].map($.trim).filter(Boolean).join(' ')),
                    company: $form.find('[name="company"]').val(),
                    email: $form.find('[name="email"]').val(),
                    phone: $form.find('[name="phone"]').val(),
                    language: $form.find('[name="language"]').val(),
                    timezone: $form.find('[name="timezone"]').val(),
                    internal_notes: $form.find('[name="internal_notes"]').val(),
                    is_vip: isVip,
                    tags: tagNames,
                    owner_id: payloadOwner,
                }),
            }).done(function () {
                toastr.success('Contacto actualizado');
                $('#contact-edit-modal').modal('hide');
                var newName = $form.find('[name="name"]').val();
                $root.find('.contact-hero-name').text(newName);

                // Refleja VIP y etiquetas en el hero sin recargar: se actualiza
                // el payload en memoria (conservando el color de las etiquetas
                // que ya existían) y se repinta con los mismos renderers.
                var prev = {};
                ((ctfData.resumen && ctfData.resumen.tags) || []).forEach(function (t) { prev[t.name] = t; });
                ctfData.resumen = ctfData.resumen || {};
                ctfData.resumen.isVip = !!isVip;
                ctfData.resumen.tags = tagNames.map(function (n) { return prev[n] || { name: n, color: null }; });
                renderCtfHeroVip();
                renderCtfHeroTags();

                // Responsable: mismo criterio, y "Cuenta y crédito" se repinta
                // (aparece, cambia o desaparece la fila sin recargar).
                if ($editOwner.length) {
                    ctfData.resumen.owner = payloadOwner
                        ? { id: payloadOwner, name: $.trim($editOwner.find('option:selected').text()) }
                        : null;
                    renderCtfAccount();
                }
            }).fail(function (xhr) {
                var errors = (xhr.responseJSON && xhr.responseJSON.errors) ? xhr.responseJSON.errors : {};
                var first = (Object.values(errors)[0] || [])[0] || 'Error al guardar';
                toastr.error(first);
            }).always(function () {
                $btn.prop('disabled', false).text('Guardar cambios');
            });
        });

        // ── PrestaShop: detalle de pedido ──────────────────────────────
        // Delegado a todo #contact360 (no solo a #pane-prestashop, que en la
        // ficha B queda oculto como data holder): la sección visible "Compras"
        // vive fuera de ese pane y dispara el mismo data-ps-order-id.
        // También desde el modal del carrito ("Ver el pedido" tras convertirlo),
        // que vive fuera de #contact360.
        $(document).on('click', '#ps-cart-detail-modal [data-ps-order-id]', function () {
            $('#ps-cart-detail-modal').modal('hide');
        });

        $(document).on('click', '#contact360 [data-ps-order-id], #ps-cart-detail-modal [data-ps-order-id]', function () {
            var orderId = $(this).data('ps-order-id');
            // Con el puente del chat montado (_ps-chat-bridge), el pedido se
            // abre en su espacio de trabajo completo: repetir, reembolso
            // parcial, documentos, notas, cobro, incidencia de envío…
            if (orderId && String($root.data('ps-can-orders')) === '1' && typeof window.openPsOrderWorkspace === 'function') {
                window.openPsOrderWorkspace(orderId);
                return;
            }
            // Sin permiso de pedidos de la tienda no hay detalle (sus rutas
            // responden 403); el enlace no hace nada más que avisar.
            if (orderId) {
                toastr.info('No tienes permiso para ver pedidos de la tienda.');
            }
        });

        // ── PrestaShop: carrito en vivo (editar) ────────────────────────
        function findCachedCart(cartId) {
            var carts = $('#pane-prestashop').data('ps-carts') || [];
            var match = carts.filter(function (c) { return String(c.id) === String(cartId); });
            return match[0] || null;
        }

        // Delegado a todo #contact360, mismo motivo que [data-ps-order-id] arriba
        // ("Necesita atención" dispara el mismo botón "Abrir" fuera del pane oculto).
        function openPsCartModal(cartId) {
            if (!cartId) {
                return;
            }
            psActiveCartId = cartId;
            $('#ps-cart-detail-body').html(renderPsCartDetail(findCachedCart(cartId)));
            $('#ps-cart-detail-modal').modal('show');
            loadCartpay(cartId);
        }

        // ── Carrito → pedido (mockup pieza 11: "Generar pedido") ─────────
        // Reutiliza la extensión cartpay de HelpdeskPrestashop (preview +
        // convert con validateOrder de ps_wirepayment). Sin permisos
        // helpdeskprestashop.cartpay.* el botón no se pinta.
        var cartpayBase = String($root.data('ps-cartpay-base') || '').replace(/\/+$/, '');
        var cartpayPreview = null;
        var cartpayKey = null;
        var cartpayOrder = null;

        function cartpayNewKey() {
            return 'c360-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 10);
        }

        function renderCartpayFoot() {
            var $foot = $('#ps-cart-foot-actions').empty();
            var d = cartpayPreview;
            if (cartpayOrder) {
                $foot.append('<button type="button" class="psc-btn psc-btn--primary" data-ps-order-id="' + esc(cartpayOrder.order_id) + '">Ver el pedido</button>');
                if (cartpayOrder.bank_wire) {
                    $foot.append('<button type="button" class="psc-btn psc-btn--outline" data-cartpay-copy-pay>Copiar datos de pago</button>');
                }
                return;
            }
            if (!d || !(d.can || {}).convert) {
                return;
            }
            var ready = !(d.blocking || []).length;
            $foot.append('<button type="button" class="psc-btn psc-btn--primary" data-cartpay-ask' + (ready ? '' : ' disabled') + '>Generar pedido</button>');
        }

        function renderCartpay() {
            var $box = $('#c3-cartpay');
            var d = cartpayPreview;
            if (!$box.length) {
                return;
            }
            if (!d) {
                $box.empty();
                renderCartpayFoot();
                return;
            }
            var html = (d.blocking_messages || []).map(function (m) {
                return '<div class="ct-note-box"><i class="fas fa-lock mt-1"></i><span>' + esc(m) + '</span></div>';
            }).join('');
            if (!(d.can || {}).convert) {
                html += '<div class="ct-note-box"><i class="fas fa-lock mt-1"></i><span>Crear el pedido requiere el permiso de convertir carritos; sin él, solo se ve y se edita el carrito.</span></div>';
            }
            $box.html(html);
            renderCartpayFoot();
        }

        function loadCartpay(cartId) {
            cartpayPreview = null;
            cartpayOrder = null;
            cartpayKey = null;
            renderCartpay();
            if (!cartpayBase) {
                return;
            }
            $.ajax({ url: cartpayBase + '/cart/' + encodeURIComponent(cartId) + '/preview', method: 'GET', headers: { 'Accept': 'application/json' } })
                .done(function (r) {
                    if (String(psActiveCartId) !== String(cartId)) { return; }
                    cartpayPreview = (r && r.data) || null;
                    renderCartpay();
                })
                .fail(function (xhr) {
                    // 403 = sin permisos de cartpay: no se ofrece, sin ruido.
                    if (xhr.status !== 403 && String(psActiveCartId) === String(cartId)) {
                        $('#c3-cartpay').html('<div class="ct-note-box"><i class="fas fa-triangle-exclamation mt-1"></i><span>' +
                            esc((xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo comprobar si el carrito se puede convertir.') + '</span></div>');
                    }
                });
        }

        // Paso de confirmación: estado del pedido (solo los permitidos) y resumen.
        $(document).on('click', '#ps-cart-detail-modal [data-cartpay-ask]', function () {
            var d = cartpayPreview || {};
            var can = d.can || {};
            var states = (d.states || []).filter(function (st) { return !st.paid || can.convert_paid; });
            if (!states.length) {
                return;
            }
            $('#c3-cartpay').html(
                '<div><span class="ct-flabel">Estado del pedido a crear</span><div class="d-flex flex-column gap-2">' +
                states.map(function (st, i) {
                    return '<label class="ct-radio-opt"><input type="radio" name="c3-cartpay-state" value="' + esc(st.key) + '"' + (i === 0 ? ' checked' : '') + '>' +
                        '<span>' + esc(st.name) + ' <span class="ct-meta">· ' + (st.paid ? 'se registra como cobrado' : 'sin pagar, por transferencia') + '</span></span></label>';
                }).join('') + '</div></div>' +
                '<div class="psc-note psc-note--warn"><i class="fas fa-triangle-exclamation mt-1"></i><span>Se creará un pedido real de <b>' + esc(money(d.total)) +
                    '</b> con pago ' + esc(d.payment_method || 'por transferencia') + '. La tienda envía el correo de confirmación y el pedido pasa a Gestión como cualquier otro.</span></div>'
            );
            $('#ps-cart-foot-actions').html(
                '<button type="button" class="psc-btn psc-btn--primary" data-cartpay-go>Crear el pedido</button>' +
                '<button type="button" class="psc-btn psc-btn--outline" data-cartpay-back>Volver</button>'
            );
            cartpayKey = cartpayKey || cartpayNewKey();
        });

        $(document).on('click', '#ps-cart-detail-modal [data-cartpay-back]', renderCartpay);

        $(document).on('click', '#ps-cart-detail-modal [data-cartpay-go]', function () {
            var $btn = $(this).prop('disabled', true).text('Creando pedido…');
            var state = $('input[name="c3-cartpay-state"]:checked').val();
            var cartId = psActiveCartId;
            $.ajax({
                url: cartpayBase + '/cart/' + encodeURIComponent(cartId) + '/convert',
                method: 'POST',
                data: { state: state },
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'Idempotency-Key': cartpayKey || cartpayNewKey() }
            }).done(function (r) {
                var d = (r && r.data) || {};
                cartpayKey = null;
                cartpayOrder = { order_id: d.order_id, reference: d.reference };
                $('#c3-cartpay').html(
                    '<div class="psc-note psc-note--good"><i class="fas fa-circle-check mt-1"></i><span>Pedido <b>' + esc(d.reference || d.order_id) + '</b> creado · ' +
                        esc(d.state_name || '') + ' · ' + esc(money(d.total)) + '</span></div>' +
                    (r && r.warning ? '<div class="ct-note-box"><i class="fas fa-triangle-exclamation mt-1"></i><span>' + esc(r.warning) + '</span></div>' : '')
                );
                renderCartpayFoot();
                notifySuccess('Pedido ' + (d.reference || d.order_id) + ' creado en PrestaShop.');
                // Datos de transferencia para el cliente (solo si queda pendiente de pago).
                $.ajax({ url: cartpayBase + '/orders/' + encodeURIComponent(d.order_id) + '/payment', method: 'GET', headers: { 'Accept': 'application/json' } })
                    .done(function (p) {
                        var pay = p && p.data;
                        if (pay && pay.bank_wire && !pay.is_paid && parseFloat(pay.pending) > 0) {
                            cartpayOrder.bank_wire = pay;
                            renderCartpayFoot();
                        }
                    });
                // El carrito ya es pedido: se refrescan las fuentes de la ficha.
                loadTab('prestashop', true);
            }).fail(function (xhr) {
                if (xhr.status === 422) { cartpayKey = cartpayNewKey(); }
                $btn.prop('disabled', false).text('Crear el pedido');
                reportFailure(xhr, 'No se pudo crear el pedido.');
            });
        });

        // "Datos de pago": en la tienda no hay enlace para pagar un pedido ya
        // creado; lo real son los datos de transferencia con la referencia.
        $(document).on('click', '#ps-cart-detail-modal [data-cartpay-copy-pay]', function () {
            var pay = cartpayOrder && cartpayOrder.bank_wire;
            if (!pay) {
                return;
            }
            var bw = pay.bank_wire || {};
            var text = [
                'Para completar el pago del pedido ' + pay.reference + ' (' + money(pay.pending) + ') puedes hacer una transferencia con estos datos:',
                'Titular: ' + bw.owner,
                'Cuenta: ' + bw.details,
                bw.address ? 'Banco: ' + bw.address : null,
                'Concepto: ' + (bw.concept || pay.reference),
                'En cuanto recibamos la transferencia, preparamos tu pedido.'
            ].filter(Boolean).join('\n');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function () { notifySuccess('Datos de pago copiados: pégalos en la conversación o el email del cliente.'); });
            }
        });

        $root.on('click', '[data-ps-cart-id]', function () {
            openPsCartModal($(this).data('ps-cart-id'));
        });

        // Vuelve a pedir la pestaña completa (los carritos no tienen fetch
        // propio, viajan dentro de /tab/prestashop) tras cada mutación, y
        // refresca tanto la lista de fondo como el modal abierto.
        function refreshPrestashopCartModal() {
            $.ajax({
                url: baseUrl + '/tab/prestashop',
                method: 'GET',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            }).done(function (resp) {
                var payload = (resp && resp.data) ? resp.data : {};
                var $pane = $('#pane-prestashop');
                $pane.html(renderPrestashop(payload));
                $pane.data('ps-external-id', (payload && payload.external_id != null) ? payload.external_id : null);
                $pane.data('ps-carts', (payload && Array.isArray(payload.carts)) ? payload.carts : []);
                $('#ps-cart-detail-body').html(renderPsCartDetail(findCachedCart(psActiveCartId)));
                // Tras cada cambio del carrito se vuelve a comprobar si se puede convertir.
                loadCartpay(psActiveCartId);
                // renderPrestashop() ya pinta direcciones/devoluciones/mensajes/
                // lista de deseos/reembolsos síncronamente desde este mismo
                // payload — no hace falta recargarlas aparte.
            });
        }

        function psCartWrite(method, suffix, data, fallback) {
            if (!psCartBaseUrl || !psActiveCartId) {
                return $.Deferred().reject().promise();
            }
            return writeRequest(method, psCartBaseUrl + '/' + encodeURIComponent(psActiveCartId) + suffix, data)
                .done(function () {
                    refreshPrestashopCartModal();
                })
                .fail(function (xhr) {
                    reportFailure(xhr, fallback);
                });
        }

        // Selector de direcciones del carrito ("Usar esta dirección" → psCartWrite()).
        function openPsAddressPicker(addressType) {
            psAddressPickerType = addressType === 'invoice' ? 'invoice' : 'delivery';
            $('#psAddressPickerModalLabel').text(psAddressPickerType === 'invoice' ? 'Elegir dirección de facturación' : 'Elegir dirección de envío');
            $('#ps-address-picker-body').html(skeleton(3));
            $('#ps-address-picker-modal').modal('show');
            $.ajax({
                url: psAddressesUrl,
                method: 'GET',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            }).done(function (resp) {
                var addresses = (resp && (resp.addresses || resp.data)) || [];
                if (!addresses.length) {
                    $('#ps-address-picker-body').html(emptyState('fa-location-dot', 'Sin direcciones', 'Este cliente no tiene direcciones guardadas.'));
                    return;
                }
                var html = '<div class="ct-row-list">';
                addresses.forEach(function (a) {
                    var fullName = [a.firstname, a.lastname].filter(Boolean).join(' ');
                    html += '<div class="ct-card mb-2">' +
                        '<div class="ct-card-title">' + esc(a.alias || 'Dirección') + '</div>' +
                        '<div class="ct-info-table">' +
                            '<div class="ct-info-row"><span class="ct-info-label">Nombre</span><span class="ct-info-value">' + esc(fullName || '—') + (a.company ? ' · ' + esc(a.company) : '') + '</span></div>' +
                            '<div class="ct-info-row"><span class="ct-info-label">Dirección</span><span class="ct-info-value">' + esc(a.address || '—') + '</span></div>' +
                            '<div class="ct-info-row"><span class="ct-info-label">Ciudad</span><span class="ct-info-value">' + esc([a.postcode, a.city].filter(Boolean).join(' ') || '—') + (a.country ? ', ' + esc(a.country) : '') + '</span></div>' +
                            (a.phone ? '<div class="ct-info-row"><span class="ct-info-label">Teléfono</span><span class="ct-info-value">' + esc(a.phone) + '</span></div>' : '') +
                        '</div>' +
                        '<button type="button" class="btn btn-sm ct-btn-outline mt-2" data-ps-address-use="' + esc(a.id) + '">Usar esta dirección</button>' +
                        '</div>';
                });
                html += '</div>';
                $('#ps-address-picker-body').html(html);
            }).fail(function () {
                $('#ps-address-picker-body').html(errorState());
            });
        }

        // Delegado en document, no en $root: los modales de carrito/dirección
        // viven fuera de #contact360 en el DOM (ver show.blade.php), así que
        // un listener en $root nunca vería estos clics burbujear.
        $(document).on('click', '#ps-cart-detail-body [data-cart-action]', function () {
            var $btn = $(this);
            var action = $btn.data('cart-action');

            if (action === 'change-address') {
                openPsAddressPicker($btn.data('address-type'));
                return;
            }

            if (action === 'apply-voucher') {
                var code = $('#ps-cart-voucher-input').val();
                if (!code) {
                    return;
                }
                $btn.prop('disabled', true);
                psCartWrite('POST', '/voucher', { code: code }, 'El cupón no se pudo aplicar').always(function () {
                    $btn.prop('disabled', false);
                });
                return;
            }

            if (action === 'remove-voucher') {
                $btn.prop('disabled', true);
                // _method: un DELETE real por AJAX da 405 detrás del nginx de Docker.
                psCartWrite('POST', '/voucher', { code: String($btn.data('voucher-code')), _method: 'DELETE' }, 'El cupón no se pudo quitar').always(function () {
                    $btn.prop('disabled', false);
                });
                return;
            }

            var $row = $btn.closest('[data-cart-product-id]');
            var productId = $row.data('cart-product-id');
            // attribute_id: el backend exige nullable|min:1 (0 no es una
            // combinación válida de PrestaShop) — se omite del payload en vez
            // de mandar 0 cuando el producto no tiene combinación.
            var attributeId = parseInt($row.data('cart-attribute-id'), 10) || 0;

            if (action === 'qty-inc' || action === 'qty-dec') {
                var current = parseInt($row.data('cart-qty'), 10) || 1;
                var next = action === 'qty-inc' ? current + 1 : current - 1;
                if (next < 0) {
                    return;
                }
                var qtyData = { product_id: productId, quantity: next };
                if (attributeId) { qtyData.attribute_id = attributeId; }
                $btn.prop('disabled', true);
                psCartWrite('PATCH', '/products/quantity', qtyData, 'No se pudo actualizar la cantidad').always(function () {
                    $btn.prop('disabled', false);
                });
                return;
            }

            if (action === 'remove') {
                var removeData = { product_id: productId };
                if (attributeId) { removeData.attribute_id = attributeId; }
                $btn.prop('disabled', true);
                psCartWrite('DELETE', '/products', removeData, 'No se pudo quitar el producto').always(function () {
                    $btn.prop('disabled', false);
                });
            }
        });

        // ── PrestaShop: buscar producto y añadirlo al carrito ───────────
        function runPsCartProductSearch() {
            var $results = $('#ps-cart-product-search-results');
            var query = $('#ps-cart-product-search-input').val();
            if (!query || !psProductsUrl) {
                return;
            }
            $results.html(skeleton(2));
            $.ajax({
                url: psProductsUrl,
                method: 'GET',
                data: { q: query },
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            }).done(function (resp) {
                var products = (resp && resp.products) || [];
                $results.html(renderPsProductSearchResults(products));
            }).fail(function () {
                $results.html(errorState());
            });
        }

        $(document).on('keydown', '#ps-cart-product-search-input', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                runPsCartProductSearch();
            }
        });

        $(document).on('click', '#ps-cart-product-search-results [data-ps-product-add]', function () {
            var $btn = $(this);
            var productId = $btn.data('ps-product-add');
            if (!productId) {
                return;
            }
            $btn.prop('disabled', true).text('Añadiendo...');
            psCartWrite('POST', '/products', { product_id: productId, quantity: 1 }, 'No se pudo añadir el producto')
                .done(function () {
                    notifySuccess('Producto añadido al carrito');
                })
                .always(function () {
                    $btn.prop('disabled', false).text('Añadir');
                });
        });

        $(document).on('click', '#ps-address-picker-body [data-ps-address-use]', function () {
            var $btn = $(this);
            var addressId = $btn.data('ps-address-use');
            if (!addressId) {
                return;
            }

            // La dirección de un pedido se cambia en el espacio de trabajo del
            // chat; este selector ya solo sirve al carrito.
            if (!psActiveCartId) { return; }
            var request = psCartWrite('POST', '/address', { address_id: addressId, type: psAddressPickerType }, 'No se pudo cambiar la dirección');

            $btn.prop('disabled', true).text('Aplicando...');
            request
                .done(function () {
                    $('#ps-address-picker-modal').modal('hide');
                })
                .always(function () {
                    $btn.prop('disabled', false).text('Usar esta dirección');
                });
        });

        // ── PrestaShop: añadir/editar dirección ─────────────────────────
        $root.on('click', '#ps-address-add-btn', function () {
            openPsAddressForm(null);
        });

        $root.on('click', '#ps-addresses-list [data-ps-address-edit]', function () {
            var id = $(this).data('ps-address-edit');
            var address = psAddressesCache.filter(function (a) { return String(a.id) === String(id); })[0];
            if (address) {
                openPsAddressForm(address);
            }
        });

        // El modal vive fuera de #contact360 (ver show.blade.php) — delegado
        // en document, igual que el resto de modales de PrestaShop.
        $(document).on('submit', '#ps-address-form', function (e) {
            e.preventDefault();
            var $btn = $('#ps-address-form-submit');
            var data = {};
            $(this).serializeArray().forEach(function (f) {
                if (f.value !== '') {
                    data[f.name] = f.value;
                }
            });

            var url = psAddressEditingId
                ? psAddressesUrl + '/' + encodeURIComponent(psAddressEditingId)
                : psAddressesUrl;
            var method = psAddressEditingId ? 'PATCH' : 'POST';

            $btn.prop('disabled', true).text('Guardando...');
            writeRequest(method, url, data)
                .done(function () {
                    notifySuccess(psAddressEditingId ? 'Dirección actualizada' : 'Dirección añadida');
                    $('#ps-address-form-modal').modal('hide');
                    loadPsAddresses();
                })
                .fail(function (xhr) {
                    reportFailure(xhr, 'No se pudo guardar la dirección');
                })
                .always(function () {
                    $btn.prop('disabled', false).text('Guardar dirección');
                });
        });

        // ── Merge contacts ─────────────────────────────────────────────
        var $mergeModal = $('#contact-merge-modal');
        var selectedLoserId = null;

        function showMergeStep(step) {
            var preview = step === 'preview';
            $('#merge-step-search').toggleClass('d-none', preview);
            $('#merge-preview').toggleClass('d-none', !preview);
            $('#merge-execute-btn, #merge-back-btn').toggleClass('d-none', !preview);
        }

        function openMergeModal() {
            selectedLoserId = null;
            $('#merge-search-input').val('');
            $('#merge-search-results').empty();
            showMergeStep('search');
            $mergeModal.modal('show');
            // ?loser= (botón "Fusionar" de la fila de duplicado del listado):
            // salta directo a la vista previa con ese duplicado.
            var preset = new URLSearchParams(location.search).get('loser');
            if (preset) {
                loadMergePreview(preset);
            }
        }

        $mergeModal.on('click', '#merge-back-btn', function () {
            selectedLoserId = null;
            showMergeStep('search');
        });

        function mergeCount(n, one, many) {
            n = parseInt(n, 10) || 0;
            return n + ' ' + (n === 1 ? one : many);
        }

        // Paso 2 (mockup pieza 06): tarjetas "Se conserva / Se absorbe" y el
        // impacto real de CustomerMergeAction (qué se mueve y qué se descarta).
        function loadMergePreview(loserId) {
            selectedLoserId = loserId;
            $.get($root.data('merge-preview-url'), { loser_id: loserId })
                .done(function (resp) {
                    var w = resp.data.winner;
                    var l = resp.data.loser;
                    var card = function (cls, eyebrow, c) {
                        return '<div class="ct-merge-card' + cls + '"><span class="e">' + eyebrow + '</span>' +
                            '<span class="n">' + esc(c.name || '—') + '</span>' +
                            '<span class="m">' + esc([mergeCount(c.total_conversations, 'conversación', 'conversaciones'), c.since_year].filter(Boolean).join(' · ')) + '</span></div>';
                    };
                    var rows = '<div class="ct-kv"><span class="k">Se moverán</span><span class="v">' +
                        esc(mergeCount(l.total_conversations, 'conversación', 'conversaciones') + ' · ' + mergeCount(l.messages_count, 'mensaje', 'mensajes')) + '</span></div>';
                    if (l.email && w.email && l.email.toLowerCase() !== w.email.toLowerCase()) {
                        rows += '<div class="ct-kv"><span class="k">Email secundario</span><span class="v mono">' + esc(l.email) + '</span></div>';
                    } else if (l.email && !w.email) {
                        rows += '<div class="ct-kv is-good"><span class="k">Email</span><span class="v">pasa al principal</span></div>';
                    }
                    if (l.phone && w.phone && l.phone !== w.phone) {
                        rows += '<div class="ct-kv"><span class="k">Conflicto de teléfono</span><span class="v">se mantiene el del principal</span></div>';
                    } else if (l.phone && !w.phone) {
                        rows += '<div class="ct-kv is-good"><span class="k">Teléfono</span><span class="v">pasa al principal</span></div>';
                    }
                    $('#merge-preview-content').html(
                        '<div class="ct-merge-cards">' + card(' is-keep', 'Se conserva', w) + card('', 'Se absorbe', l) + '</div>' + rows
                    );
                    showMergeStep('preview');
                })
                .fail(function (xhr) {
                    reportFailure(xhr, 'No se pudo cargar la vista previa de la fusión');
                });
        }

        $root.on('click', '#contact-merge-btn', openMergeModal);

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
            loadMergePreview($(this).data('loser-id'));
        });

        $mergeModal.on('click', '#merge-execute-btn', function () {
            // El propio paso 2 es la confirmación (mockup): sin confirm() del navegador.
            if (!selectedLoserId) { return; }
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
                $btn.prop('disabled', false).text('Fusionar contactos');
            });
        });

        // ── Ban / Unban: ambos triggers del dropdown abren el mismo modal
        // (Blade ya decide su contenido según $customer->banned_at) ──────
        $root.on('click', '#contact-ban-btn, #contact-unban-btn', function () {
            $('#contact-ban-modal').modal('show');
        });

        $('#contact-ban-modal').on('click', '#contact-ban-confirm-btn', function () {
            var $btn = $(this).prop('disabled', true);
            var reason = $('#contact-ban-reason').val();
            var note = $.trim($('#contact-ban-note').val() || '');
            $.ajax({
                url: $root.data('ban-url'),
                method: 'POST',
                data: { reason: note ? (reason + ' — ' + note) : reason },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            }).done(function () {
                toastr.success('Contacto bloqueado');
                setTimeout(function () { location.reload(); }, 800);
            }).fail(function () {
                toastr.error('Error al bloquear el contacto');
                $btn.prop('disabled', false);
            });
        });

        $('#contact-ban-modal').on('click', '#contact-unban-confirm-btn', function () {
            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: $root.data('unban-url'),
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            }).done(function () {
                toastr.success('Contacto desbloqueado');
                setTimeout(function () { location.reload(); }, 800);
            }).fail(function () {
                toastr.error('Error al desbloquear el contacto');
                $btn.prop('disabled', false);
            });
        });

        // ── Acción automática (?action=…) ──────────────────────────────
        // El listado enlaza a la ficha con una acción; el Blade ya la filtró
        // por whitelist, permisos y estado real (data-auto-action solo existe
        // si procede), así que aquí solo se abre el modal correspondiente.
        // Va al final para que todos los handlers de arriba ya estén registrados.
        var autoAction = String($root.data('auto-action') || '');
        var autoModals = { edit: '#contact-edit-modal', sync: '#contact-sync-modal', ban: '#contact-ban-modal', unban: '#contact-ban-modal' };

        if (autoAction === 'merge') {
            openMergeModal();
        } else if (autoAction === 'ticket') {
            if (ctfLoaded.tickets) {
                openTicketModalFromAction();
            } else {
                ctfPendingTicketAction = true;
            }
        } else if (autoModals[autoAction] && $(autoModals[autoAction]).length) {
            $(autoModals[autoAction]).modal('show');
        }

        // Limpia ?action= de la URL (con o sin acción válida) para que recargar
        // o compartir el enlace no vuelva a abrir el modal.
        if (history.replaceState && /[?&](action|loser)=/.test(location.search)) {
            var cleanUrl = new URL(location.href);
            cleanUrl.searchParams.delete('action');
            cleanUrl.searchParams.delete('loser');
            history.replaceState(null, '', cleanUrl.pathname + cleanUrl.search + cleanUrl.hash);
        }
    });

})(window.jQuery);
