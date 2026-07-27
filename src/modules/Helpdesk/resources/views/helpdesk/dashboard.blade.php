@extends('layouts.theme')

@section('title', 'Dashboard Helpdesk')

@section('content')

    {{-- Filters bar --}}
    <div class="card card-body mb-4 border-0 shadow-sm">
        <div class="row align-items-center g-3">
            <div class="col-md">
                <p class="mb-0 fw-semibold text-muted small">Datos en tiempo real — actualizados automáticamente</p>
            </div>
            <div class="col-md-auto d-flex align-items-center gap-2">
                <div class="dropdown">
                    <a href="javascript:void(0)"
                       class="d-flex align-items-center justify-content-center rounded-circle text-muted btn-icon-sm"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-vertical"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="{{ safe_route('manager.helpdesk.tickets.index') }}">Ver todos los tickets</a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" onclick="window.print()">Imprimir</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @include('core::components.alerts')

    {{-- SLA breach alert --}}
    @if($ticketStats['sla_breached'] > 0)
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
            <i class="fas fa-exclamation-circle fs-5 flex-shrink-0"></i>
            <div>
                <strong>{{ $ticketStats['sla_breached'] }} ticket(s) con SLA incumplido</strong>
                — requieren atención inmediata.
                <a href="{{ safe_route('manager.helpdesk.tickets.index') }}" class="alert-link ms-1">Ver tickets</a>
            </div>
        </div>
    @endif

    {{-- Stats cards 2x3 --}}
    <div class="row g-3 mb-4">

        <div class="col-md-6 col-xl-4">
            <div class="card w-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h5 class="card-title fw-semibold mb-3">Tickets abiertos</h5>
                            <h4 class="fw-semibold mb-2">{{ number_format($ticketStats['open']) }}</h4>
                            <p class="fs-3 mb-0 text-muted">En curso actualmente</p>
                        </div>
                        <div class="col-4 d-flex justify-content-end">
                            <span class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center stat-icon">
                                <i class="fas fa-ticket-alt text-primary"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="card w-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h5 class="card-title fw-semibold mb-3">Resueltos hoy</h5>
                            <h4 class="fw-semibold mb-2">{{ number_format($ticketStats['closed_today']) }}</h4>
                            <p class="fs-3 mb-0 text-muted">Cerrados en el día</p>
                        </div>
                        <div class="col-4 d-flex justify-content-end">
                            <span class="rounded-circle bg-success-subtle d-flex align-items-center justify-content-center stat-icon">
                                <i class="fas fa-check-circle text-success"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="card w-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h5 class="card-title fw-semibold mb-3">Creados hoy</h5>
                            <h4 class="fw-semibold mb-2">{{ number_format($ticketStats['created_today']) }}</h4>
                            <p class="fs-3 mb-0 text-muted">Nuevas solicitudes</p>
                        </div>
                        <div class="col-4 d-flex justify-content-end">
                            <span class="rounded-circle bg-info-subtle d-flex align-items-center justify-content-center stat-icon">
                                <i class="fas fa-plus-circle text-info"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="card w-100 {{ $ticketStats['sla_breached'] > 0 ? ' border-opacity-50' : '' }}">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h5 class="card-title fw-semibold mb-3">SLA incumplido</h5>
                            <h4 class="fw-semibold mb-2 {{ $ticketStats['sla_breached'] > 0 ? 'text-danger' : '' }}">
                                {{ number_format($ticketStats['sla_breached']) }}
                            </h4>
                            <p class="fs-3 mb-0 text-muted">Requieren atención</p>
                        </div>
                        <div class="col-4 d-flex justify-content-end">
                            <span class="rounded-circle bg-danger-subtle d-flex align-items-center justify-content-center stat-icon">
                                <i class="fas fa-exclamation-triangle text-danger"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="card w-100 {{ $ticketStats['unassigned'] > 0 ? 'border-warning border-opacity-50' : '' }}">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h5 class="card-title fw-semibold mb-3">Sin asignar</h5>
                            <h4 class="fw-semibold mb-2 {{ $ticketStats['unassigned'] > 0 ? 'text-warning' : '' }}">
                                {{ number_format($ticketStats['unassigned']) }}
                            </h4>
                            <p class="fs-3 mb-0 text-muted">Sin agente asignado</p>
                        </div>
                        <div class="col-4 d-flex justify-content-end">
                            <span class="rounded-circle bg-warning-subtle d-flex align-items-center justify-content-center stat-icon">
                                <i class="fas fa-user-slash text-warning"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="card w-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h5 class="card-title fw-semibold mb-3">Valoracion media</h5>
                            <h4 class="fw-semibold mb-2">{{ $avgRating > 0 ? $avgRating : '—' }}</h4>
                            <p class="fs-3 mb-0 text-muted">Satisfacción de clientes</p>
                        </div>
                        <div class="col-4 d-flex justify-content-end">
                            <span class="rounded-circle bg-warning-subtle d-flex align-items-center justify-content-center stat-icon">
                                <i class="fas fa-star text-warning"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Bottom row: SLA breaches (col-8) + Agents (col-4) --}}
    <div class="row g-3 mb-4">

        <div class="col-lg-8">
            <div class="card w-100 h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title fw-semibold mb-0">Incumplimientos SLA recientes</h4>
                        <p class="card-subtitle mt-1">Tickets con SLA vencido</p>
                    </div>
                    <a href="{{ safe_route('manager.helpdesk.tickets.index') }}" class="btn btn-sm btn-outline-secondary">
                        Ver todos
                    </a>
                </div>
                <div class="card-body p-0">
                    @if($recentBreaches->isEmpty())
                        <p class="text-muted text-center py-4 mb-0">Sin incumplimientos activos</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Ticket</th>
                                        <th scope="col">Asunto</th>
                                        <th scope="col">Cliente</th>
                                        <th scope="col">Asignado a</th>
                                        <th scope="col">Vencimiento SLA</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentBreaches as $ticket)
                                        <tr>
                                            <td>
                                                <a href="{{ safe_route('manager.helpdesk.tickets.show', $ticket) }}"
                                                   class="fw-semibold text-decoration-none">
                                                    {{ $ticket->ticket_number }}
                                                </a>
                                            </td>
                                            <td class="text-truncate" style="max-width:200px;">
                                                {{ $ticket->subject }}
                                            </td>
                                            <td>{{ $ticket->customer?->name ?? '—' }}</td>
                                            <td>
                                                @if($ticket->assignee)
                                                    {{ $ticket->assignee->full_name }}
                                                @else
                                                    <em class="text-muted">Sin asignar</em>
                                                @endif
                                            </td>
                                            <td>
                                                @if($ticket->sla_resolution_due_at)
                                                    <span class="text-danger fw-semibold">
                                                        <i class="fas fa-clock me-1"></i>
                                                        {{ $ticket->sla_resolution_due_at->format('d/m/Y H:i') }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
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

        <div class="col-lg-4">
            <div class="card w-100 h-100">
                <div class="card-header">
                    <h4 class="card-title fw-semibold mb-0">Agentes con mas tickets</h4>
                    <p class="card-subtitle mt-1">Tickets abiertos por agente</p>
                </div>
                <div class="card-body">
                    @if($agentStats->isEmpty())
                        <p class="text-muted text-center py-4 mb-0">Sin datos de agentes</p>
                    @else
                        @foreach($agentStats as $i => $agent)
                            @php $isLast = $loop->last; @endphp
                            <div class="d-flex align-items-center justify-content-between {{ $isLast ? '' : 'mb-4' }}">
                                <div class="d-flex align-items-center">
                                    <div class="p-2 bg-primary-subtle rounded-2 d-flex align-items-center justify-content-center me-3"
                                         style="width:36px;height:36px;">
                                        <i class="fas fa-user text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-semibold">{{ $agent->name }}</h6>
                                        <p class="fs-3 mb-0 text-muted">{{ $agent->closed_today }} cerrados hoy</p>
                                    </div>
                                </div>
                                <h6 class="mb-0 fw-semibold">
                                    {{ $agent->open_tickets }}
                                    <small class="text-muted fw-normal">abiertos</small>
                                </h6>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- Recent tickets --}}
    <div class="row g-3">
        <div class="col-12">
            <div class="card w-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title fw-semibold mb-0">Tickets recientes</h4>
                        <p class="card-subtitle mt-1">Últimas solicitudes recibidas</p>
                    </div>
                    <a href="{{ safe_route('manager.helpdesk.tickets.index') }}" class="btn btn-sm btn-outline-secondary">
                        Ver todos
                    </a>
                </div>
                <div class="card-body p-0">
                    @if($recentTickets->isEmpty())
                        <p class="text-muted text-center py-4 mb-0">No hay tickets aun</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Ticket</th>
                                        <th scope="col">Asunto</th>
                                        <th scope="col">Cliente</th>
                                        <th scope="col">Estado</th>
                                        <th scope="col">Creado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentTickets as $ticket)
                                        <tr>
                                            <td>
                                                <a href="{{ safe_route('manager.helpdesk.tickets.show', $ticket) }}"
                                                   class="fw-semibold text-decoration-none">
                                                    {{ $ticket->ticket_number }}
                                                </a>
                                            </td>
                                            <td class="text-truncate" style="max-width:200px;">
                                                {{ $ticket->subject }}
                                            </td>
                                            <td>{{ $ticket->customer?->name ?? '—' }}</td>
                                            <td>
                                                @if($ticket->status)
                                                    <span class="badge rounded-pill"
                                                          style="background-color: {{ $ticket->status->color ?? '#6c757d' }}">
                                                        {{ $ticket->status->name }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ $ticket->created_at->format('d/m/Y H:i') }}
                                                </small>
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

@endsection

@push('css')
<style>
    .btn-icon-sm { width: 30px; height: 30px; background: #f5f6f8; }
    .stat-icon   { width: 44px; height: 44px; }
</style>
@endpush
