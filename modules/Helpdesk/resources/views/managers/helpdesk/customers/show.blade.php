@extends('layouts.theme')

@section('title', $customer->name . ' - Helpdesk')

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
                             style="width: 64px; height: 64px; background-color: #f5f6f8; color: #90bb13; font-weight: 600; font-size: 1.5rem;">
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
                        <a href="{{ route('manager.helpdesk.customers.edit', $customer) }}" class="btn btn-primary">
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
                                        <form method="POST" action="{{ route('manager.helpdesk.customers.ban', $customer) }}"
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
                                        <form method="POST" action="{{ route('manager.helpdesk.customers.unban', $customer) }}"
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
                                    <form method="POST" action="{{ route('manager.helpdesk.customers.destroy', $customer) }}"
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

            <!-- Contact Information -->
            <div class="card-body border-bottom">
                <div class="mb-3">
                    <h6 class="mb-1 fw-bold">Información de contacto</h6>
                    <p class="text-muted mb-0">Datos de comunicación del cliente</p>
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
                        <p class="mb-0 fw-semibold">{{ $customer->phone ?? '—' }}</p>
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
                    <p class="text-muted mb-0">Información geográfica del cliente</p>
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

            <!-- Conversations -->
            <div class="card-body border-bottom">
                <div class="mb-3 d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1 fw-bold">Conversaciones recientes</h6>
                        <p class="text-muted mb-0">Historial de comunicación con el cliente</p>
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
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            {{ $conversation->status ?? 'Abierta' }}
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $conversation->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td class="text-end">
                                        <a href="#" class="btn btn-sm btn-light">
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
                        <p class="text-muted mb-0">Este cliente no ha iniciado ninguna conversación</p>
                    </div>
                @endif
            </div>

            @if($customer->internal_notes)
                <!-- Internal Notes -->
                <div class="card-body border-bottom">
                    <div class="mb-3">
                        <h6 class="mb-1 fw-bold">Notas internas</h6>
                        <p class="text-muted mb-0">Información privada sobre el cliente</p>
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
                        <p class="text-muted mb-0">Información de la última conexión del cliente</p>
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
                    <p class="text-muted mb-0">Fechas y datos de registro</p>
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

            <div class="card-footer">
                <a href="{{ route('manager.helpdesk.customers.index') }}" class="btn btn-primary w-100">
                    Volver a la lista
                </a>
            </div>

        </div>

    </div>

@endsection
