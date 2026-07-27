{{-- Tab "Documentacion" del right-panel del inbox — LISTA de expedientes del cliente.
     Propietario: HelpdeskDocument · Renderizado por Helpdesk via slot.
     Espera $rpDocuments (array de items ligeros, DocumentPanelPresenter::list) y
     $rpConvo (Conversation). El detalle de cada expediente se carga via AJAX en el
     host (#docs-detail-host) desde la ruta manager.helpdesk.conversations.documents.panel. --}}
<div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="document">
    @include('helpdeskdocument::modals._assets')


    {{-- Backdrop + modal persistente del detalle de expediente --}}
    <div class="docs-backdrop" data-docs-modal-backdrop-for="docsDetailPanel"></div>
    <div class="docs-modal docs-modal-xl" id="docsDetailPanel" data-docs-persistent
         aria-labelledby="docsDetailPanelLabel"
         data-url-manage-tpl="{{ route('documents.manage', '__UID__') }}"
         data-url-summary-tpl="{{ route('documents.summary', '__UID__') }}"
         data-url-media-tpl="{{ route('manager.helpdesk.customers.media', ['customer' => '__ID__']) }}"
         data-url-import-chat-tpl="{{ route('manager.helpdesk.conversations.documents.import-from-chat', ['conversation' => '__ID__']) }}"
         data-url-import-device-tpl="{{ route('manager.helpdesk.conversations.documents.import-from-device', ['conversation' => '__ID__']) }}">
        <div class="docs-head">
            <div class="docs-head-icon"><i class="fa-regular fa-folder-open"></i></div>
            <div class="docs-head-text">
                <div class="docs-head-label" id="docsDetailPanelLabel">Expediente</div>
                <div class="docs-head-order" id="docsDetailPanelOrder"></div>
            </div>
            <button type="button" class="docs-close" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="docs-body pad-0" id="docsDetailPanelBody">
            {{-- Relleno via AJAX al abrir --}}
        </div>
        <div class="docs-foot">
            <button type="button" class="btn btn-outline-secondary w-100" data-docs-manage-link>
                Ver en gestor de documentos
            </button>
            <button type="button" class="btn btn-outline-secondary w-100 docs-close">
                Cerrar
            </button>
        </div>
    </div>

    {{-- Modal: crear expediente nuevo para el cliente --}}
    <div class="docs-backdrop" data-docs-modal-backdrop-for="docsCreatePanel"></div>
    <div class="docs-modal docs-modal-md" id="docsCreatePanel" aria-labelledby="docsCreatePanelLabel">
        <div class="docs-head">
            <div class="docs-head-icon"><i class="fa-regular fa-folder-open"></i></div>
            <div class="docs-head-text">
                <div class="docs-head-label">Documentos</div>
                <div class="docs-head-title" id="docsCreatePanelLabel">Asignar expediente</div>
            </div>
            <button type="button" class="docs-close" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="docs-body">
            <div class="docs-minfo">
                <i class="fa-solid fa-circle-info"></i>
                <div class="docs-create-info">Se creará un expediente para <b class="docs-create-customer">el cliente</b> y quedará vinculado a esta conversación.</div>
            </div>

            {{-- Modo: crear un expediente nuevo o asignar uno ya existente --}}
            {{-- Toggle oculto: el modal opera solo en modo "Asignar existente". Para
                 reactivar "Crear nuevo", quitar la clase d-none. --}}
            <div class="btn-group w-100 mt-2 mb-2 docs-create-modes d-none" role="group" aria-label="Modo">
                <button type="button" class="btn btn-sm btn-primary" data-docs-create-mode="new">Crear nuevo</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-docs-create-mode="assign">Asignar existente</button>
            </div>

            {{-- Crear nuevo --}}
            <div data-docs-create-pane="new">
                <div class="docs-field">
                    <label class="flabel">Tipo de documento</label>
                    <select class="fselect docs-create-type"><option value="">Cargando…</option></select>
                </div>
                <div class="docs-field mt-2">
                    <label class="flabel">Referencia de orden <span class="hint">opcional</span></label>
                    <input type="text" class="finput docs-create-ref" maxlength="64" placeholder="#829575">
                </div>
            </div>

            {{-- Asignar existente --}}
            <div class="d-none" data-docs-create-pane="assign">
                <div class="docs-field">
                    <label class="flabel">Buscar expediente</label>
                    <div class="docs-search-field">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" class="docs-assign-search" autocomplete="off" placeholder="Nº de orden, DNI, correo, nombre…">
                    </div>
                    <div class="hint mt-1">Por nº de orden, documento (DNI/uid), correo o nombre. Mín. 2 caracteres.</div>
                </div>
                <div class="docs-opt-list mt-2" id="docsAssignResults"></div>
            </div>
        </div>
        <div class="docs-foot">
            <button type="button" class="btn btn-primary docs-create-submit" data-docs-mode-btn="new">Crear expediente</button>
            <button type="button" class="btn btn-primary docs-assign-submit d-none" data-docs-mode-btn="assign">Asignar expediente</button>
            <button type="button" class="btn btn-outline-secondary docs-close">Cancelar</button>
        </div>
    </div>

    {{-- Vista lista de expedientes (partial re-renderizable vía AJAX) --}}
    <div class="docs-list-view" data-docs-list-view
         @if($rpConvo)
         data-url-list="{{ route('manager.helpdesk.conversations.documents.list', $rpConvo->id) }}"
         data-url-create-options="{{ route('manager.helpdesk.conversations.documents.create-options', $rpConvo->id) }}"
         data-url-create="{{ route('manager.helpdesk.conversations.documents.store', $rpConvo->id) }}"
         data-url-search="{{ route('manager.helpdesk.conversations.documents.search', $rpConvo->id) }}"
         data-url-link="{{ route('manager.helpdesk.conversations.documents.link', [$rpConvo->id, '_DOCID_']) }}"
         @endif>
        @include('helpdeskdocument::inbox-slots._document-list', ['rpDocuments' => $rpDocuments ?? [], 'rpConvo' => $rpConvo])
    </div>
</div>

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

    function freshUrl(url) {
        return url + (String(url).indexOf('?') === -1 ? '?' : '&') + '_=' + Date.now();
    }

    // Progreso real de subida (sustituye el texto fijo "Subiendo…"): factory
    // de `xhr` para $.ajax que escribe el % en el <span class="docs-upload-pct">
    // ya presente en el label puesto por busy($btn, true, '... <span ...>').
    function xhrUploadProgress($btn) {
        return function () {
            var xhr = $.ajaxSettings.xhr();
            if (xhr.upload) {
                xhr.upload.addEventListener('progress', function (e) {
                    if (e.lengthComputable) {
                        var pct = Math.round((e.loaded / e.total) * 100);
                        $btn.find('.docs-upload-pct').text(pct + '%');
                    }
                }, false);
            }
            return xhr;
        };
    }

    // Parseo unico de errores 422 (Form Requests): concatena todos los
    // mensajes de xhr.responseJSON.errors; si no hay, cae a .message o al
    // fallback dado. Usar en TODOS los .fail() de AJAX de este archivo.
    function notifyAjaxError(xhr, fallbackMsg) {
        var err = '';
        if (xhr && xhr.responseJSON) {
            if (xhr.responseJSON.errors) {
                err = Object.values(xhr.responseJSON.errors).flat().join(' ');
            } else {
                err = xhr.responseJSON.message || '';
            }
        }
        notify('error', err || fallbackMsg || 'Error al procesar la solicitud.');
    }

    // Estado de carga del expediente (mismo lenguaje visual que los estados
    // vacíos .docs-es-empty: ícono en caja redondeada + título + descripción,
    // en vez de un spinner suelto perdido en un modal en blanco).
    function loadingExpedienteHtml(title) {
        return '<div class="docs-loading-center">' +
            '<div class="docs-es-empty">' +
            '<div class="eic"><i class="fas fa-spinner fa-spin"></i></div>' +
            '<div><div class="et">' + title + '</div>' +
            '<div class="esb">Estamos recuperando los documentos y el estado más reciente.</div></div>' +
            '</div>' +
            '</div>';
    }

    // Preservar la pestaña del workspace activa (Ficha/Estado/Validación/...)
    // a través de un reemplazo completo de #docsDetailPanelBody: sin esto,
    // aprobar/rechazar/importar/borrar archivo siempre volvía a "Estado".
    function activeWsTab($scope) {
        var $tab = $scope.find('.docs-ws-tab.on').first();
        return $tab.length ? $tab.data('ws-tab') : null;
    }

    function restoreWsTab($scope, tabKey) {
        if (!tabKey) { return; }
        $scope.find('.docs-ws-tab[data-ws-tab="' + tabKey + '"]').trigger('click');
    }

    function reloadPanel($root) {
        var panelUrl = $root.data('url-panel');
        if (!panelUrl) { return; }
        var $body = $('#docsDetailPanelBody');
        var activeTab = activeWsTab($body);
        $body.html(loadingExpedienteHtml('Actualizando expediente…'));
        $.ajax({
            url: freshUrl(panelUrl), method: 'GET',
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'text/html' }
        }).done(function (html) {
            $body.html(html);
            applyPreferredDocView($body);
            restoreWsTab($body, activeTab);
        }).fail(function () {
            notify('error', 'Error al recargar el expediente.');
        });
    }

    // Ventana de confirmación de 3s: primer clic arma el botón (countdown
    // visible "(3s)" → "(1s)"), segundo clic dentro de la ventana confirma.
    // Si expira el tiempo, vuelve al estado original sin ejecutar la acción.
    function twoClickConfirm($btn, label, onConfirm) {
        if ($btn.data('tcc')) {
            clearInterval($btn.data('tcc-i'));
            clearTimeout($btn.data('tcc-t'));
            $btn.removeData('tcc tcc-t tcc-i');
            $btn.html($btn.data('tcc-orig')).removeClass('btn-danger');
            onConfirm.call($btn[0]);
            return;
        }

        var secondsLeft = 3;
        $btn.data('tcc', true).data('tcc-orig', $btn.html());

        function renderCountdown() {
            $btn.html('<i class="fas fa-exclamation me-1"></i>' + label + ' (' + secondsLeft + 's)');
        }

        renderCountdown();
        $btn.addClass('btn-danger');

        var intervalId = setInterval(function () {
            secondsLeft -= 1;
            if (secondsLeft > 0) { renderCountdown(); }
        }, 1000);

        $btn.data('tcc-i', intervalId).data('tcc-t', setTimeout(function () {
            if ($btn.data('tcc')) {
                clearInterval(intervalId);
                $btn.removeData('tcc tcc-t tcc-i');
                $btn.html($btn.data('tcc-orig')).removeClass('btn-danger');
            }
        }, 3000));
    }

    function doPost(url, data, $btn, loadingLabel, successMsg) {
        busy($btn, true, loadingLabel);
        $.ajax({
            url: url, method: 'POST', data: data,
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function (res) {
            notify('success', (res && res.message) || successMsg);
            busy($btn, false);
        }).fail(function (xhr) {
            busy($btn, false);
            notifyAjaxError(xhr);
        });
    }

    // ══ Lista de expedientes: abrir detalle en modal persistente ══════
    $(document).on('click', '.docs-list-card[data-docs-open]', function () {
        var url   = $(this).data('docs-open');
        var $body = $('#docsDetailPanelBody');

        $body.html(loadingExpedienteHtml('Cargando expediente…'));
        window.DocsModal.open('docsDetailPanel');

        // freshUrl() evita que el navegador sirva una respuesta cacheada de
        // una apertura anterior del mismo expediente (por eso hacía falta un
        // F5 para ver archivos recién subidos: reabrir desde la lista no
        // invalidaba caché, a diferencia de reloadPanel()/refreshDocumentsList()).
        $.ajax({
            url: freshUrl(url), method: 'GET',
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'text/html' }
        }).done(function (html) {
            $body.html(html);
            applyPreferredDocView($body);
            // Header simplificado: "Expediente" fijo arriba, el número de
            // orden en su propia línea debajo (antes también mostraba tipo,
            // cliente y estado — redundante con lo que ya se ve en el tab
            // Ficha/Estado, y el chip de orden quedaba renderizado dentro del
            // contenedor del título en vez de permanecer fijo por encima de
            // todo el contenido).
            var $rpHead  = $body.find('.docs-rp-head-text');
            var orderRef = ($rpHead.data('order-ref') || '').toString().trim();
            $('#docsDetailPanelOrder').text(orderRef ? ('Orden '+orderRef) : '');
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

    // ── Asignar responsable de validación ───────────────────────
    // El valor tambien se reenvia al aprobar (ver mas abajo), pero sin este
    // handler el select no persistia nada por si solo: si el expediente
    // tiene documentos faltantes "Aprobar etapa" queda deshabilitado y la
    // seleccion se perdia en silencio al recargar.
    $(document).on('change', '.docs-rp .docs-assignee', function () {
        var $root   = rootOf(this);
        var $select = $(this);
        var url     = $root.data('url-assign');
        if (!url) { return; }

        $select.prop('disabled', true);
        $.ajax({
            url: url, method: 'POST',
            data: { assigned_user_id: $select.val() || '' },
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function (res) {
            notify('success', (res && res.message) || 'Responsable actualizado.');
        }).fail(function (xhr) {
            notifyAjaxError(xhr, 'Error al asignar responsable.');
        }).always(function () {
            $select.prop('disabled', false);
        });
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
            notifyAjaxError(xhr, 'Error al aprobar.');
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
            notifyAjaxError(xhr, 'Error al rechazar.');
        });
    });

    // ── Solicitar documentacion (send-notification) ────────────
    $(document).on('click', '.docs-rp .docs-comm-notify', function () {
        var $btn = $(this);
        twoClickConfirm($btn, '¿Enviar solicitud?', function () {
            doPost(rootOf($btn).data('url-send-notify'), {}, $btn, 'Enviando…', 'Solicitud enviada.');
        });
    });

    // ── Recordatorio ────────────────────────────────────────────
    $(document).on('click', '.docs-rp .docs-comm-reminder', function () {
        var $btn = $(this);
        twoClickConfirm($btn, '¿Enviar recordatorio?', function () {
            doPost(rootOf($btn).data('url-send-reminder'), {}, $btn, 'Enviando…', 'Recordatorio enviado.');
        });
    });

    // ── Confirmar recepcion ─────────────────────────────────────
    $(document).on('click', '.docs-rp .docs-comm-upload-confirm', function () {
        var $btn = $(this);
        twoClickConfirm($btn, '¿Confirmar recepción?', function () {
            doPost(rootOf($btn).data('url-send-upload-confirm'), {}, $btn, 'Enviando…', 'Confirmación enviada.');
        });
    });

    // ── Notificación de aprobación ───────────────────────────────
    $(document).on('click', '.docs-rp .docs-comm-approval', function () {
        var $btn = $(this);
        twoClickConfirm($btn, '¿Enviar notificación?', function () {
            doPost(rootOf($btn).data('url-send-approval'), {}, $btn, 'Enviando…', 'Notificación enviada.');
        });
    });

    // ── Solicitar faltantes: enviar desde vista inline ──────────
    $(document).on('click', '.docs-missing-send', function () {
        var $btn  = $(this);
        var $card = $btn.closest('.docs-inline-email');
        var $rp   = rootOf($btn);
        var docs  = [];
        $card.find('.docs-missing-cb:checked').each(function () { docs.push($(this).val()); });
        if (!docs.length) { notify('warning', 'Selecciona al menos un documento.'); return; }
        var notes = $card.find('.docs-missing-notes').val().trim();
        busy($btn, true, 'Enviando…');
        $.ajax({
            url: $rp.data('url-send-missing'), method: 'POST',
            data: { missing_docs: docs, notes: notes },
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function (res) {
            notify('success', (res && res.message) || 'Solicitud enviada.');
            closeFileDetail($rp);
        }).fail(function (xhr) {
            busy($btn, false);
            notifyAjaxError(xhr, 'Error al enviar.');
        });
    });

    // ── Correo de rechazo: enviar desde vista inline ────────────
    $(document).on('click', '.docs-rej-email-send', function () {
        var $btn    = $(this);
        var $card   = $btn.closest('.docs-inline-email');
        var $rp     = rootOf($btn);
        var reason  = $card.find('.docs-rej-email-reason').val().trim();
        if (reason.length < 10) { notify('warning', 'El motivo debe tener al menos 10 caracteres.'); return; }
        var rejDocs = [];
        $card.find('.docs-rej-doc-cb:checked').each(function () { rejDocs.push($(this).val()); });
        busy($btn, true, 'Enviando…');
        $.ajax({
            url: $rp.data('url-send-rej-email'), method: 'POST',
            data: { reason: reason, rejected_docs: rejDocs },
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function (res) {
            notify('success', (res && res.message) || 'Correo de rechazo enviado.');
            closeFileDetail($rp);
        }).fail(function (xhr) {
            busy($btn, false);
            notifyAjaxError(xhr, 'Error al enviar correo.');
        });
    });

    // ── Correo personalizado: enviar desde vista inline ─────────
    $(document).on('click', '.docs-custom-email-send', function () {
        var $btn     = $(this);
        var $card    = $btn.closest('.docs-inline-email');
        var $rp      = rootOf($btn);
        var subject  = $card.find('.docs-custom-subject').val().trim();
        var message  = $card.find('.docs-custom-message').val().trim();
        if (!subject) { notify('warning', 'El asunto es obligatorio.'); return; }
        if (message.length < 10) { notify('warning', 'El mensaje es muy corto.'); return; }
        busy($btn, true, 'Enviando…');
        $.ajax({
            url: $rp.data('url-send-custom'), method: 'POST',
            data: { subject: subject, message: message },
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function (res) {
            notify('success', (res && res.message) || 'Correo enviado correctamente.');
            closeFileDetail($rp);
        }).fail(function (xhr) {
            busy($btn, false);
            notifyAjaxError(xhr, 'Error al enviar correo.');
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
            notifyAjaxError(xhr, 'Error al agregar nota.');
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
            notifyAjaxError(xhr, 'Error al eliminar nota.');
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
        busy($btn, true, 'Subiendo… <span class="docs-upload-pct">0%</span>');
        $.ajax({
            url: $root.data('url-upload-attach'), method: 'POST', data: fd,
            processData: false, contentType: false, xhr: xhrUploadProgress($btn),
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function (res) {
            notify('success', (res && res.message) || 'Adjunto subido.');
            $file.val('');
            showAttachFileName($root.find('.docs-attach-dropzone'), null);
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
            notifyAjaxError(xhr, 'Error al subir adjunto.');
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
            notifyAjaxError(xhr, 'Error al eliminar adjunto.');
        });
    });

    // ── Estado: "Editar" junto a Cliente salta a Ficha y abre su editor ──
    $(document).on('click', '.docs-goto-cust-edit', function () {
        var $rp = rootOf($(this));
        $rp.find('.docs-ws-tab[data-ws-tab="ficha"]').trigger('click');
        $rp.find('.docs-cust-edit-toggle').trigger('click');
    });

    // ── Ficha: editar datos del cliente inline (sin modal aparte) ──
    $(document).on('click', '.docs-cust-edit-toggle', function () {
        var $card = $(this).closest('.docs-vd-card');
        $card.find('.docs-cust-edit input').each(function () {
            $(this).data('orig', $(this).val());
        });
        $card.find('.docs-cust-view').addClass('d-none');
        $card.find('.docs-cust-edit').removeClass('d-none');
    });

    $(document).on('click', '.docs-cust-cancel', function () {
        var $card = $(this).closest('.docs-vd-card');
        $card.find('.docs-cust-edit input').each(function () {
            $(this).val($(this).data('orig') ?? '');
        });
        $card.find('.docs-cust-edit').addClass('d-none');
        $card.find('.docs-cust-view').removeClass('d-none');
    });

    $(document).on('click', '.docs-cust-save', function () {
        var $card = $(this).closest('.docs-vd-card');
        var $edit = $card.find('.docs-cust-edit');
        var $root = rootOf($card);
        var uid   = $root.data('doc-uid');
        if (!uid) { notify('warning', 'Sin expediente activo.'); return; }

        var values = {
            firstname:  $edit.find('[name="firstname"]').val().trim(),
            lastname:   $edit.find('[name="lastname"]').val().trim(),
            identifier: $edit.find('[name="identifier"]').val().trim(),
            email:      $edit.find('[name="email"]').val().trim(),
            phone:      $edit.find('[name="phone"]').val().trim(),
            company:    $edit.find('[name="company"]').val().trim(),
        };

        var $btn = $(this);
        busy($btn, true, 'Guardando…');
        $.ajax({
            url: $root.data('url-update'), method: 'POST',
            data: {
                uid: uid,
                data: {
                    customer_firstname: values.firstname,
                    customer_lastname:  values.lastname,
                    customer_dni:       values.identifier,
                    customer_email:     values.email,
                    customer_cellphone: values.phone,
                    customer_company:   values.company,
                }
            },
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function (res) {
            notify('success', (res && res.message) || 'Datos guardados correctamente.');
            busy($btn, false);

            var $view = $card.find('.docs-cust-view');
            var order = ['firstname', 'lastname', 'identifier', 'email', 'phone', 'company'];
            $.each(order, function (i, key) {
                var $v = $view.find('> div').eq(i).find('.v');
                $v.text(values[key] || '—');
                $v.toggleClass('muted', !values[key]);
            });
            $edit.find('input').each(function () { $(this).data('orig', $(this).val()); });

            $edit.addClass('d-none');
            $view.removeClass('d-none');
        }).fail(function (xhr) {
            busy($btn, false);
            notifyAjaxError(xhr, 'Error al guardar.');
        });
    });

    // ── Descargar todo como ZIP ─────────────────────────────────
    $(document).on('click', '[data-docs-download-zip]', function () {
        var $root = tabOf($(this)).find('.docs-rp');
        var url   = $root.data('url-download-zip');
        if (!url) { notify('warning', 'No hay URL de descarga disponible.'); return; }
        window.location.href = url;
    });

    // ── Eliminar archivo principal: toast con deshacer (sin modal) ──────
    // La card se oculta de inmediato y el DELETE se difiere unos segundos;
    // "Deshacer" restaura la card sin tocar el servidor. Cerrar el toast (X)
    // o agotar el tiempo ejecuta el borrado real y recarga el panel.
    var UNDO_DELAY_MS = 6000;
    var undoState = null; // { timer, $toast, $cards, url, panelUrl }

    function dismissUndoToast() {
        if (!undoState) { return; }
        clearTimeout(undoState.timer);
        undoState.$toast.remove();
        undoState = null;
    }

    function commitPendingFileDelete() {
        if (!undoState) { return; }
        var del = { url: undoState.url, panelUrl: undoState.panelUrl };
        var $cards = undoState.$cards;
        dismissUndoToast();

        $.ajax({
            url: del.url, method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function () {
            var $body = $('#docsDetailPanelBody');
            var activeTab = activeWsTab($body);
            $body.html(loadingExpedienteHtml('Actualizando expediente…'));
            $.ajax({
                url: del.panelUrl, method: 'GET',
                headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'text/html' }
            }).done(function (html) {
                $body.html(html);
                restoreWsTab($body, activeTab);
            }).fail(function () {
                notify('error', 'Error al recargar el expediente.');
            });
        }).fail(function (xhr) {
            notifyAjaxError(xhr, 'Error al eliminar el archivo.');
            $cards.removeClass('d-none');
        });
    }

    // ── Reemplazar un archivo ya subido por otro desde el equipo ────
    // Borra el archivo actual y sube el nuevo bajo el mismo doc-type, en vez
    // de obligar al agente a eliminar y luego buscar el slot "Cargar" de nuevo.
    $(document).on('click', '.docs-file-replace', function (e) {
        e.stopPropagation();
        $(this).siblings('.docs-file-replace-input').trigger('click');
    });

    $(document).on('change', '.docs-file-replace-input', function (e) {
        e.stopPropagation();

        var files = this.files;
        if (!files || !files.length) { return; }

        var $input = $(this);
        var $rp = rootOf($input);
        var docType = $input.data('doc-type');
        var docId = $rp.data('doc-id');
        var convId = currentConversationId();

        if (!docType || !docId || !convId) { return; }

        var $btn = $input.siblings('.docs-file-replace');
        busy($btn, true, 'Reemplazando… <span class="docs-upload-pct"></span>');

        $.ajax({
            url: $rp.data('url-delete-file') + '/' + docType, method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function () {
            var fd = new FormData();
            fd.append('files[]', files[0]);
            fd.append('categories[]', docType);
            fd.append('category', docType);
            fd.append('document_id', docId);
            fd.append('notify_customer', '0');

            $.ajax({
                url: importRoute('url-import-device-tpl', convId), method: 'POST',
                data: fd, processData: false, contentType: false, xhr: xhrUploadProgress($btn),
                headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
            }).done(function () {
                notify('success', 'Documento reemplazado correctamente.');
                closeFileDetail($rp);
                refreshAfterImport($rp);
            }).fail(function (xhr) {
                notifyAjaxError(xhr, 'El anterior se eliminó pero el nuevo no se pudo subir — vuelve a cargarlo.');
                busy($btn, false);
                closeFileDetail($rp);
                refreshAfterImport($rp);
            });
        }).fail(function (xhr) {
            busy($btn, false);
            notifyAjaxError(xhr, 'Error al eliminar el archivo anterior.');
        }).always(function () {
            $input.val('');
        });
    });

    $(document).on('click', '.docs-file-del', function (e) {
        e.stopPropagation();
        // Solo un borrado pendiente a la vez: el anterior se ejecuta ya.
        commitPendingFileDelete();

        var $rp     = $(this).closest('.docs-rp');
        var docType = $(this).data('doc-type');
        var label   = $(this).data('doc-label') || 'Documento';
        // Matchear por doc-type (la clave real que también usa el DELETE al
        // backend), no por el texto del label: si dos archivos comparten el
        // mismo type_label (datos con duplicados), matchear por texto ocultaba
        // ambas tarjetas aunque el servidor solo borrara una — al recargar
        // reaparecía "sola" la que en realidad nunca se eliminó.
        var $cards = $(this).closest('.doc-card')
            .add($rp.find('[data-doc-type]').filter(function () {
                return $(this).data('doc-type') === docType;
            }));

        $cards.addClass('d-none');
        if ($(this).closest('[data-docs-file-detail]').length) {
            closeFileDetail($rp);
        }

        var $toast = $(
            '<div class="docs-undo-toast" role="status" aria-live="polite">' +
                '<i class="fa-solid fa-trash-can"></i> ' +
                '<span>' + $('<s>').text(label).html() + ' eliminado</span>' +
                '<button type="button" class="ub">Deshacer</button>' +
                '<button type="button" class="ux" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>' +
            '</div>'
        ).appendTo('body');

        undoState = {
            url: $rp.data('url-delete-file') + '/' + docType,
            panelUrl: $rp.data('url-panel'),
            $cards: $cards,
            $toast: $toast,
            timer: setTimeout(commitPendingFileDelete, UNDO_DELAY_MS),
        };
    });

    $(document).on('click', '.docs-undo-toast .ub', function () {
        if (undoState) { undoState.$cards.removeClass('d-none'); }
        dismissUndoToast();
    });

    $(document).on('click', '.docs-undo-toast .ux', commitPendingFileDelete);

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
        $side.find('.docs-ws-tab').removeClass('on').attr('aria-selected', 'false');
        $side.find('.docs-ws-pane').removeClass('on');
        $tab.addClass('on').attr('aria-selected', 'true');
        $side.find('[data-ws-pane="' + pane + '"]').addClass('on');
    });

    // ── Filtros de galería de documentos (galería y vista lista) ─
    $(document).on('click', '[data-gallery-filter]', function () {
        var filter  = $(this).data('gallery-filter');
        var $parent = $(this).closest('.docs-vd-card');
        $parent.find('[data-gallery-filter]').removeClass('on');
        $(this).addClass('on');
        $parent.find('.doc-card, .doc-listrow').each(function () {
            var tag = $(this).data('gallery-tag') || 'uploaded';
            $(this).toggle(filter === 'all' || tag === filter);
        });
    });

    // ── Toggle galería ↔ lista compacta (persistido por agente) ──
    $(document).on('click', '[data-docs-view-toggle] button', function () {
        var view    = $(this).data('docs-view');
        var $card   = $(this).closest('.docs-vd-card');
        $(this).addClass('on').siblings().removeClass('on');
        $card.find('[data-docs-doc-view]').each(function () {
            $(this).toggleClass('d-none', $(this).data('docs-doc-view') !== view);
        });
        try { localStorage.setItem('docs_doc_view', view); } catch (e) {}
    });

    // Al cargar un expediente, restaurar la vista preferida del agente.
    function applyPreferredDocView($scope) {
        var view = 'grid';
        try { view = localStorage.getItem('docs_doc_view') || 'grid'; } catch (e) {}
        if (view === 'grid') { return; }
        $scope.find('[data-docs-view-toggle] button[data-docs-view="' + view + '"]').trigger('click');
    }

    // ── Abrir documento / selección múltiple ─────────────────────
    // El estado del modo selección se deriva del DOM (bulkbar visible):
    // el panel se recarga por AJAX y una variable de módulo quedaría stale.
    function exitSelectionMode($rp) {
        $rp.find('.doc-card.docs-selectable').removeClass('selmode sel');
        $rp.find('.doc-card .doc-check').addClass('d-none');
        $rp.find('[data-docs-bulkbar]').addClass('d-none');
        $rp.find('.docs-select-toggle').text('Seleccionar');
    }

    function updateBulkCount($rp) {
        var n = $rp.find('.doc-card.sel').length;
        $rp.find('[data-docs-bulk-count]').text(n + ' seleccionado' + (n === 1 ? '' : 's'));
    }

    function fileData($el) {
        return {
            url: $el.data('doc-url') || '',
            fileName: $el.data('doc-file-name') || '',
            label: $el.data('doc-label') || $el.find('.nm, .n').first().text() || 'Documento',
            docType: $el.data('doc-type') || '',
            ext: $el.data('doc-ext') || '',
            mime: $el.data('doc-mime') || '',
            size: $el.data('doc-size') || '—',
            uploaded: $el.data('doc-uploaded') || '—',
            status: $el.data('doc-status') || 'received',
            statusLabel: $el.data('doc-status-label') || 'Recibido',
            origin: $el.data('doc-origin') || '—',
            system: $el.data('doc-system') || '—',
            uploadedBy: $el.data('doc-uploaded-by') || 'Cliente',
            created: $el.data('doc-created') || '—',
            updated: $el.data('doc-updated') || '—'
        };
    }

    function kv(label, value, mono) {
        return $('<div></div>')
            .append($('<div class="k"></div>').text(label))
            .append($('<div></div>').addClass('v').toggleClass('mono', !!mono).text(value || '—'));
    }

    function importRoute(attr, id) {
        return String($('#docsDetailPanel').data(attr) || '').replace('__ID__', id);
    }

    function isImageMedia(media) {
        var url = String((media && media.url) || '');
        var type = String((media && media.type) || '').toLowerCase();
        var mime = String((media && (media.mime || media.mime_type || media.content_type)) || '').toLowerCase();

        return type === 'image'
            || mime.indexOf('image/') === 0
            || /\.(avif|bmp|gif|heic|heif|jpe?g|png|tiff?|webp|svg)(\?|#|$)/i.test(url);
    }

    function currentConversationId() {
        return (window.HDCommerce && window.HDCommerce.conversationId) ? window.HDCommerce.conversationId() : null;
    }

    function currentCustomerId() {
        return (window.HDCommerce && window.HDCommerce.customerId) ? window.HDCommerce.customerId() : null;
    }

    function parseMissingList($rp) {
        try { return JSON.parse($rp.attr('data-missing') || '[]'); } catch (e) { return []; }
    }

    // Categoría "primaria" mostrada en el copy del panel de importación.
    // OJO: cuando la importación es genérica (sin selectedKey, ej. el botón
    // "Subir"/"Desde chat" del toolbar) y hay 2+ documentos faltantes, esto
    // NO debe usarse para etiquetar TODOS los archivos del lote — antes
    // caía siempre en missing[0], así que subir varios archivos a la vez
    // etiquetaba todo con el mismo tipo y el resto quedaba "faltante" para
    // siempre. Ver categoriesForKeys(), que reparte cada archivo entre los
    // documentos faltantes en orden.
    function importCategoryFor(missing, selectedKey) {
        if (selectedKey) {
            for (var i = 0; i < missing.length; i++) {
                if (missing[i].key === selectedKey) {
                    return missing[i];
                }
            }
        }

        if (missing.length === 1) {
            return missing[0];
        }

        return { key: 'other', label: 'Sin categoría' };
    }

    // Reparte `keys` (URLs o índices de archivo, en el orden seleccionado)
    // entre los documentos faltantes del expediente cuando la importación es
    // genérica y hay más de uno pendiente; si es una importación de un slot
    // específico (o solo falta 1 tipo), todos los archivos usan esa misma
    // categoría — ese caso sí es un único documento, no varios.
    function categoriesForKeys($card, keys) {
        var single  = $card.data('import-category') || 'other';
        var generic = !!$card.data('generic-fill');
        var missing = $card.data('missing-list') || [];
        var map = {};

        keys.forEach(function (k, i) {
            map[k] = (generic && missing.length > 1)
                ? ((missing[i] && missing[i].key) || 'other')
                : single;
        });

        return map;
    }

    function openImportDetail($source, selectedKey) {
        var $rp = rootOf($source);
        var $detail = $rp.find('[data-docs-file-detail]').first();
        var customerId = currentCustomerId();
        var convId = currentConversationId();

        if (!$detail.length) { return; }

        var missing = parseMissingList($rp);
        var category = importCategoryFor(missing, selectedKey);
        var $card = $('<div class="docs-vd-card docs-inline-import"></div>')
            .attr('data-import-category', category.key)
            .data('missing-list', missing)
            .data('generic-fill', !selectedKey);
        $card.append(
            $('<div class="h docs-file-detail-head"></div>')
                .append($('<button type="button" class="btn-icon docs-file-detail-back" aria-label="Volver a documentos"><i class="fa-solid fa-arrow-left"></i></button>'))
                .append($('<span class="docs-ws-sec-ic"><i class="fa-solid fa-cloud-arrow-up"></i></span>'))
                .append($('<div class="h-grow"></div>')
                    .append($('<span class="s docs-head-label"></span>').text('DOCUMENTOS · IMPORTAR'))
                    .append($('<span class="t"></span>').text('Cargar desde galería del chat')))
        );
        $card.append(
            $('<div class="docs-minfo"></div>')
                .append('<i class="fa-solid fa-circle-info"></i>')
                .append($('<div></div>').text(category.key === 'other'
                    ? 'Selecciona imágenes compartidas en el chat o sube imágenes desde tu equipo.'
                    : 'Selecciona la imagen para cargarla como "' + category.label + '".'))
        );
        $card.append(
            $('<div class="docs-field"></div>')
                .append($('<label class="flabel">Imágenes disponibles en el chat <span class="hint docs-inline-import-count"></span></label>'))
                .append($('<div class="docs-cg-gallery docs-inline-import-gallery"><div class="docs-cg-loading"><i class="fas fa-spinner fa-spin"></i> Cargando…</div></div>'))
        );
        $card.append(
            $('<div class="docs-file-detail-actions"></div>')
                .append($('<button type="button" class="btn btn-primary btn-sm flex-fill docs-inline-import-submit" disabled></button>').text('Selecciona imágenes'))
                .append($('<button type="button" class="btn btn-outline-secondary btn-sm flex-fill docs-inline-device-btn"></button>').text('Subir desde mi equipo'))
                .append($('<input type="file" class="docs-inline-device-input d-none" multiple accept="image/*">'))
        );

        $detail.empty().append($card).removeClass('d-none');
        $rp.find('[data-docs-doc-view], [data-docs-bulkbar], .docs-gallery-progress, .docs-gallery-toolbar').addClass('d-none');
        $rp.find('.docs-select-toggle').addClass('d-none');
        $detail[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        if (!customerId) {
            $card.find('.docs-inline-import-gallery').html('<div class="docs-vd-empty"><i class="fas fa-triangle-exclamation"></i> Sin cliente vinculado</div>');
            return;
        }

        $.ajax({
            url: importRoute('url-media-tpl', customerId),
            method: 'GET',
            data: convId ? { conversation: convId } : {},
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function (resp) {
            var files = (resp.data || []).filter(function (m) { return m && m.url && isImageMedia(m); });
            $card.find('.docs-inline-import-count').text(files.length + ' imagen(es)');

            if (!files.length) {
                $card.find('.docs-inline-import-gallery').html('<div class="docs-vd-empty"><i class="fa-regular fa-images"></i> Sin imágenes en esta conversación</div>');
                return;
            }

            var $grid = $('<div class="docs-cg-grid"></div>');
            files.forEach(function (m, i) {
                var url = String(m.url || '');
                var name = m.name || url.split('/').pop() || 'Archivo';
                var $pick = $('<button type="button" class="docs-inline-pick"></button>')
                    .attr('data-cg-key', i)
                    .attr('data-url', url);

                $pick.append($('<img loading="lazy">').addClass('preview').attr('src', url).attr('alt', name));
                $pick.append($('<span class="check"><i class="fa-solid fa-check"></i></span>'))
                    .append($('<span class="lbl"></span>').text(name));
                $grid.append($pick);
            });
            $card.find('.docs-inline-import-gallery').empty().append($grid);
        }).fail(function () {
            $card.find('.docs-inline-import-gallery').html('<div class="docs-vd-empty"><i class="fas fa-triangle-exclamation"></i> No se pudo cargar la galería</div>');
        });
    }

    function refreshAfterImport($root) {
        reloadPanel($root);
        refreshDocumentsList();
    }

    function openFileDetail($source) {
        var $rp = rootOf($source);
        var data = fileData($source);
        var $detail = $rp.find('[data-docs-file-detail]').first();
        if (!$detail.length || !data.url) { return; }

        var $preview = $('<a class="docs-file-preview" target="_blank" rel="noopener"></a>').attr('href', data.url);
        if (String(data.mime).indexOf('image/') === 0) {
            $preview.append($('<img>').attr('src', data.url).attr('alt', data.label));
        } else {
            $preview.append($('<i></i>').addClass(data.mime === 'application/pdf' ? 'fa-regular fa-file-pdf' : 'fa-regular fa-file-lines'));
        }

        var $download = $('<a class="btn btn-outline-secondary btn-sm flex-fill" target="_blank" rel="noopener"></a>')
            .attr('href', data.url)
            .attr('download', data.fileName)
            .text('Descargar');

        var isApproved = data.status === 'approved';

        var $delete = $('<button type="button" class="btn btn-outline-secondary btn-sm flex-fill docs-file-del docs-action-danger"></button>')
            .attr('data-doc-type', data.docType)
            .attr('data-doc-label', data.label)
            .text('Eliminar');

        var $replace = $('<button type="button" class="btn btn-outline-secondary btn-sm flex-fill docs-file-replace"></button>')
            .attr('data-doc-type', data.docType)
            .text('Reemplazar');
        var $replaceInput = $('<input type="file" class="docs-file-replace-input d-none" accept="image/*">')
            .attr('data-doc-type', data.docType);

        var $card = $('<div class="docs-vd-card"></div>');
        $card.append(
            $('<div class="h docs-file-detail-head"></div>')
                .append($('<button type="button" class="btn-icon docs-file-detail-back" aria-label="Volver a documentos"><i class="fa-solid fa-arrow-left"></i></button>'))
                .append($('<div class="h-grow"></div>')
                    .append($('<span class="t"></span>').text(data.label))
                    .append($('<span class="s"></span>').text(data.size + ' · ' + data.uploaded)))
                .append($('<span></span>').addClass('docs-status ' + data.status).text(data.statusLabel))
        );
        $card.append($preview);
        $card.append(
            $('<div class="docs-vd-kv"></div>')
                .append(kv('Tipo doc.', data.ext || data.docType || '—'))
                .append(kv('Origen', data.origin))
                .append(kv('Sistema', data.system))
                .append(kv('Cargado por', data.uploadedBy))
                .append(kv('Creado', data.created, true))
                .append(kv('Actualizado', data.updated, true))
        );
        var $actions = $('<div class="docs-file-detail-actions"></div>').append($download);
        if (!isApproved) {
            $actions.prepend($replace, $replaceInput);
            $actions.append($delete);
        }
        $card.append($actions);

        $detail.empty().append($card).removeClass('d-none');
        $rp.find('[data-docs-doc-view], [data-docs-bulkbar], .docs-gallery-progress, .docs-gallery-toolbar').addClass('d-none');
        $rp.find('.docs-select-toggle').addClass('d-none');
        $detail[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function closeFileDetail($rp) {
        var $detail = $rp.find('[data-docs-file-detail]').first();
        $detail.addClass('d-none').empty();
        exitSelectionMode($rp);
        $rp.find('.docs-gallery-progress, .docs-gallery-toolbar').removeClass('d-none');
        $rp.find('.docs-select-toggle').removeClass('d-none');
        var view = 'grid';
        try { view = localStorage.getItem('docs_doc_view') || 'grid'; } catch (e) {}
        $rp.find('[data-docs-doc-view]').each(function () {
            $(this).toggleClass('d-none', $(this).data('docs-doc-view') !== view);
        });
    }

    // Documentos ya subidos, para el checklist opcional del correo de
    // rechazo: mismo dato que antes leia el blade en $files, pero raspado
    // del DOM de la galeria (ya presente) para no tener que exponer un
    // nuevo data-attribute JSON en .docs-rp. Fallback a doc-file-name igual
    // que el blade original, por si algun media no tiene doc_type.
    function parseRejectableFiles($rp) {
        var out = [];
        $rp.find('.doc-card[data-doc-type]').each(function () {
            var $c = $(this);
            out.push({
                value: $c.data('doc-type') || $c.data('doc-file-name') || '',
                label: $c.data('doc-label') || $c.data('doc-file-name') || 'Documento',
            });
        });
        return out;
    }

    // Composicion de correos (Solicitar faltantes / Rechazo / Personalizado):
    // mismo patron que openFileDetail/openImportDetail — un unico card dentro
    // de [data-docs-file-detail], sin abrir un modal sobre el modal principal.
    var EMAIL_COMPOSE = {
        missing: {
            icon: 'fa-solid fa-triangle-exclamation',
            title: 'Solicitar documentos faltantes',
            buildBody: function ($rp) {
                var missing = parseMissingList($rp);
                var $body = $('<div></div>');
                $body.append('<p class="text-muted small mb-2">Selecciona los documentos que el cliente debe enviar.</p>');
                if (missing.length) {
                    missing.forEach(function (m) {
                        $body.append(
                            $('<label class="docs-check mb-1"></label>')
                                .append($('<input type="checkbox" class="docs-missing-cb" checked>').val(m.key))
                                .append(document.createTextNode(' ' + m.label))
                        );
                    });
                } else {
                    $body.append('<p class="text-muted small">No hay documentos faltantes registrados.</p>');
                }
                $body.append(
                    $('<div class="docs-field mt-2"></div>')
                        .append('<label class="flabel">Nota adicional <span class="hint">opcional</span></label>')
                        .append('<textarea class="ftextarea docs-missing-notes" rows="2" placeholder="Recuerda incluir foto con ambos lados..."></textarea>')
                );
                $body.append(
                    $('<div class="docs-file-detail-actions"></div>')
                        .append('<button type="button" class="btn btn-primary btn-sm flex-fill docs-missing-send">Enviar solicitud</button>')
                );
                return $body;
            }
        },
        rejection: {
            icon: 'fa-regular fa-envelope-circle-check',
            title: 'Correo de rechazo',
            buildBody: function ($rp) {
                var files = parseRejectableFiles($rp);
                var $body = $('<div></div>');
                $body.append(
                    $('<div class="docs-field"></div>')
                        .append('<label class="flabel">Motivo del rechazo <span class="hint">min. 10 caracteres</span></label>')
                        .append('<textarea class="ftextarea docs-rej-email-reason" rows="3" placeholder="El documento enviado no es legible..."></textarea>')
                );
                if (files.length) {
                    var $fieldset = $('<div class="docs-field mt-2"></div>')
                        .append('<label class="flabel">Documentos rechazados <span class="hint">opcional</span></label>');
                    files.forEach(function (f) {
                        $fieldset.append(
                            $('<label class="docs-check mb-1"></label>')
                                .append($('<input type="checkbox" class="docs-rej-doc-cb">').val(f.value))
                                .append(document.createTextNode(' ' + f.label))
                        );
                    });
                    $body.append($fieldset);
                }
                $body.append(
                    $('<div class="docs-file-detail-actions"></div>')
                        .append('<button type="button" class="btn btn-primary btn-sm flex-fill docs-rej-email-send">Enviar correo de rechazo</button>')
                );
                return $body;
            }
        },
        custom: {
            icon: 'fa-regular fa-envelope',
            title: 'Correo personalizado',
            buildBody: function () {
                return $('<div></div>')
                    .append(
                        $('<div class="docs-field"></div>')
                            .append('<label class="flabel">Asunto</label>')
                            .append('<input type="text" class="finput docs-custom-subject" placeholder="Información sobre su documentación">')
                    )
                    .append(
                        $('<div class="docs-field mt-2"></div>')
                            .append('<label class="flabel">Mensaje <span class="hint">min. 10 caracteres</span></label>')
                            .append('<textarea class="ftextarea docs-custom-message" rows="4" placeholder="Estimado cliente, le informamos que..."></textarea>')
                    )
                    .append(
                        $('<div class="docs-file-detail-actions"></div>')
                            .append('<button type="button" class="btn btn-primary btn-sm flex-fill docs-custom-email-send">Enviar correo</button>')
                    );
            }
        }
    };

    function openEmailCompose($source, kind) {
        var cfg = EMAIL_COMPOSE[kind];
        if (!cfg) { return; }
        var $rp = rootOf($source);
        var $detail = $rp.find('[data-docs-file-detail]').first();
        if (!$detail.length) { return; }

        var $card = $('<div class="docs-vd-card docs-inline-email"></div>').attr('data-email-kind', kind);
        $card.append(
            $('<div class="h docs-file-detail-head"></div>')
                .append('<button type="button" class="btn-icon docs-file-detail-back" aria-label="Volver a documentos"><i class="fa-solid fa-arrow-left"></i></button>')
                .append($('<span class="docs-ws-sec-ic"><i></i></span>').find('i').addClass(cfg.icon).end())
                .append(
                    $('<div class="h-grow"></div>')
                        .append('<span class="s docs-head-label">Correo al cliente</span>')
                        .append($('<span class="t"></span>').text(cfg.title))
                )
        );
        $card.append(cfg.buildBody($rp));

        $detail.empty().append($card).removeClass('d-none');
        $rp.find('[data-docs-doc-view], [data-docs-bulkbar], .docs-gallery-progress, .docs-gallery-toolbar').addClass('d-none');
        $rp.find('.docs-select-toggle').addClass('d-none');
        $detail[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    $(document).on('click', '[data-docs-email-trigger]', function () {
        openEmailCompose($(this), $(this).data('docs-email-trigger'));
    });

    $(document).on('click', '.docs-select-toggle', function () {
        var $rp = rootOf(this);
        if (!$rp.find('[data-docs-bulkbar]').hasClass('d-none')) {
            exitSelectionMode($rp);
            return;
        }
        $rp.find('.doc-card.docs-selectable').addClass('selmode');
        $rp.find('.doc-card.docs-selectable .doc-check').removeClass('d-none');
        $rp.find('[data-docs-bulkbar]').removeClass('d-none');
        $(this).text('Cancelar selección');
        updateBulkCount($rp);
    });

    $(document).on('click', '.docs-bulk-cancel', function () {
        exitSelectionMode(rootOf(this));
    });

    $(document).on('click', '.doc-card.docs-selectable', function () {
        var $card = $(this);
        if ($card.hasClass('selmode')) {
            $card.toggleClass('sel');
            updateBulkCount(rootOf(this));
            return;
        }
        openFileDetail($card);
    });

    $(document).on('click', '.docs-file-row', function () {
        openFileDetail($(this));
    });

    $(document).on('click', '.doc-card-pending, .docs-missing-row, .docs-req-import', function () {
        var key = $(this).data('missing-key') || null;
        openImportDetail($(this), key);
    });

    $(document).on('click', '.docs-open-import', function () {
        openImportDetail($(this), null);
    });

    $(document).on('click', '[data-docs-manage-link]', function () {
        var $rp = $('#docsDetailPanelBody').find('.docs-rp').first();
        var uid = $rp.data('doc-uid');
        if (!uid) {
            notify('warning', 'Abre un expediente primero.');
            return;
        }
        var tpl = $('#docsDetailPanel').data('url-manage-tpl') || '';
        window.open(tpl.replace('__UID__', uid), '_blank', 'noopener');
    });

    $(document).on('click', '[data-docs-summary-link]', function () {
        var $rp = rootOf(this);
        var uid = $rp.data('doc-uid');
        if (!uid) {
            notify('warning', 'Abre un expediente primero.');
            return;
        }
        var tpl = $('#docsDetailPanel').data('url-summary-tpl') || '';
        window.open(tpl.replace('__UID__', uid), '_blank', 'noopener');
    });

    window.openDocInlineImport = function (selectedKey) {
        var $rp = $('#docsDetailPanelBody').find('.docs-rp').first();
        if ($rp.length) {
            openImportDetail($rp, selectedKey || null);
            return true;
        }
        return false;
    };

    $(document).on('click', '.docs-inline-import .docs-inline-pick', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).toggleClass('on');
        var $card = $(this).closest('.docs-inline-import');
        var count = $card.find('.docs-inline-pick.on').length;
        $card.find('.docs-inline-import-submit')
            .prop('disabled', count === 0)
            .text(count > 0 ? 'Importar ' + count + ' imagen(es)' : 'Selecciona imágenes');
    });

    $(document).on('click', '.docs-inline-import-submit', function () {
        var $btn = $(this);
        var $card = $btn.closest('.docs-inline-import');
        var $root = rootOf($btn);
        var convId = currentConversationId();
        var documentId = $root.data('doc-id') || null;
        var urls = [];

        $card.find('.docs-inline-pick.on').each(function () {
            var url = $(this).data('url');
            if (url) { urls.push(url); }
        });

        if (!convId) { notify('warning', 'No hay conversación activa.'); return; }
        if (!urls.length) { notify('warning', 'Selecciona al menos una imagen.'); return; }

        busy($btn, true, 'Importando…');
        $.ajax({
            url: importRoute('url-import-chat-tpl', convId),
            method: 'POST',
            data: {
                file_ids: urls,
                category: $card.data('import-category') || 'other',
                categories: categoriesForKeys($card, urls),
                document_id: documentId,
                notify_customer: 1,
                keep_original: 0,
            },
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function (resp) {
            notify('success', (resp && resp.message) || 'Archivos importados.');
            refreshAfterImport($root);
        }).fail(function (xhr) {
            busy($btn, false);
            notifyAjaxError(xhr, 'Error al importar.');
        });
    });

    $(document).on('click', '.docs-inline-device-btn', function () {
        $(this).closest('.docs-inline-import').find('.docs-inline-device-input').trigger('click');
    });

    $(document).on('change', '.docs-inline-device-input', function () {
        var files = this.files;
        var $input = $(this);
        var $card = $input.closest('.docs-inline-import');
        var $root = rootOf($input);
        var $btn = $card.find('.docs-inline-device-btn');
        var convId = currentConversationId();
        var documentId = $root.data('doc-id') || null;
        var fd = new FormData();

        if (!files || !files.length) { return; }
        if (!convId) { notify('warning', 'No hay conversación activa.'); return; }

        var indices = Array.from({ length: files.length }, function (_, i) { return i; });
        var categoryByIndex = categoriesForKeys($card, indices);

        for (var i = 0; i < files.length; i++) {
            fd.append('files[]', files[i]);
            fd.append('categories[]', categoryByIndex[i]);
        }
        fd.append('category', $card.data('import-category') || 'other');
        if (documentId) { fd.append('document_id', documentId); }
        fd.append('notify_customer', '1');

        busy($btn, true, 'Subiendo… <span class="docs-upload-pct">0%</span>');
        $.ajax({
            url: importRoute('url-import-device-tpl', convId),
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            xhr: xhrUploadProgress($btn),
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function (resp) {
            notify('success', (resp && resp.message) || 'Archivos subidos correctamente.');
            refreshAfterImport($root);
        }).fail(function (xhr) {
            busy($btn, false);
            notifyAjaxError(xhr, 'Error al subir.');
        }).always(function () {
            $input.val('');
        });
    });

    $(document).on('click', '.docs-file-detail-back', function () {
        closeFileDetail(rootOf(this));
    });

    $(document).on('click', '.docs-bulk-download', function () {
        var $rp = rootOf(this);
        var $sel = $rp.find('.doc-card.sel');
        if (!$sel.length) { notify('warning', 'Selecciona al menos un documento.'); return; }
        $sel.each(function () {
            var url  = $(this).data('doc-url');
            var name = $(this).data('doc-file-name') || '';
            if (!url) { return; }
            var a = document.createElement('a');
            a.href = url; a.download = name; a.target = '_blank'; a.rel = 'noopener';
            document.body.appendChild(a); a.click(); a.remove();
        });
        exitSelectionMode($rp);
    });

    // ── CTA "Ir a validación" (banner del tab Estado) ────────────
    $(document).on('click', '.docs-goto-validation', function () {
        $(this).closest('.docs-vd-side').find('[data-ws-tab="validacion"]').trigger('click');
    });

    // ── Estados vacíos con acción: enfocar el formulario ────────
    $(document).on('click', '.docs-attach-focus', function () {
        rootOf(this).find('.docs-attach-file').trigger('click');
    });

    // ── Adjuntos: arrastrar y soltar (o clic) para elegir archivo ──
    function showAttachFileName($zone, file) {
        $zone.find('.dz-title').text(file ? file.name : 'Arrastra un archivo aquí');
        $zone.find('.dz-sub').toggleClass('d-none', !!file);
        $zone.find('.dz-file').toggleClass('d-none', !file).text(file ? Math.round(file.size / 1024) + ' KB' : '');
    }

    $(document).on('click', '.docs-attach-dropzone', function () {
        $(this).siblings('.docs-attach-file').trigger('click');
    });

    $(document).on('change', '.docs-attach-file', function () {
        var file = this.files && this.files[0];
        showAttachFileName($(this).siblings('.docs-attach-dropzone'), file);
    });

    $(document).on('dragover', '.docs-attach-dropzone', function (e) {
        e.preventDefault();
        $(this).addClass('dragover');
    });

    $(document).on('dragleave', '.docs-attach-dropzone', function () {
        $(this).removeClass('dragover');
    });

    $(document).on('drop', '.docs-attach-dropzone', function (e) {
        e.preventDefault();
        $(this).removeClass('dragover');
        var files = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files;
        if (!files || !files.length) { return; }
        var $input = $(this).siblings('.docs-attach-file');
        $input[0].files = files;
        showAttachFileName($(this), files[0]);
    });

    // ── Navegación por teclado en la lista de expedientes ────────
    // Activa solo con el tab Documentos visible y sin foco en inputs
    // (la búsqueda conserva su comportamiento normal salvo Esc).
    function visibleDocCards($tab) {
        return $tab.find('.docs-list-card:visible');
    }

    $(document).on('keydown', function (e) {
        var $tab = $('[data-bv-tab-content="document"]').not('.bv-tab-hidden');
        if (!$tab.length) { return; }

        var $target = $(e.target);
        var inInput = $target.is('input, textarea, select') || $target.attr('contenteditable');

        if (e.key === '/' && !inInput) {
            e.preventDefault();
            $tab.find('.docs-list-search').trigger('focus');
            return;
        }

        if (e.key === 'Escape') {
            if ($target.is('.docs-list-search')) { $target.val('').trigger('input').trigger('blur'); return; }
            if (!inInput && $('#docsDetailPanel').hasClass('open')) { window.DocsModal.close('docsDetailPanel'); }
            return;
        }

        if (inInput && !$target.is('.docs-list-search')) { return; }

        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            var $cards = visibleDocCards($tab);
            if (!$cards.length) { return; }
            e.preventDefault();
            var idx = $cards.index($cards.filter('.kb-active'));
            idx = e.key === 'ArrowDown'
                ? Math.min(idx + 1, $cards.length - 1)
                : Math.max(idx - 1, 0);
            $cards.removeClass('kb-active');
            $cards.eq(idx).addClass('kb-active')[0].scrollIntoView({ block: 'nearest' });
            return;
        }

        if (e.key === 'Enter' && !$target.is('.docs-list-search')) {
            var $active = $tab.find('.docs-list-card.kb-active:visible').first();
            if ($active.length) { e.preventDefault(); $active.trigger('click'); }
        }
    });

    // ── Crear expediente desde el inbox ──────────────────────────
    function listView() { return $('[data-bv-tab-content="document"] .docs-list-view'); }

    $(document).on('click', '[data-docs-create]', function () {
        var optionsUrl = listView().data('url-create-options');
        if (!optionsUrl) { return; }
        var $select = $('#docsCreatePanel .docs-create-type').html('<option value="">Cargando…</option>');
        $('#docsCreatePanel .docs-create-ref').val('');
        setCreateMode('assign');
        resetAssignSearch();
        initAssignSearch();
        window.DocsModal.open('docsCreatePanel');
        $.getJSON(optionsUrl, function (resp) {
            $('#docsCreatePanel .docs-create-customer').text((resp.customer && resp.customer.name) || 'el cliente');
            $select.empty().append($('<option value="">— Tipo por defecto —</option>'));
            (resp.types || []).forEach(function (t) {
                $select.append($('<option></option>').val(t.id).text(t.label));
            });
        }).fail(function () {
            $select.html('<option value="">No se pudieron cargar los tipos</option>');
        });
    });

    $(document).on('click', '.docs-create-submit', function () {
        var url = listView().data('url-create');
        if (!url) { return; }
        var $btn = $(this);
        busy($btn, true, 'Creando…');
        $.ajax({
            url: url, method: 'POST',
            data: {
                type_id: $('#docsCreatePanel .docs-create-type').val() || null,
                order_reference: $.trim($('#docsCreatePanel .docs-create-ref').val()) || null,
            },
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function (res) {
            notify('success', (res && res.message) || 'Expediente creado.');
            window.DocsModal.close('docsCreatePanel');
            refreshDocumentsList();
        }).fail(function (xhr) {
            notifyAjaxError(xhr, 'No se pudo crear el expediente.');
        }).always(function () { busy($btn, false); });
    });

    // ── Modo del modal: crear nuevo vs asignar un expediente existente ──
    function setCreateMode(mode) {
        var $m = $('#docsCreatePanel');
        $m.find('[data-docs-create-mode]').each(function () {
            var on = $(this).data('docs-create-mode') === mode;
            $(this).toggleClass('btn-primary', on).toggleClass('btn-outline-secondary', !on);
        });
        $m.find('[data-docs-create-pane]').each(function () {
            $(this).toggleClass('d-none', $(this).data('docs-create-pane') !== mode);
        });
        $m.find('[data-docs-mode-btn]').each(function () {
            $(this).toggleClass('d-none', $(this).data('docs-mode-btn') !== mode);
        });
        $m.find('.docs-minfo').toggleClass('d-none', mode !== 'new');
    }

    $(document).on('click', '#docsCreatePanel [data-docs-create-mode]', function () {
        var mode = $(this).data('docs-create-mode');
        setCreateMode(mode);
        if (mode === 'assign') { initAssignSearch(); }
    });

    // ── Búsqueda AJAX de expedientes existentes (cards seleccionables,
    //    en vez de un <select> select2 mostrando texto plano) ──────────
    var assignSelectedDocId = null;
    var assignSearchTimer = null;

    function resetAssignSearch() {
        assignSelectedDocId = null;
        clearTimeout(assignSearchTimer);
        $('#docsCreatePanel .docs-assign-search').val('');
        $('#docsAssignResults').empty();
    }

    function renderAssignResults(results) {
        var $list = $('#docsAssignResults');
        if (!results.length) {
            $list.html('<div class="docs-opt-empty">Sin resultados</div>');
            return;
        }
        var esc = function (s) { return $('<span>').text(s == null ? '' : String(s)).html(); };
        var html = results.map(function (r) {
            var sub = [r.type_label, r.customer_name, r.customer_email].filter(Boolean).join(' · ');
            return '<button type="button" class="docs-opt" data-doc-id="' + r.id + '">' +
                '<div class="icon"><i class="fa-regular fa-folder-open"></i></div>' +
                '<div class="body">' +
                    '<span class="name">#' + esc(r.order_reference) + '</span>' +
                    (sub ? '<span class="sub">' + esc(sub) + '</span>' : '') +
                '</div>' +
                '<span class="radio"><i class="fas fa-check"></i></span>' +
            '</button>';
        }).join('');
        $list.html(html);
    }

    function initAssignSearch() {
        var $s = $('#docsCreatePanel .docs-assign-search');
        if ($s.data('bound')) { return; }
        $s.data('bound', true);
        $s.on('input', function () {
            var q = $.trim($(this).val());
            assignSelectedDocId = null;
            clearTimeout(assignSearchTimer);

            if (q.length < 2) { $('#docsAssignResults').empty(); return; }

            var searchUrl = listView().data('url-search');
            if (!searchUrl) { return; }

            assignSearchTimer = setTimeout(function () {
                $('#docsAssignResults').html('<div class="docs-opt-empty"><i class="fas fa-spinner fa-spin"></i> Buscando…</div>');
                $.ajax({
                    url: searchUrl, method: 'GET', dataType: 'json',
                    data: { q: q },
                    headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
                }).done(function (data) {
                    renderAssignResults((data && data.results) || []);
                }).fail(function () {
                    $('#docsAssignResults').html('<div class="docs-opt-empty">Error al buscar</div>');
                });
            }, 250);
        });
    }

    $(document).on('click', '#docsAssignResults .docs-opt', function () {
        $('#docsAssignResults .docs-opt').removeClass('on');
        $(this).addClass('on');
        assignSelectedDocId = $(this).data('doc-id');
    });

    $(document).on('click', '.docs-assign-submit', function () {
        if (!assignSelectedDocId) { notify('warning', 'Selecciona un expediente.'); return; }
        var tpl = listView().data('url-link');
        if (!tpl) { return; }
        var $btn = $(this);
        busy($btn, true, 'Asignando…');
        $.ajax({
            url: String(tpl).replace('_DOCID_', assignSelectedDocId), method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function (res) {
            notify('success', (res && res.message) || 'Expediente asignado.');
            window.DocsModal.close('docsCreatePanel');
            refreshDocumentsList();
        }).fail(function (xhr) {
            notifyAjaxError(xhr, 'No se pudo asignar el expediente.');
        }).always(function () { busy($btn, false); });
    });

    // ── Refresco de la lista (tras crear/importar/subir y polling) ─
    function refreshDocumentsList() {
        var $lv = listView();
        var url = $lv.data('url-list');
        if (!url) { return; }
        $.ajax({ url: freshUrl(url), method: 'GET', headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'text/html' } })
            .done(function (html) { $lv.html(html); });
    }

    $(document).on('docs:list:refresh', refreshDocumentsList);
    $(document).on('docs:panel:refresh', function () {
        var $root = $('#docsDetailPanelBody').find('.docs-rp').first();
        if ($root.length) {
            reloadPanel($root);
        }
    });

    // Polling suave: cada 60s solo con el tab visible, sin búsqueda activa y
    // sin el detalle abierto — así el expediente refleja lo que suba el
    // cliente sin pisar lo que esté haciendo el agente.
    setInterval(function () {
        var $tab = $('[data-bv-tab-content="document"]');
        if (!$tab.length || $tab.hasClass('bv-tab-hidden')) { return; }
        if ($('#docsDetailPanel').hasClass('open')) { return; }
        if ($.trim($tab.find('.docs-list-search').val() || '') !== '') { return; }
        refreshDocumentsList();
    }, 60000);

}(window.jQuery));
</script>
@endpush
@endonce
