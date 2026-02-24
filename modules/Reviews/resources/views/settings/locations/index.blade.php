@extends('layouts.theme')

@section('title', 'Ubicaciones Google')

@section('content')
    @include('core::components.card', ['title' => 'Ubicaciones de Google My Business'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Ubicaciones de my business</h5>
                        <p class="small mb-0 text-muted">Gestiona las ubicaciones vinculadas y sincroniza sus reseñas automaticamente</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#sync-all-modal">
                            <i class="fas fa-sync"></i>
                        </button>
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
                                        <h6 class="card-title mb-2">Total ubicaciones</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['total'] ?? 0) }}</h4>
                                        <small class="text-muted">Sincronizadas en el sistema</small>
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
                                        <h6 class="card-title mb-2">Rating promedio</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['avg_rating'] ?? 0, 1) }}</h4>
                                        <small class="text-muted">de 5.0 estrellas</small>
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
                                        <h6 class="card-title mb-2">Total reseñas</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['total_reviews'] ?? 0) }}</h4>
                                        <small class="text-muted">En todas las ubicaciones</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.reviews.locations.index') }}" id="filter-form">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="input-group flex-grow-1">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="search"
                                   name="search"
                                   class="form-control"
                                   placeholder="Buscar por nombre, dirección o teléfono..."
                                   value="{{ request('search') }}">
                        </div>
                        @if(request('search'))
                            <a href="{{ route('settings.reviews.locations.index') }}"
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

            <!-- Locations List -->
            <div class="card-body">
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 fw-bold">Listado de ubicaciones</h6>
                        <p class="text-muted small mb-0">Administra todas las ubicaciones sincronizadas</p>
                    </div>
                </div>

                <div class="alert alert-info border-0 bg-info-subtle mb-3">
                    <div class="d-flex align-items-start gap-2">
                        <div>
                            <small class="fw-semibold">Importante:</small>
                            <small class="d-block">Las ubicaciones se sincronizan automaticamente cada hora. Puedes forzar una sincronizacion manual cuando sea necesario.</small>
                        </div>
                    </div>
                </div>

                @if(isset($locations) && $locations->count() > 0)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="select-all">
                            <label class="form-check-label  text-muted" for="select-all">Seleccionar todo</label>
                        </div>
                        <button type="button" class="btn btn-outline-primary d-none" id="bulk-sync-btn">
                            <i class="fas fa-sync me-1"></i>Sincronizar (<span id="selected-count">0</span>)
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="30"></th>
                                    <th>Nombre</th>
                                    <th>Direccion</th>
                                    <th>Telefono</th>
                                    <th class="text-center">Rating</th>
                                    <th class="text-center">Reseñas</th>
                                    <th class="text-center">Estado sync</th>
                                    <th>Ultima sync</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($locations as $location)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input location-checkbox" value="{{ $location->id }}">
                                        </td>
                                        <td>
                                            <strong>{{ $location->name }}</strong>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ Str::limit($location->address, 40) }}</small>
                                        </td>
                                        <td>
                                            <small>{{ $location->phone ?? '-' }}</small>
                                        </td>
                                        <td class="text-center">
                                            @if($location->average_rating)
                                                <div class="d-flex align-items-center justify-content-center gap-1">
                                                    @php
                                                        $rating = round($location->average_rating);
                                                    @endphp
                                                    @for($i = 1; $i <= 5; $i++)
                                                        @if($i <= $rating)
                                                            <i class="fas fa-star text-warning small"></i>
                                                        @else
                                                            <i class="far fa-star text-muted small"></i>
                                                        @endif
                                                    @endfor
                                                    <small class="text-muted ms-1">{{ number_format($location->average_rating, 1) }}</small>
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark text-black">{{ number_format($location->reviews_count ?? 0) }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($location->sync_status === 'syncing')
                                                <span class="badge bg-primary-subtle text-white">
                                                    <i class="fas fa-spinner fa-spin"></i> Sincronizando
                                                </span>
                                            @elseif($location->sync_status === 'error')
                                                <span class="badge bg-danger-subtle text-white">
                                                    <i class="fas fa-exclamation-circle"></i> Error
                                                </span>
                                            @else
                                                <span class="badge bg-success-subtle text-white">
                                                    <i class="fas fa-check"></i> OK
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($location->last_synced_at)
                                                <small class="text-muted">{{ $location->last_synced_at->diffForHumans() }}</small>
                                            @else
                                                <small class="text-muted">Nunca</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-flex justify-content-center">
                                                <input class="form-check-input toggle-location-active"
                                                       type="checkbox"
                                                       data-location-id="{{ $location->id }}"
                                                       {{ $location->is_active ? 'checked' : '' }}>
                                            </div>
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
                                                        <a class="dropdown-item sync-location" href="#" data-location-id="{{ $location->id }}">
                                                            Sincronizar ahora
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
                            <i class="fas fa-map-marker-alt fa-4x text-muted opacity-50"></i>
                        </div>
                        <h5 class="text-muted mb-2">No hay ubicaciones sincronizadas</h5>
                        <p class="text-muted small mb-4">Conecta una cuenta de Google para sincronizar tus ubicaciones</p>
                        <a href="{{ route('settings.reviews.connections.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                        </a>
                    </div>
                @endif
            </div>

            @if(isset($locations) && $locations->hasPages())
                <div class="card-footer">{{ $locations->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Modal sincronización masiva --}}
    <div id="sync-all-modal" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sincronizar todas las ubicaciones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="display-4 text-primary mb-3">
                        <i class="fas fa-sync"></i>
                    </div>
                    <h4 class="mb-3">¿Sincronizar todas las ubicaciones activas?</h4>
                    <p class="text-muted">Se iniciará la sincronización de reseñas para todas las ubicaciones activas. Este proceso puede tardar unos minutos.</p>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary" id="confirm-sync-all">
                            Sincronizar ahora
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('modules/reviews/js/locations-manage.js') }}"></script>
<script>
$(document).ready(function() {
    // Sincronizar todas las ubicaciones
    $('#confirm-sync-all').on('click', function() {
        const btn = $(this);

        btn.prop('disabled', true).html('Sincronizando...');

        $.post('{{ route("settings.reviews.locations.sync-all") }}', {
            _token: $('meta[name="csrf-token"]').attr('content')
        }).done(function(response) {
            toastr.success(response.message || 'Sincronización iniciada');
            $('#sync-all-modal').modal('hide');
            setTimeout(() => location.reload(), 2000);
        }).fail(function() {
            btn.prop('disabled', false).html('Sincronizar ahora');
            toastr.error('Error al iniciar sincronización');
        });
    });

    // Bulk selection
    const $selectAll = $('#select-all');
    const $checkboxes = $('.location-checkbox');
    const $bulkBtn = $('#bulk-sync-btn');
    const $selectedCount = $('#selected-count');

    function updateBulkState() {
        const selected = $checkboxes.filter(':checked');
        const count = selected.length;

        $bulkBtn.toggleClass('d-none', count === 0);
        $selectedCount.text(count);
    }

    $selectAll.on('change', function() {
        $checkboxes.prop('checked', $(this).is(':checked'));
        updateBulkState();
    });

    $checkboxes.on('change', updateBulkState);

    // Bulk sync
    $bulkBtn.on('click', function() {
        const selected = $checkboxes.filter(':checked');
        const ids = selected.map(function() { return $(this).val(); }).get();

        if (!confirm(`¿Sincronizar ${ids.length} ubicaciones seleccionadas?`)) {
            return;
        }

        $bulkBtn.prop('disabled', true).html('Sincronizando...');

        $.post('{{ route("settings.reviews.locations.bulk-sync") }}', {
            _token: $('meta[name="csrf-token"]').attr('content'),
            ids: JSON.stringify(ids)
        }).done(function(response) {
            toastr.success(response.message || 'Sincronización iniciada');
            setTimeout(() => location.reload(), 2000);
        }).fail(function() {
            $bulkBtn.prop('disabled', false).html('Sincronizar (<span id="selected-count">' + ids.length + '</span>)');
            toastr.error('Error al iniciar sincronización');
        });
    });
});
</script>
@endpush

