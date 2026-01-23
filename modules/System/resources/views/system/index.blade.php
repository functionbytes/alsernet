@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Configuración del sistema'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- System Settings Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light  text-light">
                <div>
                    <h5 class="mb-1 fw-bold">Configuración del sistema</h5>
                    <p class="small mb-0 text-black">Configura las opciones avanzadas del sistema incluyendo colas de
                        trabajo y websockets para funcionalidades en tiempo real.</p>
                </div>
            </div>

            <!-- Navigation Pills -->
            <ul class="nav nav-pills user-profile-tab" id="system-settings-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $activeTab === 'queue' ? 'active' : '' }}"
                            id="queue-tab"
                            data-bs-toggle="pill"
                            data-bs-target="#queue"
                            type="button"
                            role="tab"
                            aria-controls="queue"
                            aria-selected="{{ $activeTab === 'queue' ? 'true' : 'false' }}">
                        <i class="fa fa-list-check me-2"></i>
                        <span class="d-none d-md-block">Colas</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $activeTab === 'websockets' ? 'active' : '' }}"
                            id="websockets-tab"
                            data-bs-toggle="pill"
                            data-bs-target="#websockets"
                            type="button"
                            role="tab"
                            aria-controls="websockets"
                            aria-selected="{{ $activeTab === 'websockets' ? 'true' : 'false' }}">
                        <i class="fa fa-wifi me-2"></i>
                        <span class="d-none d-md-block">Websockets</span>
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="card-body">
                <div class="tab-content" id="system-settings-content">

                    <!-- Queue Tab -->
                    <div role="tabpanel" class="tab-pane fade {{ $activeTab === 'queue' ? 'active show' : '' }}"
                         id="queue">
                        <div class="row g-4">
                            <!-- Queue Configuration -->
                            <div class="col-12">
                                <div class="mb-4">
                                    <h6 class="mb-2 fw-bold">Configuración de colas</h6>
                                    <p class="text-muted small">Las colas permiten procesar tareas en segundo plano,
                                        mejorando el rendimiento de la aplicación.</p>
                                </div>

                                <form method="POST" action="{{ route('settings.system.queue.update') }}"
                                      id="queueForm">
                                    @csrf
                                    @method('PUT')

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h6 class="mb-3 fw-semibold">Conexión de cola</h6>

                                                    <div class="mb-3">
                                                        <label for="queueConnection" class="form-label fw-semibold">Driver
                                                            de cola</label>
                                                        <select class="form-select" id="queueConnection"
                                                                name="default_connection" required>
                                                            <option value="sync" {{ $queueSettings['default_connection'] === 'sync' ? 'selected' : '' }}>
                                                                Sync (Sincrona)
                                                            </option>
                                                            <option value="database" {{ $queueSettings['default_connection'] === 'database' ? 'selected' : '' }}>
                                                                Database
                                                            </option>
                                                            <option value="redis" {{ $queueSettings['default_connection'] === 'redis' ? 'selected' : '' }}>
                                                                Redis
                                                            </option>
                                                            <option value="sqs" {{ $queueSettings['default_connection'] === 'sqs' ? 'selected' : '' }}>
                                                                Amazon SQS
                                                            </option>
                                                        </select>
                                                        <small class="text-muted">Driver predeterminado para procesar
                                                            trabajos en cola</small>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Driver de trabajos
                                                            fallidos</label>
                                                        <p class="mb-0">
                                                            <code>{{ $queueSettings['failed_driver'] }}</code></p>
                                                        <small class="text-muted">Donde se almacenan los trabajos
                                                            fallidos</small>
                                                    </div>

                                                    <div class="d-flex gap-2">
                                                        <button type="submit" class="btn btn-primary w-100">Guardar
                                                            cambios
                                                        </button>
                                                        <button type="button" class="btn btn-outline-primary w-100"
                                                                id="testQueueBtn">Probar conexión
                                                        </button>
                                                        <button type="button" class="btn btn-outline-primary w-100"
                                                                id="restartQueueBtn">Reiniciar workers
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h6 class="mb-3 fw-semibold">Información de colas</h6>

                                                    <div class="mb-3">
                                                        <small class="text-muted d-block">Conexión actual</small>
                                                        <p class="mb-0 fw-500">{{ $queueSettings['default_connection'] }}</p>
                                                    </div>

                                                    <div class="mb-3">
                                                        <small class="text-muted d-block">Conexiones disponibles</small>
                                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                                            @foreach($queueSettings['connections'] as $name => $config)
                                                                <span class="badge bg-light-secondary text-light">{{ $name }}</span>
                                                            @endforeach
                                                        </div>
                                                    </div>

                                                    <div class="alert alert-info border-0 bg-info-subtle text-info mb-0">
                                                        <div class="d-flex align-items-start gap-2">
                                                            <i class="fa fa-circle-info fs-5"></i>
                                                            <div>
                                                                <strong>Nota:</strong> Después de cambiar la conexión de
                                                                cola, asegúrate de reiniciar los workers para aplicar
                                                                los cambios.
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- WebSockets Tab -->
                    <div role="tabpanel" class="tab-pane fade {{ $activeTab === 'websockets' ? 'active show' : '' }}"
                         id="websockets">
                        <div class="row g-4">
                            <!-- WebSockets Configuration -->
                            <div class="col-12">
                                <div class="mb-4">
                                    <h6 class="mb-2 fw-bold">Configuración de websockets</h6>
                                    <p class="text-muted small">Los WebSockets permiten comunicación en tiempo real
                                        entre el servidor y los clientes conectados.</p>
                                </div>

                                <form method="POST" action="{{ route('settings.system.websockets.update') }}"
                                      id="websocketsForm">
                                    @csrf
                                    @method('PUT')

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h6 class="mb-3 fw-semibold">Driver de broadcasting</h6>

                                                    <div class="mb-3">
                                                        <label for="broadcastDriver" class="form-label fw-semibold">Driver</label>
                                                        <select class="form-select" id="broadcastDriver"
                                                                name="broadcast_driver" required>
                                                            <option value="reverb" {{ $websocketsSettings['driver'] === 'reverb' ? 'selected' : '' }}>
                                                                Laravel Reverb
                                                            </option>
                                                            <option value="pusher" {{ $websocketsSettings['driver'] === 'pusher' ? 'selected' : '' }}>
                                                                Pusher
                                                            </option>
                                                            <option value="redis" {{ $websocketsSettings['driver'] === 'redis' ? 'selected' : '' }}>
                                                                Redis
                                                            </option>
                                                            <option value="log" {{ $websocketsSettings['driver'] === 'log' ? 'selected' : '' }}>
                                                                Log (Desarrollo)
                                                            </option>
                                                            <option value="null" {{ $websocketsSettings['driver'] === 'null' ? 'selected' : '' }}>
                                                                Deshabilitado
                                                            </option>
                                                        </select>
                                                        <small class="text-muted">Driver para transmitir eventos en
                                                            tiempo real</small>
                                                    </div>

                                                    <!-- Reverb Settings -->
                                                    <div id="reverbSettings"
                                                         style="display: {{ $websocketsSettings['driver'] === 'reverb' ? 'block' : 'none' }};">
                                                        <div class="mb-3">
                                                            <label for="reverbHost"
                                                                   class="form-label fw-semibold">Host</label>
                                                            <input type="text" class="form-control" id="reverbHost"
                                                                   name="reverb_host"
                                                                   value="{{ $websocketsSettings['reverb_host'] }}"
                                                                   placeholder="0.0.0.0">
                                                            <small class="text-muted">Dirección IP del servidor
                                                                Reverb</small>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="reverbPort" class="form-label fw-semibold">Puerto</label>
                                                            <input type="number" class="form-control" id="reverbPort"
                                                                   name="reverb_port"
                                                                   value="{{ $websocketsSettings['reverb_port'] }}"
                                                                   placeholder="8080">
                                                            <small class="text-muted">Puerto del servidor Reverb</small>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="reverbScheme" class="form-label fw-semibold">Esquema</label>
                                                            <select class="form-select" id="reverbScheme"
                                                                    name="reverb_scheme">
                                                                <option value="http" {{ $websocketsSettings['reverb_scheme'] === 'http' ? 'selected' : '' }}>
                                                                    HTTP
                                                                </option>
                                                                <option value="https" {{ $websocketsSettings['reverb_scheme'] === 'https' ? 'selected' : '' }}>
                                                                    HTTPS
                                                                </option>
                                                            </select>
                                                            <small class="text-muted">Protocolo de conexión</small>
                                                        </div>
                                                    </div>

                                                    <!-- Pusher Settings -->
                                                    <div id="pusherSettings"
                                                         style="display: {{ $websocketsSettings['driver'] === 'pusher' ? 'block' : 'none' }};">
                                                        <div class="mb-3">
                                                            <label for="pusherAppId" class="form-label fw-semibold">App
                                                                ID <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" id="pusherAppId"
                                                                   name="pusher_app_id"
                                                                   value="{{ $websocketsSettings['pusher_app_id'] }}"
                                                                   placeholder="123456">
                                                            <small class="text-muted">ID de la aplicación Pusher</small>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="pusherKey" class="form-label fw-semibold">Key
                                                                <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" id="pusherKey"
                                                                   name="pusher_key"
                                                                   value="{{ $websocketsSettings['pusher_key'] }}"
                                                                   placeholder="xxxxxxxxxxxxxxxx">
                                                            <small class="text-muted">Clave pública de la
                                                                aplicación</small>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="pusherSecret" class="form-label fw-semibold">Secret
                                                                <span class="text-danger">*</span></label>
                                                            <input type="password" class="form-control"
                                                                   id="pusherSecret" name="pusher_secret"
                                                                   value="{{ $websocketsSettings['pusher_secret'] }}"
                                                                   placeholder="••••••••••••••••">
                                                            <small class="text-muted">Clave secreta de la
                                                                aplicación</small>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="pusherCluster" class="form-label fw-semibold">Cluster
                                                                <span class="text-danger">*</span></label>
                                                            <select class="form-select" id="pusherCluster"
                                                                    name="pusher_cluster">
                                                                <option value="mt1" {{ $websocketsSettings['pusher_cluster'] === 'mt1' ? 'selected' : '' }}>
                                                                    mt1 (US East)
                                                                </option>
                                                                <option value="us2" {{ $websocketsSettings['pusher_cluster'] === 'us2' ? 'selected' : '' }}>
                                                                    us2 (US East)
                                                                </option>
                                                                <option value="us3" {{ $websocketsSettings['pusher_cluster'] === 'us3' ? 'selected' : '' }}>
                                                                    us3 (US West)
                                                                </option>
                                                                <option value="eu" {{ $websocketsSettings['pusher_cluster'] === 'eu' ? 'selected' : '' }}>
                                                                    eu (Europe)
                                                                </option>
                                                                <option value="ap1" {{ $websocketsSettings['pusher_cluster'] === 'ap1' ? 'selected' : '' }}>
                                                                    ap1 (Asia Pacific)
                                                                </option>
                                                                <option value="ap2" {{ $websocketsSettings['pusher_cluster'] === 'ap2' ? 'selected' : '' }}>
                                                                    ap2 (Asia Pacific)
                                                                </option>
                                                                <option value="ap3" {{ $websocketsSettings['pusher_cluster'] === 'ap3' ? 'selected' : '' }}>
                                                                    ap3 (Asia Pacific)
                                                                </option>
                                                                <option value="ap4" {{ $websocketsSettings['pusher_cluster'] === 'ap4' ? 'selected' : '' }}>
                                                                    ap4 (Asia Pacific)
                                                                </option>
                                                            </select>
                                                            <small class="text-muted">Región del servidor Pusher</small>
                                                        </div>
                                                    </div>

                                                    <!-- Redis Settings -->
                                                    <div id="redisSettings"
                                                         style="display: {{ $websocketsSettings['driver'] === 'redis' ? 'block' : 'none' }};">
                                                        <div class="mb-3">
                                                            <label for="redisHost" class="form-label fw-semibold">Host
                                                                <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" id="redisHost"
                                                                   name="redis_host"
                                                                   value="{{ $websocketsSettings['redis_host'] }}"
                                                                   placeholder="127.0.0.1">
                                                            <small class="text-muted">Dirección del servidor
                                                                Redis</small>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="redisPort" class="form-label fw-semibold">Puerto
                                                                <span class="text-danger">*</span></label>
                                                            <input type="number" class="form-control" id="redisPort"
                                                                   name="redis_port"
                                                                   value="{{ $websocketsSettings['redis_port'] }}"
                                                                   placeholder="6379">
                                                            <small class="text-muted">Puerto del servidor Redis</small>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="redisPassword" class="form-label fw-semibold">Contraseña</label>
                                                            <input type="password" class="form-control"
                                                                   id="redisPassword" name="redis_password"
                                                                   value="{{ $websocketsSettings['redis_password'] }}"
                                                                   placeholder="••••••••">
                                                            <small class="text-muted">Contraseña de Redis
                                                                (opcional)</small>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="redisDatabase" class="form-label fw-semibold">Base
                                                                de datos</label>
                                                            <input type="number" class="form-control" id="redisDatabase"
                                                                   name="redis_database"
                                                                   value="{{ $websocketsSettings['redis_database'] }}"
                                                                   placeholder="0" min="0" max="15">
                                                            <small class="text-muted">Número de base de datos Redis
                                                                (0-15)</small>
                                                        </div>
                                                    </div>

                                                    <button type="submit" class="btn btn-primary w-100">Guardar
                                                        cambios
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h6 class="mb-3 fw-semibold">Información de websockets</h6>

                                                    <div class="mb-3">
                                                        <small class="text-muted d-block">Driver actual</small>
                                                        <p class="mb-0 fw-500">{{ $websocketsSettings['driver'] }}</p>
                                                    </div>

                                                    @if($websocketsSettings['driver'] === 'reverb')
                                                        <div class="mb-3">
                                                            <small class="text-muted d-block">URL del servidor</small>
                                                            <code class="d-block mt-1">{{ $websocketsSettings['reverb_scheme'] }}
                                                                ://{{ $websocketsSettings['reverb_host'] }}
                                                                :{{ $websocketsSettings['reverb_port'] }}</code>
                                                        </div>
                                                    @endif

                                                    @if($websocketsSettings['driver'] === 'pusher')
                                                        <div class="mb-3">
                                                            <small class="text-muted d-block">App ID</small>
                                                            <code class="d-block mt-1">{{ $websocketsSettings['pusher_app_id'] ?: 'No configurado' }}</code>
                                                        </div>
                                                        <div class="mb-3">
                                                            <small class="text-muted d-block">Cluster</small>
                                                            <code class="d-block mt-1">{{ $websocketsSettings['pusher_cluster'] }}</code>
                                                        </div>
                                                    @endif

                                                    @if($websocketsSettings['driver'] === 'redis')
                                                        <div class="mb-3">
                                                            <small class="text-muted d-block">Servidor Redis</small>
                                                            <code class="d-block mt-1">{{ $websocketsSettings['redis_host'] }}
                                                                :{{ $websocketsSettings['redis_port'] }}</code>
                                                        </div>
                                                        <div class="mb-3">
                                                            <small class="text-muted d-block">Base de datos</small>
                                                            <code class="d-block mt-1">{{ $websocketsSettings['redis_database'] }}</code>
                                                        </div>
                                                    @endif

                                                    <div class="mb-3">
                                                        <small class="text-muted d-block">Conexiones disponibles</small>
                                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                                            @foreach($websocketsSettings['connections'] as $name => $config)
                                                                <span class="badge bg-light-secondary text-light">{{ $name }}</span>
                                                            @endforeach
                                                        </div>
                                                    </div>

                                                    @if($websocketsSettings['driver'] === 'reverb')
                                                        <div class="alert alert-warning border-0 bg-warning-subtle text-warning mb-0">
                                                            <div class="d-flex align-items-start gap-2">
                                                                <i class="fa fa-triangle-exclamation fs-5"></i>
                                                                <div>
                                                                    <strong>Importante:</strong> Asegúrate de que el
                                                                    servidor esté ejecutándose con <code>php artisan
                                                                        reverb:start</code>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    @if($websocketsSettings['driver'] === 'pusher')
                                                        <div class="alert alert-info border-0 bg-info-subtle text-info mb-0">
                                                            <div class="d-flex align-items-start gap-2">
                                                                <i class="fa fa-circle-info fs-5"></i>
                                                                <div>
                                                                    <strong>Nota:</strong> Pusher es un servicio de
                                                                    terceros. Obtén tus credenciales en <a
                                                                            href="https://pusher.com" target="_blank">pusher.com</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    @if($websocketsSettings['driver'] === 'redis')
                                                        <div class="alert alert-info border-0 bg-info-subtle text-info mb-0">
                                                            <div class="d-flex align-items-start gap-2">
                                                                <i class="fa fa-circle-info fs-5"></i>
                                                                <div>
                                                                    <strong>Nota:</strong> Asegúrate de que el servidor
                                                                    Redis esté ejecutándose y sea accesible
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Toastr configuration
                toastr.options = {
                    closeButton: true,
                    progressBar: true,
                    positionClass: "toast-bottom-right"
                };

                // Test Queue Connection
                const testQueueBtn = document.getElementById('testQueueBtn');

                if (testQueueBtn) {
                    testQueueBtn.addEventListener('click', function () {
                        const btn = this;
                        const originalContent = btn.innerHTML;
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Probando...';

                        const connection = document.getElementById('queueConnection').value;

                        fetch('{{ route("settings.system.queue.test") }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({connection: connection})
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    toastr.success(data.message, 'Conexión exitosa');
                                } else {
                                    toastr.error(data.message, 'Error en la conexión');
                                }
                            })
                            .catch(error => {
                                toastr.error('Error en la solicitud: ' + error.message, 'Error');
                            })
                            .finally(() => {
                                btn.disabled = false;
                                btn.innerHTML = originalContent;
                            });
                    });
                }

                // Restart Queue Workers
                const restartQueueBtn = document.getElementById('restartQueueBtn');

                if (restartQueueBtn) {
                    restartQueueBtn.addEventListener('click', function () {
                        if (!confirm('¿Estás seguro de que deseas reiniciar los workers de cola? Esto puede interrumpir trabajos en progreso.')) {
                            return;
                        }

                        const btn = this;
                        const originalContent = btn.innerHTML;
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Reiniciando...';

                        fetch('{{ route("settings.system.queue.restart") }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Content-Type': 'application/json'
                            }
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    toastr.success(data.message, 'Workers reiniciados');
                                } else {
                                    toastr.error(data.message, 'Error al reiniciar');
                                }
                            })
                            .catch(error => {
                                toastr.error('Error en la solicitud: ' + error.message, 'Error');
                            })
                            .finally(() => {
                                btn.disabled = false;
                                btn.innerHTML = originalContent;
                            });
                    });
                }

                // Show/Hide Driver Settings
                const broadcastDriver = document.getElementById('broadcastDriver');
                const reverbSettings = document.getElementById('reverbSettings');
                const pusherSettings = document.getElementById('pusherSettings');
                const redisSettings = document.getElementById('redisSettings');

                if (broadcastDriver) {
                    broadcastDriver.addEventListener('change', function () {
                        const selectedDriver = this.value;

                        // Hide all backups panels
                        if (reverbSettings) reverbSettings.style.display = 'none';
                        if (pusherSettings) pusherSettings.style.display = 'none';
                        if (redisSettings) redisSettings.style.display = 'none';

                        // Show selected driver backups
                        if (selectedDriver === 'reverb' && reverbSettings) {
                            reverbSettings.style.display = 'block';
                        } else if (selectedDriver === 'pusher' && pusherSettings) {
                            pusherSettings.style.display = 'block';
                        } else if (selectedDriver === 'redis' && redisSettings) {
                            redisSettings.style.display = 'block';
                        }
                    });
                }

                // Update URL with active tab
                const tabs = document.querySelectorAll('button[data-bs-toggle="pill"]');
                tabs.forEach(tab => {
                    tab.addEventListener('shown.bs.tab', function (event) {
                        const tabName = event.target.getAttribute('aria-controls');
                        const url = new URL(window.location);
                        url.searchParams.set('tab', tabName);
                        window.history.pushState({}, '', url);
                    });
                });
            });
        </script>
    @endpush

@endsection
