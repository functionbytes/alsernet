@extends('layouts.theme')

@section('title', 'Menciones sociales')

@section('page_header')
    @include('core::components.card', ['title' => 'Menciones sociales'])
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Menciones sociales</h1>
        <div class="d-flex gap-2">
            <span class="badge bg-danger">{{ $mentions->where('sentiment', 'negative')->count() }} negativas</span>
            <span class="badge bg-success">{{ $mentions->where('sentiment', 'positive')->count() }} positivas</span>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('helpdesksocial.mentions.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Plataforma</label>
                    <select name="platform" class="form-select" onchange="this.form.submit()">
                        <option value="">Todas</option>
                        <option value="facebook" {{ ($filters['platform'] ?? '') === 'facebook' ? 'selected' : '' }}>Facebook</option>
                        <option value="instagram" {{ ($filters['platform'] ?? '') === 'instagram' ? 'selected' : '' }}>Instagram</option>
                        {{-- Twitter: pendiente de provider --}}
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sentimiento</label>
                    <select name="sentiment" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        <option value="positive" {{ ($filters['sentiment'] ?? '') === 'positive' ? 'selected' : '' }}>Positivo</option>
                        <option value="neutral" {{ ($filters['sentiment'] ?? '') === 'neutral' ? 'selected' : '' }}>Neutral</option>
                        <option value="negative" {{ ($filters['sentiment'] ?? '') === 'negative' ? 'selected' : '' }}>Negativo</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        <option value="new" {{ ($filters['status'] ?? '') === 'new' ? 'selected' : '' }}>Nuevo</option>
                        <option value="reviewed" {{ ($filters['status'] ?? '') === 'reviewed' ? 'selected' : '' }}>Revisado</option>
                        <option value="archived" {{ ($filters['status'] ?? '') === 'archived' ? 'selected' : '' }}>Archivado</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <a href="{{ route('helpdesksocial.mentions.index') }}" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-times me-2"></i>Limpiar filtros
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Plataforma</th>
                            <th>Autor</th>
                            <th>Contenido</th>
                            <th>Sentimiento</th>
                            <th>Engagement</th>
                            <th>Estado</th>
                            <th>Descubierto</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mentions as $mention)
                        <tr data-mention-id="{{ $mention->id }}">
                            <td>
                                <i class="fab fa-{{ $mention->platform }} text-{{ $mention->platform === 'facebook' ? 'primary' : 'danger' }}"></i>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $mention->author_name }}</div>
                                <small class="text-muted">{{ $mention->author_username ?? '' }}</small>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 280px;" title="{{ $mention->body }}">
                                    {{ $mention->body }}
                                </div>
                                @if($mention->url)
                                <small>
                                    <a href="{{ safe_external_url($mention->url) }}" target="_blank" class="text-muted">
                                        <i class="fas fa-external-link-alt me-1"></i>Ver publicación
                                    </a>
                                </small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $mention->sentiment === 'positive' ? 'success' : ($mention->sentiment === 'negative' ? 'danger' : 'secondary') }}">
                                    <i class="fas fa-{{ $mention->sentiment === 'positive' ? 'smile' : ($mention->sentiment === 'negative' ? 'frown' : 'meh') }} me-1"></i>
                                    {{ ucfirst($mention->sentiment) }}
                                </span>
                                @if($mention->sentiment_score)
                                <small class="text-muted d-block">{{ number_format($mention->sentiment_score, 2) }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $mention->engagement_count }}</span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $mention->status === 'new' ? 'warning text-dark' : ($mention->status === 'reviewed' ? 'info' : 'secondary') }}">
                                    {{ ucfirst($mention->status) }}
                                </span>
                            </td>
                            <td>
                                <small>{{ $mention->discovered_at?->diffForHumans() }}</small>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary" title="Marcar como revisado" onclick="markMentionAsReviewed({{ $mention->id }})">
                                    <i class="fas fa-check"></i>
                                </button>
                                <a href="{{ safe_external_url($mention->url) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Ver en plataforma">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fas fa-search fa-2x mb-2"></i>
                                <p>No hay menciones encontradas</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $mentions->links() }}
        </div>
    </div>
@endsection

@section('scripts')
<script>
(function () {
    function markMentionAsReviewed(id) {
        $.ajax({
            url: '{{ url('panel/helpdesk/social/mentions') }}/' + id + '/review',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                if (window.toastr) {
                    toastr.success('Mención marcada como revisada.');
                }
                $('tr[data-mention-id="' + id + '"] td:nth-child(6) .badge').removeClass('bg-warning text-dark').addClass('bg-info').text('Reviewed');
            },
            error: function () {
                if (window.toastr) {
                    toastr.error('No se pudo actualizar la mención.');
                }
            }
        });
    }

    window.markMentionAsReviewed = markMentionAsReviewed;
})();
</script>
@endsection
