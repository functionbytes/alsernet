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
                               title="Limpiar filtros">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                        <button type="submit" class="btn btn-primary">
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
                        <p class="text-muted mb-0">Administra todas las conexiones configuradas</p>
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
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="30"><input type="checkbox" id="select-all" class="form-check-input"></th>
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
                                            <input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $connection->id }}">
                                        </td>
                                        <td>
                                            <strong>{{ $connection->name }}</strong>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $connection->google_email }}</small>
                                        </td>
                                        <td class="text-center">
                                            @if($connection->status === \Modules\Reviews\Enums\ConnectionStatus::ACTIVE && !$connection->isExpired())
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
                                                <small class="text-muted">
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
                        <p class="text-muted mb-4">Comienza creando tu primera conexion para sincronizar reseñas de Google</p>
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

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none bulk-toolbar-zindex">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
        </button>
    </div>

    {{-- Bulk modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> conexión(es)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="revoke">Revocar acceso</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
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
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos una conexión.'); return; }
        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('settings.reviews.connections.bulk-action') }}',
            method: 'POST',
            data: JSON.stringify({ action, ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.count + ' conexión(es) revocadas.');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });
});
</script>
@endpush

@push('css')
<style>
.bulk-toolbar-zindex { z-index: 1050; }
</style>
@endpush
