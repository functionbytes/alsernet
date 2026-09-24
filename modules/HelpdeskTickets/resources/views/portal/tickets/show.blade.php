@extends('helpdesktickets::portal.layout')

@section('content')
    <div class="mb-3">
        <a href="{{ route('portal.tickets') }}" class="text-muted">
            <i class="fas fa-arrow-left me-1"></i>Volver a mis tickets
        </a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="card-title mb-1">{{ $ticket->subject }}</h5>
                    <p class="text-muted mb-0">
                        Ticket <strong>{{ $ticket->ticket_number }}</strong>
                        &middot; Abierto el {{ $ticket->created_at->format('d M Y H:i') }}
                    </p>
                </div>
                <div>
                    @if ($ticket->status)
                        <span
                            class="badge fs-6 hdt-dyn-bg"
                            style="--hdt-color: {{ $ticket->status->color ?? '#6c757d' }}"
                        >
                            {{ $ticket->status->name }}
                        </span>
                    @endif
                </div>
            </div>
            @if ($ticket->category)
                <p class="text-muted mt-2 mb-0">
                    <i class="fas fa-tag me-1"></i>{{ $ticket->category->name }}
                </p>
            @endif
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-1"></i>{{ session('status') }}
        </div>
    @endif

    @if ($attachments->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-paperclip me-2"></i>Archivos adjuntos</h6></div>
            <ul class="list-group list-group-flush">
                @foreach ($attachments as $attachment)
                    {{-- Antes esto era texto plano: el cliente veía el nombre de
                         su fichero pero no podía volver a descargarlo, porque no
                         existía ninguna ruta que sirviera TicketAttachment. --}}
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="{{ route('portal.tickets.attachments.download', [$ticket->ticket_number, $attachment->id]) }}">
                            <i class="fas fa-file me-2"></i>{{ $attachment->original_filename ?? $attachment->filename }}
                        </a>
                        <small class="text-muted">{{ number_format($attachment->size / 1024, 1) }} KB</small>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Messages thread --}}
    <h6 class="text-muted mb-3">
        <i class="fas fa-comments me-1"></i>Conversación
    </h6>

    @forelse ($messages as $message)
        @php $isCustomer = $message->isFromCustomer(); @endphp
        <div class="ticket-message {{ $isCustomer ? 'from-customer' : 'from-agent' }} mb-3">
            <div class="d-flex justify-content-between mb-1">
                <strong class="small">
                    @if ($isCustomer)
                        <i class="fas fa-user me-1 text-success"></i>{{ $customer->name }} (tú)
                    @else
                        <i class="fas fa-headset me-1 text-primary"></i>{{ $message->user?->full_name ?: 'Agente de soporte' }}
                    @endif
                </strong>
                <span class="text-muted">{{ $message->created_at->format('d M Y H:i') }}</span>
            </div>
            {{-- Igual que managers/agents/tickets/show.blade.php: si hay html_body
                 (respuesta del agente con formato, o email del cliente con
                 negrita/enlaces) se usa purificado; si no, texto plano. Antes
                 siempre era texto plano aquí aunque el mensaje SÍ tuviera html_body. --}}
            <p class="mb-0 htk-pre-line">{!! $message->html_body ? $message->safeHtmlBody() : e($message->body) !!}</p>
            {{-- Adjuntos que envía el agente (TicketItem.attachment_urls): antes
                 el cliente los recibía por correo pero no podía verlos aquí. --}}
            @if (! $isCustomer && ! empty($message->attachment_urls))
                <ul class="list-unstyled small mb-0 mt-2">
                    @foreach ($message->attachment_urls as $index => $path)
                        <li>
                            <a href="{{ route('portal.tickets.item-attachments.download', [$ticket->ticket_number, $message->id, $index]) }}">{{ basename((string) $path) }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @empty
        <p class="text-muted">Todavía no hay mensajes.</p>
    @endforelse

    {{-- Reply form. closed_at y no isOpen(): "Resuelto" y "En Espera" tienen
         is_open=false y el cliente veía "ticket cerrado" sin poder responder,
         aunque responder reabre el ticket (TicketService::reopenIfCustomerCanReopen). --}}
    @if ($ticket->closed_at === null)
        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <h6 class="card-title">
                    <i class="fas fa-reply me-1"></i>Enviar una respuesta
                </h6>
                <form action="{{ route('portal.tickets.reply', $ticket->ticket_number) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <textarea
                            name="message"
                            class="form-control @error('message') is-invalid @enderror"
                            rows="5"
                            placeholder="Describe tu problema o añade más detalles..."
                            required
                            maxlength="5000"
                        >{{ old('message') }}</textarea>
                        @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    @if ($attachmentSettings['user_upload_enabled'] ?? true)
                        @php
                            $attachmentMaxMb = ($attachmentSettings['max_kilobytes'] ?? 25600) / 1024;
                            $attachmentAccept = collect($attachmentSettings['extensions'] ?? [])->map(fn ($extension) => '.'.$extension)->implode(',');
                        @endphp
                        <div class="mb-3">
                            <label class="form-label">Adjuntos <span class="text-muted">(opcional, máx. {{ rtrim(rtrim(number_format($attachmentMaxMb, 2, '.', ''), '0'), '.') }}MB cada uno)</span></label>
                            <input type="file" name="attachments[]" class="form-control @error('attachments.*') is-invalid @enderror" multiple accept="{{ $attachmentAccept }}">
                            <div class="form-text">Permitidos: {{ strtoupper(implode(', ', $attachmentSettings['extensions'] ?? [])) }}. Máx. {{ rtrim(rtrim(number_format($attachmentMaxMb, 2, '.', ''), '0'), '.') }}MB por archivo.</div>
                            @error('attachments.*')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif
                    <button type="submit" class="btn btn-primary">
                        Enviar respuesta
                    </button>
                </form>
            </div>
        </div>
        {{-- El cliente puede dar el caso por resuelto él mismo; si vuelve a
             escribir, el ticket se reabre solo. --}}
        @if (! $ticket->resolved_at)
            <form method="POST" action="{{ route('portal.tickets.resolve', $ticket->ticket_number) }}" class="mt-3 text-end">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm">Mi problema está resuelto</button>
            </form>
        @endif
    @else
        <div class="alert alert-secondary mt-4">
            <i class="fas fa-lock me-1"></i>Este ticket está cerrado. <a href="{{ route('portal.tickets.create') }}">Abre un ticket nuevo</a> si necesitas más ayuda.
        </div>
    @endif

    @if ($ticket->closed_at && !$ticket->rated_at)
        <div class="card mt-3">
            <div class="card-body">
                <h6>Valora esta experiencia de soporte</h6>
                <form method="POST" action="{{ route('portal.tickets.rate', $ticket->ticket_number) }}">
                    @csrf
                    <div class="mb-3">
                        @for ($i = 1; $i <= 5; $i++)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rating" value="{{ $i }}" id="star{{ $i }}">
                                <label class="form-check-label" for="star{{ $i }}">{{ $i }} &#9733;</label>
                            </div>
                        @endfor
                    </div>
                    <textarea name="rating_comment" class="form-control mb-2" rows="2" placeholder="Comentario opcional..." maxlength="500"></textarea>
                    <button type="submit" class="btn btn-sm btn-primary">Enviar valoración</button>
                </form>
            </div>
        </div>
    @elseif ($ticket->rated_at)
        <div class="alert alert-success mt-3">Valoraste este ticket con {{ $ticket->rating }}/5. ¡Gracias!</div>
    @endif
@endsection
