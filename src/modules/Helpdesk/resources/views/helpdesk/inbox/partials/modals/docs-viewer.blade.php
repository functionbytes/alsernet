{{-- Modal: Visualizar documentación del cliente (#32 ve-docs-viewer) --}}
<div class="bv-modal" data-bv-modal-name="docs-viewer">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="far fa-folder-open"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">DOCUMENTOS</span>
                <div class="bv-modal-title">Documentación del cliente <span class="bv-chip" id="docsViewerCount"></span></div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-info-table" id="docsViewerInfo" style="display:none">
                <div class="bv-info-table__row">
                    <span class="bv-info-table__k">Cliente</span>
                    <span class="bv-info-table__v" id="docsViewerCustomer"></span>
                </div>
                <div class="bv-info-table__row">
                    <span class="bv-info-table__k">Orden</span>
                    <span class="bv-info-table__v bv-mono" id="docsViewerOrder"></span>
                </div>
            </div>

            <div class="bv-hist-pills" id="docsViewerFilter" style="display:none">
                <button class="bv-hist-pill on" data-dvf="all">Todos <span class="bv-pill-count" id="dvCountAll">0</span></button>
                <button class="bv-hist-pill" data-dvf="approved">Aprobados <span class="bv-pill-count" id="dvCountApproved">0</span></button>
                <button class="bv-hist-pill" data-dvf="pending">Pendientes <span class="bv-pill-count" id="dvCountPending">0</span></button>
            </div>

            <div id="docsViewerLoading" class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>

            <div id="docsViewerGallery" class="bv-doc-gallery" style="display:none"></div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-docs-manage" style="display:none">Gestionar documento</button>
            <button class="btn-secondary" id="bv-docs-download-all">Descargar todo (.zip)</button>
            <button class="btn-secondary" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';

    var _dvAll      = [];
    var _dvFilter   = 'all';
    var _dvSelected = null;
    var _dvContext  = {};

    var STATUS_LABELS = { approved: 'Aprobado', pending: 'Pendiente', rejected: 'Rechazado' };

    function escHtml(s) {
        return $('<span>').text(s || '').html();
    }

    function fileIcon(type) {
        var ext = (type || '').toLowerCase();
        if (['jpg','jpeg','png','gif','webp'].indexOf(ext) !== -1) { return 'far fa-image'; }
        if (ext === 'pdf') { return 'far fa-file-pdf'; }
        return 'far fa-file-lines';
    }

    function renderGallery() {
        var list = _dvAll.filter(function (d) {
            if (_dvFilter === 'approved') { return d.status === 'approved'; }
            if (_dvFilter === 'pending')  { return d.status === 'pending'; }
            return true;
        });

        if (!list.length) {
            $('#docsViewerGallery').html('<div class="bv-cv-loading-msg"><i class="fas fa-inbox"></i></div>').show();
            return;
        }

        var html = list.map(function (d) {
            var stClass = d.status || 'pending';
            var selClass = _dvSelected === d.id ? ' on' : '';
            return '<button type="button" class="bv-doc-card' + selClass + '" data-doc-id="' + d.id + '">' +
                '<div class="bv-doc-card__thumb">' +
                    '<i class="' + fileIcon(d.type || d.extension) + '"></i>' +
                    '<span class="bv-doc-card__ext">' + escHtml((d.extension || d.type || '').toUpperCase()) + '</span>' +
                    '<span class="bv-doc-card__status bv-doc-card__status--' + stClass + '">' + escHtml(STATUS_LABELS[stClass] || stClass) + '</span>' +
                '</div>' +
                '<div class="bv-doc-card__info">' +
                    '<span class="bv-doc-card__nm">' + escHtml(d.name || d.label) + '</span>' +
                    '<span class="bv-doc-card__meta">' + escHtml(d.size || '—') + ' · ' + escHtml(d.date || '') + '</span>' +
                '</div>' +
                '</button>';
        }).join('');
        $('#docsViewerGallery').html(html).show();

        var total    = _dvAll.length;
        var approved = _dvAll.filter(function (d) { return d.status === 'approved'; }).length;
        var pending  = _dvAll.filter(function (d) { return d.status === 'pending'; }).length;
        $('#dvCountAll').text(total);
        $('#dvCountApproved').text(approved);
        $('#dvCountPending').text(pending);
    }

    $(document).on('bv:modal:open', function (e, name, data) {
        if (name !== 'docs-viewer') { return; }
        _dvSelected = null;
        _dvFilter   = 'all';
        _dvContext  = data || {};
        _dvAll      = [];
        $('#docsViewerGallery').hide().empty();
        $('#docsViewerLoading').show();
        $('#bv-docs-manage').hide();
        $('#docsViewerFilter').hide();
        $('#docsViewerInfo').hide();
        $('.bv-hist-pill[data-dvf]').removeClass('on');
        $('.bv-hist-pill[data-dvf="all"]').addClass('on');

        var customerId = _dvContext.customerId || $('[data-customer-id]').first().attr('data-customer-id');
        if (!customerId) {
            $('#docsViewerLoading').html('<i class="fas fa-user-slash"></i>');
            return;
        }

        var orderId = _dvContext.orderId;
        if (orderId) {
            $('#docsViewerOrder').text('#' + orderId);
            $('#docsViewerInfo').show();
        }
        var customerName = _dvContext.customerName || $('[data-customer-name]').first().attr('data-customer-name') || '';
        if (customerName) { $('#docsViewerCustomer').text(customerName); $('#docsViewerInfo').show(); }

        $.ajax({
            url: '/panel/helpdesk/customers/' + customerId + '/documents' + (orderId ? '?order_id=' + orderId : ''),
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (resp) {
            _dvAll = resp.data || resp || [];
            $('#docsViewerCount').text(_dvAll.length || '');
            $('#docsViewerLoading').hide();
            $('#docsViewerFilter').show();
            renderGallery();
        }).fail(function () {
            $('#docsViewerLoading').html('<i class="fas fa-triangle-exclamation"></i>');
        });
    });

    $(document).on('click', '.bv-hist-pill[data-dvf]', function () {
        $('.bv-hist-pill[data-dvf]').removeClass('on');
        $(this).addClass('on');
        _dvFilter   = $(this).data('dvf');
        _dvSelected = null;
        $('#bv-docs-manage').hide();
        renderGallery();
    });

    $(document).on('click', '.bv-doc-card', function () {
        $('.bv-doc-card').removeClass('on');
        $(this).addClass('on');
        _dvSelected = $(this).data('doc-id');
        $('#bv-docs-manage').show();
    });

    $(document).on('click', '#bv-docs-manage', function () {
        if (!_dvSelected) { return; }
        $('[data-bv-modal-name="docs-viewer"]').removeClass('on');
        $(document).trigger('bv:modal:open', ['doc-manage', { docId: _dvSelected }]);
    });

    $(document).on('click', '#bv-docs-download-all', function () {
        var customerId = _dvContext.customerId || $('[data-customer-id]').first().attr('data-customer-id');
        if (!customerId) { return; }
        window.location.href = '/panel/helpdesk/customers/' + customerId + '/documents/download-all';
    });

}(window.jQuery));
</script>
@endpush
@endonce
