{{-- Modal: Listar pedidos del cliente (#36 ve-orders-list) --}}
<div class="bv-modal" data-bv-modal-name="orders-list">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-cart-shopping"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">CHAT · CLIENTE</span>
                <div class="bv-modal-title">Pedidos <span class="bv-chip" id="ordersListCount"></span></div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-modal-search" style="margin-bottom:8px">
                <i class="fas fa-magnifying-glass"></i>
                <input id="ordersListSearch" type="text" placeholder="Buscar por número, producto…" autocomplete="off">
            </div>

            <div class="bv-hist-pills" id="ordersListFilter">
                <button class="bv-hist-pill on" data-olf="all">Todos <span class="bv-pill-count" id="olfCountAll">0</span></button>
                <button class="bv-hist-pill" data-olf="pending">Pendientes <span class="bv-pill-count" id="olfCountPending">0</span></button>
                <button class="bv-hist-pill" data-olf="shipped">Enviados <span class="bv-pill-count" id="olfCountShipped">0</span></button>
                <button class="bv-hist-pill" data-olf="delivered">Entregados <span class="bv-pill-count" id="olfCountDelivered">0</span></button>
            </div>

            <div id="ordersListLoading" class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>
            <div id="ordersListContent" style="display:none;flex-direction:column;gap:7px"></div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-orders-new">Nuevo pedido</button>
            <button class="btn-secondary" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';

    var _olfAll    = [];
    var _olfFilter = 'all';
    var _olfSearch = '';
    var _olfTimer  = null;

    var STATUS_CLASSES = { pending: 'pending', shipped: 'shipped', delivered: 'delivered', cancelled: 'cancelled' };

    function escHtml(s) {
        return $('<span>').text(s || '').html();
    }

    function filtered() {
        return _olfAll.filter(function (o) {
            var okF = _olfFilter === 'all' || (o.status_slug || o.status) === _olfFilter;
            var okS = !_olfSearch ||
                (o.reference || '').toLowerCase().indexOf(_olfSearch) !== -1 ||
                (o.product_summary || '').toLowerCase().indexOf(_olfSearch) !== -1;
            return okF && okS;
        });
    }

    function renderOrders() {
        var list = filtered();
        if (!list.length) {
            $('#ordersListContent').html('<div class="bv-cv-loading-msg"><i class="fas fa-inbox"></i></div>').css('display','flex');
            return;
        }
        var html = list.map(function (o) {
            var statusSlug  = o.status_slug || o.status || 'pending';
            var statusLabel = o.status_label || o.status || '—';
            var trackHtml   = o.tracking ? '<span><i class="fas fa-truck"></i> ' + escHtml(o.carrier || '') + ' · ' + escHtml(o.tracking) + '</span>' : '';
            var deliveryHtml = o.delivered_at ? '<span><i class="fas fa-check"></i> Entregado ' + escHtml(o.delivered_at) + '</span>' : '';
            return '<button type="button" class="bv-ord-card" data-order-id="' + o.id + '" data-order-ref="' + escHtml(o.reference) + '">' +
                '<div class="bv-ord-card__head">' +
                    '<span class="bv-ord-card__id">' + escHtml(o.reference) + '</span>' +
                    '<span class="bv-ord-card__status bv-ord-card__status--' + statusSlug + '">' + escHtml(statusLabel) + '</span>' +
                '</div>' +
                '<div class="bv-ord-card__product">' + escHtml(o.product_summary || '') + '</div>' +
                '<div class="bv-ord-card__row">' +
                    (o.date ? '<span><i class="far fa-calendar"></i> ' + escHtml(o.date) + '</span>' : '') +
                    (o.items_count ? '<span><i class="fas fa-box"></i> ' + o.items_count + ' artículos</span>' : '') +
                    trackHtml + deliveryHtml +
                    (o.total ? '<span class="bv-ord-card__total">' + escHtml(o.total) + '</span>' : '') +
                '</div>' +
                '</button>';
        }).join('');
        $('#ordersListContent').html(html).css('display','flex');

        var counts = { all: _olfAll.length, pending: 0, shipped: 0, delivered: 0 };
        _olfAll.forEach(function (o) {
            var s = o.status_slug || o.status;
            if (counts[s] !== undefined) { counts[s]++; }
        });
        $('#olfCountAll').text(counts.all);
        $('#olfCountPending').text(counts.pending);
        $('#olfCountShipped').text(counts.shipped);
        $('#olfCountDelivered').text(counts.delivered);
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'orders-list') { return; }
        _olfAll    = [];
        _olfFilter = 'all';
        _olfSearch = '';
        $('#ordersListSearch').val('');
        $('.bv-hist-pill[data-olf]').removeClass('on');
        $('.bv-hist-pill[data-olf="all"]').addClass('on');
        $('#ordersListContent').hide();
        $('#ordersListLoading').show().html('<i class="fas fa-spinner fa-spin"></i>');
        $('#ordersListCount').text('');

        var customerId = $('[data-customer-id]').first().attr('data-customer-id');
        if (!customerId) {
            $('#ordersListLoading').html('<i class="fas fa-user-slash"></i>');
            return;
        }

        $.ajax({
            url: '/panel/helpdesk/customers/' + customerId + '/orders',
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (resp) {
            _olfAll = resp.data || resp || [];
            $('#ordersListCount').text(_olfAll.length);
            $('#ordersListLoading').hide();
            renderOrders();
        }).fail(function () {
            $('#ordersListLoading').html('<i class="fas fa-triangle-exclamation"></i>');
        });
    });

    $(document).on('click', '.bv-hist-pill[data-olf]', function () {
        $('.bv-hist-pill[data-olf]').removeClass('on');
        $(this).addClass('on');
        _olfFilter = $(this).data('olf');
        renderOrders();
    });

    $(document).on('input', '#ordersListSearch', function () {
        clearTimeout(_olfTimer);
        var q = $(this).val().toLowerCase().trim();
        _olfTimer = setTimeout(function () { _olfSearch = q; renderOrders(); }, 200);
    });

    $(document).on('click', '.bv-ord-card', function () {
        var orderId = $(this).data('order-id');
        var orderRef = $(this).data('order-ref');
        $('[data-bv-modal-name="orders-list"]').removeClass('on');
        $(document).trigger('bv:modal:open', ['order', { orderId: orderId, orderRef: orderRef }]);
    });

    $(document).on('click', '#bv-orders-new', function () {
        $('[data-bv-modal-name="orders-list"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
        if (window.toastr) { toastr.info('Usa el módulo de pedidos para crear uno nuevo'); }
    });

}(window.jQuery));
</script>
@endpush
@endonce
