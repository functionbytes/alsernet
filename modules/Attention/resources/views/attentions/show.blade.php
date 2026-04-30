@extends('layouts.theme')

@section('title', 'Ver peticiones - ' . $attention->radicado)

@section('page_header')
    @include('core::components.card', ['title' => 'Detalle del peticiones'])
@endsection

@section('content')

    <div class="row">
        <div class="col-lg-8">
            <!-- Información General -->
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Información general</h5>
                            <p class="small mb-0 text-muted">Detalles del peticiones radicado</p>
                        </div>
                        <span class="badge bg-{{ $attention->status->color() }}">
                            {{ $attention->status->label() }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Radicado</label>
                            <p class="mb-0 text-primary">{{ $attention->radicado }}</p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Tipo</label>
                            <p class="mb-0">
                                @if($attention->type)
                                    <span class="badge bg-info">{{ $attention->type->name }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Categoría</label>
                            <p class="mb-0">
                                @if($attention->category)
                                    <span class="badge bg-light-primary text-primary">{{ $attention->category->name }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Sede</label>
                            <p class="mb-0">{{ $attention->sede->name ?? '-' }}</p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Fecha de radicación</label>
                            <p class="mb-0">{{ $attention->created_at->format('d/m/Y H:i:s') }}</p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Última actualización</label>
                            <p class="mb-0">{{ $attention->updated_at->format('d/m/Y H:i:s') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información del Ciudadano -->
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Información del ciudadano</h5>
                    <p class="small mb-0 text-muted">Datos de contacto del solicitante</p>
                </div>
                <div class="card-body">
                    @if($attention->is_anonymous)
                        <div class="alert alert-info">
                            <i class="fa-duotone fa-user-secret"></i>
                            <strong>Solicitud Anónima</strong> - El ciudadano ha decidido mantener su identidad privada.
                        </div>
                    @else
                        <div class="row g-3">
                            <div class="col-sm-12 col-md-6">
                                <label class="form-label fw-semibold text-muted">Nombres</label>
                                <p class="mb-0">{{ $attention->customer_firstname ?? '-' }}</p>
                            </div>
                            <div class="col-sm-12 col-md-6">
                                <label class="form-label fw-semibold text-muted">Apellidos</label>
                                <p class="mb-0">{{ $attention->customer_lastname ?? '-' }}</p>
                            </div>
                            <div class="col-sm-12 col-md-6">
                                <label class="form-label fw-semibold text-muted">Documento de Identidad</label>
                                <p class="mb-0">{{ $attention->customer_dni ?? '-' }}</p>
                            </div>
                            <div class="col-sm-12 col-md-6">
                                <label class="form-label fw-semibold text-muted">Correo electrónico</label>
                                <p class="mb-0">
                                    @if($attention->customer_email)
                                        <a href="mailto:{{ $attention->customer_email }}">{{ $attention->customer_email }}</a>
                                    @else
                                        -
                                    @endif
                                </p>
                            </div>
                            <div class="col-sm-12 col-md-6">
                                <label class="form-label fw-semibold text-muted">Teléfono/Celular</label>
                                <p class="mb-0">{{ $attention->customer_cellphone ?? '-' }}</p>
                            </div>
                            <div class="col-sm-12 col-md-6">
                                <label class="form-label fw-semibold text-muted">Dirección</label>
                                <p class="mb-0">{{ $attention->customer_address ?? '-' }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Información de gestión -->
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Información de gestión</h5>
                    <p class="small mb-0 text-muted">Datos internos de la solicitud gestionada</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Estado</label>
                            <p class="mb-0">
                                <span class="badge bg-{{ $attention->status->color() }}">{{ $attention->status->label() }}</span>
                            </p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Departamento</label>
                            <p class="mb-0">{{ $attention->department->name ?? '-' }}</p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Usuario asignado</label>
                            <p class="mb-0">{{ $attention->assignedUser->name ?? '-' }}</p>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold text-muted">Días transcurridos</label>
                            <p class="mb-0">{{ $attention->created_at->diffInDays(now()) }} días</p>
                        </div>
                        @if($attention->resolved_at)
                            <div class="col-sm-12 col-md-6">
                                <label class="form-label fw-semibold text-muted">Fecha de resolución</label>
                                <p class="mb-0">{{ $attention->resolved_at->format('d/m/Y H:i:s') }}</p>
                            </div>
                        @endif
                        @if($attention->closed_at)
                            <div class="col-sm-12 col-md-6">
                                <label class="form-label fw-semibold text-muted">Fecha de cierre</label>
                                <p class="mb-0">{{ $attention->closed_at->format('d/m/Y H:i:s') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Contenido de la Solicitud -->
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Contenido de la solicitud</h5>
                    <p class="small mb-0 text-muted">Asunto y descripción detallada</p>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted">Asunto</label>
                        <p class="mb-0">{{ $attention->subject }}</p>
                    </div>
                    <div>
                        <label class="form-label fw-semibold text-muted">Descripción</label>
                        <div class="mb-0">
                            {!! nl2br(e($attention->description)) !!}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documentos Adjuntos -->
            @if($attention->getMedia('attachments')->count() > 0)
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-1 fw-bold">Documentos adjuntos</h5>
                        <p class="small mb-0 text-muted">Archivos subidos por el ciudadano</p>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Archivo</th>
                                        <th>Tamaño</th>
                                        <th>Tipo</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($attention->getMedia('attachments') as $media)
                                        <tr>
                                            <td>
                                                <i class="fa-duotone fa-file-alt me-2"></i>
                                                {{ $media->file_name }}
                                            </td>
                                            <td>{{ $media->human_readable_size }}</td>
                                            <td>
                                                <span class="badge bg-light-secondary text-secondary">
                                                    {{ strtoupper($media->extension) }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ $media->getUrl() }}" target="_blank" class="btn btn-sm btn-primary">
                                                    <i class="fa-duotone fa-download"></i> Descargar
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Resolución -->
            @if($attention->isResolved() || $attention->isClosed())
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-1 fw-bold">Resolución</h5>
                        <p class="small mb-0 text-muted">Respuesta oficial a la solicitud</p>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted">Tipo de respuesta</label>
                            <p class="mb-0">
                                <span class="badge bg-success">
                                    {{ $attention->response_type?->label() ?? 'No especificado' }}
                                </span>
                            </p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted">Fecha de Resolución</label>
                            <p class="mb-0">{{ $attention->resolved_at?->format('d/m/Y H:i:s') ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="form-label fw-semibold text-muted">Respuesta</label>
                            <div class="border rounded p-3 bg-light-success">
                                {!! nl2br(e($attention->resolution)) !!}
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar Derecho -->
        <div class="col-lg-4">
            <!-- Asignación -->
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Asignación</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted">Departamento</label>
                        <p class="mb-0">
                            @if($attention->department)
                                <span class="badge bg-light-warning text-warning">
                                    {{ $attention->department->name }}
                                </span>
                            @else
                                <span class="text-muted">Sin asignar</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <label class="form-label fw-semibold text-muted">Usuario asignado</label>
                        <p class="mb-0">
                            @if($attention->assignedUser)
                                <span class="badge bg-light-success text-success">
                                    {{ $attention->assignedUser->name }}
                                </span>
                            @else
                                <span class="text-muted">Sin asignar</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Acciones</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($attention->canBeEdited())
                            <a href="{{ route('attention.edit', $attention->uid) }}" class="btn btn-primary">
                                Editar
                            </a>
                        @endif
                        <a href="{{ route('attention.tracking', $attention->radicado) }}" class="btn btn-info">
                            Ver seguimiento
                        </a>
                        @if($attention->mails()->count() > 0)
                            <a href="{{ route('attention.emails.index', $attention->uid) }}" class="btn btn-secondary">
                                Ver correos
                            </a>
                        @endif
                        <a href="{{ route('attention.index') }}" class="btn btn-outline-secondary">
                           Volver al listado
                        </a>
                    </div>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Estadísticas</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Notas internas:</span>
                            <span class="fw-bold">{{ $attention->notes()->count() }}</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Acciones registradas:</span>
                            <span class="fw-bold">{{ $attention->actions()->count() }}</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Emails enviados:</span>
                            <span class="fw-bold">{{ $attention->mails()->count() }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Adjuntos:</span>
                            <span class="fw-bold">{{ $attention->getMedia('attachments')->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calificación -->
            @if($attention->satisfaction_rating)
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Calificación</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="fs-1 text-warning mb-2">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= $attention->satisfaction_rating)
                                    <i class="fa-solid fa-star"></i>
                                @else
                                    <i class="fa-regular fa-star"></i>
                                @endif
                            @endfor
                        </div>
                        <p class="mb-0 text-muted">
                            {{ $attention->satisfaction_rating }} de 5 estrellas
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>

@endsection
