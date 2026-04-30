@extends('layouts.theme')

@section('content')
<div class="container-fluid">
    @include('core::components.alerts')

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header bg-info-subtle d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fab fa-instagram"></i> {{ $instagram->inbox->name ?? $instagram->username }}</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.chat.channels.instagrams.edit', $instagram) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <a href="{{ route('settings.chat.channels.instagrams.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Detalles de la cuenta</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small">Instagram ID</label>
                            <div><code>{{ $instagram->instagram_id }}</code></div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Username</label>
                            <div>{{ $instagram->username ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Bandeja de entrada</label>
                            <div>{{ $instagram->inbox->name ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Página de Facebook vinculada</label>
                            <div>
                                @if($instagram->facebookPage)
                                    <a href="{{ route('settings.chat.channels.facebook-pages.show', $instagram->facebookPage) }}">
                                        {{ $instagram->facebookPage->page_name }}
                                    </a>
                                @else
                                    <span class="text-muted">No conectada</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Estado del token</label>
                            <div>
                                @if($instagram->isTokenExpired())
                                    <span class="badge bg-danger-subtle text-danger">Expirado</span>
                                @elseif($instagram->needsTokenRefresh())
                                    <span class="badge bg-warning-subtle text-warning">Por expirar</span>
                                    @if($instagram->token_expires_at)
                                        <small class="text-muted ms-2">{{ $instagram->token_expires_at->diffForHumans() }}</small>
                                    @endif
                                @else
                                    <span class="badge bg-success-subtle text-success">Activo</span>
                                    @if($instagram->token_expires_at)
                                        <small class="text-muted ms-2">{{ $instagram->token_expires_at->diffForHumans() }}</small>
                                    @endif
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Fecha de creación</label>
                            <div>{{ $instagram->created_at->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-primary-subtle">
                    <h6 class="mb-0"><i class="fas fa-link"></i> Configuración webhook</h6>
                </div>
                <div class="card-body">
                    <p class="mb-3">Configura esta URL de webhook en tu cuenta de Instagram Business:</p>

                    <div class="mb-3">
                        <label class="control-label col-form-label">Webhook URL</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="webhookUrl" value="{{ route('webhooks.instagram.handle') }}" readonly>
                            <button class="btn btn-secondary" type="button" onclick="copyWebhookUrl()">
                                <i class="fas fa-clipboard"></i> Copiar
                            </button>
                        </div>
                    </div>

                    <div class="alert alert-info mb-0">
                        <h6 class="fw-bold"><i class="fas fa-info-circle"></i> Token de verificación</h6>
                        <p class="mb-0">Usa: <code>{{ config('channels.instagram.verify_token') }}</code></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header bg-info-subtle">
                    <h6 class="mb-0"><i class="fas fa-chart-line"></i> Estadísticas</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Conversaciones totales:</span>
                        <strong>{{ $instagram->inbox->conversations()->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Conversaciones abiertas:</span>
                        <strong>{{ $instagram->inbox->conversations()->where('status', 'open')->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Mensajes totales:</span>
                        <strong>{{ $instagram->inbox->messages()->count() }}</strong>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-success-subtle">
                    <h6 class="mb-0"><i class="fas fa-bolt"></i> Acciones rápidas</h6>
                </div>
                <div class="card-body">
                    <a href="{{ route('settings.chat.conversation.index', ['inbox_id' => $instagram->inbox->id]) }}"
                       class="btn btn-outline-primary w-100 mb-2">
                        <i class="fas fa-comments"></i> Ver conversaciones
                    </a>
                    <a href="{{ route('settings.chat.channels.instagrams.edit', $instagram) }}"
                       class="btn btn-outline-warning w-100 mb-2">
                        <i class="fas fa-edit"></i> Editar configuración
                    </a>
                    <button type="button" class="btn btn-outline-danger w-100 delete-btn"
                            data-url="{{ route('settings.chat.channels.instagrams.destroy', $instagram) }}"
                            data-title="Eliminar cuenta de Instagram">
                        <i class="fas fa-trash"></i> Eliminar cuenta
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@include('core::components.delete')
@endsection

@push('scripts')
<script>
(function() {
    function copyWebhookUrl() {
        const input = document.getElementById('webhookUrl');
        input.select();
        navigator.clipboard.writeText(input.value).then(function() {
            toastr.success('URL copiada al portapapeles');
        });
    }

    window.copyWebhookUrl = copyWebhookUrl;

    document.querySelectorAll('.delete-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const url = this.dataset.url;
            const title = this.dataset.title;
            const modal = document.getElementById('delete-modal');
            const form = modal.querySelector('#delete-form');
            const modalTitle = modal.querySelector('.modal-title');

            form.action = url;
            modalTitle.textContent = title;

            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        });
    });
})();
</script>
@endpush
