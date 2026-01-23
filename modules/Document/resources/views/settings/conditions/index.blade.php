@extends('layouts.theme')

@section('title', 'Condiciones de validación')

@section('content')

    @include('core::components.card', ['title' => 'Condiciones de validación'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Validation Conditions Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Condiciones de validación</h5>
                        <p class="small mb-0 text-muted">Define condiciones reutilizables que mapean sale_types a validaciones específicas</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request('search') || request('status'))
                            <a href="{{ route('settings.documents.conditions.index') }}" class="btn btn-secondary">
                                Limpiar búsqueda
                            </a>
                        @endif
                        <a href="{{ route('settings.documents.conditions.create') }}" class="btn btn-primary">
                            Nueva condición
                        </a>
                    </div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.documents.conditions.index') }}">
                    <div class="row align-items-center g-2">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fa fa-magnifying-glass"></i>
                                </span>
                                <input type="search" name="search" class="form-control"
                                       placeholder="Buscar por clave o nombre..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select select2 select2" name="status" data-minimum-results-for-search="Infinity">
                                <option value="">Todos los estados</option>
                                <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                Buscar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Conditions List -->
            <div class="card-body">
                @if($conditions->count() > 0)
                    <div class="alert alert-info mb-3">
                        <i class="fa fa-circle-info me-2"></i>
                        Las condiciones de validación se usan en los <strong>Validation Stages</strong> para determinar qué etapas aplicar según el sale_type del pedido
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Clave</th>
                                    <th class="text-center">Etiquetas</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($conditions as $condition)
                                    <tr>

                                        <td>
                                            <div>
                                                <strong>{{ $condition->name }}</strong>
                                                @if($condition->description)
                                                    <br>
                                                    <small class="text-muted">{{ Str::limit($condition->description, 60) }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <code class="bg-light px-2 py-1 rounded">{{ $condition->key }}</code>
                                        </td>
                                        <td class="text-left">
                                            @if(!empty($condition->sale_types))
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($condition->sale_types as $saleType)
                                                        <span class="badge bg-info-subtle text-info">{{ $saleType }}</span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="badge bg-light text-muted">
                                                    <i class="fa fa-minus"></i>
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($condition->is_active)
                                                <span class="badge bg-success-subtle text-success">
                                                    Activo
                                                </span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">
                                                    Inactivo
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fa fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="{{ route('settings.documents.conditions.edit', $condition->uid) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <form action="{{ route('settings.documents.conditions.toggle-active', $condition->uid) }}"
                                                              method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item">
                                                                @if($condition->is_active)
                                                                    Desactivar
                                                                @else
                                                                    Activar
                                                                @endif
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button type="button"
                                                                class="dropdown-item text-success delete-btn"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#delete-modal"
                                                                data-url="{{ route('settings.documents.conditions.destroy', $condition->uid) }}"
                                                                data-title="Eliminar condición: {{ $condition->name }}">
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
                                <i class="fa fa-inbox fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay condiciones de validación para mostrar</h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No se encontraron resultados para "{{ request('search') }}"
                                @else
                                    Crea tu primera condición de validación para comenzar
                                @endif
                            </p>
                            @if(!request('search'))
                                <a href="{{ route('settings.documents.conditions.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-plus"></i> Crear Primera Condición
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Pagination -->
            @if($conditions->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando <strong>{{ $conditions->firstItem() }}</strong> a <strong>{{ $conditions->lastItem() }}</strong>
                            de <strong>{{ $conditions->total() }}</strong> condiciones
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $conditions->links() }}
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
    // Initialize Select2
    $('.select2').select2({
        allowClear: false,
        minimumResultsForSearch: Infinity
    });

    // Delete modal functionality
    $('.delete-btn').on('click', function() {
        const deleteUrl = $(this).data('url');
        const deleteTitle = $(this).data('title');

        $('#delete-modal .modal-title').text(deleteTitle);
        $('#delete-form').attr('action', deleteUrl);
    });

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
