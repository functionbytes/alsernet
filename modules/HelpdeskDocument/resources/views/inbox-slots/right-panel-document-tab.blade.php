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
        <div class="docs-foot">
            <button type="button" class="btn btn-outline-secondary w-100 docs-close">Cerrar</button>
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

            <div class="tk-list" data-docs-list>
                @foreach($docList as $d)
                    @php
                        $grp = $statusGroup($d['status_key'] ?? 'pending');
                        $panelUrl = $rpConvo
                            ? route('manager.helpdesk.conversations.documents.panel', [$rpConvo->id, $d['id']])
                            : '#';
                    @endphp
                    <button type="button" class="tk-card docs-list-card"
                            data-docs-open="{{ $panelUrl }}"
                            data-doc-tags="all {{ $grp }}">
                        <div class="head">
                            <i class="fa-regular fa-folder-open" style="font-size:10px;color:var(--bv-text-muted,#71717a)"></i>
                            <span class="id">#{{ $d['order_reference'] }}</span>
                            <span class="docs-status {{ $grp }}">{{ $d['status_label'] }}</span>
                        </div>
                        <div class="title">{{ \Illuminate\Support\Str::limit($d['type_label'], 60) }}</div>
                        <div class="foot">
                            <span class="seg"><i class="fa-regular fa-file"></i> {{ $d['file_count'] }} archivo{{ $d['file_count'] === 1 ? '' : 's' }}</span>
                            <span class="seg" style="margin-left:auto"><i class="fa-regular fa-clock"></i> {{ $d['created_human'] }}</span>
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

}(window.jQuery));
</script>
@endpush
@endonce
