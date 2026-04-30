@extends('layouts.theme')

@section('content')
@include('core::components.alerts')

<div class="card bg-info-subtle mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-2">{{ $api->inbox->name ?? 'Canal API' }}</h5>
                <p class="card-text text-muted mb-0">
                    Detalles e información de integración del canal API
                </p>
            </div>
            <div>
                <a href="{{ route('settings.chat.channels.api.edit', $api) }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <a href="{{ route('settings.chat.channels.api.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3 border-bottom pb-2">Información del canal</h6>
                <table class="table">
                    <tbody>
                        <tr>
                            <th style="width: 200px;">Nombre del inbox:</th>
                            <td>{{ $api->inbox->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Identificador:</th>
                            <td><code>{{ $api->identifier }}</code></td>
                        </tr>
                        <tr>
                            <th>Webhook URL:</th>
                            <td>{{ $api->webhook_url ?? 'No configurado' }}</td>
                        </tr>
                        <tr>
                            <th>Estado:</th>
                            <td>
                                @if($api->active)
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="fas fa-check-circle"></i> Activo
                                    </span>
                                @else
                                    <span class="badge bg-info-subtle text-info">
                                        <i class="fas fa-times-circle"></i> Inactivo
                                    </span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Creado:</th>
                            <td>{{ $api->created_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                        <tr>
                            <th>Última actualización:</th>
                            <td>{{ $api->updated_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                    </tbody>
                </table>

                @if($api->additional_settings)
                    <div class="mt-3">
                        <h6 class="fw-bold mb-3 border-bottom pb-2">Configuración adicional</h6>
                        <pre class="bg-light p-3 rounded"><code>{{ json_encode(json_decode($api->additional_settings), JSON_PRETTY_PRINT) }}</code></pre>
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold mb-3 border-bottom pb-2">Integración API</h6>

                <div class="mb-4">
                    <label class="control-label col-form-label">
                        <i class="fas fa-cloud-upload-alt"></i> URL del webhook de mensajes entrantes
                    </label>
                    <p class="text-muted small">Configura tu sistema para enviar mensajes a esta URL:</p>
                    <div class="input-group">
                        <input type="text" class="form-control font-monospace" id="incomingWebhookUrl" value="{{ $incomingWebhookUrl }}" readonly>
                        <button class="btn btn-secondary" type="button" onclick="copyToClipboard('incomingWebhookUrl')">
                            <i class="fas fa-clipboard"></i> Copiar
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="control-label col-form-label">
                        <i class="fas fa-key"></i> Token HMAC
                    </label>
                    <p class="text-muted small">Usa este token para firmar tus peticiones API para autenticación:</p>
                    <div class="input-group mb-2">
                        <input type="password" class="form-control font-monospace" id="hmacToken" value="{{ $api->hmac_token }}" readonly>
                        <button class="btn btn-secondary" type="button" onclick="toggleTokenVisibility()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                        <button class="btn btn-secondary" type="button" onclick="copyToClipboard('hmacToken')">
                            <i class="fas fa-clipboard"></i> Copiar
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="control-label col-form-label">
                        <i class="fas fa-shield-alt"></i> Token de verificación del webhook
                    </label>
                    <p class="text-muted small">Usa este token para verificar las devoluciones de llamada del webhook:</p>
                    <div class="input-group">
                        <input type="text" class="form-control font-monospace" id="verifyToken" value="{{ $api->webhook_verify_token }}" readonly>
                        <button class="btn btn-secondary" type="button" onclick="copyToClipboard('verifyToken')">
                            <i class="fas fa-clipboard"></i> Copiar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card bg-light">
            <div class="card-body">
                <h6 class="mb-3"><i class="fas fa-book"></i> Guía de integración</h6>
                <p class="mb-2 small"><strong>Para enviar un mensaje a este canal:</strong></p>
                <ol class="mb-2 small">
                    <li>Envía una petición POST a la "URL del webhook de mensajes entrantes" arriba</li>
                    <li>Incluye la firma HMAC en las cabeceras de la petición</li>
                    <li>Formatea el payload del mensaje según la documentación de la API</li>
                </ol>
                <p class="mb-0 small"><strong>¿Necesitas ayuda?</strong> Consulta la documentación de la API para ejemplos de integración detallados.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyToClipboard(elementId) {
    const input = document.getElementById(elementId);
    const originalType = input.type;

    input.type = 'text';
    input.select();
    input.setSelectionRange(0, 99999);

    try {
        navigator.clipboard.writeText(input.value).then(function() {
            showCopySuccess(event.currentTarget);
        }).catch(function() {
            document.execCommand('copy');
            showCopySuccess(event.currentTarget);
        });
    } catch (err) {
        document.execCommand('copy');
        showCopySuccess(event.currentTarget);
    }

    input.type = originalType;
}

function showCopySuccess(btn) {
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i> Copiado';
    btn.classList.add('btn-success');
    btn.classList.remove('btn-outline-secondary');

    setTimeout(function() {
        btn.innerHTML = originalHtml;
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-secondary');
    }, 2000);
}

function toggleTokenVisibility() {
    const input = document.getElementById('hmacToken');
    const icon = document.getElementById('toggleIcon');

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>
@endpush
