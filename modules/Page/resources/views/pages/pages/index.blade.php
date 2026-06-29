@extends('layouts.theme')

@section('title', 'Páginas')

@section('page_header')
    @include('core::components.card', ['title' => 'Páginas'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Páginas del sitio</h5>
                        <p class="small mb-0 text-muted">Administra las páginas estáticas del sitio web</p>
                    </div>
                    @php
                        $hasActiveFilters = collect($filters ?? [])->filter(fn($v, $k) => !empty($v) && !in_array($k, ['sort_by', 'sort_order', 'per_page']))->isNotEmpty();
                    @endphp
                    <div class="ms-auto">
                        @if(!$trashed)
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('pages.create') }}">Nueva página</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{ route('pages.export.download', ['format' => 'csv']) }}">Exportar CSV</a>
                                <a class="dropdown-item" href="{{ route('pages.export.download', ['format' => 'json']) }}">Exportar JSON</a>
                                <a class="dropdown-item" href="{{ route('pages.import') }}">Importar páginas</a>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Total páginas</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
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
                                        <h6 class="card-title mb-2">Publicadas</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['published']) }}</h4>
                                        <small class="text-muted">Visibles en el sitio</small>
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
                                        <h6 class="card-title mb-2">Borradores</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['draft']) }}</h4>
                                        <small class="text-muted">Sin publicar</small>
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
                                        <h6 class="card-title mb-2">Pendientes</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['pending']) }}</h4>
                                        <small class="text-muted">En revisión</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if(!$trashed)
            {{-- Filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('pages.index') }}" id="filter-form">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control -0 ps-0"
                                       placeholder="Buscar por título o slug..."
                                       value="{{ $filters['search'] ?? '' }}">
                            </div>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 170px;">
                            <select class="form-select select2 h-100" name="status">
                                <option value="">Todos los estados</option>
                                @foreach(['draft' => 'Borrador', 'published' => 'Publicado', 'pending' => 'Pendiente'] as $key => $label)
                                    <option value="{{ $key }}" {{ ($filters['status'] ?? '') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 150px;">
                            <input type="date" name="date_from" class="form-control h-100" value="{{ $filters['date_from'] ?? '' }}">
                        </div>
                        <div class="flex-shrink-0" style="min-width: 150px;">
                            <input type="date" name="date_to" class="form-control h-100" value="{{ $filters['date_to'] ?? '' }}">
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            @if($hasActiveFilters)
                                <a href="{{ route('pages.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
            @endif

            {{-- Tabs --}}
            <ul class="nav nav-tabs border-0 user-profile-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ !$trashed && ($filters['status'] ?? '') === '' ? 'active' : '' }}"
                       href="{{ route('pages.index') }}" role="tab">
                        <span class="d-none d-md-block">Todas</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ !$trashed && ($filters['status'] ?? '') === 'published' ? 'active' : '' }}"
                       href="{{ route('pages.index', ['status' => 'published']) }}" role="tab">
                        <span class="d-none d-md-block">Publicadas</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ !$trashed && ($filters['status'] ?? '') === 'draft' ? 'active' : '' }}"
                       href="{{ route('pages.index', ['status' => 'draft']) }}" role="tab">
                        <span class="d-none d-md-block">Borradores</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $trashed ? 'active' : '' }}"
                       href="{{ route('pages.index', ['trashed' => 1]) }}" role="tab">
                        <span class="d-none d-md-block">Papelera</span>
                    </a>
                </li>
            </ul>

            {{-- Table --}}
            <div class="card-body">
                @if($trashed)
                    {{-- Trashed pages --}}
                    @if($pages->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                        <th>Título</th>
                                        <th class="text-center">Estado</th>
                                        <th>Eliminada</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pages as $page)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $page->id }}">
                                            </td>
                                            <td>
                                                <span class="fw-semibold text-muted">{{ $page->title }}</span>
                                                <small class="text-muted d-block">/{{ $page->slug }}</small>
                                            </td>
                                            <td class="text-center">
                                                @if($page->isPublished())
                                                    <span class="badge bg-success-subtle text-success">Publicado</span>
                                                @elseif($page->isDraft())
                                                    <span class="badge bg-secondary-subtle text-secondary">Borrador</span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning">Pendiente</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $page->deleted_at->format('d/m/Y H:i') }}</small>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-2 justify-content-center">
                                                    <form action="{{ route('pages.restore', $page->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Restaurar página">
                                                            <i class="fas fa-rotate-left me-1"></i>Restaurar
                                                        </button>
                                                    </form>
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-danger force-delete-btn"
                                                            data-url="{{ route('pages.force-delete', $page->id) }}"
                                                            data-title="{{ $page->title }}">
                                                        <i class="fas fa-trash me-1"></i>Eliminar definitivamente
                                                    </button>
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
                                <i class="fas fa-trash fa-4x text-muted opacity-50"></i>
                            </div>
                            <h5 class="text-muted mb-2">La papelera está vacía</h5>
                            <p class="text-muted mb-0">No hay páginas eliminadas en este momento</p>
                        </div>
                    @endif

                @else
                    {{-- Normal pages --}}
                    @if($pages->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                        <th>Título</th>
                                        <th class="text-center">Idiomas</th>
                                        <th class="text-center">Estado</th>
                                        <th>Fecha</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pages as $page)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $page->id }}">
                                            </td>
                                            <td>
                                                <a href="{{ route('pages.edit', $page->id) }}" class="text-decoration-none fw-semibold">
                                                    {{ $page->title }}
                                                </a>
                                                @if($page->categories->isNotEmpty())
                                                    <div class="mt-1">
                                                        @foreach($page->categories as $cat)
                                                            <a href="{{ route('pages.index', ['category' => $cat->slug]) }}"
                                                               class="badge text-decoration-none me-1"
                                                               style="background-color: {{ $cat->color }}">
                                                                {{ $cat->name }}
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                @endif
                                                @if($page->tags->isNotEmpty())
                                                    <div class="mt-1">
                                                        @foreach($page->tags as $tag)
                                                            <span class="badge bg-light text-secondary border me-1">{{ $tag->name }}</span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                                @if($page->featured_image)
                                                    <small class="text-muted d-block mt-1"><i class="fas fa-image me-1"></i>Con imagen destacada</small>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @foreach($page->translations->sortBy('locale') as $trans)
                                                    @php $isPublished = ($trans->status instanceof \Modules\Page\Enums\PageStatus ? $trans->status->value : $trans->status) === 'published'; @endphp
                                                    <span class="badge {{ $isPublished ? 'bg-success' : 'bg-secondary' }} me-1"
                                                          data-bs-toggle="tooltip"
                                                          data-bs-placement="top"
                                                          title="{{ $isPublished ? '✓ Publicado' : '○ Borrador' }}: /{{ $trans->slug }}">
                                                        {{ strtoupper($trans->locale) }} {{ $isPublished ? '●' : '○' }}
                                                    </span>
                                                @endforeach
                                                @if($page->translations->isEmpty())
                                                    <span class="badge bg-light text-muted border">Sin traducción</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($page->isPublished())
                                                    <span class="badge bg-success-subtle text-success">Publicado</span>
                                                    @if($page->willBeUnpublished())
                                                        <br><small class="text-muted"><i class="fas fa-clock"></i> {{ $page->unpublish_at->format('d/m/Y H:i') }}</small>
                                                    @endif
                                                @elseif($page->isDraft())
                                                    <span class="badge bg-secondary-subtle text-secondary">Borrador</span>
                                                    @if($page->willBePublished())
                                                        <br><small class="text-muted"><i class="fas fa-clock"></i> {{ $page->publish_at->format('d/m/Y H:i') }}</small>
                                                    @endif
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning">Pendiente</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ $page->created_at->format('d/m/Y') }}</span>
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
                                                            <a href="{{ $page->url }}" class="dropdown-item" target="_blank">
                                                                Ver
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('pages.edit', $page->id) }}" class="dropdown-item">
                                                                Editar
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('pages.versions.index', $page->id) }}" class="dropdown-item">
                                                                Versiones
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('pages.analytics.view', $page->id) }}" class="dropdown-item">
                                                                Analytics
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('pages.edit', $page->id) }}#performance"
                                                               class="dropdown-item">
                                                                Analizar PageSpeed
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <form method="POST" action="{{ route('pages.duplicate', $page->id) }}" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item">
                                                                    Duplicar
                                                                </button>
                                                            </form>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button type="button"
                                                                    class="dropdown-item delete-btn"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#delete-modal"
                                                                    data-url="{{ route('pages.destroy', $page->id) }}"
                                                                    data-title="Eliminar página: {{ $page->title }}">
                                                                Eliminar
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
                    @else
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-file-alt fa-4x text-muted opacity-50"></i>
                            </div>
                            <h5 class="text-muted mb-2">No hay páginas para mostrar</h5>
                            <p class="text-muted mb-4">
                                @if($hasActiveFilters)
                                    No se encontraron resultados con los filtros aplicados
                                @else
                                    Crea tu primera página para el sitio web
                                @endif
                            </p>
                            @if(!$hasActiveFilters)
                                <a href="{{ route('pages.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i> Crear primera página
                                </a>
                            @endif
                        </div>
                    @endif
                @endif
            </div>

            @if($pages->hasPages())
                <div class="card-footer">{{ $pages->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
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
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> página(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            @if($trashed)
                                <option value="restore">Restaurar</option>
                            @else
                                @if(($filters['status'] ?? '') !== 'published')
                                    <option value="publish">Publicar</option>
                                @endif
                                @if(($filters['status'] ?? '') !== 'draft')
                                    <option value="unpublish">Despublicar</option>
                                @endif
                                <option value="delete">Eliminar</option>
                            @endif
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

    {{-- Force-delete confirmation modal (trashed view) --}}
    @if($trashed)
    <div class="modal fade" id="force-delete-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-triangle-exclamation me-2"></i>Eliminar permanentemente
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-1">¿Estás seguro que deseas eliminar permanentemente la página:</p>
                    <p class="fw-semibold" id="force-delete-title"></p>
                    <p class="text-danger small mb-0">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form id="force-delete-form" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Eliminar definitivamente
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @else
        @include('core::components.delete')
    @endif
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
    toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
    toastr.error('{{ session('error') }}', 'Error');
    @endif

    // Bulk actions (toolbar flotante + modal)
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        var action = $('#bulk-action-select').val();
        var ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos una página.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar las ' + ids.length + ' página(s) seleccionadas?')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route("pages.bulk-action") }}',
            method: 'POST',
            data: JSON.stringify({ action: action, ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.message || ids.length + ' página(s) actualizadas.');
                setTimeout(function () { location.reload(); }, 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    @if($trashed)
    // Force-delete modal
    $(document).on('click', '.force-delete-btn', function () {
        $('#force-delete-form').attr('action', $(this).data('url'));
        $('#force-delete-title').text($(this).data('title'));
        new bootstrap.Modal(document.getElementById('force-delete-modal')).show();
    });
    @else
    // Delete modal
    $('.delete-btn').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $('#delete-form').attr('action', $(this).data('url'));
        new bootstrap.Modal(document.getElementById('delete-modal')).show();
    });

    // Tooltips
    $('[data-bs-toggle="tooltip"]').each(function () {
        new bootstrap.Tooltip(this);
    });
    @endif
});
</script>
@endpush
