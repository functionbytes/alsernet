@extends('layouts.theme')

@section('title', 'Bandeja social')

@section('page_header')
    @include('core::components.card', ['title' => 'Bandeja social'])
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Bandeja social</h1>
        <div class="d-flex gap-2">
            <span class="badge bg-primary">{{ $stats['pending'] }} pendientes</span>
            <span class="badge bg-success">{{ $stats['replied'] }} respondidos</span>
            <span class="badge bg-danger">{{ $stats['spam'] }} spam</span>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Sentimiento</label>
                    <select id="sentimentFilter" class="form-select" onchange="applyFilters()">
                        <option value="">Todos</option>
                        <option value="positive">Positivo</option>
                        <option value="neutral">Neutral</option>
                        <option value="negative">Negativo</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select id="statusFilter" class="form-select" onchange="applyFilters()">
                        <option value="">Todos</option>
                        <option value="pending">Pendiente</option>
                        <option value="replied">Respondido</option>
                        <option value="escalated">Escalado</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Acciones masivas</label>
                    <div class="input-group">
                        <select id="bulkTagSelect" class="form-select">
                            <option value="">Etiqueta...</option>
                        </select>
                        <button type="button" class="btn btn-outline-primary" onclick="applyBulkTag()">
                            <i class="fas fa-tag me-1"></i>Aplicar
                        </button>
                    </div>
                </div>
                <div class="col-md-3 d-flex justify-content-end">
                    <div class="form-check">
                        <input type="checkbox" id="showSlaBreached" class="form-check-input" onchange="applyFilters()">
                        <label class="form-check-label" for="showSlaBreached">Solo SLA vencido</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="30"><input type="checkbox" id="selectAll" onclick="toggleSelectAll()"></th>
                            <th width="50">Plataforma</th>
                            <th>Autor</th>
                            <th>Contenido</th>
                            <th>Intención</th>
                            <th>Urgencia</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($comments as $comment)
                        <tr data-comment-id="{{ $comment->id }}" class="{{ $comment->status === 'pending' && !$comment->is_spam ? 'table-warning' : '' }} {{ $comment->sla_response_breached ? ' border-3 ' : '' }}" data-sentiment="{{ $comment->sentiment['label'] ?? 'neutral' }}" data-status="{{ $comment->status }}" data-sla-breached="{{ $comment->sla_response_breached ? '1' : '0' }}">
                            <td>
                                <input type="checkbox" class="comment-checkbox" value="{{ $comment->id }}">
                            </td>
                            <td>
                                <i class="fab fa-{{ $comment->platform }} text-{{ $comment->platform === 'facebook' ? 'primary' : 'danger' }}"></i>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $comment->author_name }}</div>
                                <small class="text-muted">{{ $comment->author_username ?? '' }}</small>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 300px;" title="{{ $comment->body }}">
                                    {{ $comment->body }}
                                </div>
                                @if($comment->external_post_id)
                                <small class="text-muted">
                                    Post: {{ \Illuminate\Support\Str::limit($comment->external_post_id, 30) }}
                                </small>
                                @endif
                            </td>
                            <td>
                                @if($comment->intent)
                                <span class="badge bg-{{ $comment->intent === 'complaint' ? 'danger' : ($comment->intent === 'purchase_interest' ? 'success' : ($comment->intent === 'query' ? 'info' : 'secondary')) }}">
                                    {{ $comment->intent }}
                                </span>
                                @else
                                <span class="badge bg-secondary">-</span>
                                @endif
                            </td>
                            <td>
                                @if($comment->urgency)
                                <span class="badge bg-{{ $comment->urgency === 'critical' ? 'dark' : ($comment->urgency === 'high' ? 'danger' : ($comment->urgency === 'medium' ? 'warning text-dark' : 'success')) }}">
                                    {{ ucfirst($comment->urgency) }}
                                </span>
                                @else
                                <span class="badge bg-secondary">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $comment->status === 'pending' ? 'warning text-dark' : ($comment->status === 'replied' ? 'success' : ($comment->status === 'escalated' ? 'info' : 'secondary')) }}">
                                    {{ $comment->status }}
                                </span>
                                @if($comment->reply_type === 'auto')
                                <span class="badge bg-info">Auto</span>
                                @endif
                                @if($comment->sla_response_breached)
                                <span class="badge bg-danger" title="SLA vencido"><i class="fas fa-clock"></i> SLA</span>
                                @endif
                                @if($comment->sentiment && is_array($comment->sentiment))
                                <span class="badge bg-{{ ($comment->sentiment['label'] ?? 'neutral') === 'positive' ? 'success' : (($comment->sentiment['label'] ?? 'neutral') === 'negative' ? 'danger' : 'secondary') }}">
                                    <i class="fas fa-{{ ($comment->sentiment['label'] ?? 'neutral') === 'positive' ? 'smile' : (($comment->sentiment['label'] ?? 'neutral') === 'negative' ? 'frown' : 'meh') }}"></i>
                                </span>
                                @endif
                            </td>
                            <td>
                                <small>{{ $comment->posted_at->diffForHumans() }}</small>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('helpdesksocial.inbox.show', $comment) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-reply me-1"></i>Responder
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <p>No hay comentarios pendientes</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $comments->links() }}
        </div>
    </div>
@endsection

@section('scripts')
<script>
(function () {
    function setupSocialInboxListener() {
        if (typeof window.Echo === 'undefined') {
            console.warn('[SocialInbox] Echo not ready, retrying in 500ms');
            setTimeout(setupSocialInboxListener, 500);
            return;
        }

        const inboxChannel = window.Echo.private('helpdesk.social.inbox');
        console.log('[SocialInbox] Subscribed to private-helpdesk.social.inbox');

        inboxChannel.listen('.social.comment.received', function (e) {
            const comment = e.comment;
            console.log('[SocialInbox] New comment received', comment);

            // Show toast for high urgency comments
            if (comment.urgency === 'high' || comment.urgency === 'critical') {
                if (window.toastr) {
                    toastr.warning(
                        comment.author_name + ': ' + comment.body.substring(0, 60),
                        'Comentario ' + (comment.urgency === 'critical' ? 'crítico' : 'alta urgencia') + ' en ' + comment.platform,
                        { timeOut: 8000, closeButton: true }
                    );
                }
            }

            // Refresh the page to show the new comment
            window.location.reload();
        });

        inboxChannel.listen('.social.intent.classified', function (e) {
            console.log('[SocialInbox] Intent classified', e);
            // If the comment is already visible, update its badge
            const $row = $('tr[data-comment-id="' + e.comment_id + '"]');
            if ($row.length) {
                window.location.reload();
            }
        });

        inboxChannel.listen('.social.comment.replied', function (e) {
            console.log('[SocialInbox] Comment replied', e);
            window.location.reload();
        });
    }

    // Delay slightly to ensure Echo is bootstrapped
    setTimeout(setupSocialInboxListener, 300);

    function toggleSelectAll() {
        const checked = $('#selectAll').prop('checked');
        $('.comment-checkbox').prop('checked', checked);
    }

    function applyFilters() {
        const sentiment = $('#sentimentFilter').val();
        const status = $('#statusFilter').val();
        const showSla = $('#showSlaBreached').prop('checked');

        $('tr[data-comment-id]').each(function () {
            const $row = $(this);
            let visible = true;

            if (sentiment && $row.data('sentiment') !== sentiment) {
                visible = false;
            }

            if (status && $row.data('status') !== status) {
                visible = false;
            }

            if (showSla && $row.data('sla-breached') !== '1') {
                visible = false;
            }

            $row.toggle(visible);
        });
    }

    function applyBulkTag() {
        const tagId = $('#bulkTagSelect').val();
        const commentIds = $('.comment-checkbox:checked').map(function () { return $(this).val(); }).get();

        if (!tagId) {
            if (window.toastr) { toastr.warning('Selecciona una etiqueta.'); }
            return;
        }

        if (commentIds.length === 0) {
            if (window.toastr) { toastr.warning('Selecciona al menos un comentario.'); }
            return;
        }

        $.ajax({
            url: '{{ route('helpdesksocial.inbox.bulk') }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { tag_id: tagId, comment_ids: commentIds },
            success: function () {
                if (window.toastr) { toastr.success('Etiquetas aplicadas correctamente.'); }
                window.location.reload();
            },
            error: function () {
                if (window.toastr) { toastr.error('Error al aplicar etiquetas.'); }
            }
        });
    }

    $(document).ready(function () {
        $.ajax({
            url: '{{ route('helpdesksocial.tags.index') }}',
            method: 'GET',
            success: function (tags) {
                const $select = $('#bulkTagSelect');
                $select.empty().append('<option value="">Etiqueta...</option>');
                tags.forEach(function (tag) {
                    $select.append('<option value="' + tag.id + '">' + tag.name + '</option>');
                });
            }
        });
    });
})();
</script>
@endsection
