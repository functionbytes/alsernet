{{-- Modal: Visualizar email enviado (#28 ve-email-viewer) --}}
<div class="modal fade" id="emailViewerModal" tabindex="-1" aria-labelledby="evTitleText" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="max-height:90vh">

            @include('helpdesk::helpdesk.inbox.partials.modals._modal-header', [
                'icon'      => 'far fa-envelope-open',
                'label'     => 'EMAILS',
                'labelSub'  => 'ENVIADOS',
                'titleId'   => 'evTitleText',
                'titleText' => 'Visualizar email',
                'chipId'    => 'evIdChip',
            ])

            {{-- Two-column viewer --}}
            <div class="d-flex" id="evContainer" style="overflow:hidden;min-height:460px;max-height:calc(90vh - 140px)">

                {{-- Loading --}}
                <div class="d-flex align-items-center justify-content-center w-100 text-muted" id="evLoading">
                    <i class="fas fa-spinner fa-spin me-2"></i> Cargando…
                </div>

                {{-- LEFT: Vista previa HTML --}}
                <div class="d-none flex-column border-end" id="evPreview" style="flex:1;overflow:hidden">
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom bg-light flex-shrink-0">
                        <span class="small fw-semibold">Vista previa</span>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary active bv-ev-dt-btn"
                                    data-ev-device="desktop" title="Desktop">
                                <i class="far fa-window-maximize"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary bv-ev-dt-btn"
                                    data-ev-device="mobile" title="Móvil">
                                <i class="fas fa-mobile-screen"></i>
                            </button>
                        </div>
                    </div>
                    <div class="flex-grow-1" style="overflow:auto">
                        <iframe id="evIframe" sandbox="allow-same-origin"
                                style="width:100%;height:100%;border:none;min-height:400px"></iframe>
                    </div>
                </div>

                {{-- RIGHT: Detalle --}}
                <div class="d-none flex-column" id="evDetail" style="width:288px;flex-shrink:0;overflow-y:auto">

                    <div class="p-3 border-bottom">
                        <div class="fw-semibold mb-1">Detalle del email</div>
                        <div class="text-muted small mb-3">Información del correo enviado</div>
                        <div class="d-flex flex-column gap-3">
                            <div>
                                <div class="text-muted small mb-1">Asunto del email</div>
                                <div class="small" id="evSubject">—</div>
                            </div>
                            <div>
                                <div class="text-muted small mb-1">Destinatario</div>
                                <div class="small font-monospace" id="evTo">—</div>
                            </div>
                            <div class="d-none" id="evCcRow">
                                <div class="text-muted small mb-1">CC</div>
                                <div class="small font-monospace" id="evCc">—</div>
                            </div>
                            <div class="d-none" id="evTypeRow">
                                <div class="text-muted small mb-1">Tipo de email</div>
                                <div><span class="badge bg-info-subtle text-info" id="evType">—</span></div>
                            </div>
                            <div>
                                <div class="text-muted small mb-1">Estado</div>
                                <div><span class="bv-ev-status" id="evStatus">—</span></div>
                            </div>
                            <div class="d-none" id="evSentByRow">
                                <div class="text-muted small mb-1">Enviado por</div>
                                <div class="small" id="evSentBy">—</div>
                            </div>
                            <div>
                                <div class="text-muted small mb-1">Fecha de envío</div>
                                <div class="small font-monospace" id="evSentAt">—</div>
                            </div>
                            <div class="d-none" id="evTemplateRow">
                                <div class="text-muted small mb-1">Plantilla usada</div>
                                <div class="small" id="evTemplate">—</div>
                            </div>
                            <div class="d-none" id="evErrorRow">
                                <div class="text-muted small mb-1">Error</div>
                                <div class="small text-danger" id="evError">—</div>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 border-bottom">
                        <div class="fw-semibold mb-1">Acciones rápidas</div>
                        <div class="text-muted small mb-3">Opciones disponibles</div>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-primary" id="evBtnPrint">Imprimir</button>
                            <button type="button" class="btn btn-primary" id="evBtnBack">Volver a emails</button>
                            <button type="button" class="btn btn-outline-secondary d-none" id="evBtnDoc">Gestionar documento</button>
                        </div>
                    </div>

                    {{-- Documento relacionado --}}
                    <div class="p-3 d-none" id="evDocSection">
                        <div class="fw-semibold mb-1">Documento relacionado</div>
                        <div class="text-muted small mb-3">Información del documento</div>
                        <div class="d-flex flex-column gap-3">
                            <div class="d-none" id="evOrderRow">
                                <div class="text-muted small mb-1">Orden ID</div>
                                <div class="small font-monospace" id="evOrder">—</div>
                            </div>
                            <div class="d-none" id="evCustomerRow">
                                <div class="text-muted small mb-1">Cliente</div>
                                <div class="small" id="evCustomer">—</div>
                            </div>
                            <div class="d-none" id="evDocStatusRow">
                                <div class="text-muted small mb-1">Estado documento</div>
                                <div><span class="bv-ev-status" id="evDocStatus">—</span></div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer justify-content-start bg-light border-top py-2 d-none" id="evBottomNote">
                <small class="text-muted">
                    <i class="fas fa-circle-info me-1"></i>
                    Este es el contenido exacto del email que fue enviado al cliente.
                </small>
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
                var _html = e.body_html.indexOf('</head>') !== -1
                    ? e.body_html.replace('</head>', _style + '</head>')
                    : _style + e.body_html;
                $('#evIframe').prop('srcdoc', _html);
                $('#evPreview').removeClass('d-none').css('display', 'flex');
            } else {
                $('#evPreview').addClass('d-none');
            }

            $('#evDetail').removeClass('d-none').css('display', 'flex');
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
        $('.bv-ev-dt-btn').removeClass('active');
        $(this).addClass('active');
        var $iframe = $('#evIframe');
        if (device === 'mobile') {
            $iframe.css({ width: '375px', margin: '0 auto', display: 'block' });
        } else {
            $iframe.css({ width: '100%', margin: '', display: '' });
        }
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
