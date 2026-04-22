@extends('layouts.theme')

@section('title', 'Dashboard - Helpdesk')

@section('content')

    @include('core::components.card', ['title' => 'Dashboard Helpdesk'])

    <div class="widget-content">

        @include('core::components.alerts')

        {{-- SLA breach alert --}}
        @if($ticketStats['sla_breached'] > 0)
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
                <i class="fas fa-exclamation-circle fs-5 flex-shrink-0"></i>
                <div>
                    <strong>{{ $ticketStats['sla_breached'] }} ticket(s) con SLA incumplido</strong>
                    — requieren atención inmediata.
                    <a href="{{ route('manager.helpdesk.tickets.index') }}" class="alert-link ms-1">Ver tickets</a>
                </div>
            </div>
        @endif

        {{-- Stat Cards Row --}}
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-2">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                                <i class="fas fa-ticket-alt text-primary fs-5"></i>
                            </div>
                            <div>
                                <h4 class="mb-0 fw-bold">{{ number_format($ticketStats['open']) }}</h4>
                                <small class="text-muted">Tickets abiertos</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-2">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                <i class="fas fa-check-circle text-success fs-5"></i>
                            </div>
                            <div>
                                <h4 class="mb-0 fw-bold">{{ number_format($ticketStats['closed_today']) }}</h4>
                                <small class="text-muted">Resueltos hoy</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-2">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-info bg-opacity-10 p-3">
                                <i class="fas fa-plus-circle text-info fs-5"></i>
                            </div>
                            <div>
                                <h4 class="mb-0 fw-bold">{{ number_format($ticketStats['created_today']) }}</h4>
                                <small class="text-muted">Creados hoy</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-2">
                <div class="card h-100 {{ $ticketStats['sla_breached'] > 0 ? 'border-danger border-opacity-50' : '' }}">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                                <i class="fas fa-exclamation-triangle text-danger fs-5"></i>
                            </div>
                            <div>
                                <h4 class="mb-0 fw-bold {{ $ticketStats['sla_breached'] > 0 ? 'text-danger' : '' }}">
                                    {{ number_format($ticketStats['sla_breached']) }}
                                </h4>
                                <small class="text-muted">SLA incumplido</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-2">
                <div class="card h-100 {{ $ticketStats['unassigned'] > 0 ? 'border-warning border-opacity-50' : '' }}">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                                <i class="fas fa-user-slash text-warning fs-5"></i>
                            </div>
                            <div>
                                <h4 class="mb-0 fw-bold {{ $ticketStats['unassigned'] > 0 ? 'text-warning' : '' }}">
                                    {{ number_format($ticketStats['unassigned']) }}
                                </h4>
                                <small class="text-muted">Sin asignar</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-2">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                                <i class="fas fa-star text-warning fs-5"></i>
                            </div>
                            <div>
                                <h4 class="mb-0 fw-bold">{{ $avgRating > 0 ? $avgRating : '—' }}</h4>
                                <small class="text-muted">Valoracion media</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SLA Breaches --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold">
                    <i class="fas fa-exclamation-circle text-danger me-2"></i>
                    Incumplimientos SLA recientes
                </h5>
                <a href="{{ route('manager.helpdesk.tickets.index') }}" class="btn btn-sm btn-outline-secondary">
                    Ver todos
                </a>
            </div>
            <div class="card-body p-0">
                @if($recentBreaches->isEmpty())
                    <p class="text-muted text-center py-4 mb-0">Sin incumplimientos activos</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Ticket</th>
                                    <th>Asunto</th>
                                    <th>Cliente</th>
                                    <th>Asignado a</th>
                                    <th>Vencimiento SLA</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentBreaches as $ticket)
                                    <tr>
                                        <td>
                                            <a href="{{ route('manager.helpdesk.tickets.show', $ticket) }}"
                                               class="fw-semibold text-decoration-none">
                                                {{ $ticket->ticket_number }}
                                            </a>
                                        </td>
                                        <td class="text-truncate" style="max-width: 200px;">
                                            {{ $ticket->subject }}
                                        </td>
                                        <td>{{ $ticket->customer?->name ?? '—' }}</td>
                                        <td>{{ $ticket->assignee?->name ?? '<em class="text-muted">Sin asignar</em>' }}</td>
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

        {{-- Bottom Row: Agents + Recent Tickets --}}
        <div class="row g-3">
            {{-- Top Agents --}}
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-users me-2 text-primary"></i>
                            Agentes con mas tickets abiertos
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        @if($agentStats->isEmpty())
                            <p class="text-muted text-center py-4 mb-0">Sin datos de agentes</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Agente</th>
                                            <th class="text-center">Abiertos</th>
                                            <th class="text-center">Cerrados hoy</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($agentStats as $agent)
                                            <tr>
                                                <td>{{ $agent->name }}</td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary rounded-pill">
                                                        {{ $agent->open_tickets }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-success rounded-pill">
                                                        {{ $agent->closed_today }}
                                                    </span>
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

            {{-- Recent Tickets --}}
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-list me-2 text-secondary"></i>
                            Tickets recientes
                        </h5>
                        <a href="{{ route('manager.helpdesk.tickets.index') }}" class="btn btn-sm btn-outline-secondary">
                            Ver todos
                        </a>
                    </div>
                    <div class="card-body p-0">
                        @if($recentTickets->isEmpty())
                            <p class="text-muted text-center py-4 mb-0">No hay tickets aun</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Ticket</th>
                                            <th>Asunto</th>
                                            <th>Cliente</th>
                                            <th>Estado</th>
                                            <th>Creado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentTickets as $ticket)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('manager.helpdesk.tickets.show', $ticket) }}"
                                                       class="fw-semibold text-decoration-none">
                                                        {{ $ticket->ticket_number }}
                                                    </a>
                                                </td>
                                                <td class="text-truncate" style="max-width: 160px;">
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

    </div>

@endsection
