<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Bandeja social</h5>
        <a href="{{ route('helpdesksocial.inbox.index') }}" class="btn btn-sm btn-primary">
            <i class="fas fa-inbox me-1"></i> Ver todos
        </a>
    </div>
    <div class="card-body p-0">
        <div class="row g-0 text-center border-bottom">
            <div class="col-3 py-3 border-end">
                <div class="h4 mb-0 text-warning">{{ $stats['total_pending'] }}</div>
                <small class="text-muted">Pendientes</small>
            </div>
            <div class="col-3 py-3 border-end">
                <div class="h4 mb-0 text-danger">{{ $stats['total_escalated'] }}</div>
                <small class="text-muted">Escalados</small>
            </div>
            <div class="col-3 py-3 border-end">
                <div class="h4 mb-0 text-primary">{{ $stats['today_comments'] }}</div>
                <small class="text-muted">Hoy</small>
            </div>
            <div class="col-3 py-3">
                <div class="h4 mb-0 text-success">{{ $stats['today_replies'] }}</div>
                <small class="text-muted">Respuestas</small>
            </div>
        </div>

        @if($pendingComments->isEmpty())
            <div class="text-center py-4 text-muted">
                <i class="fas fa-check-circle fa-2x mb-2"></i>
                <p class="mb-0">No hay comentarios pendientes</p>
            </div>
        @else
            <div class="list-group list-group-flush">
                @foreach($pendingComments as $comment)
                    <a href="{{ route('helpdesksocial.inbox.show', $comment) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-{{ match($comment->urgency) { 'critical' => 'danger', 'high' => 'warning', 'medium' => 'info', default => 'secondary' } }}">
                                    {{ $comment->urgency }}
                                </span>
                                <span class="badge bg-light text-dark">
                                    <i class="fab fa-{{ $comment->platform === 'instagram' ? 'instagram' : 'facebook' }}"></i>
                                </span>
                                <strong>{{ $comment->author_name }}</strong>
                            </div>
                            <p class="mb-0 text-muted text-truncate" style="max-width: 300px;">
                                {{ Str::limit($comment->body, 60) }}
                            </p>
                        </div>
                        <small class="text-muted">{{ $comment->posted_at?->diffForHumans() }}</small>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
