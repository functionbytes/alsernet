@extends('layouts.theme')

@section('title', 'Encuestas')

@section('page_header')
    @include('core::components.card', ['title' => 'Encuestas'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Encuestas de satisfaccion</h5>
                        <p class="small mb-0 text-muted">Recopila feedback de tus clientes tras las conversaciones</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('settings.helpdesk.surveys.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva encuesta
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                                <small class="text-muted">Encuestas configuradas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['active']) }}</h4>
                                <small class="text-muted">Habilitadas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Respuestas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['responses']) }}</h4>
                                <small class="text-muted">Respuestas completadas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.helpdesk.surveys.index') }}">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="flex-fill">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control -0 ps-0"
                                       placeholder="Buscar por nombre..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary flex-shrink-0" aria-label="Buscar">
                            <i class="fas fa-search"></i>
                        </button>
                        @if(request('search'))
                            <a href="{{ route('settings.helpdesk.surveys.index') }}"
                               class="btn btn-outline-secondary flex-shrink-0" title="Limpiar">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($surveys->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Nombre</th>
                                    <th scope="col">Disparo</th>
                                    <th scope="col" class="text-center">Preguntas</th>
                                    <th scope="col" class="text-center">Respuestas</th>
                                    <th scope="col" class="text-center">Estado</th>
                                    <th scope="col" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($surveys as $survey)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold">{{ $survey->name }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ \Modules\Helpdesk\Models\Survey::TRIGGER_TYPES[$survey->trigger_type] ?? $survey->trigger_type }}
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                {{ is_array($survey->questions) ? count($survey->questions) : 0 }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('settings.helpdesk.surveys.responses', $survey->id) }}"
                                               class="badge bg-primary-subtle text-primary text-decoration-none">
                                                {{ number_format($survey->responses_count) }}
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            @if($survey->is_active)
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
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.surveys.edit', $survey->id) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.surveys.responses', $survey->id) }}">
                                                            Ver respuestas
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="#"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.helpdesk.surveys.destroy', $survey->id) }}"
                                                           data-title="Eliminar encuesta: {{ $survey->name }}">
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
                        <i class="fas fa-poll fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">
                            @if(request('search'))
                                No se encontraron resultados
                            @else
                                No hay encuestas configuradas
                            @endif
                        </h5>
                        <p class="text-muted mb-4">
                            @if(request('search'))
                                No hay resultados para "{{ request('search') }}"
                            @else
                                Crea tu primera encuesta para recopilar feedback de tus clientes
                            @endif
                        </p>
                        @if(request('search'))
                            <a href="{{ route('settings.helpdesk.surveys.index') }}" class="btn btn-secondary">Limpiar filtros</a>
                        @else
                            <a href="{{ route('settings.helpdesk.surveys.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Nueva encuesta
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($surveys->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $surveys->firstItem() }} - {{ $surveys->lastItem() }} de {{ $surveys->total() }}
                        </div>
                        <div>
                            {{ $surveys->appends(request()->input())->links() }}
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

    $(document).on('click', '.delete-btn', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });
});
</script>
@endpush
