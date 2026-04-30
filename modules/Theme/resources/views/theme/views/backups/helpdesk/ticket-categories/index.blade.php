@extends('layouts.theme')

@section('title', 'Categorias de tickets')

@section('page_header')
    @include('core::components.card', ['title' => 'Categorias de tickets'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Categorias de tickets</h5>
                        <p class="small mb-0 text-muted">Organiza los tickets por categoria para un mejor seguimiento</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('manager.helpdesk.settings.ticket-categories.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva categoria
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                                <small class="text-muted">Categorias registradas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['active']) }}</h4>
                                <small class="text-muted">Habilitadas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Inactivas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['inactive']) }}</h4>
                                <small class="text-muted">Deshabilitadas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Con SLA</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['with_sla']) }}</h4>
                                <small class="text-muted">Con politica SLA</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('manager.helpdesk.settings.ticket-categories.index') }}" id="filterForm">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="flex-fill">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por nombre, slug o descripcion..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary flex-shrink-0">
                            <i class="fas fa-search"></i>
                        </button>
                        @if(request('search'))
                            <a href="{{ route('manager.helpdesk.settings.ticket-categories.index') }}"
                               class="btn btn-outline-secondary flex-shrink-0" title="Limpiar">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($categories->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap" id="categories-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Slug</th>
                                    <th>Descripcion</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="categories-sortable">
                                @foreach($categories as $category)
                                    <tr data-id="{{ $category->id }}">
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @php
                                                    $icon = $category->icon;
                                                    if ($icon && str_starts_with($icon, 'ti ')) {
                                                        $icon = 'fas fa-folder';
                                                    }
                                                    $icon = $icon ?: 'fas fa-folder';
                                                @endphp
                                                <span class="rounded-circle d-inline-block flex-shrink-0"
                                                      style="width:12px;height:12px;background-color:{{ $category->color ?? '#b10100' }};"></span>
                                                <i class="{{ $icon }}" style="color:{{ $category->color ?? '#b10100' }};"></i>
                                                <span class="fw-semibold">{{ $category->name }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <code class="bg-light px-2 py-1 rounded small">{{ $category->slug }}</code>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $category->description ? Str::limit($category->description, 60) : '—' }}
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            @if($category->active)
                                                <span class="badge bg-success-subtle text-success">Activa</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Inactiva</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('manager.helpdesk.settings.ticket-categories.edit', $category->id) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <form method="POST" action="{{ route('manager.helpdesk.settings.ticket-categories.toggle', $category->id) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="dropdown-item">
                                                                {{ $category->active ? 'Desactivar' : 'Activar' }}
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="#"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('manager.helpdesk.settings.ticket-categories.destroy', $category->id) }}"
                                                           data-title="Eliminar categoria: {{ $category->name }}">
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
                        <i class="fas fa-tags fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">
                            @if(request('search'))
                                No se encontraron resultados
                            @else
                                No hay categorias configuradas
                            @endif
                        </h5>
                        <p class="text-muted mb-4">
                            @if(request('search'))
                                No hay resultados para "{{ request('search') }}"
                            @else
                                Aun no hay categorias creadas
                            @endif
                        </p>
                        @if(request('search'))
                            <a href="{{ route('manager.helpdesk.settings.ticket-categories.index') }}" class="btn btn-secondary">Limpiar filtros</a>
                        @else
                            <a href="{{ route('manager.helpdesk.settings.ticket-categories.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Nueva categoria
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($categories->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $categories->firstItem() }} - {{ $categories->lastItem() }} de {{ $categories->total() }}
                        </div>
                        <div>
                            {{ $categories->appends(request()->input())->links() }}
                        </div>
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
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // Delete modal
    $(document).on('click', '.delete-btn', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    // Drag-drop reorder (SortableJS)
    if (typeof Sortable !== 'undefined' && document.getElementById('categories-sortable')) {
        Sortable.create(document.getElementById('categories-sortable'), {
            animation: 150,
            handle: 'tr',
            onEnd: function () {
                const ids = [];
                $('#categories-sortable tr').each(function () {
                    ids.push($(this).data('id'));
                });

                $.ajax({
                    url: '{{ route('manager.helpdesk.settings.ticket-categories.reorder') }}',
                    method: 'POST',
                    data: JSON.stringify({ ids }),
                    contentType: 'application/json',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (res) {
                        toastr.success(res.message || 'Orden actualizado.');
                    },
                    error: function () {
                        toastr.error('Error al actualizar el orden.');
                    },
                });
            },
        });
    }
});
</script>
@endpush
