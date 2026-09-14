@extends('layouts.theme')

@section('title', 'Categorías del centro de ayuda')

@section('page_header')
    @include('core::components.card', ['title' => 'Centro de ayuda — Categorías'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Categorías</h5>
                        <p class="small mb-0 text-muted">Organiza tu base de conocimiento en categorías y secciones</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('manager.helpcenter.categories.create') }}">
                                    Nueva categoría
                                </a>
                                <a class="dropdown-item" href="{{ route('manager.helpcenter.sections.create') }}">
                                    Nueva sección
                                </a>
                                <a class="dropdown-item" href="{{ route('manager.helpcenter.articles.create') }}">
                                    Nuevo artículo
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Categorías</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total_categories']) }}</h4>
                                <small class="text-muted">Categorías raíz</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Secciones</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total_sections']) }}</h4>
                                <small class="text-muted">Subcategorías</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total artículos</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total_articles']) }}</h4>
                                <small class="text-muted">En todas las secciones</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Publicados</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['published_articles']) }}</h4>
                                <small class="text-muted">Visibles al público</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Busqueda y filtros --}}
            <div class="card-body border-bottom">
                @php
                    $activeFilterCount = collect(['visible_to_role', 'content'])->filter(fn ($k) => request()->filled($k))->count();
                    $hasAnyFilter = $activeFilterCount > 0 || request()->filled('search');
                    $contentLabels = ['empty' => 'Vacias', 'with' => 'Con contenido'];
                @endphp

                <form method="GET" action="{{ route('manager.helpcenter.categories') }}" id="categories-filter-form">
                    <input type="hidden" name="visible_to_role" id="filter-role" value="{{ request('visible_to_role') }}">
                    <input type="hidden" name="content" id="filter-content" value="{{ request('content') }}">

                    <div class="d-flex align-items-center gap-2">
                        <input type="search" name="search" class="form-control flex-grow-1"
                               placeholder="Buscar por nombre..."
                               value="{{ request('search') }}">

                        <x-filter-button target="categories-filter-modal" :count="$activeFilterCount" />

                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="submit" class="btn btn-primary" title="Buscar">
                                <i class="fas fa-magnifying-glass"></i>
                            </button>
                            @if($hasAnyFilter)
                                <a href="{{ route('manager.helpcenter.categories') }}" class="btn btn-secondary" title="Limpiar filtros">
                                    <i class="fas fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    @if($activeFilterCount > 0)
                        <div class="d-flex gap-2 flex-wrap mt-4 align-items-center">
                            <h6 class="mb-0">Filtrados:</h6>
                            @if(request('visible_to_role'))
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">Rol: {{ request('visible_to_role') }}</span>
                            @endif
                            @if(request('content'))
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">{{ $contentLabels[request('content')] ?? request('content') }}</span>
                            @endif
                        </div>
                    @endif
                </form>
            </div>

            {{-- Tabla --}}
            <div class="card-body">
                @if($categories->isEmpty())
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="far fa-folder-tree fs-7"></i>
                            </div>
                            <h6 class="mb-1">
                                @if(request('search'))
                                    No se encontraron categorías
                                @else
                                    Aún no hay categorías
                                @endif
                            </h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    Ajusta los filtros para ver resultados
                                @else
                                    Crea la primera categoría para comenzar
                                @endif
                            </p>
                            @if(! request('search'))
                                <a href="{{ route('manager.helpcenter.categories.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus me-1"></i> Nueva categoría
                                </a>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th class="text-center">Secciones</th>
                                    <th class="text-center">Artículos</th>
                                    <th class="text-center">Posición</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categories as $category)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $category->id }}"></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @if($category->icon)
                                                    {{-- Los iconos guardados vienen como "fa-truck", sin la clase de
                                                         estilo: sin ella heredan la fuente del tema y salen en blanco. --}}
                                                    @php($hcIcon = preg_match('/\b(fa[srlbd]|fa-solid|fa-regular|fa-light|fa-brands|fa-duotone)\b/', $category->icon) ? $category->icon : 'fas '.$category->icon)
                                                    <i class="{{ $hcIcon }} text-muted"></i>
                                                @else
                                                    <i class="far fa-folder text-muted"></i>
                                                @endif
                                                <span class="fw-semibold">{{ $category->name }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ Str::limit($category->description, 60) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light-primary text-primary">
                                                {{ $category->sections_count }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light-info text-info">
                                                {{ $category->articles_count }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light-secondary text-secondary">
                                                #{{ $category->position }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown"
                                                   aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="{{ route('manager.helpcenter.categories.show', $category->id) }}">
                                                            Ver categoría
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="{{ route('manager.helpcenter.categories.edit', $category->id) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="{{ route('manager.helpcenter.sections.create', ['parent_id' => $category->id]) }}">
                                                            Añadir sección
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn"
                                                           data-bs-toggle="modal" data-bs-target="#delete-modal"
                                                           data-url="{{ route('manager.helpcenter.categories.destroy', $category->id) }}"
                                                           data-title="Eliminar: {{ $category->name }}">
                                                            Eliminar
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>

                                    @if($category->sections_count > 0)
                                        @foreach($category->sections as $section)
                                            <tr class="bg-light-subtle">
                                                <td></td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2 ps-4">
                                                        <i class="fas fa-arrow-turn-down-right text-muted"></i>
                                                        <i class="fas fa-layer-group text-muted"></i>
                                                        <a href="{{ route('manager.helpcenter.sections.show', $section->id) }}"
                                                           class="text-decoration-none text-body">
                                                            {{ $section->name }}
                                                        </a>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="text-muted">{{ Str::limit($section->description, 60) }}</span>
                                                </td>
                                                <td class="text-center">—</td>
                                                <td class="text-center">
                                                    <span class="badge bg-light-info text-info">
                                                        {{ $section->articles_count }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-light-secondary text-secondary">
                                                        #{{ $section->position }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="dropdown">
                                                        <a href="#" class="text-muted" data-bs-toggle="dropdown"
                                                           aria-expanded="false">
                                                            <i class="fas fa-ellipsis-vertical"></i>
                                                        </a>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <a class="dropdown-item"
                                                                   href="{{ route('manager.helpcenter.sections.show', $section->id) }}">
                                                                    Ver sección
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item"
                                                                   href="{{ route('manager.helpcenter.sections.edit', $section->id) }}">
                                                                    Editar
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item"
                                                                   href="{{ route('manager.helpcenter.sections.articles.create', $section->id) }}">
                                                                    Añadir artículo
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a class="dropdown-item delete-btn"
                                                                   data-bs-toggle="modal" data-bs-target="#delete-modal"
                                                                   data-url="{{ route('manager.helpcenter.sections.destroy', $section->id) }}"
                                                                   data-title="Eliminar: {{ $section->name }}">
                                                                    Eliminar
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            @if($categories->hasPages())
                <div class="card-footer">{{ $categories->withQueryString()->links() }}</div>
            @endif

        </div>
    </div>

    @include('core::components.delete')

    {{-- Filtros avanzados --}}
    <x-filter-shell id="categories-filter-modal"
                    :count="$activeFilterCount"
                    apply-id="categories-filter-apply-btn"
                    clear-id="categories-filter-clear-btn">
        <div class="fs-field">
            <label class="form-label fw-semibold">Visible para el rol</label>
            <select id="modal-role" class="form-control select2-filter-modal">
                <option value="">Cualquier rol</option>
                @foreach($roles as $role)
                    <option value="{{ $role }}" @selected(request('visible_to_role') === $role)>{{ $role }}</option>
                @endforeach
            </select>
        </div>
        <div class="fs-field">
            <label class="form-label fw-semibold">Contenido</label>
            <select id="modal-content" class="form-control select2-filter-modal">
                <option value="">Todas</option>
                <option value="empty" @selected(request('content') === 'empty')>Vacias (se pueden borrar)</option>
                <option value="with" @selected(request('content') === 'with')>Con secciones o articulos</option>
            </select>
        </div>
    </x-filter-shell>

    {{-- Barra flotante de seleccion --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none hc-bulk-toolbar">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionada(s) &mdash; Aplicar accion
        </button>
    </div>

    {{-- Accion masiva --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Accion masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicara sobre <strong><span data-bulk-count>0</span> categoria(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Accion</label>
                        <select id="bulk-action-select" class="form-select select2-bulk">
                            <option value="">Seleccionar accion...</option>
                            <option value="delete">Eliminar</option>
                        </select>
                    </div>
                    <p class="small text-muted mb-0">
                        Las categorias que tengan secciones o articulos se omiten, igual que al borrarlas de una en una.
                    </p>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    .hc-bulk-toolbar { z-index: 1050; }
</style>
@endpush

@push('scripts')
<script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
<script>
$(document).ready(function () {
    // El contenedor de filtros es modal o panel lateral segun el .env.
    $('.select2-filter-modal').select2({
        dropdownParent: window.FilterShell.el('categories-filter-modal'),
        width: '100%',
    });

    $('#categories-filter-apply-btn').on('click', function () {
        $('#filter-role').val($('#modal-role').val());
        $('#filter-content').val($('#modal-content').val());
        window.FilterShell.close('categories-filter-modal');
        $('#categories-filter-form').submit();
    });

    $('#categories-filter-clear-btn').on('click', function () {
        $('#modal-role, #modal-content').val(null).trigger('change');
    });

    // ── Acciones masivas ─────────────────────────────────────────────────
    if (document.querySelector('.bulk-checkbox')) {
        $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

        var bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

        $('#bulk-modal').on('hide.bs.modal', function () {
            $('#bulk-action-select').val('').trigger('change');
            $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            bulk.reset();
        });

        $('#bulk-apply-btn').on('click', function () {
            var action = $('#bulk-action-select').val();
            var ids = bulk.getIds();

            if (! action) { toastr.warning('Selecciona una accion.'); return; }
            if (! ids.length) { toastr.warning('Selecciona al menos una categoria.'); return; }

            $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

            $.ajax({
                url: '{{ route('manager.helpcenter.categories.bulk-action') }}',
                method: 'POST',
                data: JSON.stringify({ action: action, ids: ids }),
                contentType: 'application/json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    $('#bulk-modal').modal('hide');
                    toastr.success(res.message);
                    setTimeout(function () { location.reload(); }, 800);
                },
                error: function (xhr) {
                    toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error al procesar.');
                    $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
                },
            });
        });
    }

    $('.delete-btn').on('click', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
