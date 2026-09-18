@extends('layouts.theme')

@section('title', 'Ticket ' . $ticket->ticket_number)

@section('page_header')
    @include('core::components.card', ['title' => 'Ticket ' . $ticket->ticket_number])
@endsection

@section('content')
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('agent.helpdesk.tickets.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h5 class="mb-0 fw-bold">{{ $ticket->ticket_number }} — {{ $ticket->subject }}</h5>
        @if($ticket->status)
            <span class="badge" style="background-color:{{ $ticket->status->color }}">{{ $ticket->status->name }}</span>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3">
        {{-- Conversation thread --}}
        <div class="col-lg-8">
            <div id="messages-container">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <p class="text-muted mb-1">Descripción original</p>
                    <div>{!! nl2br(e($ticket->description)) !!}</div>
                </div>
            </div>

            @foreach($ticket->items->where('type', 'message') as $item)
            <div class="card shadow-sm mb-2 {{ $item->is_internal ? 'border-warning' : '' }}">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <strong class="small">{{ $item->sender_name }}</strong>
                        @if($item->is_internal)
                            <span class="badge bg-warning text-dark">Nota interna</span>
                        @endif
                        <small class="text-muted ms-auto">{{ $item->created_at?->diffForHumans() }}</small>
                    </div>
                    <div>{!! $item->html_body ? $item->safeHtmlBody() : nl2br(e($item->body)) !!}</div>
                </div>
            </div>
            @endforeach

            </div>{{-- #messages-container --}}

            {{-- Reply form --}}
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Responder</div>
                <div class="card-body">
                    <form action="{{ route('agent.helpdesk.messages.store', $ticket) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <textarea name="body" rows="4" class="form-control" required
                                placeholder="Escribe tu respuesta..."></textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_internal" id="is_internal" value="1">
                            <label class="form-check-label" for="is_internal">Nota interna (solo agentes)</label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-paper-plane me-1"></i>Enviar
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header fw-semibold">Detalles</div>
                <div class="card-body small">
                    <dl class="row mb-0">
                        <dt class="col-5">Cliente</dt>
                        <dd class="col-7">{{ $ticket->customer?->name ?? '-' }}</dd>
                        <dt class="col-5">Categoría</dt>
                        <dd class="col-7">{{ $ticket->category?->name ?? '-' }}</dd>
                        <dt class="col-5">Prioridad</dt>
                        <dd class="col-7">{{ ucfirst($ticket->priority ?? 'normal') }}</dd>
                        <dt class="col-5">Creado</dt>
                        <dd class="col-7">{{ $ticket->created_at?->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Acciones</div>
                <div class="card-body d-grid gap-2">
                    @if($ticket->isOpen())
                        <form action="{{ route('agent.helpdesk.tickets.close', $ticket) }}" method="POST">
                            @csrf
                            <button class="btn btn-sm btn-outline-danger w-100">
                                <i class="fas fa-lock me-1"></i>Cerrar ticket
                            </button>
                        </form>
                    @else
                        <form action="{{ route('agent.helpdesk.tickets.reopen', $ticket) }}" method="POST">
                            @csrf
                            <button class="btn btn-sm btn-outline-success w-100">
                                <i class="fas fa-lock-open me-1"></i>Reabrir ticket
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
@if(isset($ticket))
<script>
if (typeof window.Echo !== 'undefined') {
    window.Echo.private('helpdesk.ticket.{{ $ticket->id }}')
        .listen('.ticket.message.new', function(data) {
            const time = new Date(data.message.created_at).toLocaleTimeString();

            const $author = $('<strong class="small">').text(data.message.author);
            const $time   = $('<small class="text-muted ms-auto">').text(time);
            const $header = $('<div class="d-flex align-items-center gap-2 mb-2">').append($author, $time);
            const $body   = $('<div>').text(data.message.content);
            const $card   = $('<div class="card shadow-sm mb-2">').append(
                $('<div class="card-body">').append($header, $body)
            );

            $('#messages-container').append($card);

            const container = document.getElementById('messages-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        });
}
</script>
@endif
@endpush
