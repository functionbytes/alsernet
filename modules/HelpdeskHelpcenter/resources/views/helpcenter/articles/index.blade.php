@extends('layouts.theme')

@section('title', 'Artículos del centro de ayuda')

@section('page_header')
    @include('core::components.card', ['title' => 'Centro de ayuda — Artículos'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Artículos</h5>
                        <p class="small mb-0 text-muted">Gestiona el contenido del centro de ayuda</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('manager.helpcenter.articles.create') }}">
                                    Nuevo artículo
                                </a>
                                <a class="dropdown-item" href="{{ route('manager.helpcenter.categories') }}">
                                    Ver categorías
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
                                <h6 class="card-title mb-2">Total artículos</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                                <small class="text-muted">Registrados en la base</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Publicados</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['published']) }}</h4>
                                <small class="text-muted">Visibles al público</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Borradores</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['drafts']) }}</h4>
                                <small class="text-muted">Pendientes de publicar</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Vistas totales</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total_views']) }}</h4>
                                <small class="text-muted">Lecturas acumuladas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Busqueda y filtros --}}
            <div class="card-body border-bottom">
                @php
                    $activeFilterCount = collect(['draft', 'category_id', 'author_id'])->filter(fn ($k) => request()->filled($k))->count();
                    $hasAnyFilter = $activeFilterCount > 0 || request()->filled('search');
                    $draftLabels = ['0' => 'Publicados', '1' => 'Borradores'];
                @endphp

                <form method="GET" action="{{ route('manager.helpcenter.articles') }}" id="articles-filter-form"
                      data-bulk-url="{{ route('manager.helpcenter.articles.bulk-action') }}">
                    <input type="hidden" name="draft" id="filter-draft" value="{{ request('draft') }}">
                    <input type="hidden" name="category_id" id="filter-category" value="{{ request('category_id') }}">
                    <input type="hidden" name="author_id" id="filter-author" value="{{ request('author_id') }}">

                    <div class="d-flex align-items-center gap-2">
                        <input type="search" name="search" class="form-control flex-grow-1"
                               placeholder="Buscar por título..."
                               value="{{ request('search') }}">

                        <x-filter-button target="articles-filter-modal" :count="$activeFilterCount" />

                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="submit" class="btn btn-primary" title="Buscar">
                                <i class="fas fa-magnifying-glass"></i>
                            </button>
                            @if($hasAnyFilter)
                                <a href="{{ route('manager.helpcenter.articles') }}" class="btn btn-secondary" title="Limpiar filtros">
                                    <i class="fas fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    @if($activeFilterCount > 0)
                        <div class="d-flex gap-2 flex-wrap mt-4 align-items-center">
                            <h6 class="mb-0">Filtrados:</h6>
                            @if(request()->filled('draft'))
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">Estado: {{ $draftLabels[request('draft')] ?? request('draft') }}</span>
                            @endif
                            @if(request('category_id'))
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">Categoria: {{ $categories->firstWhere('id', request('category_id'))->name ?? request('category_id') }}</span>
                            @endif
                            @if(request('author_id'))
                                @php $hcAuthor = $authors->firstWhere('id', request('author_id')); @endphp
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">Autor: {{ $hcAuthor ? trim($hcAuthor->firstname.' '.$hcAuthor->lastname) ?: $hcAuthor->email : request('author_id') }}</span>
                            @endif
                        </div>
                    @endif
                </form>
            </div>

            {{-- Tabla --}}
            <div class="card-body">
                @if($articles->isEmpty())
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="far fa-file-lines fs-7"></i>
                            </div>
                            <h6 class="mb-1">
                                @if(request('search') || request('draft') !== null)
                                    No se encontraron artículos
                                @else
                                    Aún no hay artículos
                                @endif
                            </h6>
                            <p class="text-muted mb-3">
                                @if(request('search') || request('draft') !== null)
                                    Ajusta los filtros para ver resultados
                                @else
                                    Crea el primer artículo del centro de ayuda
                                @endif
                            </p>
                            @if(! request('search') && request('draft') === null)
                                <a href="{{ route('manager.helpcenter.articles.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus me-1"></i> Nuevo artículo
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
                                    <th>Título</th>
                                    <th>Secciones</th>
                                    <th>Estado</th>
                                    <th class="text-center">Vistas</th>
                                    <th class="text-center">Útil</th>
                                    <th>Autor</th>
                                    <th>Creado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($articles as $article)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $article->id }}"></td>
                                        <td>
                                            <div class="fw-semibold">{{ $article->title }}</div>
                                            @if($article->description)
                                                <small class="text-muted">{{ Str::limit($article->description, 60) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($article->categories->count() > 0)
                                                @foreach($article->categories as $section)
                                                    <span class="badge bg-light text-dark">{{ $section->name }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($article->draft)
                                                <span class="badge bg-warning-subtle text-warning">Borrador</span>
                                            @else
                                                <span class="badge bg-success-subtle text-success">Publicado</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light-secondary text-secondary">
                                                {{ number_format($article->views_count ?? 0) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light-primary text-primary">
                                                {{ number_format($article->helpful_count ?? 0) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($article->author)
                                                <span class="text-muted">{{ $article->author->name }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $article->created_at->format('d/m/Y') }}</span>
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
                                                           href="{{ route('manager.helpcenter.articles.edit', $article->id) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="{{ route('manager.helpcenter.articles.translations.index', $article) }}">
                                                            Traducciones
                                                        </a>
                                                    </li>
                                                    @if(! $article->draft)
                                                        <li>
                                                            <a class="dropdown-item"
                                                               href="{{ route('public.helpcenter.show', $article->slug) }}"
                                                               target="_blank">
                                                                Ver en público
                                                            </a>
                                                        </li>
                                                    @endif
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn"
                                                           data-bs-toggle="modal" data-bs-target="#delete-modal"
                                                           data-url="{{ route('manager.helpcenter.articles.destroy', $article->id) }}"
                                                           data-title="Eliminar: {{ $article->title }}">
                                                            Eliminar
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
                @endif
            </div>

            @if($articles->hasPages())
                <div class="card-footer">{{ $articles->withQueryString()->links() }}</div>
            @endif

        </div>
    </div>

    @include('core::components.delete')

    {{-- Filtros avanzados --}}
    <x-filter-shell id="articles-filter-modal"
                    :count="$activeFilterCount"
                    apply-id="articles-filter-apply-btn"
                    clear-id="articles-filter-clear-btn">
        <div class="fs-field">
            <label class="form-label fw-semibold">Estado</label>
            <select id="modal-draft" class="form-control select2-filter-modal">
                <option value="">Todos los estados</option>
                <option value="0" @selected(request('draft') === '0')>Publicados</option>
                <option value="1" @selected(request('draft') === '1')>Borradores</option>
            </select>
        </div>
        <div class="fs-field">
            <label class="form-label fw-semibold">Categoria</label>
            <select id="modal-category" class="form-control select2-filter-modal">
                <option value="">Todas las categorias</option>
                @foreach($categories as $hcCategory)
                    <option value="{{ $hcCategory->id }}" @selected(request('category_id') == $hcCategory->id)>{{ $hcCategory->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="fs-field">
            <label class="form-label fw-semibold">Autor</label>
            <select id="modal-author" class="form-control select2-filter-modal">
                <option value="">Cualquier autor</option>
                @foreach($authors as $hcAuthor)
                    <option value="{{ $hcAuthor->id }}" @selected(request('author_id') == $hcAuthor->id)>
                        {{ trim($hcAuthor->firstname.' '.$hcAuthor->lastname) ?: $hcAuthor->email }}
                    </option>
                @endforeach
            </select>
        </div>
    </x-filter-shell>

    {{-- Barra flotante de seleccion --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none hc-bulk-toolbar">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar accion
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
                    <p class="text-muted mb-3">Se aplicara sobre <strong><span data-bulk-count>0</span> articulo(s)</strong>.</p>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Accion</label>
                        <select id="bulk-action-select" class="form-select select2-bulk">
                            <option value="">Seleccionar accion...</option>
                            <option value="publish">Publicar</option>
                            <option value="draft">Pasar a borrador</option>
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

@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('modules/helpdeskhelpcenter/css/helpcenter-manager.css') }}?v={{ filemtime(public_path('modules/helpdeskhelpcenter/css/helpcenter-manager.css')) }}">
@endpush

@include('helpdeskhelpcenter::partials.common-scripts')

@push('scripts')
<script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
<script src="{{ asset('modules/helpdeskhelpcenter/js/articles-index.js') }}?v={{ filemtime(public_path('modules/helpdeskhelpcenter/js/articles-index.js')) }}"></script>
@endpush
