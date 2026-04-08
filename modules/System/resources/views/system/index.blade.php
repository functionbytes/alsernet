@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Configuración del sistema'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            <div class="card-header p-4 border-bottom">
                <h5 class="mb-1 fw-bold">Configuración del sistema</h5>
                <p class="small mb-0 text-muted">Gestiona la conexión de colas, websockets y el monitoreo del servidor.</p>
            </div>

            {{-- Navigation Tabs --}}
            <ul class="nav nav-tabs border-0 user-profile-tab" id="system-settings-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $activeTab === 'queue' ? 'active' : '' }}"
                            id="queue-tab" data-bs-toggle="pill" data-bs-target="#queue"
                            type="button" role="tab" aria-controls="queue" aria-selected="{{ $activeTab === 'queue' ? 'true' : 'false' }}">
                        <span class="d-none d-md-block">Colas</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $activeTab === 'websockets' ? 'active' : '' }}"
                            id="websockets-tab" data-bs-toggle="pill" data-bs-target="#websockets"
                            type="button" role="tab" aria-controls="websockets" aria-selected="{{ $activeTab === 'websockets' ? 'true' : 'false' }}">
                        <span class="d-none d-md-block">Websockets</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $activeTab === 'monitor' ? 'active' : '' }}"
                            id="monitor-tab" data-bs-toggle="pill" data-bs-target="#monitor"
                            type="button" role="tab" aria-controls="monitor" aria-selected="{{ $activeTab === 'monitor' ? 'true' : 'false' }}">
                        <span class="d-none d-md-block">Monitoreo</span>
                    </button>
                </li>
            </ul>

        <div class="tab-content p-4" id="system-settings-content">

            {{-- ═══════════════════════════════════════════════════════════════
                 QUEUE TAB
                 ═══════════════════════════════════════════════════════════════ --}}
            <div role="tabpanel" class="tab-pane fade {{ $activeTab === 'queue' ? 'active show' : '' }}" id="queue">
                <div class="row g-4 align-items-start">

                    <div class="col-lg-12">
                        <form method="POST" action="{{ route('settings.system.queue.update') }}" id="queueForm">
                            @csrf
                            @method('PUT')

                            <div class="card">
                                <div class="card-body">
                                    <h6 class="fw-bold text-dark mb-1">Conexión de cola</h6>
                                    <p class="text-muted mb-3">Selecciona el driver que procesará los trabajos en segundo plano.</p>

                                    <label for="queueConnection" class="form-label fw-semibold">Driver de cola</label>
                                    <select class="form-select select2" id="queueConnection" name="default_connection" required>
                                        <option value="sync" {{ $queueSettings['default_connection'] === 'sync' ? 'selected' : '' }}>Sync (Síncrona)</option>
                                        <option value="database" {{ $queueSettings['default_connection'] === 'database' ? 'selected' : '' }}>Database</option>
                                        <option value="redis" {{ $queueSettings['default_connection'] === 'redis' ? 'selected' : '' }}>Redis</option>
                                        <option value="sqs" {{ $queueSettings['default_connection'] === 'sqs' ? 'selected' : '' }}>Amazon SQS</option>
                                    </select>
                                    <small class="text-muted d-block mt-1">Driver predeterminado para procesar trabajos en cola</small>
                                </div>

                                <hr class="my-0">

                                <div class="card-body">
                                    <h6 class="fw-bold text-dark mb-1">Trabajos fallidos</h6>
                                    <p class="text-muted mb-3">Configuración del almacenamiento de trabajos que fallan.</p>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Driver de trabajos fallidos</label>
                                        <p class="mb-0"><code>{{ $queueSettings['failed_driver'] }}</code></p>
                                        <small class="text-muted">Donde se almacenan los trabajos fallidos</small>
                                    </div>

                                    <div class="alert alert-info border-0 mb-0">
                                        <small>
                                            <strong>Nota:</strong> Después de cambiar la conexión de cola, reinicia los workers para aplicar los cambios.
                                        </small>
                                    </div>
                                </div>

                                <div class="card-footer">
                                        <button type="submit" class="btn btn-primary flex-fill w-100 mb-1">Guardar configuración</button>
                                        <button type="button" class="btn btn-outline-secondary w-100 mb-1" id="testQueueBtn">
                                            Probar
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary w-100 mb-1" id="restartQueueBtn">
                                            Reiniciar
                                        </button>
                                </div>
                            </div>
                        </form>

                        {{-- Queue Health --}}
                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Estado de colas</h6>
                                <p class="text-muted mb-0">Monitoreo en tiempo real de trabajos pendientes y fallidos.</p>
                            </div>
                            <button type="button" class="btn btn-info btn-sm" id="refreshQueueStatsBtn">
                                <i class="fas fa-rotate-right me-1"></i>
                            </button>
                        </div>
                        <div id="queue-stats-content">
                            <div class="text-center py-3 text-muted">
                                <i class="fas fa-spinner fa-spin me-1"></i> Cargando...
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════════════
                 WEBSOCKETS TAB
                 ═══════════════════════════════════════════════════════════════ --}}
            <div role="tabpanel" class="tab-pane fade {{ $activeTab === 'websockets' ? 'active show' : '' }}" id="websockets">
                <div class="row g-4 align-items-start">

                    <div class="col-lg-12">
                        <form method="POST" action="{{ route('settings.system.websockets.update') }}" id="websocketsForm">
                            @csrf
                            @method('PUT')

                            <div class="card">
                                <div class="card-body">
                                    <h6 class="fw-bold text-dark mb-1">Driver de broadcasting</h6>
                                    <p class="text-muted mb-3">Selecciona el servicio para transmitir eventos en tiempo real.</p>

                                    <label for="broadcastDriver" class="form-label fw-semibold">Driver</label>
                                    <select class="form-select select2" id="broadcastDriver" name="broadcast_driver" required>
                                        <option value="reverb" {{ $websocketsSettings['driver'] === 'reverb' ? 'selected' : '' }}>Laravel Reverb</option>
                                        <option value="pusher" {{ $websocketsSettings['driver'] === 'pusher' ? 'selected' : '' }}>Pusher</option>
                                        <option value="redis" {{ $websocketsSettings['driver'] === 'redis' ? 'selected' : '' }}>Redis</option>
                                        <option value="log" {{ $websocketsSettings['driver'] === 'log' ? 'selected' : '' }}>Log (Desarrollo)</option>
                                        <option value="null" {{ $websocketsSettings['driver'] === 'null' ? 'selected' : '' }}>Deshabilitado</option>
                                    </select>
                                    <small class="text-muted d-block mt-1">Driver para transmitir eventos en tiempo real</small>
                                </div>

                                {{-- Reverb Settings --}}
                                <div id="reverbSettings" class="{{ $websocketsSettings['driver'] === 'reverb' ? '' : 'd-none' }}">
                                    <hr class="my-0">
                                    <div class="card-body">
                                        <h6 class="fw-bold text-dark mb-1">Configuración Reverb</h6>
                                        <p class="text-muted mb-3">Parámetros de conexión del servidor Laravel Reverb.</p>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="reverbHost" class="form-label fw-semibold">Host</label>
                                                <input type="text" class="form-control" id="reverbHost" name="reverb_host"
                                                       value="{{ $websocketsSettings['reverb_host'] }}" placeholder="0.0.0.0">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="reverbPort" class="form-label fw-semibold">Puerto</label>
                                                <input type="number" class="form-control" id="reverbPort" name="reverb_port"
                                                       value="{{ $websocketsSettings['reverb_port'] }}" placeholder="8080">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="reverbScheme" class="form-label fw-semibold">Esquema</label>
                                                <select class="form-select" id="reverbScheme" name="reverb_scheme">
                                                    <option value="http" {{ $websocketsSettings['reverb_scheme'] === 'http' ? 'selected' : '' }}>HTTP</option>
                                                    <option value="https" {{ $websocketsSettings['reverb_scheme'] === 'https' ? 'selected' : '' }}>HTTPS</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Pusher Settings --}}
                                <div id="pusherSettings" class="{{ $websocketsSettings['driver'] === 'pusher' ? '' : 'd-none' }}">
                                    <hr class="my-0">
                                    <div class="card-body">
                                        <h6 class="fw-bold text-dark mb-1">Credenciales Pusher</h6>
                                        <p class="text-muted mb-3">Ingresa las credenciales de tu aplicación Pusher.</p>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="pusherAppId" class="form-label fw-semibold">App ID <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="pusherAppId" name="pusher_app_id"
                                                       value="{{ $websocketsSettings['pusher_app_id'] }}" placeholder="123456">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="pusherCluster" class="form-label fw-semibold">Cluster <span class="text-danger">*</span></label>
                                                <select class="form-select" id="pusherCluster" name="pusher_cluster">
                                                    @foreach(['mt1' => 'mt1 (US East)', 'us2' => 'us2 (US East)', 'us3' => 'us3 (US West)', 'eu' => 'eu (Europe)', 'ap1' => 'ap1 (Asia)', 'ap2' => 'ap2 (Asia)', 'ap3' => 'ap3 (Asia)', 'ap4' => 'ap4 (Asia)'] as $val => $label)
                                                        <option value="{{ $val }}" {{ $websocketsSettings['pusher_cluster'] === $val ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="pusherKey" class="form-label fw-semibold">Key <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="pusherKey" name="pusher_key"
                                                       value="{{ $websocketsSettings['pusher_key'] }}" placeholder="xxxxxxxxxxxxxxxx">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="pusherSecret" class="form-label fw-semibold">Secret <span class="text-danger">*</span></label>
                                                <input type="password" class="form-control" id="pusherSecret" name="pusher_secret"
                                                       value="{{ $websocketsSettings['pusher_secret'] }}" placeholder="••••••••••••••••" autocomplete="off">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Redis Settings --}}
                                <div id="redisSettings" class="{{ $websocketsSettings['driver'] === 'redis' ? '' : 'd-none' }}">
                                    <hr class="my-0">
                                    <div class="card-body">
                                        <h6 class="fw-bold text-dark mb-1">Configuración Redis</h6>
                                        <p class="text-muted mb-3">Parámetros de conexión del servidor Redis.</p>
                                        <div class="row g-3">
                                            <div class="col-md-5">
                                                <label for="redisHost" class="form-label fw-semibold">Host <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="redisHost" name="redis_host"
                                                       value="{{ $websocketsSettings['redis_host'] }}" placeholder="127.0.0.1">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="redisPort" class="form-label fw-semibold">Puerto <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" id="redisPort" name="redis_port"
                                                       value="{{ $websocketsSettings['redis_port'] }}" placeholder="6379">
                                            </div>
                                            <div class="col-md-2">
                                                <label for="redisDatabase" class="form-label fw-semibold">DB</label>
                                                <input type="number" class="form-control" id="redisDatabase" name="redis_database"
                                                       value="{{ $websocketsSettings['redis_database'] }}" placeholder="0" min="0" max="15">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="redisPassword" class="form-label fw-semibold">Contraseña</label>
                                                <input type="password" class="form-control" id="redisPassword" name="redis_password"
                                                       value="{{ $websocketsSettings['redis_password'] }}" placeholder="••••••••" autocomplete="off">
                                                <small class="text-muted d-block mt-1">Opcional</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary w-100">Guardar configuración</button>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════════════
                 MONITOR TAB
                 ═══════════════════════════════════════════════════════════════ --}}
            <div role="tabpanel" class="tab-pane fade {{ $activeTab === 'monitor' ? 'active show' : '' }}" id="monitor">
                <div class="row g-4 align-items-start">

                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1">Espacio en disco</h6>
                                        <p class="text-muted mb-0">Estado actual del almacenamiento del servidor.</p>
                                    </div>
                                    <button type="button" class="btn btn-info btn-sm" id="refreshDiskStatsBtn">
                                        <i class="fas fa-rotate-right me-1"></i>
                                    </button>
                                </div>
                                <div id="disk-stats-content">
                                    <div class="text-center py-3 text-muted">
                                        <i class="fas fa-spinner fa-spin me-1"></i> Cargando...
                                    </div>
                                </div>
                                <div class="alert alert-info border-0 mt-3 mb-0">
                                    <small>
                                        El espacio en disco se consulta en tiempo real. Si supera el 90% de uso, considera limpiar logs o backups antiguos.
                                        El archivo <code>laravel.log</code> puede crecer significativamente en producción.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        </div>{{-- card --}}

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });

    // ── Driver toggle (websockets) ─────────────────────────────────
    $('#broadcastDriver').on('change', function () {
        $('#reverbSettings, #pusherSettings, #redisSettings').addClass('d-none');
        var sel = $(this).val();
        if (sel === 'reverb') $('#reverbSettings').removeClass('d-none');
        if (sel === 'pusher') $('#pusherSettings').removeClass('d-none');
        if (sel === 'redis')  $('#redisSettings').removeClass('d-none');
    });

    // ── Tab URL sync ───────────────────────────────────────────────
    $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
        var tabName = $(e.target).attr('aria-controls') || e.target.getAttribute('data-bs-target').replace('#','');
        var url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        window.history.pushState({}, '', url);
    });

    // ── Test Queue ─────────────────────────────────────────────────
    $('#testQueueBtn').on('click', function () {
        var $btn = $(this), orig = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.post('{{ route("settings.system.queue.test") }}', {
            _token: $('meta[name="csrf-token"]').attr('content'),
            connection: $('#queueConnection').val()
        }).done(function (data) {
            data.success ? toastr.success(data.message) : toastr.error(data.message);
        }).fail(function () {
            toastr.error('Error en la solicitud');
        }).always(function () {
            $btn.prop('disabled', false).html(orig);
        });
    });

    // ── Restart Workers ────────────────────────────────────────────
    $('#restartQueueBtn').on('click', function () {
        if (!confirm('¿Reiniciar los workers de cola? Puede interrumpir trabajos en progreso.')) return;
        var $btn = $(this), orig = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.post('{{ route("settings.system.queue.restart") }}', {
            _token: $('meta[name="csrf-token"]').attr('content')
        }).done(function (data) {
            data.success ? toastr.success(data.message) : toastr.error(data.message);
        }).fail(function () {
            toastr.error('Error al reiniciar');
        }).always(function () {
            $btn.prop('disabled', false).html(orig);
        });
    });

    // ── Queue Stats ────────────────────────────────────────────────
    function loadQueueStats() {
        $.get('{{ route("settings.system.queue-stats") }}')
            .done(renderQueueStats)
            .fail(function () {
                $('#queue-stats-content').html('<div class="alert alert-danger mb-0">No se pudo obtener el estado de las colas.</div>');
            });
    }

    function renderQueueStats(data) {
        if (data.error) {
            $('#queue-stats-content').html('<div class="alert alert-warning mb-0">' + data.error + '</div>');
            return;
        }

        var horizonBadge = data.horizon
            ? (data.horizon.status === 'running'
                ? '<span class="badge bg-success ms-2">Horizon activo</span>'
                : '<span class="badge bg-secondary ms-2">Horizon detenido</span>')
            : '';

        var byQueueRows = '';
        if (data.by_queue && data.by_queue.length) {
            data.by_queue.forEach(function (q) {
                byQueueRows += '<tr><td>' + q.queue + '</td><td><span class="badge bg-info text-white">' + q.count + '</span></td></tr>';
            });
        } else {
            byQueueRows = '<tr><td colspan="2" class="text-muted">Sin jobs pendientes</td></tr>';
        }

        var failedRows = '';
        if (data.recent_failed && data.recent_failed.length) {
            data.recent_failed.forEach(function (job) {
                var excerpt = job.exception ? job.exception.substring(0, 120) + '…' : '—';
                failedRows += '<tr><td>' + job.queue + '</td>'
                    + '<td><small class="text-muted font-monospace">' + excerpt + '</small></td>'
                    + '<td>' + job.failed_at + '</td>'
                    + '<td class="text-end">'
                    + '<div class="dropdown">'
                    + '<a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-auto-close="true" data-bs-boundary="viewport"><i class="fas fa-ellipsis-vertical"></i></a>'
                    + '<ul class="dropdown-menu dropdown-menu-end">'
                    + '<li><a class="dropdown-item btn-retry-job" href="#" data-id="' + job.id + '">Reintentar</a></li>'
                    + '<li><hr class="dropdown-divider"></li>'
                    + '<li><a class="dropdown-item btn-delete-job" href="#" data-id="' + job.id + '">Eliminar</a></li>'
                    + '</ul>'
                    + '</div>'
                    + '</td></tr>';
            });
        } else {
            failedRows = '<tr><td colspan="4" class="text-muted">Sin jobs fallidos recientes</td></tr>';
        }

        $('#queue-stats-content').html(
            '<div class="row g-3 mb-4">'
            + '<div class="col-md-6"><div class="card bg-light-secondary stat-card h-100"><div class="card-body"><h6 class="card-title mb-2">Jobs pendientes</h6><h4 class="mb-1 fw-bold">' + (data.pending ?? '—') + '</h4><small class="text-muted">En espera de procesarse</small></div></div></div>'
            + '<div class="col-md-6"><div class="card bg-light-secondary stat-card h-100"><div class="card-body"><h6 class="card-title mb-2">Jobs fallidos</h6><h4 class="mb-1 fw-bold">' + (data.failed ?? '—') + '</h4><small class="text-muted">Requieren atención</small></div></div></div>'
            + (horizonBadge ? '<div class="col-md-3 d-flex align-items-center">' + horizonBadge + '</div>' : '')
            + '</div>'
            + '<div class="row">'
            + '<div class="col-md-12  mt-5"><h6 class="fw-semibold mb-3">Jobs por cola</h6>'
            + '<div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Cola</th><th>Cantidad</th></tr></thead><tbody>' + byQueueRows + '</tbody></table></div></div></div>'

            + '<div class="row">'
            + '<div class="col-md-12 mt-5"><h6 class="fw-semibold mb-3">Jobs fallidos recientes</h6>'
            + '<div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Cola</th><th>Error</th><th>Fecha</th><th></th></tr></thead><tbody>' + failedRows + '</tbody></table></div></div></div>'
            + '</div>'
        );
    }

    $('#refreshQueueStatsBtn').on('click', loadQueueStats);

    $(document).on('click', '.btn-retry-job', function () {
        var $btn = $(this), id = $btn.data('id');
        $btn.prop('disabled', true);
        $.post('{{ url("setting/system/retry-failed-job") }}/' + id, {
            _token: $('meta[name="csrf-token"]').attr('content')
        }).done(function (data) {
            data.success ? (toastr.success(data.message), loadQueueStats()) : toastr.error(data.message);
        }).fail(function () { toastr.error('Error al reintentar'); $btn.prop('disabled', false); });
    });

    $(document).on('click', '.btn-delete-job', function () {
        if (!confirm('¿Eliminar este job fallido?')) return;
        var $btn = $(this), id = $btn.data('id');
        $btn.prop('disabled', true);
        $.ajax({
            url: '{{ url("setting/system/failed-jobs") }}/' + id,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (data) {
            data.success ? (toastr.success(data.message), loadQueueStats()) : toastr.error(data.message);
        }).fail(function () { toastr.error('Error al eliminar'); $btn.prop('disabled', false); });
    });

    // ── Disk Stats ─────────────────────────────────────────────────
    function loadDiskStats() {
        $.get('{{ route("settings.system.disk-stats") }}')
            .done(function (data) {
                var barColor = data.used_percent >= 90 ? 'danger' : (data.used_percent >= 70 ? 'warning' : 'success');
                $('#disk-stats-content').html(
                    '<div class="row g-3 mb-3">'
                    + '<div class="col-md-3 col-6"><div class="p-3 border rounded text-center"><div class="fw-bold fs-5">' + data.total + '</div><div class="small text-muted">Total</div></div></div>'
                    + '<div class="col-md-3 col-6"><div class="p-3 border rounded text-center"><div class="fw-bold fs-5">' + data.used + '</div><div class="small text-muted">Usado</div></div></div>'
                    + '<div class="col-md-3 col-6"><div class="p-3 border rounded text-center"><div class="fw-bold fs-5">' + data.free + '</div><div class="small text-muted">Libre</div></div></div>'
                    + '<div class="col-md-3 col-6"><div class="p-3 border rounded text-center"><div class="fw-bold fs-5">' + data.log_size + '</div><div class="small text-muted">Log laravel.log</div></div></div>'
                    + '</div>'
                    + '<label class="form-label fw-semibold mb-1">Disco usado: ' + data.used_percent + '%</label>'
                    + '<div class="progress" style="height:12px;"><div class="progress-bar bg-' + barColor + '" style="width:' + data.used_percent + '%"></div></div>'
                );
            })
            .fail(function () {
                $('#disk-stats-content').html('<div class="alert alert-danger mb-0">No se pudo obtener el estado del disco.</div>');
            });
    }

    $('#refreshDiskStatsBtn').on('click', loadDiskStats);

    // ── Auto-load on tab ───────────────────────────────────────────
    var activeTab = '{{ $activeTab }}';
    if (activeTab === 'queue') loadQueueStats();
    if (activeTab === 'monitor') loadDiskStats();

    document.getElementById('queue-tab').addEventListener('shown.bs.tab', loadQueueStats);
    document.getElementById('monitor-tab').addEventListener('shown.bs.tab', loadDiskStats);
});
</script>
@endpush
