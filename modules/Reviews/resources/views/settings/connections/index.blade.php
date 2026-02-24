@extends('layouts.theme')

@section('title', 'Conexiones Google')

@section('content')
    @include('core::components.card', ['title' => 'Conexiones Google My Business'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Conexiones my business</h5>
                        <p class="small mb-0 text-muted">Gestiona las conexiones con Google para sincronizar reseñas de tus ubicaciones</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.reviews.connections.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Status Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Total conexiones</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['total'] ?? 0) }}</h4>
                                        <small class="text-muted">Configuradas en el sistema</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Activas</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['active'] ?? 0) }}</h4>
                                        <small class="text-muted">{{ number_format(($stats['total'] ?? 0) - ($stats['active'] ?? 0)) }} inactivas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Expiradas</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['expired'] ?? 0) }}</h4>
                                        <small class="text-muted">Requieren reconexion</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Ubicaciones</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['locations'] ?? 0) }}</h4>
                                        <small class="text-muted">Total vinculadas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.reviews.connections.index') }}" id="filter-form">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="input-group flex-grow-1">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="search"
                                   name="search"
                                   class="form-control"
                                   placeholder="Buscar por nombre o email..."
                                   value="{{ request('search') }}">
                        </div>
                        @if(request('search'))
                            <a href="{{ route('settings.reviews.connections.index') }}"
                               class="btn btn-outline-secondary"
                               title="Limpiar filtros"
                               style="min-width: 45px;">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                        <button type="submit" class="btn btn-primary" style="min-width: 45px;">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Connections List -->
            <div class="card-body">
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 fw-bold">Listado de conexiones</h6>
                        <p class="text-muted small mb-0">Administra todas las conexiones configuradas</p>
                    </div>
                </div>

                <div class="alert alert-info border-0 bg-info-subtle mb-3">
                    <div class="d-flex align-items-start gap-2">
                        <div>
                            <small class="fw-semibold">Importante:</small>
                            <small class="d-block">Las conexiones expiradas requieren reconexión para continuar sincronizando reseñas.</small>
                        </div>
                    </div>
                </div>

                @if(isset($connections) && $connections->count() > 0)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="select-all">
                            <label class="form-check-label  text-muted" for="select-all">Seleccionar todo</label>
                        </div>
                        <button type="button" class="btn btn-outline-danger d-none" id="bulk-revoke-btn" data-bs-toggle="modal" data-bs-target="#bulk-revoke-modal">
                            <i class="fas fa-ban me-1"></i> (<span id="selected-count">0</span>)
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="30"></th>
                                    <th>Nombre</th>
                                    <th>Email Google</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Ubicaciones</th>
                                    <th>Fecha conexion</th>
                                    <th>Expira</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($connections as $connection)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input connection-checkbox" value="{{ $connection->id }}">
                                        </td>
                                        <td>
                                            <strong>{{ $connection->name }}</strong>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $connection->google_email }}</small>
                                        </td>
                                        <td class="text-center">
                                            @if($connection->status->name === 'ACTIVE' && !$connection->isExpired())
                                                <span class="badge bg-success-subtle text-white">Activa</span>
                                            @elseif($connection->isExpired())
                                                <span class="badge bg-danger-subtle text-white">Expirada</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Inactiva</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark text-black">{{ number_format($connection->locations_count ?? 0) }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $connection->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td>
                                            @if($connection->token_expires_at)
                                                <small class="{{ $connection->isExpired() ? 'text-muted' : 'text-muted' }}">
                                                    {{ $connection->token_expires_at->format('d/m/Y H:i') }}
                                                </small>
                                            @else
                                                <small class="text-muted">-</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#"
                                                   class="text-muted"
                                                   data-bs-toggle="dropdown"
                                                   data-bs-auto-close="true"
                                                   data-bs-boundary="viewport">
                                                    <i class="fa fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.reviews.locations.index', ['connection' => $connection->id]) }}">
                                                            Ver ubicaciones
                                                        </a>
                                                    </li>
                                                    @if($connection->isExpired())
                                                        <li>
                                                            <a class="dropdown-item reconnect-btn"
                                                               data-bs-toggle="modal"
                                                               data-bs-target="#reconnect-modal"
                                                               data-url="{{ route('settings.reviews.connections.reconnect', $connection->id) }}"
                                                               data-title="Reconectar: {{ $connection->name }}">
                                                                Reconectar
                                                            </a>
                                                        </li>
                                                    @endif
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item revoke-btn"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#revoke-modal"
                                                           data-url="{{ route('settings.reviews.connections.revoke', $connection->id) }}"
                                                           data-title="Revocar: {{ $connection->name }}">
                                                            Revocar acceso
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-plug fa-4x text-muted opacity-50"></i>
                        </div>
                        <h5 class="text-muted mb-2">No hay conexiones configuradas</h5>
                        <p class="text-muted small mb-4">Comienza creando tu primera conexion para sincronizar reseñas de Google</p>
                        <a href="{{ route('settings.reviews.connections.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Crear primera conexion
                        </a>
                    </div>
                @endif
            </div>

            @if(isset($connections) && $connections->hasPages())
                <div class="card-footer">{{ $connections->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Modal revocar individual --}}
    <div id="revoke-modal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <form id="revoke-form" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title">Revocar conexion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="display-4 text-warning mb-3">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h4 class="my-0">Revocar esta conexion?</h4>
                        <p>Se perdera el acceso a todas las ubicaciones asociadas. Esta accion no se puede deshacer.</p>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger">Confirmar revocacion</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal revocacion masiva --}}
    <div id="bulk-revoke-modal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <form id="bulk-revoke-form" method="POST" action="{{ route('settings.reviews.connections.bulk-revoke') }}">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="ids" id="bulk-revoke-ids">
                    <div class="modal-header">
                        <h5 class="modal-title">Revocacion masiva</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="display-4 text-warning mb-3">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h4 class="my-0">Revocar conexiones seleccionadas?</h4>
                        <p>Se revocaran <strong id="bulk-count">0</strong> conexiones. Esta accion no se puede deshacer.</p>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger">Confirmar revocacion</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal reconectar --}}
    <div id="reconnect-modal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <form id="reconnect-form" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Reconectar</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="display-4 text-info mb-3">
                            <i class="fas fa-sync"></i>
                        </div>
                        <h4 class="my-0">Reconectar con Google?</h4>
                        <p>Se redirigirá a Google para reauthenticar esta conexion y actualizar los tokens de acceso.</p>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-info">Ir a google</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Revoke modal
    $('.revoke-btn').on('click', function() {
        $('#revoke-modal .modal-title').text($(this).data('title'));
        $('#revoke-form').attr('action', $(this).data('url'));
    });

    // Reconnect modal
    $('.reconnect-btn').on('click', function() {
        $('#reconnect-modal .modal-title').text($(this).data('title'));
        $('#reconnect-form').attr('action', $(this).data('url'));
    });

    // Bulk selection
    const $selectAll = $('#select-all');
    const $checkboxes = $('.connection-checkbox');
    const $bulkBtn = $('#bulk-revoke-btn');
    const $selectedCount = $('#selected-count');
    const $bulkCount = $('#bulk-count');
    const $bulkIds = $('#bulk-revoke-ids');

    function updateBulkState() {
        const selected = $checkboxes.filter(':checked');
        const count = selected.length;

        $bulkBtn.toggleClass('d-none', count === 0);
        $selectedCount.text(count);
        $bulkCount.text(count);
        $bulkIds.val(JSON.stringify(selected.map(function() { return $(this).val(); }).get()));
    }

    $selectAll.on('change', function() {
        $checkboxes.prop('checked', $(this).is(':checked'));
        updateBulkState();
    });

    $checkboxes.on('change', updateBulkState);
});
</script>
@endpush

