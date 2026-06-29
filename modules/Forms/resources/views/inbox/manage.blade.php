@extends('layouts.theme')

@section('title', 'Gestionar solicitud #' . $submission->id)

@section('page_header')
    @include('core::components.card', ['title' => 'Gestionar solicitud #' . $submission->id])
@endsection

@section('content')

    @php
        $statusMap = [
            'new'       => ['label' => 'Nuevo',       'class' => 'bg-primary'],
            'in_review' => ['label' => 'En revisión', 'class' => 'bg-warning text-dark'],
            'resolved'  => ['label' => 'Resuelto',    'class' => 'bg-success'],
            'rejected'  => ['label' => 'Rechazado',   'class' => 'bg-danger'],
        ];
        $st             = $statusMap[$submission->status ?? 'new'] ?? $statusMap['new'];
        $submitterEmail = $submission->getEmailValue();
        $recentEmails   = $submission->emails->sortByDesc('created_at')->take(3);
        $statusChanges  = $submission->actions->where('action', 'status_changed')->sortByDesc('created_at');
        $recentActions  = $submission->actions->sortByDesc('created_at')->take(5);
    @endphp

    <div class="row">

        {{-- ===== COLUMNA IZQUIERDA ===== --}}
        <div class="col-lg-4">

            {{-- 1. Acciones de email --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Acciones de email</h5>
                    <p class="small mb-0 text-muted">Comunicación con el solicitante</p>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold mb-1">Confirmación de recepción</label>
                        <p class="text-muted mb-2">Notificar al solicitante que su solicitud fue recibida</p>
                        <button type="button" class="btn btn-outline-primary w-100" id="sendConfirmationBtn"
                                data-url="{{ route('forms.inbox.emails.send-confirmation', $submission) }}"
                                @if(!$submitterEmail) disabled title="Sin email registrado en el formulario" @endif>
                            Enviar confirmación
                        </button>
                    </div>
                    <hr class="my-3">
                    <div class="mb-0">
                        <label class="form-label fw-semibold mb-1">Correo personalizado</label>
                        <p class="text-muted mb-2">Enviar email con contenido personalizado al solicitante</p>
                        <button type="button" class="btn btn-outline-primary w-100"
                                data-bs-toggle="modal" data-bs-target="#customEmailModal">
                            Redactar correo
                        </button>
                    </div>
                </div>
            </div>

            {{-- 2. Notas internas --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Notas internas</h5>
                    <p class="small mb-0 text-muted">Comentarios visibles solo para el equipo</p>
                </div>
                <div class="card-body">
                    <div id="notesList" style="max-height: 220px; overflow-y: auto;">
                        @forelse ($submission->notes->sortByDesc('created_at') as $note)
                            <div class="border-bottom pb-2 mb-2">
                                <strong class="small">{{ $note->user?->full_name ?? 'Sistema' }}</strong><br>
                                <span class="small">{{ $note->note }}</span>
                                <small class="text-muted d-block">{{ $note->created_at?->format('d/m/Y H:i') }}</small>
                            </div>
                        @empty
                            <p class="text-muted" id="emptyNotes">Sin notas aún.</p>
                        @endforelse
                    </div>
                    <form id="addNoteForm" class="mt-3">
                        <div class="mb-2">
                            <textarea id="noteText" class="form-control form-control-sm" rows="3"
                                      placeholder="Añadir nota interna..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            Guardar nota
                        </button>
                    </form>
                </div>
            </div>

            {{-- 3. Historial de acciones --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Historial de acciones</h5>
                    <p class="small mb-0 text-muted">Registro de todas las acciones realizadas</p>
                </div>
                @if($recentActions->count() > 0)
                    <div class="card-body p-0">
                        <div style="max-height: 260px; overflow-y: auto;">
                            @foreach($recentActions as $action)
                                <div class="border-bottom p-3 small">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Acción</span>
                                        <span class="fw-semibold text-end">{{ $action->action_label }}</span>
                                    </div>
                                    @if($action->description)
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Detalle</span>
                                            <span class="fw-semibold text-end" style="max-width:60%; word-break:break-word;">
                                                {{ $action->description }}
                                            </span>
                                        </div>
                                    @endif
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Por</span>
                                        <span class="fw-semibold text-end">
                                            {{ $action->user ? ucfirst(strtolower($action->user->firstname ?? '')) . ' ' . strtoupper(substr($action->user->lastname ?? '', 0, 1)) . '.' : 'Sistema' }}
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Fecha</span>
                                        <span class="fw-semibold">{{ $action->created_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="card-body text-center py-4">
                        <i class="fas fa-inbox text-muted mb-2" style="font-size: 2rem;"></i>
                        <p class="text-muted mb-0"><strong>Sin acciones registradas</strong></p>
                    </div>
                @endif
            </div>

            {{-- 4. Correos enviados --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Correos enviados</h5>
                        <p class="small mb-0 text-muted">Historial de correos electrónicos</p>
                    </div>
                </div>
                @if($recentEmails->count() > 0)
                    <div class="card-body p-0">
                        <div class="email-history-list">
                            @foreach($recentEmails as $email)
                                <a href="{{ route('forms.inbox.emails.preview', [$submission, $email]) }}"
                                   class="email-item d-block text-decoration-none" target="_blank">
                                    <div class="d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-semibold small text-dark">{{ $email->email_type_label }}</span>
                                            <span class="badge bg-{{ $email->status_color }}-subtle text-{{ $email->status_color }}">
                                                {{ $email->status_label }}
                                            </span>
                                        </div>
                                        <p class="text-muted mb-1" title="{{ $email->subject }}">
                                            {{ \Illuminate\Support\Str::limit($email->subject, 45) }}
                                        </p>
                                        <small class="text-muted">
                                            <i class="fa fa-clock me-1"></i>
                                            {{ ($email->sent_at ?? $email->created_at)->format('d/m/Y H:i') }}
                                        </small>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                        <div class="border-top p-3">
                            <a href="{{ route('forms.inbox.emails.index', $submission) }}" class="btn btn-primary w-100 btn-sm">
                                Ver historial completo
                            </a>
                        </div>
                    </div>
                @else
                    <div class="card-body text-center py-4">
                        <i class="fas fa-envelope text-muted mb-2" style="font-size: 2rem;"></i>
                        <p class="text-muted mb-0"><strong>Sin correos enviados</strong></p>
                    </div>
                @endif
            </div>

            {{-- 5. Historial de estado --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Historial de estado</h5>
                    <p class="small mb-0 text-muted" style="font-size: 0.75rem;">Transiciones de la solicitud</p>
                </div>
                <div class="card-body p-2">
                    @php
                        $totalChanges = $statusChanges->count();
                        $showLimit    = 5;
                        $hasMore      = $totalChanges > $showLimit;
                    @endphp

                    @if($totalChanges > 0)
                        @if($hasMore)
                            <div class="alert alert-info py-1 px-2 mb-2" role="alert">
                                <i class="fas fa-info-circle me-1"></i> Últimos {{ $showLimit }}/{{ $totalChanges }}
                            </div>
                        @endif

                        <div class="status-timeline-scroll @if($hasMore) scrollable @endif"
                             @if($hasMore) style="max-height: 350px; overflow-y: auto;" @endif>
                            <ul class="timeline-widget mb-0 list-unstyled">
                                @foreach($statusChanges->take($showLimit) as $change)
                                    <li class="p-3 border-bottom small">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Fecha</span>
                                            <span class="fw-semibold">{{ $change->created_at->format('d/m H:i') }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Cambio</span>
                                            <span class="fw-semibold">{{ $change->description }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Actualizado por</span>
                                            <span class="fw-semibold">{{ $change->user->full_name ?? 'Sistema' }}</span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <div class="alert alert-info alert-sm mb-2 mx-3 mt-3" role="alert">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-circle-info text-black me-1 mt-1" style="font-size: 0.7rem;"></i>
                                <div>
                                    <div class="fw-semibold" style="font-size: 0.7rem;">Sin cambios</div>
                                    <div class="text-muted" style="font-size: 0.65rem; line-height: 1.2;">No hay historial registrado</div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- ===== COLUMNA DERECHA ===== --}}
        <div class="col-lg-8">

            {{-- 1. Información del ciudadano --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Información del ciudadano</h5>
                    <p class="small mb-0 text-muted">Datos de contacto del ciudadano</p>
                </div>
                <div class="card-body">
                    @if(!$citizenInfo['name'] && !$citizenInfo['lastname'] && !$submitterEmail && !$citizenInfo['phone'])
                        <div class="alert alert-info py-2 px-3 mb-0">
                            <small><i class="fas fa-info-circle me-1"></i> No se detectaron datos de contacto en las respuestas del formulario.</small>
                        </div>
                    @else
                        <div class="row g-3">
                            @if($citizenInfo['name'])
                                <div class="col-sm-12 col-md-6">
                                    <label class="form-label fw-semibold">Nombre</label>
                                    <input type="text" class="form-control" value="{{ $citizenInfo['name'] }}" disabled>
                                </div>
                            @endif
                            @if($citizenInfo['lastname'])
                                <div class="col-sm-12 col-md-6">
                                    <label class="form-label fw-semibold">Apellido</label>
                                    <input type="text" class="form-control" value="{{ $citizenInfo['lastname'] }}" disabled>
                                </div>
                            @endif
                            @if($submitterEmail)
                                <div class="col-sm-12 col-md-6">
                                    <label class="form-label fw-semibold">Correo electrónico</label>
                                    <input type="text" class="form-control" value="{{ $submitterEmail }}" disabled>
                                </div>
                            @endif
                            @if($citizenInfo['phone'])
                                <div class="col-sm-12 col-md-6">
                                    <label class="form-label fw-semibold">Teléfono</label>
                                    <input type="text" class="form-control" value="{{ $citizenInfo['phone'] }}" disabled>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- 2. Detalle de la solicitud --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Detalle de la solicitud</h5>
                    <p class="small mb-0 text-muted">Información del radicado y fechas</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold">Radicado / ID</label>
                            <input type="text" class="form-control" value="#{{ $submission->id }}" disabled>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold">Formulario</label>
                            <input type="text" class="form-control" value="{{ $submission->form->name ?? '—' }}" disabled>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold">Estado actual</label>
                            <input type="text" class="form-control" value="{{ $st['label'] }}" disabled>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold">Asignado a</label>
                            <input type="text" class="form-control"
                                   value="{{ $submission->assignedTo?->full_name ?? 'Sin asignar' }}" disabled>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold">Fecha de envío</label>
                            <input type="text" class="form-control"
                                   value="{{ $submission->created_at?->format('d/m/Y H:i:s') ?? '—' }}" disabled>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold">Última actualización</label>
                            <input type="text" class="form-control"
                                   value="{{ $submission->updated_at?->format('d/m/Y H:i:s') ?? '—' }}" disabled>
                        </div>
                        @if($submission->ip_address)
                            <div class="col-sm-12 col-md-6">
                                <label class="form-label fw-semibold">IP</label>
                                <input type="text" class="form-control" value="{{ $submission->ip_address }}" disabled>
                            </div>
                        @endif
                        @if($submission->country || $submission->city)
                            <div class="col-sm-12 col-md-6">
                                <label class="form-label fw-semibold">País / Ciudad</label>
                                <input type="text" class="form-control"
                                       value="{{ implode(', ', array_filter([$submission->country, $submission->city])) }}" disabled>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 3. Respuestas del formulario --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Respuestas del formulario</h5>
                    <p class="small mb-0 text-muted">Campos enviados por el solicitante</p>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 35%;">Campo</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($submission->values->reject(fn($v) => $v->field_type === 'hidden') as $value)
                                    <tr>
                                        <td class="text-muted fw-semibold">
                                            {{ $value->field_label ?: $value->field_key }}
                                        </td>
                                        <td>
                                            @if ($value->field_type === 'signature' && $value->value)
                                                <img src="{{ $value->value }}" alt="Firma"
                                                     style="max-height: 60px; border: 1px solid #ddd; border-radius: 4px;">
                                            @else
                                                {{ $value->getDisplayValue() ?: '—' }}
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-3">Sin respuestas registradas</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- 4. Gestión de la solicitud --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Gestión de la solicitud</h5>
                    <p class="small mb-0 text-muted">Cambiar estado, asignar y resolver</p>
                </div>
                <div class="card-body">
                    <form id="submissionManageForm">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold" for="statusSelect">Estado</label>
                                <select class="form-select" name="status" id="statusSelect">
                                    <option value="">Seleccionar estado...</option>
                                    <option value="new"       {{ ($submission->status ?? 'new') === 'new'       ? 'selected' : '' }}>Nuevo</option>
                                    <option value="in_review" {{ ($submission->status ?? 'new') === 'in_review' ? 'selected' : '' }}>En revisión</option>
                                    <option value="resolved"  {{ ($submission->status ?? 'new') === 'resolved'  ? 'selected' : '' }}>Resuelto</option>
                                    <option value="rejected"  {{ ($submission->status ?? 'new') === 'rejected'  ? 'selected' : '' }}>Rechazado</option>
                                </select>
                                <small class="text-muted">Cambiar el estado de la solicitud</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold" for="assignedToSelect">Usuario responsable</label>
                                <select class="form-select" name="assigned_to" id="assignedToSelect">
                                    <option value="">— Sin asignar —</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}"
                                            {{ $submission->assigned_to == $user->id ? 'selected' : '' }}>
                                            {{ $user->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Asignar a un usuario específico</small>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary w-100 mb-1" id="saveManageBtn">
                                    Guardar cambios
                                </button>
                                <a href="{{ route('forms.inbox.show', $submission) }}" class="btn btn-secondary w-100  mb-1">
                                    Ver detalles
                                </a>
                                <a href="{{ route('forms.inbox.index') }}" class="btn btn-outline-secondary w-100">
                                    Volver al inbox
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- 5. Archivos adjuntos --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Archivos adjuntos</h5>
                            <p class="small mb-0 text-muted">Documentos relacionados con la solicitud</p>
                        </div>
                        <span class="badge bg-primary">{{ $submission->files->count() }} archivo(s)</span>
                    </div>
                </div>
                <div class="card-body" id="filesContainer">
                    @if($submission->files->count() > 0)
                        <div class="list-group mb-3" id="filesList">
                            @foreach($submission->files as $file)
                                <div class="list-group-item d-flex justify-content-between align-items-center" data-file-id="{{ $file->id }}">
                                    <div class="d-flex align-items-center flex-grow-1">
                                        <i class="fas fa-file text-primary me-2"></i>
                                        <div>
                                            <p class="mb-0 fw-semibold small">{{ $file->filename }}</p>
                                            <small class="text-muted">
                                                {{ $file->size_formatted }} •
                                                {{ $file->created_at->format('d/m/Y H:i') }}
                                                @if($file->uploader)
                                                    • {{ $file->uploader->full_name }}
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="{{ $file->url }}" class="btn btn-sm btn-primary" target="_blank" title="Descargar">
                                            <i class="fa fa-download"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger btn-delete-file"
                                                data-file-id="{{ $file->id }}" title="Eliminar">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted" id="noFilesMsg">Sin archivos adjuntos.</p>
                    @endif

                    <form id="uploadFileForm" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label fw-semibold">Cargar nuevo archivo</label>
                            <input type="file" class="form-control" name="file" id="fileInput"
                                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                            <small class="text-muted">PDF, JPG, PNG, DOC (máximo 10MB)</small>
                        </div>
                        <div id="uploadProgress" style="display:none;" class="mb-2">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success progress-bar-striped progress-bar-animated"
                                     id="uploadProgressBar" style="width: 0%"></div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mt-1" id="uploadFileBtn">
                            Cargar archivo
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    {{-- Modal: Correo personalizado --}}
    <div class="modal fade" id="customEmailModal" tabindex="-1" aria-labelledby="customEmailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title" id="customEmailModalLabel">Redactar correo personalizado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="sendCustomEmailForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Destinatario <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="recipient"
                                   value="{{ $submitterEmail ?? '' }}" required
                                   placeholder="correo@ejemplo.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Asunto <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="subject" required
                                   value="RE: Solicitud #{{ $submission->id }}"
                                   placeholder="Asunto del correo...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Mensaje <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="message" rows="6" required
                                      placeholder="Escribir el mensaje..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="submit" class="btn btn-primary w-100 mb-1" id="sendCustomEmailBtn">
                            Enviar correo
                        </button>
                        <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Confirmar eliminación de archivo --}}
    <div class="modal fade" id="confirmDeleteFileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0"><strong>¿Estás seguro de que deseas eliminar este archivo?</strong></p>
                    <p class="text-muted mt-2 mb-0">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger w-100 mb-1" id="confirmDeleteFileBtn">Eliminar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    .email-history-list { max-height: 300px; overflow-y: auto; }
    .email-item {
        display: block; padding: 12px 16px;
        border-bottom: 1px solid #e9ecef; color: inherit;
        transition: background-color 0.15s ease;
    }
    .email-item:hover { background-color: #f8f9fa; }
    .email-item:last-child { border-bottom: none; }

    .status-timeline-scroll { border-radius: 3px; padding-right: 0.25rem; }
    .status-timeline-scroll::-webkit-scrollbar { width: 4px; }
    .status-timeline-scroll::-webkit-scrollbar-track { background: #f8f9fa; border-radius: 3px; }
    .status-timeline-scroll::-webkit-scrollbar-thumb { background: #dee2e6; border-radius: 3px; }
    .status-timeline-scroll::-webkit-scrollbar-thumb:hover { background: #adb5bd; }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {
    const addNoteUrl     = '{{ route('settings.forms.submissions.notes.add', [$submission->form, $submission]) }}';
    const manageUrl      = '{{ route('forms.inbox.manage.update', $submission) }}';
    const uploadFileUrl  = '{{ route('forms.inbox.files.upload', $submission) }}';
    const deleteFileBase = '{{ route('forms.inbox.files.delete', [$submission, '__FILE_ID__']) }}';
    const customEmailUrl = '{{ route('forms.inbox.emails.send-custom', $submission) }}';
    const csrfToken      = $('meta[name="csrf-token"]').attr('content');
    let pendingDeleteFileId = null;

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrfToken } });

    // ── Agregar nota interna ──────────────────────────────────────────────────
    $('#addNoteForm').on('submit', function (e) {
        e.preventDefault();
        const note = $('#noteText').val().trim();
        if (!note) { return; }

        $.ajax({
            url: addNoteUrl, method: 'POST', data: { note },
            success: function (res) {
                $('#emptyNotes').remove();
                $('#notesList').prepend(
                    '<div class="border-bottom pb-2 mb-2">' +
                        '<strong class="small">' + (res.note.user ? res.note.user.full_name : 'Sistema') + '</strong><br>' +
                        '<span class="small">' + res.note.note + '</span>' +
                        '<small class="text-muted d-block">' + res.note.created_at + '</small>' +
                    '</div>'
                );
                $('#noteText').val('');
                toastr.success('Nota añadida');
            },
            error: function () { toastr.error('Error al añadir nota'); }
        });
    });

    // ── Guardar gestión (estado + asignación) ─────────────────────────────────
    $('#submissionManageForm').on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#saveManageBtn');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');

        $.ajax({
            url: manageUrl, method: 'POST', data: $(this).serialize(),
            success: function (response) {
                toastr.success(response.message || 'Cambios guardados correctamente');
                setTimeout(() => location.reload(), 1000);
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message
                    || Object.values(xhr.responseJSON?.errors ?? {}).flat().join(', ')
                    || 'Error al guardar';
                toastr.error(msg);
                $btn.prop('disabled', false).html('Guardar cambios');
            }
        });
    });

    // ── Enviar confirmación de recepción ──────────────────────────────────────
    $('#sendConfirmationBtn').on('click', function () {
        const $btn = $(this);
        const url  = $btn.data('url');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enviando...');

        $.ajax({
            url: url, method: 'POST',
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message || 'Confirmación enviada correctamente');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toastr.error(response.message || 'No se pudo enviar la confirmación');
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al enviar la confirmación');
            },
            complete: function () { $btn.prop('disabled', false).html('Enviar confirmación'); }
        });
    });

    // ── Enviar correo personalizado ───────────────────────────────────────────
    $('#sendCustomEmailForm').on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#sendCustomEmailBtn');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enviando...');

        $.ajax({
            url: customEmailUrl, method: 'POST', data: $(this).serialize(),
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message || 'Correo enviado correctamente');
                    $('#customEmailModal').modal('hide');
                    $('#sendCustomEmailForm')[0].reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toastr.error(response.message || 'No se pudo enviar el correo');
                }
            },
            error: function (xhr) {
                const errors = xhr.responseJSON?.errors
                    ? Object.values(xhr.responseJSON.errors).flat().join('<br>')
                    : (xhr.responseJSON?.message || 'Error al enviar');
                toastr.error(errors);
            },
            complete: function () { $btn.prop('disabled', false).html('Enviar correo'); }
        });
    });

    // ── Cargar archivo ────────────────────────────────────────────────────────
    $('#uploadFileForm').on('submit', function (e) {
        e.preventDefault();
        const fileInput = $('#fileInput')[0];
        if (!fileInput.files || fileInput.files.length === 0) {
            toastr.warning('Selecciona un archivo primero');
            return;
        }

        if (fileInput.files[0].size > 10 * 1024 * 1024) {
            toastr.error('El archivo excede el tamaño máximo de 10MB');
            return;
        }

        const formData = new FormData(this);
        const $btn     = $('#uploadFileBtn');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Cargando...');
        $('#uploadProgress').show();

        $.ajax({
            url: uploadFileUrl, method: 'POST',
            data: formData, contentType: false, processData: false,
            xhr: function () {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function (e) {
                    if (e.lengthComputable) {
                        const pct = Math.round((e.loaded / e.total) * 100);
                        $('#uploadProgressBar').css('width', pct + '%');
                    }
                });
                return xhr;
            },
            success: function (response) {
                if (response.success) {
                    toastr.success('Archivo cargado correctamente');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.message || 'No se pudo cargar el archivo');
                }
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message
                    || Object.values(xhr.responseJSON?.errors ?? {}).flat().join(', ')
                    || 'Error al cargar el archivo';
                toastr.error(msg);
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-upload me-1"></i> Cargar archivo');
                $('#uploadProgress').hide();
                $('#uploadProgressBar').css('width', '0%');
                $('#fileInput').val('');
            }
        });
    });

    // ── Eliminar archivo ──────────────────────────────────────────────────────
    $(document).on('click', '.btn-delete-file', function () {
        pendingDeleteFileId = $(this).data('file-id');
        $('#confirmDeleteFileModal').modal('show');
    });

    $('#confirmDeleteFileBtn').on('click', function () {
        if (!pendingDeleteFileId) { return; }
        const $btn = $(this);
        const url  = deleteFileBase.replace('__FILE_ID__', pendingDeleteFileId);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Eliminando...');

        $.ajax({
            url: url, method: 'DELETE',
            success: function (response) {
                if (response.success) {
                    toastr.success('Archivo eliminado correctamente');
                    $('#confirmDeleteFileModal').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.message || 'No se pudo eliminar el archivo');
                }
            },
            error: function () { toastr.error('Error al eliminar el archivo'); },
            complete: function () {
                $btn.prop('disabled', false).html('Eliminar');
                pendingDeleteFileId = null;
            }
        });
    });
});
</script>
@endpush
