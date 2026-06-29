/**
 * Customer 360 panel — fetches cross-platform data (Engagement, ERP, PrestaShop)
 * and renders it using the .rsp panel design system.
 */
(function () {
    'use strict';

    var $panel = $('#customer360Panel');
    if (!$panel.length) return;

    var customerId = $panel.data('customer-id');
    var inboxId    = $panel.data('inbox-id');
    var cached     = null;
    var loaded     = false;

    function endpoint(force) {
        var url = '/panel/helpdesk/customers/' + customerId + '/insights?panel=360';
        if (inboxId) url += '&inbox_id=' + inboxId;
        if (force)   url += '&force=1';
        return url;
    }

    function esc(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function fmtDate(iso) {
        if (!iso) return '—';
        try { return new Date(iso).toLocaleDateString('es', { day: '2-digit', month: 'short', year: 'numeric' }); }
        catch (e) { return iso; }
    }

    function fmtPrice(v) {
        if (v === null || v === undefined) return '—';
        return new Intl.NumberFormat('es', { style: 'currency', currency: 'EUR' }).format(v);
    }

    function emptyState(msg) {
        return '<div class="rsp-empty">' + esc(msg) + '</div>';
    }

    function kvRow(label, value, opts) {
        opts = opts || {};
        var cls = 'v' + (opts.mono ? ' mono' : '') + (opts.muted ? ' muted' : '');
        var style = opts.muted ? ' style="color:var(--bv-text-muted, #71717a)"' : '';
        return '<div class="rsp-kv">'
            + '<span class="k">' + esc(label) + '</span>'
            + '<span class="' + cls + '"' + style + '>' + value + '</span>'
            + '</div>';
    }

    /* ── Engagement ──────────────────────────────────────────────────── */
    function renderEngagement(data) {
        if (!data || !data.engagement) {
            return emptyState('Activa el módulo Engagement para ver score y segmentación.');
        }

        var e = data.engagement;
        var score = e.score ?? 0;
        var segment = e.segment || 'sin segmento';

        var segTag = '<span class="r-tag">' + esc(segment) + '</span>';
        var extraTags = (e.segment_names || []).map(function (n) {
            return '<span class="r-tag">' + esc(n) + '</span>';
        }).join(' ');

        return kvRow('Score', '<span class="mono">' + score + '</span>', { mono: true })
            + kvRow('Segmento', segTag + (extraTags ? ' ' + extraTags : ''))
            + kvRow('Últ. actividad', esc(fmtDate(e.last_activity_at)), { muted: !e.last_activity_at });
    }

    /* ── Top eventos ─────────────────────────────────────────────────── */
    function renderTopEvents(data) {
        var events = data?.engagement?.top_events || [];
        if (!events.length) {
            return emptyState('Sin eventos registrados');
        }
        return events.slice(0, 5).map(function (ev) {
            return '<div class="rsp-kv">'
                + '<span class="k" title="' + esc(ev.event_name) + '">' + esc(ev.event_name) + '</span>'
                + '<span class="v"><span class="r-tag">' + esc(ev.occurrences) + '×</span></span>'
                + '</div>';
        }).join('');
    }

    /* ── Pedidos ─────────────────────────────────────────────────────── */
    function renderOrders(data) {
        var platforms = data?.platforms || [];
        var allOrders = [];

        platforms.forEach(function (p) {
            if (p.ok && p.data?.orders) {
                (p.data.orders || []).forEach(function (o) {
                    allOrders.push(Object.assign({}, o, { _platform: p.platform }));
                });
            }
        });

        if (!allOrders.length) {
            return emptyState('No hay pedidos en plataformas integradas.');
        }

        return allOrders.slice(0, 10).map(function (o) {
            var ref = o.id || o.reference || '—';
            var price = fmtPrice(o.total_paid || o.total);
            var date = fmtDate(o.date_add || o.created_at);
            return '<div class="rsp-kv">'
                + '<span class="k">'
                +   '<span class="mono">' + esc(ref) + '</span>'
                +   ' · <small style="color:var(--bv-text-muted, #71717a)">' + esc(o._platform) + '</small>'
                + '</span>'
                + '<span class="v">'
                +   esc(price) + ' '
                +   '<small style="color:var(--bv-text-muted, #71717a)">· ' + esc(date) + '</small>'
                + '</span>'
                + '</div>';
        }).join('');
    }

    /* ── Datos ───────────────────────────────────────────────────────── */
    function renderData(data) {
        var platforms = data?.platforms || [];
        var blocks = [];

        platforms.forEach(function (p) {
            if (!p.ok) return;
            var d = p.data || {};
            var items = [];
            if (d.balance        !== undefined) items.push(['Balance',         fmtPrice(d.balance)]);
            if (d.loyalty_points !== undefined) items.push(['Puntos lealtad',  d.loyalty_points]);
            if (d.debts          !== undefined) items.push(['Deuda pendiente', fmtPrice(d.debts)]);
            if (d.bonuses        !== undefined) items.push(['Bonos',           d.bonuses]);

            if (!items.length) return;

            var rows = items.map(function (item) {
                return kvRow(item[0], esc(item[1]));
            }).join('');

            blocks.push('<div class="rsp-c360-platform">' + esc(p.platform) + '</div>' + rows);
        });

        if (!blocks.length) {
            return emptyState('No hay datos adicionales disponibles.');
        }

        return blocks.join('');
    }

    /* ── Recomendaciones ─────────────────────────────────────────────── */
    function renderRecommendations(data) {
        var recs = data?.engagement?.recommendations || [];

        if (!recs.length) {
            return emptyState('Sin recomendaciones generadas.');
        }

        return recs.slice(0, 5).map(function (r) {
            var img = r.image_url
                ? '<img src="' + esc(r.image_url) + '" class="rsp-c360-prod-img" width="40" height="40" alt="" loading="lazy">'
                : '<div class="rsp-c360-prod-img rsp-c360-prod-placeholder"><i class="fa-regular fa-image"></i></div>';

            var link = r.url
                ? '<a href="' + esc(r.url) + '" target="_blank" class="rsp-c360-prod-link" title="Ver producto"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>'
                : '';

            return '<div class="rsp-c360-product">'
                + img
                + '<div class="rsp-c360-prod-info">'
                +   '<div class="rsp-c360-prod-name">' + esc(r.name) + '</div>'
                +   '<div class="rsp-c360-prod-price">' + esc(fmtPrice(r.price)) + '</div>'
                + '</div>'
                + link
                + '</div>';
        }).join('');
    }

    /* ── Render all ──────────────────────────────────────────────────── */
    function render(data) {
        cached = data;
        $('#c360-engagement').html(renderEngagement(data));
        $('#c360-top-events').html(renderTopEvents(data));
        $('#c360-orders').html(renderOrders(data));
        $('#c360-data').html(renderData(data));
        $('#c360-recommendations').html(renderRecommendations(data));
    }

    function setLoading() {
        $('.bv-c360-block').html('<div class="rsp-empty"><i class="fa-solid fa-spinner fa-spin"></i> Cargando…</div>');
    }

    function load(force) {
        if (cached && !force) return;
        setLoading();
        $.ajax({
            url: endpoint(force),
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        })
        .done(render)
        .fail(function () {
            $('.bv-c360-block').html('<div class="rsp-empty"><i class="fa-solid fa-triangle-exclamation"></i> Error cargando datos</div>');
            if (typeof toastr !== 'undefined') toastr.error('No se pudieron cargar los datos del cliente');
        });
    }

    /* ── Triggers ────────────────────────────────────────────────────── */
    $(document).on('click', '.bv-right-tab[data-bv-tab="customer-360"]', function () {
        if (!loaded) { loaded = true; load(false); }
    });

    $(document).on('click', '#customer360Refresh', function () {
        cached = null;
        load(true);
    });
})();
