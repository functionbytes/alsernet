@extends('layouts.theme')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-0">{{ $integration->name }}</h1>
            <p class="text-muted small mb-0">UID: {{ $integration->uid }}</p>
        </div>
        <a href="{{ route('webhooks.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>

    <div class="row gap-4">
        <!-- Left Column: Integration Details -->
        <div class="col-lg-8">
            <!-- General Information -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0 fw-bold">Información general</h6>
                </div>
                <div class="card-body">
                    <div class="row gap-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Nombre</label>
                            <p class="fw-medium">{{ $integration->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Estado</label>
                            <p>
                                @if($integration->isActive())
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="fas fa-check-circle me-1"></i>Activo
                                    </span>
                                @elseif($integration->isSuspended())
                                    <span class="badge bg-warning-subtle text-warning">
                                        <i class="fas fa-pause-circle me-1"></i>Suspendido
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">
                                        <i class="fas fa-times-circle me-1"></i>Deshabilitado
                                    </span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Plan</label>
                            <p class="fw-medium">{{ ucfirst($integration->plan) }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Límite diario</label>
                            <p class="fw-medium">{{ number_format($integration->daily_limit) }} solicitudes/día</p>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted small">Notas</label>
                            <p class="fw-medium">{{ $integration->notes ?? 'Sin notas' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0 fw-bold">Configuración de seguridad</h6>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <label class="form-label text-muted small">IPs permitidas</label>
                        @if($integration->allowed_ips && count($integration->allowed_ips) > 0)
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($integration->allowed_ips as $ip)
                                    <span class="badge bg-light text-dark border">{{ $ip }}</span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted small mb-0">Todas las IPs permitidas</p>
                        @endif
                    </div>
                    <div>
                        <label class="form-label text-muted small">Dominios permitidos</label>
                        @if($integration->allowed_domains && count($integration->allowed_domains) > 0)
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($integration->allowed_domains as $domain)
                                    <span class="badge bg-light text-dark border">{{ $domain }}</span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted small mb-0">Todos los dominios permitidos</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Subscriptions -->
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0 fw-bold">Suscripciones activas</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Evento</th>
                                <th>URL</th>
                                <th>Estado</th>
                                <th class="text-end pe-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($integration->activeSubscriptions as $subscription)
                                <tr>
                                    <td>
                                        <span class="badge bg-info-subtle text-info">{{ $subscription->event_type ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted" title="{{ $subscription->webhook_url ?? 'N/A' }}">
                                            {{ Str::limit($subscription->webhook_url ?? 'N/A', 40) }}
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="fas fa-check-circle me-1"></i>Activa
                                        </span>
                                    </td>
                                    <td class="pe-3 text-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger" title="Desactivar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <small>No hay suscripciones activas</small>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Column: Stats & Info -->
        <div class="col-lg-4">
            <!-- Quick Stats -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h3 class="h2 fw-bold text-primary">{{ $integration->subscriptions->count() }}</h3>
                        <p class="text-muted small mb-0">Total de suscripciones</p>
                    </div>
                    <div class="text-center">
                        <h3 class="h2 fw-bold text-success">{{ $integration->activeSubscriptions->count() }}</h3>
                        <p class="text-muted small mb-0">Suscripciones activas</p>
                    </div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title fw-bold mb-3">Información adicional</h6>
                    <div class="small">
                        <div class="mb-3 pb-3 border-bottom">
                            <label class="text-muted d-block mb-1">Creado</label>
                            <small class="fw-medium">{{ $integration->created_at->format('d/m/Y H:i') }}</small>
                        </div>
                        <div>
                            <label class="text-muted d-block mb-1">Actualizado</label>
                            <small class="fw-medium">{{ $integration->updated_at->format('d/m/Y H:i') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
