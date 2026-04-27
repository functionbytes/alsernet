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
                                @if(\Modules\System\Helpers\ModuleStatusHelper::isModuleEnabled('System'))
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
    {{-- Instructions modal: shows copy-paste commands for the admin --}}
    <div class="modal fade" id="modal-instructions" tabindex="-1" aria-labelledby="modal-instructions-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-semibold" id="modal-instructions-label">Instrucciones</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3" id="modal-instructions-text"></p>
                    <div id="modal-instructions-list"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cerrar</button>
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

    // Mostrar instrucciones para configurar el cron del scheduler
    @if(!$schedulerActive)
    $('#btn-configure-cron').on('click', function () {
        $.get('{{ route('settings.backups.scheduler.configure-instructions') }}', function (res) {
            showInstructions('Configurar crontab', res.instructions, [res.cron_line]);
        }).fail(function () {
            toastr.error('No se pudieron obtener las instrucciones.');
        });
    });
    @endif

    var instructionsModal = new bootstrap.Modal(document.getElementById('modal-instructions'));

    function showInstructions(title, text, commands) {
        $('#modal-instructions-label').text(title);
        $('#modal-instructions-text').text(text);

        var html = '';
        $.each(commands, function (i, cmd) {
            html += '<div class="input-group mb-2">' +
                '<code class="form-control bg-light small" id="guide-cmd-' + i + '">' + $('<span>').text(cmd).html() + '</code>' +
                '<button class="btn btn-outline-secondary btn-copy-guide" data-idx="' + i + '" type="button">Copiar</button>' +
                '</div>';
        });
        $('#modal-instructions-list').html(html);
        instructionsModal.show();
    }

    $(document).on('click', '.btn-copy-guide', function () {
        var idx = $(this).data('idx');
        var text = $('#guide-cmd-' + idx).text().trim();
        navigator.clipboard.writeText(text).then(function () {
            toastr.success('Copiado al portapapeles');
        }).catch(function () {
            toastr.error('No se pudo copiar');
        });
    });

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
                $apply.text('Ver cómo activar').removeClass('d-none');
                $restart.addClass('d-none');
            } else {
                $badge.html('<span class="badge bg-danger-subtle text-danger">No configurado</span>');
                $apply.text('Ver instrucciones').removeClass('d-none');
                $restart.addClass('d-none');
            }
        }).fail(function () {
            $('#supervisor-status-badge').html('<span class="badge bg-secondary-subtle text-secondary">Error al verificar</span>');
        });
    }

    $('#btn-supervisor-install').on('click', function () {
        $.get('{{ route('settings.backups.supervisor.install-instructions') }}', function (res) {
            showInstructions('Instalar Supervisor', res.instructions, res.commands);
        }).fail(function () {
            toastr.error('No se pudieron obtener las instrucciones.');
        });
    });

    $('#btn-supervisor-apply').on('click', function () {
        $.get('{{ route('settings.backups.supervisor.apply-instructions') }}', function (res) {
            showInstructions('Configurar worker de cola', res.instructions, res.commands);
        }).fail(function () {
            toastr.error('No se pudieron obtener las instrucciones.');
        });
    });

    $('#btn-supervisor-restart').on('click', function () {
        $.get('{{ route('settings.backups.supervisor.restart-instructions') }}', function (res) {
            showInstructions('Reiniciar worker', res.instructions, res.commands);
        }).fail(function () {
            toastr.error('No se pudieron obtener las instrucciones.');
        });
    });

    $('#btn-supervisor-refresh').on('click', function () {
        $('#supervisor-status-badge').html('<span class="badge bg-secondary-subtle text-secondary">Verificando...</span>');
        $('#btn-supervisor-apply, #btn-supervisor-restart').addClass('d-none');
        loadSupervisorStatus();
    });

    loadSupervisorStatus();

    @endif
});
</script>
@endpush
