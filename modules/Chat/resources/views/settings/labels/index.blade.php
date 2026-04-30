@extends('layouts.theme')

@section('title', 'Etiquetas')

@section('content')

    @include('core::components.card', ['title' => 'Etiquetas'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Etiquetas</h5>
                        <p class="small mb-0 text-muted">Organiza conversaciones con etiquetas para clasificar y filtrar mejor</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request('search'))
                            <a href="{{ route('settings.chat.labels.index') }}" class="btn btn-secondary">
                                Limpiar búsqueda
                            </a>
                        @endif
                        <a href="{{ route('settings.chat.labels.create') }}" class="btn btn-primary">
                            Nueva etiqueta
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">Total</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                        <small class="text-muted">Etiquetas creadas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">En sidebar</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['on_sidebar'] }}</h4>
                                        <small class="text-muted">Visibles en barra lateral</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-warning mb-2">Ocultas</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['hidden'] }}</h4>
                                        <small class="text-muted">No visibles en sidebar</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-info mb-2">Uso total</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['total_usage'] }}</h4>
                                        <small class="text-muted">Conversaciones etiquetadas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.chat.labels.index') }}">
                    <div class="row align-items-center g-2">
                        <div class="col-md-9">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fa fa-magnifying-glass"></i>
                                </span>
                                <input type="search" name="search" class="form-control"
                                       placeholder="Buscar etiquetas..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                Buscar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Labels List -->
            <div class="card-body">
                @if($labels->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="25%">Nombre</th>
                                    <th width="35%">Descripción</th>
                                    <th width="15%" class="text-center">Mostrar en sidebar</th>
                                    <th width="15%" class="text-center">Fecha de creación</th>
                                    <th width="10%" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($labels as $label)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                {{ $label->name }}
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $label->description ? Str::limit($label->description, 50) : '-' }}</small>
                                        </td>
                                        <td class="text-center">
                                            @if($label->show_on_sidebar)
                                                <span class="badge bg-success-subtle text-white">Sí</span>
                                            @else
                                                <span class="badge bg-light-subtle text-black">No</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <small class="text-muted">{{ $label->created_at->format('d/m/Y') }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fa-duotone fa-solid fa-ellipsis"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.chat.labels.edit', $label) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button type="button"
                                                            class="dropdown-item delete-btn"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#delete-modal"
                                                            data-url="{{ route('settings.chat.labels.destroy', $label) }}"
                                                            data-title="Eliminar etiqueta: {{ $label->name }}">
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
                            <h6 class="mb-1">No hay etiquetas para mostrar</h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No se encontraron resultados para "{{ request('search') }}"
                                @else
                                    Crea tu primera etiqueta para comenzar
                                @endif
                            </p>
                            @if(!request('search'))
                                <a href="{{ route('settings.chat.labels.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-plus"></i> Crear primera etiqueta
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Pagination -->
            @if($labels->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando <strong>{{ $labels->firstItem() }}</strong> a <strong>{{ $labels->lastItem() }}</strong>
                            de <strong>{{ $labels->total() }}</strong> etiquetas
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $labels->links() }}
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
