@extends('layouts.theme')

@section('title', 'Filtros guardados')

@section('content')

    @include('core::components.card', ['title' => 'Filtros guardados de reseñas'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Filtros guardados</h5>
                        <p class="small mb-0 text-muted">Accede rápidamente a tus configuraciones de búsqueda guardadas</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('reviews.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                <small class="text-muted">Filtros guardados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Predeterminados</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['defaults'] }}</h4>
                                <small class="text-muted">Aplicados automáticamente</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                    <div class="flex-fill">
                        <div class="input-group h-100">
                            <span class="input-group-text bg-white border-end-1">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="search" id="searchFilter" class="form-control border-start-0 ps-0"
                                   placeholder="Buscar por nombre...">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($filters->isEmpty())
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-filter fs-7"></i>
                            </div>
                            <h6 class="mb-1">No tienes filtros guardados</h6>
                            <p class="text-muted mb-3">Configura tus filtros en la lista de reseñas y guárdalos para acceso rápido.</p>
                            <a href="{{ route('reviews.index') }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-list me-1"></i> Ir a reseñas
                            </a>
                        </div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Nombre</th>
                                    <th class="text-center">Predeterminado</th>
                                    <th>Creado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="filtersBody">
                                @foreach($filters as $filter)
                                    <tr class="filter-row" data-filter-id="{{ $filter->id }}"
                                        data-name="{{ strtolower($filter->name) }}">
                                        @if($filter->user_id === $userId)
                                            <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $filter->id }}"></td>
                                        @else
                                            <td></td>
                                        @endif
                                        <td>
                                            <strong>{{ $filter->name }}</strong>
                                            @if($filter->is_default)
                                                <span class="badge bg-success-subtle text-success ms-2">Predeterminado</span>
                                            @endif
                                            @if($filter->is_shared && $filter->user_id === $userId)
                                                <span class="badge bg-info-subtle text-info ms-1">Compartido</span>
                                            @endif
                                            @if($filter->user_id !== $userId)
                                                <span class="badge bg-secondary-subtle text-secondary ms-1">
                                                    De {{ $filter->sharedBy ? $filter->sharedBy->firstname.' '.$filter->sharedBy->lastname : 'otro usuario' }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($filter->user_id === $userId)
                                                @if($filter->is_default)
                                                    <span class="badge bg-success-subtle text-success">Sí</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <p class="text-muted">{{ $filter->created_at->format('d/m/Y H:i') }}</p>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item btn-apply-filter" href="javascript:void(0)"
                                                           data-filter-id="{{ $filter->id }}"
                                                           data-url="{{ route('reviews.saved-filters.apply', $filter) }}">
                                                            Aplicar
                                                        </a>
                                                    </li>
                                                    @if($filter->user_id === $userId)
                                                        @if(!$filter->is_default)
                                                            <li>
                                                                <a class="dropdown-item btn-set-default" href="javascript:void(0)"
                                                                   data-url="{{ route('reviews.saved-filters.set-default', $filter) }}">
                                                                    Establecer predeterminado
                                                                </a>
                                                            </li>
                                                        @endif
                                                        <li>
                                                            <a class="dropdown-item btn-share" href="javascript:void(0)"
                                                               data-url="{{ route('reviews.saved-filters.share', $filter) }}"
                                                               data-shared="{{ $filter->is_shared ? '1' : '0' }}"
                                                               data-filter-name="{{ $filter->name }}">
                                                                {{ $filter->is_shared ? 'Dejar de compartir' : 'Compartir' }}
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item btn-rename" href="javascript:void(0)"
                                                               data-url="{{ route('reviews.saved-filters.update', $filter) }}"
                                                               data-filter-id="{{ $filter->id }}"
                                                               data-filter-name="{{ $filter->name }}">
                                                                Renombrar
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item delete-btn"
                                                               data-bs-toggle="modal" data-bs-target="#delete-modal"
                                                               data-url="{{ route('reviews.saved-filters.destroy', $filter) }}"
                                                               data-title="Eliminar: {{ $filter->name }}">
                                                                Eliminar
                                                            </a>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div id="noResults" class="text-center py-5 d-none">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-search fs-7"></i>
                            </div>
                            <h6 class="mb-1">No se encontraron filtros</h6>
                            <p class="text-muted mb-0">No hay resultados para los criterios de búsqueda</p>
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- Bulk Toolbar --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
        </button>
    </div>

    {{-- Bulk Modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> filtro(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="delete">Eliminar</option>
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

    {{-- Rename Modal --}}
    <div class="modal fade" id="renameModal" tabindex="-1" aria-labelledby="renameModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="renameModalLabel">Renombrar filtro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Asigna un nombre descriptivo que te ayude a identificar rápidamente los criterios de este filtro.</p>
                    <form id="rename-form">
                        <input type="hidden" id="rename-filter-id">
                        <input type="hidden" id="rename-filter-url">
                        <div class="mb-0">
                            <label for="filter-name" class="form-label">Nombre del filtro</label>
                            <input type="text" class="form-control" id="filter-name" required maxlength="100"
                                   placeholder="Ej: Reseñas negativas sin responder">
                            <div class="form-text">Máximo 100 caracteres.</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary  w-100 mb-2" id="btn-save-rename">Guardar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Share Modal --}}
    <div class="modal fade" id="shareModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shareModalLabel">Compartir filtro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="share-confirm-section">
                        <p class="text-muted mb-2">Vas a compartir el filtro <strong id="share-filter-name"></strong>.</p>
                        <div class="alert alert-info mb-0">
                            Al compartirlo, todos los usuarios con acceso a las reseñas podrán ver y aplicar este filtro.
                        </div>
                    </div>
                    <div id="unshare-confirm-section">
                        <p class="text-muted mb-2">Vas a dejar de compartir el filtro <strong id="unshare-filter-name"></strong>.</p>
                        <div class="alert alert-warning mb-0">
                            Los demás usuarios ya no podrán ver ni aplicar este filtro.
                        </div>
                    </div>
                    <input type="hidden" id="share-filter-url">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100 mb-1" id="btn-confirm-share">Confirmar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

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
        if (!ids.length) { toastr.warning('Selecciona al menos un filtro.'); return; }
        

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('reviews.saved-filters.bulk-delete') }}',
            method: 'DELETE',
            data: JSON.stringify({ ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.count + ' filtro(s) eliminado(s).');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    // Delete modal
    $('.delete-btn').on('click', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    // Client-side search
    $('#searchFilter').on('input', function () {
        const search = $(this).val().toLowerCase();
        let visible = 0;

        $('.filter-row').each(function () {
            const match = !search || $(this).data('name').includes(search);
            $(this).toggleClass('d-none', !match);
            if (match) visible++;
        });

        $('#noResults').toggleClass('d-none', visible > 0);
    });

    // Apply filter
    $(document).on('click', '.btn-apply-filter', function () {
        const url = $(this).data('url');
        $('<form>', { method: 'POST', action: url })
            .append($('<input>', { type: 'hidden', name: '_token', value: csrfToken }))
            .appendTo('body')
            .submit();
    });

    // Set as default
    $(document).on('click', '.btn-set-default', function () {
        $.ajax({
            url: $(this).data('url'),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (response) {
                toastr.success(response.message || 'Filtro establecido como predeterminado');
                setTimeout(() => location.reload(), 600);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al establecer filtro predeterminado');
            }
        });
    });

    // Share / unshare — open modal
    $(document).on('click', '.btn-share', function () {
        const isShared = $(this).data('shared') === 1 || $(this).data('shared') === '1';
        const name     = $(this).data('filter-name');
        const url      = $(this).data('url');

        $('#share-filter-url').val(url);

        if (isShared) {
            $('#share-confirm-section').addClass('d-none');
            $('#unshare-confirm-section').removeClass('d-none');
            $('#unshare-filter-name').text(name);
            $('#shareModalLabel').text('Dejar de compartir filtro');
        } else {
            $('#unshare-confirm-section').addClass('d-none');
            $('#share-confirm-section').removeClass('d-none');
            $('#share-filter-name').text(name);
            $('#shareModalLabel').text('Compartir filtro');
        }

        $('#shareModal').modal('show');
    });

    // Share confirm
    $('#btn-confirm-share').on('click', function () {
        const url = $('#share-filter-url').val();
        const btn = $(this);
        btn.prop('disabled', true).text('Procesando...');

        $.ajax({
            url,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (response) {
                toastr.success(response.message);
                $('#shareModal').modal('hide');
                setTimeout(() => location.reload(), 600);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al cambiar estado de compartido');
            },
            complete: function () {
                btn.prop('disabled', false).text('Confirmar');
            }
        });
    });

    $('#shareModal').on('hidden.bs.modal', function () {
        $('#share-confirm-section, #unshare-confirm-section').removeClass('d-none');
    });

    // Rename
    $(document).on('click', '.btn-rename', function () {
        $('#rename-filter-id').val($(this).data('filter-id'));
        $('#rename-filter-url').val($(this).data('url'));
        $('#filter-name').val($(this).data('filter-name'));
        $('#renameModal').modal('show');
    });

    $('#btn-save-rename').on('click', function () {
        const url     = $('#rename-filter-url').val();
        const newName = $('#filter-name').val().trim();

        if (!newName) { toastr.error('El nombre es obligatorio'); return; }

        const btn = $(this);
        btn.prop('disabled', true);

        $.ajax({
            url,
            method: 'PUT',
            data: { name: newName, filters_json: '{}' },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (response) {
                toastr.success(response.message || 'Filtro renombrado correctamente');
                $('#renameModal').modal('hide');
                setTimeout(() => location.reload(), 600);
            },
            error: function (xhr) {
                const message = xhr.status === 422
                    ? Object.values(xhr.responseJSON?.errors ?? {}).flat().join('\n')
                    : (xhr.responseJSON?.message || 'Error al renombrar filtro');
                toastr.error(message);
            },
            complete: function () { btn.prop('disabled', false); }
        });
    });
});
</script>
@endpush
