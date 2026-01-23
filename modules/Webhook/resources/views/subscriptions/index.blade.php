@extends('layouts.theme')

@section('page_title', 'Suscripciones Webhook')

@section('content')

    {{-- Breadcrumb Card --}}
    @include('core::components.card', [
        'title' => 'Suscripciones webhook',
        'breadcrumbs' => [
            ['label' => 'Dashboard', 'url' => url('/home')],
            ['label' => 'Configuración', 'url' => ''],
            ['label' => 'Suscripciones', 'active' => true]
        ]
    ])

    <div class="widget-content searchable-container list">

        {{-- Main Card --}}
        <div class="card">
            {{-- Header Section --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Suscripciones webhook</h5>
                        <p class="small mb-0 text-muted">Gestiona las suscripciones a eventos y sus destinos</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route("webhooks.backups.integrations.index") }}" class="btn btn-secondary">
                            Ver integraciones
                        </a>
                        <a href="{{ route('webhooks.backups.subscriptions.create') }}" class="btn btn-primary">
                            Nueva suscripción
                        </a>
                    </div>
                </div>
            </div>

            {{-- Info Section --}}
            <div class="card-body border-bottom">
                <div class="alert alert-info border-0 mb-0" role="alert">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-start">
                            <i class="fa fa-circle-info fs-5 me-3 mt-1"></i>
                            <div>
                                <h6 class="fw-bold mb-2">¿Qué son las suscripciones webhook?</h6>
                                <p class="mb-0">
                                    Las suscripciones vinculan integraciones con eventos específicos del sistema.
                                    Cuando ocurre un evento, se envía una notificación HTTP POST a la URL configurada
                                    con la información del evento en formato JSON.
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('webhooks.events') }}" class="btn btn-info flex-shrink-0">
                            <i class="fa fa-arrow-right me-2"></i>Ver eventos
                        </a>
                    </div>
                </div>
            </div>

            {{-- Alerts --}}
            @if ($errors->any())
                <div class="card-body border-bottom">
                    <div class="alert alert-danger alert-dismissible fade show mb-0" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="fa fa-exclamation-circle fs-4 me-2 mt-1"></i>
                            <div>
                                <h6 class="alert-heading fw-bold mb-2">Errores de validación</h6>
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            @if (session('success'))
                <div class="card-body border-bottom">
                    <div class="alert alert-success alert-dismissible fade show mb-0" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-check fs-4 me-2"></i>
                            <div>{{ session('success') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            {{-- Filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route("webhooks.backups.subscriptions.index") }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-7">
                            <label for="search" class="form-label fw-semibold">Búsqueda</label>
                            <input type="text"
                                   id="search"
                                   name="search"
                                   class="form-control"
                                   placeholder="Buscar por evento, integración o URL..."
                                   value="{{ $search }}">
                        </div>

                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="is_active" class="form-label fw-semibold">Estado</label>
                            <select id="is_active" name="is_active" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="1" {{ $isActive === '1' ? 'selected' : '' }}>Activas</option>
                                <option value="0" {{ $isActive === '0' ? 'selected' : '' }}>Inactivas</option>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fa fa-magnifying-glass me-2"></i>Buscar
                            </button>
                            @if ($search || $isActive)
                                <a href="{{ route("webhooks.backups.subscriptions.index") }}" class="btn btn-outline-secondary">
                                    <i class="fa fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Subscriptions Table --}}
            @if ($subscriptions->count() > 0)
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="20%">Integración</th>
                                <th width="15%">Evento</th>
                                <th width="30%">URL destino</th>
                                <th width="12%">Estado</th>
                                <th width="13%">Creada</th>
                                <th width="10%" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($subscriptions as $subscription)
                                <tr>
                                    <td>
                                        <div>
                                            <strong class="d-block">{{ $subscription->integration->name ?? 'N/A' }}</strong>
                                            <small class="text-muted font-monospace d-block">{{ $subscription->integration->uid ?? '' }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info">{{ $subscription->event_type ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted font-monospace" title="{{ $subscription->webhook_url }}">
                                            {{ Str::limit($subscription->webhook_url, 50) }}
                                        </small>
                                    </td>
                                    <td>
                                        @if($subscription->is_active)
                                            <span class="badge bg-success-subtle text-success">
                                                Activa
                                            </span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">
                                                Inactiva
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $subscription->created_at->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-duotone fa-solid fa-ellipsis"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('webhooks.backups.subscriptions.edit', $subscription->uid) }}">
                                                        Editar suscripción
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button class="dropdown-item text-danger delete-subscription"
                                                            data-id="{{ $subscription->id }}"
                                                            data-name="{{ $subscription->event_type }}">
                                                        Eliminar suscripción
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @else
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="fa fa-inbox fa-3x mb-3 text-muted opacity-50"></i>
                    <h5 class="fw-bold mb-2">No hay suscripciones</h5>
                    <p class="text-muted mb-4">
                        @if ($search || $isActive)
                            No se encontraron resultados con los filtros aplicados.
                        @else
                            Comienza creando tu primera suscripción para recibir notificaciones de eventos.
                        @endif
                    </p>
                    @if ($search || $isActive)
                        <a href="{{ route("webhooks.backups.subscriptions.index") }}" class="btn btn-secondary">
                            Ver todas
                        </a>
                    @else
                        <a href="{{ route('webhooks.backups.subscriptions.create') }}" class="btn btn-primary">
                            + Crear ahora
                        </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Pagination --}}
            @if($subscriptions->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $subscriptions->firstItem() }} - {{ $subscriptions->lastItem() }} de {{ $subscriptions->total() }} suscripciones
                        </div>
                        <div>
                            {{ $subscriptions->links() }}
                        </div>
                    </div>
                </div>
            @endif

        </div>

    </div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.delete-subscription').forEach(btn => {
        btn.addEventListener('click', function() {
            const subscriptionId = this.dataset.id;
            const eventType = this.dataset.name;

            Swal.fire({
                title: '¿Eliminar suscripción?',
                text: `Se eliminará la suscripción al evento "${eventType}".`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/manager/settings/webhooks/subscriptions/${subscriptionId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('¡Eliminado!', data.message, 'success')
                                .then(() => window.location.reload());
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(err => {
                        Swal.fire('Error', 'Error al eliminar la suscripción', 'error');
                    });
                }
            });
        });
    });
});
</script>
@endpush
