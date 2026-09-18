@extends('layouts.theme')

@section('title', 'Configuración del sistema')

@push('css')
<style>
.status-pill {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px; font-size: .72rem; font-weight: 600;
}
.status-pill .dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; flex-shrink: 0; }
.status-pill.ok     { background: rgba(177,1,0,.1);  color: #13C672; }
.status-pill.warn   { background: #fff9e5;            color: #a07a00; }
.status-pill.danger { background: rgba(177,1,0,.08); color: #7a0000; }
.status-pill.muted  { background: #e9ecef;            color: #6c757d; }

.health-strip {
    display: flex; align-items: center; gap: 16px;
    padding: 16px 20px; border-radius: 8px;
}
.health-icon {
    width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 1rem;
}
.health-strip.ok     { background: rgba(177,1,0,.06); }
.health-strip.ok     .health-icon { background: #13C672; color: #fff; }
.health-strip.ok     .health-title { color: #13C672; }
.health-strip.warn   { background: #fff9e5; }
.health-strip.warn   .health-icon { background: #FEC90F; color: #fff; }
.health-strip.warn   .health-title { color: #7a6200; }
.health-strip.danger { background: rgba(177,1,0,.06); }
.health-strip.danger .health-icon { background: #8b0000; color: #fff; }
.health-strip.danger .health-title { color: #8b0000; }
.health-strip.muted  { background: #f5f6f8; }
.health-strip.muted  .health-icon { background: #adb5bd; color: #fff; }
.health-strip .health-title { font-weight: 600; font-size: .9rem; margin: 0; }
.health-strip .health-sub   { font-size: .8rem; color: #6c757d; margin: 0; }
</style>
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Configuración del sistema'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Configuración del sistema</h5>
                        <p class="small mb-0 text-muted">Estado de los componentes necesarios para ejecutar backups programados</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('settings.backups.index') }}">Ver backups</a>
                                <a class="dropdown-item" href="{{ route('settings.backup.schedules.index') }}">Programaciones</a>
                                <a class="dropdown-item" href="{{ route('settings.backups.guide') }}">Guía completa</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Loading --}}
            <div id="status-loading" class="card-body text-center py-5">
                <div class="spinner-border mb-3" role="status" style="color:#13C672; width:2rem; height:2rem;"></div>
                <p class="text-muted mb-0">Verificando estado del sistema...</p>
            </div>

            <div id="status-content" class="d-none">

                {{-- Health strip --}}
                <div class="card-body border-bottom">
                    <div id="health-strip" class="health-strip muted">
                        <div class="health-icon"><i id="health-icon" class="fas fa-circle-notch fa-spin"></i></div>
                        <div>
                            <p class="health-title" id="health-title">Verificando...</p>
                            <p class="health-sub"   id="health-sub"></p>
                        </div>
                        <div class="ms-auto">
                            <a href="{{ route('settings.backups.index') }}" class="btn btn-info">Ver backups</a>
                        </div>
                    </div>
                </div>

                {{-- Component cards (bg-light-secondary stat style) --}}
                <div class="card-body border-bottom">
                    <div class="row g-3">

                        <div class="col-md-4">
                            <div class="card bg-light-secondary h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title mb-0">Scheduler</h6>
                                        <span id="badge-scheduler"></span>
                                    </div>
                                    <small class="text-muted d-block mb-3">Ejecución automática de tareas programadas</small>
                                    <div id="action-scheduler" class="d-none"></div>
                                </div>
                            </div>
                        </div>

                        @if($os !== 'Windows')
                        <div class="col-md-4">
                            <div class="card bg-light-secondary h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title mb-0">Supervisor</h6>
                                        <span id="badge-supervisor"></span>
                                    </div>
                                    <small class="text-muted d-block mb-3">Gestor de procesos para el worker de cola</small>
                                    <div id="action-supervisor" class="d-none"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card bg-light-secondary h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title mb-0">Worker de cola</h6>
                                        <span id="badge-worker"></span>
                                    </div>
                                    <small class="text-muted d-block mb-3"><code class="small">{{ basename($supervisorConfigPath) }}</code></small>
                                    <div id="action-worker" class="d-none"></div>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="col-md-4">
                            <div class="card bg-light-secondary h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title mb-0">Worker de cola</h6>
                                        <span class="status-pill muted"><span class="dot"></span>Manual</span>
                                    </div>
                                    <small class="text-muted d-block mb-3">Requiere configuración manual en Windows</small>
                                    <a href="{{ route('settings.backups.guide') }}" class="btn btn-sm btn-outline-secondary">Ver instrucciones</a>
                                </div>
                            </div>
                        </div>
                        @endif

                    </div>
                </div>

                {{-- Output area --}}
                <div class="card-body border-bottom d-none" id="detail-area">
                    <p class="text-muted fw-semibold mb-2" style="font-size:.7rem; text-transform:uppercase; letter-spacing:.05em;">Salida del sistema</p>
                    <pre id="detail-output" class="bg-light border rounded p-3 small mb-0"
                         style="max-height:150px; overflow-y:auto; white-space:pre-wrap; font-size:.78rem;"></pre>
                </div>

                {{-- Detail tabs --}}
                <div class="card-footer p-0 border-top-0">
                    <ul class="nav nav-tabs px-4 pt-3" id="setupDetailTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-env-btn"
                                    data-bs-toggle="tab" data-bs-target="#tab-env"
                                    type="button" role="tab" aria-selected="true">
                                <i class="fas fa-circle-info me-1"></i>Detalles del entorno
                            </button>
                        </li>
                        @if($os !== 'Windows')
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-config-btn"
                                    data-bs-toggle="tab" data-bs-target="#tab-config"
                                    type="button" role="tab" aria-selected="false">
                                <i class="fas fa-file-lines me-1"></i>Configuración de supervisor
                            </button>
                        </li>
                        @endif
                    </ul>
                    <div class="tab-content px-4 py-3">
                        <div class="tab-pane fade show active" id="tab-env" role="tabpanel">
                            <table class="table table-sm table-borderless mb-0" style="max-width:640px">
                                <tbody>
                                    <tr>
                                        <td class="text-muted ps-0" style="width:120px; font-size:.8rem; white-space:nowrap;">PHP</td>
                                        <td style="font-size:.8rem;">
                                            <span id="env-php-version" class="fw-semibold me-2"></span>
                                            <span id="env-php-badge"></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted ps-0" style="font-size:.8rem; white-space:nowrap;">Binario PHP</td>
                                        <td><code id="env-php-binary" class="small" style="word-break:break-all;"></code></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted ps-0" style="font-size:.8rem; white-space:nowrap;">Cola</td>
                                        <td style="font-size:.8rem;"><span id="env-queue" class="fw-semibold"></span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted ps-0" style="font-size:.8rem; white-space:nowrap;">Artisan</td>
                                        <td><span id="env-artisan"></span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted ps-0" style="font-size:.8rem; white-space:nowrap;">Ruta</td>
                                        <td><code id="env-path" class="small" style="word-break:break-all;"></code></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        @if($os !== 'Windows')
                        <div class="tab-pane fade" id="tab-config" role="tabpanel">
                            <p class="text-muted mb-1" style="font-size:.78rem;">Archivo: <code>{{ $supervisorConfigPath }}</code></p>
                            @php
                                $userLine = $os === 'Linux' ? "\nuser=www-data" : '';
                                $configPreview = implode("\n", [
                                    '[program:backups-worker]',
                                    'process_name=%(program_name)s_%(process_num)02d',
                                    'command="' . $phpBinary . '" "' . $projectPath . '/artisan" queue:work --sleep=3 --tries=3 --max-time=3600',
                                    'autostart=true',
                                    'autorestart=true',
                                    'stopasgroup=true',
                                    'killasgroup=true' . $userLine,
                                    'numprocs=1',
                                    'redirect_stderr=true',
                                    'stdout_logfile=' . $projectPath . '/storage/logs/worker.log',
                                    'stopwaitsecs=3600',
                                ]);
                            @endphp
                            <pre class="bg-light border rounded p-3 mb-0" style="font-size:.75rem;">{{ $configPreview }}</pre>
                        </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>

    @if($os !== 'Windows')
    {{-- Instructions modal: shows copy-paste commands for the admin --}}
    <div class="modal fade" id="modal-instructions" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-semibold" id="modal-instructions-title">Instrucciones</h6>
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

    @if($os !== 'Windows')
    var instructionsModal = new bootstrap.Modal(document.getElementById('modal-instructions'));
    @endif

    function pill(type, label) {
        return '<span class="status-pill ' + type + '"><span class="dot"></span>' + label + '</span>';
    }

    function setHealth(status, title, sub) {
        var icons = { ok: 'fa-check', warn: 'fa-exclamation', danger: 'fa-times', muted: 'fa-circle-notch' };
        $('#health-strip').removeClass('ok warn danger muted').addClass(status);
        $('#health-icon').removeClass('fa-circle-notch fa-spin fa-check fa-exclamation fa-times').addClass(icons[status] || 'fa-circle-notch');
        $('#health-title').text(title);
        $('#health-sub').text(sub);
    }

    @if($os !== 'Windows')
    function showInstructions(title, text, commands) {
        $('#modal-instructions-title').text(title);
        $('#modal-instructions-text').text(text);

        var html = '';
        $.each(commands, function (i, cmd) {
            html += '<div class="input-group mb-2">' +
                '<code class="form-control bg-light small" id="modal-cmd-' + i + '">' + $('<span>').text(cmd).html() + '</code>' +
                '<button class="btn btn-outline-secondary btn-copy-modal" data-idx="' + i + '" type="button">Copiar</button>' +
                '</div>';
        });
        $('#modal-instructions-list').html(html);
        instructionsModal.show();
    }

    $(document).on('click', '.btn-copy-modal', function () {
        var idx = $(this).data('idx');
        var text = $('#modal-cmd-' + idx).text().trim();
        navigator.clipboard.writeText(text).then(function () {
            toastr.success('Copiado al portapapeles');
        }).catch(function () {
            toastr.error('No se pudo copiar');
        });
    });
    @endif

    // ── Status load ───────────────────────────────────────────────────────────

    function loadStatus() {
        $('#status-loading').removeClass('d-none');
        $('#status-content').addClass('d-none');

        var prereqDone = false, supervisorDone = false;
        var prereqData = {}, supervisorData = {};

        function render() {
            if (!prereqDone || !supervisorDone) { return; }

            $('#status-loading').addClass('d-none');
            $('#status-content').removeClass('d-none');

            // Env table
            $('#env-php-version').text(prereqData.php_version || '');
            $('#env-php-binary').text(prereqData.php_binary || '');
            $('#env-queue').text(prereqData.queue_connection || '');
            $('#env-path').text(prereqData.project_path || '');
            $('#env-php-badge').html(prereqData.php_ok ? pill('ok', 'OK') : pill('danger', 'Requiere >= 8.1'));
            $('#env-artisan').html(prereqData.artisan_exists ? pill('ok', 'Encontrado') : pill('danger', 'No encontrado'));

            // Scheduler
            var schedulerOk = prereqData.scheduler_active;
            $('#badge-scheduler').html(pill(schedulerOk ? 'ok' : 'warn', schedulerOk ? 'Activo' : 'No detectado'));

            if (!schedulerOk) {
                @if($os !== 'Windows')
                $('#action-scheduler').html(
                    '<button type="button" class="btn btn-sm btn-outline-secondary" id="btn-cron">' +
                    '<i class="fas fa-terminal me-1"></i>Ver instrucciones cron</button>'
                ).removeClass('d-none');
                @else
                $('#action-scheduler').html(
                    '<a href="{{ route('settings.backups.guide') }}" class="btn btn-sm btn-outline-secondary">Ver instrucciones</a>'
                ).removeClass('d-none');
                @endif
            } else {
                $('#action-scheduler').addClass('d-none');
            }

            @if($os !== 'Windows')
            // Supervisor
            var supOk = supervisorData.supervisor_available;
            $('#badge-supervisor').html(pill(supOk ? 'ok' : 'danger', supOk ? 'Instalado' : 'No instalado'));

            if (!supOk) {
                $('#action-supervisor').html(
                    '<button type="button" class="btn btn-sm btn-outline-secondary" id="btn-install">' +
                    '<i class="fas fa-terminal me-1"></i>Ver instrucciones</button>'
                ).removeClass('d-none');
            } else {
                $('#action-supervisor').addClass('d-none');
            }

            // Worker
            var workerOk     = supervisorData.process_running;
            var configExists = supervisorData.config_exists;

            if (workerOk) {
                $('#badge-worker').html(pill('ok', 'En ejecución'));
                $('#action-worker').html(
                    '<div class="d-flex gap-2">' +
                    '<button type="button" class="btn btn-sm btn-outline-secondary" id="btn-restart"><i class="fas fa-terminal me-1"></i>Ver cómo reiniciar</button>' +
                    '<button type="button" class="btn btn-sm btn-outline-secondary" id="btn-apply"><i class="fas fa-terminal me-1"></i>Ver cómo reconfigurar</button>' +
                    '</div>'
                ).removeClass('d-none');
            } else if (configExists) {
                $('#badge-worker').html(pill('warn', 'Detenido'));
                $('#action-worker').html(
                    '<div class="d-flex gap-2">' +
                    '<button type="button" class="btn btn-sm btn-outline-secondary" id="btn-restart"><i class="fas fa-terminal me-1"></i>Ver cómo iniciar</button>' +
                    '<button type="button" class="btn btn-sm btn-outline-secondary" id="btn-apply"><i class="fas fa-terminal me-1"></i>Ver cómo reconfigurar</button>' +
                    '</div>'
                ).removeClass('d-none');
            } else {
                $('#badge-worker').html(pill('danger', 'Sin configurar'));
                $('#action-worker').html(
                    '<button type="button" class="btn btn-sm btn-outline-secondary" id="btn-apply">' +
                    '<i class="fas fa-terminal me-1"></i>Ver instrucciones</button>'
                ).removeClass('d-none');
            }

            // Overall health
            if (schedulerOk && supOk && workerOk) {
                setHealth('ok', 'Sistema completamente operativo', 'El scheduler, supervisor y worker están funcionando correctamente.');
            } else if (!supOk || !workerOk) {
                setHealth('danger', 'Hay componentes que requieren atención', 'Revisa las tarjetas a continuación y sigue las instrucciones en terminal.');
            } else {
                setHealth('warn', 'Scheduler no configurado', 'Los backups automáticos no se ejecutarán hasta configurar el crontab.');
            }
            @endif
        }

        $.get('{{ route('settings.backups.prerequisites') }}', function (data) {
            prereqData = data; prereqDone = true; render();
        }).fail(function () { prereqDone = true; render(); });

        @if($os !== 'Windows')
        $.get('{{ route('settings.backups.supervisor.status') }}', function (data) {
            supervisorData = data; supervisorDone = true; render();
        }).fail(function () { supervisorDone = true; render(); });
        @else
        supervisorDone = true; render();
        @endif
    }

    // ── Actions (fetch instructions and show in modal) ────────────────────────

    @if($os !== 'Windows')

    $(document).on('click', '#btn-install', function () {
        $.get('{{ route('settings.backups.supervisor.install-instructions') }}', function (res) {
            showInstructions('Instalar Supervisor', res.instructions, res.commands);
        }).fail(function () {
            toastr.error('No se pudieron obtener las instrucciones.');
        });
    });

    $(document).on('click', '#btn-apply', function () {
        $.get('{{ route('settings.backups.supervisor.apply-instructions') }}', function (res) {
            showInstructions('Configurar worker de cola', res.instructions, res.commands);
        }).fail(function () {
            toastr.error('No se pudieron obtener las instrucciones.');
        });
    });

    $(document).on('click', '#btn-restart', function () {
        $.get('{{ route('settings.backups.supervisor.restart-instructions') }}', function (res) {
            showInstructions('Reiniciar worker', res.instructions, res.commands);
        }).fail(function () {
            toastr.error('No se pudieron obtener las instrucciones.');
        });
    });

    $(document).on('click', '#btn-cron', function () {
        $.get('{{ route('settings.backups.scheduler.configure-instructions') }}', function (res) {
            showInstructions('Configurar crontab', res.instructions, [res.cron_line]);
        }).fail(function () {
            toastr.error('No se pudieron obtener las instrucciones.');
        });
    });

    @endif

    loadStatus();
});
</script>
@endpush
