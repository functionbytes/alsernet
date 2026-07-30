{{-- Detalle de un expediente — cargado via AJAX dentro de #docsDetailPanel.
     Propietario: HelpdeskDocument. Espera $rpDocument (DocumentPanelPresenter::present)
     y $rpConvo (Conversation). Layout 2 columnas: main (info/docs/notas) + side (estado/acciones). --}}
@php
    $doc = $rpDocument;
    $docId          = $doc['id']              ?? null;
    $docUid         = $doc['uid']             ?? null;
    $orderRef       = $doc['order_reference'] ?? '—';
    $typeLabel      = $doc['type_label']      ?? 'Documento';
    $statusLabel    = $doc['status_label']    ?? 'Pendiente';
    $statusKey      = $doc['status_key']      ?? 'pending';
    $validationStatus = $doc['validation_status'] ?? 'pending';
    $stage          = $doc['current_stage']   ?? 1;
    $totalStages    = $doc['total_stages']    ?? 1;
    $custFirstname  = $doc['customer_firstname'] ?? '';
    $custLastname   = $doc['customer_lastname']  ?? '';
    $custName       = $doc['customer_name']   ?? '—';
    $custEmail      = $doc['customer_email']  ?? '';
    $custDni        = $doc['customer_dni']    ?? '—';
    $custPhone      = $doc['customer_phone']  ?? '—';
    $custCompany    = $doc['customer_company'] ?? null;
    // Valores "crudos" (sin el placeholder "—") para prellenar el form de
    // edición inline — si se usara $custDni/$custPhone tal cual, guardar sin
    // tocar el campo grabaría el guion largo como DNI/teléfono real.
    $custDniRaw     = $doc['customer_dni']    ?? '';
    $custPhoneRaw   = $doc['customer_phone']  ?? '';
    $products       = $doc['products']        ?? [];
    $actionHistory  = $doc['actions']          ?? [];
    $docTimeline    = $doc['timeline']         ?? [];
    $files          = $doc['files']           ?? [];
    $missing        = $doc['missing']         ?? [];
    $attachments    = $doc['attachments']     ?? [];
    $notes          = $doc['notes']           ?? [];
    $mails          = $doc['mails']           ?? [];
    $assignedUserId = $doc['assigned_user_id'] ?? null;
    $modalSuffix    = 'doc'.($docId ?? 0);

    $statusBadge = match($statusKey) {
        'approved' => 'docs-status approved',
        'rejected' => 'docs-status rejected',
        default    => 'docs-status pending',
    };

    $canValidate = in_array($validationStatus, ['pending', 'in_validation'], true);

    // Assignees are resolved in DocumentPanelController (no DB queries in the view).
    $assignees = $rpAssignees ?? [];
@endphp

@if($docId)

    {{-- ── Sub-modales (posicion fija, activados via trigger) ──────────── --}}

    <div class="docs-backdrop" data-docs-modal-backdrop-for="docsReject_{{ $modalSuffix }}"></div>
    <div class="docs-modal docs-modal-md" id="docsReject_{{ $modalSuffix }}" data-rp-type="reject" aria-labelledby="docsReject_{{ $modalSuffix }}Label">
        <div class="docs-head">
            <div class="docs-head-icon"><i class="fas fa-circle-xmark"></i></div>
            <div class="docs-head-text">
                <div class="docs-head-label">Documento #{{ $docId }}</div>
                <div class="docs-head-title" id="docsReject_{{ $modalSuffix }}Label">Rechazar etapa {{ $stage }}/{{ $totalStages }}</div>
            </div>
            <button type="button" class="docs-close" aria-label="Cerrar"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="docs-body">
            <p class="text-muted small mb-2">El estado del documento cambiará a Rechazado y se enviará correo al cliente.</p>
            <div class="docs-field">
                <label class="flabel">Motivo del rechazo <span class="hint">min. 10 caracteres</span></label>
                <textarea class="ftextarea docs-reject-reason" rows="3" placeholder="La documentación está incompleta porque..."></textarea>
            </div>
        </div>
        <div class="docs-foot">
            <button type="button" class="btn btn-primary docs-reject-confirm">
                Rechazar documento
            </button>
            <button type="button" class="btn btn-outline-secondary docs-close">Cancelar</button>
        </div>
    </div>

    {{-- Campo oculto para que el JS actualice la etiqueta del header del modal --}}
    <div class="docs-rp-head-text d-none" aria-hidden="true" data-order-ref="{{ $orderRef }}"></div>

    {{-- La confirmación de borrado de archivo es ahora un toast con
         "Deshacer" (mockup Alvarez): el DELETE se difiere unos segundos y el
         agente puede revertirlo sin modal. Ver JS docs-file-del. --}}

    {{-- ── Panel principal (2 columnas) ────────────────────────────────── --}}
    <div class="docs-rp"
         data-doc-id="{{ $docId }}"
         data-doc-uid="{{ $docUid }}"
         data-missing="{{ collect($missing)->map(fn ($m) => ['key' => $m['key'], 'label' => $m['label']])->toJson() }}"
         data-url-panel="{{ route('manager.helpdesk.conversations.documents.panel', [$rpConvo, $docId]) }}"
         data-url-delete-file="{{ rtrim(route('manager.helpdesk.conversations.documents.files.destroy', [$rpConvo, $docId, '_TYPE_']), '/_TYPE_') }}"
         data-url-upload="{{ route('manager.helpdesk.conversations.documents.upload', [$rpConvo, $docId]) }}"
         data-url-assign="{{ route('manager.helpdesk.conversations.documents.assign', [$rpConvo, $docId]) }}"
         data-url-approve="{{ route('manager.helpdesk.conversations.documents.approve-stage', [$rpConvo, $docId]) }}"
         data-url-reject="{{ route('manager.helpdesk.conversations.documents.reject-stage', [$rpConvo, $docId]) }}"
         data-url-send-notify="{{ route('manager.helpdesk.conversations.documents.send-notification', [$rpConvo, $docId]) }}"
         data-url-send-reminder="{{ route('manager.helpdesk.conversations.documents.send-reminder', [$rpConvo, $docId]) }}"
         data-url-send-upload-confirm="{{ route('manager.helpdesk.conversations.documents.send-upload-confirmation', [$rpConvo, $docId]) }}"
         data-url-send-approval="{{ route('manager.helpdesk.conversations.documents.send-approval', [$rpConvo, $docId]) }}"
         data-url-send-missing="{{ route('manager.helpdesk.conversations.documents.send-missing', [$rpConvo, $docId]) }}"
         data-url-send-rej-email="{{ route('manager.helpdesk.conversations.documents.send-rejection', [$rpConvo, $docId]) }}"
         data-url-send-custom="{{ route('manager.helpdesk.conversations.documents.send-custom-email', [$rpConvo, $docId]) }}"
         data-url-notes="{{ route('manager.helpdesk.conversations.documents.notes.add', [$rpConvo, $docId]) }}"
         data-url-upload-attach="{{ route('manager.helpdesk.conversations.documents.upload-attachment', [$rpConvo, $docId]) }}"
         data-url-delete-attach="{{ rtrim(route('manager.helpdesk.conversations.documents.delete-attachment', [$rpConvo, $docId, '_MID_']), '/_MID_') }}"
         data-url-update="{{ route('manager.helpdesk.conversations.documents.update', [$rpConvo, $docId]) }}"
         data-url-action-history="{{ route('manager.helpdesk.conversations.documents.action-history', [$rpConvo, $docId]) }}"
         data-url-download-zip="{{ route('manager.helpdesk.conversations.documents.download-zip', [$rpConvo, $docId]) }}">

        <div class="docs-vd-grid">

            {{-- ═══ Columna principal (izquierda) ═══════════════════════ --}}
            <div class="docs-vd-main">

                {{-- 1. Documentación — galería unificada (subidos + faltantes) --}}
                @php
                    $isImage = fn($mime) => str_starts_with($mime ?? '', 'image/');
                    $isPdf   = fn($mime) => ($mime ?? '') === 'application/pdf';
                    $fileStatusClass = function (string $key): string {
                        return match($key) {
                            'approved', 'completed' => 'approved',
                            'rejected', 'cancelled' => 'rejected',
                            default                  => 'pending',
                        };
                    };
                    $fileExt = fn($name) => strtoupper(pathinfo($name ?? '', PATHINFO_EXTENSION)) ?: '';
                    $docIcon = function(string $docType, string $mime = ''): string {
                        if (str_starts_with($mime, 'image/')) return 'fa-regular fa-id-card';
                        if ($mime === 'application/pdf') return 'fa-regular fa-file-pdf';
                        if (str_contains($docType, 'dni') || str_contains($docType, 'id') ||
                            str_contains($docType, 'pasaporte') || str_contains($docType, 'licencia')) {
                            return 'fa-regular fa-id-card';
                        }
                        return 'fa-regular fa-file-lines';
                    };
                    $totalDocs = count($files) + count($missing);
                @endphp

                @php
                    // Conteo por estado real (para pills de filtro); la barra de
                    // progreso principal usa documentos CARGADOS, no aprobados —
                    // la aprobación es una decisión por etapa sobre todo el
                    // expediente, no un estado independiente por archivo.
                    $galApprovedCount = 0; $galReceivedCount = 0; $galRejectedCount = 0;
                    foreach ($files as $f) {
                        $k = $fileStatusClass($f['status_key'] ?? $f['status'] ?? 'pending');
                        if ($k === 'approved') { $galApprovedCount++; }
                        elseif ($k === 'rejected') { $galRejectedCount++; }
                        else { $galReceivedCount++; }
                    }
                    $galUploadedCount = count($files);
                    $galProgressPct = $totalDocs > 0 ? (int) round(($galUploadedCount / $totalDocs) * 100) : 0;
                @endphp
                <div class="docs-vd-card">
                    <div class="h">
                        <span class="t">Documentación del expediente</span>
                        <span class="s">{{ $totalDocs }} documento(s) · {{ count($missing) }} pendiente(s)</span>
                    </div>
                    <div class="docs-toolbar-row">
                        @if(count($files) > 0)
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-docs-summary-link
                                    title="Ver todos los documentos combinados en un PDF">
                                Visualizar PDF
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-docs-download-zip
                                    title="Descargar todos los documentos en un .zip">
                                Descargar todo
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm docs-select-toggle">
                                Seleccionar
                            </button>
                        @endif
                        @if(count($missing) > 0)
                            <button type="button" class="btn btn-outline-secondary btn-sm docs-open-import">
                                Desde chat
                            </button>
                            <button type="button" class="btn btn-primary btn-sm docs-open-import">
                                Subir
                            </button>
                        @endif
                        @if(count($files) > 0)
                            <div class="dg-toggle ms-auto" data-docs-view-toggle>
                                <button type="button" class="on" data-docs-view="grid" title="Cuadrícula" aria-label="Vista cuadrícula"><i class="fa-solid fa-table-cells-large"></i></button>
                                <button type="button" data-docs-view="list" title="Lista" aria-label="Vista lista"><i class="fa-solid fa-list"></i></button>
                            </div>
                        @endif
                    </div>
                    @if($totalDocs > 0)
                        <div class="docs-gallery-progress">
                            <div class="docs-gallery-progress-track">
                                <div class="docs-gallery-progress-fill" style="--pct: {{ $galProgressPct }}%"></div>
                            </div>
                            <span class="docs-gallery-progress-label">{{ $galUploadedCount }} de {{ $totalDocs }} documento(s) cargado(s)</span>
                        </div>
                        <div class="docs-gallery-toolbar">
                            <div class="docs-gallery-filters">
                                <button type="button" class="docs-pill on" data-gallery-filter="all">Todos <span class="c">{{ $totalDocs }}</span></button>
                                @if($galApprovedCount > 0)
                                    <button type="button" class="docs-pill" data-gallery-filter="approved">Aprobados <span class="c">{{ $galApprovedCount }}</span></button>
                                @endif
                                @if($galReceivedCount > 0)
                                    <button type="button" class="docs-pill" data-gallery-filter="received">Recibidos <span class="c">{{ $galReceivedCount }}</span></button>
                                @endif
                                @if($galRejectedCount > 0)
                                    <button type="button" class="docs-pill" data-gallery-filter="rejected">Rechazados <span class="c">{{ $galRejectedCount }}</span></button>
                                @endif
                                @if(count($missing) > 0)
                                    <button type="button" class="docs-pill" data-gallery-filter="pending">Pendientes <span class="c">{{ count($missing) }}</span></button>
                                @endif
                            </div>
                        </div>
                        {{-- Barra de selección múltiple (visible en modo selección) --}}
                        <div class="docs-bulkbar d-none" data-docs-bulkbar>
                            <span data-docs-bulk-count>0 seleccionados</span>
                            <span class="sp"></span>
                            <button type="button" class="bb-btn docs-bulk-download">Descargar</button>
                            <button type="button" class="bb-btn docs-bulk-cancel">Cancelar</button>
                        </div>

                        <div class="doc-gallery" data-docs-doc-view="grid">
                            {{-- Archivos ya subidos --}}
                            @foreach($files as $f)
                                @php
                                    $fStatusKey   = $fileStatusClass($f['status_key'] ?? $f['status'] ?? 'pending');
                                    // Archivos subidos pero aún no aprobados → "Recibido" (azul)
                                    if ($fStatusKey === 'pending') { $fStatusKey = 'received'; }
                                    $fStatusLabel = match($fStatusKey) {
                                        'approved' => 'Aprobado',
                                        'rejected' => 'Rechazado',
                                        'received' => 'Recibido',
                                        default    => 'Pendiente',
                                    };
                                    $fExt  = $fileExt($f['file_name'] ?? ($f['name'] ?? ''));
                                    $fIcon = $docIcon($f['doc_type'] ?? '', $f['mime'] ?? '');
                                @endphp
                                <div class="doc-card docs-selectable" data-gallery-tag="{{ $fStatusKey }}"
                                     data-doc-url="{{ $f['url'] }}"
                                     data-doc-file-name="{{ $f['file_name'] ?? $f['type_label'] }}"
                                     data-doc-label="{{ $f['type_label'] }}"
                                     data-doc-type="{{ $f['doc_type'] }}"
                                     data-doc-ext="{{ $fExt }}"
                                     data-doc-mime="{{ $f['mime'] ?? '' }}"
                                     data-doc-size="{{ $f['size_human'] ?? '—' }}"
                                     data-doc-uploaded="{{ $f['uploaded_human'] ?? '—' }}"
                                     data-doc-status="{{ $fStatusKey }}"
                                     data-doc-status-label="{{ $fStatusLabel }}"
                                     data-doc-origin="{{ $doc['origin'] ?? '—' }}"
                                     data-doc-system="{{ $doc['system'] ?? '—' }}"
                                     data-doc-uploaded-by="{{ $doc['uploaded_by'] ?? 'Cliente' }}"
                                     data-doc-created="{{ $doc['created_human'] ?? '—' }}"
                                     data-doc-updated="{{ $doc['updated_human'] ?? '—' }}">
                                    <div class="thumb">
                                        @if($isImage($f['mime'] ?? ''))
                                            {{-- Si la miniatura no carga (archivo faltante/404), sustituir por
                                                 el mismo icono Font Awesome que usan los tipos no-imagen, en vez
                                                 del icono nativo de "imagen rota" del navegador. --}}
                                            <img src="{{ $f['url'] }}" alt="{{ $f['type_label'] }}" loading="lazy"
                                                 onerror="this.replaceWith(Object.assign(document.createElement('i'), {className: '{{ $fIcon }}'}))">
                                        @else
                                            <i class="{{ $fIcon }}"></i>
                                        @endif
                                        <span class="doc-check d-none"><i class="fa-solid fa-check"></i></span>
                                        @if($fExt)
                                            <span class="badge-tp">{{ $fExt }}</span>
                                        @endif
                                        <span class="status-pill {{ $fStatusKey }}">{{ $fStatusLabel }}</span>
                                        @if($fStatusKey !== 'approved')
                                            <button type="button" class="doc-del docs-file-del"
                                                    data-doc-type="{{ $f['doc_type'] }}"
                                                    data-doc-label="{{ $f['type_label'] }}">
                                                Eliminar
                                            </button>
                                        @endif
                                    </div>
                                    <div class="info">
                                        <span class="nm">{{ $f['type_label'] }}</span>
                                        <span class="meta">{{ $f['size_human'] ?? '—' }} · {{ $f['uploaded_human'] ?? '—' }}</span>
                                    </div>
                                </div>
                            @endforeach
                            {{-- Faltantes como cards "Pendiente" --}}
                            @foreach($missing as $mm)
                                @php $mmIcon = $docIcon($mm['key'] ?? ''); @endphp
                                <div class="doc-card doc-card-pending" data-gallery-tag="pending"
                                     data-missing-key="{{ $mm['key'] }}"
                                     data-missing-label="{{ $mm['label'] }}">
                                    <div class="thumb">
                                        <i class="{{ $mmIcon }}"></i>
                                        <span class="status-pill pending">Pendiente</span>
                                    </div>
                                    <div class="info">
                                        <span class="nm">{{ $mm['label'] }}</span>
                                        <span class="meta">— · no subido</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Vista compacta (lista) — alternativa a la galería via dg-toggle --}}
                        <div class="doc-list-wrap d-none" data-docs-doc-view="list">
                            @foreach($files as $f)
                                @php
                                    $fStatusKey = $fileStatusClass($f['status_key'] ?? $f['status'] ?? 'pending');
                                    if ($fStatusKey === 'pending') { $fStatusKey = 'received'; }
                                    $fStatusLabel = match($fStatusKey) {
                                        'approved' => 'Aprobado',
                                        'rejected' => 'Rechazado',
                                        default    => 'Recibido',
                                    };
                                    $fExt  = $fileExt($f['file_name'] ?? ($f['name'] ?? ''));
                                    $fIcon = $docIcon($f['doc_type'] ?? '', $f['mime'] ?? '');
                                @endphp
                                <div class="doc-listrow docs-file-row" data-gallery-tag="{{ $fStatusKey }}"
                                     data-doc-url="{{ $f['url'] }}"
                                     data-doc-file-name="{{ $f['file_name'] ?? $f['type_label'] }}"
                                     data-doc-label="{{ $f['type_label'] }}"
                                     data-doc-type="{{ $f['doc_type'] }}"
                                     data-doc-ext="{{ $fExt }}"
                                     data-doc-mime="{{ $f['mime'] ?? '' }}"
                                     data-doc-size="{{ $f['size_human'] ?? '—' }}"
                                     data-doc-uploaded="{{ $f['uploaded_human'] ?? '—' }}"
                                     data-doc-status="{{ $fStatusKey }}"
                                     data-doc-status-label="{{ $fStatusLabel }}"
                                     data-doc-origin="{{ $doc['origin'] ?? '—' }}"
                                     data-doc-system="{{ $doc['system'] ?? '—' }}"
                                     data-doc-uploaded-by="{{ $doc['uploaded_by'] ?? 'Cliente' }}"
                                     data-doc-created="{{ $doc['created_human'] ?? '—' }}"
                                     data-doc-updated="{{ $doc['updated_human'] ?? '—' }}">
                                    <div class="lic"><i class="{{ $fIcon }}"></i></div>
                                    <div class="lb">
                                        <span class="n">{{ $f['type_label'] }}</span>
                                        <span class="m">{{ $fExt ?: '—' }} · {{ $f['size_human'] ?? '—' }} · {{ $f['uploaded_human'] ?? '—' }}</span>
                                    </div>
                                    <span class="docs-status {{ $fStatusKey }}">{{ $fStatusLabel }}</span>
                                </div>
                            @endforeach
                            @foreach($missing as $mm)
                                <div class="doc-listrow is-pending docs-missing-row" data-gallery-tag="pending"
                                     data-missing-key="{{ $mm['key'] }}"
                                     data-missing-label="{{ $mm['label'] }}">
                                    <div class="lic"><i class="{{ $docIcon($mm['key'] ?? '') }}"></i></div>
                                    <div class="lb">
                                        <span class="n">{{ $mm['label'] }}</span>
                                        <span class="m">— · no subido</span>
                                    </div>
                                    <span class="docs-status pending">Pendiente</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="docs-vd-empty">
                            <i class="fa-regular fa-folder-open"></i>
                            No hay documentos requeridos
                            <button type="button" class="btn btn-primary btn-sm mt-2 docs-open-import">
                                Cargar documentos
                            </button>
                        </div>
                    @endif
                    <div class="docs-file-detail d-none" data-docs-file-detail></div>
                </div>

                {{-- Panel desacoplado: correos al cliente e importar documentos. Va
                     FUERA de la tarjeta "Documentación del expediente": son
                     componentes propios que la sustituyen mientras estan abiertos,
                     no secciones suyas. Solo uno puede estar abierto a la vez. --}}
                <div class="docs-detached-pane d-none" data-docs-pane></div>

            </div>

            {{-- ═══ Columna lateral (derecha) — 5 tabs ══════════════════ --}}
            <div class="docs-vd-side">

                @php
                    // Stepper de validación por etapas
                    $stageNodes = [];
                    for ($i = 1; $i <= $totalStages; $i++) {
                        if ($statusKey === 'approved') { $nodeCls = 'done'; }
                        elseif ($statusKey === 'rejected') { $nodeCls = $i < $stage ? 'done' : ($i === $stage ? 'rejected' : 'todo'); }
                        else { $nodeCls = $i < $stage ? 'done' : ($i === $stage ? 'curr' : 'todo'); }
                        $stageNodes[] = [
                            'n' => $i,
                            'cls' => $nodeCls,
                            'showBar' => $i < $totalStages,
                            'barCls' => ($statusKey === 'approved' || $i < $stage) ? 'fill' : ($nodeCls === 'rejected' ? 'rej' : ''),
                        ];
                    }
                    $valBanner = match(true) {
                        $statusKey === 'approved' => 'Todas las etapas validadas correctamente',
                        $statusKey === 'rejected' => 'Validación rechazada en la etapa '.$stage.' / '.$totalStages,
                        default => 'Etapa actual: Pendiente de aprobación',
                    };
                    $valDotCls = $statusKey === 'approved' ? '' : ($statusKey === 'rejected' ? 'rejected' : 'pending');

                    // Comunicaciones automáticas: última fecha de envío por tipo
                    $mailByType = [];
                    foreach ($mails as $m) {
                        if (!isset($mailByType[$m['type']])) { $mailByType[$m['type']] = $m; }
                    }
                    $commDefs = [
                        ['request', 'Solicitud inicial', 'Solicitar documentos al cliente', 'fa-regular fa-paper-plane', 'docs-comm-notify'],
                        ['reminder', 'Recordatorio', 'Reenviar solicitud pendiente', 'fa-regular fa-bell', 'docs-comm-reminder'],
                        ['upload', 'Confirmación de carga', 'Avisar recepción correcta', 'fa-regular fa-circle-check', 'docs-comm-upload-confirm'],
                        ['approval', 'Notificación aprobación', 'Documento aprobado', 'fa-regular fa-thumbs-up', 'docs-comm-approval'],
                    ];
                @endphp

                {{-- Nav de tabs --}}
                <div class="docs-ws-tabs" id="docsWsTabs_{{ $modalSuffix }}" role="tablist" aria-label="Secciones del expediente">
                    <button type="button" class="docs-ws-tab" data-ws-tab="ficha" title="Ficha" role="tab" aria-selected="false" aria-controls="docsWsPane_ficha_{{ $modalSuffix }}" id="docsWsTab_ficha_{{ $modalSuffix }}"><i class="fa-solid fa-address-card"></i><span>Ficha</span></button>
                    <button type="button" class="docs-ws-tab on" data-ws-tab="estado" title="Estado" role="tab" aria-selected="true" aria-controls="docsWsPane_estado_{{ $modalSuffix }}" id="docsWsTab_estado_{{ $modalSuffix }}"><i class="fa-solid fa-circle-info"></i><span>Estado</span></button>
                    <button type="button" class="docs-ws-tab" data-ws-tab="validacion" title="Validación" role="tab" aria-selected="false" aria-controls="docsWsPane_validacion_{{ $modalSuffix }}" id="docsWsTab_validacion_{{ $modalSuffix }}"><i class="fa-solid fa-clipboard-check"></i><span>Validación</span></button>
                    <button type="button" class="docs-ws-tab" data-ws-tab="correos" title="Correos" role="tab" aria-selected="false" aria-controls="docsWsPane_correos_{{ $modalSuffix }}" id="docsWsTab_correos_{{ $modalSuffix }}"><i class="fa-solid fa-envelope"></i><span>Correos</span></button>
                    <button type="button" class="docs-ws-tab" data-ws-tab="notas" title="Notas" role="tab" aria-selected="false" aria-controls="docsWsPane_notas_{{ $modalSuffix }}" id="docsWsTab_notas_{{ $modalSuffix }}"><i class="fa-solid fa-pen-to-square"></i><span>Notas</span></button>
                    <button type="button" class="docs-ws-tab" data-ws-tab="adjuntos" title="Adjuntos" role="tab" aria-selected="false" aria-controls="docsWsPane_adjuntos_{{ $modalSuffix }}" id="docsWsTab_adjuntos_{{ $modalSuffix }}"><i class="fa-solid fa-paperclip"></i><span>Adjuntos</span></button>
                    <button type="button" class="docs-ws-tab" data-ws-tab="historial" title="Historial" role="tab" aria-selected="false" aria-controls="docsWsPane_historial_{{ $modalSuffix }}" id="docsWsTab_historial_{{ $modalSuffix }}"><i class="fa-solid fa-clock-rotate-left"></i><span>Historial</span></button>
                </div>

                {{-- Tab: Ficha — cliente + productos (antes vivían en la columna
                     principal; el mockup Alvarez los ubica en su propia pestaña) --}}
                <div class="docs-ws-pane" data-ws-pane="ficha" role="tabpanel" id="docsWsPane_ficha_{{ $modalSuffix }}" aria-labelledby="docsWsTab_ficha_{{ $modalSuffix }}" tabindex="0">
                    <div class="docs-vd-card">
                        <div class="h h-row">
                            <div class="h-grow">
                                <span class="t">Información del cliente</span>
                                <span class="s">Datos de contacto del cliente</span>
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm docs-cust-edit-toggle">
                                Editar datos
                            </button>
                        </div>
                        <div class="docs-vd-kv docs-cust-view">
                            <div>
                                <div class="k">Nombres</div>
                                <div class="v">{{ $custFirstname ?: ($custName ?: '—') }}</div>
                            </div>
                            <div>
                                <div class="k">Apellidos</div>
                                <div class="v">{{ $custLastname ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="k">DNI/NIE/CIF</div>
                                <div class="v mono">{{ $custDni ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="k">Correo electrónico</div>
                                <div class="v mono docs-break-all">{{ $custEmail ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="k">Teléfono</div>
                                <div class="v mono">{{ $custPhone ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="k">Empresa</div>
                                <div class="v muted">{{ $custCompany ?: '—' }}</div>
                            </div>
                        </div>
                        <div class="docs-cust-edit d-none">
                            <div class="docs-frow">
                                <div class="docs-field">
                                    <label class="flabel">Nombre</label>
                                    <input class="finput" name="firstname" value="{{ $custFirstname }}">
                                </div>
                                <div class="docs-field">
                                    <label class="flabel">Apellidos</label>
                                    <input class="finput" name="lastname" value="{{ $custLastname }}">
                                </div>
                            </div>
                            <div class="docs-frow">
                                <div class="docs-field">
                                    <label class="flabel">DNI/NIE/CIF</label>
                                    <input class="finput" name="identifier" value="{{ $custDniRaw }}">
                                </div>
                                <div class="docs-field">
                                    <label class="flabel">Correo</label>
                                    <input class="finput" name="email" type="email" value="{{ $custEmail }}">
                                </div>
                            </div>
                            <div class="docs-frow">
                                <div class="docs-field">
                                    <label class="flabel">Teléfono</label>
                                    <input class="finput" name="phone" value="{{ $custPhoneRaw }}">
                                </div>
                                <div class="docs-field">
                                    <label class="flabel">Empresa</label>
                                    <input class="finput" name="company" value="{{ $custCompany }}">
                                </div>
                            </div>
                            <div class="docs-btn-row-8">
                                <button type="button" class="btn btn-primary btn-sm flex-fill docs-cust-save">
                                    Guardar cambios
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm flex-fill docs-cust-cancel">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>

                    @if(count($products) > 0)
                    <div class="docs-vd-card">
                        <div class="h">
                            <span class="t">Productos</span>
                            <span class="s">Productos asociados a la orden</span>
                        </div>
                        @foreach(array_slice($products, 0, 4) as $p)
                            <div class="docs-vd-prod-row">
                                <div>
                                    <div class="nm">{{ $p['name'] ?? '—' }}</div>
                                    <div class="sku">{{ $p['sku'] ?? '' }}</div>
                                </div>
                                <div class="qty">{{ $p['qty'] ?? '1 ud' }}</div>
                            </div>
                        @endforeach
                        @if(count($products) > 4)
                            <div class="docs-rp-more">+{{ count($products) - 4 }} más</div>
                        @endif
                    </div>
                    @endif
                </div>

                {{-- Tab: Estado --}}
                <div class="docs-ws-pane on" data-ws-pane="estado" role="tabpanel" id="docsWsPane_estado_{{ $modalSuffix }}" aria-labelledby="docsWsTab_estado_{{ $modalSuffix }}" tabindex="0">

                    {{-- Acción principal contextual (mockup .ws-cta) --}}
                    @if(count($missing) > 0)
                        <div class="docs-cta warn">
                            <div class="ic"><i class="fa-solid fa-triangle-exclamation"></i></div>
                            <div class="tx">
                                <div class="t">Falta{{ count($missing) === 1 ? '' : 'n' }} {{ count($missing) }} documento{{ count($missing) === 1 ? '' : 's' }} requerido{{ count($missing) === 1 ? '' : 's' }}</div>
                                <div class="s">No podrás validar hasta completarlo{{ count($missing) === 1 ? '' : 's' }}</div>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm"
                                    data-docs-email-trigger="missing">
                                Solicitar
                            </button>
                        </div>
                    @elseif($statusKey === 'approved')
                        <div class="docs-cta ok">
                            <div class="ic"><i class="fa-solid fa-circle-check"></i></div>
                            <div class="tx">
                                <div class="t">Documentación completa y aprobada</div>
                                <div class="s">No hay acciones pendientes en este expediente</div>
                            </div>
                        </div>
                    @elseif(count($files) > 0 && $canValidate)
                        <div class="docs-cta">
                            <div class="ic"><i class="fa-solid fa-clipboard-check"></i></div>
                            <div class="tx">
                                <div class="t">Documentación completa</div>
                                <div class="s">Lista para revisar y validar la etapa {{ $stage }} / {{ $totalStages }}</div>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm docs-goto-validation">
                                Ir a validación
                            </button>
                        </div>
                    @endif

                    {{-- Checklist de documentos requeridos (mockup .req-item) --}}
                    @if(count($files) > 0 || count($missing) > 0)
                        <div class="docs-vd-card">
                            <div class="h h-row">
                                <div class="h-grow">
                                    <span class="t">Documentos requeridos</span>
                                    <span class="s">{{ count($files) }} de {{ $totalDocs }} completado{{ $totalDocs === 1 ? '' : 's' }}</span>
                                </div>
                            </div>
                            <div class="docs-req-list">
                                @foreach($files as $f)
                                    @php $fReqKey = $fileStatusClass($f['status_key'] ?? 'pending'); @endphp
                                    <div class="docs-req-item">
                                        <span class="docs-req-ic done"><i class="fa-solid fa-check"></i></span>
                                        <span class="docs-req-nm">{{ $f['type_label'] }}</span>
                                        <span class="docs-req-tag done">{{ $fReqKey === 'approved' ? 'Aprobado' : 'Recibido' }}</span>
                                    </div>
                                @endforeach
                                @foreach($missing as $mm)
                                    <div class="docs-req-item pending">
                                        <span class="docs-req-ic miss"><i class="fa-solid fa-clock"></i></span>
                                        <span class="docs-req-nm">{{ $mm['label'] }}</span>
                                        <button type="button" class="docs-req-link docs-req-import"
                                                data-missing-key="{{ $mm['key'] }}"
                                                data-missing-label="{{ $mm['label'] }}">
                                            Cargar
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="docs-vd-card">
                        <div class="h h-row">
                            <span class="t">Estado del expediente</span>
                        </div>
                        <div class="docs-vd-status-banner">
                            <span class="{{ $statusBadge }} docs-vd-status-banner-badge">{{ $statusLabel }}</span>
                            <span class="docs-vd-status-banner-stage">Etapa <b class="docs-vd-status-banner-stage-value">{{ $stage }} / {{ $totalStages }}</b></span>
                        </div>
                        <div class="docs-vd-status-list">
                            <div class="docs-vd-status-row"><span class="k">Tipo</span><span class="v">{{ $typeLabel }}</span></div>
                            <div class="docs-vd-status-row">
                                <span class="k">Cliente</span>
                                <span class="v">{{ $custName ?: '—' }}</span>
                                <button type="button" class="docs-req-link docs-goto-cust-edit">Editar</button>
                            </div>
                            <div class="docs-vd-status-row"><span class="k">Orden</span><span class="v mono docs-vd-order-ref">{{ $orderRef }}</span></div>
                            @if($assignedUserId)
                                <div class="docs-vd-status-row"><span class="k">Asignado</span><span class="v">Usuario #{{ $assignedUserId }}</span></div>
                            @endif
                        </div>
                    </div>

                    <div class="docs-vd-card">
                        <div class="h">
                            <span class="t">Metadatos</span>
                            <span class="s">Información técnica</span>
                        </div>
                        <div class="docs-vd-meta-block">
                            <div class="item"><span class="k">UUID</span><span class="v">{{ $docUid }}</span></div>
                            <div class="item"><span class="k">Origen</span><span class="v">{{ $doc['origin'] ?? '—' }}</span></div>
                            <div class="item"><span class="k">Sistema</span><span class="v">{{ $doc['system'] ?? '—' }}</span></div>
                            <div class="item"><span class="k">Creado</span><span class="v">{{ $doc['created_human'] ?? '—' }}</span></div>
                            <div class="item"><span class="k">Actualizado</span><span class="v">{{ $doc['updated_human'] ?? '—' }}</span></div>
                        </div>
                    </div>
                </div>

                {{-- Tab: Validación --}}
                <div class="docs-ws-pane" data-ws-pane="validacion" role="tabpanel" id="docsWsPane_validacion_{{ $modalSuffix }}" aria-labelledby="docsWsTab_validacion_{{ $modalSuffix }}" tabindex="0">
                    <div class="docs-vd-card">
                        <div class="h h-row">
                            <div class="h-grow">
                                <span class="t">Validación por etapas</span>
                                <span class="s">Etapa {{ $stage }} / {{ $totalStages }}</span>
                            </div>
                        </div>
                        {{-- Stepper con etiquetas y estado por etapa (mockup .wv-steps) --}}
                        <div class="docs-wv-steps">
                            @foreach($stageNodes as $st)
                                @php
                                    $wvState = in_array($st['cls'], ['done', 'curr', 'rejected'], true) ? $st['cls'] : 'todo';
                                    $wvSub = match($wvState) {
                                        'done' => 'Completada',
                                        'curr' => 'En curso',
                                        'rejected' => 'Rechazada',
                                        default => 'Pendiente',
                                    };
                                @endphp
                                <div class="docs-wv-step {{ $wvState }}">
                                    <div class="docs-wv-node">
                                        @if($wvState === 'done')
                                            <i class="fa-solid fa-check"></i>
                                        @elseif($wvState === 'rejected')
                                            <i class="fa-solid fa-xmark"></i>
                                        @else
                                            {{ $st['n'] }}
                                        @endif
                                    </div>
                                    <div class="docs-wv-lbl">Etapa {{ $st['n'] }}</div>
                                    <div class="docs-wv-sub">{{ $wvSub }}</div>
                                </div>
                            @endforeach
                        </div>
                        <div class="docs-validation"><span class="dot {{ $valDotCls }}"></span> {{ $valBanner }}</div>

                        @if($canValidate && count($files) > 0)
                            @if(count($missing) > 0)
                                <div class="docs-cta warn">
                                    <div class="ic"><i class="fa-solid fa-lock"></i></div>
                                    <div class="tx">
                                        <div class="t">Validación bloqueada</div>
                                        <div class="s">Falta «{{ $missing[0]['label'] }}»{{ count($missing) > 1 ? ' y '.(count($missing) - 1).' más' : '' }} para poder aprobar</div>
                                    </div>
                                </div>
                            @endif
                            <div class="docs-field">
                                <label class="flabel">Responsable de validación</label>
                                <select class="fselect docs-assignee">
                                    <option value="">Sin asignar</option>
                                    @foreach($assignees as $u)
                                        <option value="{{ $u['id'] }}" @selected($u['id'] == $assignedUserId)>{{ $u['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="docs-btn-row-8">
                                <button type="button" class="btn btn-primary btn-sm flex-fill docs-approve"
                                        @if(count($missing) > 0) disabled title="Completa la documentación requerida antes de aprobar" @endif>
                                    Aprobar etapa
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm flex-fill docs-reject"
                                        data-docs-modal-trigger="docsReject_{{ $modalSuffix }}">
                                    Rechazar
                                </button>
                            </div>
                        @elseif(!$canValidate)
                            @php
                                $resolutionEntry = collect($docTimeline)->first(fn ($t) => in_array($t['dot'] ?? '', ['ok', 'rej'], true));
                                $wasApproved = $validationStatus === 'approved';
                            @endphp
                            <div class="docs-cta {{ $wasApproved ? 'ok' : 'warn' }}">
                                <div class="ic">
                                    <i class="fa-solid {{ $wasApproved ? 'fa-circle-check' : 'fa-circle-xmark' }}"></i>
                                </div>
                                <div class="tx">
                                    <div class="t">{{ $wasApproved ? 'Etapa aprobada' : 'Etapa rechazada' }}</div>
                                    <div class="s">{{ $resolutionEntry['sub'] ?? $statusLabel }}</div>
                                </div>
                            </div>
                        @else
                            <div class="docs-vd-empty">
                                <i class="fa-regular fa-circle-question"></i>
                                Sin documentos subidos para validar
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Tab: Correos --}}
                <div class="docs-ws-pane" data-ws-pane="correos" role="tabpanel" id="docsWsPane_correos_{{ $modalSuffix }}" aria-labelledby="docsWsTab_correos_{{ $modalSuffix }}" tabindex="0">
                    <div class="docs-ws-pane-scroll">
                        <div class="docs-vd-card">
                            <div class="h h-row">
                                <div class="h-grow">
                                    <span class="t">Acciones de envío</span>
                                    <span class="s">Comunicaciones automáticas</span>
                                </div>
                            </div>
                            @foreach($commDefs as [$mailType, $cmName, $cmDesc, $cmIcon, $cmClass])
                                @php $sentMail = $mailByType[$mailType] ?? null; @endphp
                                <div class="docs-action-row docs-action-row--comm">
                                    <div class="docs-ws-comm-ic {{ $sentMail ? 'on' : '' }}"><i class="{{ $cmIcon }}"></i></div>
                                    <div class="body"><div class="nm">{{ $cmName }}</div><div class="ds">{{ $cmDesc }}</div></div>
                                    {{-- Ya enviado: se conserva la marca, pero se permite reenviar.
                                         Antes la fila se quedaba sin acción y no habia forma de repetir
                                         el aviso si el cliente decia no haberlo recibido. --}}
                                    @if($sentMail)
                                        {{-- Sin marca "Enviado": la propia palabra "Reenviar" ya dice que salió
                                             una vez. La fecha exacta vive en el tooltip. --}}
                                        <button type="button" class="btn btn-outline-secondary btn-sm {{ $cmClass }}"
                                                title="Enviado el {{ $sentMail['sent_at'] ?? '—' }} · volver a enviar">
                                            <i class="fa-solid fa-rotate-right"></i> Reenviar
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-outline-secondary btn-sm {{ $cmClass }}">Enviar</button>
                                    @endif
                                </div>
                            @endforeach

                            <div class="docs-action-divider"></div>

                            @if(count($missing) > 0)
                                <div class="docs-action-row docs-action-row--comm">
                                    <div class="docs-ws-comm-ic"><i class="fa-regular fa-envelope"></i></div>
                                    <div class="body"><div class="nm">Solicitar documentos</div><div class="ds">Pedir al cliente lo que falta</div></div>
                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                            data-docs-email-trigger="missing">
                                        Redactar
                                    </button>
                                </div>
                            @endif
                            <div class="docs-action-row docs-action-row--comm">
                                <div class="docs-ws-comm-ic"><i class="fa-regular fa-circle-xmark"></i></div>
                                <div class="body"><div class="nm">Correo de rechazo</div><div class="ds">Notificar rechazo de documentación</div></div>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                        data-docs-email-trigger="rejection">
                                    Redactar
                                </button>
                            </div>
                            <div class="docs-action-row docs-action-row--comm">
                                <div class="docs-ws-comm-ic"><i class="fa-regular fa-pen-to-square"></i></div>
                                <div class="body"><div class="nm">Correo personalizado</div><div class="ds">Mensaje libre al cliente</div></div>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                        data-docs-email-trigger="custom">
                                    Redactar
                                </button>
                            </div>
                        </div>

                        @if(count($mails) > 0)
                            <div class="docs-vd-card">
                                <div class="h"><span class="t">Correos enviados</span><span class="s">{{ count($mails) }}</span></div>
                                {{-- Cada correo abre su vista previa, igual que en la pantalla
                                     de gestión: antes la lista era texto muerto y no había forma
                                     de comprobar qué se le envió al cliente. --}}
                                @foreach($mails as $mail)
                                    <a class="docs-mail-item" target="_blank" rel="noopener"
                                       href="{{ route('documents.emails.preview', $mail['uid']) }}"
                                       title="Ver el correo enviado">
                                        <span class="docs-mail-type">{{ $mail['type_label'] }}</span>
                                        <span class="docs-mail-subject">{{ $mail['subject'] }}</span>
                                        <span class="docs-mail-ts">{{ $mail['sent_at'] }}</span>
                                        <i class="fa-solid fa-arrow-up-right-from-square docs-mail-go"></i>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Tab: Notas --}}
                <div class="docs-ws-pane" data-ws-pane="notas" role="tabpanel" id="docsWsPane_notas_{{ $modalSuffix }}" aria-labelledby="docsWsTab_notas_{{ $modalSuffix }}" tabindex="0">
                    <div class="docs-ws-pane-scroll">
                        <div class="docs-vd-card">
                            <div class="h h-row">
                                <div class="h-grow">
                                    <span class="t">Notas del expediente</span>
                                    <span class="s">Anotaciones internas del equipo</span>
                                </div>
                            </div>
                            <div class="docs-notes-list mb-2">
                                @forelse($notes as $n)
                                    <div class="docs-note-item">
                                        <div class="docs-note-header">
                                            <span class="docs-note-ts">{{ $n['ts'] }}</span>
                                            <button type="button" class="btn-icon docs-note-del"
                                                    aria-label="Eliminar nota"
                                                    data-url="{{ route('manager.helpdesk.conversations.documents.notes.delete', [$rpConvo, $docId, $n['id']]) }}">
                                                <i class="fas fa-xmark"></i>
                                            </button>
                                        </div>
                                        <div class="docs-note-text">{{ $n['text'] }}</div>
                                    </div>
                                @empty
                                    <div class="docs-es-empty">
                                        <div class="eic"><i class="fa-regular fa-note-sticky"></i></div>
                                        <div>
                                            <div class="et">Sin notas todavía</div>
                                            <div class="esb">Añade anotaciones internas para el resto del equipo.</div>
                                        </div>
                                    </div>
                                @endforelse
                            </div>
                            <div class="docs-field">
                                <textarea class="ftextarea docs-note-input" rows="3"
                                          placeholder="Añadir nota interna…"></textarea>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm w-100 docs-note-submit">
                                Agregar nota
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Tab: Adjuntos — archivos internos, no visibles para el cliente
                     (antes vivían dentro del tab Estado; el mockup Alvarez los
                     separa en su propia pestaña, icono de clip) --}}
                <div class="docs-ws-pane" data-ws-pane="adjuntos" role="tabpanel" id="docsWsPane_adjuntos_{{ $modalSuffix }}" aria-labelledby="docsWsTab_adjuntos_{{ $modalSuffix }}" tabindex="0">
                    <div class="docs-vd-card">
                        <div class="h">
                            <span class="t">Adjuntos internos</span>
                            <span class="s">{{ count($attachments) }}</span>
                        </div>
                        <div class="docs-attach-list">
                            @forelse($attachments as $a)
                                <div class="docs-attach-item">
                                    <a href="{{ $a['url'] }}" target="_blank" rel="noopener" class="docs-attach-link">{{ $a['name'] }}</a>
                                    <span class="docs-attach-meta">{{ $a['size_human'] }}</span>
                                    <button type="button" class="btn-icon docs-attach-del"
                                            aria-label="Eliminar adjunto"
                                            data-url="{{ route('manager.helpdesk.conversations.documents.delete-attachment', [$rpConvo, $docId, $a['id']]) }}">
                                        <i class="fas fa-xmark"></i>
                                    </button>
                                </div>
                            @empty
                                <div class="docs-es-empty">
                                    <div class="eic"><i class="fa-solid fa-paperclip"></i></div>
                                    <div>
                                        <div class="et">Sin adjuntos internos</div>
                                        <div class="esb">Sube documentos de uso interno, no visibles para el cliente.</div>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm docs-attach-focus">
                                        Añadir adjunto
                                    </button>
                                </div>
                            @endforelse
                        </div>
                        <form class="docs-attach-form mt-2">
                            <input type="file" class="d-none docs-attach-file"
                                   accept="image/*,application/pdf,.doc,.docx,.xls,.xlsx">
                            <div class="docs-attach-dropzone">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <div class="dz-title">Arrastra un archivo aquí</div>
                                <div class="dz-sub">o haz clic para seleccionarlo</div>
                                <div class="dz-file d-none"></div>
                            </div>
                            <textarea class="form-control form-control-sm mt-1 docs-attach-notes" rows="1"
                                      placeholder="Nota sobre este adjunto"></textarea>
                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 mt-1 docs-attach-submit">
                                Subir adjunto
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Tab: Historial — timeline unificada (acciones + correos) --}}
                <div class="docs-ws-pane" data-ws-pane="historial" role="tabpanel" id="docsWsPane_historial_{{ $modalSuffix }}" aria-labelledby="docsWsTab_historial_{{ $modalSuffix }}" tabindex="0">
                    <div class="docs-ws-pane-scroll">
                        <div class="docs-vd-card">
                            <div class="h h-row">
                                <div class="h-grow">
                                    <span class="t">Actividad del expediente</span>
                                    <span class="s">Acciones y correos en orden cronológico</span>
                                </div>
                            </div>
                            <div class="docs-utl docs-timeline">
                                @forelse($docTimeline as $tl)
                                    <div class="docs-utl-item">
                                        <div class="docs-utl-dot {{ $tl['dot'] }}"></div>
                                        <div class="docs-utl-lbl">
                                            {{ $tl['label'] }}
                                            @if($tl['kind'] === 'mail')
                                                <span class="docs-utl-chip"><i class="fa-regular fa-envelope"></i> Correo</span>
                                            @endif
                                        </div>
                                        <div class="docs-utl-sub">{{ $tl['sub'] }}</div>
                                    </div>
                                @empty
                                    <div class="docs-vd-empty">
                                        <i class="fa-regular fa-clock"></i>
                                        Sin actividad registrada
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            {{-- /docs-vd-side --}}
        </div>{{-- /docs-vd-grid --}}
    </div>{{-- /docs-rp --}}

@endif
