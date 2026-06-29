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
    $products       = $doc['products']        ?? [];
    $actionHistory  = $doc['actions']          ?? [];
    $files          = $doc['files']           ?? [];
    $missing        = $doc['missing']         ?? [];
    $attachments    = $doc['attachments']     ?? [];
    $notes          = $doc['notes']           ?? [];
    $mails          = $doc['mails']           ?? [];
    $assignedUserId = $doc['assigned_user_id'] ?? null;
    $modalSuffix    = $docId ? 'doc'.$docId : 'demo';

    $statusBadge = match($statusKey) {
        'approved' => 'docs-status approved',
        'rejected' => 'docs-status rejected',
        default    => 'docs-status pending',
    };

    $canValidate = in_array($validationStatus, ['pending', 'in_validation'], true);

    $assignees = [];
    try {
        $assignees = \Modules\Document\Entities\DocumentValidatorGroup::with('users')->get()
            ->pluck('users')->flatten()->unique('id')
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name ?? $u->full_name ?? ('Usuario #'.$u->id)])
            ->values()->all();
    } catch (\Throwable $e) {}
@endphp

@if($docId)

    {{-- ── Sub-modales (posicion fija, activados via trigger) ──────────── --}}

    <div class="docs-backdrop" data-docs-modal-backdrop-for="docsReject_{{ $modalSuffix }}"></div>
    <div class="docs-modal docs-modal-md" id="docsReject_{{ $modalSuffix }}" data-rp-type="reject">
        <div class="docs-head">
            <div class="docs-head-icon"><i class="fas fa-circle-xmark"></i></div>
            <div class="docs-head-text">
                <div class="docs-head-label">Documento #{{ $docId }}</div>
                <div class="docs-head-title">Rechazar etapa {{ $stage }}/{{ $totalStages }}</div>
            </div>
            <button class="docs-close"><i class="fas fa-xmark"></i></button>
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
                <i class="fas fa-circle-xmark me-1"></i> Rechazar documento
            </button>
            <button type="button" class="btn btn-outline-secondary docs-close">Cancelar</button>
        </div>
    </div>

    <div class="docs-backdrop" data-docs-modal-backdrop-for="docsMissing_{{ $modalSuffix }}"></div>
    <div class="docs-modal docs-modal-md" id="docsMissing_{{ $modalSuffix }}" data-rp-type="missing">
        <div class="docs-head">
            <div class="docs-head-icon"><i class="fas fa-triangle-exclamation"></i></div>
            <div class="docs-head-text">
                <div class="docs-head-label">Correo al cliente</div>
                <div class="docs-head-title">Solicitar documentos faltantes</div>
            </div>
            <button class="docs-close"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="docs-body">
            <p class="text-muted small mb-2">Selecciona los documentos que el cliente debe enviar.</p>
            @if(count($missing))
                @foreach($missing as $mm)
                    <label class="docs-check mb-1">
                        <input type="checkbox" class="docs-missing-cb" value="{{ $mm['key'] }}" checked>
                        {{ $mm['label'] }}
                    </label>
                @endforeach
            @else
                <p class="text-muted small">No hay documentos faltantes registrados.</p>
            @endif
            <div class="docs-field mt-2">
                <label class="flabel">Nota adicional <span class="hint">opcional</span></label>
                <textarea class="ftextarea docs-missing-notes" rows="2" placeholder="Recuerda incluir foto con ambos lados..."></textarea>
            </div>
        </div>
        <div class="docs-foot">
            <button type="button" class="btn btn-primary docs-missing-send">
                <i class="fas fa-paper-plane me-1"></i> Enviar solicitud
            </button>
            <button type="button" class="btn btn-outline-secondary docs-close">Cancelar</button>
        </div>
    </div>

    <div class="docs-backdrop" data-docs-modal-backdrop-for="docsRejEmail_{{ $modalSuffix }}"></div>
    <div class="docs-modal docs-modal-md" id="docsRejEmail_{{ $modalSuffix }}" data-rp-type="rej-email">
        <div class="docs-head">
            <div class="docs-head-icon"><i class="fas fa-envelope-circle-check"></i></div>
            <div class="docs-head-text">
                <div class="docs-head-label">Correo al cliente</div>
                <div class="docs-head-title">Correo de rechazo</div>
            </div>
            <button class="docs-close"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="docs-body">
            <div class="docs-field">
                <label class="flabel">Motivo del rechazo <span class="hint">min. 10 caracteres</span></label>
                <textarea class="ftextarea docs-rej-email-reason" rows="3" placeholder="El documento enviado no es legible..."></textarea>
            </div>
            @if(count($files))
                <div class="docs-field mt-2">
                    <label class="flabel">Documentos rechazados <span class="hint">opcional</span></label>
                    @foreach($files as $f)
                        <label class="docs-check mb-1">
                            <input type="checkbox" class="docs-rej-doc-cb" value="{{ $f['doc_type'] ?? $f['file_name'] }}">
                            {{ $f['type_label'] }}
                        </label>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="docs-foot">
            <button type="button" class="btn btn-primary docs-rej-email-send">
                <i class="fas fa-paper-plane me-1"></i> Enviar correo de rechazo
            </button>
            <button type="button" class="btn btn-outline-secondary docs-close">Cancelar</button>
        </div>
    </div>

    <div class="docs-backdrop" data-docs-modal-backdrop-for="docsCustomEmail_{{ $modalSuffix }}"></div>
    <div class="docs-modal docs-modal-md" id="docsCustomEmail_{{ $modalSuffix }}" data-rp-type="custom-email">
        <div class="docs-head">
            <div class="docs-head-icon"><i class="fas fa-envelope"></i></div>
            <div class="docs-head-text">
                <div class="docs-head-label">Correo al cliente</div>
                <div class="docs-head-title">Correo personalizado</div>
            </div>
            <button class="docs-close"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="docs-body">
            <div class="docs-field">
                <label class="flabel">Asunto</label>
                <input type="text" class="finput docs-custom-subject" placeholder="Información sobre su documentación">
            </div>
            <div class="docs-field mt-2">
                <label class="flabel">Mensaje <span class="hint">min. 10 caracteres</span></label>
                <textarea class="ftextarea docs-custom-message" rows="4" placeholder="Estimado cliente, le informamos que..."></textarea>
            </div>
        </div>
        <div class="docs-foot">
            <button type="button" class="btn btn-primary docs-custom-email-send">
                <i class="fas fa-paper-plane me-1"></i> Enviar correo
            </button>
            <button type="button" class="btn btn-outline-secondary docs-close">Cancelar</button>
        </div>
    </div>

    {{-- Campos ocultos para que el JS actualice el header del modal --}}
    <div class="docs-rp-head-text" style="display:none" aria-hidden="true">
        <div class="docs-rp-head-label">Documento #{{ $docId }}</div>
        <div class="docs-rp-head-title">{{ $typeLabel }}</div>
    </div>

    {{-- Modal confirmacion eliminar archivo --}}
    <div class="docs-backdrop" data-docs-modal-backdrop-for="docsFileDelConfirm_{{ $modalSuffix }}"></div>
    <div class="docs-modal" id="docsFileDelConfirm_{{ $modalSuffix }}" data-rp-type="file-del-confirm">
        <div class="docs-head">
            <div class="docs-head-icon"><i class="fas fa-trash"></i></div>
            <div class="docs-head-text">
                <div class="docs-head-label">Eliminar archivo</div>
                <div class="docs-head-title">¿Confirmar eliminación?</div>
            </div>
            <button class="docs-close"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="docs-body">
            <p class="text-muted small mb-0">Se eliminará el archivo cargado. Podrás subir uno nuevo desde la sección "Cargar documentación".</p>
        </div>
        <div class="docs-foot">
            <button type="button" class="btn btn-primary docs-confirm-file-del-ok">
                <i class="fas fa-trash me-1"></i> Eliminar archivo
            </button>
            <button type="button" class="btn btn-outline-secondary docs-close">Cancelar</button>
        </div>
    </div>

    {{-- ── Panel principal (2 columnas) ────────────────────────────────── --}}
    <div class="docs-rp"
         data-doc-uid="{{ $docUid }}"
         data-url-panel="{{ route('manager.helpdesk.conversations.documents.panel', [$rpConvo, $docId]) }}"
         data-url-delete-file="{{ rtrim(route('manager.helpdesk.conversations.documents.files.destroy', [$rpConvo, $docId, '_TYPE_']), '/_TYPE_') }}"
         data-url-upload="{{ route('api.documents.upload', $docUid) }}"
         data-url-assign="{{ route('api.documents.assign', $docUid) }}"
         data-url-approve="{{ route('api.documents.approve-stage', $docUid) }}"
         data-url-reject="{{ route('api.documents.reject-stage', $docUid) }}"
         data-url-send-notify="{{ route('api.documents.send-notification', $docUid) }}"
         data-url-send-reminder="{{ route('api.documents.send-reminder', $docUid) }}"
         data-url-send-upload-confirm="{{ route('api.documents.send-upload-confirmation', $docUid) }}"
         data-url-send-approval="{{ route('api.documents.send-approval', $docUid) }}"
         data-url-send-missing="{{ route('api.documents.send-missing', $docUid) }}"
         data-url-send-rej-email="{{ route('api.documents.send-rejection', $docUid) }}"
         data-url-send-custom="{{ route('api.documents.send-custom-email', $docUid) }}"
         data-url-notes="{{ route('api.documents.notes.add', $docUid) }}"
         data-url-upload-attach="{{ route('api.documents.upload-attachment', $docUid) }}"
         data-url-delete-attach="{{ url('api/documents/'.$docUid.'/delete-attachment') }}"
         data-url-update="{{ route('api.documents.update') }}"
         data-url-action-history="{{ route('api.documents.action-history', $docUid) }}"
         data-url-download-zip="{{ route('api.documents.download-zip', $docUid) }}">

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
                <div class="docs-vd-card">
                    <div class="h">
                        <span class="t">Documentación del expediente</span>
                        <span class="s">{{ count($files) }} subido(s) · {{ count($missing) }} pendiente(s)</span>
                    </div>
                    @if($totalDocs > 0)
                        <div class="doc-gallery">
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
                                <div class="doc-card" onclick="window.open('{{ $f['url'] }}','_blank')">
                                    <div class="thumb">
                                        @if($isImage($f['mime'] ?? ''))
                                            <img src="{{ $f['url'] }}" alt="{{ $f['type_label'] }}" loading="lazy">
                                        @else
                                            <i class="{{ $fIcon }}"></i>
                                        @endif
                                        @if($fExt)
                                            <span class="badge-tp">{{ $fExt }}</span>
                                        @endif
                                        <span class="status-pill {{ $fStatusKey }}">{{ $fStatusLabel }}</span>
                                        <button type="button" class="doc-del docs-file-del"
                                                data-doc-type="{{ $f['doc_type'] }}"
                                                data-modal-confirm="docsFileDelConfirm_{{ $modalSuffix }}"
                                                title="Eliminar"
                                                onclick="event.stopPropagation()">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
                                <div class="doc-card doc-card-pending">
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
                    @else
                        <div class="docs-vd-empty">
                            <i class="fa-regular fa-folder-open"></i>
                            No hay documentos requeridos
                        </div>
                    @endif
                </div>

                {{-- 3. Cargar documentación faltante --}}
                @if(count($missing))
                    <div class="docs-vd-card">
                        <div class="h">
                            <span class="t">Cargar documentación</span>
                            <span class="s">{{ count($missing) }} pendiente(s)</span>
                        </div>
                        <form class="docs-upload-form">
                            @foreach($missing as $mm)
                                <label class="docs-up-row">
                                    <span class="docs-up-label">{{ $mm['label'] }}</span>
                                    <input type="file" class="form-control form-control-sm"
                                           name="documents[{{ $mm['key'] }}]"
                                           accept="image/*,application/pdf">
                                </label>
                            @endforeach
                            <button type="button" class="btn btn-primary btn-sm w-100 mt-2 docs-up-submit">
                                <i class="fas fa-cloud-arrow-up me-1"></i> Subir archivos
                            </button>
                        </form>
                    </div>
                @endif

            </div>

            {{-- ═══ Columna lateral (derecha) ════════════════════════════ --}}
            <div class="docs-vd-side">

                {{-- A. Estado del documento --}}
                <div class="docs-vd-card">
                    <div class="h">
                        <span class="t">Estado</span>
                    </div>
                    <div class="docs-vd-status-list">
                        <div class="docs-vd-status-row"><span class="k">Tipo</span><span class="v">{{ $typeLabel }}</span></div>
                        <div class="docs-vd-status-row"><span class="k">Estado</span><span class="v"><span class="{{ $statusBadge }}">{{ $statusLabel }}</span></span></div>
                        <div class="docs-vd-status-row"><span class="k">Etapa</span><span class="v">{{ $stage }} / {{ $totalStages }}</span></div>
                    </div>
                </div>

                {{-- B. Validacion (aprobar/rechazar) — solo si hay archivos subidos --}}
                @if($canValidate && count($files) > 0)
                    <div class="docs-vd-card">
                        <div class="h"><span class="t">Validación · etapa {{ $stage }}/{{ $totalStages }}</span></div>
                        <select class="form-select form-select-sm mb-2 docs-assignee">
                            <option value="">Sin asignar</option>
                            @foreach($assignees as $u)
                                <option value="{{ $u['id'] }}" @selected($u['id'] == $assignedUserId)>{{ $u['name'] }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100 mb-2 docs-assign">
                            <i class="fas fa-user-check me-1"></i> Asignar
                        </button>
                        <button type="button" class="btn btn-primary btn-sm w-100 mb-2 docs-approve">
                            <i class="fas fa-check me-1"></i> Aprobar etapa
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100 docs-reject"
                                data-docs-modal-trigger="docsReject_{{ $modalSuffix }}">
                            <i class="fas fa-xmark me-1"></i> Rechazar
                        </button>
                    </div>
                @endif

                {{-- D. Adjuntos internos --}}
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
                                        data-url="{{ url('api/documents/'.$docUid.'/delete-attachment/'.$a['id']) }}">
                                    <i class="fas fa-xmark"></i>
                                </button>
                            </div>
                        @empty
                            <div class="docs-vd-empty" style="padding:8px 0">
                                <i class="fa-regular fa-paperclip"></i> Sin adjuntos internos
                            </div>
                        @endforelse
                    </div>
                    <form class="docs-attach-form mt-2">
                        <input type="file" class="form-control form-control-sm docs-attach-file"
                               accept="image/*,application/pdf,.doc,.docx,.xls,.xlsx">
                        <textarea class="form-control form-control-sm mt-1 docs-attach-notes" rows="1"
                                  placeholder="Notas opcionales sobre este adjunto"></textarea>
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100 mt-1 docs-attach-submit">
                            <i class="fas fa-cloud-arrow-up me-1"></i> Subir adjunto
                        </button>
                    </form>
                </div>

                {{-- E. Acciones --}}
                <div class="docs-vd-card">
                    <div class="h"><span class="t">Acciones</span></div>
                    <div class="docs-rp-actions">
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100"
                                data-docs-modal-trigger="docsViewer_{{ $modalSuffix }}">
                            <i class="fa-regular fa-folder-open me-1"></i> Visualizar documentación
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100"
                                data-docs-modal-trigger="docView_{{ $modalSuffix }}">
                            <i class="fa-regular fa-file-lines me-1"></i> Ver detalle
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100"
                                data-docs-modal-trigger="docManage_{{ $modalSuffix }}">
                            <i class="fa-regular fa-folder me-1"></i> Gestionar
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100"
                                onclick="window.openDocFromChat && window.openDocFromChat()">
                            <i class="fas fa-images me-1"></i> Cargar desde galería del chat
                        </button>
                    </div>
                </div>

            </div>
        </div>{{-- /docs-vd-grid --}}
    </div>{{-- /docs-rp --}}

    {{-- ── Modales de detalle ──────────────────────────────────────────── --}}
    @include('helpdeskdocument::modals.docs-viewer', [
        'id' => 'docsViewer_'.$modalSuffix,
        'customer' => ['name' => $custName, 'email' => $custEmail, 'identifier' => $custDni],
        'order' => ['reference' => $orderRef, 'status' => $statusKey, 'status_label' => $statusLabel],
        'documents' => $files,
        'manage_id' => 'docManage_'.$modalSuffix,
    ])

    @include('helpdeskdocument::modals.doc-view', [
        'id' => 'docView_'.$modalSuffix,
        'document' => [
            'uuid'          => $docUid,
            'type_label'    => $typeLabel,
            'status_label'  => $statusLabel,
            'origin'        => $doc['origin'] ?? 'Frontend',
            'system'        => $doc['system'] ?? 'Master',
            'doc_type'      => $doc['doc_type'] ?? '—',
            'uploaded_by'   => $doc['uploaded_by'] ?? 'Sistema',
            'created_human' => $doc['created_human'] ?? '—',
            'updated_human' => $doc['updated_human'] ?? '—',
        ],
        'customer' => [
            'firstname' => $custFirstname,
            'lastname'  => $custLastname,
            'email'     => $custEmail,
            'identifier'=> $custDni,
            'phone'     => $custPhone,
            'company'   => $custCompany,
        ],
        'products'  => $products,
        'uploaded'  => $files,
        'notes'     => $notes,
        'sent_count'=> count($actionHistory),
    ])

    @include('helpdeskdocument::modals.doc-manage', [
        'id' => 'docManage_'.$modalSuffix,
        'document' => [
            'id'           => $docId,
            'name'         => $typeLabel,
            'status'       => $statusKey,
            'status_label' => $statusLabel,
        ],
        'customer' => [
            'firstname'  => $custFirstname,
            'lastname'   => $custLastname,
            'email'      => $custEmail,
            'identifier' => $custDni,
        ],
        'order'   => ['reference' => $orderRef],
        'history' => $actionHistory,
    ])

@endif
