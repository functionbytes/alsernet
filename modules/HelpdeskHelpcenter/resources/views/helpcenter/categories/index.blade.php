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

            {{-- Filtros --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('manager.helpcenter.categories') }}">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por nombre..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>
                            </button>
                            @if(request('search'))
                                <a href="{{ route('manager.helpcenter.categories') }}" class="btn btn-outline-secondary"
                                   title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
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
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @if($category->icon)
                                                    <i class="{{ $category->icon }} text-muted"></i>
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

@endsection

@push('scripts')
<script>
$(document).ready(function () {
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
