{{-- Modal: Cargar documento desde galería del chat (#34 ve-doc-from-chat) --}}
<div class="bv-modal" data-bv-modal-name="doc-from-chat">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-cloud-arrow-up"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">DOCUMENTOS · IMPORTAR</span>
                <div class="bv-modal-title">Cargar desde galería del chat</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-minfo bv-minfo--info">
                <i class="fas fa-circle-info"></i>
                <div>Selecciona los archivos compartidos en el chat para asociar al expediente <b id="dfcOrderRef">—</b>.</div>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">Categoría destino</label>
                <select id="dfcCategory" class="bv-form-input">
                    <option value="dni_front">DNI frontal</option>
                    <option value="dni_back">DNI trasera</option>
                    <option value="invoice">Factura proforma</option>
                    <option value="contract">Contrato firmado</option>
                    <option value="other">Otro — sin categoría</option>
                </select>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">Archivos disponibles en el chat <span class="bv-form-hint" id="dfcFilesHint"></span></label>
                <div id="dfcLoading" class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>
                <div id="dfcGrid" class="bv-cg-grid" style="display:none"></div>
            </div>

            <label class="bv-check">
                <input type="checkbox" id="dfcNotifyClient" checked>
                Notificar al cliente cuando se carguen los documentos
            </label>
            <label class="bv-check">
                <input type="checkbox" id="dfcKeepChat">
                Mantener copia original en el chat
            </label>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-dfc-import" disabled>Importar archivos</button>
            <button class="btn-secondary" id="bv-dfc-from-disk">Subir desde mi equipo</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';

    var _dfcSelected = [];
    var _dfcAll      = [];
    var _dfcContext  = {};

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    function escHtml(s) {
        return $('<span>').text(s || '').html();
    }

    function fileIcon(type) {
        var t = (type || '').toLowerCase();
        if (['jpg','jpeg','png','gif','webp'].indexOf(t) !== -1) { return 'far fa-image'; }
        if (t === 'pdf') { return 'far fa-file-pdf'; }
        if (['mp4','webm','mov'].indexOf(t) !== -1) { return 'fas fa-video'; }
        return 'far fa-file-lines';
    }

    function renderGrid() {
        var html = _dfcAll.map(function (f, i) {
            var on = _dfcSelected.indexOf(i) !== -1 ? ' on' : '';
            return '<button type="button" class="bv-cg-pick' + on + '" data-dfc-idx="' + i + '">' +
                '<i class="bv-cg-pick__preview ' + fileIcon(f.type || f.extension) + '"></i>' +
                '<span class="bv-cg-pick__check"><i class="fas fa-check"></i></span>' +
                '<span class="bv-cg-pick__lbl">' + escHtml(f.name) + '</span>' +
                '</button>';
        }).join('');
        $('#dfcGrid').html(html).show();
        $('#dfcLoading').hide();
        updateImportBtn();
    }

    function updateImportBtn() {
        var n = _dfcSelected.length;
        $('#bv-dfc-import').prop('disabled', n === 0).text(n ? 'Importar ' + n + ' ' + (n === 1 ? 'archivo' : 'archivos') : 'Importar archivos');
    }

    $(document).on('bv:modal:open', function (e, name, data) {
        if (name !== 'doc-from-chat') { return; }
        _dfcSelected = [];
        _dfcAll      = [];
        _dfcContext  = data || {};
        $('#dfcGrid').hide().empty();
        $('#dfcLoading').show();
        $('#bv-dfc-import').prop('disabled', true).text('Importar archivos');

        var orderRef = _dfcContext.orderRef || '—';
        $('#dfcOrderRef').text('#' + orderRef);

        var convId = $('.bv-composer').data('bv-conversation-id') || null;
        if (!convId) {
            $('#dfcLoading').html('<i class="fas fa-triangle-exclamation"></i> Sin conversación activa');
            return;
        }

        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/attachments',
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (resp) {
            _dfcAll = resp.data || resp || [];
            $('#dfcFilesHint').text(_dfcAll.length + ' archivos');
            renderGrid();
        }).fail(function () {
            $('#dfcLoading').html('<i class="fas fa-triangle-exclamation"></i>');
        });
    });

    $(document).on('click', '.bv-cg-pick', function () {
        var idx = parseInt($(this).data('dfc-idx'), 10);
        var pos = _dfcSelected.indexOf(idx);
        if (pos === -1) {
            _dfcSelected.push(idx);
            $(this).addClass('on');
        } else {
            _dfcSelected.splice(pos, 1);
            $(this).removeClass('on');
        }
        updateImportBtn();
    });

    $(document).on('click', '#bv-dfc-import', function () {
        if (!_dfcSelected.length) { return; }
        var convId = $('.bv-composer').data('bv-conversation-id') || null;
        var files  = _dfcSelected.map(function (i) { return _dfcAll[i]; });

        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Importando…');

        $.ajax({
            url: '/panel/helpdesk/documents/import-from-chat',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                conversation_id:  convId,
                order_ref:        _dfcContext.orderRef,
                category:         $('#dfcCategory').val(),
                file_ids:         files.map(function (f) { return f.id; }),
                notify_client:    $('#dfcNotifyClient').is(':checked'),
                keep_in_chat:     $('#dfcKeepChat').is(':checked'),
            }),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('doc-from-chat');
            if (window.toastr) { toastr.success('Archivos importados al expediente'); }
            $(document).trigger('bv:documents:imported', [_dfcContext.orderRef]);
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al importar archivos';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Importar archivos');
        });
    });

    $(document).on('click', '#bv-dfc-from-disk', function () {
        closeBvModal('doc-from-chat');
        $(document).trigger('bv:modal:open', ['attach-file']);
    });

}(window.jQuery));
</script>
@endpush
@endonce
