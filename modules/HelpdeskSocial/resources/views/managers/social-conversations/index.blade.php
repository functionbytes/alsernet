@extends('theme::layouts.admin')

@section('title', 'Conversaciones sociales')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Conversaciones sociales</h1>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Participante</th>
                            <th>Plataforma</th>
                            <th>Cuenta</th>
                            <th>Estado</th>
                            <th>Mensajes</th>
                            <th>Último mensaje</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($conversations as $conversation)
                        <tr data-conversation-id="{{ $conversation->id }}">
                            <td>
                                <div class="fw-semibold">{{ $conversation->participant_name ?? 'Anónimo' }}</div>
                                <small class="text-muted">{{ $conversation->participant_external_id ?? '-' }}</small>
                            </td>
                            <td>
                                <span class="badge bg-{{ $conversation->platform === 'facebook' ? 'primary' : 'danger' }}">
                                    <i class="fab fa-{{ $conversation->platform }} me-1"></i>
                                    {{ ucfirst($conversation->platform) }}
                                </span>
                            </td>
                            <td>{{ $conversation->account?->name ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $conversation->status === 'open' ? 'success' : 'secondary' }}">
                                    {{ $conversation->status === 'open' ? 'Abierta' : 'Cerrada' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $conversation->message_count }}</span>
                            </td>
                            <td>
                                <small>{{ $conversation->last_message_at?->diffForHumans() ?? '-' }}</small>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('helpdesksocial.conversations.show', $conversation) }}" class="btn btn-sm btn-outline-primary" title="Ver conversación">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-comments fa-2x mb-2 d-block"></i>
                                No hay conversaciones registradas
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $conversations->links() }}
        </div>
    </div>
</div>
@endsection
