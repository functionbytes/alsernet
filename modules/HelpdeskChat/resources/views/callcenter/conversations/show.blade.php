@extends('layouts.helpdesk')

@section('title', 'Conversación')

@section('content')
<div class="container-fluid">
    <div class="row">
        {{-- Conversation Details --}}
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="ti ti-message-circle me-2"></i>
                            {{ $conversation->contact->name }}
                        </h5>
                        <small>{{ $conversation->inbox->name }}</small>
                    </div>
                    <div>
                        @if($conversation->status !== 'closed')
                            <form method="POST" action="{{ route('callcenter.helpdesk.conversation.close', $conversation->id) }}" class="d-inline" id="closeForm">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-light" onclick="return confirm('¿Cerrar esta conversación?')">
                                    <i class="ti ti-check"></i> Cerrar conversación
                                </button>
                            </form>
                        @else
                            <span class="badge bg-secondary">Cerrada</span>
                        @endif
                    </div>
                </div>
                <div class="card-body" style="height: 600px; overflow-y: auto;" id="messagesContainer">
                    @foreach($messages as $message)
                        <div class="message mb-3 {{ $message->message_type === 'outgoing' ? 'text-end' : '' }}">
                            <div class="d-inline-block p-3 rounded {{ $message->message_type === 'outgoing' ? 'bg-primary text-white' : 'bg-light' }}" style="max-width: 70%;">
                                <div class="message-content">
                                    {!! nl2br(e($message->content)) !!}
                                </div>
                                <div class="mt-2">
                                    <small class="{{ $message->message_type === 'outgoing' ? 'text-white-50' : 'text-muted' }}">
                                        {{ $message->sender->name ?? 'Unknown' }} • {{ $message->created_at->format('d/m/Y H:i') }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($conversation->status !== 'closed')
                    <div class="card-footer">
                        <form method="POST" action="{{ route('callcenter.helpdesk.conversation.messages.store', $conversation->id) }}" id="messageForm">
                            @csrf
                            <div class="input-group">
                                <textarea class="form-control" name="content" rows="2" placeholder="Escribe tu mensaje..." required></textarea>
                                <button class="btn btn-primary" type="submit">
                                    <i class="ti ti-send"></i> Enviar
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        {{-- Contact Info Sidebar --}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Información del contacto</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Nombre</label>
                        <div>{{ $conversation->contact->name }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Email</label>
                        <div>{{ $conversation->contact->email }}</div>
                    </div>
                    @if($conversation->contact->phone_number)
                        <div class="mb-3">
                            <label class="text-muted small">Teléfono</label>
                            <div>{{ $conversation->contact->phone_number }}</div>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="text-muted small">Estado</label>
                        <div>
                            @if($conversation->status == 'open')
                                <span class="badge bg-success">Abierta</span>
                            @elseif($conversation->status == 'pending')
                                <span class="badge bg-warning">Pendiente</span>
                            @else
                                <span class="badge bg-secondary">Cerrada</span>
                            @endif
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Creada</label>
                        <div>{{ $conversation->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Última actividad</label>
                        <div>{{ $conversation->last_activity_at->diffForHumans() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Auto-scroll to bottom of messages
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('messagesContainer');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }

    // Auto-reload messages every 10 seconds
    setInterval(function() {
        if (document.querySelector('#messagesContainer')) {
            location.reload();
        }
    }, 10000);
});
</script>
@endpush
@endsection
