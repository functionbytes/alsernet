@extends('layouts.theme')

@section('title', 'Busqueda avanzada - Helpdesk')

@section('content')
    @include('core::components.card', ['title' => 'Busqueda avanzada'])

    <div class="row g-3">
        {{-- Filters sidebar --}}
        <div class="col-12 col-lg-3">
            <div class="card">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Filtros</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('manager.helpdesk.search') }}" id="search-form">

                        <div class="mb-3">
                            <label class="form-label">Texto libre</label>
                            <input type="text" name="q" class="form-control"
                                   placeholder="Titulo, asunto, descripcion, #numero..."
                                   value="{{ $filters['q'] ?? '' }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Estado</label>
                            <select name="status_id" class="form-select">
                                <option value="">Todos los estados</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status->id }}" {{ ($filters['status_id'] ?? '') == $status->id ? 'selected' : '' }}>
                                        {{ $status->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Categoria</label>
                            <select name="category_id" class="form-select">
                                <option value="">Todas las categorias</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ ($filters['category_id'] ?? '') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Prioridad</label>
                            <select name="priority" class="form-select">
                                <option value="">Todas</option>
                                <option value="urgent" {{ ($filters['priority'] ?? '') === 'urgent' ? 'selected' : '' }}>Urgente</option>
                                <option value="high" {{ ($filters['priority'] ?? '') === 'high' ? 'selected' : '' }}>Alta</option>
                                <option value="normal" {{ ($filters['priority'] ?? '') === 'normal' ? 'selected' : '' }}>Normal</option>
                                <option value="low" {{ ($filters['priority'] ?? '') === 'low' ? 'selected' : '' }}>Baja</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Agente asignado</label>
                            <select name="assignee_id" class="form-select">
                                <option value="">Cualquier agente</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}" {{ ($filters['assignee_id'] ?? '') == $agent->id ? 'selected' : '' }}>
                                        {{ trim($agent->firstname.' '.$agent->lastname) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Desde</label>
                            <input type="date" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Hasta</label>
                            <input type="date" name="to" class="form-control" value="{{ $filters['to'] ?? '' }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tag</label>
                            <input type="text" name="tag" class="form-control"
                                   placeholder="Nombre del tag..."
                                   value="{{ $filters['tag'] ?? '' }}">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-search me-1"></i> Buscar
                        </button>
                        <a href="{{ route('manager.helpdesk.search') }}" class="btn btn-light w-100">
                            Limpiar filtros
                        </a>
                    </form>
                </div>
            </div>
        </div>

        {{-- Results --}}
        <div class="col-12 col-lg-9">
            <div class="card">
                <div class="card-header border-bottom p-3 d-flex align-items-center justify-content-between">
                    <h5 class="mb-0 fw-bold">Resultados</h5>
                    <span class="badge bg-primary-subtle text-primary px-3 py-2">
                        {{ $results->total() }} {{ $results->total() == 1 ? 'ticket' : 'tickets' }}
                    </span>
                </div>

                @if($results->isEmpty())
                    <div class="card-body text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3 d-block"></i>
                        <p class="text-muted">
                            @if(array_filter($filters))
                                No se encontraron tickets con los filtros aplicados.
                            @else
                                Usa los filtros para realizar una busqueda avanzada.
                            @endif
                        </p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Titulo</th>
                                    <th>Estado</th>
                                    <th>Prioridad</th>
                                    <th>Cliente</th>
                                    <th>Agente</th>
                                    <th>Creado</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($results as $ticket)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold text-muted small">#{{ $ticket->ticket_number }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('manager.helpdesk.tickets.show', $ticket) }}" class="text-dark fw-semibold text-decoration-none">
                                                {{ \Str::limit($ticket->title, 50) }}
                                            </a>
                                            @if($ticket->category)
                                                <br><small class="text-muted">{{ $ticket->category->name }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($ticket->status)
                                                <span class="badge rounded-pill"
                                                      style="background-color: {{ $ticket->status->color ?? '#6c757d' }}; color: white;">
                                                    {{ $ticket->status->name }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $priorityClasses = [
                                                    'urgent' => 'bg-danger-subtle text-danger',
                                                    'high' => 'bg-warning-subtle text-warning',
                                                    'normal' => 'bg-info-subtle text-info',
                                                    'low' => 'bg-secondary-subtle text-secondary',
                                                ];
                                                $priorityLabels = [
                                                    'urgent' => 'Urgente',
                                                    'high' => 'Alta',
                                                    'normal' => 'Normal',
                                                    'low' => 'Baja',
                                                ];
                                            @endphp
                                            <span class="badge {{ $priorityClasses[$ticket->priority] ?? 'bg-secondary-subtle text-secondary' }}">
                                                {{ $priorityLabels[$ticket->priority] ?? $ticket->priority }}
                                            </span>
                                        </td>
                                        <td>{{ $ticket->customer?->name ?? '—' }}</td>
                                        <td>{{ $ticket->assignee ? trim($ticket->assignee->firstname.' '.$ticket->assignee->lastname) : '—' }}</td>
                                        <td>
                                            <span class="text-muted small">{{ $ticket->created_at?->format('d/m/Y H:i') }}</span>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('manager.helpdesk.tickets.show', $ticket) }}">
                                                            Ver ticket
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('manager.helpdesk.tickets.edit', $ticket) }}">
                                                            Editar
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

                    @if($results->hasPages())
                        <div class="card-footer bg-white border-top">
                            {{ $results->appends(request()->input())->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection
