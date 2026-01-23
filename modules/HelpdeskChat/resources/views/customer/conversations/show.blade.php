@extends('layouts.helpdesk')

@section('title', 'Conversación')

@section('content')
<div class="container-fluid">
    <div class="row">
        {{-- Conversation Messages --}}
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="ti ti-message-circle me-2"></i>
                        {{ $conversation->subject ?? 'Conversación' }}
                    </h5>
                    <small>{{ $conversation->inbox->name }}</small>
                </div>
                <div class="card-body" style="height: 600px; overflow-y: auto;" id="messagesContainer">
                    @foreach($messages as $message)
                        <div class="message mb-3 {{ $message->message_type === 'incoming' ? 'text-start' : 'text-end' }}">
                            <div class="d-inline-block p-3 rounded {{ $message->message_type === 'incoming' ? 'bg-light' : 'bg-primary text-white' }}" style="max-width: 70%;">
                                <div class="message-content">
                                    {!! nl2br(e($message->content)) !!}
                                </div>
                                <div class="mt-2">
                                    <small class="{{ $message->message_type === 'incoming' ? 'text-muted' : 'text-white-50' }}">
                                        {{ $message->sender->name ?? 'Unknown' }} • {{ $message->created_at->format('d/m/Y H:i') }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($conversation->status !== 'closed')
                    <div class="card-footer">
                        <form method="POST" action="{{ route('customer.helpdesk.conversation.messages.store', $conversation->id) }}" id="messageForm">
                            @csrf
                            <div class="input-group">
                                <textarea class="form-control" name="content" rows="2" placeholder="Escribe tu mensaje..." required></textarea>
                                <button class="btn btn-primary" type="submit">
                                    <i class="ti ti-send"></i> Enviar
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="card-footer bg-light">
                        <p class="mb-0 text-muted text-center">
                            <i class="ti ti-lock"></i> Esta conversación está cerrada
                        </p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Conversation Info --}}
        <div class="col-md-3">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Información</h6>
                </div>
                <div class="card-body">
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
                    @if($conversation->assignee)
                        <div class="mb-3">
                            <label class="text-muted small">Asignado a</label>
                            <div>{{ $conversation->assignee->name }}</div>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="text-muted small">Creada</label>
                        <div>{{ $conversation->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Última actividad</label>
                        <div>{{ $conversation->last_activity_at->diffForHumans() }}</div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('customer.helpdesk.conversation.index') }}" class="btn btn-sm btn-outline-primary w-100">
                            <i class="ti ti-arrow-left"></i> Volver a mis conversaciones
                        </a>
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
