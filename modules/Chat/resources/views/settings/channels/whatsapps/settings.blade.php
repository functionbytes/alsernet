@extends('layouts.theme')

@section('title', 'Ajustes avanzados de WhatsApp')

@section('content')
<div class="container-fluid">
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-semibold mb-2">{{ $whatsapp->inbox->name }} - Ajustes avanzados</h4>
                    <p class="text-muted mb-0">Configura los ajustes de la instancia de Evolution API</p>
                </div>
                <div>
                    <a href="{{ route('settings.chat.channels.whatsapps.show', $whatsapp) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    @include('core::components.alerts')

    <div class="card">
        <div class="card-body">
            <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="actions-tab" data-bs-toggle="tab" data-bs-target="#actions" type="button" role="tab">
                        <i class="fas fa-bolt me-1"></i> Acciones de instancia
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="instance-tab" data-bs-toggle="tab" data-bs-target="#instance" type="button" role="tab">
                        <i class="fas fa-cog me-1"></i> Ajustes de instancia
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="chatwoot-tab" data-bs-toggle="tab" data-bs-target="#chatwoot" type="button" role="tab">
                        <i class="fas fa-comment-dots me-1"></i> Integracion Chatwoot
                    </button>
                </li>
            </ul>

            <form action="{{ route('settings.chat.channels.whatsapps.update-settings', $whatsapp) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="tab-content" id="settingsTabContent">
                    <!-- Tab: Acciones de instancia -->
                    <div class="tab-pane fade show active" id="actions" role="tabpanel">
                        <h5 class="mb-3">Conexion y sincronizacion</h5>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h6 class="card-title"><i class="fas fa-plug me-1"></i> Conexion</h6>
                                        <p class="card-text text-muted small">Gestiona la conexion de WhatsApp</p>
                                        <div class="d-grid gap-2">
                                            <button type="button" class="btn btn-outline-success" onclick="showQrCode()">
                                                <i class="fas fa-qrcode me-1"></i> Mostrar codigo QR
                                            </button>
                                            <form action="{{ route('settings.chat.channels.whatsapps.restart', $whatsapp) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-warning w-100" onclick="return confirm('¿Reiniciar instancia?')">
                                                    <i class="fas fa-redo me-1"></i> Reiniciar instancia
                                                </button>
                                            </form>
                                            <form action="{{ route('settings.chat.channels.whatsapps.logout', $whatsapp) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-danger w-100" onclick="return confirm('¿Desconectar de WhatsApp?')">
                                                    <i class="fas fa-sign-out-alt me-1"></i> Desconectar
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card mb-3" id="sync-card">
                                    <div class="card-body">
                                        <h6 class="card-title"><i class="fas fa-sync-alt me-1"></i> Sincronizacion</h6>
                                        <p class="card-text text-muted small">Sincroniza datos desde WhatsApp</p>
                                        <div id="sync-buttons" class="d-grid gap-2" style="display: none;">
                                            <form action="{{ route('settings.chat.channels.whatsapps.sync-contacts', $whatsapp) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-primary w-100">
                                                    <i class="fas fa-users me-1"></i> Sincronizar contactos
                                                </button>
                                            </form>
                                            <form action="{{ route('settings.chat.channels.whatsapps.sync-chats', $whatsapp) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-primary w-100">
                                                    <i class="fas fa-comments me-1"></i> Sincronizar chats
                                                </button>
                                            </form>
                                            <form action="{{ route('settings.chat.channels.whatsapps.sync-messages', $whatsapp) }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="limit" value="50">
                                                <button type="submit" class="btn btn-outline-success w-100" onclick="return confirm('Se sincronizaran los ultimos 50 mensajes de cada conversacion. Esto puede tardar. ¿Continuar?')">
                                                    <i class="fas fa-envelope me-1"></i> Sincronizar mensajes (ultimos 50)
                                                </button>
                                            </form>
                                        </div>
                                        <div id="sync-disabled-message" class="alert alert-warning mb-0">
                                            <i class="fas fa-exclamation-triangle me-1"></i> Conecta WhatsApp primero para habilitar la sincronizacion
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Seccion de codigo QR -->
                        <div id="qr-code-section" style="display:none;" class="mb-4">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="fas fa-qrcode me-1"></i> Conectar WhatsApp</h6>
                                </div>
                                <div class="card-body text-center">
                                    <p class="mb-3">Escanea este codigo QR con WhatsApp para conectar:</p>
                                    <div id="qr-code-display" class="mb-3">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Cargando...</span>
                                        </div>
                                    </div>
                                    <div class="alert alert-info text-start">
                                        <strong>Instrucciones:</strong>
                                        <ol class="mb-0">
                                            <li>Abre WhatsApp en tu telefono</li>
                                            <li>Ve a Ajustes → Dispositivos vinculados</li>
                                            <li>Toca "Vincular un dispositivo"</li>
                                            <li>Escanea este codigo QR</li>
                                        </ol>
                                    </div>
                                    <button type="button" class="btn btn-primary" onclick="refreshQrCodeInActions()">
                                        <i class="fas fa-redo me-1"></i> Actualizar codigo QR
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="hideQrCode()">
                                        Cerrar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <h5 class="mb-3">Informacion de la instancia</h5>
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Nombre de instancia:</strong> <code>{{ $whatsapp->getInstanceName() }}</code></p>
                                        <p><strong>Numero de telefono:</strong> <code>{{ $whatsapp->phone_number }}</code></p>
                                        <p><strong>URL de API:</strong> <code>{{ $whatsapp->getApiUrl() }}</code></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Estado de conexion:</strong> <span id="status-badge" class="badge bg-secondary">Verificando...</span></p>
                                        <p><strong>Creado:</strong> {{ $whatsapp->created_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Ajustes de instancia -->
                    <div class="tab-pane fade" id="instance" role="tabpanel">
                        <h5 class="mb-3">Comportamiento de la instancia</h5>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="reject_call" name="reject_call" value="1"
                                            {{ ($settings['rejectCall'] ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="reject_call">
                                            <strong>Rechazar llamadas</strong>
                                            <br>
                                            <small class="text-muted">Rechazar automaticamente las llamadas entrantes de WhatsApp</small>
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3" id="msg_call_container">
                                    <label for="msg_call" class="control-label col-form-label">Mensaje de rechazo de llamada</label>
                                    <textarea class="form-control" id="msg_call" name="msg_call" rows="2"
                                        placeholder="Lo siento, no puedo responder llamadas en este momento.">{{ $settings['msgCall'] ?? '' }}</textarea>
                                    <small class="form-text text-muted">Mensaje enviado al rechazar una llamada</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="groups_ignore" name="groups_ignore" value="1"
                                            {{ ($settings['groupsIgnore'] ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="groups_ignore">
                                            <strong>Ignorar grupos</strong>
                                            <br>
                                            <small class="text-muted">No procesar mensajes de grupos de WhatsApp</small>
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="always_online" name="always_online" value="1"
                                            {{ ($settings['alwaysOnline'] ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="always_online">
                                            <strong>Siempre en linea</strong>
                                            <br>
                                            <small class="text-muted">Mostrar como siempre en linea en WhatsApp</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="read_messages" name="read_messages" value="1"
                                            {{ ($settings['readMessages'] ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="read_messages">
                                            <strong>Leer mensajes</strong>
                                            <br>
                                            <small class="text-muted">Marcar automaticamente los mensajes recibidos como leidos</small>
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="read_status" name="read_status" value="1"
                                            {{ ($settings['readStatus'] ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="read_status">
                                            <strong>Leer estados</strong>
                                            <br>
                                            <small class="text-muted">Recibir y procesar actualizaciones de estado de WhatsApp</small>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="sync_full_history" name="sync_full_history" value="1"
                                            {{ ($settings['syncFullHistory'] ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="sync_full_history">
                                            <strong>Sincronizar historial completo</strong>
                                            <br>
                                            <small class="text-muted">Sincronizar el historial completo de mensajes al conectar</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Integracion Chatwoot -->
                    <div class="tab-pane fade" id="chatwoot" role="tabpanel">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="chatwoot_enabled" name="chatwoot_enabled" value="1"
                                    {{ ($chatwootSettings['enabled'] ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="chatwoot_enabled">
                                    <strong>Habilitar integracion con Chatwoot</strong>
                                    <br>
                                    <small class="text-muted">Reenviar mensajes a la instancia de Chatwoot</small>
                                </label>
                            </div>
                        </div>

                        <div id="chatwoot-settings-container">
                            <h5 class="mb-3">Ajustes de conexion</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="chatwoot_url" class="control-label col-form-label">URL de Chatwoot <span class="text-danger">*</span></label>
                                        <input type="url" class="form-control" id="chatwoot_url" name="chatwoot_url"
                                            value="{{ $chatwootSettings['url'] ?? '' }}" placeholder="https://app.chatwoot.com">
                                    </div>

                                    <div class="mb-3">
                                        <label for="chatwoot_account_id" class="control-label col-form-label">ID de cuenta <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="chatwoot_account_id" name="chatwoot_account_id"
                                            value="{{ $chatwootSettings['accountId'] ?? '' }}" placeholder="123">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="chatwoot_token" class="control-label col-form-label">Token de API <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="chatwoot_token" name="chatwoot_token"
                                            value="{{ $chatwootSettings['token'] ?? '' }}" placeholder="tu-token-api">
                                    </div>

                                    <div class="mb-3">
                                        <label for="chatwoot_name_inbox" class="control-label col-form-label">Nombre de bandeja</label>
                                        <input type="text" class="form-control" id="chatwoot_name_inbox" name="chatwoot_name_inbox"
                                            value="{{ $chatwootSettings['nameInbox'] ?? $whatsapp->inbox->name }}">
                                    </div>
                                </div>
                            </div>

                            <h5 class="mb-3 mt-4">Ajustes de mensajes</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="chatwoot_sign_msg" name="chatwoot_sign_msg" value="1"
                                                {{ ($chatwootSettings['signMsg'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="chatwoot_sign_msg">
                                                <strong>Firmar mensajes</strong>
                                                <br>
                                                <small class="text-muted">Agregar firma del agente a los mensajes salientes</small>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="chatwoot_reopen_conversation" name="chatwoot_reopen_conversation" value="1"
                                                {{ ($chatwootSettings['reopenConversation'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="chatwoot_reopen_conversation">
                                                <strong>Reabrir conversacion</strong>
                                                <br>
                                                <small class="text-muted">Reabrir automaticamente conversaciones resueltas</small>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="chatwoot_conversation_pending" name="chatwoot_conversation_pending" value="1"
                                                {{ ($chatwootSettings['conversationPending'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="chatwoot_conversation_pending">
                                                <strong>Conversaciones como pendientes</strong>
                                                <br>
                                                <small class="text-muted">Las nuevas conversaciones inician como pendientes en lugar de abiertas</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="chatwoot_import_contacts" name="chatwoot_import_contacts" value="1"
                                                {{ ($chatwootSettings['importContacts'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="chatwoot_import_contacts">
                                                <strong>Importar contactos</strong>
                                                <br>
                                                <small class="text-muted">Sincronizar contactos de WhatsApp a Chatwoot</small>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="chatwoot_import_messages" name="chatwoot_import_messages" value="1"
                                                {{ ($chatwootSettings['importMessages'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="chatwoot_import_messages">
                                                <strong>Importar mensajes</strong>
                                                <br>
                                                <small class="text-muted">Importar mensajes existentes de WhatsApp a Chatwoot</small>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mb-3" id="import_days_container">
                                        <label for="chatwoot_days_limit_import_messages" class="control-label col-form-label">Limite de dias para importar</label>
                                        <input type="number" class="form-control" id="chatwoot_days_limit_import_messages" name="chatwoot_days_limit_import_messages"
                                            value="{{ $chatwootSettings['daysLimitImportMessages'] ?? 60 }}" min="1" max="365">
                                        <small class="form-text text-muted">Numero de dias de historial de mensajes a importar</small>
                                    </div>
                                </div>
                            </div>

                            <h5 class="mb-3 mt-4">Ajustes avanzados</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="chatwoot_merge_brazil_contacts" name="chatwoot_merge_brazil_contacts" value="1"
                                                {{ ($chatwootSettings['mergeBrazilContacts'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="chatwoot_merge_brazil_contacts">
                                                <strong>Fusionar contactos de Brasil</strong>
                                                <br>
                                                <small class="text-muted">Fusionar contactos con variaciones de formato de telefono brasileno</small>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="chatwoot_organization" class="control-label col-form-label">Organizacion</label>
                                        <input type="text" class="form-control" id="chatwoot_organization" name="chatwoot_organization"
                                            value="{{ $chatwootSettings['organization'] ?? '' }}" placeholder="Tu organizacion">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="chatwoot_logo" class="control-label col-form-label">URL del logo</label>
                                        <input type="url" class="form-control" id="chatwoot_logo" name="chatwoot_logo"
                                            value="{{ $chatwootSettings['logo'] ?? '' }}" placeholder="https://ejemplo.com/logo.png">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check-circle me-1"></i> Guardar ajustes
                    </button>
                    <a href="{{ route('settings.chat.channels.whatsapps.show', $whatsapp) }}" class="btn btn-secondary">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let connectionCheckInterval = null;

function showQrCode() {
    $('#qr-code-section').show();
    loadQrCodeInActions();
    startConnectionPolling();
}

function hideQrCode() {
    $('#qr-code-section').hide();
    stopConnectionPolling();
}

function startConnectionPolling() {
    stopConnectionPolling();
    checkConnectionAfterQR();
    connectionCheckInterval = setInterval(checkConnectionAfterQR, 3000);
}

function stopConnectionPolling() {
    if (connectionCheckInterval) {
        clearInterval(connectionCheckInterval);
        connectionCheckInterval = null;
    }
}

function checkConnectionAfterQR() {
    $.ajax({
        url: '{{ route('settings.chat.channels.whatsapps.connection-status', $whatsapp) }}',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            const state = data.state || (data.instance && data.instance.state) || 'unknown';
            const $statusBadge = $('#status-badge');

            if ($statusBadge.length) {
                if (state === 'open') {
                    $statusBadge.html('<i class="fas fa-check-circle me-1"></i> Conectado').removeClass().addClass('badge bg-success');
                } else if (state === 'close' || state === 'connecting') {
                    const msg = state === 'connecting' ? 'Conectando...' : 'Desconectado';
                    const cls = state === 'connecting' ? 'badge bg-warning' : 'badge bg-danger';
                    $statusBadge.html('<i class="fas fa-exclamation-circle me-1"></i> ' + msg).removeClass().addClass(cls);
                } else {
                    $statusBadge.html('<i class="fas fa-question-circle me-1"></i> ' + state).removeClass().addClass('badge bg-warning');
                }
            }

            const $syncButtons = $('#sync-buttons');
            const $syncMessage = $('#sync-disabled-message');
            if (state === 'open') {
                $syncButtons.show();
                $syncMessage.hide();
            } else {
                $syncButtons.hide();
                $syncMessage.show();
            }

            if (state === 'open') {
                const $qrDisplay = $('#qr-code-display');
                if ($qrDisplay.find('.spinner-border').length) {
                    $qrDisplay.html(
                        '<div class="alert alert-success">' +
                            '<i class="fas fa-check-circle fs-1 d-block mb-3"></i>' +
                            '<h5>Conectado exitosamente</h5>' +
                            '<p class="mb-0">Tu instancia de WhatsApp esta conectada y lista para usar.</p>' +
                        '</div>' +
                        '<button type="button" class="btn btn-primary mt-3" onclick="hideQrCode(); location.reload();">' +
                            '<i class="fas fa-redo me-1"></i> Recargar pagina' +
                        '</button>'
                    );
                }
                stopConnectionPolling();
            }
        },
        error: function(error) {
            console.error('Error checking connection status:', error);
        }
    });
}

function loadQrCodeInActions() {
    const $qrDisplay = $('#qr-code-display');
    $qrDisplay.html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>');

    $.ajax({
        url: '{{ route('settings.chat.channels.whatsapps.qr-code', $whatsapp) }}',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            $qrDisplay.empty();

            if (data.error) {
                const $alert = $('<div>').addClass('alert alert-warning').text(data.error);
                $qrDisplay.append($alert);
                return;
            }

            let qrBase64 = null;
            let pairingCode = null;

            if (data.qrcode && data.qrcode.base64) {
                qrBase64 = data.qrcode.base64;
                pairingCode = data.qrcode.pairingCode;
            } else if (data.base64) {
                qrBase64 = data.base64;
                pairingCode = data.pairingCode;
            } else if (data.qrcode && data.qrcode.code) {
                qrBase64 = data.qrcode.code;
                pairingCode = data.qrcode.pairingCode;
            } else if (data.code) {
                qrBase64 = data.code;
                pairingCode = data.pairingCode;
            }

            if (qrBase64) {
                if (!qrBase64.startsWith('data:image')) {
                    qrBase64 = 'data:image/png;base64,' + qrBase64;
                }
                const $img = $('<img>').attr({
                    'src': qrBase64,
                    'alt': 'Codigo QR'
                }).addClass('img-fluid').css('max-width', '400px');
                $qrDisplay.append($img);

                if (pairingCode) {
                    const $pairingAlert = $('<div>').addClass('alert alert-info mt-3');
                    $pairingAlert.append($('<strong>').text('Codigo de emparejamiento: '));
                    $pairingAlert.append(document.createTextNode(pairingCode));
                    $qrDisplay.append($pairingAlert);
                }
            } else if (pairingCode) {
                const $pairingAlert = $('<div>').addClass('alert alert-info');
                $pairingAlert.append($('<strong>').text('Codigo de emparejamiento: '));
                $pairingAlert.append($('<code>').addClass('fs-4').text(pairingCode));
                $pairingAlert.append($('<br>'));
                $pairingAlert.append($('<small>').text('Ingresa este codigo en WhatsApp → Dispositivos vinculados → Vincular con numero de telefono'));
                $qrDisplay.append($pairingAlert);
            } else if (data.instance && data.instance.status === 'open') {
                const $successAlert = $('<div>').addClass('alert alert-success');
                $successAlert.append($('<i>').addClass('fas fa-check-circle me-1'));
                $successAlert.append(document.createTextNode(' La instancia ya esta conectada'));
                $qrDisplay.append($successAlert);
            } else {
                const $infoAlert = $('<div>').addClass('alert alert-info').text('Codigo QR no disponible. La instancia puede estar ya conectada o aun conectando.');
                $qrDisplay.append($infoAlert);
            }
        },
        error: function(error) {
            console.error('Error loading QR code:', error);
            const $errorAlert = $('<div>').addClass('alert alert-danger');
            $errorAlert.append(document.createTextNode('Error al cargar el codigo QR: '));
            $errorAlert.append(document.createTextNode(error.message || 'Error desconocido'));
            $qrDisplay.empty().append($errorAlert);
        }
    });
}

function refreshQrCodeInActions() {
    loadQrCodeInActions();
}

function checkConnectionStatusInActions() {
    $.ajax({
        url: '{{ route('settings.chat.channels.whatsapps.connection-status', $whatsapp) }}',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            const state = data.state || (data.instance && data.instance.state) || 'unknown';
            const $statusBadge = $('#status-badge');

            if ($statusBadge.length) {
                if (state === 'open') {
                    $statusBadge.html('<i class="fas fa-check-circle me-1"></i> Conectado').removeClass().addClass('badge bg-success');
                } else if (state === 'close' || state === 'connecting') {
                    const msg = state === 'connecting' ? 'Conectando...' : 'Desconectado';
                    const cls = state === 'connecting' ? 'badge bg-warning' : 'badge bg-danger';
                    $statusBadge.html('<i class="fas fa-exclamation-circle me-1"></i> ' + msg).removeClass().addClass(cls);
                } else {
                    $statusBadge.html('<i class="fas fa-question-circle me-1"></i> ' + state).removeClass().addClass('badge bg-warning');
                }
            }

            const $syncButtons = $('#sync-buttons');
            const $syncMessage = $('#sync-disabled-message');
            if (state === 'open') {
                $syncButtons.show();
                $syncMessage.hide();
            } else {
                $syncButtons.hide();
                $syncMessage.show();
            }
        },
        error: function(error) {
            console.error('Error checking connection status:', error);
            $('#status-badge').html('<i class="fas fa-times-circle me-1"></i> Error').removeClass().addClass('badge bg-danger');
        }
    });
}

$(document).ready(function() {
    $('#reject_call').on('change', function() {
        $('#msg_call_container').toggle(this.checked);
    });

    $('#chatwoot_enabled').on('change', function() {
        $('#chatwoot-settings-container').toggle(this.checked);
    });

    $('#chatwoot_import_messages').on('change', function() {
        $('#import_days_container').toggle(this.checked);
    });

    $('#msg_call_container').toggle($('#reject_call').is(':checked'));
    $('#chatwoot-settings-container').toggle($('#chatwoot_enabled').is(':checked'));
    $('#import_days_container').toggle($('#chatwoot_import_messages').is(':checked'));

    checkConnectionStatusInActions();
    setInterval(checkConnectionStatusInActions, 10000);
});
</script>
@endpush
