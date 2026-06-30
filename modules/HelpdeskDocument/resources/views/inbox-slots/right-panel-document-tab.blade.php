{{-- Tab "Documentacion" del right-panel del inbox — LISTA de expedientes del cliente.
     Propietario: HelpdeskDocument · Renderizado por Helpdesk via slot.
     Espera $rpDocuments (array de items ligeros, DocumentPanelPresenter::list) y
     $rpConvo (Conversation). El detalle de cada expediente se carga via AJAX en el
     host (#docs-detail-host) desde la ruta manager.helpdesk.conversations.documents.panel. --}}
<div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="document">
    @include('helpdeskdocument::modals._assets')

    @php
        $docList   = $rpDocuments ?? [];
        $docCount  = count($docList);

        $statusGroup = function (string $key): string {
            return match ($key) {
                'approved', 'completed'  => 'approved',
                'rejected', 'cancelled'  => 'rejected',
                default                  => 'pending',
            };
        };

        $grouped   = collect($docList)->groupBy(fn ($d) => $statusGroup($d['status_key'] ?? 'pending'));
        $pendCount = $grouped->get('pending', collect())->count();
        $apprCount = $grouped->get('approved', collect())->count();
        $rejCount  = $grouped->get('rejected', collect())->count();
    @endphp

    {{-- Backdrop + modal persistente del detalle de expediente --}}
    <div class="docs-backdrop" data-docs-modal-backdrop-for="docsDetailPanel"></div>
    <div class="docs-modal docs-modal-xl" id="docsDetailPanel" data-docs-persistent>
        <div class="docs-head">
            <div class="docs-head-icon"><i class="fa-regular fa-folder-open"></i></div>
            <div class="docs-head-text">
                <div class="docs-head-label" id="docsDetailPanelLabel">Dashboard · Documentos</div>
                <div class="docs-head-title" id="docsDetailPanelTitle">
                    Expediente
                    <span class="docs-breadcrumb" id="docsDetailPanelCrumb" style="display:none">
                        Dashboard · Documentos <i class="fa-solid fa-chevron-right"></i>
                        <span id="docsDetailPanelCrumbText"></span>
                    </span>
                </div>
            </div>
            <button type="button" class="docs-close" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="docs-body pad-0" id="docsDetailPanelBody">
            {{-- Relleno via AJAX al abrir --}}
        </div>
        <div class="docs-foot" style="flex-direction:row;gap:8px;padding:10px 14px">
            <button type="button" class="btn btn-sm btn-outline-secondary docs-chat-import-btn"
                    onclick="window.openDocFromChat && window.openDocFromChat()"
                    title="Cargar desde galería del chat">
                <i class="fas fa-images me-1"></i> Desde chat
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-docs-download-zip
                    title="Descargar todos los documentos como ZIP">
                <i class="fas fa-file-zipper me-1"></i> ZIP
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary docs-close ms-auto">
                <i class="fas fa-xmark me-1"></i> Cerrar
            </button>
        </div>
    </div>

    {{-- Vista lista de expedientes --}}
    <div class="docs-list-view" data-docs-list-view>
        @if($docCount === 0)
            <div class="bv-tab-empty">
                <i class="far fa-folder-open"></i>
                <div class="bv-tab-empty-title">Sin documentación</div>
                <div class="bv-tab-empty-sub">Este cliente no tiene expedientes registrados</div>
            </div>
        @else
            <div class="tk-panel-head">
                <span class="num">{{ $docCount }}</span>
                <div class="meta">
                    <span class="lbl">Documentos</span>
                    <span class="sub">
                        {{ $pendCount }} pendiente{{ $pendCount === 1 ? '' : 's' }}
                        @if($apprCount > 0) · {{ $apprCount }} aprobado{{ $apprCount === 1 ? '' : 's' }} @endif
                    </span>
                </div>
            </div>

            <div class="tk-search-row">
                <div class="tk-search-wrap">
                    <i class="fa-solid fa-magnifying-glass tk-search-icon"></i>
                    <input type="text" class="tk-search-input docs-list-search"
                           placeholder="Buscar por ref. o tipo…">
                </div>
            </div>

            <div class="tk-filter-row">
                <button type="button" class="media-pill docs-list-filter on" data-filter="all">
                    Todos <span class="c">{{ $docCount }}</span>
                </button>
                @if($pendCount > 0)
                    <button type="button" class="media-pill docs-list-filter" data-filter="pending">
                        Pendientes <span class="c">{{ $pendCount }}</span>
                    </button>
                @endif
                @if($apprCount > 0)
                    <button type="button" class="media-pill docs-list-filter" data-filter="approved">
                        Aprobados <span class="c">{{ $apprCount }}</span>
                    </button>
                @endif
                @if($rejCount > 0)
                    <button type="button" class="media-pill docs-list-filter" data-filter="rejected">
                        Rechazados <span class="c">{{ $rejCount }}</span>
                    </button>
                @endif
            </div>

            <div class="sort-row">
                <span class="sort-lbl">Ordenar por</span>
                <div class="sort-dd">
                    <button type="button" class="sort-trigger docs-sort-trigger">
                        <i class="fa-solid fa-arrow-down-short-wide lead"></i>
                        <span class="docs-sort-label">Más reciente</span>
                        <i class="fa-solid fa-chevron-down chev"></i>
                    </button>
                </div>
                <div class="sort-backdrop docs-sort-backdrop"></div>
                <div class="sort-menu docs-sort-menu">
                    <button type="button" class="sort-item sel" data-sort="date-desc">
                        <span class="ico"><i class="fa-solid fa-clock-rotate-left"></i></span>
                        <span class="sort-item-lbl">Más reciente</span>
                        <span class="ck"><i class="fa-solid fa-check"></i></span>
                    </button>
                    <button type="button" class="sort-item" data-sort="date-asc">
                        <span class="ico"><i class="fa-solid fa-clock"></i></span>
                        <span class="sort-item-lbl">Más antiguo</span>
                        <span class="ck"></span>
                    </button>
                    <button type="button" class="sort-item" data-sort="name">
                        <span class="ico"><i class="fa-solid fa-font"></i></span>
                        <span class="sort-item-lbl">Por tipo</span>
                        <span class="ck"></span>
                    </button>
                    <button type="button" class="sort-item" data-sort="status">
                        <span class="ico"><i class="fa-solid fa-tag"></i></span>
                        <span class="sort-item-lbl">Por estado</span>
                        <span class="ck"></span>
                    </button>
                </div>
            </div>

            <div class="tk-list" data-docs-list>
                @foreach($docList as $d)
                    @php
                        $grp = $statusGroup($d['status_key'] ?? 'pending');
                        $panelUrl = $rpConvo
                            ? route('manager.helpdesk.conversations.documents.panel', [$rpConvo->id, $d['id']])
                            : '#';
                    @endphp
                    @php
                        $fileUploaded = $d['file_uploaded'] ?? $d['file_count'] ?? 0;
                        $fileTotal    = $d['file_total']    ?? $d['file_count'] ?? 0;
                        $progressPct  = $d['progress_pct'] ?? ($fileTotal > 0 ? (int) round(($fileUploaded / max($fileTotal, 1)) * 100) : 0);
                        $progressAccent = $d['progress_accent'] ?? ($grp === 'approved' ? '#90bb13' : ($grp === 'rejected' ? '#FA896B' : '#FEC90F'));
                        $agoHuman     = $d['ago_human'] ?? $d['created_human'] ?? '—';
                    @endphp
                    <button type="button" class="tk-card docs-list-card"
                            style="border-left:1px solid var(--bv-border,#e4e4e7); gap:7px; padding:11px 13px"
                            data-docs-open="{{ $panelUrl }}"
                            data-doc-tags="all {{ $grp }}"
                            data-doc-status="{{ $grp }}"
                            data-doc-name="{{ strtolower($d['type_label']) }}"
                            data-doc-ref="{{ strtolower($d['order_reference']) }}"
                            data-doc-date="{{ $d['created_human'] ?? '' }}">
                        <div class="docs-card-header">
                            <div class="docs-card-info">
                                <span class="title">{{ $d['customer_name'] ?: $d['type_label'] }}</span>
                                <span class="id">#{{ $d['order_reference'] }} · {{ $d['type_label'] }}</span>
                            </div>
                            <span class="docs-status {{ $grp }}">{{ $d['status_label'] }}</span>
                        </div>
                        @php $desc = !empty($d['description']) ? __($d['description']) : ''; @endphp
                        @if($desc && $desc !== $d['description'])
                            <div class="docs-card-desc">{{ $desc }}</div>
                        @endif
                        @if($fileTotal > 0)
                            <div class="docs-card-prog">
                                <div class="docs-card-prog-track">
                                    <div class="docs-card-prog-fill" style="width:{{ $progressPct }}%; background:{{ $progressAccent }}"></div>
                                </div>
                                <span class="docs-card-docs-lbl">{{ $fileTotal }} docs</span>
                            </div>
                        @endif
                        <div class="foot">
                            <span class="seg">
                                <i class="fa-regular fa-file"></i>
                                @if($fileTotal > 0)
                                    {{ $fileUploaded }}/{{ $fileTotal }}
                                @else
                                    {{ $fileUploaded }} archivo{{ $fileUploaded !== 1 ? 's' : '' }}
                                @endif
                            </span>
                            <span class="seg docs-card-time">
                                <i class="fa-regular fa-clock"></i> {{ $agoHuman }}
                            </span>
                        </div>
                    </button>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- Modal: cargar adjuntos del chat como documento (HelpdeskDocument) --}}
@include('helpdeskdocument::modals.doc-from-chat')

@once
@push('scripts')
<script>
(function ($) {
    'use strict';
    if (!window.jQuery) { return; }

    function csrf() { return $('meta[name="csrf-token"]').attr('content'); }
    function notify(type, msg) { if (window.toastr) { toastr[type](msg); } else { alert(msg); } }

    function busy($btn, on, label) {
        if (on) {
            $btn.data('orig', $btn.html()).prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin me-1"></i>' + (label || 'Procesando…'));
        } else {
            $btn.prop('disabled', false).html($btn.data('orig') || $btn.html());
        }
    }

    function rootOf(el) {
        return $(el).closest('.docs-rp');
    }

    function tabOf(el) {
        return $(el).closest('[data-bv-tab-content]');
    }

    function reloadPanel($root) {
        var panelUrl = $root.data('url-panel');
        if (!panelUrl) { return; }
        var $body = $('#docsDetailPanelBody');
        $body.html('<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin me-1"></i> Actualizando expediente…</div>');
        $.ajax({
            url: panelUrl, method: 'GET',
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'text/html' }
        }).done(function (html) {
            $body.html(html);
        }).fail(function () {
            notify('error', 'Error al recargar el expediente.');
        });
    }

    function twoClickConfirm($btn, label, onConfirm) {
        if ($btn.data('tcc')) {
            clearTimeout($btn.data('tcc-t'));
            $btn.removeData('tcc tcc-t');
            $btn.html($btn.data('tcc-orig')).removeClass('btn-danger');
            onConfirm.call($btn[0]);
            return;
        }
        $btn.data('tcc', true).data('tcc-orig', $btn.html());
        $btn.html('<i class="fas fa-exclamation me-1"></i>' + label).addClass('btn-danger');
        $btn.data('tcc-t', setTimeout(function () {
            if ($btn.data('tcc')) {
                $btn.removeData('tcc tcc-t');
                $btn.html($btn.data('tcc-orig')).removeClass('btn-danger');
            }
        }, 3000));
    }

    function doPost(url, data, $btn, loadingLabel, successMsg, reload) {
        busy($btn, true, loadingLabel);
        $.ajax({
            url: url, method: 'POST', data: data,
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function (res) {
            notify('success', (res && res.message) || successMsg);
            if (reload) {
                setTimeout(function () { window.location.reload(); }, 700);
            } else {
                busy($btn, false);
            }
        }).fail(function (xhr) {
            busy($btn, false);
            var err = '';
            if (xhr.responseJSON) {
                if (xhr.responseJSON.errors) {
                    err = Object.values(xhr.responseJSON.errors).flat().join(' ');
                } else {
                    err = xhr.responseJSON.message || '';
                }
            }
            notify('error', err || 'Error al procesar la solicitud.');
        });
    }

    // ══ Lista de expedientes: abrir detalle en modal persistente ══════
    $(document).on('click', '.docs-list-card[data-docs-open]', function () {
        var url   = $(this).data('docs-open');
        var $body = $('#docsDetailPanelBody');

        $body.html('<div class="text-center text-muted py-4">' +
                   '<i class="fas fa-spinner fa-spin me-1"></i> Cargando expediente…</div>');
        window.DocsModal.open('docsDetailPanel');

        $.ajax({
            url: url, method: 'GET',
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'text/html' }
        }).done(function (html) {
            $body.html(html);
            // Actualizar header del panel con título real del expediente
            var $rpHead = $body.find('.docs-rp-head-text');
            var ref     = ($rpHead.find('.docs-rp-head-label').text() || '').trim();
            var title   = ($rpHead.find('.docs-rp-head-title').text() || '').trim();
            if (title) {
                $('#docsDetailPanelLabel').text(ref || 'Dashboard · Documentos');
                $('#docsDetailPanelTitle').contents().first()[0].nodeValue = title + ' ';
                $('#docsDetailPanelCrumbText').text(title);
                $('#docsDetailPanelCrumb').show();
            }
        }).fail(function () {
            notify('error', 'No se pudo cargar el expediente.');
            window.DocsModal.close('docsDetailPanel');
            $body.empty();
        });
    });

    // ══ Filtros de la lista por estado ══════════════════════════════
    $(document).on('click', '.docs-list-filter', function () {
        var $pill = $(this);
        var filter = $pill.data('filter');
        $pill.siblings('.docs-list-filter').removeClass('on');
        $pill.addClass('on');

        $pill.closest('.docs-list-view').find('.docs-list-card').each(function () {
            var tags = ' ' + ($(this).data('doc-tags') || '') + ' ';
            $(this).toggle(filter === 'all' || tags.indexOf(' ' + filter + ' ') !== -1);
        });
    });

    // ── Subir documentacion ─────────────────────────────────────
    $(document).on('click', '.docs-rp .docs-up-submit', function () {
        var $root = rootOf(this);
        var fd = new FormData();
        var has = false;
        $root.find('.docs-upload-form input[type=file]').each(function () {
            if (this.files && this.files.length) { fd.append(this.name, this.files[0]); has = true; }
        });
        if (!has) { notify('warning', 'Selecciona al menos un archivo.'); return; }
        var $btn = $(this);
        busy($btn, true, 'Subiendo…');
        $.ajax({
            url: $root.data('url-upload'), method: 'POST', data: fd,
            processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function () {
            notify('success', 'Documentación subida correctamente.');
            reloadPanel($root);
        }).fail(function (xhr) {
            busy($btn, false);
            notify('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Error al subir los archivos.');
        });
    });

    // ── Asignar validador ───────────────────────────────────────
    $(document).on('click', '.docs-rp .docs-assign', function () {
        var $root = rootOf(this);
        var userId = $root.find('.docs-assignee').val() || '';
        doPost($root.data('url-assign'), { assigned_user_id: userId }, $(this), 'Asignando…', 'Documento asignado.', false);
    });

    // ── Aprobar etapa (panel principal) ────────────────────────
    $(document).on('click', '.docs-rp .docs-approve', function () {
        var $root = rootOf(this);
        var $btn  = $(this);
        busy($btn, true, 'Aprobando…');
        $.ajax({
            url: $root.data('url-approve'), method: 'POST',
            data: { assigned_user_id: $root.find('.docs-assignee').val() || '' },
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function (res) {
            notify('success', (res && res.message) || 'Etapa aprobada.');
            reloadPanel($root);
        }).fail(function (xhr) {
            busy($btn, false);
            notify('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Error al aprobar.');
        });
    });

    // ── Confirmar rechazo desde modal ──────────────────────────
    $(document).on('click', '.docs-reject-confirm', function () {
        var $modal = $(this).closest('.docs-modal');
        var $root  = tabOf($modal).find('.docs-rp');
        var reason = $modal.find('.docs-reject-reason').val().trim();
        if (reason.length < 10) { notify('warning', 'El motivo debe tener al menos 10 caracteres.'); return; }
        var $btn = $(this);
        busy($btn, true, 'Rechazando…');
        $.ajax({
            url: $root.data('url-reject'), method: 'POST', data: { reason: reason },
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function (res) {
            notify('success', (res && res.message) || 'Documento rechazado.');
            window.DocsModal.close($modal.attr('id'));
            reloadPanel($root);
        }).fail(function (xhr) {
            busy($btn, false);
            notify('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Error al rechazar.');
        });
    });

    // ── doc-manage: Aprobar ─────────────────────────────────────
    $(document).on('click', '[data-docs-approve]', function () {
        var $modal = $(this).closest('.docs-modal');
        var $root  = tabOf($modal).find('.docs-rp');
        var $btn   = $(this);
        busy($btn, true, 'Aprobando…');
        $.ajax({
            url: $root.data('url-approve'), method: 'POST',
            data: { assigned_user_id: $root.find('.docs-assignee').val() || '' },
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function (res) {
            notify('success', (res && res.message) || 'Etapa aprobada.');
            window.DocsModal.close($modal.attr('id'));
            reloadPanel($root);
        }).fail(function (xhr) {
            busy($btn, false);
            notify('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Error al aprobar.');
        });
    });

    // ── doc-manage: Rechazar → cerrar manage y abrir modal de rechazo
    $(document).on('click', '[data-docs-reject]', function () {
        var manageId  = $(this).closest('.docs-modal').attr('id');
        var rejectId  = manageId.replace('docManage_', 'docsReject_');
        window.DocsModal.close(manageId);
        window.DocsModal.open(rejectId);
    });

    // ── doc-manage: Acciones de envío ─────────────────────────
    $(document).on('click', '[data-docs-action]', function () {
        var action = $(this).data('docs-action');
        var $modal = $(this).closest('.docs-modal');
        var $root  = tabOf($modal).find('.docs-rp');
        var urlMap = {
            initial:   $root.data('url-send-notify'),
            reminder:  $root.data('url-send-reminder'),
            confirmed: $root.data('url-send-upload-confirm'),
            approved:  $root.data('url-send-approval'),
        };
        var url = urlMap[action];
        if (!url) { notify('warning', 'Acción no disponible.'); return; }
        var $btn = $(this);
        busy($btn, true, 'Enviando…');
        $.ajax({
            url: url, method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function (res) {
            notify('success', (res && res.message) || 'Enviado correctamente.');
            busy($btn, false);
        }).fail(function (xhr) {
            busy($btn, false);
            notify('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Error al enviar.');
        });
    });

    // ── doc-manage: Agregar nota ────────────────────────────────
    $(document).on('click', '[data-docs-add-note]', function () {
        var $modal    = $(this).closest('.docs-modal');
        var $root     = tabOf($modal).find('.docs-rp');
        var $textarea = $modal.find('.docs-section .ftextarea');
        var content   = $textarea.val().trim();
        if (content.length < 3) { notify('warning', 'La nota debe tener al menos 3 caracteres.'); return; }
        var $btn = $(this);
        busy($btn, true, 'Guardando…');
        $.ajax({
            url: $root.data('url-notes'), method: 'POST',
            data: { content: content, is_internal: 1 },
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function () {
            notify('success', 'Nota agregada.');
            $textarea.val('');
            busy($btn, false);
        }).fail(function (xhr) {
            busy($btn, false);
            notify('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Error al agregar nota.');
        });
    });

    // ── Solicitar documentacion (send-notification) ────────────
    $(document).on('click', '.docs-rp .docs-comm-notify', function () {
        var $btn = $(this);
        twoClickConfirm($btn, '¿Enviar solicitud?', function () {
            doPost(rootOf($btn).data('url-send-notify'), {}, $btn, 'Enviando…', 'Solicitud enviada.', false);
        });
    });

    // ── Recordatorio ────────────────────────────────────────────
    $(document).on('click', '.docs-rp .docs-comm-reminder', function () {
        var $btn = $(this);
        twoClickConfirm($btn, '¿Enviar recordatorio?', function () {
            doPost(rootOf($btn).data('url-send-reminder'), {}, $btn, 'Enviando…', 'Recordatorio enviado.', false);
        });
    });

    // ── Confirmar recepcion ─────────────────────────────────────
    $(document).on('click', '.docs-rp .docs-comm-upload-confirm', function () {
        var $btn = $(this);
        twoClickConfirm($btn, '¿Confirmar recepción?', function () {
            doPost(rootOf($btn).data('url-send-upload-confirm'), {}, $btn, 'Enviando…', 'Confirmación enviada.', false);
        });
    });

    // ── Solicitar faltantes: enviar desde modal ─────────────────
    $(document).on('click', '.docs-missing-send', function () {
        var $modal = $(this).closest('.docs-modal');
        var $root  = tabOf($modal).find('.docs-rp');
        var docs   = [];
        $modal.find('.docs-missing-cb:checked').each(function () { docs.push($(this).val()); });
        if (!docs.length) { notify('warning', 'Selecciona al menos un documento.'); return; }
        var notes  = $modal.find('.docs-missing-notes').val().trim();
        var $btn   = $(this);
        busy($btn, true, 'Enviando…');
        $.ajax({
            url: $root.data('url-send-missing'), method: 'POST',
            data: { missing_docs: docs, notes: notes },
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function (res) {
            notify('success', (res && res.message) || 'Solicitud enviada.');
            window.DocsModal.close($modal.attr('id'));
            busy($btn, false);
        }).fail(function (xhr) {
            busy($btn, false);
            notify('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Error al enviar.');
        });
    });

    // ── Correo de rechazo: enviar desde modal ──────────────────
    $(document).on('click', '.docs-rej-email-send', function () {
        var $modal  = $(this).closest('.docs-modal');
        var $root   = tabOf($modal).find('.docs-rp');
        var reason  = $modal.find('.docs-rej-email-reason').val().trim();
        if (reason.length < 10) { notify('warning', 'El motivo debe tener al menos 10 caracteres.'); return; }
        var rejDocs = [];
        $modal.find('.docs-rej-doc-cb:checked').each(function () { rejDocs.push($(this).val()); });
        var $btn = $(this);
        busy($btn, true, 'Enviando…');
        $.ajax({
            url: $root.data('url-send-rej-email'), method: 'POST',
            data: { reason: reason, rejected_docs: rejDocs },
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function (res) {
            notify('success', (res && res.message) || 'Correo de rechazo enviado.');
            window.DocsModal.close($modal.attr('id'));
            busy($btn, false);
        }).fail(function (xhr) {
            busy($btn, false);
            notify('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Error al enviar correo.');
        });
    });

    // ── Correo personalizado: enviar desde modal ───────────────
    $(document).on('click', '.docs-custom-email-send', function () {
        var $modal   = $(this).closest('.docs-modal');
        var $root    = tabOf($modal).find('.docs-rp');
        var subject  = $modal.find('.docs-custom-subject').val().trim();
        var message  = $modal.find('.docs-custom-message').val().trim();
        if (!subject) { notify('warning', 'El asunto es obligatorio.'); return; }
        if (message.length < 10) { notify('warning', 'El mensaje es muy corto.'); return; }
        var $btn = $(this);
        busy($btn, true, 'Enviando…');
        $.ajax({
            url: $root.data('url-send-custom'), method: 'POST',
            data: { subject: subject, message: message },
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function (res) {
            notify('success', (res && res.message) || 'Correo enviado correctamente.');
            $modal.find('.docs-custom-subject, .docs-custom-message').val('');
            window.DocsModal.close($modal.attr('id'));
            busy($btn, false);
        }).fail(function (xhr) {
            busy($btn, false);
            notify('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Error al enviar correo.');
        });
    });

    // ── Agregar nota interna ────────────────────────────────────
    $(document).on('click', '.docs-rp .docs-note-submit', function () {
        var $root   = rootOf(this);
        var $input  = $root.find('.docs-note-input');
        var content = $input.val().trim();
        if (content.length < 3) { notify('warning', 'La nota debe tener al menos 3 caracteres.'); return; }
        var $btn = $(this);
        busy($btn, true, 'Guardando…');
        $.ajax({
            url: $root.data('url-notes'), method: 'POST',
            data: { content: content, is_internal: 1 },
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function (res) {
            notify('success', 'Nota agregada.');
            $input.val('');
            var note    = res.note || {};
            var noteId  = note.id || 0;
            var ts      = (note.created_at || '').substring(0, 16).replace('T', ' ') || '—';
            var delUrl  = $root.data('url-notes') + '/' + noteId;
            var $list   = $root.find('.docs-notes-list');
            $list.find('.docs-rp-empty-mini').remove();
            $list.prepend(
                '<div class="docs-note-item">' +
                '<div class="docs-note-header">' +
                '<span class="docs-note-ts">' + $('<s>').text(ts).html() + '</span>' +
                '<button type="button" class="btn-icon docs-note-del" data-url="' + delUrl + '">' +
                '<i class="fas fa-xmark"></i></button>' +
                '</div>' +
                '<div class="docs-note-text">' + $('<s>').text(content).html() + '</div>' +
                '</div>'
            );
            busy($btn, false);
        }).fail(function (xhr) {
            busy($btn, false);
            notify('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Error al agregar nota.');
        });
    });

    // ── Eliminar nota interna ───────────────────────────────────
    $(document).on('click', '.docs-rp .docs-note-del', function () {
        var $btn = $(this);
        if (!$btn.data('tcc')) { twoClickConfirm($btn, '¿Eliminar?', function () { $btn.trigger('click'); }); return; }
        var url = $btn.data('url');
        var $item = $(this).closest('.docs-note-item');
        $.ajax({
            url: url, method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function (res) {
            notify('success', (res && res.message) || 'Nota eliminada.');
            $item.remove();
        }).fail(function (xhr) {
            notify('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Error al eliminar nota.');
        });
    });

    // ── Subir adjunto adicional ─────────────────────────────────
    $(document).on('click', '.docs-rp .docs-attach-submit', function () {
        var $root = rootOf(this);
        var $file = $root.find('.docs-attach-file');
        if (!$file[0].files || !$file[0].files.length) {
            notify('warning', 'Selecciona un archivo.'); return;
        }
        var fd    = new FormData();
        fd.append('file', $file[0].files[0]);
        var notes = $root.find('.docs-attach-notes').val().trim();
        if (notes) { fd.append('notes', notes); }
        var $btn  = $(this);
        busy($btn, true, 'Subiendo…');
        $.ajax({
            url: $root.data('url-upload-attach'), method: 'POST', data: fd,
            processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function (res) {
            notify('success', (res && res.message) || 'Adjunto subido.');
            $file.val('');
            $root.find('.docs-attach-notes').val('');
            var f = res.file || {};
            var delUrl = $root.data('url-delete-attach') + '/' + (f.id || 0);
            var $list  = $root.find('.docs-attach-list');
            $list.find('.docs-rp-empty-mini').remove();
            $list.append(
                '<div class="docs-attach-item">' +
                '<a href="' + (f.url || '#') + '" target="_blank" rel="noopener" class="docs-attach-link">' +
                $('<s>').text(f.name || f.file_name || 'Adjunto').html() + '</a>' +
                '<span class="docs-attach-meta">' + (f.size ? Math.round(f.size / 1024) + ' KB' : '') + '</span>' +
                '<button type="button" class="btn-icon docs-attach-del" data-url="' + delUrl + '">' +
                '<i class="fas fa-xmark"></i></button>' +
                '</div>'
            );
            busy($btn, false);
        }).fail(function (xhr) {
            busy($btn, false);
            notify('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Error al subir adjunto.');
        });
    });

    // ── Eliminar adjunto adicional ──────────────────────────────
    $(document).on('click', '.docs-rp .docs-attach-del', function () {
        var $btn = $(this);
        if (!$btn.data('tcc')) { twoClickConfirm($btn, '¿Eliminar?', function () { $btn.trigger('click'); }); return; }
        var url   = $btn.data('url');
        var $item = $(this).closest('.docs-attach-item');
        $.ajax({
            url: url, method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function (res) {
            notify('success', (res && res.message) || 'Adjunto eliminado.');
            $item.remove();
        }).fail(function (xhr) {
            notify('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Error al eliminar adjunto.');
        });
    });

    // ── doc-manage: Guardar datos del cliente ──────────────────
    $(document).on('click', '[data-docs-save]', function () {
        var $modal = $(this).closest('.docs-modal');
        var $root  = tabOf($modal).find('.docs-rp');
        var uid    = $root.data('doc-uid');
        if (!uid) { notify('warning', 'Sin expediente activo.'); return; }
        var $btn   = $(this);
        var data   = {
            uid: uid,
            data: {
                customer_firstname: $modal.find('[name="firstname"]').val().trim(),
                customer_lastname:  $modal.find('[name="lastname"]').val().trim(),
                customer_dni:       $modal.find('[name="identifier"]').val().trim(),
                customer_email:     $modal.find('[name="email"]').val().trim(),
            }
        };
        busy($btn, true, 'Guardando…');
        $.ajax({
            url: $root.data('url-update'), method: 'POST',
            data: data,
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function (res) {
            notify('success', (res && res.message) || 'Datos guardados correctamente.');
            busy($btn, false);
        }).fail(function (xhr) {
            busy($btn, false);
            notify('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Error al guardar.');
        });
    });

    // ── doc-manage: Ver historial completo ─────────────────────
    $(document).on('click', '[data-docs-history-full]', function () {
        var $modal = $(this).closest('.docs-modal');
        var $root  = tabOf($modal).find('.docs-rp');
        var histUrl = $root.data('url-action-history');
        if (!histUrl) { window.DocsModal.close($modal.attr('id')); return; }
        window.DocsModal.close($modal.attr('id'));
        var $timeline = $('#docsDetailPanelBody').find('.docs-timeline');
        if (!$timeline.length) { return; }
        $timeline.html('<div class="text-center text-muted py-2"><i class="fas fa-spinner fa-spin"></i></div>');
        $.getJSON(histUrl, function (data) {
            var items = Array.isArray(data) ? data : (data.data || []);
            if (!items.length) { $timeline.html('<div class="text-muted small">Sin historial registrado.</div>'); return; }
            var html = '';
            $.each(items, function (i, a) {
                var actor = (a.actor && a.actor !== 'Sistema') ? ' · ' + a.actor : '';
                html += '<div class="docs-tl-item">' +
                        '<div class="docs-tl-dot"></div>' +
                        '<div class="docs-tl-body">' +
                        '<div class="docs-tl-label">' + $('<s>').text(a.label || a.description || '—').html() + '</div>' +
                        '<div class="docs-tl-ts">' + $('<s>').text((a.ts || a.created_at || '') + actor).html() + '</div>' +
                        '</div></div>';
            });
            $timeline.html(html);
            $timeline[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }).fail(function () {
            $timeline.html('<div class="text-danger small">Error al cargar historial.</div>');
        });
    });

    // ── Descargar todo como ZIP ─────────────────────────────────
    $(document).on('click', '[data-docs-download-zip]', function () {
        var $root = tabOf($(this)).find('.docs-rp');
        var url   = $root.data('url-download-zip');
        if (!url) { notify('warning', 'No hay URL de descarga disponible.'); return; }
        window.location.href = url;
    });

    // ── Eliminar archivo principal del expediente ───────────────
    var pendingFileDelete = null;

    $(document).on('click', '.docs-file-del', function (e) {
        e.stopPropagation();
        var $rp       = $(this).closest('.docs-rp');
        var docType   = $(this).data('doc-type');
        var modalId   = $(this).data('modal-confirm');
        var deleteBase = $rp.data('url-delete-file');
        var panelUrl  = $rp.data('url-panel');

        pendingFileDelete = {
            url:      deleteBase + '/' + docType,
            panelUrl: panelUrl,
        };

        if (modalId) {
            window.DocsModal.open(modalId);
        }
    });

    $(document).on('click', '.docs-confirm-file-del-ok', function () {
        if (!pendingFileDelete) { return; }

        var del       = pendingFileDelete;
        pendingFileDelete = null;

        var $btn = $(this);
        $btn.prop('disabled', true);
        var $modal = $btn.closest('.docs-modal');
        window.DocsModal.close($modal.attr('id'));

        $.ajax({
            url: del.url, method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function () {
            notify('success', 'Archivo eliminado. Ya puedes subir uno nuevo.');
            var $body = $('#docsDetailPanelBody');
            $body.html('<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin me-1"></i> Actualizando expediente…</div>');
            $.ajax({
                url: del.panelUrl, method: 'GET',
                headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'text/html' }
            }).done(function (html) {
                $body.html(html);
            }).fail(function () {
                notify('error', 'Error al recargar el expediente.');
            });
        }).fail(function (xhr) {
            notify('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Error al eliminar el archivo.');
            $btn.prop('disabled', false);
        });
    });

    // ── Búsqueda en tiempo real ──────────────────────────────────
    $(document).on('input', '.docs-list-search', function () {
        var q = $(this).val().trim().toLowerCase();
        var activeFilter = $(this).closest('.docs-list-view').find('.docs-list-filter.on').data('filter') || 'all';

        $(this).closest('.docs-list-view').find('.docs-list-card').each(function () {
            var ref  = ($(this).data('doc-ref')  || '').toLowerCase();
            var name = ($(this).data('doc-name') || '').toLowerCase();
            var matchQ = !q || ref.indexOf(q) !== -1 || name.indexOf(q) !== -1;
            var tags   = ' ' + ($(this).data('doc-tags') || '') + ' ';
            var matchF = activeFilter === 'all' || tags.indexOf(' ' + activeFilter + ' ') !== -1;
            $(this).toggle(matchQ && matchF);
        });
    });

    // ── Ordenación ───────────────────────────────────────────────
    function sortDocCards($listView, sortBy) {
        var $list  = $listView.find('[data-docs-list]');
        var $cards = $list.children('.docs-list-card').detach().toArray();
        $cards.sort(function (a, b) {
            if (sortBy === 'name') {
                return ($(a).data('doc-name') || '').localeCompare($(b).data('doc-name') || '');
            }
            if (sortBy === 'status') {
                var order = { pending: 0, rejected: 1, approved: 2 };
                return (order[$(a).data('doc-status')] || 0) - (order[$(b).data('doc-status')] || 0);
            }
            if (sortBy === 'date-asc') {
                return ($(a).data('doc-date') || '').localeCompare($(b).data('doc-date') || '');
            }
            return ($(b).data('doc-date') || '').localeCompare($(a).data('doc-date') || '');
        });
        $list.append($cards);
    }

    function closeSortMenu($listView) {
        var $row = $listView.find('.sort-row');
        $row.find('.docs-sort-trigger').removeClass('open');
        $row.find('.docs-sort-menu').hide();
        $row.find('.docs-sort-backdrop').hide();
    }

    $(document).on('click', '.docs-sort-trigger', function () {
        var $t    = $(this);
        var $row  = $t.closest('.sort-row');
        var $menu = $row.find('.docs-sort-menu');
        var open  = $t.hasClass('open');
        $t.toggleClass('open', !open);
        $menu.toggle(!open);
        $row.find('.docs-sort-backdrop').toggle(!open);
    });

    $(document).on('click', '.docs-sort-backdrop', function () {
        closeSortMenu($(this).closest('[data-docs-list-view]'));
    });

    $(document).on('click', '.docs-sort-menu .sort-item', function () {
        var $item     = $(this);
        var $listView = $item.closest('[data-docs-list-view]');
        var sort      = $item.data('sort');
        $item.siblings().removeClass('sel').find('.ck').empty();
        $item.addClass('sel').find('.ck').html('<i class="fa-solid fa-check"></i>');
        $listView.find('.docs-sort-label').text($item.find('.sort-item-lbl').text().trim());
        closeSortMenu($listView);
        sortDocCards($listView, sort);
    });

    // ── Tabs de la columna lateral del workspace ─────────────────
    $(document).on('click', '.docs-ws-tab', function () {
        var $tab  = $(this);
        var pane  = $tab.data('ws-tab');
        var $side = $tab.closest('.docs-vd-side');
        $side.find('.docs-ws-tab').removeClass('on');
        $side.find('.docs-ws-pane').removeClass('on');
        $tab.addClass('on');
        $side.find('[data-ws-pane="' + pane + '"]').addClass('on');
    });

    // ── Filtros de galería de documentos ─────────────────────────
    $(document).on('click', '[data-gallery-filter]', function () {
        var filter  = $(this).data('gallery-filter');
        var $parent = $(this).closest('.docs-vd-card');
        $parent.find('[data-gallery-filter]').removeClass('on');
        $(this).addClass('on');
        $parent.find('.doc-card').each(function () {
            var tag = $(this).data('gallery-tag') || 'uploaded';
            $(this).toggle(filter === 'all' || tag === filter);
        });
    });

}(window.jQuery));
</script>
@endpush
@endonce
