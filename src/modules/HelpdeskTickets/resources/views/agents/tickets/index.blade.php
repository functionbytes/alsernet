@extends('layouts.theme')

@section('title', 'Mis tickets')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0 fw-bold"><i class="fas fa-ticket-alt me-2 text-primary"></i>Mis tickets</h4>
        <a href="{{ route('agent.helpdesk.tickets.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i>Nuevo ticket
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" class="row g-2 mb-3">
        <div class="col-auto">
            <select name="status" class="form-select form-select-sm">
                <option value="">Todos los estados</option>
                @foreach($statuses as $status)
                    <option value="{{ $status->slug }}" @selected(request('status') === $status->slug)>
                        {{ $status->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar..." value="{{ request('search') }}">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Asunto</th>
                        <th>Estado</th>
                        <th>Categoría</th>
                        <th>Cliente</th>
                        <th>Creado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                    <tr>
                        <td><small class="text-muted">{{ $ticket->ticket_number }}</small></td>
                        <td>{{ $ticket->subject }}</td>
                        <td>
                            @if($ticket->status)
                                <span class="badge" style="background-color:{{ $ticket->status->color }}">
                                    {{ $ticket->status->name }}
                                </span>
                            @endif
                        </td>
                        <td>{{ $ticket->category?->name ?? '-' }}</td>
                        <td>{{ $ticket->customer?->name ?? '-' }}</td>
                        <td><small>{{ $ticket->created_at?->diffForHumans() }}</small></td>
                        <td>
                            <a href="{{ route('agent.helpdesk.tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No tienes tickets asignados.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $tickets->links() }}</div>
@endsection
