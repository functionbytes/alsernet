@extends('layouts.theme')

@section('title', $customer->name . ' - Groups')

@section('content')

    @include('core::components.card', ['title' => 'Detalles del Cliente'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Main Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width: 64px; height: 64px; background-color: #f5f6f8; color: #081A28; font-weight: 600; font-size: 1.5rem;">
                            {{ strtoupper(substr($customer->name, 0, 2)) }}
                        </div>
                        <div>
                            <h5 class="mb-1 fw-bold">{{ $customer->name }}</h5>
                            <p class="small mb-0 text-muted">{{ $customer->email }}</p>
                            <div class="mt-2">
                                @if($customer->is_banned)
                                    <span class="badge bg-danger">
                                        <i class="fa fa-ban me-1"></i> Suspendido
                                    </span>
                                @elseif($customer->email_verified_at)
                                    <span class="badge bg-success">
                                        <i class="fa fa-check me-1"></i> Verificado
                                    </span>
                                @else
                                    <span class="badge bg-primary">
                                        <i class="fa fa-exclamation-triangle me-1"></i> Pendiente
                                    </span>
                                @endif

                                @if($customer->country)
                                    <span class="badge bg-light text-dark border">
                                        {{ strtoupper($customer->country) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('chat.customers.edit', $customer) }}" class="btn btn-primary">
                            <i class="fa fa-pen me-1"></i> Editar
                        </a>
                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="#">
                                        Nueva conversación
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#">
                                      Exportar datos
                                    </a>
                                </li>
                                @if(!$customer->is_banned)
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('chat.customers.ban', $customer) }}"
                                              style="display: inline;">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-warning"
                                                    onclick="return confirm('¿Suspender a este cliente?');">
                                                Suspender cliente
                                            </button>
                                        </form>
                                    </li>
                                @else
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('chat.customers.unban', $customer) }}"
                                              style="display: inline;">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-success">
                                               Reactivar cliente
                                            </button>
                                        </form>
                                    </li>
                                @endif
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('chat.customers.destroy', $customer) }}"
                                          style="display: inline;"
                                          onsubmit="return confirm('¿Estás seguro de eliminar este cliente? Esta acción no se puede deshacer.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="fa fa-trash me-2"></i> Eliminar Cliente
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            @if($customer->is_banned && $customer->ban_reason)
                <!-- Ban Warning -->
                <div class="card-body border-bottom">
                    <div class="alert alert-warning border-0 bg-warning-subtle mb-0">
                        <div class="d-flex align-items-start gap-2">
                            <i class="fa fa-exclamation-triangle fs-5"></i>
                            <div>
                                <small class="fw-semibold">Cliente suspendido:</small>
                                <p class="mb-0 mt-1 small">{{ $customer->ban_reason }}</p>
                                @if($customer->banned_at)
                                    <small class="text-muted d-block mt-1">
                                        Suspendido el {{ $customer->banned_at->format('d/m/Y H:i') }}
                                    </small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Statistics Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-subtle h-100 mb-0">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2 small">Conversaciones</h6>
                                <h3 class="mb-1 fw-bold text-primary">{{ $customer->total_conversations ?? 0 }}</h3>
                                <small class="text-muted">Total de chats</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-subtle h-100 mb-0">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2 small">Páginas Visitadas</h6>
                                <h3 class="mb-1 fw-bold text-success">{{ $customer->total_page_visits ?? 0 }}</h3>
                                <small class="text-muted">Vistas totales</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-subtle h-100 mb-0">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2 small">Último Acceso</h6>
                                @if($customer->last_seen_at)
                                    <h3 class="mb-1 fw-bold text-info" style="font-size: 1rem;">
                                        {{ $customer->last_seen_at->diffForHumans() }}
                                    </h3>
                                    <small class="text-muted">{{ $customer->last_seen_at->format('d/m/Y H:i') }}</small>
                                @else
                                    <h3 class="mb-1 fw-bold text-muted" style="font-size: 1rem;">—</h3>
                                    <small class="text-muted">Sin registro</small>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-subtle h-100 mb-0">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2 small">Miembro Desde</h6>
                                <h3 class="mb-1 fw-bold text-dark" style="font-size: 1rem;">
                                    {{ $customer->created_at->diffForHumans() }}
                                </h3>
                                <small class="text-muted">{{ $customer->created_at->format('d/m/Y') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customers Information -->
            <div class="card-body border-bottom">
                <div class="mb-3">
                    <h6 class="mb-1 fw-bold">Información de contacto</h6>
                    <p class="text-muted small mb-0">Datos de comunicación del cliente</p>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small text-muted">Correo electrónico</label>
                        <p class="mb-0 fw-semibold">{{ $customer->email }}</p>
                        @if($customer->email_verified_at)
                            <small class="text-success">
                                <i class="fa fa-check-circle me-1"></i>
                                Verificado el {{ $customer->email_verified_at->format('d/m/Y H:i') }}
                            </small>
                        @else
                            <small class="text-warning">
                                <i class="fa fa-exclamation-triangle me-1"></i>
                                Email no verificado
                            </small>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small text-muted">Teléfono</label>
                        <p class="mb-0 fw-semibold">{{ $customer->phone_number ?? '—' }}</p>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small text-muted">Idioma preferido</label>
                        <p class="mb-0 fw-semibold">
                            @if($customer->language === 'es') Español
                            @elseif($customer->language === 'en') Ingles
                            @elseif($customer->language === 'fr') Frances
                            @elseif($customer->language === 'pt') Portugues
                            @elseif($customer->language === 'de') Aleman
                            @elseif($customer->language === 'it') Italiano
                            @else — @endif
                        </p>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small text-muted">Zona horaria</label>
                        <p class="mb-0 fw-semibold">{{ $customer->timezone ?? 'Detección automática' }}</p>
                    </div>
                </div>
            </div>

            <!-- Location Information -->
            <div class="card-body border-bottom">
                <div class="mb-3">
                    <h6 class="mb-1 fw-bold">Ubicación</h6>
                    <p class="text-muted small mb-0">Información geográfica del cliente</p>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-muted">País</label>
                        <p class="mb-0 fw-semibold">{{ $customer->country ? strtoupper($customer->country) : '—' }}</p>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-muted">Estado/Región</label>
                        <p class="mb-0 fw-semibold">{{ $customer->state ?? '—' }}</p>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-muted">Ciudad</label>
                        <p class="mb-0 fw-semibold">{{ $customer->city ?? '—' }}</p>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="card-body border-bottom bg-light">
                <ul class="nav nav-pills" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-info" type="button">
                            <i class="fa fa-info-circle me-1"></i> Información
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-notas" type="button">
                            <i class="fa fa-sticky-note me-1"></i> Notas
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-combinar" type="button">
                            <i class="fa fa-users me-1"></i> Combinar
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Info Tab -->
                <div class="tab-pane fade show active" id="tab-info">

            <!-- Labels -->
            <div class="card-body border-bottom">
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 fw-bold">Etiquetas</h6>
                        <p class="text-muted small mb-0">Categorías asignadas al cliente</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addLabelModal">
                        <i class="fa fa-plus me-1"></i> Agregar
                    </button>
                </div>

                @if($customer->labels && $customer->labels->count() > 0)
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($customer->labels as $label)
                            <span class="badge d-inline-flex align-items-center gap-2"
                                  style="background-color: {{ $label->color }}; font-size: 0.85rem; padding: 0.5rem 0.75rem;">
                                {{ $label->name }}
                                <form method="POST"
                                      action="{{ route('chat.customers.labels.remove', [$customer, $label]) }}"
                                      style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-link p-0 text-white"
                                            style="font-size: 0.75rem; opacity: 0.8;"
                                            onclick="return confirm('¿Eliminar esta etiqueta?');">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </form>
                            </span>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fa fa-tag fs-3 text-muted mb-2 d-block"></i>
                        <p class="text-muted mb-0 small">Sin etiquetas asignadas</p>
                    </div>
                @endif
            </div>

            <!-- Custom Attributes -->
            @php
                $customAttrs = is_array($customer->additional_attributes) ? $customer->additional_attributes : [];
                $hasCustomAttrs = count(array_filter($customAttrs)) > 0;
            @endphp
            @if($hasCustomAttrs)
            <div class="card-body border-bottom">
                <div class="mb-3">
                    <h6 class="mb-1 fw-bold">Atributos personalizados</h6>
                    <p class="text-muted small mb-0">Información adicional del cliente</p>
                </div>

                <div class="row g-3">
                    @foreach($customAttributes as $attribute)
                        @if(isset($customAttrs[$attribute->attribute_key]))
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">
                                    {{ $attribute->attribute_display_name }}
                                </label>
                                <p class="mb-0 fw-semibold">
                                    @if($attribute->attribute_display_type === 'checkbox')
                                        @if($customAttrs[$attribute->attribute_key])
                                            <span class="badge bg-success"><i class="fa fa-check"></i> Sí</span>
                                        @else
                                            <span class="badge bg-secondary"><i class="fa fa-times"></i> No</span>
                                        @endif
                                    @else
                                        {{ $customAttrs[$attribute->attribute_key] }}
                                    @endif
                                </p>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Notes -->
            <div class="card-body border-bottom">
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 fw-bold">Notas</h6>
                        <p class="text-muted small mb-0">Anotaciones sobre el cliente</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                        <i class="fa fa-plus me-1"></i> Nueva Nota
                    </button>
                </div>

                @if($customer->notes && $customer->notes->count() > 0)
                    <div class="d-flex flex-column gap-3">
                        @foreach($customer->notes as $note)
                            <div class="card bg-light mb-0">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                                 style="width: 32px; height: 32px; background-color: #e3e7ed; font-size: 0.75rem; font-weight: 600;">
                                                {{ strtoupper(substr($note->user->name ?? 'U', 0, 2)) }}
                                            </div>
                                            <div>
                                                <p class="mb-0 fw-semibold small">{{ $note->user->name ?? 'Usuario' }}</p>
                                                <small class="text-muted">{{ $note->created_at->diffForHumans() }}</small>
                                            </div>
                                        </div>
                                        <form method="POST"
                                              action="{{ route('chat.customers.notes.destroy', [$customer, $note]) }}"
                                              style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-sm btn-link text-danger p-0"
                                                    onclick="return confirm('¿Eliminar esta nota?');">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <p class="mb-0 small">{{ $note->content }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fa fa-sticky-note fs-3 text-muted mb-2 d-block"></i>
                        <p class="text-muted mb-0 small">Sin notas registradas</p>
                    </div>
                @endif
            </div>

            <!-- Conversations -->
            <div class="card-body border-bottom">
                <div class="mb-3 d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1 fw-bold">Conversaciones recientes</h6>
                        <p class="text-muted small mb-0">Historial de comunicación con el cliente</p>
                    </div>
                    <span class="badge bg-primary-subtle text-primary">
                        <i class="fa fa-comments"></i> {{ $customer->total_conversations ?? 0 }}
                    </span>
                </div>

                @if($customer->conversations && $customer->conversations->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Asunto</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th width="80"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customer->conversations->take(5) as $conversation)
                                <tr>
                                    <td>
                                        <strong>{{ $conversation->subject ?? 'Sin asunto' }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info">
                                            {{ $conversation->status ?? 'Abierta' }}
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $conversation->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('chat.conversations.show', $conversation->id) }}" class="btn btn-sm btn-light">
                                            <i class="fa fa-arrow-right"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($customer->conversations->count() > 5)
                        <div class="text-center mt-3">
                            <a href="#" class="btn btn-sm btn-outline-primary">
                                Ver todas ({{ $customer->total_conversations }})
                            </a>
                        </div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                             style="width: 64px; height: 64px; background-color: #f5f6f8;">
                            <i class="fa fa-inbox fs-3 text-muted"></i>
                        </div>
                        <h6 class="mb-1">Sin conversaciones</h6>
                        <p class="text-muted mb-0 small">Este cliente no ha iniciado ninguna conversación</p>
                    </div>
                @endif
            </div>

            @if($customer->internal_notes)
                <!-- Internal Notes -->
                <div class="card-body border-bottom">
                    <div class="mb-3">
                        <h6 class="mb-1 fw-bold">Notas internas</h6>
                        <p class="text-muted small mb-0">Información privada sobre el cliente</p>
                    </div>

                    <div class="alert alert-info bg-info-subtle border-0 mb-0">
                        <p class="mb-0 small">{{ $customer->internal_notes }}</p>
                    </div>
                </div>
            @endif

            <!-- Session Information -->
            @if($customer->latestSession)
                <div class="card-body border-bottom">
                    <div class="mb-3">
                        <h6 class="mb-1 fw-bold">Última sesión</h6>
                        <p class="text-muted small mb-0">Información de la última conexión del cliente</p>
                    </div>

                    <div class="row g-3">
                        @if($customer->latestSession->country)
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted">País de Conexión</label>
                                <p class="mb-0 fw-semibold">{{ $customer->latestSession->country }}</p>
                            </div>
                        @endif

                        @if($customer->latestSession->user_agent)
                            <div class="col-md-8">
                                <label class="form-label fw-semibold small text-muted">Dispositivo</label>
                                <p class="mb-0 small text-break">{{ $customer->latestSession->user_agent }}</p>
                            </div>
                        @endif

                        <div class="col-md-12">
                            <label class="form-label fw-semibold small text-muted">Fecha y Hora</label>
                            <p class="mb-0 fw-semibold">
                                {{ $customer->latestSession->created_at->format('d/m/Y H:i') }}
                                <small class="text-muted">({{ $customer->latestSession->created_at->diffForHumans() }})</small>
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- System Information -->
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="mb-1 fw-bold">Información del sistema</h6>
                    <p class="text-muted small mb-0">Fechas y datos de registro</p>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-muted">Fecha de Registro</label>
                        <p class="mb-0 fw-semibold">{{ $customer->created_at->format('d/m/Y H:i') }}</p>
                        <small class="text-muted">{{ $customer->created_at->diffForHumans() }}</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-muted">Última Actualización</label>
                        <p class="mb-0 fw-semibold">{{ $customer->updated_at->format('d/m/Y H:i') }}</p>
                        <small class="text-muted">{{ $customer->updated_at->diffForHumans() }}</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-muted">ID del Cliente</label>
                        <p class="mb-0 fw-semibold">#{{ $customer->id }}</p>
                    </div>
                </div>
            </div>

                </div>
                <!-- End Info Tab -->

                <!-- Notas Tab -->
                <div class="tab-pane fade" id="tab-notas">
                    <div class="card-body">
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Gestión de Notas</h5>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                                <i class="fa fa-plus me-1"></i> Nueva Nota
                            </button>
                        </div>

                        @if($customer->notes && $customer->notes->count() > 0)
                            <div class="d-flex flex-column gap-3">
                                @foreach($customer->notes as $note)
                                    <div class="card bg-light mb-0">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                                                         style="width: 32px; height: 32px; background-color: #e3e7ed; font-size: 0.75rem; font-weight: 600;">
                                                        {{ strtoupper(substr($note->user->name ?? 'U', 0, 2)) }}
                                                    </div>
                                                    <div>
                                                        <p class="mb-0 fw-semibold small">{{ $note->user->name ?? 'Usuario' }}</p>
                                                        <small class="text-muted">{{ $note->created_at->diffForHumans() }}</small>
                                                    </div>
                                                </div>
                                                <form method="POST"
                                                      action="{{ route('chat.customers.notes.destroy', [$customer, $note]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="btn btn-sm btn-link text-danger p-0"
                                                            onclick="return confirm('¿Eliminar esta nota?');">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                            <p class="mb-0 small">{{ $note->content }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fa fa-sticky-note fs-1 text-muted mb-3 d-block"></i>
                                <p class="text-muted mb-0">Sin notas registradas</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Combinar Tab -->
                <div class="tab-pane fade" id="tab-combinar">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h5 class="mb-3">Combinar contacto</h5>
                                <p class="text-muted">
                                    Combine dos perfiles en uno, incluyendo todos los atributos y conversaciones.
                                    En caso de conflicto, los atributos del contacto principal tendrán prioridad.
                                </p>

                                <div class="alert alert-warning">
                                    <i class="fa fa-exclamation-triangle me-1"></i>
                                    <strong>Advertencia:</strong> Esta acción no se puede deshacer. El contacto secundario será eliminado permanentemente.
                                </div>

                                <form id="merge-customer-form" method="POST" action="{{ route('chat.customers.merge.execute') }}">
                                    @csrf
                                    <input type="hidden" name="primary_contact_id" id="primary_contact_id">
                                    <input type="hidden" name="duplicate_contact_ids[]" value="{{ $customer->id }}">

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="card border-success">
                                                <div class="card-header bg-success text-white">
                                                    <h6 class="mb-0">
                                                        <i class="fa fa-check-circle me-1"></i> Contacto principal
                                                    </h6>
                                                    <small>A guardar</small>
                                                </div>
                                                <div class="card-body">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Buscar contacto primario</label>
                                                        <select id="primary-contact-search" class="form-control select2 select2" style="width: 100%;">
                                                            <option value="">Buscar por nombre, email o teléfono...</option>
                                                        </select>
                                                        <small class="text-muted">Este contacto se mantendrá y recibirá todos los datos</small>
                                                    </div>
                                                    <div id="primary-contact-preview" class="d-none">
                                                        <h6 class="fw-semibold mb-2">Contacto seleccionado:</h6>
                                                        <div id="primary-contact-info"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="card border-danger">
                                                <div class="card-header bg-danger text-white">
                                                    <h6 class="mb-0">
                                                        <i class="fa fa-times-circle me-1"></i> A combinar
                                                    </h6>
                                                    <small>A eliminar</small>
                                                </div>
                                                <div class="card-body">
                                                    <div class="d-flex align-items-center gap-2 mb-2">
                                                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                                                             style="width: 40px; height: 40px; background-color: #f5f6f8; font-weight: 600;">
                                                            {{ strtoupper(substr($customer->name, 0, 2)) }}
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0">{{ $customer->name }}</h6>
                                                            <small class="text-muted">{{ $customer->email ?? 'Sin email' }}</small>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <p class="small mb-1"><strong>Teléfono:</strong> {{ $customer->phone_number ?? 'N/A' }}</p>
                                                    <p class="small mb-1"><strong>Conversaciones:</strong> {{ $customer->conversations->count() }}</p>
                                                    <p class="small mb-0"><strong>Etiquetas:</strong> {{ $customer->labels->count() }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <button type="button" class="btn btn-light me-2" onclick="window.location.reload()">
                                            <i class="fa fa-times me-1"></i> Cancelar
                                        </button>
                                        <button type="submit" class="btn btn-danger" id="btn-merge" disabled>
                                            <i class="fa fa-users me-1"></i> Combinar contacto
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="col-md-4">
                                <div class="card bg-info-subtle">
                                    <div class="card-body">
                                        <h6 class="fw-semibold mb-3">
                                            <i class="fa fa-info-circle me-1"></i> ¿Cómo funciona?
                                        </h6>
                                        <ol class="small ps-3">
                                            <li class="mb-2">Busca el contacto que quieres mantener (contacto principal)</li>
                                            <li class="mb-2">El contacto actual se fusionará con el contacto principal</li>
                                            <li class="mb-2">Todas las conversaciones, notas y etiquetas se transferirán</li>
                                            <li class="mb-0">El contacto actual será eliminado permanentemente</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="card mt-3">
                                    <div class="card-body">
                                        <h6 class="fw-semibold mb-3">
                                            <i class="fa fa-tools me-1"></i> Limpieza masiva
                                        </h6>
                                        <p class="small text-muted">
                                            ¿Necesitas detectar y fusionar múltiples duplicados?
                                        </p>
                                        <a href="{{ route('chat.customers.merge.index') }}" class="btn btn-outline-primary btn-sm w-100">
                                            <i class="fa fa-search me-1"></i> Buscar duplicados
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Tab Content -->

            <div class="card-footer">
                <a href="{{ route('chat.customers.index') }}" class="btn btn-primary w-100">
                    Volver a la lista
                </a>
            </div>

        </div>

    </div>

    <!-- Add Label Modal -->
    <div class="modal fade" id="addLabelModal" tabindex="-1" aria-labelledby="addLabelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('chat.customers.labels.add', $customer) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addLabelModalLabel">Agregar Etiqueta</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="label_id" class="form-label fw-semibold">
                                Selecciona una etiqueta
                            </label>
                            <select name="label_id" id="label_id" class="form-control select2 select2" required>
                                <option value="">— Seleccionar etiqueta —</option>
                                @foreach($accountLabels as $label)
                                    @if(!$customer->labels->contains($label->id))
                                        <option value="{{ $label->id }}">{{ $label->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Agregar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Note Modal -->
    <div class="modal fade" id="addNoteModal" tabindex="-1" aria-labelledby="addNoteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('chat.customers.notes.store', $customer) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addNoteModalLabel">Nueva Nota</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="content" class="form-label fw-semibold">
                                Contenido de la nota
                            </label>
                            <textarea name="content"
                                      id="content"
                                      class="form-control"
                                      rows="4"
                                      placeholder="Escribe una nota sobre este cliente..."
                                      required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Nota</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2 in modals
    $('#addLabelModal, #addNoteModal').on('shown.bs.modal', function () {
        $('.select2', this).select2({
            dropdownParent: $(this),
            allowClear: true
        });
    });

    // Primary contact search with AJAX
    $('#primary-contact-search').select2({
        ajax: {
            url: '{{ route("chat.search.contacts") }}',
            dataType: 'json',
            delay: 300,
            data: function (params) {
                return {
                    q: params.term,
                    page: params.page || 1
                };
            },
            processResults: function (data) {
                var contacts = data.payload && data.payload.contacts ? data.payload.contacts : [];

                // Filter out current customer
                contacts = contacts.filter(function(c) {
                    return c.id != {{ $customer->id }};
                });

                return {
                    results: contacts.map(function(contact) {
                        return {
                            id: contact.id,
                            text: contact.name + (contact.email ? ' - ' + contact.email : '') + (contact.phone_number ? ' - ' + contact.phone_number : ''),
                            contact: contact
                        };
                    })
                };
            },
            cache: true
        },
        minimumInputLength: 2,
        placeholder: 'Buscar por nombre, email o teléfono...',
        allowClear: true,
        language: {
            inputTooShort: function() {
                return 'Escribe al menos 2 caracteres...';
            },
            searching: function() {
                return 'Buscando...';
            },
            noResults: function() {
                return 'No se encontraron contactos';
            }
        }
    });

    // Handle primary contact selection
    $('#primary-contact-search').on('select2:select', function (e) {
        var contact = e.params.data.contact;

        $('#primary_contact_id').val(contact.id);
        $('#btn-merge').prop('disabled', false);

        var html = '<div class="d-flex align-items-center gap-2 mb-2">';
        html += '<div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #f5f6f8; font-weight: 600;">';
        html += contact.name.substring(0, 2).toUpperCase();
        html += '</div>';
        html += '<div>';
        html += '<h6 class="mb-0">' + contact.name + '</h6>';
        html += '<small class="text-muted">' + (contact.email || 'Sin email') + '</small>';
        html += '</div>';
        html += '</div>';
        html += '<hr>';
        html += '<p class="small mb-0"><strong>Teléfono:</strong> ' + (contact.phone_number || 'N/A') + '</p>';

        $('#primary-contact-info').html(html);
        $('#primary-contact-preview').removeClass('d-none');
    });

    // Handle clear selection
    $('#primary-contact-search').on('select2:clear', function (e) {
        $('#primary_contact_id').val('');
        $('#btn-merge').prop('disabled', true);
        $('#primary-contact-preview').addClass('d-none');
    });

    // Merge form submission
    $('#merge-customer-form').on('submit', function(e) {
        e.preventDefault();

        if (!confirm('¿Estás seguro? Esta acción no se puede deshacer. El contacto actual será eliminado y todos sus datos se transferirán al contacto principal.')) {
            return false;
        }

        var $btn = $('#btn-merge');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Combinando...');

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Contactos combinados correctamente');
                    setTimeout(function() {
                        window.location.href = '{{ route("chat.customers.index") }}';
                    }, 1500);
                } else {
                    toastr.error(response.message || 'Error al combinar contactos');
                    $btn.prop('disabled', false).html('<i class="fa fa-users me-1"></i> Combinar contacto');
                }
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'Error al combinar contactos';
                toastr.error(message);
                $btn.prop('disabled', false).html('<i class="fa fa-users me-1"></i> Combinar contacto');
            }
        });
    });

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
