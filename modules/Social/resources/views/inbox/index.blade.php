@extends('layouts.admin')

@section('title', 'Bandeja de Entrada Unificada')

@section('content')

    <div class="widget-content searchable-container list">

        <!-- Alerts -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Main Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">
                            <i class="fas fa-inbox me-2"></i>Bandeja de Entrada Unificada
                        </h5>
                        <p class="small mb-0 text-muted">Gestiona comentarios, menciones y mensajes desde todas tus redes sociales</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.social.inbox.archived') }}" class="btn btn-secondary">
                            <i class="fas fa-archive me-1"></i> Ver Archivados
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-danger stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-danger mb-2">No Leídas</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['unread'] }}</h4>
                                        <small class="text-muted">Requieren atención</small>
                                    </div>
                                    <i class="fas fa-exclamation-circle fs-1 text-danger opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-info stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-info mb-2">Total</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                        <small class="text-muted">Menciones totales</small>
                                    </div>
                                    <i class="fas fa-comments fs-1 text-info opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-success stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-success mb-2">Respondidas</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['replied'] }}</h4>
                                        <small class="text-muted">Con respuesta</small>
                                    </div>
                                    <i class="fas fa-comment-check fs-1 text-success opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-warning stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-warning mb-2">Sin Responder</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['not_replied'] }}</h4>
                                        <small class="text-muted">Pendientes</small>
                                    </div>
                                    <i class="fas fa-clock fs-1 text-warning opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card-body border-bottom">
                <form method="GET" class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Estado</label>
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="unread" {{ request('status', 'unread') === 'unread' ? 'selected' : '' }}>No Leídas</option>
                            <option value="read" {{ request('status') === 'read' ? 'selected' : '' }}>Leídas</option>
                            <option value="replied" {{ request('status') === 'replied' ? 'selected' : '' }}>Respondidas</option>
                            <option value="not_replied" {{ request('status') === 'not_replied' ? 'selected' : '' }}>Sin Responder</option>
                            <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>Todas</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Tipo</label>
                        <select name="type" class="form-select" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <option value="comment" {{ request('type') === 'comment' ? 'selected' : '' }}>Comentarios</option>
                            <option value="mention" {{ request('type') === 'mention' ? 'selected' : '' }}>Menciones</option>
                            <option value="message" {{ request('type') === 'message' ? 'selected' : '' }}>Mensajes</option>
                            <option value="reply" {{ request('type') === 'reply' ? 'selected' : '' }}>Respuestas</option>
                            <option value="share" {{ request('type') === 'share' ? 'selected' : '' }}>Compartidos</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Red Social</label>
                        <select name="network" class="form-select" onchange="this.form.submit()">
                            <option value="">Todas</option>
                            <option value="facebook" {{ request('network') === 'facebook' ? 'selected' : '' }}>Facebook</option>
                            <option value="instagram" {{ request('network') === 'instagram' ? 'selected' : '' }}>Instagram</option>
                            <option value="twitter" {{ request('network') === 'twitter' ? 'selected' : '' }}>Twitter</option>
                            <option value="linkedin" {{ request('network') === 'linkedin' ? 'selected' : '' }}>LinkedIn</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Sentimiento</label>
                        <select name="sentiment" class="form-select" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <option value="positive" {{ request('sentiment') === 'positive' ? 'selected' : '' }}>Positivo</option>
                            <option value="neutral" {{ request('sentiment') === 'neutral' ? 'selected' : '' }}>Neutral</option>
                            <option value="negative" {{ request('sentiment') === 'negative' ? 'selected' : '' }}>Negativo</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Buscar</label>
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Buscar en contenido, autor..." value="{{ request('search') }}">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            @if(request()->hasAny(['status', 'type', 'network', 'sentiment', 'search']))
                                <a href="{{ route('admin.social.inbox.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <!-- Mentions List -->
            <div class="card-body p-0">
                @if($mentions->isEmpty())
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fs-1 text-muted mb-3 d-block"></i>
                        <p class="text-muted">No hay menciones para mostrar</p>
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($mentions as $mention)
                            <div class="list-group-item {{ !$mention->is_read ? 'bg-light' : '' }}" id="mention-{{ $mention->id }}">
                                <div class="d-flex w-100">
                                    <!-- Checkbox -->
                                    <div class="me-3">
                                        <input type="checkbox" name="mention_ids[]" value="{{ $mention->id }}" class="form-check-input mention-checkbox">
                                    </div>

                                    <!-- Author Avatar -->
                                    <div class="me-3">
                                        @if($mention->author_avatar)
                                            <img src="{{ $mention->author_avatar }}" class="rounded-circle" width="50" height="50" alt="{{ $mention->author_name }}">
                                        @else
                                            <div class="avatar avatar-md rounded-circle bg-light-info text-info d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                {{ substr($mention->author_name ?? 'U', 0, 1) }}
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="mb-1">
                                                    {{ $mention->author_name }}
                                                    <small class="text-muted">@{{ $mention->author_username }}</small>
                                                </h6>
                                                <div class="d-flex gap-2 align-items-center">
                                                    <!-- Network Icon -->
                                                    <i class="ti {{ $mention->network_icon }} fs-5"></i>

                                                    <!-- Type Badge -->
                                                    <span class="badge bg-light-secondary text-secondary">
                                                        <i class="ti {{ $mention->type_icon }} me-1"></i>{{ ucfirst($mention->type) }}
                                                    </span>

                                                    <!-- Sentiment Badge -->
                                                    @if($mention->sentiment)
                                                        <span class="badge bg-light-{{ $mention->sentiment_color }} text-{{ $mention->sentiment_color }}">
                                                            {{ ucfirst($mention->sentiment) }}
                                                        </span>
                                                    @endif

                                                    <!-- Time -->
                                                    <small class="text-muted">{{ $mention->created_at->diffForHumans() }}</small>
                                                </div>
                                            </div>

                                            <!-- Quick Actions -->
                                            <div class="btn-group btn-group-sm">
                                                @if(!$mention->is_read)
                                                    <button type="button" class="btn btn-light" onclick="markAsRead({{ $mention->id }})" title="Marcar como leído">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-light" onclick="markAsUnread({{ $mention->id }})" title="Marcar como no leído">
                                                        <i class="fas fa-envelope"></i>
                                                    </button>
                                                @endif

                                                <button type="button" class="btn btn-light" onclick="archiveMention({{ $mention->id }})" title="Archivar">
                                                    <i class="fas fa-archive"></i>
                                                </button>

                                                @if($mention->external_url)
                                                    <a href="{{ $mention->external_url }}" target="_blank" class="btn btn-light" title="Ver en la red social">
                                                        <i class="fas fa-external-link-alt"></i>
                                                    </a>
                                                @endif

                                                @if(!$mention->is_replied)
                                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#replyModal{{ $mention->id }}" title="Responder">
                                                        <i class="fas fa-paper-plane"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Content Text -->
                                        <p class="mb-2">{{ $mention->content }}</p>

                                        <!-- Media -->
                                        @if($mention->media_url)
                                            <img src="{{ $mention->media_url }}" class="rounded mb-2" style="max-width: 300px;" alt="Media">
                                        @endif

                                        <!-- Post Reference -->
                                        @if($mention->post)
                                            <div class="alert alert-info py-2 px-3 mb-2 small">
                                                <i class="fas fa-link me-1"></i>
                                                En respuesta a: <strong>{{ Str::limit($mention->post->content, 50) }}</strong>
                                            </div>
                                        @endif

                                        <!-- Reply -->
                                        @if($mention->is_replied)
                                            <div class="card bg-light mt-2">
                                                <div class="card-body py-2">
                                                    <div class="d-flex align-items-start">
                                                        <i class="fas fa-reply text-success me-2 mt-1"></i>
                                                        <div>
                                                            <small class="text-muted d-block">
                                                                Respondido por {{ $mention->repliedBy->name }} el {{ $mention->replied_at->format('d/m/Y H:i') }}
                                                            </small>
                                                            <p class="mb-0 small">{{ $mention->reply_content }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Reply Modal -->
                            @if(!$mention->is_replied)
                                <div class="modal fade" id="replyModal{{ $mention->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('admin.social.inbox.reply', $mention) }}" method="POST">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Responder a {{ $mention->author_name }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <!-- Original Message -->
                                                    <div class="alert alert-light mb-3">
                                                        <small class="text-muted d-block mb-1">Mensaje original:</small>
                                                        <p class="mb-0">{{ $mention->content }}</p>
                                                    </div>

                                                    <!-- Reply Field -->
                                                    <div class="mb-3">
                                                        <label class="form-label">Tu Respuesta</label>
                                                        <textarea name="reply_content" class="form-control" rows="4" placeholder="Escribe tu respuesta..." required></textarea>
                                                        <small class="text-muted">Máximo 5000 caracteres</small>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-paper-plane me-1"></i>Enviar Respuesta
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <!-- Bulk Actions -->
                    <div class="card-footer border-top d-none" id="bulk-actions">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">
                                <span id="selected-count">0</span> menciones seleccionadas
                            </span>
                            <div class="btn-group">
                                <button type="button" class="btn btn-success btn-sm" onclick="bulkMarkAsRead()">
                                    <i class="fas fa-check me-1"></i>Marcar como Leídas
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="bulkArchive()">
                                    <i class="fas fa-archive me-1"></i>Archivar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="card-footer">
                        {{ $mentions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.mention-checkbox');
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');

    // Individual checkbox change
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkActions);
    });

    function updateBulkActions() {
        const selected = document.querySelectorAll('.mention-checkbox:checked');
        const count = selected.length;

        if (count > 0) {
            bulkActions.classList.remove('d-none');
            selectedCount.textContent = count;
        } else {
            bulkActions.classList.add('d-none');
        }
    }

    // Mark as read
    window.markAsRead = function(mentionId) {
        fetch('{{ route('admin.social.inbox.mark-as-read', ':id') }}'.replace(':id', mentionId), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById(`mention-${mentionId}`).classList.remove('bg-light');
                location.reload();
            }
        });
    };

    // Mark as unread
    window.markAsUnread = function(mentionId) {
        fetch('{{ route('admin.social.inbox.mark-as-unread', ':id') }}'.replace(':id', mentionId), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById(`mention-${mentionId}`).classList.add('bg-light');
                location.reload();
            }
        });
    };

    // Archive mention
    window.archiveMention = function(mentionId) {
        if (confirm('¿Archivar esta mención?')) {
            fetch('{{ route('admin.social.inbox.archive', ':id') }}'.replace(':id', mentionId), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById(`mention-${mentionId}`).remove();
                    location.reload();
                }
            });
        }
    };

    // Bulk mark as read
    window.bulkMarkAsRead = function() {
        const selected = Array.from(document.querySelectorAll('.mention-checkbox:checked')).map(cb => cb.value);

        if (selected.length === 0) {
            alert('Selecciona al menos una mención');
            return;
        }

        fetch('{{ route('admin.social.inbox.bulk-mark-as-read') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ mention_ids: selected })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    };

    // Bulk archive
    window.bulkArchive = function() {
        const selected = Array.from(document.querySelectorAll('.mention-checkbox:checked')).map(cb => cb.value);

        if (selected.length === 0) {
            alert('Selecciona al menos una mención');
            return;
        }

        if (confirm(`¿Archivar ${selected.length} menciones?`)) {
            fetch('{{ route('admin.social.inbox.bulk-archive') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ mention_ids: selected })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
    };
});
</script>
@endpush
