@extends('layouts.theme')

@section('page_title', 'Páginas')

@section('content')

    @include('core::components.card', ['title' => 'Páginas'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Páginas del sitio</h5>
                        <p class="small mb-0 text-muted">Administra las páginas estáticas del sitio web</p>
                    </div>
                    <div class="d-flex gap-2">
                        @php
                            $hasActiveFilters = collect($filters ?? [])->filter(fn($v, $k) => !empty($v) && !in_array($k, ['sort_by', 'sort_order', 'per_page']))->isNotEmpty();
                        @endphp
                        @if($hasActiveFilters)
                            <a href="{{ route('pages.index') }}" class="btn btn-secondary">
                                Limpiar filtros
                            </a>
                        @endif
                        <a href="{{ route('pages.create') }}" class="btn btn-primary">
                            Nueva página
                        </a>
                    </div>
                </div>
            </div>

            {{-- Search & Filters --}}
            <div class="card-body border-bottom">
                <form action="{{ route('pages.index') }}" method="GET">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="search" name="search" class="form-control"
                                       placeholder="Buscar por título, contenido o slug..."
                                       value="{{ $filters['search'] ?? '' }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">Todos los estados</option>
                                @foreach(['draft' => 'Borrador', 'published' => 'Publicado', 'pending' => 'Pendiente'] as $key => $label)
                                    <option value="{{ $key }}" {{ ($filters['status'] ?? '') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="template" class="form-select">
                                <option value="">Todas las plantillas</option>
                                @foreach(config('page.templates', []) as $key => $label)
                                    <option value="{{ $key }}" {{ ($filters['template'] ?? '') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Buscar</button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($pages->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Título</th>
                                    <th>Slug</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Plantilla</th>
                                    <th>Autor</th>
                                    <th>Fecha</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pages as $page)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <a href="{{ route('pages.edit', $page->id) }}" class="text-decoration-none fw-semibold">
                                                        {{ $page->title }}
                                                    </a>
                                                    @if($page->featured_image)
                                                        <small class="d-block text-muted">
                                                            <i class="fas fa-image me-1"></i>Con imagen destacada
                                                        </small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted font-monospace">{{ $page->slug }}</small>
                                        </td>
                                        <td class="text-center">
                                            @if($page->status === 'published')
                                                <span class="badge bg-success-subtle text-success">Publicado</span>
                                                @if($page->willBeUnpublished())
                                                    <br><small class="text-muted"><i class="fas fa-clock"></i> {{ $page->unpublish_at->format('d/m/Y H:i') }}</small>
                                                @endif
                                            @elseif($page->status === 'draft')
                                                <span class="badge bg-secondary-subtle text-secondary">Borrador</span>
                                                @if($page->willBePublished())
                                                    <br><small class="text-muted"><i class="fas fa-clock"></i> {{ $page->publish_at->format('d/m/Y H:i') }}</small>
                                                @endif
                                            @else
                                                <span class="badge bg-warning-subtle text-warning">Pendiente</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light-subtle text-black">
                                                {{ config('page.templates.' . $page->template, $page->template) }}
                                            </span>
                                        </td>
                                        <td>{{ $page->user->full_name ?? 'N/A' }}</td>
                                        <td>{{ $page->created_at->format('d/m/Y') }}</td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
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
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button type="button" class="dropdown-item delete-btn"
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
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-file-alt fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay páginas para mostrar</h6>
                            <p class="text-muted mb-3">
                                @if($hasActiveFilters)
                                    No se encontraron resultados con los filtros aplicados
                                @else
                                    Crea tu primera página para el sitio web
                                @endif
                            </p>
                            @if(!$hasActiveFilters)
                                <a href="{{ route('pages.create') }}" class="btn btn-sm btn-primary">
                                    Crear primera página
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($pages->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando <strong>{{ $pages->firstItem() }}</strong> a <strong>{{ $pages->lastItem() }}</strong>
                            de <strong>{{ $pages->total() }}</strong> páginas
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $pages->withQueryString()->links() }}
                        </nav>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.delete-btn').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const deleteUrl = $(this).data('url');
        $('#delete-form').attr('action', deleteUrl);

        const deleteModal = new bootstrap.Modal(document.getElementById('delete-modal'));
        deleteModal.show();
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
