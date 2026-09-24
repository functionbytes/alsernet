/*!
 * HelpdeskPrestashop · tab "Tienda" del panel derecho del inbox.
 *
 * Diseño: "Alvarez PrestaShop en el Chat" (pieza Unificado + 1, 2, 5, 7,
 * 13, 14 y 17). jQuery sin frameworks; solo cambia clases, nunca estilos.
 *
 * Todo el tab sale de UNA llamada: GET /customers/{id}/ps/orders devuelve el
 * customer.helpdesk_context completo (cliente, pedidos, carritos,
 * direcciones, devoluciones, cupones, reembolsos, mensajes y lista de
 * deseos). Los endpoints sueltos (/ps/returns, /ps/vouchers…) solo se usan
 * para refrescar un bloque después de una escritura.
 *
 * Estados honestos (pieza 13): 503 del endpoint = el puente no responde →
 * aviso gris oscuro, y si hay caché se pinta marcada como tal. found=false
 * con el puente sano = "Sin cliente en PrestaShop · Buscar y vincular".
 *
 * Expone window.PscStore para prestashop-chat.js y order-workspace.js.
 *
 * OJO 1: depende de window.HDCommerce (modals/_commerce-js), que se carga
 * antes por el orden de @push('scripts').
 * OJO 2: la fuente es este fichero; asset() sirve desde
 * public/modules/helpdeskprestashop/js/ — hay que copiarlo allí tras editar.
 */
(function () {
    var PS_MONTHS = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    var COLLAPSE_KEY = 'psc.collapse';
    var ALERT_MAX = 3;

    var psCtx = null;          // último contexto recibido (o caché servida con el puente caído)
    var psCtxUrl = null;       // URL para la que se cargó psCtx (cambia con la conversación)
    var psFetching = false;
    var psWaiters = [];
    var psListeners = [];
    var psActiveCartId = null;
    var psAddrPickerType = 'both';
    var psHealthTimer = null;

    /* ── Utilidades ───────────────────────────────────────────── */

    function esc(s) {
        return $('<span>').text(s == null ? '' : String(s)).html();
    }

    function escAttr(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // 2418.6 → "2.418,60". es-ES no agrupa los números de 4 cifras con
    // toLocaleString (minimumGroupingDigits = 2), así que se formatea a mano.
    function psNum(n) {
        var fixed = (parseFloat(n) || 0).toFixed(2);
        var neg = fixed.charAt(0) === '-';
        var parts = fixed.replace('-', '').split('.');
        var int = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return (neg ? '−' : '') + int + ',' + parts[1];
    }

    function psMoney(n) {
        return psNum(n) + ' €';
    }

    function psParse(iso) {
        if (!iso) { return null; }
        var d = new Date(String(iso).replace(' ', 'T'));
        return isNaN(d.getTime()) ? null : d;
    }

    function psFormatDate(iso, withYear) {
        var d = psParse(iso);
        if (!d) { return '—'; }
        var out = ('0' + d.getDate()).slice(-2) + ' ' + PS_MONTHS[d.getMonth()];
        return withYear ? out + ' ' + d.getFullYear() : out;
    }

    function psDaysAgo(iso) {
        var d = psParse(iso);
        return d ? Math.floor((Date.now() - d.getTime()) / 86400000) : null;
    }

    function psRelativeTime(iso) {
        var d = psParse(iso);
        if (!d) { return ''; }
        var mins = Math.max(0, Math.round((Date.now() - d.getTime()) / 60000));
        if (mins < 1) { return 'ahora mismo'; }
        if (mins < 60) { return 'hace ' + mins + ' min'; }
        var hours = Math.round(mins / 60);
        if (hours < 24) { return 'hace ' + hours + (hours === 1 ? ' hora' : ' horas'); }
        var days = Math.floor(mins / 1440);
        return 'hace ' + days + (days === 1 ? ' día' : ' días');
    }

    // Miniaturas que no cargan (staging sin imágenes, CDN caído): se
    // sustituyen por el placeholder rayado en vez del icono de imagen rota.
    // Con host de respaldo configurado (staging sin imágenes), se reintenta
    // una vez con la misma ruta en ese host antes de rendirse.
    document.addEventListener('error', function (e) {
        var img = e.target;
        if (!img || img.tagName !== 'IMG' || !img.parentNode || !img.parentNode.classList || !img.parentNode.classList.contains('psc-thumb')) { return; }
        var fallback = String($('#bv-ps-orders').data('ps-img-fallback') || '');
        if (fallback && !img.getAttribute('data-psc-retried')) {
            try {
                var u = new URL(img.src, window.location.href);
                if (u.origin !== new URL(fallback).origin) {
                    img.setAttribute('data-psc-retried', '1');
                    img.src = fallback + u.pathname;
                    return;
                }
            } catch (err) { /* URL inválida: placeholder */ }
        }
        img.parentNode.textContent = 'foto';
    }, true);

    function psInitials(name) {
        return String(name || '').replace(/[^\p{L}\s]/gu, '').trim().split(/\s+/).slice(0, 2)
            .map(function (w) { return w.charAt(0); }).join('').toUpperCase() || '?';
    }

    function psEmptyHtml(icon, title, sub) {
        return '<div class="psc-state">' +
            (icon ? '<i class="fas ' + icon + '"></i>' : '') +
            '<span class="t">' + esc(title) + '</span>' +
            (sub ? '<span class="s">' + esc(sub) + '</span>' : '') +
        '</div>';
    }

    function psWarnHtml(text, retry) {
        return '<div class="psc-note psc-note--warn">' +
            '<span class="psc-note-txt">' + esc(text) + '</span>' +
            (retry ? '<button type="button" class="psc-note-act" data-psc-retry="store">Reintentar</button>' : '') +
        '</div>';
    }

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    // Inserta en el composer SIN enviar: si ya hay texto, en línea nueva;
    // deja el foco y el cursor al final.
    function psInsertComposer(text) {
        var $ta = $('.bv-composer-input').first();
        if (!$ta.length || !text) { return false; }
        var current = String($ta.val() || '').replace(/\s+$/, '');
        var next = current ? current + '\n\n' + text : text;
        $ta.val(next).trigger('input');
        var el = $ta.get(0);
        el.focus();
        if (el.setSelectionRange) { el.setSelectionRange(next.length, next.length); }
        return true;
    }

    // Estado de pedido → familia visual del mockup (sin barras de color):
    // acento = pendiente, verde = en curso, verde fuerte = enviado,
    // gris = cerrado, gris oscuro = cancelado.
    function psOrderKind(order) {
        var st = order.state || {};
        var name = String(st.name || order.state_name || order.status || '').toLowerCase();
        if (/cancel|anulad|reembols|devuelto|error/.test(name)) { return 'cancelled'; }
        if (/entregad|complet|finaliz/.test(name)) { return 'closed'; }
        if (st.shipped || /enviad|transit|reparto/.test(name)) { return 'shipped'; }
        if (st.paid || /prepar|acept|erp/.test(name)) { return 'prep'; }
        return 'pending';
    }

    var PS_KIND_TAG = {
        pending: 'psc-tag--pending',
        prep: 'psc-tag--progress',
        shipped: 'psc-tag--done',
        closed: 'psc-tag--closed',
        cancelled: 'psc-tag--blocked',
    };

    function psOrderStateName(order) {
        return (order.state && order.state.name) || order.state_name || order.status || 'Pendiente';
    }

    function psOrderTotal(order) {
        var total = parseFloat((order.totals && order.totals.total) || order.total || 0);
        if (total <= 0 && order.totals && order.totals.products != null) {
            total = parseFloat(order.totals.products);
        }
        return total || 0;
    }

    function psOrderLines(order) {
        return order.lines || order.products || [];
    }

    function psOrderFirstLine(order) {
        var lines = psOrderLines(order);
        if (!lines.length) { return ''; }
        return (lines[0].name || 'Producto') + (lines.length > 1 ? ' +' + (lines.length - 1) : '');
    }

    function psReturnKind(stateName) {
        var s = String(stateName || '').toLowerCase();
        if (/denegad|cancelad|rechazad/.test(s)) { return 'blocked'; }
        if (/complet|recibid|reembols/.test(s)) { return 'done'; }
        return 'open';
    }

    function psVoucherStateInfo(v) {
        if (v.expired) { return { cls: 'psc-tag--blocked', txt: 'Caducado', available: false }; }
        var dateTo = psParse(v.date_to);
        if (dateTo && dateTo.getTime() < Date.now()) {
            return { cls: 'psc-tag--blocked', txt: 'Caducado', available: false };
        }
        if (v.used || v.quantity === 0) { return { cls: 'psc-tag--closed', txt: 'Usado', available: false }; }
        if (v.active === false) { return { cls: 'psc-tag--closed', txt: 'Inactivo', available: false }; }
        return { cls: 'psc-tag--progress', txt: 'Disponible', available: true };
    }

    function psVoucherValueLabel(v) {
        var pct = parseFloat(v.reduction_percent);
        if (pct > 0) { return '−' + String(pct).replace('.', ',') + ' %'; }
        var amount = parseFloat(v.reduction_amount);
        if (amount > 0) { return '−' + psMoney(amount); }
        if (v.free_shipping) { return 'Envío gratis'; }
        return '—';
    }

    /* ── Carga del contexto (una sola llamada) ───────────────── */

    function $store() { return $('#bv-ps-orders'); }

    function ordersUrl() {
        return $store().data('ps-orders-url') || '';
    }

    function activeCart() {
        var carts = (psCtx && psCtx.carts) || [];
        return carts.length ? carts[0] : null;
    }

    function notify() {
        psListeners.forEach(function (cb) {
            try { cb(psCtx); } catch (e) { /* un listener roto no tumba el resto */ }
        });
    }

    // onDone(ctx) — ctx.bridge: 'ok' | 'down' | 'none'. Nunca null: el
    // llamador decide qué pintar con ctx.bridge/ctx.stale.
    function fetchCtx(force, onDone) {
        var url = ordersUrl();
        if (!url) {
            psCtx = { bridge: 'none', customer: null, orders: [], carts: [] };
            onDone(psCtx);
            return;
        }
        if (!force && psCtx && psCtxUrl === url && psCtx.bridge === 'ok') { onDone(psCtx); return; }

        psWaiters.push(onDone);
        if (psFetching) { return; }
        psFetching = true;

        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            data: force ? { fresh: 1 } : {},
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
        }).done(function (r) {
            psCtx = r || {};
            psCtx.bridge = 'ok';
        }).fail(function (xhr) {
            var r = xhr.responseJSON || {};
            psCtx = r;
            psCtx.bridge = 'down';
            psCtx.orders = r.orders || [];
            psCtx.carts = r.carts || [];
        }).always(function () {
            psFetching = false;
            psCtxUrl = url;
            var cbs = psWaiters.splice(0);
            cbs.forEach(function (cb) { cb(psCtx); });
            notify();
        });
    }

    // Refresco de un bloque suelto tras una escritura (sin tirar todo el tab).
    function fetchScoped(suffix, key, onDone) {
        var base = window.HDCommerce ? window.HDCommerce.base() : null;
        if (!base) { onDone(null); return; }
        $.ajax({
            url: base + suffix,
            method: 'GET',
            dataType: 'json',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
        }).done(function (r) {
            var data = r[key] || [];
            if (psCtx) { psCtx[key] = data; }
            onDone(data);
        }).fail(function () { onDone(null); });
    }

    /* ── Tab "Tienda": orquestación ───────────────────────────── */

    function refreshStoreTab(force) {
        setPsHealth(null, 'Cargando…');
        fetchCtx(!!force, renderStore);
        subscribePsCartLive();
    }

    function renderStore(ctx) {
        var customer = ctx.customer || null;
        var hasData = !!(customer && customer.found);

        // Puente caído y sin caché: un único aviso ámbar por bloque, el tab
        // sigue usable (acciones de chat) y el pie dice qué pasa.
        if (ctx.bridge === 'down' && !hasData) {
            toggleLinkMode(false);
            $('#ps-alerts-wrap').empty();
            $('#ps-customer-body .psc-skel').remove();
            ['#ps-addr-defaults', '#ps-vch-inline', '#ps-orders-body', '#ps-activity-body'].forEach(function (sel) {
                $(sel).html(psWarnHtml('No se ha podido cargar la tienda.', true));
            });
            $('#ps-addr-summary, #ps-vch-summary').text('—');
            $('#ps-orders-count').text('—');
            $('#ps-cart-live-wrap').empty();
            $('#ps-refunds-wrap').empty();
            $('#ps-replies-wrap').empty();
            setPsHealth('down', 'Sin conexión con el puente');
            return;
        }

        // Puente sano y cliente no encontrado: todo colapsa a una caja.
        if (ctx.bridge === 'ok' && !hasData) {
            toggleLinkMode(true);
            setPsHealth(null, 'Datos de ' + psFreshness(ctx.fetched_at));
            return;
        }

        toggleLinkMode(false);
        renderPsCustomerCard(customer);
        renderPsAlerts();
        renderPsAddressesInline(ctx.addresses || []);
        renderPsVouchersInline(ctx.vouchers || []);
        renderPsCartLive();
        renderPsOrdersCard(ctx.orders || []);
        renderPsRefundsCard(ctx.refunds || [], false);
        renderPsActivity();
        renderPsQuickReplies();
        applyCollapseState();

        if (ctx.bridge === 'down') {
            setPsHealth('stale', 'No responde · mostrando caché');
        } else {
            setPsHealth(null, 'Datos de ' + psFreshness(ctx.fetched_at));
        }
        // Punto de extensión: js/ext/*.js pueden añadir bloques al tab Tienda
        // (dentro de #ps-ext-wrap) al recibir esto.
        $(document).trigger('psc:store-rendered', [ctx]);
    }

    function psFreshness(ts) {
        if (!ts) { return 'ahora mismo'; }
        var mins = Math.max(0, Math.round((Date.now() / 1000 - ts) / 60));
        return mins < 1 ? 'hace menos de 1 min' : 'hace ' + mins + ' min';
    }

    function setPsHealth(state, text) {
        $('#ps-health').removeClass('psc-health--stale psc-health--down')
            .toggleClass('psc-health--' + state, !!state);
        $('#ps-health-txt').text(text);

        clearInterval(psHealthTimer);
        if (!state && psCtx && psCtx.fetched_at) {
            psHealthTimer = setInterval(function () {
                if (!$('#ps-health').length) { clearInterval(psHealthTimer); return; }
                $('#ps-health-txt').text('Datos de ' + psFreshness(psCtx.fetched_at));
            }, 60000);
        }
    }

    $(document).on('click', '[data-psc-retry="store"]', function () { refreshStoreTab(true); });

    // Lazy-load al abrir el tab "Tienda" — capture phase nativo (el panel
    // derecho se sustituye entero al cambiar de conversación y un listener en
    // document sobrevive a ese reemplazo).
    document.addEventListener('click', function (e) {
        if (!$(e.target).closest('.bv-right-tab[data-bv-tab="ps-orders"]').length) { return; }
        refreshStoreTab(false);
    }, true);

    // Si el inbox abre directamente con ?rtab=ps-orders no hay clic: se
    // comprueba al cargar y cada vez que el panel derecho se repinta.
    function autoloadIfVisible() {
        var $tab = $store();
        if ($tab.length && !$tab.hasClass('bv-tab-hidden') && ordersUrl() !== psCtxUrl) {
            refreshStoreTab(false);
        }
    }
    $(autoloadIfVisible);
    // conversations-list.js emite 'pane:loaded' tras sustituir el panel al
    // cambiar de conversación sin recargar la página.
    document.addEventListener('pane:loaded', function () {
        psCtx = null;
        psCtxUrl = null;
        setTimeout(autoloadIfVisible, 0);
    });

    /* ── Sin cliente en PrestaShop: Buscar y vincular ──────────── */

    function toggleLinkMode(on) {
        var $tab = $store();
        $tab.toggleClass('psc-store--unlinked', on);
        if (!on) { return; }
        var c = window.HDCommerce ? window.HDCommerce.customer() : {};
        var email = c.email || '';
        var phone = String(c.phone || '').replace(/\s+/g, '');
        $('#ps-link-intro').html(email
            ? 'El email <b>' + esc(email) + '</b> no existe en la tienda. Busca al cliente por otro dato.'
            : 'Este contacto no tiene email. Busca al cliente por teléfono, nombre o ID.');
        $('#ps-link-query').val(email || phone);
        $('#ps-link-type').val(email ? 'email' : (phone ? 'phone' : 'name'));
        $('#ps-link-results').empty();
        $('#ps-link-submit').prop('disabled', true).addClass('is-disabled');
    }

    $(document).on('click', '#ps-link-search', function () {
        var url = $store().data('ps-link-search-url');
        var q = String($('#ps-link-query').val() || '').trim();
        var type = $('#ps-link-type').val() || 'email';
        if (!url || q.length < 2) { return; }
        var $btn = $(this).prop('disabled', true);
        $('#ps-link-results').html('<div class="psc-loading"><i class="fas fa-spinner fa-spin"></i> Buscando…</div>');
        $.ajax({
            url: url, method: 'GET', dataType: 'json',
            data: { platform: 'prestashop', q: q, type: type },
            headers: { 'Accept': 'application/json' },
        }).done(function (r) {
            if (r.platform_error) {
                $('#ps-link-results').html(psWarnHtml('PrestaShop no ha respondido a la búsqueda.', false));
                return;
            }
            var rows = r.results || [];
            if (!rows.length) {
                $('#ps-link-results').html(psEmptyHtml(null, 'Sin resultados', 'Prueba con otro dato: teléfono, nombre o ID.'));
                return;
            }
            $('#ps-link-results').html(rows.map(function (c) {
                return '<label class="psc-radio-opt" data-psc-link-opt>' +
                    '<input type="radio" name="psLinkPick" value="' + escAttr(c.id) + '">' +
                    '<span class="psc-avatar psc-avatar--sm">' + esc(psInitials(c.name)) + '</span>' +
                    '<span class="psc-radio-body">' +
                        '<span class="t">' + esc(c.name || '—') + '</span>' +
                        '<span class="s">PS-' + esc(c.id) + (c.email ? ' · ' + esc(c.email) : '') + (c.city ? ' · ' + esc(c.city) : '') + '</span>' +
                    '</span>' +
                '</label>';
            }).join(''));
        }).fail(function (xhr) {
            $('#ps-link-results').html(psWarnHtml(window.HDCommerce.errorMessage(xhr, 'No se pudo buscar en PrestaShop.'), false));
        }).always(function () { $btn.prop('disabled', false); });
    });

    $(document).on('keydown', '#ps-link-query', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); $('#ps-link-search').trigger('click'); }
    });

    $(document).on('change', 'input[name="psLinkPick"]', function () {
        $('[data-psc-link-opt]').removeClass('is-on');
        $(this).closest('[data-psc-link-opt]').addClass('is-on');
        $('#ps-link-submit').prop('disabled', false).removeClass('is-disabled');
    });

    $(document).on('click', '#ps-link-submit', function () {
        var url = $store().data('ps-link-url');
        var id = $('input[name="psLinkPick"]:checked').val();
        if (!url || !id) { return; }
        var $btn = $(this).prop('disabled', true).text('Vinculando…');
        $.ajax({
            url: url, method: 'POST', dataType: 'json',
            data: { platform: 'prestashop', external_id: id },
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
        }).done(function () {
            if (window.toastr) { toastr.success('Cliente vinculado.'); }
            // El endpoint lee el external_id del cliente en cada petición:
            // basta con recargar el contexto saltando la caché.
            refreshStoreTab(true);
        }).fail(function (xhr) {
            if (window.toastr) { toastr.warning(window.HDCommerce.errorMessage(xhr, 'No se pudo vincular el cliente.')); }
        }).always(function () { $btn.prop('disabled', false).text('Vincular cliente'); });
    });

    /* ── Cliente + métricas ───────────────────────────────────── */

    function psVouchersAvailable() {
        return ((psCtx && psCtx.vouchers) || []).filter(function (v) { return psVoucherStateInfo(v).available; });
    }

    function psCustomerPhone() {
        var fromHelpdesk = window.HDCommerce ? window.HDCommerce.customer().phone : '';
        if (fromHelpdesk) { return fromHelpdesk; }
        var addr = ((psCtx && psCtx.addresses) || []).filter(function (a) { return a.phone || a.phone_mobile; })[0];
        return addr ? (addr.phone || addr.phone_mobile) : '';
    }

    function renderPsCustomerCard(customer) {
        var fullName = [customer.firstname, customer.lastname].filter(Boolean).join(' ').trim() || (customer.email || '—');
        var since = psParse(customer.registered_at);
        var metaParts = [customer.company, since ? ('desde ' + since.getFullYear()) : null, customer.group && customer.group.name].filter(Boolean);

        var available = psVouchersAvailable();
        var vouchersAmount = available.reduce(function (sum, v) { return sum + (parseFloat(v.reduction_amount) || 0); }, 0);
        var vouchersLabel = vouchersAmount > 0 ? psMoney(vouchersAmount) : (available.length ? String(available.length) : '0');
        var phone = psCustomerPhone();
        var adminUrl = $store().data('ps-admin-url');

        $('#ps-customer-body').html(
            '<div class="psc-cust-id">' +
                '<span class="psc-avatar">' + esc(psInitials(fullName)) + '</span>' +
                '<span class="psc-cust-id-body">' +
                    '<span class="nm">' + esc(fullName) + '</span>' +
                    (metaParts.length ? '<span class="s">' + esc(metaParts.join(' · ')) + '</span>' : '') +
                '</span>' +
                '<span class="psc-tag psc-tag--progress">PS</span>' +
            '</div>' +
            '<div class="psc-row"><span class="k">Email</span><span class="v mono">' + esc(customer.email || '—') + '</span></div>' +
            (phone ? '<div class="psc-row"><span class="k">Teléfono</span><span class="v mono">' + esc(phone) + '</span></div>' : '') +
            '<div class="psc-stats">' +
                '<div class="psc-stat"><span class="n">' + (customer.orders_count != null ? customer.orders_count : ((psCtx.orders || []).length)) + '</span><span class="l">Pedidos</span></div>' +
                '<div class="psc-stat"><span class="n is-good">' + (customer.ltv != null ? psMoney(customer.ltv) : '—') + '</span><span class="l">LTV</span></div>' +
                '<div class="psc-stat"><span class="n">' + esc(vouchersLabel) + '</span><span class="l">' + (vouchersAmount > 0 ? 'Vales' : 'Cupones') + '</span></div>' +
            '</div>' +
            '<button type="button" class="psc-btn psc-btn--outline" data-psc-open-customer="dashboard">Abrir ficha del cliente</button>' +
            (adminUrl && customer.id
                ? '<a class="psc-btn psc-btn--outline" target="_blank" rel="noopener" href="' + escAttr(adminUrl + '/index.php?controller=AdminCustomers&viewcustomer&id_customer=' + customer.id) + '">Abrir en el back-office</a>'
                : '')
        );
    }

    /* ── Alertas del cliente (pieza 14): máx. 3, cada una con acción ── */

    function renderPsAlerts() {
        var alerts = [];

        (psCtx.orders || []).forEach(function (o) {
            if (psOrderKind(o) !== 'pending') { return; }
            var days = psDaysAgo(o.placed_at);
            alerts.push({
                text: 'Pedido <b>#' + esc(o.reference || o.id) + '</b> sin pagar' + (days ? ' hace ' + days + (days === 1 ? ' día' : ' días') : ''),
                act: 'Ver', attr: 'data-ps-order-open data-order-id="' + escAttr(o.id) + '"',
            });
        });

        (psCtx.returns || []).forEach(function (r) {
            if (psReturnKind(r.state_name) !== 'open') { return; }
            var days = psDaysAgo(r.created_at);
            alerts.push({
                text: 'RMA-' + esc(r.id) + ' ' + esc(String(r.state_name || '').toLowerCase()) + (days ? ' hace ' + days + (days === 1 ? ' día' : ' días') : ''),
                act: 'Ver', attr: 'data-psc-go-tab="ps-returns"',
            });
        });

        var cart = activeCart();
        if (cart && (cart.products_count || (cart.items || []).length)) {
            alerts.push({
                text: 'Carrito activo de <b>' + psMoney(cart.totals && cart.totals.total) + '</b>',
                act: 'Abrir', attr: 'data-psc-open-cart',
            });
        }

        var $wrap = $('#ps-alerts-wrap');
        if (!alerts.length) { $wrap.empty(); return; }

        $wrap.html('<div class="psc-alerts">' + alerts.slice(0, ALERT_MAX).map(function (a) {
            return '<div class="psc-alert"><span class="dot"></span>' +
                '<span class="txt">' + a.text + '</span>' +
                '<button type="button" class="act" ' + a.attr + '>' + a.act + '</button></div>';
        }).join('') + '</div>');
    }

    $(document).on('click', '[data-psc-go-tab]', function () {
        $('.bv-right-tab[data-bv-tab="' + $(this).data('psc-go-tab') + '"]').trigger('click');
    });

    $(document).on('click', '[data-psc-open-cart]', function () { window.openPsCartModal(); });

    /* ── Direcciones por defecto: envío en verde, facturación neutra ── */

    // Envío/facturación "por defecto" = las del carrito en vivo (el bridge las
    // devuelve con su id). Sin carrito, las dos más recientes sin tipo.
    function psDefaultAddressIds() {
        var cart = activeCart();
        return {
            shipping: cart && cart.delivery_address ? cart.delivery_address.id : null,
            billing: cart && cart.invoice_address ? cart.invoice_address.id : null,
        };
    }

    function psAddressCardHtml(a, kind) {
        var fullName = [a.firstname, a.lastname].filter(Boolean).join(' ');
        var label = kind === 'shipping' ? 'Envío · ' : (kind === 'billing' ? 'Facturación · ' : '');
        var canChange = kind && activeCart();
        return '<div class="psc-addr' + (kind === 'shipping' ? ' psc-addr--shipping' : '') + '">' +
            '<div class="psc-addr-hd">' +
                '<span class="psc-addr-kind">' + esc(label + (a.alias || 'Dirección')) + '</span>' +
                (canChange ? '<button type="button" data-psc-addr-change="' + (kind === 'billing' ? 'invoice' : 'delivery') + '">Cambiar</button>' : '') +
            '</div>' +
            '<div class="psc-addr-line">' +
                esc(fullName) + (a.company ? ' · ' + esc(a.company) : '') + '<br>' +
                esc(a.address1 || a.address || '') + (a.address2 ? ', ' + esc(a.address2) : '') + ' · ' +
                esc([a.postcode, a.city].filter(Boolean).join(' ')) + (a.state ? ', ' + esc(a.state) : '') +
            '</div>' +
            (a.phone || a.phone_mobile ? '<div class="psc-addr-tax">' + esc(a.phone || a.phone_mobile) + '</div>' : '') +
        '</div>';
    }

    function renderPsAddressesInline(addresses) {
        var $summary = $('#ps-addr-summary');
        var $body = $('#ps-addr-defaults');
        if (!addresses.length) {
            $summary.text('Sin direcciones');
            $body.html(psEmptyHtml('fa-location-dot', 'Sin direcciones', 'Este cliente no tiene direcciones guardadas'));
            return;
        }

        var ids = psDefaultAddressIds();
        var byId = function (id) { return addresses.filter(function (a) { return String(a.id) === String(id); })[0]; };
        var shipping = ids.shipping ? byId(ids.shipping) : null;
        var billing = ids.billing ? byId(ids.billing) : null;
        var cards = [];

        if (shipping) { cards.push(psAddressCardHtml(shipping, 'shipping')); }
        if (billing && (!shipping || billing.id !== shipping.id)) { cards.push(psAddressCardHtml(billing, 'billing')); }
        if (!cards.length) {
            addresses.slice(0, 2).forEach(function (a) { cards.push(psAddressCardHtml(a, null)); });
        }

        var aliases = [shipping, billing].filter(Boolean).map(function (a) { return a.alias; });
        $summary.text(aliases.length ? aliases.join(' · ') : (addresses.length + (addresses.length === 1 ? ' dirección' : ' direcciones')));

        if (addresses.length > cards.length) {
            cards.push('<button type="button" class="psc-btn psc-btn--outline" data-psc-open-customer="addresses">Ver las ' + addresses.length + ' direcciones</button>');
        }
        $body.html(cards.join(''));
    }

    $(document).on('click', '[data-psc-addr-change]', function () {
        window.openPsAddressesModal('cart-picker', $(this).data('psc-addr-change'));
    });

    /* ── Cupones ──────────────────────────────────────────────── */

    function psVoucherCardHtml(v, opts) {
        var st = psVoucherStateInfo(v);
        var dateTo = psParse(v.date_to);
        var validity = st.txt === 'Caducado' && dateTo
            ? 'Caducó el ' + psFormatDate(v.date_to, true)
            : (dateTo ? 'hasta ' + psFormatDate(v.date_to, true) : 'sin fecha de caducidad');
        var hasCart = !!activeCart();
        var showActs = opts && opts.showApply && st.available && v.code;

        var minParts = [];
        var minAmount = parseFloat(v.minimum_amount);
        if (minAmount > 0) { minParts.push('mínimo ' + psMoney(minAmount)); }
        if (v.quantity_per_user != null && parseInt(v.quantity_per_user, 10) > 0) {
            var q = parseInt(v.quantity_per_user, 10);
            minParts.push(q + ' uso' + (q === 1 ? '' : 's') + ' por cliente');
        }

        return '<div class="psc-vch' + (st.available ? ' psc-vch--available' : ' psc-vch--spent') + '" data-voucher-id="' + escAttr(v.id) + '">' +
            '<div class="psc-vch-hd">' +
                '<span class="psc-vch-code">' + esc(v.code || 'Sin código') + '</span>' +
                (v.code ? '<button type="button" class="ps-copy-btn" data-psc-copy="' + escAttr(v.code) + '" title="Copiar código" aria-label="Copiar código"><i class="fas fa-copy"></i></button>' : '') +
                '<span class="psc-tag ' + st.cls + '">' + esc(st.txt) + '</span>' +
            '</div>' +
            '<div class="psc-vch-value"><b>' + esc(psVoucherValueLabel(v)) + '</b><span>' + esc(validity) + '</span></div>' +
            (v.description ? '<div class="psc-vch-desc">' + esc(v.description) + '</div>' : '') +
            (showActs
                ? '<div class="psc-vch-acts">' +
                    '<button type="button" data-psc-apply-voucher-suggest="' + escAttr(v.code) + '"' +
                        (hasCart ? '' : ' disabled title="El cliente no tiene carrito activo"') + '>Aplicar al carrito</button>' +
                    '<button type="button" class="is-secondary" data-psc-send-voucher-chat="' + escAttr(v.code) + '">Enviar al chat</button>' +
                  '</div>'
                : '') +
            (minParts.length ? '<div class="psc-vch-min">' + esc(minParts.join(' · ')) + '</div>' : '') +
        '</div>';
    }

    function psVoucherCreateBtn() {
        var limit = parseFloat($('[data-bv-modal-name="ps-voucher-create"]').data('limit')) || 0;
        return limit > 0 ? '<button type="button" class="psc-btn psc-btn--outline" data-psc-voucher-create>Crear vale de compensación</button>' : '';
    }

    function renderPsVouchersInline(vouchers) {
        var available = vouchers.filter(function (v) { return psVoucherStateInfo(v).available; });
        $('#ps-vch-summary').text(available.length ? (available.length + (available.length === 1 ? ' disponible' : ' disponibles')) : 'Sin cupones');
        if (!vouchers.length) {
            $('#ps-vch-inline').html(psEmptyHtml('fa-tag', 'Sin cupones', 'Este cliente no tiene cupones en PrestaShop') + psVoucherCreateBtn());
            return;
        }
        var shown = (available.length ? available : vouchers).slice(0, 3);
        var html = shown.map(function (v) { return psVoucherCardHtml(v, { showApply: true }); }).join('');
        if (vouchers.length > shown.length) {
            html += '<button type="button" class="psc-btn psc-btn--outline" data-psc-go-tab="ps-vouchers">Ver los ' + vouchers.length + ' cupones</button>';
        }
        $('#ps-vch-inline').html(html + psVoucherCreateBtn());
    }

    function renderPsVouchersTab(vouchers) {
        var $tab = $('#bv-ps-vouchers');
        if (vouchers === null) {
            $tab.html('<div class="psc-card-body">' + psWarnHtml('No se han podido cargar los cupones del cliente.', true) + '</div>');
            return;
        }
        if (!vouchers.length) {
            $tab.html(psEmptyHtml('fa-tag', 'Sin cupones', 'Este cliente no tiene cupones en PrestaShop') +
                '<div class="psc-card-body">' + psVoucherCreateBtn() + '</div>');
            return;
        }
        var available = vouchers.filter(function (v) { return psVoucherStateInfo(v).available; });
        var spent = vouchers.filter(function (v) { return !psVoucherStateInfo(v).available; });
        var html = '<div class="psc-card-body">';
        if (available.length) {
            html += '<div class="ps-sec-label"><span>Disponibles</span><span class="ct">' + available.length + '</span><span class="ln"></span></div>' +
                available.map(function (v) { return psVoucherCardHtml(v, { showApply: true }); }).join('');
        }
        if (spent.length) {
            html += '<div class="ps-sec-label"><span>Usados y caducados</span><span class="ct">' + spent.length + '</span><span class="ln"></span></div>' +
                spent.map(function (v) { return psVoucherCardHtml(v); }).join('');
        }
        $tab.html(html + psVoucherCreateBtn() + '</div>');
    }

    $(document).on('click', '[data-psc-send-voucher-chat]', function () {
        var code = $(this).data('psc-send-voucher-chat');
        var v = ((psCtx && psCtx.vouchers) || []).filter(function (x) { return x.code === code; })[0] || {};
        var value = psVoucherValueLabel(v);
        var until = v.date_to ? ' Válido hasta el ' + psFormatDate(v.date_to, true) + '.' : '';
        psInsertComposer('Te dejo el código ' + code + (value !== '—' ? ' (' + value + ')' : '') + ' para aplicarlo en tu pedido.' + until);
    });

    $(document).on('click', '[data-psc-copy]', function () {
        var text = $(this).data('psc-copy');
        if (!text || !navigator.clipboard) { return; }
        navigator.clipboard.writeText(String(text));
        if (window.toastr) { toastr.info('Copiado: ' + text); }
    });

    /* ── Devoluciones (pieza 1) ───────────────────────────────── */

    var PS_RETURN_TAG = { open: 'psc-tag--pending', done: 'psc-tag--progress', blocked: 'psc-tag--blocked' };

    function psReturnCardHtml(ret) {
        var kind = psReturnKind(ret.state_name);
        var items = ret.items || [];
        var orderRef = ret.order_reference || ret.order_id || '—';
        var rmaId = ret.id != null ? ('RMA-' + ret.id) : '';

        return '<div class="psc-rma' + (kind === 'blocked' ? ' psc-rma--blocked' : '') + '" data-rma-id="' + escAttr(ret.id) + '" data-order-id="' + escAttr(ret.order_id) + '">' +
            '<div class="psc-rma-hd">' +
                '<span class="psc-rma-ref">' + esc(rmaId) + '</span>' +
                '<span class="psc-tag ' + PS_RETURN_TAG[kind] + '">' + esc(ret.state_name || 'Pendiente') + '</span>' +
            '</div>' +
            '<div class="psc-rma-meta">' + esc(psFormatDate(ret.created_at, true)) + ' · pedido <span class="psc-link-ref">#' + esc(orderRef) + '</span></div>' +
            items.map(function (it) {
                return '<div class="psc-rma-line"><span class="th"></span>' +
                    '<span class="nm">' + esc(it.product_name || it.name || 'Producto') + '</span>' +
                    '<span class="qty">×' + (parseInt(it.quantity, 10) || 1) + '</span></div>';
            }).join('') +
            (ret.reason ? '<div class="psc-rma-reason"><span class="lbl">Motivo · </span>' + esc(ret.reason) + '</div>' : '') +
            (kind !== 'blocked'
                ? '<div class="psc-rma-acts">' +
                    (ret.order_id ? '<button type="button" data-ps-order-open data-order-id="' + escAttr(ret.order_id) + '">Ver pedido</button>' : '') +
                    (rmaId ? '<button type="button" data-psc-copy="' + escAttr(rmaId) + '">Copiar nº RMA</button>' : '') +
                  '</div>'
                : '') +
        '</div>';
    }

    function renderPsReturnsTab(returns) {
        var $tab = $('#bv-ps-returns');
        if (returns === null) {
            $tab.html('<div class="psc-card-body">' + psWarnHtml('No se ha podido consultar PrestaShop. Inténtalo de nuevo.', true) + '</div>');
            return;
        }
        if (!returns.length) {
            $tab.html(psEmptyHtml('fa-rotate-left', 'Sin devoluciones', 'No hay devoluciones registradas en PrestaShop'));
            return;
        }
        $tab.html('<div class="psc-card-body">' +
            '<div class="ps-sec-label"><span>Devoluciones</span><span class="ct">' + returns.length + '</span><span class="ln"></span></div>' +
            returns.map(psReturnCardHtml).join('') +
        '</div>');
        $(document).trigger('psc:returns-rendered', [returns]);
    }

    function psLoadingHtml(text) {
        return '<div class="psc-card-body"><div class="psc-skel"></div><div class="psc-skel"></div>' +
            '<div class="psc-loading">' + esc(text) + '</div></div>';
    }

    // Tabs Devoluciones/Cupones: comparten la llamada del tab Tienda; si ya
    // está cargada, se pintan al instante.
    function loadReturnsTab() {
        $('#bv-ps-returns').html(psLoadingHtml('Cargando devoluciones…'));
        fetchCtx(false, function (ctx) {
            renderPsReturnsTab(ctx.bridge === 'down' && !ctx.returns ? null : (ctx.returns || []));
        });
    }

    function loadVouchersTab() {
        $('#bv-ps-vouchers').html(psLoadingHtml('Cargando cupones…'));
        fetchCtx(false, function (ctx) {
            renderPsVouchersTab(ctx.bridge === 'down' && !ctx.vouchers ? null : (ctx.vouchers || []));
        });
    }

    document.addEventListener('click', function (e) {
        if ($(e.target).closest('.bv-right-tab[data-bv-tab="ps-returns"]').length) { loadReturnsTab(); }
        if ($(e.target).closest('.bv-right-tab[data-bv-tab="ps-vouchers"]').length) { loadVouchersTab(); }
    }, true);

    /* ── Carrito en vivo ──────────────────────────────────────── */

    function renderPsCartLive() {
        var cart = activeCart();
        var count = cart ? (cart.products_count || (cart.items || []).length) : 0;

        if (!cart || !count) {
            $('#ps-cart-live-wrap').html(
                '<div class="psc-live-hint psc-live-hint--muted">' +
                    '<span class="dot"></span>' +
                    '<span class="txt">Sin carrito activo en este momento</span>' +
                '</div>'
            );
            return;
        }

        var units = (cart.items || []).reduce(function (sum, it) { return sum + (parseInt(it.quantity, 10) || 0); }, 0);
        var voucherCount = (cart.vouchers || []).length;
        var subParts = [count + ' artículo' + (count === 1 ? '' : 's')];
        if (units) { subParts.push(units + ' unidad' + (units === 1 ? '' : 'es')); }
        if (voucherCount) { subParts.push(voucherCount + ' cupón' + (voucherCount === 1 ? '' : 'es') + ' aplicado' + (voucherCount === 1 ? '' : 's')); }

        // Barra: importe del carrito frente al ticket medio real del cliente
        // (LTV / nº de pedidos). Sin pedidos no hay media y no se pinta.
        var c = psCtx.customer || {};
        var avg = (c.ltv && c.orders_count) ? c.ltv / c.orders_count : 0;
        var total = parseFloat(cart.totals && cart.totals.total) || 0;
        var pct = avg > 0 ? Math.round((total / avg) * 100) : null;

        $('#ps-cart-live-wrap').html(
            '<div class="psc-live">' +
                '<div class="psc-live-hd">' +
                    '<span class="t">En vivo</span>' +
                    '<span class="id">CART-#' + esc(cart.id || '') + '</span>' +
                    '<span class="dot"></span>' +
                '</div>' +
                '<div class="psc-live-body">' +
                    '<span class="psc-live-amount">' + psMoney(total) + '<span class="psc-live-when">' + esc(psRelativeTime(cart.updated_at)) + '</span></span>' +
                    '<span class="psc-live-sub">' + esc(subParts.join(' · ')) + '</span>' +
                    (pct !== null
                        ? '<span class="psc-live-bar"><span class="psc-w-' + Math.min(100, Math.round(pct / 5) * 5) + '"></span></span>' +
                          '<span class="psc-live-cap">' + pct + ' % del valor medio del cliente</span>'
                        : '') +
                '</div>' +
                '<div class="psc-live-acts psc-live-acts--2">' +
                    '<button type="button" class="is-primary" data-psc-open-cart>Ver</button>' +
                    '<button type="button" data-psc-cart-summary>Enviar resumen al chat</button>' +
                '</div>' +
            '</div>'
        );
    }

    function psCartSummaryText(cart) {
        var lines = (cart.items || []).map(function (it) {
            return '• ' + it.name + ' ×' + (parseInt(it.quantity, 10) || 1) + (it.total_wt ? ' — ' + psMoney(it.total_wt) : '');
        });
        return 'Este es el contenido de tu carrito:\n' + lines.join('\n') + '\nTotal: ' + psMoney(cart.totals && cart.totals.total);
    }

    $(document).on('click', '[data-psc-cart-summary]', function () {
        var cart = activeCart();
        if (cart) { psInsertComposer(psCartSummaryText(cart)); }
        var m = bootstrap.Modal.getInstance(document.getElementById('psCartModal'));
        if (m) { m.hide(); }
    });

    /* ── Pedidos y carritos ───────────────────────────────────── */

    function psOrdRowHtml(o) {
        var kind = psOrderKind(o);
        return '<div class="psc-ord-row is-' + kind + '" data-ps-order-open data-order-id="' + escAttr(o.id || '') + '">' +
            '<div class="info">' +
                '<span class="psc-ord-top"><span class="ref">#' + esc(o.reference || o.id) + '</span>' +
                '<span class="psc-tag ' + PS_KIND_TAG[kind] + '">' + esc(psOrderStateName(o)) + '</span></span>' +
                (psOrderFirstLine(o) ? '<span class="t">' + esc(psOrderFirstLine(o)) + '</span>' : '') +
                '<span class="m">' + esc(psFormatDate(o.placed_at, true)) + (o.payment_method ? ' · ' + esc(o.payment_method) : '') + '</span>' +
            '</div>' +
            '<span class="amt">' + psMoney(psOrderTotal(o)) + '</span>' +
        '</div>';
    }

    function renderPsOrdersCard(orders) {
        var total = (psCtx.customer && psCtx.customer.orders_count) || orders.length;
        $('#ps-orders-count').text(total);

        if (!orders.length) {
            $('#ps-orders-sub').text('');
            $('#ps-orders-body').html(psEmptyHtml('fa-box', 'Sin pedidos', 'Este cliente no ha comprado todavía'));
            return;
        }

        var pending = orders.filter(function (o) { return psOrderKind(o) === 'pending'; }).length;
        var subParts = [];
        if (pending) { subParts.push(pending + ' pendiente' + (pending === 1 ? '' : 's') + ' de pago'); }
        subParts.push('último ' + psFormatDate(orders[0].placed_at, false));
        $('#ps-orders-sub').text(subParts.join(' · '));

        var html = orders.slice(0, 3).map(psOrdRowHtml).join('');
        html += '<button type="button" class="psc-btn psc-btn--outline" data-psc-open-orders>Ver ' +
            (total > 3 ? 'los ' + total + ' pedidos' : 'pedidos del cliente') + '</button>';
        $('#ps-orders-body').html(html);
    }

    // Abrir el workspace de un pedido desde cualquier fila/acción.
    $(document).on('click', '.rp3-order[data-ps-order-open], .psc-ord-row[data-ps-order-open], [data-ps-order-open]', function (e) {
        var id = $(this).data('order-id');
        if (!id) { return; }
        e.stopPropagation();
        var lm = bootstrap.Modal.getInstance(document.getElementById('psOrdersModal'));
        if (lm) { lm.hide(); }
        if (window.HDCommerce) { window.HDCommerce.close('ps-customer-workspace'); }
        if (typeof window.openPsOrderWorkspace === 'function') { window.openPsOrderWorkspace(id); }
    });

    /* ── Reembolsos (pieza 7, solo lectura) ───────────────────── */

    function psRefundAmount(x) {
        return parseFloat(x.amount != null ? x.amount : (x.total || 0)) || 0;
    }

    function renderPsRefundsCard(refunds, explicit) {
        var $wrap = $('#ps-refunds-wrap');
        if (refunds === null) {
            $wrap.html('<div class="psc-card"><div class="psc-card-body">' + psWarnHtml('Reembolsos no disponibles ahora mismo.', false) + '</div></div>');
            return;
        }
        if (!refunds.length) {
            if (!explicit) { $wrap.empty(); return; }
            $wrap.html('<div class="psc-card"><div class="psc-card-body">' + psEmptyHtml(null, 'Sin reembolsos', 'No hay importes devueltos a este cliente') + '</div></div>');
            return;
        }

        var total = refunds.reduce(function (sum, x) { return sum + psRefundAmount(x); }, 0);
        $wrap.html(
            '<div class="psc-card">' +
                '<div class="psc-card-head">' +
                    '<span class="psc-card-head-tt"><span>Reembolsos</span><span class="s">Dinero ya devuelto al cliente</span></span>' +
                    '<span class="psc-count">' + refunds.length + '</span>' +
                '</div>' +
                '<div class="psc-card-body">' +
                    refunds.map(function (x) {
                        var ref = x.order_reference || x.order_id || '—';
                        var slip = x.number || x.id_order_slip || x.id;
                        return '<div class="psc-hist-row psc-hist-row--static">' +
                            '<div class="info">' +
                                '<span class="t">' + esc(psFormatDate(x.date_add || x.date || x.created_at, true)) + '</span>' +
                                '<span class="m">Pedido <span class="psc-link-ref">#' + esc(ref) + '</span>' + (slip ? ' · albarán ' + esc(slip) : '') + '</span>' +
                            '</div>' +
                            '<span class="amt">' + psMoney(psRefundAmount(x)) + '</span>' +
                        '</div>';
                    }).join('') +
                    '<div class="psc-row psc-row--total"><span class="k">Total reembolsado</span><span class="v">' + psMoney(total) + '</span></div>' +
                '</div>' +
            '</div>'
        );
    }

    /* ── Últimos movimientos: todo lo fechado que trae el contexto ── */

    function psActivityEvents() {
        var events = [];
        var cart = activeCart();
        if (cart && cart.updated_at) {
            events.push({ icon: 'fa-cart-shopping', good: true, text: 'Actualizó su carrito · CART-#' + cart.id, at: cart.updated_at });
        }
        (psCtx.orders || []).forEach(function (o) {
            events.push({ icon: 'fa-box', good: false, text: 'Pedido #' + (o.reference || o.id) + ' · ' + psOrderStateName(o) + ' · ' + psMoney(psOrderTotal(o)), at: o.placed_at });
        });
        (psCtx.returns || []).forEach(function (r) {
            events.push({ icon: 'fa-rotate-left', good: false, text: 'RMA-' + r.id + ' ' + String(r.state_name || '').toLowerCase() + ' · pedido #' + (r.order_reference || r.order_id), at: r.created_at });
        });
        (psCtx.refunds || []).forEach(function (x) {
            events.push({ icon: 'fa-sack-dollar', good: true, text: 'Reembolso de ' + psMoney(psRefundAmount(x)) + ' emitido', at: x.date_add || x.date || x.created_at });
        });
        (psCtx.messages || []).forEach(function (m) {
            events.push({ icon: 'fa-comment', good: false, text: 'Mensaje en tienda · ' + (m.subject || 'Mensaje general'), at: m.created_at });
        });
        return events.filter(function (e) { return psParse(e.at); }).sort(function (a, b) {
            return psParse(b.at) - psParse(a.at);
        });
    }

    function renderPsActivity() {
        var $wrap = $('#ps-activity-body');
        var events = psActivityEvents();
        var monthAgo = Date.now() - 30 * 86400000;
        var recent = events.filter(function (e) { return psParse(e.at).getTime() >= monthAgo; }).length;
        $('#ps-activity-meta').text(recent ? recent + ' · 30 d' : '');

        if (!events.length) {
            $wrap.html(psEmptyHtml('fa-clock-rotate-left', 'Sin movimientos recientes', null));
            return;
        }
        $wrap.html(events.slice(0, 4).map(function (e) {
            return '<div class="psc-act-row">' +
                '<span class="ic' + (e.good ? ' is-good' : '') + '"><i class="fas ' + e.icon + '"></i></span>' +
                '<span><span class="t">' + esc(e.text) + '</span>' +
                '<span class="m">' + esc(psRelativeTime(e.at) + ' · ' + psFormatDate(e.at, true)) + '</span></span>' +
            '</div>';
        }).join(''));
    }

    /* ── Respuestas con datos reales (pieza 17) ───────────────── */

    function psQuickReplies() {
        // Punto de extensión: plantillas configurables en Ajustes (js/ext).
        // Si el proveedor devuelve una lista, sustituye a las de serie.
        if (typeof window.PscQuickRepliesProvider === 'function') {
            try {
                var custom = window.PscQuickRepliesProvider(psCtx || {});
                if (Array.isArray(custom)) { return custom; }
            } catch (e) { /* plantilla rota: se usan las de serie */ }
        }
        var orders = (psCtx && psCtx.orders) || [];
        var replies = [];
        var last = orders[0];

        if (last) {
            var tr = (last.tracking || [])[0];
            replies.push({
                t: 'Estado del pedido',
                s: '“Tu pedido #' + (last.reference || last.id) + ' está ' + psOrderStateName(last).toLowerCase() + '…”',
                text: 'Tu pedido #' + (last.reference || last.id) + ' está en estado «' + psOrderStateName(last) + '».' +
                    (tr && tr.tracking_number ? ' Lo lleva ' + (tr.carrier_name || 'el transportista') + ' con el número ' + tr.tracking_number + (tr.tracking_url ? ': ' + tr.tracking_url : '.') : ''),
            });
        }

        var openRma = ((psCtx && psCtx.returns) || []).filter(function (r) { return psReturnKind(r.state_name) === 'open'; })[0];
        if (openRma) {
            replies.push({
                t: 'Instrucciones de devolución',
                s: 'Incluye RMA-' + openRma.id + ' y el pedido',
                text: 'Hemos registrado tu devolución RMA-' + openRma.id + ' del pedido #' + (openRma.order_reference || openRma.order_id) +
                    '. Prepara el paquete con los artículos y el número de devolución visible; te avisaremos en cuanto lo recibamos.',
            });
        }

        var refund = ((psCtx && psCtx.refunds) || [])[0];
        if (refund) {
            replies.push({
                t: 'Confirmación de reembolso',
                s: 'Importe y plazo del banco',
                text: 'Te confirmamos el reembolso de ' + psMoney(psRefundAmount(refund)) + ' del pedido #' + (refund.order_reference || refund.order_id) +
                    '. Según tu banco puede tardar de 3 a 5 días hábiles en verse en tu cuenta.',
            });
        }
        return replies;
    }

    function renderPsQuickReplies() {
        var replies = psQuickReplies();
        var $wrap = $('#ps-replies-wrap');
        if (!replies.length) { $wrap.empty(); return; }
        $wrap.html(
            '<div class="psc-card">' +
                '<button type="button" class="psc-card-head psc-card-head--toggle" data-psc-toggle="replies" aria-expanded="false">' +
                    'Respuestas con datos reales<span class="psc-meta">' + replies.length + '</span><i class="fas fa-chevron-down psc-chevron"></i>' +
                '</button>' +
                '<div class="psc-card-body psc-collapse" data-psc-collapse="replies" hidden>' +
                    replies.map(function (r, i) {
                        return '<button type="button" class="psc-tpl" data-psc-reply="' + i + '">' +
                            '<span class="psc-tpl-body"><span class="t">' + esc(r.t) + '</span><span class="s">' + esc(r.s) + '</span></span>' +
                            '<span class="act">Insertar</span></button>';
                    }).join('') +
                '</div>' +
            '</div>'
        );
    }

    $(document).on('click', '[data-psc-reply]', function () {
        var r = psQuickReplies()[parseInt($(this).data('psc-reply'), 10)];
        if (r) { psInsertComposer(r.text); }
    });

    /* ── Plegables con memoria (localStorage) ─────────────────── */

    function readCollapseState() {
        try { return JSON.parse(window.localStorage.getItem(COLLAPSE_KEY) || '{}') || {}; } catch (e) { return {}; }
    }

    function writeCollapseState(state) {
        try { window.localStorage.setItem(COLLAPSE_KEY, JSON.stringify(state)); } catch (e) { /* sin persistencia */ }
    }

    function setCollapsed(key, open) {
        $('[data-psc-toggle="' + key + '"]').attr('aria-expanded', open ? 'true' : 'false');
        $('[data-psc-collapse="' + key + '"]').prop('hidden', !open);
    }

    function applyCollapseState() {
        var state = readCollapseState();
        Object.keys(state).forEach(function (key) { setCollapsed(key, !!state[key]); });
    }

    $(document).on('click', '[data-psc-toggle]', function () {
        var key = $(this).data('psc-toggle');
        var open = $(this).attr('aria-expanded') !== 'true';
        setCollapsed(key, open);
        var state = readCollapseState();
        state[key] = open;
        writeCollapseState(state);
    });

    /* ── Carrito en tiempo real (evento ps.cart.updated) ──────── */

    var psLiveSubscribedConvId = null;

    function subscribePsCartLive() {
        if (!window.Echo) { return; }
        var convId = window.HDCommerce ? window.HDCommerce.conversationId() : null;
        if (!convId || convId === psLiveSubscribedConvId) { return; }
        psLiveSubscribedConvId = convId;

        window.Echo.private('helpdesk.conversation.' + convId)
            .listen('.ps.cart.updated', function (payload) {
                fetchCtx(true, function (ctx) {
                    if (!$store().hasClass('bv-tab-hidden')) { renderStore(ctx); }
                    if ($('#psCartModal').hasClass('show')) { renderPsCartModalFromCache(); }
                });
                $(document).trigger('psc:cart-updated', [payload || {}]);
            });
    }

    /* ── Modal de direcciones (selector para el carrito) ──────── */

    // mode 'cart-picker' + type 'delivery'|'invoice'|'both': cada tarjeta
    // lleva "Usar esta dirección" y cambia la del carrito abierto.
    window.openPsAddressesModal = function (mode, type) {
        psAddrPickerType = type || 'both';
        var cart = activeCart();
        psActiveCartId = cart ? cart.id : psActiveCartId;
        var $body = $('#psAddressesBody');
        var title = psAddrPickerType === 'invoice' ? 'Dirección de facturación' : 'Direcciones de envío';
        $('#psAddressesTitle').text(title);

        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('psAddressesModal'));
        modal.show();
        $body.html('<div class="psc-card-body"><div class="psc-skel"></div><div class="psc-loading">Cargando direcciones…</div></div>');

        fetchCtx(false, function (ctx) {
            var addresses = ctx.addresses || [];
            if (!addresses.length) {
                $body.html(psEmptyHtml('fa-location-dot', 'Sin direcciones guardadas', 'Crea una dirección desde el pedido'));
                return;
            }
            var ids = psDefaultAddressIds();
            var currentId = psAddrPickerType === 'invoice' ? ids.billing : ids.shipping;
            $body.html('<div class="psc-card-body">' + addresses.map(function (a) {
                var isCurrent = mode === 'cart-picker' && String(a.id) === String(currentId);
                return '<div class="ps-addr-card' + (isCurrent ? ' is-current' : '') + '">' +
                    '<div class="ps-addr-alias">' + esc(a.alias) + (isCurrent ? ' · en uso' : '') + '</div>' +
                    '<div class="ps-addr-name">' + esc([a.firstname, a.lastname].filter(Boolean).join(' ')) + (a.company ? ' · ' + esc(a.company) : '') + '</div>' +
                    '<div class="ps-addr-line">' + esc(a.address1 || a.address) + '</div>' +
                    '<div class="ps-addr-city">' + esc(a.postcode) + ' ' + esc(a.city) + (a.country ? ', ' + esc(a.country) : '') + '</div>' +
                    (a.phone ? '<div class="ps-addr-phone">' + esc(a.phone) + '</div>' : '') +
                    (mode === 'cart-picker' && !isCurrent
                        ? '<button type="button" class="ps-addr-use-btn" data-address-id="' + escAttr(a.id) + '">Usar esta dirección</button>'
                        : '') +
                '</div>';
            }).join('') + '</div>');
        });
    };

    $(document).on('click', '.ps-addr-use-btn', function () {
        var $btn = $(this);
        var addressId = $btn.data('address-id');
        if (!addressId || !psActiveCartId) { return; }

        $btn.prop('disabled', true).text('Aplicando…');
        psCartAjax('POST', '/address', { address_id: addressId, type: psAddrPickerType }, function (ok, r, errMsg) {
            if (ok) {
                var addrModal = bootstrap.Modal.getInstance(document.getElementById('psAddressesModal'));
                if (addrModal) { addrModal.hide(); }
                psCartRefreshAfterAction();
            } else {
                $btn.prop('disabled', false).text('Usar esta dirección');
                psCartShowError(errMsg || 'No se pudo cambiar la dirección.');
            }
        });
    });

    /* ── Modal de carrito (pieza 5): líneas, cupones y totales ── */

    function psCartItemRowHtml(item) {
        var qty = parseInt(item.quantity || 1, 10);
        var unitPrice = parseFloat(item.unit_price || 0);
        var lineTotal = item.total_wt != null ? parseFloat(item.total_wt) : unitPrice * qty;

        return '<div class="ps-cart-line" data-cart-product-id="' + escAttr(item.product_id) + '" data-cart-attribute-id="' + escAttr(item.attribute_id || 0) + '">' +
            '<span class="psc-thumb psc-thumb--md">' + (item.image_url ? '<img src="' + escAttr(item.image_url) + '" alt="" loading="lazy">' : 'foto') + '</span>' +
            '<span class="ps-cart-line-body">' +
                '<span class="nm">' + esc(item.name) + '</span>' +
                '<span class="s">' + esc([item.reference, item.attributes_small, psMoney(unitPrice) + ' / ud'].filter(Boolean).join(' · ')) + '</span>' +
            '</span>' +
            '<span class="ps-cart-qty-stepper">' +
                '<button type="button" class="ps-cart-qty-btn" data-cart-action="qty-dec" aria-label="Quitar una unidad">−</button>' +
                '<span class="ps-cart-qty-val">' + qty + '</span>' +
                '<button type="button" class="ps-cart-qty-btn" data-cart-action="qty-inc" aria-label="Añadir una unidad">+</button>' +
            '</span>' +
            '<span class="ps-cart-line-amt">' + psMoney(lineTotal) + '</span>' +
            '<button type="button" class="ps-cart-remove-btn" data-cart-action="remove" title="Quitar del carrito" aria-label="Quitar del carrito"><i class="fas fa-xmark"></i></button>' +
        '</div>';
    }

    function psCartModalHtml(cart) {
        if (!cart || !(cart.items || []).length) {
            return psEmptyHtml('fa-cart-shopping', 'Carrito vacío', 'Este cliente no tiene productos en el carrito');
        }

        var appliedCodes = (cart.vouchers || []).map(function (v) { return String(v.code || v.name || '').toUpperCase(); });
        // El bridge quita el cupón por código: las reglas automáticas del
        // carrito no traen código, así que no se ofrece quitarlas.
        var chips = (cart.vouchers || []).map(function (v) {
            return '<span class="ps-cart-voucher-chip">' + esc(v.code || v.name) +
                (v.code ? '<button type="button" class="rm" data-cart-action="remove-voucher" data-voucher-code="' + escAttr(v.code) + '" title="Quitar cupón" aria-label="Quitar cupón"><i class="fas fa-xmark"></i></button>' : '') +
            '</span>';
        }).join('');

        var t = cart.totals || {};
        var discounts = parseFloat(t.discounts || 0);
        var totals = '<div class="psc-totals">' +
            '<div class="psc-tot"><span>Subtotal</span><span>' + psMoney(t.products != null ? t.products : t.total) + '</span></div>' +
            (discounts > 0 ? '<div class="psc-tot is-discount"><span>Descuentos</span><span>−' + psMoney(discounts) + '</span></div>' : '') +
            '<div class="psc-tot"><span>Envío</span><span>' + psMoney(t.shipping || 0) + '</span></div>' +
            '<div class="psc-tot is-total"><span>Total</span><span>' + psMoney(t.total) + '</span></div>' +
        '</div>';

        var delivery = cart.delivery_address;
        var invoice = cart.invoice_address;
        var addrRow = function (label, a, type) {
            return '<div class="ps-cart-address-row">' +
                '<span class="ps-cart-address-lbl">' + label + '</span>' +
                (a ? '<span class="ps-cart-address-txt"><b>' + esc(a.name) + '</b> · ' + esc(a.line) + ' · ' + esc(a.city) + '</span>'
                   : '<span class="ps-cart-address-txt is-missing">Sin dirección</span>') +
                '<button type="button" data-cart-action="change-address" data-type="' + type + '">' + (a ? 'Cambiar' : 'Elegir') + '</button>' +
            '</div>';
        };

        return '<div class="ps-cart-body">' +
            '<div class="ps-cart-lines">' + cart.items.map(psCartItemRowHtml).join('') + '</div>' +
            '<div class="ps-sec-label"><span>Cupones</span><span class="ln"></span></div>' +
            (chips ? '<div class="ps-cart-vouchers-applied">' + chips + '</div>' : '') +
            '<div class="ps-cart-voucher-row">' +
                '<input type="text" class="form-control ps-cart-voucher-input" placeholder="Código de cupón o buscar en los del cliente…" autocomplete="off">' +
                '<button type="button" data-cart-action="apply-voucher">Aplicar</button>' +
            '</div>' +
            '<div id="psCartVoucherSuggestions" data-applied="' + escAttr(appliedCodes.join(',')) + '"></div>' +
            '<div class="ps-sec-label"><span>Direcciones</span><span class="ln"></span></div>' +
            addrRow('Envío', delivery, 'delivery') +
            (invoice && delivery && String(invoice.id) === String(delivery.id)
                ? '<span class="ps-cart-sugg-empty">Facturación: la misma dirección.</span>'
                : addrRow('Facturación', invoice, 'invoice')) +
            totals +
        '</div>';
    }

    // Sugerencias del cliente como lista compacta: los disponibles con "Usar",
    // los caducados deshabilitados para explicar por qué no sirven.
    function renderPsCartVoucherSuggestions(filter) {
        var $wrap = $('#psCartVoucherSuggestions');
        if (!$wrap.length) { return; }
        var applied = String($wrap.data('applied') || '').split(',').filter(Boolean);
        var term = String(filter || '').toUpperCase();
        var vouchers = ((psCtx && psCtx.vouchers) || []).filter(function (v) {
            var code = String(v.code || '').toUpperCase();
            return code && applied.indexOf(code) === -1 && (!term || code.indexOf(term) !== -1);
        });

        if (!(psCtx && psCtx.vouchers && psCtx.vouchers.length)) {
            $wrap.html('<span class="ps-cart-sugg-empty">El cliente no tiene cupones asignados: escribe cualquier código válido de la tienda.</span>');
            return;
        }
        if (!vouchers.length) { $wrap.empty(); return; }

        $wrap.html('<span class="ps-cart-sugg-lbl">Cupones del cliente</span>' + vouchers.map(function (v) {
            var st = psVoucherStateInfo(v);
            var sub = st.available
                ? [psVoucherValueLabel(v), v.date_to ? 'hasta ' + psFormatDate(v.date_to, true) : null, parseFloat(v.minimum_amount) > 0 ? 'mínimo ' + psMoney(v.minimum_amount) : null].filter(Boolean).join(' · ')
                : (st.txt === 'Caducado' && v.date_to ? 'Caducado el ' + psFormatDate(v.date_to, true) : st.txt);
            return '<div class="list-item ps-cart-sugg' + (st.available ? '' : ' is-off') + '">' +
                '<span class="body"><span class="t">' + esc(v.code) + '</span><span class="s">' + esc(sub) + '</span></span>' +
                (st.available ? '<button type="button" class="act" data-psc-apply-voucher-suggest="' + escAttr(v.code) + '">Usar</button>' : '') +
            '</div>';
        }).join(''));
    }

    $(document).on('input', '#psCartModalBody .ps-cart-voucher-input', function () {
        renderPsCartVoucherSuggestions($(this).val());
    });

    $(document).on('keydown', '#psCartModalBody .ps-cart-voucher-input', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); $('#psCartModalBody [data-cart-action="apply-voucher"]').trigger('click'); }
    });

    function psApplyVoucherCode(code) {
        $('#psCartModalBody .ps-cart-voucher-input').val(code);
        $('#psCartModalBody [data-cart-action="apply-voucher"]').trigger('click');
    }

    $(document).on('click', '[data-psc-apply-voucher-suggest]', function () {
        var code = $(this).data('psc-apply-voucher-suggest');
        if (!code || !activeCart()) { return; }
        if (!$('#psCartModal').hasClass('show')) {
            window.openPsCartModal();
            setTimeout(function () { psApplyVoucherCode(code); }, 300);
            return;
        }
        psApplyVoucherCode(code);
    });

    function renderPsCartModalFromCache() {
        var cart = activeCart();
        psActiveCartId = cart ? cart.id : null;
        $('#psCartModalTitle').text(cart ? ('Carrito #' + cart.id) : 'Carrito');
        if (cart) {
            var n = cart.products_count || (cart.items || []).length;
            var units = (cart.items || []).reduce(function (s, it) { return s + (parseInt(it.quantity, 10) || 0); }, 0);
            $('#psCartModalMeta').text(n + (n === 1 ? ' artículo' : ' artículos') + ' · ' + units + (units === 1 ? ' ud' : ' uds') +
                (cart.updated_at ? ' · actualizado ' + psRelativeTime(cart.updated_at) : ''));
        } else {
            $('#psCartModalMeta').text('');
        }
        $('#psCartModalBody').html(psCartModalHtml(cart));
        $('#psCartSummaryBtn').toggleClass('bv-hidden', !cart);
        if (cart) { renderPsCartVoucherSuggestions(''); }
    }

    window.openPsCartModal = function () {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('psCartModal')).show();
        if (psCtx && psCtxUrl === ordersUrl()) { renderPsCartModalFromCache(); return; }
        $('#psCartModalBody').html('<div class="psc-card-body"><div class="psc-skel"></div><div class="psc-loading">Cargando carrito…</div></div>');
        fetchCtx(false, renderPsCartModalFromCache);
    };

    function psCartAjax(method, suffix, data, onDone) {
        var base = window.HDCommerce ? window.HDCommerce.base() : null;
        var url = (base && psActiveCartId) ? (base + '/ps/cart/' + psActiveCartId + suffix) : null;
        if (!url) { onDone(false, null, 'No hay carrito activo.'); return; }

        $.ajax({
            url: url,
            method: method,
            data: data || {},
            dataType: 'json',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
        }).done(function (r) {
            onDone(!!(r && r.success), r, r && r.message);
        }).fail(function (xhr) {
            onDone(false, null, (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo completar la acción.');
        });
    }

    // Rechazo: banner ámbar con el motivo de PrestaShop, el código se queda
    // en el campo para corregirlo.
    function psCartShowError(msg) {
        $('#psCartModalBody .ps-cart-error-banner').remove();
        var $banner = $('<div class="ps-cart-error-banner"></div>').text(msg);
        $('#psCartModalBody .ps-cart-body').prepend($banner);
    }

    function psCartRefreshAfterAction() {
        fetchCtx(true, function (ctx) {
            renderPsCartModalFromCache();
            if (!$store().hasClass('bv-tab-hidden')) { renderStore(ctx); }
        });
    }

    $(document).on('click', '#psCartModalBody [data-cart-action]', function () {
        var $btn = $(this);
        var action = $btn.data('cart-action');

        if (action === 'change-address') {
            window.openPsAddressesModal('cart-picker', $btn.data('type') || 'delivery');
            return;
        }

        if (action === 'apply-voucher') {
            var $input = $('#psCartModalBody .ps-cart-voucher-input');
            var code = String($input.val() || '').trim();
            if (!code) { return; }
            $btn.prop('disabled', true).addClass('is-busy').text('Aplicando…');
            $input.prop('disabled', true);
            psCartAjax('POST', '/voucher', { code: code }, function (ok, r, errMsg) {
                $btn.prop('disabled', false).removeClass('is-busy').text('Aplicar');
                $input.prop('disabled', false);
                if (ok) { psCartRefreshAfterAction(); } else { psCartShowError(errMsg || 'El cupón ' + code + ' no es válido para este carrito.'); }
            });
            return;
        }

        if (action === 'remove-voucher') {
            var voucherCode = $btn.data('voucher-code');
            if (!voucherCode) { return; }
            // Confirmación inline: primer clic arma, segundo quita.
            if (!$btn.hasClass('is-armed')) {
                $btn.addClass('is-armed').attr('title', 'Pulsa otra vez para quitar');
                setTimeout(function () { $btn.removeClass('is-armed'); }, 3000);
                return;
            }
            $btn.prop('disabled', true);
            psCartAjax('DELETE', '/voucher', { code: voucherCode }, function (ok, r, errMsg) {
                if (ok) { psCartRefreshAfterAction(); return; }
                $btn.prop('disabled', false);
                psCartShowError(errMsg || 'No se pudo quitar el cupón.');
            });
            return;
        }

        var $row = $btn.closest('[data-cart-product-id]');
        var productId = $row.data('cart-product-id');
        // attribute_id: el backend exige nullable|min:1 — se omite cuando el
        // producto no tiene combinación en vez de mandar 0.
        var attributeId = parseInt($row.data('cart-attribute-id'), 10) || 0;

        if (action === 'qty-inc' || action === 'qty-dec') {
            var current = parseInt($row.find('.ps-cart-qty-val').text(), 10) || 1;
            var next = action === 'qty-inc' ? current + 1 : current - 1;
            if (next < 0) { return; }
            var qtyData = { product_id: productId, quantity: next };
            if (attributeId) { qtyData.attribute_id = attributeId; }
            $btn.prop('disabled', true);
            psCartAjax('PATCH', '/products/quantity', qtyData, function (ok, r, errMsg) {
                $btn.prop('disabled', false);
                if (ok) { psCartRefreshAfterAction(); } else { psCartShowError(errMsg || 'No se pudo actualizar la cantidad.'); }
            });
            return;
        }

        if (action === 'remove') {
            var removeData = { product_id: productId };
            if (attributeId) { removeData.attribute_id = attributeId; }
            $btn.prop('disabled', true);
            psCartAjax('DELETE', '/products', removeData, function (ok, r, errMsg) {
                $btn.prop('disabled', false);
                if (ok) { psCartRefreshAfterAction(); } else { psCartShowError(errMsg || 'No se pudo quitar el producto.'); }
            });
        }
    });

    /* ── API para prestashop-chat.js / order-workspace.js ─────── */

    window.PscStore = {
        ctx: function () { return psCtx; },
        load: function (force, cb) { fetchCtx(!!force, cb || function () {}); },
        refresh: function () { refreshStoreTab(true); },
        onChange: function (cb) { psListeners.push(cb); },
        cart: activeCart,
        money: psMoney,
        num: psNum,
        date: psFormatDate,
        relative: psRelativeTime,
        daysAgo: psDaysAgo,
        parse: psParse,
        esc: esc,
        escAttr: escAttr,
        initials: psInitials,
        insert: psInsertComposer,
        emptyHtml: psEmptyHtml,
        warnHtml: psWarnHtml,
        orderKind: psOrderKind,
        orderKindTag: function (kind) { return PS_KIND_TAG[kind] || PS_KIND_TAG.pending; },
        orderStateName: psOrderStateName,
        orderTotal: psOrderTotal,
        orderFirstLine: psOrderFirstLine,
        returnKind: psReturnKind,
        returnCardHtml: psReturnCardHtml,
        voucherCardHtml: psVoucherCardHtml,
        voucherState: psVoucherStateInfo,
        addressCardHtml: psAddressCardHtml,
        defaultAddressIds: psDefaultAddressIds,
        activity: psActivityEvents,
        cartSummaryText: psCartSummaryText,
        refundAmount: psRefundAmount,
        vouchersAvailable: psVouchersAvailable,
        phone: psCustomerPhone,
        reloadReturns: function (cb) { fetchScoped('/ps/returns', 'returns', cb || function () {}); },
    };
})();
