@extends('layouts.theme')

@section('title', 'Detalle de WhatsApp')

@section('content')
<div class="container-fluid">
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-semibold mb-2">{{ $whatsapp->inbox->name }}</h4>
                    <p class="text-muted mb-0">Configuración del canal de WhatsApp</p>
                </div>
                <div>
                    <a href="{{ route('settings.chat.channels.whatsapps.logs.index', $whatsapp) }}" class="btn btn-warning me-1">
                        <i class="fas fa-file-alt me-1"></i> Ver logs
                    </a>
                    <button class="btn btn-info me-1" onclick="verifyConnection()">
                        <i class="fas fa-check-circle me-1"></i> Verificar conexión
                    </button>
                    <button class="btn btn-success me-1" onclick="syncTemplates()">
                        <i class="fas fa-sync-alt me-1"></i> Sincronizar plantillas
                    </button>
                    <a href="{{ route('settings.chat.channels.whatsapps.edit', $whatsapp) }}" class="btn btn-primary me-1">
                        <i class="fas fa-edit me-1"></i> Editar
                    </a>
                    <a href="{{ route('settings.chat.channels.whatsapps.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    @include('core::components.alerts')

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold"><i class="fab fa-whatsapp me-2"></i> Configuración de WhatsApp</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <td class="fw-bold" width="200">Numero de telefono:</td>
                                <td><code>{{ $whatsapp->phone_number }}</code></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Proveedor:</td>
                                <td>
                                    <span class="badge bg-info-subtle text-info">
                                        @if($whatsapp->provider === 'whatsapp_cloud')
                                            WhatsApp Cloud API
                                        @elseif($whatsapp->provider === '360dialog')
                                            360Dialog
                                        @elseif($whatsapp->provider === 'evolution_api')
                                            Evolution API (Baileys)
                                        @else
                                            {{ $whatsapp->provider }}
                                        @endif
                                    </span>
                                </td>
                            </tr>

                            @if($whatsapp->isEvolutionApi())
                            <tr>
                                <td class="fw-bold">Nombre de instancia:</td>
                                <td><code>{{ $whatsapp->getInstanceName() }}</code></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">URL de API:</td>
                                <td><code>{{ $whatsapp->getApiUrl() }}</code></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Estado de conexion:</td>
                                <td>
                                    <span id="connection-status" class="badge bg-secondary">
                                        <span class="spinner-border spinner-border-sm me-1"></span>
                                        Verificando...
                                    </span>
                                </td>
                            </tr>
                            @elseif($whatsapp->is360Dialog())
                            <tr>
                                <td class="fw-bold">Clave API:</td>
                                <td><code>{{ Str::mask($whatsapp->getApiCredential(), '*', 0, -4) }}</code></td>
                            </tr>
                            @elseif($whatsapp->isCloudApi())
                            <tr>
                                <td class="fw-bold">ID numero de telefono:</td>
                                <td><code>{{ $whatsapp->getPhoneNumberId() }}</code></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">ID cuenta de negocio:</td>
                                <td><code>{{ $whatsapp->getBusinessAccountId() }}</code></td>
                            </tr>
                            @endif
                            <tr>
                                <td class="fw-bold">Plantillas de mensaje:</td>
                                <td>{{ count($whatsapp->message_templates ?? []) }} plantillas</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Creado:</td>
                                <td>{{ $whatsapp->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-link me-2"></i> Configuracion de webhook</h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">Configura esta URL de webhook en los ajustes de WhatsApp Business:</p>

                    <div class="input-group mb-3">
                        <input type="text" class="form-control"
                               value="{{ route('api.webhooks.whatsapp.handle') }}"
                               readonly id="webhook-url">
                        <button class="btn btn-secondary"
                                onclick="copyToClipboard('{{ route('api.webhooks.whatsapp.handle') }}')">
                            <i class="fas fa-clipboard me-1"></i> Copiar
                        </button>
                    </div>

                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>Token de verificacion:</strong> Usa <code>{{ config('channels.whatsapp.verify_token') }}</code>
                    </div>
                </div>
            </div>

            @if($whatsapp->isEvolutionApi())
            <div class="card mb-4" id="qr-code-card" style="display:none;">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-qrcode me-2"></i> Conectar WhatsApp</h5>
                </div>
                <div class="card-body text-center">
                    <p class="mb-3">Escanea este codigo QR con WhatsApp para conectar tu instancia:</p>
                    <div id="qr-code-container" class="mb-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando codigo QR...</span>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>Instrucciones:</strong>
                        <ol class="mb-0 text-start">
                            <li>Abre WhatsApp en tu telefono</li>
                            <li>Ve a Ajustes → Dispositivos vinculados</li>
                            <li>Toca "Vincular un dispositivo"</li>
                            <li>Escanea este codigo QR</li>
                        </ol>
                    </div>
                    <button class="btn btn-primary" onclick="refreshQrCode()">
                        <i class="fas fa-redo me-1"></i> Actualizar codigo QR
                    </button>
                </div>
            </div>
            @endif

            @if($whatsapp->message_templates && count($whatsapp->message_templates) > 0)
            <div class="card">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-file-alt me-2"></i> Plantillas de mensaje</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Idioma</th>
                                    <th>Estado</th>
                                    <th>Categoria</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($whatsapp->message_templates as $template)
                                <tr>
                                    <td><code>{{ $template['name'] ?? 'N/A' }}</code></td>
                                    <td>{{ $template['language'] ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $template['status'] === 'APPROVED' ? 'success-subtle text-success' : 'warning-subtle text-warning' }}">
                                            {{ $template['status'] ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>{{ $template['category'] ?? 'N/A' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-chart-bar me-2"></i> Estadisticas</h5>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total de conversaciones:</span>
                        <strong>{{ $whatsapp->inbox->conversations()->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Conversaciones abiertas:</span>
                        <strong>{{ $whatsapp->inbox->conversations()->whereHas('status', fn($q) => $q->where('is_open', true))->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Total de mensajes:</span>
                        <strong>{{ $whatsapp->inbox->messages()->count() }}</strong>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-bolt me-2"></i> Acciones rapidas</h5>
                </div>
                <div class="card-body p-4">
                    <a href="{{ route('chat.conversations.index', ['inbox' => $whatsapp->inbox->id]) }}"
                       class="btn btn-outline-primary w-100 mb-2">
                        <i class="fas fa-comments me-1"></i> Ver conversaciones
                    </a>
                    <a href="{{ route('settings.chat.channels.whatsapps.edit', $whatsapp) }}"
                       class="btn btn-outline-warning w-100 mb-2">
                        <i class="fas fa-edit me-1"></i> Editar ajustes
                    </a>
                    @if($whatsapp->isEvolutionApi())
                    <button type="button" class="btn btn-outline-info w-100 mb-2" onclick="alert('Ajustes avanzados disponibles proximamente')">
                        <i class="fas fa-sliders-h me-1"></i> Ajustes avanzados
                    </button>
                    @endif
                    <button type="button" class="btn btn-outline-danger w-100" onclick="confirmDelete()">
                        <i class="fas fa-trash me-1"></i> Eliminar canal
                    </button>

                    <form id="delete-form" action="{{ route('settings.chat.channels.whatsapps.destroy', $whatsapp) }}" method="POST" class="d-none">
                        @csrf
                        @method('DELETE')
                    </form>

                    <form id="sync-templates-form" action="{{ route('settings.chat.channels.whatsapps.sync-templates', $whatsapp) }}" method="POST" class="d-none">
                        @csrf
                    </form>

                    <form id="verify-connection-form" action="{{ route('settings.chat.channels.whatsapps.verify-connection', $whatsapp) }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        toastr.success('Copiado al portapapeles');
    });
}

function confirmDelete() {
    if (confirm('¿Estas seguro de que deseas eliminar este canal de WhatsApp? Esta accion no se puede deshacer.')) {
        $('#delete-form').submit();
    }
}

function verifyConnection() {
    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Verificando...';

    $.ajax({
        url: '{{ route('settings.chat.channels.whatsapps.verify-connection', $whatsapp) }}',
        type: 'POST',
        dataType: 'json',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                console.log('Connection Info:', response.data);

                // Show connection details in alert
                let details = 'Número: ' + response.data.phone_number + '\n';
                details += 'Estado: ' + response.data.status + '\n';
                details += 'Rating: ' + response.data.quality_rating;
                alert('✅ Conexión exitosa!\n\n' + details);
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            let errorMessage = 'Error al verificar la conexión';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            toastr.error(errorMessage);
        },
        complete: function() {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    });
}

function syncTemplates() {
    if (confirm('¿Sincronizar plantillas de mensaje desde WhatsApp?')) {
        $('#sync-templates-form').submit();
    }
}

@if($whatsapp->isEvolutionApi())
function checkConnectionStatus() {
    $.ajax({
        url: '{{ route('settings.chat.channels.whatsapps.connection-status', $whatsapp) }}',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            const $statusElement = $('#connection-status');
            const $qrCodeCard = $('#qr-code-card');

            if (data.state === 'open') {
                $statusElement
                    .html('<i class="fas fa-check-circle me-1"></i> Conectado')
                    .removeClass()
                    .addClass('badge bg-success');
                $qrCodeCard.hide();
            } else if (data.state === 'close' || data.state === 'connecting') {
                $statusElement
                    .html('<i class="fas fa-exclamation-circle me-1"></i> Desconectado')
                    .removeClass()
                    .addClass('badge bg-danger');
                $qrCodeCard.show();
                loadQrCode();
            } else {
                $statusElement
                    .empty()
                    .append($('<i>').addClass('fas fa-question-circle me-1'))
                    .append(document.createTextNode(' ' + data.state))
                    .removeClass()
                    .addClass('badge bg-warning');
            }
        },
        error: function(error) {
            console.error('Error checking connection status:', error);
            $('#connection-status')
                .html('<i class="fas fa-times-circle me-1"></i> Error')
                .removeClass()
                .addClass('badge bg-danger');
        }
    });
}

function loadQrCode() {
    const $qrContainer = $('#qr-code-container');
    const $spinner = $('<div>').addClass('spinner-border text-primary').attr('role', 'status');
    $spinner.append($('<span>').addClass('visually-hidden').text('Cargando codigo QR...'));
    $qrContainer.empty().append($spinner);

    $.ajax({
        url: '{{ route('settings.chat.channels.whatsapps.qr-code', $whatsapp) }}',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            $qrContainer.empty();

            if (data.qrcode && data.qrcode.base64) {
                const $img = $('<img>').attr({
                    'src': data.qrcode.base64,
                    'alt': 'Codigo QR'
                }).addClass('img-fluid').css('max-width', '300px');
                $qrContainer.append($img);
            } else if (data.error) {
                const $alert = $('<div>').addClass('alert alert-warning').text(data.error);
                $qrContainer.append($alert);
            } else {
                const $alert = $('<div>').addClass('alert alert-info').text('Codigo QR no disponible. La instancia puede estar ya conectada.');
                $qrContainer.append($alert);
            }
        },
        error: function(error) {
            console.error('Error loading QR code:', error);
            const $alert = $('<div>').addClass('alert alert-danger').text('Error al cargar el codigo QR. Intenta de nuevo.');
            $qrContainer.empty().append($alert);
        }
    });
}

function refreshQrCode() {
    loadQrCode();
}

$(document).ready(function() {
    checkConnectionStatus();
    setInterval(checkConnectionStatus, 10000);
});
@endif
</script>
@endpush
