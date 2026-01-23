@extends('layouts.helpdesk')

@section('title', 'Mis conversaciones')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="ti ti-messages me-2"></i>
                Mis conversaciones
            </h4>
            <a href="{{ route('customer.helpdesk.conversation.create') }}" class="btn btn-light btn-sm">
                <i class="ti ti-plus"></i> Nueva conversación
            </a>
        </div>
        <div class="card-body">
            {{-- Filter --}}
            <div class="row mb-4">
                <div class="col-md-3">
                    <form method="GET" action="{{ route('customer.helpdesk.conversation.index') }}">
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">Todos los estados</option>
                            <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Abiertas</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendientes</option>
                            <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Cerradas</option>
                        </select>
                    </form>
                </div>
            </div>

            {{-- Conversations List --}}
            @if($conversations->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Inbox</th>
                                <th>Estado</th>
                                <th>Mensajes sin leer</th>
                                <th>Asignado a</th>
                                <th>Última actividad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($conversations as $conversation)
                                <tr>
                                    <td>{{ $conversation->inbox->name }}</td>
                                    <td>
                                        @if($conversation->status == 'open')
                                            <span class="badge bg-success">Abierta</span>
                                        @elseif($conversation->status == 'pending')
                                            <span class="badge bg-warning">Pendiente</span>
                                        @else
                                            <span class="badge bg-secondary">Cerrada</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(($conversation->unread_count ?? 0) > 0)
                                            <span class="badge bg-danger">{{ $conversation->unread_count }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($conversation->assignee)
                                            {{ $conversation->assignee->name }}
                                        @else
                                            <span class="text-muted">Sin asignar</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $conversation->last_activity_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('customer.helpdesk.conversation.show', $conversation->id) }}" class="btn btn-sm btn-primary">
                                            <i class="ti ti-eye"></i> Ver
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="mt-4">
                    {{ $conversations->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="ti ti-inbox fs-1 text-muted"></i>
                    <p class="text-muted mt-3">No tienes conversaciones aún</p>
                    <a href="{{ route('customer.helpdesk.conversation.create') }}" class="btn btn-primary mt-2">
                        <i class="ti ti-plus"></i> Iniciar conversación
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
