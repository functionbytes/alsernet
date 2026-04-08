@extends('layouts.theme')

@section('title', 'Correo entrante')

@push('css')
<style>
/* Bootstrap 3/5 class conflict fix: .collapse.show gets visibility:collapse from theme CSS */
.collapse.show { visibility: visible !important; }
</style>
@endpush

@section('content')

    @include('core::components.card', ['title' => 'Correo entrante'])

    @include('core::components.alerts')

    <div class="row g-4 align-items-start">

        {{-- Columna izquierda: handlers --}}
        <div class="col-lg-8">

            {{-- Header --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Manejadores de correo entrante</h5>
                            <p class="small mb-0 text-muted">Configure los canales para recibir correos y convertirlos en tickets</p>
                        </div>
                        <a href="{{ route('settings.email.index') }}" class="btn btn-outline-secondary btn-sm">
                            Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info border-0 mb-0">
                        <div class="d-flex align-items-start gap-2">
                            <i class="fas fa-circle-info fs-5 mt-1"></i>
                            <p class="mb-0 small">Puede habilitar múltiples manejadores al mismo tiempo. Cada uno procesará los correos entrantes de forma independiente.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- IMAP --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 fw-bold">IMAP</h6>
                            <small class="text-muted">Conexión directa a cuentas de correo mediante IMAP</small>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#imapCollapse">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                </div>
                <div class="collapse show" id="imapCollapse">
                    <div class="card-body">
                        <button class="btn btn-primary btn-sm mb-3" data-bs-toggle="modal" data-bs-target="#addImapConnectionModal">
                            <i class="fas fa-plus me-1"></i> Agregar conexión
                        </button>

                        @if(isset($settings['imap']['connections']) && count($settings['imap']['connections']) > 0)
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Servidor</th>
                                            <th>Usuario</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($settings['imap']['connections'] as $connection)
                                            <tr>
                                                <td>{{ $connection['name'] }}</td>
                                                <td>{{ $connection['host'] }}:{{ $connection['port'] }}</td>
                                                <td>{{ $connection['username'] }}</td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-outline-secondary" onclick="deleteImapConnection('{{ $connection['id'] }}')">
                                                        Eliminar
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted mb-0">No hay conexiones IMAP configuradas.</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Pipe --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 fw-bold">Pipe</h6>
                            <small class="text-muted">Reenvío de correos desde cPanel u otro panel de hosting</small>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#pipeCollapse">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                </div>
                <div class="collapse" id="pipeCollapse">
                    <form method="POST" action="{{ route('settings.incoming-email.pipe.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="pipeEnabled" name="pipe_enabled" value="1"
                                       {{ ($settings['pipe']['enabled'] ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="pipeEnabled">Habilitar Pipe Handler</label>
                            </div>

                            <div class="mb-3">
                                <label for="pipeEmailAddress" class="form-label fw-semibold">Dirección de email</label>
                                <input type="email" class="form-control" id="pipeEmailAddress" name="pipe_email_address"
                                       value="{{ $settings['pipe']['email_address'] ?? '' }}"
                                       placeholder="soporte@ejemplo.com">
                                <small class="text-muted">Email de destino que reenviará correos al sistema</small>
                            </div>

                            <div class="alert alert-info border-0 mb-0">
                                <strong class="d-block mb-1">Ruta del script:</strong>
                                <code>{{ $settings['pipe']['script_path'] ?? '' }}</code>
                                <p class="mb-0 mt-2 small">Configure esta ruta en su panel de control de email (cPanel, Plesk, etc.)</p>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                Guardar configuración
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- REST API --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 fw-bold">REST API</h6>
                            <small class="text-muted">Recibe correos desde aplicaciones externas vía API</small>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#apiCollapse">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                </div>
                <div class="collapse" id="apiCollapse">
                    <form method="POST" action="{{ route('settings.incoming-email.api.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="apiEnabled" name="api_enabled" value="1"
                                       {{ ($settings['api']['enabled'] ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="apiEnabled">Habilitar REST API Handler</label>
                            </div>

                            <div class="mb-3">
                                <label for="apiKey" class="form-label fw-semibold">API Key</label>
                                <div class="input-group">
                                    <input type="text" class="form-control font-monospace" id="apiKey" name="api_key"
                                           value="{{ $settings['api']['api_key'] ?? '' }}" readonly>
                                    <button type="button" class="btn btn-outline-secondary" onclick="copyToClipboard('apiKey')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="generateApiKeyBtn">
                                        <i class="fas fa-rotate"></i> Regenerar
                                    </button>
                                </div>
                                <small class="text-muted">Esta clave autentica las peticiones a la API</small>
                            </div>

                            <div class="alert alert-info border-0 mb-0">
                                <strong class="d-block mb-1">Endpoint URL:</strong>
                                <code>{{ $settings['api']['api_url'] ?? '' }}</code>
                                <input type="hidden" id="apiUrl" value="{{ $settings['api']['api_url'] ?? '' }}">
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('apiUrl')">
                                        <i class="fas fa-copy me-1"></i> Copiar URL
                                    </button>
                                    <a href="{{ route('settings.incoming-email.api.documentation') }}" class="btn btn-sm btn-outline-secondary ms-1" target="_blank">
                                        Ver documentación
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                Guardar configuración
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Gmail API --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 fw-bold">Gmail API</h6>
                            <small class="text-muted">Conexión directa con cuentas Gmail vía OAuth2</small>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#gmailCollapse">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                </div>
                <div class="collapse" id="gmailCollapse">
                    <form method="POST" action="{{ route('settings.incoming-email.gmail.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="gmailEnabled" name="gmail_enabled" value="1"
                                       {{ ($settings['gmail']['enabled'] ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="gmailEnabled">Habilitar Gmail Handler</label>
                            </div>

                            <div class="alert alert-info border-0 mb-3">
                                <p class="mb-1 small fw-semibold">Redirect URI autorizado (configurar en Google Cloud Console):</p>
                                <code class="small">{{ route('settings.incoming-email.gmail.callback') }}</code>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="gmailClientId" class="form-label fw-semibold">Client ID</label>
                                    <input type="text" class="form-control font-monospace" id="gmailClientId" name="gmail_client_id"
                                           value="{{ $settings['gmail']['client_id'] ?? '' }}"
                                           placeholder="xxx.apps.googleusercontent.com">
                                    <small class="text-muted">Desde Google Cloud Console</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="gmailClientSecret" class="form-label fw-semibold">Client Secret</label>
                                    <input type="password" class="form-control font-monospace" id="gmailClientSecret" name="gmail_client_secret"
                                           value="{{ $settings['gmail']['client_secret'] ?? '' }}">
                                    <small class="text-muted">Se almacena encriptado</small>
                                </div>
                            </div>

                            @if(isset($settings['gmail']['connections']) && count($settings['gmail']['connections']) > 0)
                                <h6 class="fw-semibold mb-2">Cuentas conectadas</h6>
                                <div class="table-responsive mb-3">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Email</th>
                                                <th>Conectado</th>
                                                <th class="text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($settings['gmail']['connections'] as $connection)
                                                <tr>
                                                    <td><i class="fab fa-google text-danger me-1"></i> {{ $connection['email'] }}</td>
                                                    <td><small class="text-muted">{{ $connection['created_at'] }}</small></td>
                                                    <td class="text-center">
                                                        <button class="btn btn-sm btn-outline-secondary" onclick="deleteGmailConnection('{{ $connection['id'] }}')">
                                                            Eliminar
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            <a href="{{ route('settings.incoming-email.gmail.authorize') }}"
                               class="btn btn-outline-secondary btn-sm {{ empty($settings['gmail']['client_id'] ?? '') || empty($settings['gmail']['client_secret'] ?? '') ? 'disabled' : '' }}">
                                <i class="fab fa-google me-1"></i> Conectar cuenta Gmail
                            </a>
                            @if(empty($settings['gmail']['client_id'] ?? '') || empty($settings['gmail']['client_secret'] ?? ''))
                                <small class="text-danger d-block mt-2">Configure primero Client ID y Client Secret</small>
                            @endif
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                Guardar configuración
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Mailgun --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 fw-bold">Mailgun</h6>
                            <small class="text-muted">Recepción de correos mediante webhooks de Mailgun</small>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#mailgunCollapse">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                </div>
                <div class="collapse" id="mailgunCollapse">
                    <form method="POST" action="{{ route('settings.incoming-email.mailgun.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="mailgunEnabled" name="mailgun_enabled" value="1"
                                       {{ ($settings['mailgun']['enabled'] ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="mailgunEnabled">Habilitar Mailgun Handler</label>
                            </div>

                            <div class="mb-3">
                                <label for="mailgunApiKey" class="form-label fw-semibold">Mailgun API Key</label>
                                <input type="password" class="form-control font-monospace" id="mailgunApiKey" name="mailgun_api_key"
                                       value="{{ $settings['mailgun']['api_key'] ?? '' }}"
                                       placeholder="key-xxxxxxxxxxxxxxxxxxxxxxxx">
                                <small class="text-muted">API Key de su cuenta de Mailgun</small>
                            </div>

                            <div class="mb-3">
                                <label for="mailgunDomain" class="form-label fw-semibold">Mailgun Domain</label>
                                <input type="text" class="form-control" id="mailgunDomain" name="mailgun_domain"
                                       value="{{ $settings['mailgun']['domain'] ?? '' }}"
                                       placeholder="mg.ejemplo.com">
                                <small class="text-muted">Dominio configurado en Mailgun</small>
                            </div>

                            <div class="alert alert-info border-0 mb-0">
                                <strong class="d-block mb-1">Webhook URL:</strong>
                                <code>{{ $settings['mailgun']['webhook_url'] ?? '' }}</code>
                                <input type="hidden" id="mailgunWebhookUrl" value="{{ $settings['mailgun']['webhook_url'] ?? '' }}">
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('mailgunWebhookUrl')">
                                        <i class="fas fa-copy me-1"></i> Copiar URL
                                    </button>
                                </div>
                                <p class="mb-0 mt-2 small">Configure esta URL en Mailgun Dashboard → Sending → Webhooks</p>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                Guardar configuración
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- phpList --}}
            <div class="card">
                <div class="card-header p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 fw-bold">phpList</h6>
                            <small class="text-muted">Integración con listas de correo de phpList</small>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#phplistCollapse">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                </div>
                <div class="collapse" id="phplistCollapse">
                    <form method="POST" action="{{ route('settings.incoming-email.phplist.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="phplistEnabled" name="phplist_enabled" value="1"
                                       {{ ($settings['phplist']['enabled'] ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="phplistEnabled">Habilitar phpList Handler</label>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-8">
                                    <label for="phplistApiUrl" class="form-label fw-semibold">API URL</label>
                                    <input type="url" class="form-control" id="phplistApiUrl" name="phplist_api_url"
                                           value="{{ $settings['phplist']['api_url'] ?? '' }}"
                                           placeholder="https://phplist.example.com/api">
                                    <small class="text-muted">URL de la API de phpList</small>
                                </div>
                                <div class="col-md-4">
                                    <label for="phplistApiKey" class="form-label fw-semibold">API Key</label>
                                    <input type="password" class="form-control font-monospace" id="phplistApiKey" name="phplist_api_key"
                                           value="{{ $settings['phplist']['api_key'] ?? '' }}">
                                    <small class="text-muted">API Key de phpList</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="phplistDefaultList" class="form-label fw-semibold">Lista por defecto (ID)</label>
                                <input type="number" class="form-control" id="phplistDefaultList" name="phplist_default_list"
                                       value="{{ $settings['phplist']['default_list'] ?? '' }}" placeholder="1">
                                <small class="text-muted">ID de la lista predeterminada para nuevas suscripciones</small>
                            </div>

                            <div class="d-flex gap-2 mb-3">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="testPhplistBtn">
                                    <i class="fas fa-plug me-1"></i> Probar conexión
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="loadPhplistListsBtn">
                                    <i class="fas fa-list me-1"></i> Cargar listas
                                </button>
                            </div>

                            <div id="phplistListsContainer" class="d-none">
                                <h6 class="fw-semibold mb-2">Listas disponibles</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="phplistListsTable">
                                        <thead class="table-light">
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

                            <div id="phplistStatus" class="d-none"></div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                Guardar configuración
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>

        {{-- Columna derecha: sidebar --}}
        <div class="col-lg-4">

            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">¿Cómo funciona?</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0">Cada manejador convierte correos electrónicos entrantes en tickets o respuestas dentro del sistema. Puedes activar varios al mismo tiempo.</p>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Manejadores disponibles</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="fw-semibold mb-1">IMAP</h6>
                        <p class="text-muted mb-0">Conecta cuentas de correo existentes (Gmail, Outlook, etc.) directamente al sistema usando protocolo IMAP.</p>
                    </div>
                    <hr class="my-3">
                    <div class="mb-3">
                        <h6 class="fw-semibold mb-1">Pipe</h6>
                        <p class="text-muted mb-0">Ideal para hosting compartido con cPanel o Plesk. El correo se redirige directamente al sistema mediante un script.</p>
                    </div>
                    <hr class="my-3">
                    <div class="mb-3">
                        <h6 class="fw-semibold mb-1">REST API</h6>
                        <p class="text-muted mb-0">Permite que aplicaciones externas envíen correos al sistema mediante peticiones HTTP autenticadas con API Key.</p>
                    </div>
                    <hr class="my-3">
                    <div class="mb-3">
                        <h6 class="fw-semibold mb-1">Gmail API</h6>
                        <p class="text-muted mb-0">Conecta cuentas de Gmail usando OAuth2. Requiere credenciales de Google Cloud Console.</p>
                    </div>
                    <hr class="my-3">
                    <div class="mb-3">
                        <h6 class="fw-semibold mb-1">Mailgun</h6>
                        <p class="text-muted mb-0">Recibe correos enviados a través de Mailgun mediante webhooks. Útil para dominios propios gestionados en Mailgun.</p>
                    </div>
                    <hr class="my-3">
                    <div>
                        <h6 class="fw-semibold mb-1">phpList</h6>
                        <p class="text-muted mb-0">Integra el sistema con una instalación de phpList para gestionar listas de correo y suscripciones.</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Recomendaciones</h5>
                </div>
                <div class="card-body">
                    <ul class="text-muted  mb-0">
                        <li class="mb-2">Usa <strong>IMAP</strong> si ya tienes una cuenta de correo configurada.</li>
                        <li class="mb-2">Usa <strong>Pipe</strong> si tu hosting es cPanel o Plesk.</li>
                        <li class="mb-2">Usa <strong>REST API</strong> para integrar formularios web o aplicaciones externas.</li>
                        <li class="mb-2">Usa <strong>Gmail API</strong> para cuentas de Google Workspace.</li>
                        <li>Puedes tener múltiples manejadores activos simultáneamente.</li>
                    </ul>
                </div>
            </div>

        </div>

    </div>

    {{-- Modal Agregar Conexión IMAP --}}
    <div class="modal fade" id="addImapConnectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Agregar conexión IMAP</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('settings.incoming-email.imap.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="imapName" class="form-label fw-semibold">Nombre de la conexión <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="imapName" name="name" required placeholder="Ej: Soporte principal">
                            </div>
                            <div class="col-md-8">
                                <label for="imapHost" class="form-label fw-semibold">Servidor IMAP <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="imapHost" name="host" required placeholder="imap.gmail.com">
                            </div>
                            <div class="col-md-4">
                                <label for="imapPort" class="form-label fw-semibold">Puerto <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="imapPort" name="port" required value="993">
                            </div>
                            <div class="col-md-6">
                                <label for="imapUsername" class="form-label fw-semibold">Usuario <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="imapUsername" name="username" required placeholder="soporte@ejemplo.com">
                            </div>
                            <div class="col-md-6">
                                <label for="imapPassword" class="form-label fw-semibold">Contraseña <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="imapPassword" name="password" required>
                            </div>
                            <div class="col-md-6">
                                <label for="imapFolder" class="form-label fw-semibold">Carpeta</label>
                                <input type="text" class="form-control" id="imapFolder" name="folder" value="INBOX">
                            </div>
                            <div class="col-md-6">
                                <label for="imapEncryption" class="form-label fw-semibold">Encriptación</label>
                                <select class="form-select" id="imapEncryption" name="encryption">
                                    <option value="tls" selected>TLS</option>
                                    <option value="ssl">SSL</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="createTickets" name="create_tickets" value="1" checked>
                                    <label class="form-check-label" for="createTickets">Crear tickets desde correos nuevos</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="createReplies" name="create_replies" value="1" checked>
                                    <label class="form-check-label" for="createReplies">Crear respuestas desde correos de seguimiento</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            Guardar conexión
                        </button>
                        <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
function copyToClipboard(elementId) {
    const el = document.getElementById(elementId);
    if (!el) return;
    navigator.clipboard.writeText(el.value || el.textContent).then(() => {
        toastr.success('Copiado al portapapeles');
    });
}

function deleteImapConnection(connectionId) {
    if (!confirm('¿Eliminar esta conexión IMAP?')) return;
    fetch(`{{ url('manager/backups/email/incoming/imap') }}/${connectionId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Content-Type': 'application/json' }
    }).then(r => r.json()).then(data => {
        if (data.success || data.message) { toastr.success('Conexión IMAP eliminada'); setTimeout(() => location.reload(), 1200); }
    }).catch(() => toastr.error('Error al eliminar la conexión'));
}

function deleteGmailConnection(connectionId) {
    if (!confirm('¿Eliminar esta cuenta de Gmail?')) return;
    fetch(`{{ url('manager/backups/email/incoming/gmail') }}/${connectionId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Content-Type': 'application/json' }
    }).then(r => r.json()).then(data => {
        if (data.success || data.message) { toastr.success('Cuenta Gmail eliminada'); setTimeout(() => location.reload(), 1200); }
    }).catch(() => toastr.error('Error al eliminar la cuenta'));
}

document.addEventListener('DOMContentLoaded', function () {
    const generateApiKeyBtn = document.getElementById('generateApiKeyBtn');
    if (generateApiKeyBtn) {
        generateApiKeyBtn.addEventListener('click', function () {
            if (!confirm('¿Regenerar la API Key? Las integraciones existentes dejarán de funcionar.')) return;
            const btn = this;
            const original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch('{{ route("settings.incoming-email.api.generate-key") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Content-Type': 'application/json' }
            }).then(r => r.json()).then(data => {
                if (data.success) { document.getElementById('apiKey').value = data.api_key; toastr.success('Nueva API Key generada'); }
                else { toastr.error(data.message); }
            }).finally(() => { btn.disabled = false; btn.innerHTML = original; });
        });
    }

    const testPhplistBtn = document.getElementById('testPhplistBtn');
    if (testPhplistBtn) {
        testPhplistBtn.addEventListener('click', function () {
            const apiUrl = document.getElementById('phplistApiUrl').value;
            const apiKey = document.getElementById('phplistApiKey').value;
            if (!apiUrl || !apiKey) { toastr.warning('Ingrese API URL y API Key'); return; }

            const btn = this;
            const original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Probando...';

            fetch('{{ route("settings.incoming-email.phplist.test") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Content-Type': 'application/json' },
                body: JSON.stringify({ api_url: apiUrl, api_key: apiKey })
            }).then(r => r.json()).then(data => {
                const statusDiv = document.getElementById('phplistStatus');
                statusDiv.classList.remove('d-none');
                statusDiv.innerHTML = data.success
                    ? `<div class="alert alert-success border-0 mb-0"><i class="fas fa-check me-1"></i>${data.message}</div>`
                    : `<div class="alert alert-danger border-0 mb-0"><i class="fas fa-times me-1"></i>${data.message}</div>`;
            }).finally(() => { btn.disabled = false; btn.innerHTML = original; });
        });
    }

    const loadListsBtn = document.getElementById('loadPhplistListsBtn');
    if (loadListsBtn) {
        loadListsBtn.addEventListener('click', function () {
            const btn = this;
            const original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Cargando...';

            fetch('{{ route("settings.incoming-email.phplist.lists") }}', {
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Content-Type': 'application/json' }
            }).then(r => r.json()).then(data => {
                if (data.success && data.lists) {
                    const tbody = document.querySelector('#phplistListsTable tbody');
                    tbody.innerHTML = data.lists.map(l =>
                        `<tr><td>${l.id}</td><td><strong>${l.name}</strong></td><td>${l.description || '-'}</td><td>${l.subscribers || 0}</td></tr>`
                    ).join('');
                    document.getElementById('phplistListsContainer').classList.remove('d-none');
                } else {
                    toastr.error(data.message || 'No se pudieron cargar las listas');
                }
            }).finally(() => { btn.disabled = false; btn.innerHTML = original; });
        });
    }
});
</script>
@endpush
