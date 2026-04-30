{{-- Variables: $conversation --}}
<div class="p-9 border-bottom chat-meta-user d-flex align-items-center justify-content-between chat-active flex-shrink-0">
    <div class="hstack gap-3 current-chat-user-name">
        <div class="position-relative">
            @if($conversation->customer?->avatar_url)
                <img src="{{ $conversation->customer->avatar_url }}"
                     alt="{{ $conversation->customer->name }}"
                     width="48"
                     height="48"
                     class="rounded-circle">
            @else
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                     style="width: 48px; height: 48px; font-size: 20px;">
                    {{ strtoupper(substr($conversation->customer?->name ?? 'C', 0, 1)) }}
                </div>
            @endif
            @if($conversation->status?->slug === 'open')
                <span class="position-absolute bottom-0 end-0 p-1 badge rounded-pill bg-success">
                    <span class="visually-hidden">Active</span>
                </span>
            @endif
        </div>
        <div>
            <h6 class="mb-1 name fw-semibold">{{ $conversation->customer?->name ?? 'Cliente desconocido' }}</h6>
            <p class="mb-0">{{ $conversation->customer?->email ?? $conversation->customer?->phone_number ?? 'N/A' }}</p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <!-- Acciones rapidas -->
        @if($conversation->status?->slug !== 'resolved')
            <form action="{{ route('chat.conversations.updateStatus', $conversation->id) }}" method="POST" class="d-inline quick-action-form">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="resolved">
                <button type="submit" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Marcar como resuelta">
                    <i class="fas fa-check-circle"></i>
                </button>
            </form>
        @endif

        @if($conversation->status?->slug !== 'pending')
            <form action="{{ route('chat.conversations.updateStatus', $conversation->id) }}" method="POST" class="d-inline quick-action-form">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="pending">
                <button type="submit" class="btn btn-sm btn-outline-warning" data-bs-toggle="tooltip" title="Marcar como pendiente">
                    <i class="fas fa-hourglass-half"></i>
                </button>
            </form>
        @endif

        @if(!$conversation->assignee_id || $conversation->assignee_id !== auth()->id())
            <form action="{{ route('chat.conversations.assign', $conversation->id) }}" method="POST" class="d-inline quick-action-form">
                @csrf
                @method('PATCH')
                <input type="hidden" name="assignee_id" value="{{ auth()->id() }}">
                <button type="submit" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Asignarme">
                    <i class="fas fa-user-check"></i>
                </button>
            </form>
        @endif

        <!-- Exportar -->
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Exportar">
                <i class="fas fa-download"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="{{ route('chat.conversations.exportPdf', $conversation->id) }}" target="_blank">
                        <i class="fas fa-file-pdf text-danger"></i> Exportar PDF
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('chat.conversations.print', $conversation->id) }}" target="_blank">
                        <i class="fas fa-print text-secondary"></i> Vista de impresion
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#emailTranscriptModal">
                        <i class="fas fa-envelope text-primary"></i> Enviar por email
                    </a>
                </li>
            </ul>
        </div>

        <!-- Mas opciones -->
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-ellipsis-v"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><h6 class="dropdown-header">Mas opciones</h6></li>
                <li><a class="dropdown-item" href="javascript:void(0)"><i class="fas fa-phone"></i> Llamar</a></li>
                <li><a class="dropdown-item" href="javascript:void(0)"><i class="fas fa-video"></i> Video llamada</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="javascript:void(0)"><i class="fas fa-trash"></i> Eliminar</a></li>
            </ul>
        </div>
    </div>
</div>
