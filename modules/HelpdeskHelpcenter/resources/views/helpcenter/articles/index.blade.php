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

            {{-- Filtros --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('manager.helpcenter.articles') }}">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control -0 ps-0"
                                       placeholder="Buscar por título..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="flex-shrink-0 helpcenter-filter-status">
                            <select name="draft" class="form-select h-100">
                                <option value="">Todos los estados</option>
                                <option value="0" {{ request('draft') === '0' ? 'selected' : '' }}>Publicados</option>
                                <option value="1" {{ request('draft') === '1' ? 'selected' : '' }}>Borradores</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>
                            </button>
                            @if(request('search') || request('draft') !== null)
                                <a href="{{ route('manager.helpcenter.articles') }}" class="btn btn-outline-secondary"
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

@endsection

@push('styles')
<style>
.helpcenter-filter-status { min-width: 180px; }
</style>
@endpush

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
