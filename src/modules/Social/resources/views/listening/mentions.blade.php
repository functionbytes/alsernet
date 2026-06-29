@extends('layouts.theme')

@section('title', 'Social Listening - Mentions')

@section('content')

    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item">
                                <a href="{{ route('admin.social.listening.index') }}">Social Listening</a>
                            </li>
                            <li class="breadcrumb-item active">{{ $keyword->formatted_name }}</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">
                        <i class="{{ $keyword->type_icon }} me-2"></i>{{ $keyword->formatted_name }}
                    </h2>
                    <p class="text-muted mb-0">
                        {{ $keyword->type_label }} ·
                        @foreach($keyword->networks as $network)
                            <span class="badge bg-light text-dark me-1">{{ ucfirst($network) }}</span>
                        @endforeach
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" id="scanNow">
                        <i class="fas fa-sync-alt"></i> Escanear ahora
                    </button>
                    <a href="{{ route('admin.social.listening.edit', $keyword) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-cog"></i> Configurar
                    </a>
                </div>
            </div>

            <!-- Sentiment Stats -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="text-muted mb-1">Total Mentions</p>
                                    <h3 class="mb-0">{{ number_format($mentions->total()) }}</h3>
                                </div>
                                <div class="bg-primary bg-opacity-10 rounded p-3">
                                    <i class="fas fa-comment fs-1 text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="text-muted mb-1">Positivo</p>
                                    <h3 class="mb-0 text-success">{{ number_format($sentimentStats['positive']) }}</h3>
                                    <small class="text-muted">
                                        {{ $mentions->total() > 0 ? round(($sentimentStats['positive'] / $mentions->total()) * 100) : 0 }}%
                                    </small>
                                </div>
                                <div class="bg-success bg-opacity-10 rounded p-3">
                                    <i class="fas fa-smile fs-1 text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="text-muted mb-1">Negativo</p>
                                    <h3 class="mb-0 text-danger">{{ number_format($sentimentStats['negative']) }}</h3>
                                    <small class="text-muted">
                                        {{ $mentions->total() > 0 ? round(($sentimentStats['negative'] / $mentions->total()) * 100) : 0 }}%
                                    </small>
                                </div>
                                <div class="bg-danger bg-opacity-10 rounded p-3">
                                    <i class="fas fa-frown fs-1 text-danger"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="text-muted mb-1">Neutral</p>
                                    <h3 class="mb-0 text-secondary">{{ number_format($sentimentStats['neutral']) }}</h3>
                                    <small class="text-muted">
                                        {{ $mentions->total() > 0 ? round(($sentimentStats['neutral'] / $mentions->total()) * 100) : 0 }}%
                                    </small>
                                </div>
                                <div class="bg-secondary bg-opacity-10 rounded p-3">
                                    <i class="fas fa-meh fs-1 text-secondary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters & Mentions -->
            <div class="row">
                <!-- Filters Sidebar -->
                <div class="col-lg-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-filter me-2"></i>Filtros
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="filterForm">
                                <!-- Sentiment Filter -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Sentimiento</label>
                                    <select class="form-select" name="sentiment" id="filterSentiment">
                                        <option value="">Todos</option>
                                        <option value="positive">Positivo</option>
                                        <option value="negative">Negativo</option>
                                        <option value="neutral">Neutral</option>
                                    </select>
                                </div>

                                <!-- Network Filter -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Red Social</label>
                                    <select class="form-select" name="network" id="filterNetwork">
                                        <option value="">Todas</option>
                                        @foreach($keyword->networks as $network)
                                            <option value="{{ $network }}">{{ ucfirst($network) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Status Filter -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Estado</label>
                                    <select class="form-select" name="status" id="filterStatus">
                                        <option value="">Todos</option>
                                        <option value="unread">No leídos</option>
                                        <option value="read">Leídos</option>
                                        <option value="replied">Respondidos</option>
                                        <option value="not_replied">Sin responder</option>
                                    </select>
                                </div>

                                <!-- Archived Filter -->
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="showArchived" name="archived">
                                        <label class="form-check-label" for="showArchived">
                                            Mostrar archivados
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter"></i> Aplicar Filtros
                                </button>
                            </form>

                            <hr class="my-3">

                            <!-- Bulk Actions -->
                            <div>
                                <label class="form-label fw-semibold">Acciones masivas</label>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-sm btn-outline-primary" id="markAllRead">
                                        <i class="fas fa-check-double"></i> Marcar todo como leído
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" id="archiveAll">
                                        <i class="fas fa-archive"></i> Archivar todos
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mentions List -->
                <div class="col-lg-9">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Menciones</h5>
                                <span class="text-muted">{{ $mentions->total() }} resultados</span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            @forelse($mentions as $mention)
                                <div class="mention-item border-bottom p-3 @if(!$mention->is_read) bg-light @endif @if($mention->is_archived) opacity-50 @endif"
                                     data-mention-id="{{ $mention->id }}">
                                    <div class="d-flex">
                                        <!-- Author Avatar -->
                                        <div class="flex-shrink-0 me-3">
                                            @if($mention->author_avatar)
                                                <img src="{{ $mention->author_avatar }}"
                                                     alt="{{ $mention->author_name }}"
                                                     class="rounded-circle"
                                                     width="48"
                                                     height="48">
                                            @else
                                                <div class="rounded-circle bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center"
                                                     style="width: 48px; height: 48px;">
                                                    <i class="fas fa-user text-secondary fs-4"></i>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Mention Content -->
                                        <div class="flex-grow-1">
                                            <!-- Header -->
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <strong>{{ $mention->author_name }}</strong>
                                                    <span class="text-muted">@{{ $mention->author_username }}</span>
                                                    <span class="mx-2">·</span>
                                                    <small class="text-muted">{{ $mention->created_at->diffForHumans() }}</small>
                                                </div>
                                                <div class="d-flex gap-2 align-items-center">
                                                    <i class="{{ $mention->network_icon }} fs-5"></i>
                                                    <span class="badge bg-{{ $mention->sentiment_color }}">
                                                        {{ ucfirst($mention->sentiment) }}
                                                    </span>
                                                    @if($mention->is_replied)
                                                        <span class="badge bg-info">
                                                            <i class="fas fa-check"></i> Respondido
                                                        </span>
                                                    @endif
                                                    @if($mention->is_archived)
                                                        <span class="badge bg-secondary">Archivado</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Content -->
                                            <p class="mb-2">{{ $mention->content }}</p>

                                            <!-- Media -->
                                            @if($mention->media_url)
                                                <div class="mb-2">
                                                    <img src="{{ $mention->media_url }}"
                                                         alt="Media"
                                                         class="img-fluid rounded"
                                                         style="max-height: 300px;">
                                                </div>
                                            @endif

                                            <!-- Reply Content (if replied) -->
                                            @if($mention->is_replied)
                                                <div class="bg-light rounded p-2 mb-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-reply me-1"></i>
                                                        Tu respuesta: {{ $mention->reply_content }}
                                                    </small>
                                                </div>
                                            @endif

                                            <!-- Actions -->
                                            <div class="d-flex gap-2">
                                                @if($mention->external_url)
                                                    <a href="{{ $mention->external_url }}"
                                                       target="_blank"
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-external-link-alt"></i> Ver original
                                                    </a>
                                                @endif

                                                @if(!$mention->is_replied)
                                                    <button class="btn btn-sm btn-primary btn-reply"
                                                            data-mention-id="{{ $mention->id }}">
                                                        <i class="fas fa-comment"></i> Responder
                                                    </button>
                                                @endif

                                                @if(!$mention->is_read)
                                                    <button class="btn btn-sm btn-outline-success btn-mark-read"
                                                            data-mention-id="{{ $mention->id }}">
                                                        <i class="fas fa-check"></i> Marcar leído
                                                    </button>
                                                @else
                                                    <button class="btn btn-sm btn-outline-secondary btn-mark-unread"
                                                            data-mention-id="{{ $mention->id }}">
                                                        <i class="fas fa-circle"></i> Marcar no leído
                                                    </button>
                                                @endif

                                                @if(!$mention->is_archived)
                                                    <button class="btn btn-sm btn-outline-warning btn-archive"
                                                            data-mention-id="{{ $mention->id }}">
                                                        <i class="fas fa-archive"></i> Archivar
                                                    </button>
                                                @else
                                                    <button class="btn btn-sm btn-outline-info btn-unarchive"
                                                            data-mention-id="{{ $mention->id }}">
                                                        <i class="fas fa-archive"></i> Desarchivar
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-5">
                                    <i class="fas fa-comment-slash fs-1 text-muted mb-3"></i>
                                    <p class="text-muted">No se encontraron menciones</p>
                                </div>
                            @endforelse
                        </div>

                        @if($mentions->hasPages())
                            <div class="card-footer bg-white">
                                {{ $mentions->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Reply Modal -->
<div class="modal fade" id="replyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-comment me-2"></i>Responder mención
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="replyForm">
                    <input type="hidden" id="replyMentionId">
                    <div class="mb-3">
                        <label class="form-label">Tu respuesta</label>
                        <textarea class="form-control"
                                  id="replyContent"
                                  rows="4"
                                  placeholder="Escribe tu respuesta..."
                                  required></textarea>
                        <div class="form-text">Esta respuesta se publicará en la red social correspondiente</div>
                    </div>
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Enviar respuesta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const keywordId = {{ $keyword->id }};
const replyModal = new bootstrap.Modal(document.getElementById('replyModal'));

// Scan Now
document.getElementById('scanNow')?.addEventListener('click', async function() {
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Escaneando...';

    try {
        const response = await fetch('{{ route('admin.social.listening.scan', ':id') }}'.replace(':id', keywordId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert(data.message || 'Error al escanear');
        }
    } catch (error) {
        alert('Error: ' + error.message);
    } finally {
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-sync-alt"></i> Escanear ahora';
    }
});

// Filter Form
document.getElementById('filterForm')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const params = new URLSearchParams();

    for (let [key, value] of formData.entries()) {
        if (value) params.append(key, value);
    }

    window.location.href = window.location.pathname + '?' + params.toString();
});

// Reply Button
document.querySelectorAll('.btn-reply').forEach(button => {
    button.addEventListener('click', function() {
        const mentionId = this.dataset.mentionId;
        document.getElementById('replyMentionId').value = mentionId;
        document.getElementById('replyContent').value = '';
        replyModal.show();
    });
});

// Reply Form Submit
document.getElementById('replyForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();

    const mentionId = document.getElementById('replyMentionId').value;
    const replyContent = document.getElementById('replyContent').value;

    if (!replyContent.trim()) {
        alert('Por favor escribe una respuesta');
        return;
    }

    try {
        const response = await fetch('{{ route('admin.social.inbox.reply', ':id') }}'.replace(':id', mentionId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ reply: replyContent })
        });

        const data = await response.json();

        if (data.success) {
            alert('Respuesta enviada exitosamente');
            replyModal.hide();
            window.location.reload();
        } else {
            alert(data.message || 'Error al enviar respuesta');
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
});

// Mark as Read
document.querySelectorAll('.btn-mark-read').forEach(button => {
    button.addEventListener('click', async function() {
        const mentionId = this.dataset.mentionId;
        await updateMentionStatus(mentionId, 'mark-as-read');
    });
});

// Mark as Unread
document.querySelectorAll('.btn-mark-unread').forEach(button => {
    button.addEventListener('click', async function() {
        const mentionId = this.dataset.mentionId;
        await updateMentionStatus(mentionId, 'mark-as-unread');
    });
});

// Archive
document.querySelectorAll('.btn-archive').forEach(button => {
    button.addEventListener('click', async function() {
        const mentionId = this.dataset.mentionId;
        await updateMentionStatus(mentionId, 'archive');
    });
});

// Unarchive
document.querySelectorAll('.btn-unarchive').forEach(button => {
    button.addEventListener('click', async function() {
        const mentionId = this.dataset.mentionId;
        await updateMentionStatus(mentionId, 'unarchive');
    });
});

// Update Mention Status
async function updateMentionStatus(mentionId, action) {
    const routeMap = {
        'mark-as-read': '{{ route('admin.social.inbox.mark-as-read', ':id') }}',
        'mark-as-unread': '{{ route('admin.social.inbox.mark-as-unread', ':id') }}',
        'archive': '{{ route('admin.social.inbox.archive', ':id') }}',
        'unarchive': '{{ route('admin.social.inbox.unarchive', ':id') }}',
    };
    const url = (routeMap[action] || '').replace(':id', mentionId);

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();

        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Error al actualizar');
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

// Bulk Actions
document.getElementById('markAllRead')?.addEventListener('click', async function() {
    if (!confirm('¿Marcar todas las menciones como leídas?')) return;

    try {
        const response = await fetch('{{ route('admin.social.inbox.bulk-mark-as-read') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                mention_ids: [...document.querySelectorAll('.mention-item')].map(el => el.dataset.mentionId)
            })
        });

        const data = await response.json();

        if (data.success) {
            alert('Todas las menciones marcadas como leídas');
            window.location.reload();
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
});

document.getElementById('archiveAll')?.addEventListener('click', async function() {
    if (!confirm('¿Archivar todas las menciones?')) return;

    try {
        const response = await fetch('{{ route('admin.social.inbox.bulk-archive') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                mention_ids: [...document.querySelectorAll('.mention-item')].map(el => el.dataset.mentionId)
            })
        });

        const data = await response.json();

        if (data.success) {
            alert('Todas las menciones archivadas');
            window.location.reload();
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
});
</script>
@endpush

@push('styles')
<style>
.mention-item {
    transition: all 0.2s;
}

.mention-item:hover {
    background-color: #f8f9fa !important;
}

.mention-item.bg-light {
    border-left: 4px solid #0d6efd;
}
</style>
@endpush
