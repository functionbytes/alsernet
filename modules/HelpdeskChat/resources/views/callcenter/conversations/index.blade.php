@extends('layouts.helpdesk')

@section('title', 'Mis conversaciones')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">
                <i class="ti ti-message-circle me-2"></i>
                Mis conversaciones
            </h4>
        </div>
        <div class="card-body">
            {{-- Filters --}}
            <div class="row mb-4">
                <div class="col-md-4">
                    <form method="GET" action="{{ route('callcenter.helpdesk.conversation.index') }}">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Buscar por nombre o email..." value="{{ request('search') }}">
                            <button class="btn btn-primary" type="submit">
                                <i class="ti ti-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="col-md-3">
                    <form method="GET" action="{{ route('callcenter.helpdesk.conversation.index') }}" class="d-inline">
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
                                <th>Contacto</th>
                                <th>Inbox</th>
                                <th>Estado</th>
                                <th>Mensajes sin leer</th>
                                <th>Última actividad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($conversations as $conversation)
                                <tr>
                                    <td>
                                        <strong>{{ $conversation->contact->name }}</strong><br>
                                        <small class="text-muted">{{ $conversation->contact->email }}</small>
                                    </td>
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
                                        <small>{{ $conversation->last_activity_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('callcenter.helpdesk.conversation.show', $conversation->id) }}" class="btn btn-sm btn-primary">
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
                    <p class="text-muted mt-3">No tienes conversaciones asignadas</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
