@extends('layouts.theme')

@section('title', 'Guía de configuración')

@section('content')

    @include('core::components.card', ['title' => 'Guía de configuración'])

    @php
        $detectedTab = match($os) {
            'Darwin'  => 'mac',
            'Windows' => 'windows',
            default   => 'linux',
        };
    @endphp

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Guía de configuración</h5>
                        <p class="small mb-0 text-muted">Configura el scheduler y el worker de cola para que los backups programados se ejecuten automáticamente</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('settings.backups.index') }}">Ver backups</a>
                                <a class="dropdown-item" href="{{ route('settings.backup.schedules.index') }}">Ver programaciones</a>
                                <a class="dropdown-item" href="{{ route('settings.backups.setup') }}">Asistente de configuración</a>
                                @if(\App\Helpers\ModuleStatusHelper::isModuleEnabled('System'))
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="{{ route('settings.system.supervisor.index') }}">Configurar supervisor</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Estado del sistema --}}
            <div class="card-body border-bottom">
                <h6 class="fw-semibold mb-1">Estado del sistema</h6>
                <p class="text-muted mb-3">Información del entorno detectado automáticamente</p>
                <div class="row g-3">
                    <div class="col-sm-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Sistema operativo</h6>
                                <h5 class="mb-1 fw-bold">
                                    @if($os === 'Darwin') Mac
                                    @elseif($os === 'Windows') Windows
                                    @else Linux
                                    @endif
                                </h5>
                                <small class="text-muted">{{ PHP_OS }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Scheduler</h6>
                                @if($schedulerActive)
                                    <span class="badge bg-success-subtle text-success fs-6 mb-1">Activo</span>
                                    <div></div>
                                    <small class="text-muted">schedule:run detectado en crontab</small>
                                @else
                                    <span class="badge bg-warning-subtle text-warning fs-6 mb-1">No detectado</span>
                                    <div class="mt-2">
                                        @if($os !== 'Windows')
                                            <button type="button" class="btn btn-primary btn-sm" id="btn-configure-cron">
                                                Configurar cron
                                            </button>
                                        @else
                                            <small class="text-muted">Configura el Programador de tareas</small>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">PHP</h6>
                                <h5 class="mb-1 fw-bold">{{ PHP_VERSION }}</h5>
                                <small class="text-muted text-truncate d-block" title="{{ $phpBinary }}">{{ $phpBinary }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Scheduler (Cron) --}}
            <div class="card-body border-bottom">
                <h6 class="fw-semibold mb-1">Configuración del scheduler</h6>
                <p class="text-muted mb-3">El scheduler de Laravel ejecuta los backups programados. Debe configurarse una vez en el sistema.</p>

                <ul class="nav nav-tabs mb-3" id="schedulerTabs">
                    <li class="nav-item">
                        <a class="nav-link {{ $detectedTab === 'linux' ? 'active text-white' : '' }}" data-bs-toggle="tab" href="#tab-linux">
                            Linux
                            @if($detectedTab === 'linux') <span class="badge bg-primary-subtle text-black ms-1">Detectado</span> @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $detectedTab === 'mac' ? 'active text-white' : '' }}" data-bs-toggle="tab" href="#tab-mac">
                            Mac
                            @if($detectedTab === 'mac') <span class="badge bg-primary-subtle text-black ms-1">Detectado</span> @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $detectedTab === 'windows' ? 'active text-white' : '' }}" data-bs-toggle="tab" href="#tab-windows">
                            Windows
                            @if($detectedTab === 'windows') <span class="badge bg-primary-subtle text-black ms-1">Detectado</span> @endif
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- Linux --}}
                    <div class="tab-pane fade {{ $detectedTab === 'linux' ? 'show active' : '' }}" id="tab-linux">
                        <p class="small text-muted mb-2">Abre el crontab con <code>crontab -e</code> y agrega esta línea:</p>
                        <div class="input-group mb-2">
                            <code class="form-control bg-light small" id="cron-linux">* * * * * cd {{ $projectPath }} && {{ $phpBinary }} artisan schedule:run >> /dev/null 2>&1</code>
                            <button class="btn btn-outline-secondary btn-copy" data-target="cron-linux" type="button">Copiar</button>
                        </div>
                        <p class="small text-muted mb-0">Para verificar que está activo: <code>crontab -l</code></p>
                    </div>

                    {{-- Mac --}}
                    <div class="tab-pane fade {{ $detectedTab === 'mac' ? 'show active' : '' }}" id="tab-mac">
                        <p class="small text-muted mb-2">Abre el crontab con <code>crontab -e</code> y agrega esta línea:</p>
                        <div class="input-group mb-2">
                            <code class="form-control bg-light small" id="cron-mac">* * * * * cd {{ $projectPath }} && {{ $phpBinary }} artisan schedule:run >> /dev/null 2>&1</code>
                            <button class="btn btn-outline-secondary btn-copy" data-target="cron-mac" type="button">Copiar</button>
                        </div>
                        <p class="small text-muted mb-2">O con Herd (recomendado para desarrollo):</p>
                        <div class="input-group mb-2">
                            <code class="form-control bg-light small" id="cron-herd">* * * * * cd {{ $projectPath }} && /usr/bin/php artisan schedule:run >> /dev/null 2>&1</code>
                            <button class="btn btn-outline-secondary btn-copy" data-target="cron-herd" type="button">Copiar</button>
                        </div>
                    </div>

                    {{-- Windows --}}
                    <div class="tab-pane fade {{ $detectedTab === 'windows' ? 'show active' : '' }}" id="tab-windows">
                        <p class="small text-muted mb-2">Abre el <strong>Programador de tareas</strong> de Windows y crea una tarea con esta acción (cada minuto):</p>
                        <div class="input-group mb-2">
                            <code class="form-control bg-light small" id="cron-win">{{ $phpBinary }} {{ $projectPath }}\artisan schedule:run</code>
                            <button class="btn btn-outline-secondary btn-copy" data-target="cron-win" type="button">Copiar</button>
                        </div>
                        <p class="small text-muted mb-0">O ejecuta en PowerShell cada minuto con un bucle mientras desarrollas.</p>
                    </div>
                </div>
            </div>

            {{-- Supervisor: instalación e implementación automática --}}
            @if($os !== 'Windows')
            <div class="card-body border-bottom">
                <h6 class="fw-semibold mb-1">Worker de cola — Supervisor</h6>
                <p class="text-muted mb-3">
                    Los backups se procesan en segundo plano con Supervisor. El archivo de configuración se guardará en
                    <code>{{ $supervisorConfigPath }}</code>.
                </p>

                @if(!$supervisorInstalled)
                {{-- Supervisor not installed --}}
                <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                    <span class="badge bg-danger-subtle text-danger fs-6">Supervisor no instalado</span>
                    <button id="btn-supervisor-install" class="btn btn-primary btn-sm" type="button">
                        Instalar Supervisor
                    </button>
                    <button id="btn-supervisor-refresh" class="btn btn-outline-secondary btn-sm" type="button">
                        Actualizar estado
                    </button>
                </div>

                <div id="supervisor-output-area" class="d-none mb-3">
                    <pre id="supervisor-output" class="bg-light border rounded p-3 small mb-0" style="max-height:160px;overflow-y:auto;white-space:pre-wrap;"></pre>
                </div>

                <ul class="nav nav-tabs mb-3" id="installTabs">
                    <li class="nav-item">
                        <a class="nav-link {{ $detectedTab === 'linux' ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-install-linux">
                            Linux
                            @if($detectedTab === 'linux') <span class="badge bg-primary-subtle text-primary ms-1">Detectado</span> @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $detectedTab === 'mac' ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-install-mac">
                            Mac
                            @if($detectedTab === 'mac') <span class="badge bg-primary-subtle text-primary ms-1">Detectado</span> @endif
                        </a>
                    </li>
                </ul>
                <div class="tab-content mb-0">
                    <div class="tab-pane fade {{ $detectedTab === 'linux' ? 'show active' : '' }}" id="tab-install-linux">
                        <p class="small text-muted mb-2">O instálalo manualmente con tu gestor de paquetes:</p>
                        <div class="input-group mb-2">
                            <code class="form-control bg-light small" id="install-ubuntu">sudo apt-get update && sudo apt-get install -y supervisor && sudo systemctl enable supervisor && sudo systemctl start supervisor</code>
                            <button class="btn btn-outline-secondary btn-copy" data-target="install-ubuntu" type="button">Copiar (Debian/Ubuntu)</button>
                        </div>
                        <div class="input-group">
                            <code class="form-control bg-light small" id="install-rhel">sudo yum install -y supervisor && sudo systemctl enable supervisord && sudo systemctl start supervisord</code>
                            <button class="btn btn-outline-secondary btn-copy" data-target="install-rhel" type="button">Copiar (RHEL/CentOS)</button>
                        </div>
                    </div>
                    <div class="tab-pane fade {{ $detectedTab === 'mac' ? 'show active' : '' }}" id="tab-install-mac">
                        <p class="small text-muted mb-2">O instálalo manualmente con Homebrew:</p>
                        <div class="input-group mb-2">
                            <code class="form-control bg-light small" id="install-mac">brew install supervisor && brew services start supervisor</code>
                            <button class="btn btn-outline-secondary btn-copy" data-target="install-mac" type="button">Copiar</button>
                        </div>
                        <p class="small text-muted mb-0">Apple Silicon (M1/M2/M3): Homebrew instala en <code>/opt/homebrew</code>. Intel Mac: en <code>/usr/local</code>.</p>
                    </div>
                </div>
                @else
                {{-- Supervisor installed: show status + one-click apply --}}
                <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                    <div id="supervisor-status-badge">
                        <span class="badge bg-secondary-subtle text-secondary">Verificando...</span>
                    </div>
                    <button id="btn-supervisor-apply" class="btn btn-primary btn-sm d-none" type="button">
                        Instalar configuración
                    </button>
                    <button id="btn-supervisor-restart" class="btn btn-outline-secondary btn-sm d-none" type="button">
                        Reiniciar worker
                    </button>
                    <button id="btn-supervisor-refresh" class="btn btn-outline-secondary btn-sm" type="button">
                        Actualizar estado
                    </button>
                </div>

                <div id="supervisor-output-area" class="d-none">
                    <pre id="supervisor-output" class="bg-light border rounded p-3 small mb-0" style="max-height:160px;overflow-y:auto;white-space:pre-wrap;"></pre>
                </div>

                <hr class="my-3">
                <p class="small text-muted mb-2">O configúralo manualmente. Crea el archivo <code>{{ $supervisorConfigPath }}</code>:</p>
                <div class="position-relative mb-2">
                    <pre class="bg-light border rounded p-3 small mb-0" id="supervisor-config">[program:backups-worker]
process_name=%(program_name)s_%(process_num)02d
command={{ $phpBinary }} {{ $projectPath }}/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile={{ $projectPath }}/storage/logs/worker.log
stopwaitsecs=3600</pre>
                    <button class="btn btn-sm btn-outline-secondary btn-copy position-absolute top-0 end-0 m-2"
                            data-target="supervisor-config" type="button">Copiar</button>
                </div>
                <div class="input-group">
                    <code class="form-control bg-light small" id="supervisor-reload">sudo {{ $supervisorctlBin }} reread && sudo {{ $supervisorctlBin }} update && sudo {{ $supervisorctlBin }} start backups-worker:*</code>
                    <button class="btn btn-outline-secondary btn-copy" data-target="supervisor-reload" type="button">Copiar</button>
                </div>
                @endif
            </div>

            {{-- Manual worker (always shown as alternative) --}}
            <div class="card-body border-bottom">
                <h6 class="fw-semibold mb-1">Worker manual (alternativa)</h6>
                <p class="text-muted mb-3">Ejecuta el worker directamente en una terminal. El proceso se detiene al cerrar la ventana.</p>
                <div class="input-group">
                    <code class="form-control bg-light small" id="worker-manual">{{ $phpBinary }} {{ $projectPath }}/artisan queue:work --sleep=3 --tries=3</code>
                    <button class="btn btn-outline-secondary btn-copy" data-target="worker-manual" type="button">Copiar</button>
                </div>
            </div>
            @else
            {{-- Windows --}}
            <div class="card-body border-bottom">
                <h6 class="fw-semibold mb-1">Worker de cola — Windows</h6>
                <p class="text-muted mb-3">En Windows puedes ejecutar el worker manualmente o configurarlo como servicio con NSSM.</p>
                <p class="small text-muted mb-2">Ejecución manual (mantén la ventana abierta):</p>
                <div class="input-group mb-3">
                    <code class="form-control bg-light small" id="worker-win">{{ $phpBinary }} {{ $projectPath }}\artisan queue:work --sleep=3 --tries=3</code>
                    <button class="btn btn-outline-secondary btn-copy" data-target="worker-win" type="button">Copiar</button>
                </div>
                <p class="small text-muted mb-2">Con <a href="https://nssm.cc" target="_blank">NSSM</a> como servicio de Windows:</p>
                <div class="input-group mb-2">
                    <code class="form-control bg-light small" id="worker-nssm">nssm install backups-worker "{{ $phpBinary }}" "{{ $projectPath }}\artisan queue:work --sleep=3 --tries=3"</code>
                    <button class="btn btn-outline-secondary btn-copy" data-target="worker-nssm" type="button">Copiar</button>
                </div>
                <div class="input-group">
                    <code class="form-control bg-light small" id="worker-nssm-start">nssm start backups-worker</code>
                    <button class="btn btn-outline-secondary btn-copy" data-target="worker-nssm-start" type="button">Copiar</button>
                </div>
            </div>
            @endif

            {{-- Comando artisan --}}
            <div class="card-body">
                <h6 class="fw-semibold mb-1">Comando artisan</h6>
                <p class="text-muted mb-3">El scheduler ejecuta automáticamente este comando cuando hay backups pendientes. También puedes ejecutarlo manualmente para probar.</p>
                <div class="input-group">
                    <code class="form-control bg-light small" id="artisan-cmd">{{ $phpBinary }} {{ $projectPath }}/artisan app:run-scheduled-backups</code>
                    <button class="btn btn-outline-secondary btn-copy" data-target="artisan-cmd" type="button">Copiar</button>
                </div>
            </div>

        </div>
    </div>

    @if($os !== 'Windows')
    {{-- Sudo password modal --}}
    <div class="modal fade" id="modal-sudo" tabindex="-1" aria-labelledby="modal-sudo-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-semibold" id="modal-sudo-label">Autenticación requerida</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3" id="sudo-modal-message">El proceso web no tiene permisos para escribir en <code>/etc/supervisor/conf.d/</code>. Introduce tu contraseña sudo para continuar.</p>
                    <div class="mb-0">
                        <label for="sudo-password-input" class="form-label small fw-semibold">Contraseña sudo</label>
                        <input type="password" class="form-control" id="sudo-password-input"
                               placeholder="Contraseña del sistema" autocomplete="current-password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-sudo-confirm">Confirmar</button>
                </div>
            </div>
        </div>
    </div>
    @endif

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    // Copy to clipboard
    $(document).on('click', '.btn-copy', function () {
        var target = $(this).data('target');
        var text = $('#' + target).text().trim();
        navigator.clipboard.writeText(text).then(function () {
            toastr.success('Copiado al portapapeles');
        }).catch(function () {
            toastr.error('No se pudo copiar');
        });
    });

    @if($os !== 'Windows')

    // Configurar cron del scheduler
    @if(!$schedulerActive)
    $('#btn-configure-cron').on('click', function () {
        var $btn = $(this).prop('disabled', true).text('Configurando...');
        $.ajax({
            url: '{{ route('settings.backups.scheduler.configure') }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                if (res.success) {
                    toastr.success(res.message);
                    $btn.closest('.card').find('.badge').replaceWith('<span class="badge bg-success-subtle text-success fs-6 mb-1">Activo</span>');
                    $btn.replaceWith('<small class="text-muted">schedule:run detectado en crontab</small>');
                } else {
                    toastr.error(res.message);
                    $btn.prop('disabled', false).text('Configurar cron');
                }
            },
            error: function () {
                toastr.error('Error al configurar el crontab.');
                $btn.prop('disabled', false).text('Configurar cron');
            }
        });
    });
    @endif

    var sudoModal    = new bootstrap.Modal(document.getElementById('modal-sudo'));
    var pendingAction = null; // 'install' | 'apply' | 'restart'

    function loadSupervisorStatus() {
        $.get('{{ route('settings.backups.supervisor.status') }}', function (data) {
            var $badge   = $('#supervisor-status-badge');
            var $apply   = $('#btn-supervisor-apply');
            var $restart = $('#btn-supervisor-restart');

            if (!data.supervisor_available) {
                $badge.html('<span class="badge bg-warning-subtle text-warning">Supervisor no disponible</span>');
                $apply.addClass('d-none');
                $restart.addClass('d-none');
                return;
            }

            if (data.process_running) {
                $badge.html('<span class="badge bg-success-subtle text-success">Worker activo</span>');
                $apply.addClass('d-none');
                $restart.removeClass('d-none');
            } else if (data.config_exists) {
                $badge.html('<span class="badge bg-warning-subtle text-warning">Configurado pero inactivo</span>');
                $apply.text('Activar worker').removeClass('d-none');
                $restart.addClass('d-none');
            } else {
                $badge.html('<span class="badge bg-danger-subtle text-danger">No configurado</span>');
                $apply.text('Instalar configuración').removeClass('d-none');
                $restart.addClass('d-none');
            }
        }).fail(function () {
            $('#supervisor-status-badge').html('<span class="badge bg-secondary-subtle text-secondary">Error al verificar</span>');
        });
    }

    function showOutput(text) {
        $('#supervisor-output').text(text);
        $('#supervisor-output-area').removeClass('d-none');
    }

    function openSudoModal(action, message) {
        pendingAction = action;
        if (message) {
            $('#sudo-modal-message').html(message);
        }
        $('#sudo-password-input').val('');
        sudoModal.show();
        setTimeout(function () { $('#sudo-password-input').focus(); }, 400);
    }

    function doApply(sudoPassword) {
        var data = {};
        if (sudoPassword) {
            data.sudo_password = sudoPassword;
        }
        var $btn = $('#btn-supervisor-apply').prop('disabled', true).text('Aplicando...');

        $.ajax({
            url: '{{ route('settings.backups.supervisor.apply') }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: data,
            success: function (res) {
                toastr.success(res.message);
                if (res.output) { showOutput(res.output); }
                loadSupervisorStatus();
            },
            error: function (xhr) {
                var res = xhr.responseJSON || {};
                if (res.requires_sudo) {
                    openSudoModal('apply', res.message);
                } else {
                    toastr.error(res.message || 'Error al aplicar la configuración.');
                    if (res.output) { showOutput(res.output); }
                }
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    }

    function doRestart(sudoPassword) {
        var data = {};
        if (sudoPassword) {
            data.sudo_password = sudoPassword;
        }
        var $btn = $('#btn-supervisor-restart').prop('disabled', true).text('Reiniciando...');

        $.ajax({
            url: '{{ route('settings.backups.supervisor.restart') }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: data,
            success: function (res) {
                toastr.success(res.message);
                if (res.output) { showOutput(res.output); }
                loadSupervisorStatus();
            },
            error: function (xhr) {
                var res = xhr.responseJSON || {};
                if (res.requires_sudo) {
                    openSudoModal('restart', res.message);
                } else {
                    toastr.error(res.message || 'Error al reiniciar el worker.');
                    if (res.output) { showOutput(res.output); }
                }
            },
            complete: function () {
                $btn.prop('disabled', false).text('Reiniciar worker');
            }
        });
    }

    function doInstall(sudoPassword) {
        var data = {};
        if (sudoPassword) { data.sudo_password = sudoPassword; }
        var $btn = $('#btn-supervisor-install').prop('disabled', true).text('Instalando...');

        $.ajax({
            url: '{{ route('settings.backups.supervisor.install') }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: data,
            success: function (res) {
                toastr.success(res.message);
                if (res.output) { showOutput(res.output); }
                if (res.reload) { setTimeout(function () { location.reload(); }, 1500); }
            },
            error: function (xhr) {
                var res = xhr.responseJSON || {};
                if (res.requires_sudo) {
                    openSudoModal('install', res.message);
                } else {
                    toastr.error(res.message || 'Error durante la instalación.');
                    if (res.output) { showOutput(res.output); }
                }
            },
            complete: function () {
                $btn.prop('disabled', false).text('Instalar Supervisor');
            }
        });
    }

    $('#btn-supervisor-install').on('click', function () { doInstall(null); });
    $('#btn-supervisor-apply').on('click', function () { doApply(null); });
    $('#btn-supervisor-restart').on('click', function () { doRestart(null); });

    $('#btn-supervisor-refresh').on('click', function () {
        $('#supervisor-status-badge').html('<span class="badge bg-secondary-subtle text-secondary">Verificando...</span>');
        $('#btn-supervisor-apply, #btn-supervisor-restart').addClass('d-none');
        loadSupervisorStatus();
    });

    // Confirm sudo modal
    $('#btn-sudo-confirm').on('click', function () {
        var password = $('#sudo-password-input').val();
        if (!password) {
            $('#sudo-password-input').addClass('is-invalid').focus();
            return;
        }
        $('#sudo-password-input').removeClass('is-invalid');
        sudoModal.hide();

        if (pendingAction === 'install') {
            doInstall(password);
        } else if (pendingAction === 'apply') {
            doApply(password);
        } else if (pendingAction === 'restart') {
            doRestart(password);
        }
    });

    // Allow Enter key in modal
    $('#sudo-password-input').on('keydown', function (e) {
        if (e.key === 'Enter') { $('#btn-sudo-confirm').trigger('click'); }
    });

    loadSupervisorStatus();

    @endif
});
</script>
@endpush
