@extends('layouts.theme')

@section('title', $category->name)

@section('page_header')
    @include('core::components.card', ['title' => 'Centro de ayuda — '.$category->name])
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
                    <li class="breadcrumb-item active" aria-current="page">{{ $category->name }}</li>
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
                        @if($category->icon)<i class="{{ $category->icon }} me-2 text-muted"></i>@endif
                        {{ $category->name }}
                    </h5>
                    @if($category->description)
                        <p class="small mb-0 text-muted">{{ $category->description }}</p>
                    @else
                        <p class="small mb-0 text-muted">Detalle de la categoría y sus secciones</p>
                    @endif
                </div>
                <div class="ms-auto">
                    <div class="btn-group">
                        <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                data-bs-toggle="dropdown" aria-expanded="false">
                            Acciones
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="{{ route('manager.helpcenter.categories.edit', $category->id) }}">
                                Editar categoría
                            </a>
                            <a class="dropdown-item" href="{{ route('manager.helpcenter.sections.create', ['parent_id' => $category->id]) }}">
                                Nueva sección
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Secciones</h6>
                            <h4 class="mb-1 fw-bold">{{ number_format($category->sections_count) }}</h4>
                            <small class="text-muted">Subcategorías de esta categoría</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Artículos</h6>
                            <h4 class="mb-1 fw-bold">{{ number_format($category->articles_count) }}</h4>
                            <small class="text-muted">Total dentro de esta categoría</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="card-body">
            @if($category->sections->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Sección</th>
                                <th class="text-center">Artículos</th>
                                <th class="text-center">Posición</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($category->sections as $section)
                                <tr>
                                    <td>
                                        <a href="{{ route('manager.helpcenter.sections.show', $section->id) }}"
                                           class="fw-semibold text-decoration-none">
                                            {{ $section->name }}
                                        </a>
                                        @if($section->description)
                                            <br>
                                            <small class="text-muted">{{ Str::limit($section->description, 80) }}</small>
                                        @endif
                                    </td>
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
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
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
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                        <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                            <i class="far fa-folder-open fs-7"></i>
                        </div>
                        <h6 class="mb-1">Aún no hay secciones</h6>
                        <p class="text-muted mb-3">Crea la primera sección para esta categoría</p>
                        <a href="{{ route('manager.helpcenter.sections.create', ['parent_id' => $category->id]) }}"
                           class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva sección
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
