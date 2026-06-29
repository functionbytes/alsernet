{{-- Modal: Visualizar email enviado (#28 ve-email-viewer) --}}
<div class="modal fade" id="emailViewerModal" tabindex="-1" aria-labelledby="evTitleText" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bv-ev-content">

            @include('helpdesk::helpdesk.inbox.partials.modals._modal-header', [
                'icon'      => 'far fa-envelope-open',
                'label'     => 'EMAILS',
                'labelSub'  => 'ENVIADOS',
                'titleId'   => 'evTitleText',
                'titleText' => 'Visualizar email',
                'chipId'    => 'evIdChip',
            ])

            {{-- Two-column viewer --}}
            <div class="bv-email-viewer" id="evContainer">

                {{-- Loading --}}
                <div class="bv-ev-loading" id="evLoading">
                    <i class="fas fa-spinner fa-spin"></i> Cargando…
                </div>

                {{-- LEFT: Vista previa HTML --}}
                <div class="bv-ev-preview d-none" id="evPreview">
                    <div class="bv-ev-preview-head">
                        <span>Vista previa</span>
                        <div class="bv-ev-device-toggle">
                            <button type="button" class="on bv-ev-dt-btn" data-ev-device="desktop" title="Desktop">
                                <i class="far fa-window-maximize"></i>
                            </button>
                            <button type="button" class="bv-ev-dt-btn" data-ev-device="mobile" title="Móvil">
                                <i class="fas fa-mobile-screen"></i>
                            </button>
                        </div>
                    </div>
                    <div class="bv-ev-preview-body">
                        <iframe id="evIframe" class="bv-ev-iframe" sandbox="allow-same-origin" title="Vista previa del email"></iframe>
                    </div>
                </div>

                {{-- RIGHT: Detalle --}}
                <div class="bv-ev-detail d-none" id="evDetail">

                    {{-- Detalle del email --}}
                    <div class="bv-ev-section">
                        <div class="bv-ev-hdr">
                            <span class="t">Detalle del email</span>
                            <span class="s">Información del correo enviado</span>
                        </div>
                        <div class="bv-ev-field">
                            <span class="k">Asunto del email</span>
                            <span class="v" id="evSubject">—</span>
                        </div>
                        <div class="bv-ev-field">
                            <span class="k">Destinatario</span>
                            <span class="v mono" id="evTo">—</span>
                        </div>
                        <div class="bv-ev-field d-none" id="evCcRow">
                            <span class="k">CC</span>
                            <span class="v mono" id="evCc">—</span>
                        </div>
                        <div class="bv-ev-field d-none" id="evTypeRow">
                            <span class="k">Tipo de email</span>
                            <span class="v"><span class="bv-ev-tag" id="evType">—</span></span>
                        </div>
                        <div class="bv-ev-field">
                            <span class="k">Estado</span>
                            <span class="v"><span class="bv-ev-status" id="evStatus">—</span></span>
                        </div>
                        <div class="bv-ev-field d-none" id="evSentByRow">
                            <span class="k">Enviado por</span>
                            <span class="v" id="evSentBy">—</span>
                        </div>
                        <div class="bv-ev-field">
                            <span class="k">Fecha de envío</span>
                            <span class="v mono" id="evSentAt">—</span>
                        </div>
                        <div class="bv-ev-field d-none" id="evTemplateRow">
                            <span class="k">Plantilla usada</span>
                            <span class="v" id="evTemplate">—</span>
                        </div>
                        <div class="bv-ev-field d-none" id="evErrorRow">
                            <span class="k">Error</span>
                            <span class="v text-danger" id="evError">—</span>
                        </div>
                    </div>

                    {{-- Acciones rápidas --}}
                    <div class="bv-ev-section">
                        <div class="bv-ev-hdr">
                            <span class="t">Acciones rápidas</span>
                            <span class="s">Opciones disponibles</span>
                        </div>
                        <div class="bv-ev-actions">
                            <button type="button" class="btn btn-primary" id="evBtnPrint">Imprimir</button>
                            <button type="button" class="btn btn-primary" id="evBtnBack">Volver a emails</button>
                            <button type="button" class="btn btn-outline-secondary d-none" id="evBtnDoc">Gestionar documento</button>
                        </div>
                    </div>

                    {{-- Documento relacionado --}}
                    <div class="bv-ev-section d-none" id="evDocSection">
                        <div class="bv-ev-hdr">
                            <span class="t">Documento relacionado</span>
                            <span class="s">Información del documento</span>
                        </div>
                        <div class="bv-ev-field d-none" id="evOrderRow">
                            <span class="k">Orden ID</span>
                            <span class="v mono" id="evOrder">—</span>
                        </div>
                        <div class="bv-ev-field d-none" id="evCustomerRow">
                            <span class="k">Cliente</span>
                            <span class="v" id="evCustomer">—</span>
                        </div>
                        <div class="bv-ev-field d-none" id="evDocStatusRow">
                            <span class="k">Estado documento</span>
                            <span class="v"><span class="bv-ev-status" id="evDocStatus">—</span></span>
                        </div>
                    </div>

                </div>
            </div>

            <div class="bv-ev-bottom-note d-none" id="evBottomNote">
                <i class="fas fa-circle-info"></i>
                Este es el contenido exacto del email que fue enviado al cliente.
            </div>

        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    var $modal = $('#emailViewerModal');
    var _bsModal = null;

    function getModal() {
        if (!_bsModal) { _bsModal = new bootstrap.Modal($modal.get(0)); }
        return _bsModal;
    }

    function evSetText(id, text) { $(id).text(text || '—'); }

    function evRowToggle(rowId, show) {
        if (show) { $(rowId).removeClass('d-none'); }
        else { $(rowId).addClass('d-none'); }
    }

    window.loadEmailViewer = function (uid) {
        var convId = $('.bv-composer').data('bv-conversation-id');
        if (!convId || !uid) { return; }

        $('#evLoading').removeClass('d-none').html('<i class="fas fa-spinner fa-spin me-2"></i> Cargando…');
        $('#evPreview, #evDetail, #evBottomNote').addClass('d-none');

        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/emails/' + uid,
            method: 'GET',
            dataType: 'json',
            headers: { 'Accept': 'application/json' },
        }).done(function (e) {
            $('#evTitleText').text(e.subject || 'Visualizar email');
            if (e.id_label) {
                $('#evIdChip').text(e.id_label).removeClass('d-none');
            } else {
                $('#evIdChip').addClass('d-none');
            }

            evSetText('#evSubject', e.subject);
            evSetText('#evTo', Array.isArray(e.to) ? e.to.join(', ') : e.to);

            if (e.cc && e.cc.length) {
                $('#evCc').text(e.cc.join(', '));
                evRowToggle('#evCcRow', true);
            } else {
                evRowToggle('#evCcRow', false);
            }

            if (e.type_label) {
                $('#evType').text(e.type_label);
                evRowToggle('#evTypeRow', true);
            } else {
                evRowToggle('#evTypeRow', false);
            }

            var statusClass = e.status === 'sent' ? 'sent' : (e.status === 'failed' ? 'failed' : 'queued');
            var statusIco = e.status === 'sent'
                ? '<i class="fas fa-check"></i> '
                : (e.status === 'failed' ? '<i class="fas fa-xmark"></i> ' : '<i class="far fa-clock"></i> ');
            $('#evStatus').attr('class', 'bv-ev-status ' + statusClass).html(statusIco + (e.status_label || e.status || '—'));

            if (e.sent_by) {
                $('#evSentBy').text(e.sent_by);
                evRowToggle('#evSentByRow', true);
            } else {
                evRowToggle('#evSentByRow', false);
            }

            var sentText = e.sent_at_formatted || e.sent_at || '—';
            var sentHtml = $('<span>').text(sentText).html();
            if (e.sent_at && e.sent_at_human) {
                sentHtml += ' <span class="text-muted">· ' + $('<span>').text(e.sent_at_human).html() + '</span>';
            }
            $('#evSentAt').html(sentHtml);

            if (e.template_name) {
                $('#evTemplate').text(e.template_name);
                evRowToggle('#evTemplateRow', true);
            } else {
                evRowToggle('#evTemplateRow', false);
            }

            if (e.error_message) {
                $('#evError').text(e.error_message);
                evRowToggle('#evErrorRow', true);
            } else {
                evRowToggle('#evErrorRow', false);
            }

            var hasDoc = false;
            if (e.related_order_id) {
                $('#evOrder').text('#' + e.related_order_id);
                evRowToggle('#evOrderRow', true);
                hasDoc = true;
            } else { evRowToggle('#evOrderRow', false); }

            if (e.related_customer_name) {
                $('#evCustomer').text(e.related_customer_name);
                evRowToggle('#evCustomerRow', true);
                hasDoc = true;
            } else { evRowToggle('#evCustomerRow', false); }

            if (e.related_document_status) {
                var docStatusClass = e.related_document_status_code === 'approved' ? 'sent'
                    : (e.related_document_status_code === 'rejected' ? 'failed' : 'queued');
                var docStatusIco = e.related_document_status_code === 'approved'
                    ? '<i class="fas fa-check"></i> '
                    : (e.related_document_status_code === 'rejected' ? '<i class="fas fa-xmark"></i> ' : '');
                $('#evDocStatus').attr('class', 'bv-ev-status ' + docStatusClass)
                    .html(docStatusIco + e.related_document_status);
                evRowToggle('#evDocStatusRow', true);
                hasDoc = true;
            } else { evRowToggle('#evDocStatusRow', false); }

            evRowToggle('#evDocSection', hasDoc);
            evRowToggle('#evBtnDoc', hasDoc);

            if (e.body_html) {
                var _style = '<style>body{padding:20px!important;box-sizing:border-box}</style>';
                var _closingHead = '</' + 'head>';
                var _html = e.body_html.indexOf(_closingHead) !== -1
                    ? e.body_html.replace(_closingHead, _style + _closingHead)
                    : _style + e.body_html;
                $('#evIframe').prop('srcdoc', _html);
                $('#evPreview').removeClass('d-none');
            } else {
                $('#evPreview').addClass('d-none');
            }

            $('#evDetail').removeClass('d-none');
            evRowToggle('#evBottomNote', true);
            $('#evLoading').addClass('d-none');
        }).fail(function () {
            $('#evLoading').removeClass('d-none').html(
                '<i class="fas fa-triangle-exclamation text-muted me-2"></i> No se pudo cargar el email.'
            );
        });
    };

    // Device toggle (iconos se mantienen porque el botón ES el icono)
    $(document).on('click', '.bv-ev-dt-btn', function () {
        var device = $(this).data('ev-device');
        $('.bv-ev-dt-btn').removeClass('on');
        $(this).addClass('on');
        $('#evIframe').toggleClass('mobile', device === 'mobile');
    });

    $('#evBtnPrint').on('click', function () {
        var $iframe = $('#evIframe');
        if ($iframe.length && $iframe.get(0).contentWindow) {
            $iframe.get(0).contentWindow.print();
        }
    });

    $('#evBtnBack').on('click', function () {
        getModal().hide();
    });

    $modal.on('hidden.bs.modal', function () {
        window._emViewerUid = null;
    });

    // API global para abrir el modal
    window.openEmailViewer = function (uid) {
        window._emViewerUid = uid;
        getModal().show();
        window.loadEmailViewer(uid);
    };
}());
</script>
@endpush
@endonce
