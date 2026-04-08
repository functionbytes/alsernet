@extends('layouts.theme')

@section('title', ($agent->firstname ?? '') . ' ' . ($agent->lastname ?? '') . ' - Agente Helpdesk')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-headset me-2 text-primary"></i>
                Perfil del agente
            </h4>
            <p class="text-muted mb-0">
                <a href="{{ route('manager.helpdesk.agents.index') }}" class="text-muted text-decoration-none">
                    Agentes
                </a>
                <i class="fas fa-chevron-right mx-1" style="font-size:.65rem;"></i>
                {{ $agent->firstname }} {{ $agent->lastname }}
            </p>
        </div>
        <a href="{{ route('manager.helpdesk.agents.edit', $agent) }}" class="btn btn-primary btn-sm">
            <i class="fas fa-edit me-1"></i> Editar configuración
        </a>
    </div>

    @include('core::components.alerts')

    <div class="row g-4">

        {{-- Agent Info --}}
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center pt-4">
                    <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center fw-bold mx-auto mb-3"
                         style="width:72px;height:72px;font-size:1.6rem;">
                        {{ strtoupper(substr($agent->firstname ?? '?', 0, 1)) }}{{ strtoupper(substr($agent->lastname ?? '', 0, 1)) }}
                    </div>
                    <h5 class="fw-bold mb-1">{{ $agent->firstname }} {{ $agent->lastname }}</h5>
                    <p class="text-muted mb-3">{{ $agent->email }}</p>

                    @php $availability = $agent->agentSettings->accepts_conversations ?? null; @endphp
                    @if($availability === 'yes')
                        <span class="badge bg-success-subtle text-success px-3 py-2">
                            <i class="fas fa-circle me-1" style="font-size:.6rem;"></i> Disponible
                        </span>
                    @elseif($availability === 'working_hours')
                        <span class="badge bg-warning-subtle text-warning px-3 py-2">
                            <i class="fas fa-clock me-1"></i> Horario laboral
                        </span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary px-3 py-2">
                            <i class="fas fa-circle me-1" style="font-size:.6rem;"></i> No disponible
                        </span>
                    @endif

                    @if($agent->agentSettings)
                        <div class="mt-3 text-muted">
                            Máx. conversaciones: <strong>{{ $agent->agentSettings->max_concurrent_conversations ?? '—' }}</strong>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="col-md-8">
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="card text-center">
                        <div class="card-body py-3">
                            <div class="fs-3 fw-bold text-primary">{{ $stats['open'] }}</div>
                            <div class="small text-muted">Tickets abiertos</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card text-center">
                        <div class="card-body py-3">
                            <div class="fs-3 fw-bold text-success">{{ $stats['closed_this_month'] }}</div>
                            <div class="small text-muted">Cerrados este mes</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card text-center">
                        <div class="card-body py-3">
                            <div class="fs-3 fw-bold text-danger">{{ $stats['sla_breached'] }}</div>
                            <div class="small text-muted">SLA incumplido</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card text-center">
                        <div class="card-body py-3">
                            <div class="fs-3 fw-bold text-warning">{{ $stats['avg_rating'] }}</div>
                            <div class="small text-muted">Valoración media</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recent Tickets --}}
            <div class="card">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">Tickets recientes</h6>
                </div>
                <div class="card-body p-0">
                    @if($recentTickets->isEmpty())
                        <div class="text-center py-4 text-muted">Sin tickets recientes</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Ticket</th>
                                        <th>Cliente</th>
                                        <th>Estado</th>
                                        <th>Actualizado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentTickets as $ticket)
                                        <tr>
                                            <td class="align-middle">
                                                <a href="{{ route('manager.helpdesk.tickets.show', $ticket) }}" class="text-decoration-none fw-semibold">
                                                    #{{ $ticket->ticket_number }}
                                                </a>
                                                <div class="text-muted text-truncate" style="max-width:200px;">{{ $ticket->subject }}</div>
                                            </td>
                                            <td class="align-middle">{{ $ticket->customer->name ?? '—' }}</td>
                                            <td class="align-middle">
                                                @if($ticket->status)
                                                    <span class="badge" style="background-color: {{ $ticket->status->color ?? '#6c757d' }}20; color: {{ $ticket->status->color ?? '#6c757d' }};">
                                                        {{ $ticket->status->name }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="align-middle text-muted">{{ $ticket->updated_at->diffForHumans() }}</td>
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
