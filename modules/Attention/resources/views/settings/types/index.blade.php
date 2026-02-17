@extends('layouts.theme')

@section('title', 'Tipos de PQRSF')

@section('content')

    @include('core::components.card', ['title' => 'Tipos de PQRSF'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- System Settings Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Tipos de PQRSF</h5>
                        <p class="small mb-0 text-muted">Gestiona los tipos de solicitudes disponibles (Petición, Queja, Reclamo, Sugerencia, Felicitación)</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request('search') || request('status'))
                            <a href="{{ route('settings.attention.types.index') }}" class="btn btn-secondary">
                                Limpiar búsqueda
                            </a>
                        @endif
                        <a href="{{ route('settings.attention.types.create') }}" class="btn btn-primary">
                            Nuevo tipo
                        </a>
                    </div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.attention.types.index') }}">
                    <div class="row align-items-center g-2">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fa fa-magnifying-glass"></i>
                                </span>
                                <input type="search" name="search" class="form-control"
                                       placeholder="Buscar por nombre o código..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select select2" name="status" data-minimum-results-for-search="Infinity">
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

            <!-- Types List -->
            <div class="card-body">
                @if($types->count() > 0)
                    <div class="alert alert-info mb-3">
                        <i class="fa fa-circle-info me-2"></i>
                        Cada tipo de PQRSF puede tener configuraciones específicas de SLA y plantillas de correo
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Código</th>
                                    <th class="text-center">Color</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Orden</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($types as $type)
                                    <tr>
                                        <td>
                                            <div>
                                                <strong>{{ $type->name }}</strong>
                                                @if($type->description)
                                                    <br>
                                                    <small class="text-muted">{{ Str::limit($type->description, 60) }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <code class="bg-light px-2 py-1 rounded">{{ $type->code }}</code>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex align-items-center justify-content-center gap-2">
                                                <span class="rounded-circle d-inline-block" style="width:20px;height:20px;background-color:{{ $type->color ?? '#ccc' }};border:1px solid #dee2e6;"></span>
                                                <code class="bg-light px-1 rounded small">{{ $type->color ?? '-' }}</code>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if($type->is_active)
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
                                            <span class="badge bg-light text-dark">{{ $type->sort_order }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fa fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="{{ route('settings.attention.types.edit', $type->id) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <form action="{{ route('settings.attention.types.toggle', $type->id) }}"
                                                              method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item">
                                                                @if($type->is_active)
                                                                    Desactivar
                                                                @else
                                                                    Activar
                                                                @endif
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a
                                                                class="dropdown-item text-danger delete-btn"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#delete-modal"
                                                                data-url="{{ route('settings.attention.types.destroy', $type->id) }}"
                                                                data-title="Eliminar tipo: {{ $type->name }}">
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
                                <i class="fa fa-inbox fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay tipos de PQRSF para mostrar</h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No se encontraron resultados para "{{ request('search') }}"
                                @else
                                    Crea tu primer tipo de PQRSF para comenzar
                                @endif
                            </p>
                            @if(!request('search'))
                                <a href="{{ route('settings.attention.types.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-plus"></i> Crear Primer Tipo
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Pagination -->
            @if($types->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando <strong>{{ $types->firstItem() }}</strong> a <strong>{{ $types->lastItem() }}</strong>
                            de <strong>{{ $types->total() }}</strong> tipos
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $types->links() }}
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
