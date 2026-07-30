{{-- Workspace de pedido ERP (bv-modal) — READ-ONLY (el ERP no permite mutar
     pedidos como PrestaShop). Datos reales vía el manager/Oracle. El shape es
     el del contrato de ErpCustomerDataService::getOrderDetail(). --}}

{{-- CSS del panel/tarjetas ERP (.rp3-points/.rp3-stat), movido desde el core.
     Se carga aquí (modal siempre presente si el módulo está activo) para cubrir
     el tab del panel derecho y este modal. --}}
<link rel="stylesheet" href="{{ asset('modules/helpdeskerp/css/erp-inbox.css') }}?v={{ @filemtime(public_path('modules/helpdeskerp/css/erp-inbox.css')) }}"/>

{{-- Motor del panel ERP (carga lazy de tabs + detalle de pedido), movido desde
     conversations.js del core. Vía @push('scripts') para cargarse al final del body,
     DESPUÉS de jQuery/conversations.js (un <script> directo aquí correría antes y
     saldría por el guard `typeof jQuery === undefined`). Guard idempotente en el JS. --}}
@push('scripts')
    <script src="{{ asset('modules/helpdeskerp/js/erp-inbox.js') }}?v={{ @filemtime(public_path('modules/helpdeskerp/js/erp-inbox.js')) }}"></script>
@endpush

<div class="bv-modal" data-bv-modal-name="erp-order-workspace">
    <div class="bv-modal-dialog xxl bv-po-dialog">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-clipboard-list"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label"><i class="fas fa-database"></i> ERP · Gestión</span>
                <div class="bv-modal-title"><span id="erpowTitle">Pedido</span></div>
            </div>
            <span class="bv-po-status" id="erpowStatus"></span>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-modal-body bv-po-body">
            <div class="bv-po-loading" id="erpowLoading"><i class="fas fa-spinner fa-spin"></i> Cargando pedido del ERP… <span class="bv-po-loading-hint">La consulta a Oracle puede tardar unos segundos.</span></div>
            <div class="bv-po-error bv-hidden" id="erpowError"><i class="fas fa-triangle-exclamation"></i> <span></span></div>

            <div class="bv-po-grid bv-hidden" id="erpowGrid">
                {{-- Columna principal: contenido --}}
                <div class="bv-po-main">
                    <div class="bv-po-card">
                        <div class="bv-po-card-h">
                            <div class="bv-po-card-ht">
                                <span class="t">Contenido del pedido</span>
                                <span class="s" id="erpowSummary"></span>
                            </div>
                        </div>
                        <div id="erpowLines"></div>
                        <div class="bv-po-totals" id="erpowTotals"></div>
                        <div id="erpowObs"></div>
                    </div>
                </div>

                {{-- Panel lateral: pestañas --}}
                <div class="bv-po-side">
                    <div class="bv-po-tabs" id="erpowTabs">
                        <button type="button" class="bv-po-tab on" data-po-tab="info" title="Información"><i class="fas fa-circle-info"></i></button>
                        <button type="button" class="bv-po-tab" data-po-tab="cliente" title="Cliente"><i class="far fa-address-card"></i></button>
                    </div>
                    <div class="bv-po-panel" data-po-panel="info" id="erpowPanelInfo"></div>
                    <div class="bv-po-panel bv-hidden" data-po-panel="cliente" id="erpowPanelCliente"></div>
                </div>
            </div>
        </div>

        <div class="bv-modal-foot">
            <button class="btn-secondary w-100" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    var C = window.HDCommerce;
    var _cid = null, _oid = null;
    var _detailCache = {}; // idem al modal nativo: cachea el detalle por pedido

    // Mismo mapeo de código→etiqueta que usa el inbox nativo (conversations.js).
    var ERP_STATUS = { 0: 'Pendiente', 1: 'Confirmado', 2: 'En preparación', 3: 'Enviado', 5: 'Entregado', 7: 'Servido', 9: 'Cancelado' };
    function statusLabel(code) {
        if (code == null) { return 'Sin estado'; }
        return ERP_STATUS[code] || ('Estado ' + code);
    }

    function $body() { return $('[data-bv-modal-name="erp-order-workspace"]'); }
    function money(n) { return C.money(n); }
    function esc(s) { return C.esc(s); }
    function fmtDate(s) {
        if (!s) { return '—'; }
        var d = new Date(String(s).replace(' ', 'T'));
        if (isNaN(d.getTime())) { return esc(s); }
        return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function setLoading() { $('#erpowLoading').removeClass('bv-hidden'); $('#erpowError').addClass('bv-hidden'); $('#erpowGrid').addClass('bv-hidden'); }
    function setError(msg) { $('#erpowLoading').addClass('bv-hidden'); $('#erpowGrid').addClass('bv-hidden'); $('#erpowError').removeClass('bv-hidden').find('span').text(msg || 'No se pudo cargar el pedido.'); }
    function setReady() { $('#erpowLoading').addClass('bv-hidden'); $('#erpowError').addClass('bv-hidden'); $('#erpowGrid').removeClass('bv-hidden'); }

    function render(o) {
        var num = o.number || ('#' + o.id);
        var custName = C.customer().name || '';
        $('#erpowTitle').html('Pedido <span class="bv-po-chip">' + esc(num) + '</span>' +
            (custName ? ' <span class="bv-po-crumb">' + esc(custName) + '</span>' : ''));
        // Estado mapeado a etiqueta legible (mismo mapeo que el inbox nativo).
        $('#erpowStatus').html('<span class="bv-po-pill" style="background:#eef0f2;color:#52525b;border:1px solid #e5e7eb">' +
            esc(statusLabel(o.status)) + '</span>');

        renderLines(o);
        renderTotals(o);
        renderObs(o);
        renderInfo(o);
        renderCliente(o);
        setReady();
    }

    function renderLines(o) {
        var lines = o.lines || [];
        var units = lines.reduce(function (s, l) { return s + (parseInt(l.qty, 10) || 0); }, 0);
        $('#erpowSummary').text(lines.length + ' línea(s) · ' + units + ' unidades');
        if (!lines.length) { $('#erpowLines').html('<div class="bv-po-empty">Este pedido no tiene líneas o el usuario de lectura no tiene acceso a ellas.</div>'); return; }
        $('#erpowLines').html(lines.map(function (l) {
            var qty = parseInt(l.qty, 10) || 1;
            var net = qty * (Number(l.price) || 0) * (1 - (Number(l.discount) || 0) / 100) * (1 + (Number(l.vat) || 0) / 100);
            var chips = '';
            if (Number(l.discount) > 0) { chips += '<span class="bv-po-lnchip disc">−' + esc(l.discount) + '%</span>'; }
            if (Number(l.vat) > 0) { chips += '<span class="bv-po-lnchip">IVA ' + esc(l.vat) + '%</span>'; }
            return '<div class="bv-po-line">' +
                '<div class="thumb"><i class="fas fa-box"></i></div>' +
                '<div class="body"><div class="nm">' + esc(l.name) + '</div>' +
                    '<div class="sku">' + money(l.price) + ' /ud' + (chips ? ' <span class="bv-po-lntags">' + chips + '</span>' : '') + '</div></div>' +
                '<div class="qty">×' + qty + '</div>' +
                '<div class="price">' + money(net) + '</div>' +
            '</div>';
        }).join(''));
    }

    function renderTotals(o) {
        $('#erpowTotals').html('<div class="row total"><span class="k">Total</span><span class="v">' +
            (o.total != null ? money(o.total) : '—') + '</span></div>');
    }

    function renderObs(o) {
        if (!o.observations) { $('#erpowObs').html(''); return; }
        $('#erpowObs').html('<div class="bv-po-obs"><span class="k">Observaciones</span><div class="v">' + esc(o.observations) + '</div></div>');
    }

    function kv(k, v, mono) {
        if (v == null || v === '') { return ''; }
        return '<div><div class="k">' + k + '</div><div class="v' + (mono ? ' mono' : '') + '">' + esc(v) + '</div></div>';
    }

    function renderInfo(o) {
        $('#erpowPanelInfo').html(
            '<div class="bv-po-card">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-circle-info"></i></span>' +
                    '<div class="bv-po-card-ht"><span class="t">Datos del pedido</span><span class="s">Gestión ERP</span></div></div>' +
                '<div class="bv-po-kv">' +
                    kv('Nº pedido', o.number, true) +
                    kv('Estado', o.status != null ? statusLabel(o.status) : null) +
                    '<div><div class="k">Fecha pedido</div><div class="v mono">' + fmtDate(o.date) + '</div></div>' +
                    '<div><div class="k">Prevista</div><div class="v mono">' + fmtDate(o.expected_date) + '</div></div>' +
                    '<div><div class="k">Servido</div><div class="v mono">' + fmtDate(o.served_date) + '</div></div>' +
                    kv('Almacén', o.warehouse) +
                    kv('Forma de pago', o.payment_method) +
                '</div>' +
            '</div>'
        );
    }

    function renderCliente(o) {
        var c = C.customer();
        $('#erpowPanelCliente').html(
            '<div class="bv-po-card">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="far fa-address-card"></i></span>' +
                    '<div class="bv-po-card-ht"><span class="t">Cliente</span><span class="s">Contacto ERP</span></div></div>' +
                '<div class="bv-po-kv">' +
                    kv('Nombre', c.name) +
                    kv('Correo', c.email, true) +
                    kv('Teléfono', o.phone || c.phone, true) +
                '</div>' +
            '</div>' +
            '<div class="bv-po-card">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-location-dot"></i></span>' +
                    '<div class="bv-po-card-ht"><span class="t">Dirección de envío</span></div></div>' +
                (o.address ? '<div class="bv-po-addr">' + esc(o.address) + '</div>' : '<div class="bv-po-empty">Sin dirección registrada.</div>') +
            '</div>'
        );
    }

    function loadOrder(customerId, orderId) {
        _cid = customerId; _oid = orderId;
        // Caché de detalle (como el modal nativo): Oracle es lento, así que una
        // segunda apertura del mismo pedido es instantánea.
        if (_detailCache[orderId]) { render(_detailCache[orderId]); return; }
        setLoading();
        // Oracle directo (oci8) puede tardar decenas de segundos: timeout amplio
        // y explícito en vez de dejar el modal colgado indefinidamente.
        $.ajax({
            url: '/panel/helpdesk/erp/orders/' + customerId + '/' + orderId,
            method: 'GET', dataType: 'json',
            timeout: 45000,
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': C.csrf() },
        }).done(function (r) {
            if (r && r.success && r.data) { _detailCache[orderId] = r.data; render(r.data); }
            else { setError('El pedido no está disponible.'); }
        }).fail(function (xhr, status) {
            if (status === 'timeout') { setError('El ERP tardó demasiado en responder. Vuelve a intentarlo en un momento.'); return; }
            setError(xhr.status === 404 ? 'Pedido no encontrado.' : (xhr.status === 403 ? 'Sin permiso para ver este pedido.' : 'El ERP no responde ahora mismo.'));
        });
    }

    $(document).on('click', '#erpowTabs .bv-po-tab', function () {
        var go = $(this).data('po-tab');
        $('#erpowTabs .bv-po-tab').removeClass('on');
        $(this).addClass('on');
        $body().find('.bv-po-panel').addClass('bv-hidden').filter('[data-po-panel="' + go + '"]').removeClass('bv-hidden');
    });

    // API pública. customerId = idcliente del ERP; orderId = idpedidocli_central.
    window.openErpOrderWorkspace = function (customerId, orderId) {
        if (!customerId || !orderId) { return; }
        $('#erpowTabs .bv-po-tab').removeClass('on').filter('[data-po-tab="info"]').addClass('on');
        $body().find('.bv-po-panel').addClass('bv-hidden').filter('[data-po-panel="info"]').removeClass('bv-hidden');
        C.open('erp-order-workspace');
        loadOrder(customerId, orderId);
    };
})();
</script>
@endpush
@endonce
