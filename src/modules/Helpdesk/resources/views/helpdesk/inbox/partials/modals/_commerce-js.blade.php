{{-- Helper JS compartido para los modales de pedidos y carritos --}}
@once
@push('scripts')
<script>
(function () {
    window.HDCommerce = {
        customerId: function () {
            return $('.bv-right').data('customer-id') || null;
        },
        conversationId: function () {
            return $('.bv-composer').data('bv-conversation-id') || null;
        },
        csrf: function () {
            return $('meta[name="csrf-token"]').attr('content');
        },
        base: function () {
            var id = this.customerId();
            return id ? '/panel/helpdesk/customers/' + id : null;
        },
        open: function (name) {
            $('[data-bv-modal-name="' + name + '"]').addClass('on');
            $('body').css('overflow', 'hidden');
            $(document).trigger('bv:modal:open', [name]);
        },
        close: function (name) {
            $('[data-bv-modal-name="' + name + '"]').removeClass('on');
            if (!$('.bv-modal.on').length) {
                $('body').css('overflow', '');
            }
        },
        hasCustomer: function () {
            return !!this.customerId();
        },
        customer: function () {
            var $r = $('.bv-right');
            return {
                name: $r.data('customer-name') || '',
                email: $r.data('customer-email') || '',
                phone: $r.data('customer-phone') || '',
                city: $r.data('customer-city') || '',
                state: $r.data('customer-state') || '',
                country: $r.data('customer-country') || 'ES',
                zip_code: $r.data('customer-zip') || '',
            };
        },
        money: function (n) {
            var v = (Number(n) || 0).toLocaleString('es-ES', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
            return '€ ' + v;
        },
        esc: function (s) {
            return $('<span>').text(s == null ? '' : String(s)).html();
        },
        ajax: function (opts) {
            return $.ajax($.extend({
                dataType: 'json',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': window.HDCommerce.csrf(),
                },
            }, opts));
        },
        errorMessage: function (xhr, fallback) {
            var j = xhr && xhr.responseJSON;
            if (j && j.errors) {
                var first = Object.values(j.errors)[0];
                if (first && first[0]) { return first[0]; }
            }
            return (j && j.message) || fallback || 'Ha ocurrido un error.';
        },
    };
})();

// ── Tab lateral "Carritos" ──
(function () {
    function esc(s) { return window.HDCommerce.esc(s); }
    function money(n) { return window.HDCommerce.money(n); }

    function renderCartsTab(data) {
        var counts = data.counts || {};
        var carts = data.carts || [];
        var active = null;
        var history = [];
        carts.forEach(function (c) {
            if (!active && c.status_group === 'active') { active = c; }
            else { history.push(c); }
        });

        var html = '';

        html += '<div class="rsp-section"><div class="bv-cart-quick">' +
            '<button class="r-tag" data-cart-action="carts"><i class="fas fa-cart-shopping ic"></i> Carritos</button>' +
            '<button class="r-tag add" data-cart-action="new"><i class="fas fa-plus ic"></i> Nuevo</button>' +
            '</div></div>';

        html += '<div class="rsp-section"><div class="lbl"><i class="fas fa-cart-shopping"></i> Resumen</div>' +
            '<div class="rsp-kv"><span class="k">Activos</span><span class="v mono">' + (counts.active || 0) + '</span></div>' +
            '<div class="rsp-kv"><span class="k">Abandonados</span><span class="v mono">' + (counts.abandoned || 0) + '</span></div>' +
            '<div class="rsp-kv"><span class="k">Convertidos</span><span class="v mono">' + (counts.converted || 0) + '</span></div>' +
            '</div>';

        if (active) {
            html += '<div class="rsp-section"><div class="lbl"><i class="fas fa-bolt"></i> Activo ahora</div>' +
                '<div class="bv-cart-now-card">' +
                    '<div class="top"><span class="lbl-live">En vivo</span><span class="id">' + esc(active.reference) + '</span><span class="live-dot"></span></div>' +
                    '<div class="body"><div class="price">' + money(active.total) + '</div>' +
                    '<div class="meta"><i class="fas fa-cart-shopping"></i> ' + active.items_count + ' artículos · ' + active.units_count + ' uds. <span class="bv-ms-auto"><i class="far fa-clock"></i> ' + esc(active.updated_at_human || '') + '</span></div></div>' +
                    '<div class="actions">' +
                        '<button type="button" data-cart-action="edit-active"><i class="fas fa-pen"></i> Editar</button>' +
                        '<button type="button" data-cart-action="detail" data-cart-id="' + active.id + '"><i class="fas fa-eye"></i> Ver</button>' +
                    '</div>' +
                '</div></div>';
        }

        if (history.length) {
            html += '<div class="rsp-section"><div class="lbl"><i class="fas fa-clock-rotate-left"></i> Historial</div><div class="bv-cart-hist-list">';
            history.forEach(function (c) {
                var stClass = c.status_group === 'converted' ? 'converted' : '';
                var icon = c.status_group === 'converted' ? 'fa-receipt' : 'fa-box-archive';
                html += '<button class="bv-cart-hist-item" type="button" data-cart-action="detail" data-cart-id="' + c.id + '">' +
                    '<div class="ico"><i class="fas ' + icon + '"></i></div>' +
                    '<div class="body">' +
                        '<div class="top"><span class="id">' + esc(c.reference) + '</span><span class="price">' + money(c.total) + '</span></div>' +
                        '<div class="desc">' + c.items_count + ' artículos' + (c.order_code ? ' → ' + esc(c.order_code) : '') + '</div>' +
                        '<div class="meta-row"><span class="st ' + stClass + '">' + esc(c.status_label) + '</span><span class="dot">·</span><span>' + esc(c.updated_at_human || '') + '</span></div>' +
                    '</div>' +
                '</button>';
            });
            html += '</div></div>';
        }

        if (!active && !history.length) {
            html += '<div class="bv-oc-empty"><i class="fas fa-cart-shopping"></i><div class="title">Sin carritos</div><div>Crea un carrito para este cliente</div></div>';
        }

        $('#bv-carts-tab').html(html);
    }

    function loadCartsTab() {
        var base = window.HDCommerce.base();
        if (!base || !$('#bv-carts-tab').length) { return; }
        $('#bv-carts-tab').html('<div class="bv-tab-loading"><i class="fas fa-spinner fa-spin"></i> Cargando carritos...</div>');
        window.HDCommerce.ajax({ url: base + '/carts', method: 'GET' })
            .done(function (resp) { renderCartsTab(resp); })
            .fail(function () {
                $('#bv-carts-tab').html('<div class="bv-oc-empty"><i class="fas fa-triangle-exclamation"></i><div class="title">No se pudieron cargar los carritos</div></div>');
            });
    }

    window.refreshCartsTab = function () { loadCartsTab(); };

    $(document).on('click', '[data-bv-tab="carts"]', function () { loadCartsTab(); });

    $(document).on('click', '[data-cart-action]', function () {
        var action = $(this).data('cart-action');
        var id = $(this).data('cart-id');
        if (action === 'carts' && typeof window.openCartsList === 'function') { window.openCartsList(); }
        else if ((action === 'new' || action === 'edit-active') && typeof window.openCartBuild === 'function') { window.openCartBuild(); }
        else if (action === 'detail' && id && typeof window.openCartDetail === 'function') { window.openCartDetail(id); }
    });
})();
</script>
@endpush
@endonce
