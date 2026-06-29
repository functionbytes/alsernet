@extends('layouts.theme')

@section('title', 'Ubicaciones Google')

@section('page_header')
    @include('core::components.card', ['title' => 'Ubicaciones de Google My Business'])
@endsection

@section('content')
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
                        <a href="{{ route('settings.reviews.locations.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nueva ubicación
                        </a>
                        <button class="btn btn-outline-secondary"
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

            <!-- Locations List -->
            <div class="card-body">
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 fw-bold">Listado de ubicaciones</h6>
                        <p class="text-muted mb-0">Administra todas las ubicaciones sincronizadas</p>
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
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="30"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Nombre</th>
                                    <th>Estrategia</th>
                                    <th class="text-center">Rating</th>
                                    <th class="text-center">Reseñas</th>
                                    <th class="text-center">Estado sync</th>
                                    <th>Ultima sync</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($locations as $location)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $location->id }}">
                                        </td>
                                        <td>
                                            <strong>{{ $location->name }}</strong>
                                        </td>
                                        <td>
                                            @php
                                                $strategyLabels = [
                                                    'oauth'      => ['label' => 'OAuth', 'class' => 'bg-primary'],
                                                    'places_api' => ['label' => 'Places API', 'class' => 'bg-info text-dark'],
                                                    'serpapi'    => ['label' => 'SerpAPI', 'class' => 'bg-warning text-dark'],
                                                    'manual'     => ['label' => 'Manual', 'class' => 'bg-secondary'],
                                                ];
                                                $strategy = $strategyLabels[$location->sync_strategy?->value ?? 'oauth'] ?? $strategyLabels['oauth'];
                                            @endphp
                                            <span class="badge {{ $strategy['class'] }}">{{ $strategy['label'] }}</span>
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
                                                            <i class="far fa-star text-muted"></i>
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
                                                    Sincronizando
                                                </span>
                                            @elseif($location->sync_status === 'error')
                                                <span class="badge bg-danger-subtle text-white">
                                                    Error
                                                </span>
                                            @else
                                                <span class="badge bg-success-subtle text-white">
                                                    OK
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($location->synced_at)
                                                <span class="text-muted">{{ $location->synced_at->diffForHumans() }}</span>
                                            @else
                                                <span class="text-muted">Nunca</span>
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
                                                        <a class="dropdown-item sync-location" href="#" data-location-id="{{ $location->id }}">
                                                            Sincronizar ahora
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.reviews.locations.import.create', $location) }}">
                                                            Importar reseñas
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item manage-tags-btn" href="#"
                                                           data-location-id="{{ $location->id }}"
                                                           data-location-name="{{ $location->name }}"
                                                           data-tags-url="{{ route('settings.reviews.locations.tags.index', $location) }}">
                                                            Gestionar tags
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
                        <p class="text-muted mb-4">Conecta una cuenta de Google para sincronizar tus ubicaciones</p>
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
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> ubicación(es)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="activate">Activar</option>
                            <option value="deactivate">Desactivar</option>
                            <option value="sync">Sincronizar</option>
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

    {{-- Modal gestionar tags de ubicación --}}
    <div class="modal fade" id="tags-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tags de <span id="tags-modal-location-name"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="tags-list" class="mb-3">
                        <p class="text-muted text-center py-2">Cargando...</p>
                    </div>
                    <hr>
                    <p class="fw-semibold mb-2">Añadir tag</p>
                    <div class="row g-2">
                        <div class="col-12">
                            <input type="text" class="form-control" id="tag-label" placeholder="Etiqueta (ej: Ventanas PVC)">
                        </div>
                        <div class="col-sm-6">
                            <input type="text" class="form-control" id="tag-slug" placeholder="Slug (ej: ventanas)">
                        </div>
                        <div class="col-sm-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-tag" id="tag-icon-preview"></i></span>
                                <input type="text" class="form-control" id="tag-icon" placeholder="fa-tag">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-block">
                    <button type="button" class="btn btn-primary w-100 mb-2" id="tag-add-btn">Añadir tag</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
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
        if (!ids.length) { toastr.warning('Selecciona al menos una ubicación.'); return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('settings.reviews.locations.bulk-action') }}',
            method: 'POST',
            data: JSON.stringify({ action, ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.count + ' ubicación(es) actualizadas.');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });
});

// Tags management modal
(function () {
    let currentTagsUrl   = null;
    let currentStoreUrl  = null;
    let currentDestroyBaseUrl = null;

    function slugify(str) {
        return str.toLowerCase().trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-');
    }

    function renderTags(tags) {
        if (!tags.length) {
            $('#tags-list').html('<p class="text-muted text-center py-2">No hay tags definidos.</p>');
            return;
        }
        const items = tags.map(function (t) {
            return `<div class="d-flex align-items-center justify-content-between p-2 border rounded mb-1" data-slug="${t.slug}">
                <span><i class="fas ${t.icon ?? 'fa-tag'} me-2 text-muted"></i>${t.label} <small class="text-muted">(${t.slug})</small></span>
                <button type="button" class="btn btn-sm btn-link text-muted p-0 delete-tag-btn" data-slug="${t.slug}">
                    <i class="fas fa-times"></i>
                </button>
            </div>`;
        });
        $('#tags-list').html(items.join(''));
    }

    function loadTags() {
        $.get(currentTagsUrl).done(function (res) {
            renderTags(res.tags || []);
        }).fail(function () {
            $('#tags-list').html('<p class="text-danger text-center py-2">Error al cargar tags.</p>');
        });
    }

    $(document).on('click', '.manage-tags-btn', function (e) {
        e.preventDefault();
        const btn = $(this);
        currentTagsUrl       = btn.data('tags-url');
        currentStoreUrl      = currentTagsUrl;
        currentDestroyBaseUrl = currentTagsUrl + '/';

        $('#tags-modal-location-name').text(btn.data('location-name'));
        $('#tag-label, #tag-slug, #tag-icon').val('');
        $('#tag-icon-preview').attr('class', 'fas fa-tag');
        $('#tags-modal').modal('show');
        loadTags();
    });

    $(document).on('input', '#tag-label', function () {
        if (!$('#tag-slug').val()) {
            $('#tag-slug').val(slugify($(this).val()));
        }
    });

    $(document).on('input', '#tag-icon', function () {
        const val = $(this).val().trim();
        $('#tag-icon-preview').attr('class', 'fas ' + (val || 'fa-tag'));
    });

    $(document).on('click', '#tag-add-btn', function () {
        const label = $('#tag-label').val().trim();
        const slug  = $('#tag-slug').val().trim();
        const icon  = $('#tag-icon').val().trim() || 'fa-tag';

        if (!label || !slug) { toastr.warning('La etiqueta y el slug son obligatorios.'); return; }

        const btn = $(this).prop('disabled', true).text('Guardando...');
        $.ajax({
            url: currentStoreUrl,
            method: 'POST',
            data: { label, slug, icon, _token: $('meta[name="csrf-token"]').attr('content') },
        }).done(function () {
            $('#tag-label, #tag-slug, #tag-icon').val('');
            $('#tag-icon-preview').attr('class', 'fas fa-tag');
            toastr.success('Tag añadido');
            loadTags();
        }).fail(function (xhr) {
            toastr.error(xhr.responseJSON?.message ?? 'Error al añadir tag');
        }).always(function () {
            btn.prop('disabled', false).text('Añadir tag');
        });
    });

    $(document).on('click', '.delete-tag-btn', function () {
        const slug = $(this).data('slug');
        $.ajax({
            url: currentDestroyBaseUrl + slug,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        }).done(function () {
            toastr.success('Tag eliminado');
            loadTags();
        }).fail(function () {
            toastr.error('Error al eliminar tag');
        });
    });
})();
</script>
@endpush

