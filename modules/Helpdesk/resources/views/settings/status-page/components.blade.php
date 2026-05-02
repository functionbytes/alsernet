@extends('layouts.theme')

@section('title', 'Pagina de estado — Componentes')

@section('page_header')
    @include('core::components.card', ['title' => 'Pagina de estado'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        {{-- Navigation tabs --}}
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('settings.helpdesk.status.components') }}">
                    Componentes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('settings.helpdesk.status.incidents') }}">
                    Incidentes
                </a>
            </li>
        </ul>

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Componentes del sistema</h5>
                        <p class="small mb-0 text-muted">Servicios y sistemas monitoreados en la pagina de estado publica</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('settings.helpdesk.status.components.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nuevo componente
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
                                <small class="text-muted">Componentes registrados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Operacionales</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['operational']) }}</h4>
                                <small class="text-muted">Funcionando correctamente</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Con problemas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['issues']) }}</h4>
                                <small class="text-muted">Degradados o interrumpidos</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($components->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>Orden</th>
                                    <th>Componente</th>
                                    <th>Descripcion</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Visible</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($components as $component)
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary fw-bold">
                                                {{ $component->order }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ $component->name }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $component->description ? Str::limit($component->description, 60) : '—' }}
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            @php $statusColor = \Modules\Helpdesk\Models\StatusComponent::STATUS_COLORS[$component->status] ?? 'secondary'; @endphp
                                            <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">
                                                {{ \Modules\Helpdesk\Models\StatusComponent::STATUSES[$component->status] ?? $component->status }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($component->is_visible)
                                                <span class="badge bg-success-subtle text-success">Visible</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Oculto</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.status.components.edit', $component->id) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="#"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.helpdesk.status.components.destroy', $component->id) }}"
                                                           data-title="Eliminar componente: {{ $component->name }}">
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
                        <i class="fas fa-server fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">No hay componentes registrados</h5>
                        <p class="text-muted mb-4">Agrega los servicios que deseas monitorear en la pagina de estado</p>
                        <a href="{{ route('settings.helpdesk.status.components.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nuevo componente
                        </a>
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($components->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $components->firstItem() }} - {{ $components->lastItem() }} de {{ $components->total() }}
                        </div>
                        <div>
                            {{ $components->links() }}
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
