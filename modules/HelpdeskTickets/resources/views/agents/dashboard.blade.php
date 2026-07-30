@extends('layouts.theme')

@section('title', 'Dashboard')

@section('page_header')
    @include('core::components.card', ['title' => 'Dashboard'])
@endsection

@section('content')

    {{-- Filters bar --}}
    <div class="card card-body mb-4 border-0 shadow-sm">
        <div class="row align-items-center g-3">
            <div class="col-md">
                <div>
                    <p class="fw-semibold mb-0">Bienvenido, {{ auth()->user()->name ?: auth()->user()->firstname }}</p>
                    <p class="text-muted mb-0 small">Resumen de tu actividad de hoy</p>
                </div>
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
                            <a class="dropdown-item" href="{{ route('agent.helpdesk.tickets.index') }}">Ver mis tickets</a>
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

    {{-- Stats cards 2x3 --}}
    <div class="row g-3 mb-4">

        <div class="col-md-6 col-xl-4">
            <div class="card w-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h5 class="card-title fw-semibold mb-3">Tickets abiertos</h5>
                            <h4 class="fw-semibold mb-2">{{ $stats['my_open'] }}</h4>
                            <p class="fs-3 mb-0 text-muted">Asignados a ti</p>
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
                            <h5 class="card-title fw-semibold mb-3">SLA incumplido</h5>
                            <h4 class="fw-semibold mb-2 {{ $stats['my_sla_breached'] > 0 ? 'text-danger' : '' }}">
                                {{ $stats['my_sla_breached'] }}
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
            <div class="card w-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h5 class="card-title fw-semibold mb-3">Vencen hoy</h5>
                            <h4 class="fw-semibold mb-2 {{ $stats['my_due_today'] > 0 ? 'text-warning' : '' }}">
                                {{ $stats['my_due_today'] }}
                            </h4>
                            <p class="fs-3 mb-0 text-muted">Con vencimiento hoy</p>
                        </div>
                        <div class="col-4 d-flex justify-content-end">
                            <span class="rounded-circle bg-warning-subtle d-flex align-items-center justify-content-center stat-icon">
                                <i class="fas fa-clock text-warning"></i>
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
                            <h5 class="card-title fw-semibold mb-3">Cerrados hoy</h5>
                            <h4 class="fw-semibold mb-2">{{ $stats['my_closed_today'] }}</h4>
                            <p class="fs-3 mb-0 text-muted">Resueltos en el día</p>
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
                            <h5 class="card-title fw-semibold mb-3">Conversaciones</h5>
                            <h4 class="fw-semibold mb-2">{{ $stats['open_conversations'] }}</h4>
                            <p class="fs-3 mb-0 text-muted">Abiertas asignadas</p>
                        </div>
                        <div class="col-4 d-flex justify-content-end">
                            <span class="rounded-circle bg-info-subtle d-flex align-items-center justify-content-center stat-icon">
                                <i class="fas fa-comments text-info"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Bottom row: urgent tickets (col-8) + recent tickets (col-4) breakdown --}}
    <div class="row g-3">

        <div class="col-lg-8">
            <div class="card w-100 h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title fw-semibold mb-0">Tickets recientes</h4>
                        <p class="card-subtitle mt-1">Últimas actualizaciones</p>
                    </div>
                    <a href="{{ route('agent.helpdesk.tickets.index') }}" class="btn btn-sm btn-outline-secondary">
                        Ver todos
                    </a>
                </div>
                <div class="card-body p-0">
                    @if($recentTickets->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                            <small>No tienes tickets asignados</small>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
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
                                            <td class="pe-3 text-muted small">
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

        <div class="col-lg-4">
            <div class="card w-100 h-100">
                <div class="card-header">
                    <h4 class="card-title fw-semibold mb-0">Tickets urgentes</h4>
                    <p class="card-subtitle mt-1">SLA incumplido</p>
                </div>
                <div class="card-body">
                    @if($urgentTickets->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-check-circle fa-2x mb-2 d-block text-success opacity-50"></i>
                            <small>Sin tickets con SLA incumplido</small>
                        </div>
                    @else
                        @foreach($urgentTickets as $ticket)
                            @php
                                $isPast = $ticket->sla_resolution_due_at && $ticket->sla_resolution_due_at->isPast();
                                $isLast = $loop->last;
                            @endphp
                            <div class="d-flex align-items-center justify-content-between {{ $isLast ? '' : 'mb-4' }}">
                                <div class="d-flex align-items-center">
                                    <div class="p-2 bg-danger-subtle rounded-2 d-flex align-items-center justify-content-center me-3"
                                         style="width:36px;height:36px;">
                                        <i class="fas fa-exclamation-circle text-danger"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-semibold text-truncate" style="max-width:140px;">
                                            {{ $ticket->subject }}
                                        </h6>
                                        <p class="fs-3 mb-0 text-muted">{{ $ticket->ticket_number }}</p>
                                    </div>
                                </div>
                                <span class="small {{ $isPast ? 'text-danger fw-semibold' : 'text-warning' }}">
                                    {{ $ticket->sla_resolution_due_at?->format('d/m H:i') ?? '—' }}
                                </span>
                            </div>
                        @endforeach
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
