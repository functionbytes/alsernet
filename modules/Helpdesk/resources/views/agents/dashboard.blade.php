@extends('layouts.theme')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid py-4">

    <div class="mb-4">
        <h4 class="fw-bold mb-0">Bienvenido, {{ auth()->user()->name }}</h4>
        <p class="text-muted mb-0">Resumen de tu actividad de hoy</p>
    </div>

    {{-- Stats row --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 text-primary">
                        <i class="fas fa-ticket-alt fa-lg"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold text-primary">{{ $stats['my_open'] }}</div>
                        <div class="text-muted">Tickets abiertos</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3 text-danger">
                        <i class="fas fa-exclamation-triangle fa-lg"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold text-danger">{{ $stats['my_sla_breached'] }}</div>
                        <div class="text-muted">SLA incumplido</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 text-warning">
                        <i class="fas fa-clock fa-lg"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold text-warning">{{ $stats['my_due_today'] }}</div>
                        <div class="text-muted">Vencen hoy</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 text-success">
                        <i class="fas fa-check-circle fa-lg"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold text-success">{{ $stats['my_closed_today'] }}</div>
                        <div class="text-muted">Cerrados hoy</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-info bg-opacity-10 p-3 text-info">
                        <i class="fas fa-comments fa-lg"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold text-info">{{ $stats['open_conversations'] }}</div>
                        <div class="text-muted">Conversaciones</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- Urgent tickets --}}
        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom d-flex align-items-center gap-2 py-3">
                    <i class="fas fa-exclamation-circle text-danger"></i>
                    <h6 class="mb-0 fw-semibold">Tickets urgentes (SLA incumplido)</h6>
                </div>
                <div class="card-body p-0">
                    @if($urgentTickets->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-check-circle fa-2x mb-2 text-success"></i>
                            <p class="mb-0 small">Sin tickets con SLA incumplido</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">#</th>
                                        <th>Asunto</th>
                                        <th>Cliente</th>
                                        <th>Estado</th>
                                        <th class="pe-3">Vencimiento</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($urgentTickets as $ticket)
                                        @php
                                            $isPast = $ticket->sla_resolution_due_at && $ticket->sla_resolution_due_at->isPast();
                                        @endphp
                                        <tr>
                                            <td class="ps-3 text-muted">{{ $ticket->ticket_number }}</td>
                                            <td class="text-truncate" style="max-width:160px;">
                                                {{ $ticket->subject }}
                                            </td>
                                            <td class="text-muted">{{ $ticket->customer?->name ?? '—' }}</td>
                                            <td>
                                                @if($ticket->status)
                                                    <span class="badge rounded-pill"
                                                        style="background-color: {{ $ticket->status->color ?? '#6c757d' }}">
                                                        {{ $ticket->status->name }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="pe-3 {{ $isPast ? 'text-danger fw-semibold' : 'text-warning' }}">
                                                {{ $ticket->sla_resolution_due_at?->format('d/m H:i') ?? '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Recent tickets --}}
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-list text-primary"></i>
                        <h6 class="mb-0 fw-semibold">Tickets recientes</h6>
                    </div>
                    <a href="{{ route('agent.helpdesk.tickets.index') }}" class="btn btn-sm btn-outline-primary">
                        Ver todos
                    </a>
                </div>
                <div class="card-body p-0">
                    @if($recentTickets->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <p class="mb-0 small">No tienes tickets asignados</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">#</th>
                                        <th>Asunto</th>
                                        <th>Cliente</th>
                                        <th>Estado</th>
                                        <th>Categoría</th>
                                        <th class="pe-3">Actualizado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentTickets as $ticket)
                                        <tr>
                                            <td class="ps-3">
                                                <a href="{{ route('agent.helpdesk.tickets.show', $ticket) }}"
                                                    class="text-decoration-none text-muted">
                                                    {{ $ticket->ticket_number }}
                                                </a>
                                            </td>
                                            <td class="text-truncate" style="max-width:200px;">
                                                <a href="{{ route('agent.helpdesk.tickets.show', $ticket) }}"
                                                    class="text-decoration-none text-dark">
                                                    {{ $ticket->subject }}
                                                </a>
                                            </td>
                                            <td class="text-muted">{{ $ticket->customer?->name ?? '—' }}</td>
                                            <td>
                                                @if($ticket->status)
                                                    <span class="badge rounded-pill"
                                                        style="background-color: {{ $ticket->status->color ?? '#6c757d' }}">
                                                        {{ $ticket->status->name }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-muted">{{ $ticket->category?->name ?? '—' }}</td>
                                            <td class="pe-3 text-muted">
                                                {{ $ticket->updated_at?->diffForHumans() ?? '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
