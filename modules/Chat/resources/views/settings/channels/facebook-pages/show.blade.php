@extends('layouts.theme')

@section('content')
<div class="container-fluid">
    @include('core::components.alerts')

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header bg-info-subtle d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fab fa-facebook"></i> {{ $facebookPage->page_name }}</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.chat.channels.facebook-pages.edit', $facebookPage) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <a href="{{ route('settings.chat.channels.facebook-pages.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Información de la página</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small">Nombre de página</label>
                            <div class="fw-bold">{{ $facebookPage->page_name }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Page ID</label>
                            <div><code>{{ $facebookPage->page_id }}</code></div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Bandeja de entrada</label>
                            <div>{{ $facebookPage->inbox->name ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Estado de conexión</label>
                            <div>
                                @if($facebookPage->inbox)
                                    <span class="badge bg-success-subtle text-success">Conectado</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">Desconectado</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Instagram vinculado</label>
                            <div>
                                @if($facebookPage->instagram_id)
                                    <span class="badge bg-success-subtle text-success">Sí</span>
                                    <code class="ms-2">{{ $facebookPage->instagram_id }}</code>
                                @else
                                    <span class="badge bg-info-subtle text-info">No</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Fecha de creación</label>
                            <div>{{ $facebookPage->created_at->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-primary-subtle">
                    <h6 class="mb-0"><i class="fas fa-link"></i> Configuración webhook</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="control-label col-form-label">Webhook URL</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="webhookUrl" value="{{ $facebookPage->getWebhookUrl() }}" readonly>
                            <button class="btn btn-secondary" type="button" onclick="copyWebhookUrl()">
                                <i class="fas fa-clipboard"></i> Copiar
                            </button>
                        </div>
                    </div>

                    <div class="alert alert-info mb-0">
                        <h6 class="fw-bold"><i class="fas fa-info-circle"></i> Instrucciones de configuración</h6>
                        <ol class="small mb-0">
                            <li>Ve a Facebook Developer Console</li>
                            <li>Navega a Webhooks → Page</li>
                            <li>Suscríbete a: messages, messaging_postbacks, message_deliveries, message_reads</li>
                            <li>Usa la URL del webhook anterior</li>
                            <li>Token de verificación: <code>{{ config('channels.facebook.verify_token') }}</code></li>
                        </ol>
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
                        <strong>{{ $facebookPage->inbox->conversations()->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Conversaciones abiertas:</span>
                        <strong>{{ $facebookPage->inbox->conversations()->where('status', 'open')->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Mensajes totales:</span>
                        <strong>{{ $facebookPage->inbox->messages()->count() }}</strong>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-success-subtle">
                    <h6 class="mb-0"><i class="fas fa-bolt"></i> Acciones rápidas</h6>
                </div>
                <div class="card-body">
                    <a href="{{ route('settings.chat.conversation.index', ['inbox_id' => $facebookPage->inbox->id]) }}"
                       class="btn btn-outline-primary w-100 mb-2">
                        <i class="fas fa-comments"></i> Ver conversaciones
                    </a>
                    <form action="{{ route('settings.chat.channels.facebook-pages.reauthorize', $facebookPage) }}" method="POST" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-outline-warning w-100">
                            <i class="fas fa-sync-alt"></i> Reautorizar
                        </button>
                    </form>
                    <button type="button" class="btn btn-outline-danger w-100 delete-btn"
                            data-url="{{ route('settings.chat.channels.facebook-pages.destroy', $facebookPage) }}"
                            data-title="Desconectar página de Facebook">
                        <i class="fas fa-trash"></i> Desconectar
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
