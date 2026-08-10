@extends('layouts.theme')

@section('title', 'Tickets recurrentes')

@section('page_header')
    @include('core::components.card', ['title' => 'Tickets recurrentes'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Tickets recurrentes</h5>
                        <p class="small mb-0 text-muted">Tickets generados automaticamente segun la programacion configurada</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('manager.helpdesk.recurring-tickets.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nuevo ticket recurrente
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
                                <small class="text-muted">Schedules registrados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activos</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['active']) }}</h4>
                                <small class="text-muted">En ejecucion</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Inactivos</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['inactive']) }}</h4>
                                <small class="text-muted">Pausados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Proximos</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['upcoming']) }}</h4>
                                <small class="text-muted">Con próxima ejecución futura</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($recurringTickets->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Asunto</th>
                                    <th>Frecuencia</th>
                                    <th>Próxima ejecución</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recurringTickets as $recurring)
                                    @php
                                        $frequencyMap = [
                                            'daily'   => ['label' => 'Diario',        'class' => 'bg-primary-subtle text-primary'],
                                            'weekly'  => ['label' => 'Semanal',        'class' => 'bg-info-subtle text-info'],
                                            'monthly' => ['label' => 'Mensual',        'class' => 'bg-warning-subtle text-warning'],
                                            'custom'  => ['label' => 'Personalizado',  'class' => 'bg-secondary-subtle text-secondary'],
                                        ];
                                        $freq = $frequencyMap[$recurring->frequency] ?? ['label' => $recurring->frequency, 'class' => 'bg-secondary-subtle text-secondary'];
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $recurring->name }}</div>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $recurring->subject }}</small>
                                        </td>
                                        <td>
                                            <span class="badge {{ $freq['class'] }}">{{ $freq['label'] }}</span>
                                            @if($recurring->frequency === 'custom' && $recurring->cron_expression)
                                                <br><small class="text-muted font-monospace">{{ $recurring->cron_expression }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($recurring->next_run_at)
                                                <span title="{{ $recurring->next_run_at->format('Y-m-d H:i') }}">
                                                    {{ $recurring->next_run_at->diffForHumans() }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($recurring->is_active)
                                                <span class="badge bg-success-subtle text-success">Activo</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('manager.helpdesk.recurring-tickets.edit', $recurring->id) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <form action="{{ route('manager.helpdesk.recurring-tickets.toggle', $recurring->id) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item">
                                                                {{ $recurring->is_active ? 'Desactivar' : 'Activar' }}
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="#"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('manager.helpdesk.recurring-tickets.destroy', $recurring->id) }}"
                                                           data-title="Eliminar ticket recurrente: {{ $recurring->name }}">
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
                        <i class="fas fa-clock fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">No hay tickets recurrentes configurados</h5>
                        <p class="text-muted mb-4">Crea tu primer schedule para generar tickets automaticamente</p>
                        <a href="{{ route('manager.helpdesk.recurring-tickets.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nuevo ticket recurrente
                        </a>
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($recurringTickets->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $recurringTickets->firstItem() }} - {{ $recurringTickets->lastItem() }} de {{ $recurringTickets->total() }}
                        </div>
                        <div>
                            {{ $recurringTickets->appends(request()->input())->links() }}
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
