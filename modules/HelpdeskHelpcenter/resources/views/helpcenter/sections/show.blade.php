@extends('layouts.theme')

@section('title', $section->name)

@section('page_header')
    @include('core::components.card', ['title' => 'Centro de ayuda — '.$section->name])
@endsection

@section('content')

    @include('core::components.alerts')

    {{-- Breadcrumb --}}
    <div class="card mb-3">
        <div class="card-body p-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('manager.helpcenter.categories') }}" class="text-decoration-none">
                            Centro de ayuda
                        </a>
                    </li>
                    @if($section->parent)
                        <li class="breadcrumb-item">
                            <a href="{{ route('manager.helpcenter.categories.show', $section->parent->id) }}"
                               class="text-decoration-none">
                                {{ $section->parent->name }}
                            </a>
                        </li>
                    @endif
                    <li class="breadcrumb-item active" aria-current="page">{{ $section->name }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">

        {{-- Header --}}
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">
                        <i class="fas fa-layer-group me-2 text-muted"></i>{{ $section->name }}
                    </h5>
                    @if($section->description)
                        <p class="small mb-0 text-muted">{{ $section->description }}</p>
                    @else
                        <p class="small mb-0 text-muted">Detalle de la sección y sus artículos</p>
                    @endif
                </div>
                <div class="ms-auto">
                    <div class="btn-group">
                        <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                data-bs-toggle="dropdown" aria-expanded="false">
                            Acciones
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="{{ route('manager.helpcenter.sections.edit', $section->id) }}">
                                Editar sección
                            </a>
                            <a class="dropdown-item" href="{{ route('manager.helpcenter.sections.articles.create', $section->id) }}">
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
                <div class="col-md-4">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Total artículos</h6>
                            <h4 class="mb-1 fw-bold">{{ number_format($section->articles_count) }}</h4>
                            <small class="text-muted">En esta sección</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Publicados</h6>
                            <h4 class="mb-1 fw-bold">{{ number_format($section->articles->where('draft', false)->count()) }}</h4>
                            <small class="text-muted">Visibles al público</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Borradores</h6>
                            <h4 class="mb-1 fw-bold">{{ number_format($section->articles->where('draft', true)->count()) }}</h4>
                            <small class="text-muted">Pendientes de publicar</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="card-body">
            @if($section->articles->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Título</th>
                                <th>Estado</th>
                                <th class="text-center">Vistas</th>
                                <th class="text-center">Útil</th>
                                <th>Autor</th>
                                <th>Creado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($section->articles as $article)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $article->title }}</div>
                                        @if($article->description)
                                            <small class="text-muted">{{ Str::limit($article->description, 60) }}</small>
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
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
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
            @else
                <div class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                        <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                            <i class="far fa-file-circle-question fs-7"></i>
                        </div>
                        <h6 class="mb-1">Aún no hay artículos</h6>
                        <p class="text-muted mb-3">Crea el primer artículo de esta sección</p>
                        <a href="{{ route('manager.helpcenter.sections.articles.create', $section->id) }}"
                           class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-1"></i> Nuevo artículo
                        </a>
                    </div>
                </div>
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
