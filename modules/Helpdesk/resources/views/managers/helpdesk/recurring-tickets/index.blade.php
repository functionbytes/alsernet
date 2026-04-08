@extends('layouts.helpdesk')

@section('title', 'Tickets recurrentes - Helpdesk')

@section('content')
    {{-- Header --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-semibold mb-3">Tickets recurrentes</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('manager.dashboard') }}">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item active">Tickets recurrentes</li>
                        </ol>
                    </nav>
                </div>
                <a href="{{ route('recurring-tickets.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Nuevo schedule
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Frecuencia</th>
                            <th>Proxima ejecucion</th>
                            <th>Ultima ejecucion</th>
                            <th>Tickets creados</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recurringTickets as $recurring)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $recurring->name }}</div>
                                    <small class="text-muted">{{ $recurring->subject }}</small>
                                </td>
                                <td>
                                    @php
                                        $frequencyLabels = [
                                            'daily'   => ['label' => 'Diario',   'class' => 'bg-primary'],
                                            'weekly'  => ['label' => 'Semanal',  'class' => 'bg-info'],
                                            'monthly' => ['label' => 'Mensual',  'class' => 'bg-warning text-dark'],
                                            'custom'  => ['label' => 'Personalizado', 'class' => 'bg-secondary'],
                                        ];
                                        $freq = $frequencyLabels[$recurring->frequency] ?? ['label' => $recurring->frequency, 'class' => 'bg-secondary'];
                                    @endphp
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
                                <td>
                                    @if($recurring->last_run_at)
                                        <span title="{{ $recurring->last_run_at->format('Y-m-d H:i') }}">
                                            {{ $recurring->last_run_at->diffForHumans() }}
                                        </span>
                                    @else
                                        <span class="text-muted">Nunca</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $recurring->tickets_created }}
                                    </span>
                                </td>
                                <td>
                                    @if($recurring->is_active)
                                        <span class="badge bg-success">Activo</span>
                                    @else
                                        <span class="badge bg-secondary">Inactivo</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    {{-- Toggle --}}
                                    <form action="{{ route('recurring-tickets.toggle', $recurring) }}"
                                          method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-sm {{ $recurring->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                title="{{ $recurring->is_active ? 'Desactivar' : 'Activar' }}">
                                            <i class="fas fa-{{ $recurring->is_active ? 'pause' : 'play' }}"></i>
                                        </button>
                                    </form>
                                    {{-- Edit --}}
                                    <a href="{{ route('recurring-tickets.edit', $recurring) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    {{-- Delete --}}
                                    <form action="{{ route('recurring-tickets.destroy', $recurring) }}"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('¿Eliminar este schedule?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-clock fa-2x mb-2 d-block"></i>
                                    No hay tickets recurrentes configurados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($recurringTickets->hasPages())
            <div class="card-footer">
                {{ $recurringTickets->links() }}
            </div>
        @endif
    </div>
@endsection
