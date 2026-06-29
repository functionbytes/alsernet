{{-- Modal: Gestionar documento (#33 ve-doc-manage) --}}
<div class="bv-modal" data-bv-modal-name="doc-manage">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="far fa-folder"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">DOCUMENTOS · GESTIONAR</span>
                <div class="bv-modal-title" id="dmTitle">Documento</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div id="dmLoading" class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>

            <div id="dmContent" style="display:none">

                {{-- Validación --}}
                <div class="bv-gd-section">
                    <div class="bv-gd-section__h">
                        <span class="bv-gd-section__t">Validación</span>
                        <span class="bv-gd-section__s">Progreso de validación del documento</span>
                    </div>
                    <div class="bv-gd-validation">
                        <span class="bv-gd-dot" id="dmStatusDot"></span>
                        Etapa actual: <b id="dmStatusLabel">—</b>
                    </div>
                    <div style="display:flex;gap:6px">
                        <button type="button" class="btn-primary" id="bv-dm-approve" style="flex:1;justify-content:center">Aprobar</button>
                        <button type="button" class="btn-secondary" id="bv-dm-reject" style="flex:1;justify-content:center">Rechazar</button>
                    </div>
                </div>

                {{-- Acciones de envío --}}
                <div class="bv-gd-section">
                    <div class="bv-gd-section__h">
                        <span class="bv-gd-section__t">Acciones de envío</span>
                        <span class="bv-gd-section__s">Comunicaciones automáticas</span>
                    </div>
                    <div class="bv-gd-action-row">
                        <div class="bv-gd-action-row__body">
                            <div class="bv-gd-action-row__nm">Solicitud inicial</div>
                            <div class="bv-gd-action-row__ds">Solicitar documentos al cliente</div>
                        </div>
                        <button type="button" class="btn-secondary btn-sm bv-dm-send-action" data-action="request">Enviar</button>
                    </div>
                    <div class="bv-gd-action-row">
                        <div class="bv-gd-action-row__body">
                            <div class="bv-gd-action-row__nm">Recordatorio</div>
                            <div class="bv-gd-action-row__ds">Reenviar solicitud pendiente</div>
                        </div>
                        <button type="button" class="btn-secondary btn-sm bv-dm-send-action" data-action="reminder">Enviar</button>
                    </div>
                    <div class="bv-gd-action-row">
                        <div class="bv-gd-action-row__body">
                            <div class="bv-gd-action-row__nm">Confirmación de carga</div>
                            <div class="bv-gd-action-row__ds">Avisar recepción correcta</div>
                        </div>
                        <button type="button" class="btn-secondary btn-sm bv-dm-send-action" data-action="upload_confirm">Enviar</button>
                    </div>
                    <div class="bv-gd-action-row">
                        <div class="bv-gd-action-row__body">
                            <div class="bv-gd-action-row__nm">Notificación aprobación</div>
                            <div class="bv-gd-action-row__ds">Documento aprobado</div>
                        </div>
                        <button type="button" class="btn-secondary btn-sm bv-dm-send-action" data-action="approved">Enviar</button>
                    </div>
                </div>

                {{-- Información del cliente --}}
                <div class="bv-gd-section">
                    <div class="bv-gd-section__h"><span class="bv-gd-section__t">Información del cliente</span></div>
                    <div class="bv-frow">
                        <div class="bv-form-field">
                            <label class="bv-form-label">Nombre</label>
                            <input id="dmClientName" type="text" class="bv-form-input" autocomplete="off">
                        </div>
                        <div class="bv-form-field">
                            <label class="bv-form-label">Apellidos</label>
                            <input id="dmClientLastName" type="text" class="bv-form-input" autocomplete="off">
                        </div>
                    </div>
                    <div class="bv-frow">
                        <div class="bv-form-field">
                            <label class="bv-form-label">DNI/NIE/CIF</label>
                            <input id="dmClientId" type="text" class="bv-form-input" autocomplete="off">
                        </div>
                        <div class="bv-form-field">
                            <label class="bv-form-label">Correo</label>
                            <input id="dmClientEmail" type="email" class="bv-form-input" autocomplete="off">
                        </div>
                    </div>
                </div>

                {{-- Notas --}}
                <div class="bv-gd-section">
                    <div class="bv-gd-section__h"><span class="bv-gd-section__t">Notas internas</span></div>
                    <textarea id="dmNotes" class="bv-form-input" rows="2" placeholder="Añadir comentario sobre el documento…"></textarea>
                </div>

                {{-- Historial --}}
                <div class="bv-gd-section">
                    <div class="bv-gd-section__h"><span class="bv-gd-section__t">Historial de acciones</span></div>
                    <div id="dmHistory" style="font-size:11px;line-height:1.7"></div>
                </div>

            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-dm-save" disabled>Guardar cambios</button>
            <button class="btn-secondary" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';

    var _dmDocId = null;

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    function escHtml(s) {
        return $('<span>').text(s || '').html();
    }

    function loadDoc(docId) {
        _dmDocId = docId;
        $('#dmContent').hide();
        $('#dmLoading').show();
        $('#bv-dm-save').prop('disabled', true);

        $.ajax({
            url: '/panel/helpdesk/documents/' + docId,
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (resp) {
            var d = resp.data || resp;
            $('#dmTitle').text((d.order_ref ? 'Orden ' + d.order_ref + ' · ' : '') + (d.name || 'Documento'));
            $('#dmStatusLabel').text(d.status_label || d.status || '—');
            $('#dmStatusDot').removeClass('approved pending rejected').addClass(d.status || 'pending');
            $('#dmClientName').val(d.client_name || '');
            $('#dmClientLastName').val(d.client_last_name || '');
            $('#dmClientId').val(d.client_id_number || '');
            $('#dmClientEmail').val(d.client_email || '');
            $('#dmNotes').val(d.notes || '');

            var hist = (d.history || []).map(function (h) {
                return '<div><span style="color:var(--bv-text-muted)">' + escHtml(h.date) + '</span> · ' + escHtml(h.action) + ' por <b>' + escHtml(h.actor) + '</b></div>';
            }).join('');
            $('#dmHistory').html(hist || '<span style="color:var(--bv-text-muted)">Sin historial</span>');

            $('#dmLoading').hide();
            $('#dmContent').show();
            $('#bv-dm-save').prop('disabled', false);
        }).fail(function () {
            $('#dmLoading').html('<i class="fas fa-triangle-exclamation"></i>');
        });
    }

    $(document).on('bv:modal:open', function (e, name, data) {
        if (name !== 'doc-manage') { return; }
        var docId = (data && data.docId) || null;
        if (!docId) { return; }
        loadDoc(docId);
    });

    function changeStatus(status) {
        if (!_dmDocId) { return; }
        $.ajax({
            url: '/panel/helpdesk/documents/' + _dmDocId + '/status',
            method: 'PATCH',
            data: { status: status },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            if (window.toastr) { toastr.success('Estado actualizado'); }
            loadDoc(_dmDocId);
            $(document).trigger('bv:document:status:changed', [_dmDocId, status]);
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al cambiar estado';
            if (window.toastr) { toastr.error(msg); }
        });
    }

    $(document).on('click', '#bv-dm-approve', function () { changeStatus('approved'); });
    $(document).on('click', '#bv-dm-reject',  function () { changeStatus('rejected'); });

    $(document).on('click', '.bv-dm-send-action', function () {
        if (!_dmDocId) { return; }
        var action = $(this).data('action');
        var $btn   = $(this).prop('disabled', true).text('Enviando…');
        $.ajax({
            url: '/panel/helpdesk/documents/' + _dmDocId + '/send',
            method: 'POST',
            data: { action: action },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            if (window.toastr) { toastr.success('Comunicación enviada'); }
        }).fail(function () {
            if (window.toastr) { toastr.error('Error al enviar'); }
        }).always(function () {
            $btn.prop('disabled', false).text('Enviar');
        });
    });

    $(document).on('click', '#bv-dm-save', function () {
        if (!_dmDocId) { return; }
        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando…');
        $.ajax({
            url: '/panel/helpdesk/documents/' + _dmDocId,
            method: 'PATCH',
            data: {
                client_name:        $('#dmClientName').val().trim(),
                client_last_name:   $('#dmClientLastName').val().trim(),
                client_id_number:   $('#dmClientId').val().trim(),
                client_email:       $('#dmClientEmail').val().trim(),
                notes:              $('#dmNotes').val().trim(),
            },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('doc-manage');
            if (window.toastr) { toastr.success('Cambios guardados'); }
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al guardar';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Guardar cambios');
        });
    });

}(window.jQuery));
</script>
@endpush
@endonce
