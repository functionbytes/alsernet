@extends('layouts.theme')

@section('title', 'Responder comentario')

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Comentario de {{ $comment->author_name }}</span>
                    <div>
                        <span class="badge bg-{{ $comment->platform === 'facebook' ? 'primary' : 'danger' }}">
                            <i class="fab fa-{{ $comment->platform }}"></i> {{ ucfirst($comment->platform) }}
                        </span>
                        @if($comment->is_mention)
                        <span class="badge bg-info">Mención</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="p-3 bg-light rounded mb-4">
                        <p class="mb-1">{{ $comment->body }}</p>
                        <small class="text-muted">
                            {{ $comment->posted_at->format('d/m/Y H:i') }} ·
                            Post: {{ $comment->external_post_id }}
                        </small>
                    </div>

                    @if($comment->intent)
                    <div class="mb-3">
                        <span class="badge bg-secondary">Intención: {{ $comment->intent }}</span>
                        <span class="badge bg-secondary">Confianza: {{ $comment->intent_confidence }}</span>
                        <span class="badge bg-secondary">Urgencia: {{ $comment->urgency }}</span>
                    </div>
                    @endif

                    @if($comment->tags->count() > 0)
                    <div class="mb-3">
                        <label class="form-label small text-muted">Etiquetas</label>
                        <div id="tagsContainer" class="d-flex flex-wrap gap-1">
                            @foreach($comment->tags as $tag)
                            <span class="badge" style="background-color: {{ $tag->color }}; color: #fff;">
                                {{ $tag->name }}
                                <button type="button" class="btn btn-link btn-sm p-0 ms-1 text-white" onclick="removeTag({{ $comment->id }}, {{ $tag->id }})" title="Quitar">
                                    <i class="fas fa-times" style="font-size: 10px;"></i>
                                </button>
                            </span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label small text-muted">Agregar etiqueta</label>
                        <div class="input-group input-group-sm">
                            <select id="tagSelect" class="form-select">
                                <option value="">Seleccionar etiqueta...</option>
                            </select>
                            <button type="button" class="btn btn-outline-primary" onclick="addTag({{ $comment->id }})">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>

                    @if($comment->status === 'replied')
                    <div class="alert alert-success">
                        <strong><i class="fas fa-check me-2"></i>Respondido</strong>
                        <p class="mb-1 mt-2">{{ $comment->reply_body }}</p>
                        <small>{{ $comment->replied_at?->format('d/m/Y H:i') }}</small>
                    </div>
                    @else
                    <div class="mb-3">
                        <label class="form-label">Respuesta</label>
                        <div class="dropdown mb-2">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-reply me-1"></i>Respuestas guardadas
                            </button>
                            <ul class="dropdown-menu" id="savedRepliesDropdown">
                                <li><span class="dropdown-item-text text-muted">Cargando...</span></li>
                            </ul>
                        </div>
                        <div id="aiSuggestionBox" class="alert alert-light border mb-2 d-none">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-robot text-primary"></i>
                                <strong class="small">Sugerencia IA</strong>
                            </div>
                            <p class="mb-1 small mt-1" id="aiSuggestionText"></p>
                            <button type="button" class="btn btn-sm btn-link p-0" onclick="useAiSuggestion()">Usar sugerencia</button>
                        </div>
                        <form id="replyForm" action="{{ route('helpdesksocial.inbox.reply', $comment) }}" method="POST">
                            @csrf
                            <textarea name="body" id="replyBody" class="form-control" rows="4" maxlength="2000" required></textarea>
                            <div class="form-text">Máximo 2000 caracteres</div>
                            <div class="d-flex gap-2 mt-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Enviar respuesta
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="loadSavedReplies()">
                                    <i class="fas fa-sync me-2"></i>Recargar plantillas
                                </button>
                                <button type="button" class="btn btn-outline-info" onclick="requestApproval({{ $comment->id }})">
                                    <i class="fas fa-gavel me-2"></i>Solicitar aprobación
                                </button>
                            </div>
                        </form>
                    </div>
                    @endif

                    <div class="card bg-light border-0 mt-4">
                        <div class="card-header bg-light border-0">
                            <i class="fas fa-sticky-note me-2"></i>Notas internas
                        </div>
                        <div class="card-body">
                            <div id="notesList" class="mb-3">
                                @forelse($comment->internalNotes as $note)
                                <div class=" border-3 border-secondary ps-2 mb-2">
                                    <p class="mb-1 small">{{ $note->body }}</p>
                                    <small class="text-muted">{{ $note->user?->name ?? 'Sistema' }} · {{ $note->created_at->diffForHumans() }}</small>
                                </div>
                                @empty
                                <p class="text-muted small mb-0" id="noNotesText">No hay notas internas.</p>
                                @endforelse
                            </div>
                            <div class="input-group">
                                <input type="text" id="noteInput" class="form-control form-control-sm" placeholder="Escribir nota interna..." maxlength="500">
                                <button type="button" class="btn btn-sm btn-secondary" onclick="postNote({{ $comment->id }})">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">Información del autor</div>
                <div class="card-body">
                    <p><strong>Nombre:</strong> {{ $comment->author_name }}</p>
                    <p><strong>Usuario:</strong> {{ $comment->author_username ?? '-' }}</p>
                    <p><strong>ID externo:</strong> {{ $comment->external_user_id }}</p>
                </div>
            </div>

            @if($comment->slaPolicy || $comment->sla_response_breached)
            <div class="card mb-4 border-{{ $comment->sla_response_breached ? 'danger' : 'warning' }}">
                <div class="card-header bg-{{ $comment->sla_response_breached ? 'danger' : 'warning' }} text-{{ $comment->sla_response_breached ? 'white' : 'dark' }}">
                    <i class="fas fa-clock me-2"></i>SLA
                </div>
                <div class="card-body">
                    @if($comment->sla_response_breached)
                        <div class="alert alert-danger mb-2">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            <strong>Respuesta vencida</strong>
                        </div>
                    @endif
                    @if($comment->sla_response_deadline)
                        <p class="mb-1 small"><strong>Límite respuesta:</strong> {{ $comment->sla_response_deadline->format('d/m/Y H:i') }}</p>
                    @endif
                    @if($comment->sla_resolution_deadline)
                        <p class="mb-0 small"><strong>Límite resolución:</strong> {{ $comment->sla_resolution_deadline->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
            </div>
            @endif

            <div class="card mb-4">
                <div class="card-header">Acciones</div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if(!$comment->is_spam)
                        <form action="{{ route('helpdesksocial.inbox.spam', $comment) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-warning w-100">
                                <i class="fas fa-ban me-2"></i>Marcar como spam
                            </button>
                        </form>
                        @endif

                        @if($comment->status !== 'escalated')
                        <form action="{{ route('helpdesksocial.inbox.escalate', $comment) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-info w-100">
                                <i class="fas fa-arrow-up me-2"></i>Escalar a humano
                            </button>
                        </form>
                        @endif

                        @if($comment->conversation)
                        <a href="{{ route('helpdesk.conversations.show', $comment->conversation) }}" class="btn btn-outline-primary w-100">
                            <i class="fas fa-comments me-2"></i>Ver conversación
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('js/social-inbox-enhanced.js') }}"></script>
<script>
(function () {
    window.currentCommentId = {{ $comment->id }};
    window.currentPlatform = '{{ $comment->platform }}';

    $(document).ready(function () {
        loadSavedReplies();
        loadAvailableTags();
    });
})();
</script>
@endpush
