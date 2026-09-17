/*!
 * HelpdeskPrestashop · modal "carts-list" del inbox.
 *
 * Extraido de resources/views/modals/carts-list.blade.php, donde vivia como
 * <script> inline que se re-descargaba en CADA carga del inbox (el modal se
 * incluye siempre desde helpdesk/inbox/partials/modals.blade.php). No tiene
 * interpolacion Blade: la config llega por atributos data-* y por
 * window.HDCommerce, que define el core en modals/_commerce-js.blade.php.
 *
 * OJO 1: depende de window.HDCommerce en el nivel superior, asi que debe
 * cargarse DESPUES de _commerce-js — lo garantiza el orden de @include en
 * modals.blade.php (_commerce-js antes que carts-list).
 * OJO 2: la fuente es este fichero; asset() sirve desde
 * public/modules/helpdeskprestashop/js/ — hay que copiarlo alli tras editar.
 */
(function () {
    var _allCarts = [];
    var _activeFilter = 'all';

    /* ── helpers ── */
    function modal() { return $('[data-bv-modal-name="carts-list"]'); }

    function statusClass(group) {
        if (group === 'abandoned') { return 'draft'; }
        if (group === 'converted') { return 'delivered'; }
        return '';
    }

    /* ── load ── */
    function loadCarts() {
        var cid = HDCommerce.customerId();
        if (!cid) { return; }

        $('#clList').html('<div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> Cargando…</div>');

        $.ajax({
            url: HDCommerce.base() + '/carts',
            method: 'GET',
            dataType: 'json',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        }).done(function (resp) {
            _allCarts = resp.carts || [];
            var counts = resp.counts || {};

            $('#clCountAll').text(counts.all || 0);
            $('#clCountActive').text(counts.active || 0);
            $('#clCountAbandoned').text(counts.abandoned || 0);
            $('#clCountConverted').text(counts.converted || 0);

            renderList(_activeFilter);
        }).fail(function (xhr) {
            $('#clList').html('<div class="bv-oc-empty"><i class="fas fa-triangle-exclamation"></i><div class="title">Error al cargar</div><div>' + HDCommerce.errorMessage(xhr, 'No se pudieron cargar los carritos') + '</div></div>');
        });
    }

    /* ── render ── */
    function renderList(filter) {
        _activeFilter = filter;

        var carts = filter === 'all'
            ? _allCarts
            : _allCarts.filter(function (c) { return c.status_group === filter; });

        if (!carts.length) {
            $('#clList').html('<div class="bv-oc-empty"><i class="fas fa-cart-shopping"></i><div class="title">Sin carritos</div><div>Este cliente no tiene carritos</div></div>');
            return;
        }

        var html = carts.map(function (c) {
            var sc   = statusClass(c.status_group);
            var ref  = HDCommerce.esc(c.reference);
            var lbl  = HDCommerce.esc(c.status_label);
            var upd  = HDCommerce.esc(c.updated_at_human);
            var crt  = HDCommerce.esc(c.created_at_human);
            var tot  = HDCommerce.money(c.total);
            var art  = c.items_count === 1 ? '1 artículo' : c.items_count + ' artículos';

            var orderSpan = c.order_code
                ? '<span><i class="fas fa-check"></i> Pedido ' + HDCommerce.esc(c.order_code) + '</span>'
                : '';

            return '<button class="bv-ord-card" data-cart-id="' + c.id + '">' +
                '<div class="head"><span class="id">' + ref + '</span><span class="status ' + sc + '">' + lbl + '</span></div>' +
                '<div class="customer">' + art + ' · actualizado ' + upd + '</div>' +
                '<div class="row"><span><i class="far fa-clock"></i> Iniciado ' + crt + '</span>' + orderSpan + '<span class="total">' + tot + '</span></div>' +
            '</button>';
        }).join('');

        $('#clList').html('<div class="bv-oc-list">' + html + '</div>');
    }

    /* ── events ── */
    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'carts-list') { return; }
        _activeFilter = 'all';
        $('#clFilterRow .bv-media-pill').removeClass('on');
        $('#clFilterRow .bv-media-pill[data-cf="all"]').addClass('on');
        loadCarts();
    });

    modal().on('click', '.bv-media-pill[data-cf]', function () {
        modal().find('.bv-media-pill[data-cf]').removeClass('on');
        $(this).addClass('on');
        renderList($(this).data('cf'));
    });

    $(document).on('click', '.bv-ord-card[data-cart-id]', function () {
        if (typeof window.openCartDetail === 'function') {
            window.openCartDetail($(this).data('cart-id'));
        }
    });

    $('#clNew').on('click', function () {
        if (typeof window.openCartBuild === 'function') {
            window.openCartBuild();
        }
    });

    /* ── public API ── */
    window.openCartsList = function () {
        if (!HDCommerce.customerId()) {
            toastr.warning('Selecciona una conversación con cliente');
            return;
        }
        HDCommerce.open('carts-list');
        loadCarts();
    };
}());
