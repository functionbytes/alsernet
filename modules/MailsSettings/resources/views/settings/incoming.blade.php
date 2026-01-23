@extends('layouts.theme')

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        {{-- Main Card --}}
        <div class="card">
            {{-- Header Section --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Configuración de correo entrante</h5>
                        <p class="small mb-0 text-muted">Configure diferentes manejadores para convertir los correos electrónicos entrantes en tickets y respuestas</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.email.index') }}" class="btn btn-light">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </div>
            </div>

            {{-- Info Alert --}}
            <div class="card-body border-bottom bg-light-info">
                <div class="alert alert-info border-0 bg-info-subtle text-info mb-0">
                    <div class="d-flex align-items-start gap-2">
                        <i class="fa fa-circle-info fs-5"></i>
                        <div>
                            <strong>Manejadores de email</strong>
                            <p class="mb-0">Puede habilitar múltiples manejadores al mismo tiempo. Cada uno procesará los correos entrantes de forma independiente.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Handlers Content --}}
            <div class="card-body">

                <!-- IMAP Handler -->
                <div class="card mb-3 shadow-sm">
                    <div class="card-header bg-light border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">IMAP</h6>
                            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#imapCollapse">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                    </div>
                    <div class="collapse show" id="imapCollapse">
                        <div class="card-body">
                            <p class="text-muted mb-3">Conecte sus cuentas de correo electrónico existentes a Alsernet usando IMAP.</p>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addImapConnectionModal">
                                <i class="fa fa-plus"></i> Agregar conexión
                            </button>

                            <!-- Lista de conexiones IMAP -->
                            <div class="mt-3" id="imapConnectionsList">
                                @if(isset($settings['imap']['connections']) && count($settings['imap']['connections']) > 0)
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Nombre</th>
                                                    <th>Servidor</th>
                                                    <th>Usuario</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($settings['imap']['connections'] as $connection)
                                                    <tr>
                                                        <td>{{ $connection['name'] }}</td>
                                                        <td>{{ $connection['host'] }}:{{ $connection['port'] }}</td>
                                                        <td>{{ $connection['username'] }}</td>
                                                        <td>
                                                            <button class="btn btn-sm btn-danger" onclick="deleteImapConnection('{{ $connection['id'] }}')">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="text-muted small mb-0">No hay conexiones IMAP configuradas</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pipe Handler -->
                <div class="card mb-3 shadow-sm">
                    <div class="card-header bg-light border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">Pipe</h6>
                            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#pipeCollapse">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                    </div>
                    <div class="collapse" id="pipeCollapse">
                        <div class="card-body">
                            <p class="text-muted mb-3">Reciba correos electrónicos en Alsernet desde cPanel u otro panel de control utilizado por su proveedor de alojamiento.</p>

                            <form method="POST" action="{{ route('settings.incoming-email.pipe.update') }}">
                                @csrf
                                @method('PUT')

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="pipeEnabled" name="pipe_enabled" value="1" {{ ($settings['pipe']['enabled'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="pipeEnabled">Habilitar Pipe Handler</label>
                                </div>

                                <div class="mb-3">
                                    <label for="pipeEmailAddress" class="form-label fw-semibold">Dirección de email</label>
                                    <input type="email" class="form-control" id="pipeEmailAddress" name="pipe_email_address" value="{{ ($settings['pipe']['email_address'] ?? '') }}" placeholder="support@Alsernet.com">
                                    <small class="text-muted">Email de destino que reenviará correos al sistema</small>
                                </div>

                                <div class="alert alert-info mb-3">
                                    <strong class="mb-1">Ruta del script:</strong><br>
                                    <code>{{ ($settings['pipe']['script_path'] ?? '') }}</code>
                                    <p class="mb-0 mt-2 small">Configure esta ruta en su panel de control de email (cPanel, Plesk, etc.)</p>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Guardar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- REST API Handler -->
                <div class="card mb-3 shadow-sm">
                    <div class="card-header bg-light border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">REST API</h6>
                            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#apiCollapse">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                    </div>
                    <div class="collapse" id="apiCollapse">
                        <div class="card-body">
                            <p class="text-muted mb-3">Envíe correos electrónicos a Alsernet desde una aplicación de terceros o un sitio web diferente usando la API REST.</p>

                            <form method="POST" action="{{ route('settings.incoming-email.api.update') }}">
                                @csrf
                                @method('PUT')

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="apiEnabled" name="api_enabled" value="1" {{ ($settings['api']['enabled'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="apiEnabled">Habilitar REST API Handler</label>
                                </div>

                                <div class="mb-3">
                                    <label for="apiKey" class="form-label fw-semibold">API Key</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control font-monospace" id="apiKey" name="api_key" value="{{ ($settings['api']['api_key'] ?? '') }}" readonly>
                                        <button type="button" class="btn btn-outline-secondary" onclick="copyToClipboard('apiKey')">
                                            <i class="fa fa-copy"></i>
                                        </button>
                                        <button type="button" class="btn btn-primary" id="generateApiKeyBtn">
                                            <i class="fa fa-rotate"></i> Regenerar
                                        </button>
                                    </div>
                                    <small class="text-muted">Esta clave se usa para autenticar peticiones a la API</small>
                                </div>

                                <div class="alert alert-info mb-3">
                                    <strong>Endpoint URL:</strong><br>
                                    <code>{{ ($settings['api']['api_url'] ?? '') }}</code>
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="copyToClipboard('apiUrl')">
                                        <i class="fa fa-copy"></i> Copiar URL
                                    </button>
                                    <input type="hidden" id="apiUrl" value="{{ ($settings['api']['api_url'] ?? '') }}">
                                </div>

                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Guardar
                                    </button>
                                    <a href="{{ route('settings.incoming-email.api.documentation') }}" class="btn btn-outline-info" target="_blank">
                                        <i class="fas fa-book me-2"></i>Ver documentación
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Gmail API Handler -->
                <div class="card mb-3 shadow-sm">
                    <div class="card-header bg-light border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">Gmail API</h6>
                            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#gmailCollapse">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                    </div>
                    <div class="collapse" id="gmailCollapse">
                        <div class="card-body">
                            <p class="text-muted mb-3">Conecte cuentas de Gmail directamente usando la API de Gmail con OAuth2.</p>

                            <form method="POST" action="{{ route('settings.incoming-email.gmail.update') }}">
                                @csrf
                                @method('PUT')

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="gmailEnabled" name="gmail_enabled" value="1" {{ ($settings['gmail']['enabled'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="gmailEnabled">Habilitar Gmail Handler</label>
                                </div>

                                <div class="alert alert-info mb-3">
                                    <i class="fa fa-info-circle"></i> <strong>Configuración OAuth2</strong>
                                    <p class="mb-0 mt-2 small">Necesita crear credenciales OAuth2 en <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a> y configurar el Redirect URI autorizado:</p>
                                    <code class="mt-1 d-block">{{ route('settings.incoming-email.gmail.callback') }}</code>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="gmailClientId" class="form-label fw-semibold">Client ID</label>
                                        <input type="text" class="form-control font-monospace" id="gmailClientId" name="gmail_client_id" value="{{ ($settings['gmail']['client_id'] ?? '') }}" placeholder="xxx.apps.googleusercontent.com">
                                        <small class="text-muted">Client ID de Google Cloud Console</small>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="gmailClientSecret" class="form-label fw-semibold">Client Secret</label>
                                        <input type="password" class="form-control font-monospace" id="gmailClientSecret" name="gmail_client_secret" value="{{ ($settings['gmail']['client_secret'] ?? '') }}">
                                        <small class="text-muted">Client Secret de Google Cloud Console</small>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary mb-3">
                                    <i class="fas fa-save me-2"></i>Guardar configuración
                                </button>
                            </form>

                            <!-- Gmail Connections List -->
                            <hr class="my-3">
                            <h6 class="mb-3">Cuentas conectadas</h6>

                            @if(isset($settings['gmail']['connections']) && count($settings['gmail']['connections']) > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Email</th>
                                                <th>Conectado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($settings['gmail']['connections'] as $connection)
                                                <tr>
                                                    <td><i class="fa-brands fa-google text-danger"></i> {{ $connection['email'] }}</td>
                                                    <td><small class="text-muted">{{ $connection['created_at'] }}</small></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-danger" onclick="deleteGmailConnection('{{ $connection['id'] }}')">
                                                            <i class="fa fa-trash"></i> Eliminar
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted small mb-3">No hay cuentas de Gmail conectadas</p>
                            @endif

                            <a href="{{ route('settings.incoming-email.gmail.authorize') }}" class="btn btn-success btn-sm" {{ empty(($settings['gmail']['client_id'] ?? '')) || empty(($settings['gmail']['client_secret'] ?? '')) ? 'disabled' : '' }}>
                                <i class="fa-brands fa-google"></i> Conectar cuenta Gmail
                            </a>
                            @if(empty(($settings['gmail']['client_id'] ?? '')) || empty(($settings['gmail']['client_secret'] ?? '')))
                                <small class="text-danger d-block mt-2">Configure primero Client ID y Client Secret</small>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Mailgun Handler -->
                <div class="card mb-3 shadow-sm">
                    <div class="card-header bg-light border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">Mailgun</h6>
                            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#mailgunCollapse">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                    </div>
                    <div class="collapse" id="mailgunCollapse">
                        <div class="card-body">
                            <p class="text-muted mb-3">Reciba correos electrónicos a través de webhooks de Mailgun.</p>

                            <form method="POST" action="{{ route('settings.incoming-email.mailgun.update') }}">
                                @csrf
                                @method('PUT')

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="mailgunEnabled" name="mailgun_enabled" value="1" {{ ($settings['mailgun']['enabled'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="mailgunEnabled">Habilitar Mailgun Handler</label>
                                </div>

                                <div class="mb-3">
                                    <label for="mailgunApiKey" class="form-label fw-semibold">Mailgun API Key</label>
                                    <input type="password" class="form-control font-monospace" id="mailgunApiKey" name="mailgun_api_key" value="{{ ($settings['mailgun']['api_key'] ?? '') }}" placeholder="key-xxxxxxxxxxxxxxxxxxxxxxxx">
                                    <small class="text-muted">API Key de su cuenta de Mailgun</small>
                                </div>

                                <div class="mb-3">
                                    <label for="mailgunDomain" class="form-label fw-semibold">Mailgun Domain</label>
                                    <input type="text" class="form-control" id="mailgunDomain" name="mailgun_domain" value="{{ ($settings['mailgun']['domain'] ?? '') }}" placeholder="mg.Alsernet.com">
                                    <small class="text-muted">Dominio configurado en Mailgun</small>
                                </div>

                                <div class="alert alert-info mb-3">
                                    <strong>Webhook URL:</strong><br>
                                    <code>{{ ($settings['mailgun']['webhook_url'] ?? '') }}</code>
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="copyToClipboard('mailgunWebhookUrl')">
                                        <i class="fa fa-copy"></i> Copiar URL
                                    </button>
                                    <input type="hidden" id="mailgunWebhookUrl" value="{{ ($settings['mailgun']['webhook_url'] ?? '') }}">
                                    <p class="mb-0 mt-2 small">Configure esta URL en Mailgun Dashboard → Sending → Webhooks</p>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Guardar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- phpList Handler -->
                <div class="card mb-3 shadow-sm">
                    <div class="card-header bg-light border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">phpList</h6>
                            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#phplistCollapse">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                    </div>
                    <div class="collapse" id="phplistCollapse">
                        <div class="card-body">
                            <p class="text-muted mb-3">Conecte con su instalación de phpList para gestionar listas de correo y suscripciones.</p>

                            <form method="POST" action="{{ route('settings.incoming-email.phplist.update') }}">
                                @csrf
                                @method('PUT')

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="phplistEnabled" name="phplist_enabled" value="1" {{ ($settings['phplist']['enabled'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="phplistEnabled">Habilitar phpList Handler</label>
                                </div>

                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label for="phplistApiUrl" class="form-label fw-semibold">API URL</label>
                                        <input type="url" class="form-control" id="phplistApiUrl" name="phplist_api_url" value="{{ ($settings['phplist']['api_url'] ?? '') }}" placeholder="https://phplist.example.com/api">
                                        <small class="text-muted">URL de la API de phpList</small>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="phplistApiKey" class="form-label fw-semibold">API Key</label>
                                        <input type="password" class="form-control font-monospace" id="phplistApiKey" name="phplist_api_key" value="{{ $settings['phplist']['api_key'] }}">
                                        <small class="text-muted">API Key de phpList</small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="phplistDefaultList" class="form-label fw-semibold">Lista Por defecto (ID)</label>
                                    <input type="number" class="form-control" id="phplistDefaultList" name="phplist_default_list" value="{{ $settings['phplist']['default_list'] }}" placeholder="1">
                                    <small class="text-muted">ID de la lista predeterminada para nuevas suscripciones</small>
                                </div>

                                <div class="row g-2">
                                    <div class="col-12 col-md-4">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-save me-2"></i>Guardar
                                        </button>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <button type="button" class="btn btn-outline-primary w-100" id="testPhplistBtn">
                                            <i class="fas fa-plug me-2"></i>Probar conexión
                                        </button>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <button type="button" class="btn btn-outline-info w-100" id="loadPhplistListsBtn">
                                            <i class="fas fa-list me-2"></i>Cargar listas
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <!-- phpList Lists -->
                            <div id="phplistListsContainer" class="mt-4" style="display: none;">
                                <hr>
                                <h6 class="mb-3">Listas disponibles en phpList</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="phplistListsTable">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nombre</th>
                                                <th>Descripción</th>
                                                <th>Suscriptores</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Connection Status -->
                            <div id="phplistStatus" class="mt-3" style="display: none;"></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal Agregar Conexión IMAP -->
    <div class="modal fade" id="addImapConnectionModal" tabindex="-1" aria-labelledby="addImapConnectionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light border-bottom">
                    <h5 class="modal-title fw-bold" id="addImapConnectionModalLabel">
                        Agregar conexión IMAP
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('settings.incoming-email.imap.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="imapName" class="form-label fw-semibold">
                                    Nombre de la conexión <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="imapName" name="name" required placeholder="Ej: Soporte Alsernet">
                            </div>
                            <div class="col-md-8 mb-3">
                                <label for="imapHost" class="form-label fw-semibold">
                                    Servidor IMAP <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="imapHost" name="host" required placeholder="imap.gmail.com">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="imapPort" class="form-label fw-semibold">
                                    Puerto <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="imapPort" name="port" required value="993">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="imapUsername" class="form-label fw-semibold">
                                    Usuario <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="imapUsername" name="username" required placeholder="soporte@Alsernet.com">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="imapPassword" class="form-label fw-semibold">
                                    Contraseña <span class="text-danger">*</span>
                                </label>
                                <input type="password" class="form-control" id="imapPassword" name="password" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="imapFolder" class="form-label fw-semibold">Carpeta</label>
                                <input type="text" class="form-control" id="imapFolder" name="folder" value="INBOX">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="imapEncryption" class="form-label fw-semibold">Encriptación</label>
                                <select class="form-select" id="imapEncryption" name="encryption">
                                    <option value="tls" selected>TLS</option>
                                    <option value="ssl">SSL</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="createTickets" name="create_tickets" value="1" checked>
                                    <label class="form-check-label" for="createTickets">
                                        Crear tickets desde correos nuevos
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="createReplies" name="create_replies" value="1" checked>
                                    <label class="form-check-label" for="createReplies">
                                        Crear respuestas desde correos de seguimiento
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-top">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Guardar conexión
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@push('scripts')
<script>
// Delete IMAP Connection
function deleteImapConnection(connectionId) {
    if (confirm('¿Está seguro de que desea eliminar esta conexión IMAP?')) {
        fetch(`{{ url('manager/backups/email/incoming/imap') }}/${connectionId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success || data.message) {
                toastr.success('Conexión IMAP eliminada correctamente', 'Éxito', {
                    positionClass: 'toast-bottom-right'
                });
                setTimeout(() => window.location.reload(), 1500);
            }
        })
        .catch(error => {
            toastr.error('Error al eliminar la conexión: ' + error.message, 'Error', {
                positionClass: 'toast-bottom-right'
            });
        });
    }
}

// Delete Gmail Connection
function deleteGmailConnection(connectionId) {
    if (confirm('¿Está seguro de que desea eliminar esta cuenta de Gmail?')) {
        fetch(`{{ url('manager/backups/email/incoming/gmail') }}/${connectionId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success || data.message) {
                toastr.success('Cuenta Gmail eliminada correctamente', 'Éxito', {
                    positionClass: 'toast-bottom-right'
                });
                setTimeout(() => window.location.reload(), 1500);
            }
        })
        .catch(error => {
            toastr.error('Error al eliminar la cuenta Gmail: ' + error.message, 'Error', {
                positionClass: 'toast-bottom-right'
            });
        });
    }
}

// Copy to Clipboard Helper
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    if (!element) return;

    const value = element.value || element.textContent;

    navigator.clipboard.writeText(value).then(() => {
        toastr.success('Copiado al portapapeles', 'Éxito', {
            positionClass: 'toast-bottom-right',
            timeOut: 2000
        });
    }).catch(err => {
        toastr.error('Error al copiar: ' + err, 'Error', {
            positionClass: 'toast-bottom-right'
        });
    });
}

// Generate API Key
document.addEventListener('DOMContentLoaded', function() {
    const generateApiKeyBtn = document.getElementById('generateApiKeyBtn');

    if (generateApiKeyBtn) {
        generateApiKeyBtn.addEventListener('click', function() {
            if (!confirm('¿Está seguro? Esto invalidará la API Key actual y todas las integraciones existentes dejarán de funcionar.')) {
                return;
            }

            const btn = this;
            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Generando...';

            fetch('{{ route("settings.incoming-email.api.generate-key") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('apiKey').value = data.api_key;
                    toastr.success(data.message, 'API Key generada', {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error(data.message, 'Error', {
                        positionClass: 'toast-bottom-right'
                    });
                }
            })
            .catch(error => {
                toastr.error('Error: ' + error.message, 'Error', {
                    positionClass: 'toast-bottom-right'
                });
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            });
        });
    }

    // phpList Test Connection
    const testPhplistBtn = document.getElementById('testPhplistBtn');
    if (testPhplistBtn) {
        testPhplistBtn.addEventListener('click', function() {
            const apiUrl = document.getElementById('phplistApiUrl').value;
            const apiKey = document.getElementById('phplistApiKey').value;

            if (!apiUrl || !apiKey) {
                toastr.warning('Por favor ingrese API URL y API Key', 'Campos requeridos', {
                    positionClass: 'toast-bottom-right'
                });
                return;
            }

            const btn = this;
            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Probando...';

            fetch('{{ route("settings.incoming-email.phplist.test") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    api_url: apiUrl,
                    api_key: apiKey
                })
            })
            .then(response => response.json())
            .then(data => {
                const statusDiv = document.getElementById('phplistStatus');
                statusDiv.style.display = 'block';

                if (data.success) {
                    statusDiv.innerHTML = `
                        <div class="alert alert-success">
                            <i class="fa fa-check-circle"></i> ${data.message}
                        </div>
                    `;
                } else {
                    statusDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fa fa-exclamation-circle"></i> ${data.message}
                        </div>
                    `;
                }
            })
            .catch(error => {
                const statusDiv = document.getElementById('phplistStatus');
                statusDiv.style.display = 'block';
                statusDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-circle"></i> Error: ${error.message}
                    </div>
                `;
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            });
        });
    }

    // phpList Load Lists
    const loadListsBtn = document.getElementById('loadPhplistListsBtn');
    if (loadListsBtn) {
        loadListsBtn.addEventListener('click', function() {
            const btn = this;
            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Cargando...';

            fetch('{{ route("settings.incoming-email.phplist.lists") }}', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.lists) {
                    const container = document.getElementById('phplistListsContainer');
                    const tbody = document.querySelector('#phplistListsTable tbody');

                    tbody.innerHTML = '';
                    data.lists.forEach(list => {
                        const row = `
                            <tr>
                                <td>${list.id}</td>
                                <td><strong>${list.name}</strong></td>
                                <td>${list.description || 'N/A'}</td>
                                <td>${list.subscribers || 0}</td>
                            </tr>
                        `;
                        tbody.innerHTML += row;
                    });

                    container.style.display = 'block';
                } else {
                    toastr.error(data.message || 'Respuesta inválida', 'Error al cargar listas', {
                        positionClass: 'toast-bottom-right'
                    });
                }
            })
            .catch(error => {
                toastr.error('Error: ' + error.message, 'Error', {
                    positionClass: 'toast-bottom-right'
                });
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            });
        });
    }
});
</script>
@endpush

@endsection
