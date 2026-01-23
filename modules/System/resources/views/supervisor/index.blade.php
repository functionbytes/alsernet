@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Panel de Control - Supervisor'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        @if(isset($error))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-circle-exclamation"></i> {{ $error }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- System Settings Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light text-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Panel de Control - Supervisor</h5>
                        <p class="small mb-0 text-black">Gestiona procesos de Supervisor, configuraciones, backups y
                            logs de servicios.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary"
                                onclick="restartSupervisor()" title="Reinicia el servicio Supervisor completo">
                            <i class="fa fa-arrows-rotate me-1"></i> Reiniciar supervisor
                        </button>
                        <button type="button" class="btn btn-secondary"
                                onclick="reloadSupervisor()" title="Recarga la configuración sin detener servicios">
                            <i class="fa fa-repeat me-1"></i> Recargar config
                        </button>
                        <button type="button" class="btn btn-primary"
                                onclick="refreshStatus()" title="Actualiza el estado en tiempo real">
                            <i class="fa fa-arrows-rotate me-1"></i> Actualizar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Status Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body position-relative">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="card-title mb-2">Total de procesos</h6>
                                        <h2 id="totalProcesses" class="text-success"
                                            style="font-weight: 700;">{{ count($processes) }}</h2>
                                        <small class="text-muted">Procesos registrados en Supervisor</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body position-relative">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="card-title mb-2">Procesos activos</h6>
                                        <h2 id="runningProcesses" class="text-success"
                                            style="font-weight: 700;">{{ collect($processes)->filter(fn($p) => ($p['state'] ?? '') === 'RUNNING')->count() }}</h2>
                                        <small class="text-muted">Procesos en estado RUNNING</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body position-relative">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="card-title mb-2">Procesos detenidos</h6>
                                        <h2 id="stoppedProcesses" class="text-success"
                                            style="font-weight: 700;">{{ collect($processes)->filter(fn($p) => ($p['state'] ?? '') === 'STOPPED')->count() }}</h2>
                                        <small class="text-muted">Procesos detenidos o parados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body position-relative">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="card-title mb-2">Procesos Alsernet</h6>
                                        <h2 id="alsarnetCount" class="text-success"
                                            style="font-weight: 700;">{{ count($alsarnetProcesses) }}</h2>
                                        <small class="text-muted">Procesos de la aplicación</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Pills -->
            <ul class="nav nav-pills user-profile-tab" id="supervisor-settings-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 active"
                            id="processes-tab"
                            data-bs-toggle="pill"
                            data-bs-target="#processes"
                            type="button"
                            role="tab"
                            aria-controls="processes"
                            aria-selected="true">
                        <i class="fa fa-person-running me-2"></i>
                        <span class="d-none d-md-block">Procesos</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                            id="backups-tab"
                            data-bs-toggle="pill"
                            data-bs-target="#backups"
                            type="button"
                            role="tab"
                            aria-controls="backups"
                            aria-selected="false">
                        <i class="fa fa-database me-2"></i>
                        <span class="d-none d-md-block">Backups</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                            id="config-tab"
                            data-bs-toggle="pill"
                            data-bs-target="#config"
                            type="button"
                            role="tab"
                            aria-controls="config"
                            aria-selected="false">
                        <i class="fa fa-gear me-2"></i>
                        <span class="d-none d-md-block">Configuración</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                            id="logs-tab"
                            data-bs-toggle="pill"
                            data-bs-target="#logs"
                            type="button"
                            role="tab"
                            aria-controls="logs"
                            aria-selected="false">
                        <i class="fa fa-list me-2"></i>
                        <span class="d-none d-md-block">Logs</span>
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="card-body">
                <div class="tab-content" id="supervisor-settings-content">

                    <!-- PROCESSES TAB -->
                    <div role="tabpanel" class="tab-pane fade show active" id="processes">
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="mb-4 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1 fw-bold">Gestión de procesos</h6>
                                        <p class="text-muted small mb-0">Administra todos los procesos de Supervisor en
                                            una lista unificada</p>
                                    </div>
                                    <div class="d-flex gap-2 align-items-center">
                                        <label class="text-muted small mb-0 me-2">Filtrar:</label>
                                        <select class="form-select form-select-sm" id="processTypeFilter"
                                                onchange="filterProcesses()" style="width: auto;">
                                            <option value="all">Todos los procesos</option>
                                            <option value="alsernet">Solo Alsernet</option>
                                            <option value="system">Solo Sistema</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Info Panel -->
                                <div class="alert alert-info border-0 bg-info-subtle d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex gap-4 align-items-center">
                                        <div>
                                            <span class="badge bg-white text-primary me-2">
                                                <i class="fa fa-person-running me-1"></i> Alsernet
                                            </span>
                                            <small class="text-muted">Puedes iniciar, detener y reiniciar estos
                                                procesos</small>
                                        </div>
                                        <div class="vr"></div>
                                        <div>
                                            <span class="badge bg-white text-secondary me-2">
                                                <i class="fa fa-gear me-1"></i> Sistema
                                            </span>
                                            <small class="text-muted">Solo lectura</small>
                                        </div>
                                    </div>
                                    <div>
                                        <small class="text-muted">
                                            <i class="fa fa-database me-1"></i>
                                            <strong id="processCount">{{ count($processes) }}</strong> procesos totales
                                        </small>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover" id="processesTable">
                                        <thead class="table-light">
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Nombre del proceso</th>
                                            <th>Estado</th>
                                            <th>PID</th>
                                            <th>Uptime</th>
                                            <th>Acciones</th>
                                        </tr>
                                        </thead>
                                        <tbody id="processesTableBody">
                                        @forelse($processes as $process)
                                            @php
                                                // Determinar si es proceso de Alsernet
                                                $isAlsernet = strpos($process['name'], 'Alsernet') !== false;
                                                $processType = $isAlsernet ? 'alsernet' : 'system';

                                                // Parse state
                                                $state = $process['state'] ?? 'UNKNOWN';
                                                $badgeClass = 'bg-success';
                                                if ($state === 'STOPPED') $badgeClass = 'bg-danger';
                                                elseif ($state === 'STOPPING') $badgeClass = 'bg-warning';
                                                elseif ($state === 'STARTING') $badgeClass = 'bg-info';
                                                elseif ($state === 'BACKOFF') $badgeClass = 'bg-warning';
                                                elseif ($state === 'FATAL') $badgeClass = 'bg-danger';
                                                elseif ($state === 'EXITED') $badgeClass = 'bg-secondary';

                                                // Parse PID and uptime
                                                preg_match('/pid (\d+)/', $process['details'] ?? '', $pidMatch);
                                                preg_match('/uptime ([\d:]+)/', $process['details'] ?? '', $uptimeMatch);
                                                $pid = $pidMatch[1] ?? '-';
                                                $uptime = $uptimeMatch[1] ?? '-';
                                            @endphp
                                            <tr class="process-row" data-process-type="{{ $processType }}"
                                                id="row-{{ str_replace(':', '-', $process['name']) }}">
                                                <td>
                                                    @if($isAlsernet)
                                                        <span class="badge bg-primary-subtle text-primary">
                                                            <i class="fa fa-person-running me-1"></i> Alsernet
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary-subtle text-secondary">
                                                            <i class="fa fa-gear me-1"></i> Sistema
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($isAlsernet)
                                                        <strong>{{ $process['name'] }}</strong>
                                                    @else
                                                        {{ $process['name'] }}
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge {{ $badgeClass }}">{{ $state }}</span>
                                                </td>
                                                <td>{{ $pid }}</td>
                                                <td>{{ $uptime }}</td>
                                                <td>
                                                    @if($isAlsernet)
                                                        <!-- Acciones para procesos Alsernet -->
                                                        <div class="btn-group" role="group">
                                                            @if($state === 'RUNNING')
                                                                <button type="button"
                                                                        class="btn btn-sm btn-warning waves-effect"
                                                                        onclick="stopProcess('{{ $process['name'] }}')">
                                                                    <i class="fa fa-stop"></i> Detener
                                                                </button>
                                                                <button type="button"
                                                                        class="btn btn-sm btn-info waves-effect"
                                                                        onclick="restartProcess('{{ $process['name'] }}')">
                                                                    <i class="fa fa-arrows-rotate"></i> Reiniciar
                                                                </button>
                                                            @else
                                                                <button type="button"
                                                                        class="btn btn-sm btn-success waves-effect"
                                                                        onclick="startProcess('{{ $process['name'] }}')">
                                                                    <i class="fa fa-play"></i> Iniciar
                                                                </button>
                                                            @endif
                                                            <a href="{{ route('settings.system.supervisor.show', ['processName' => $process['name']]) }}"
                                                               class="btn btn-sm btn-outline-primary waves-effect">
                                                                <i class="fa fa-eye"></i> Detalles
                                                            </a>
                                                        </div>
                                                    @else
                                                        <!-- Solo lectura para procesos del sistema -->
                                                        <small class="text-muted">
                                                            <i class="fa fa-lock me-1"></i> Solo lectura
                                                        </small>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">
                                                    <i class="fa fa-circle-exclamation"></i> No hay procesos disponibles
                                                </td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- BACKUPS TAB -->
                    <div role="tabpanel" class="tab-pane fade" id="backups">
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="mb-4">
                                    <h6 class="mb-1 fw-bold">Gestión de backups</h6>
                                    <p class="text-muted small">Crea y administra backups de configuraciones de
                                        Supervisor para restauración rápida.</p>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="mb-3 fw-semibold">Crear nuevo backup</h6>
                                                <form id="backupForm" onsubmit="createBackup(event)">
                                                    @csrf
                                                    <div class="mb-3">
                                                        <label for="backupName" class="form-label fw-semibold">Nombre
                                                            del backup <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" id="backupName"
                                                               name="name" required
                                                               placeholder="ej: Backup Producción 2024">
                                                        <small class="text-muted">Nombre descriptivo para identificar el
                                                            backup</small>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="backupDesc" class="form-label fw-semibold">Descripción</label>
                                                        <textarea class="form-control" id="backupDesc"
                                                                  name="description" rows="2"
                                                                  placeholder="Notas sobre este backup..."></textarea>
                                                        <small class="text-muted">Opcional: Detalles adicionales sobre
                                                            este backup</small>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="backupEnv" class="form-label fw-semibold">Ambiente
                                                            <span class="text-danger">*</span></label>
                                                        <select class="form-select" id="backupEnv" name="environment"
                                                                required>
                                                            <option value="dev">Desarrollo</option>
                                                            <option value="prod" selected>Producción</option>
                                                            <option value="staging">Staging</option>
                                                        </select>
                                                        <small class="text-muted">Ambiente al que pertenece este
                                                            backup</small>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary w-100">
                                                        Crear backup
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="mb-3 fw-semibold">Filtros de búsqueda</h6>
                                                <div class="mb-3">
                                                    <label for="filterEnv" class="form-label fw-semibold">Filtrar por
                                                        ambiente</label>
                                                    <select class="form-select" id="filterEnv" onchange="loadBackups()">
                                                        <option value="">Todos los ambientes</option>
                                                        <option value="dev">Desarrollo</option>
                                                        <option value="prod">Producción</option>
                                                        <option value="staging">Staging</option>
                                                    </select>
                                                    <small class="text-muted">Filtra los backups por ambiente</small>
                                                </div>

                                                <div class="alert alert-info border-0 bg-info-subtle text-info mb-0">
                                                    <div class="d-flex align-items-start gap-2">
                                                        <i class="fa fa-circle-info fs-5"></i>
                                                        <div>
                                                            <strong>Importante:</strong> Los backups se almacenan en el
                                                            servidor. Descarga copias locales periódicamente.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <h6 class="mb-3 mt-4 fw-semibold">Backups disponibles</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Ambiente</th>
                                            <th>Tamaño</th>
                                            <th>Fecha</th>
                                            <th>Restaurado</th>
                                            <th>Acciones</th>
                                        </tr>
                                        </thead>
                                        <tbody id="backupsTableBody">
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">
                                                <i class="fa fa-spinner fa-spin"></i> Cargando backups...
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CONFIG TAB -->
                    <div role="tabpanel" class="tab-pane fade" id="config">
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="mb-4">
                                    <h6 class="mb-1 fw-bold">Editor de configuración</h6>
                                    <p class="text-muted small">Edita los archivos de configuración de Supervisor
                                        directamente desde el panel de administración.</p>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="mb-0 fw-semibold">Archivos de configuración</h6>
                                            </div>
                                            <div class="list-group list-group-flush" id="configFilesList">
                                                <a href="#" class="list-group-item list-group-item-action">
                                                    <i class="fa fa-spinner fa-spin"></i> Cargando archivos...
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <div class="card">
                                            <div class="card-body">
                                                <div id="configEditorContainer"
                                                     class="alert alert-info border-0 bg-info-subtle text-info">
                                                    <div class="d-flex align-items-start gap-2">
                                                        <i class="fa fa-circle-info fs-5"></i>
                                                        <div>
                                                            <strong>Selecciona un archivo</strong><br>
                                                            Haz clic en un archivo de la lista para editarlo
                                                        </div>
                                                    </div>
                                                </div>
                                                <div id="configEditor" style="display:none;">
                                                    <form id="configForm" onsubmit="updateConfigFile(event)">
                                                        @csrf
                                                        <input type="hidden" id="configFilePath" name="file">
                                                        <div class="mb-3">
                                                            <label for="configContent" class="form-label fw-semibold">Contenido
                                                                del archivo</label>
                                                            <textarea class="form-control" id="configContent"
                                                                      name="content" rows="15"
                                                                      style="font-family: monospace; font-size: 13px;"></textarea>
                                                            <small class="text-muted">Edita el contenido y guarda los
                                                                cambios</small>
                                                        </div>
                                                        <div class="row">
                                                            <button type="submit" class="btn btn-primary w-100 mb-2">
                                                                Guardar
                                                            </button>
                                                            <button type="button" class="btn btn-secondary w-100"
                                                                    onclick="closeConfigEditor()">
                                                                Cerrar
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- LOGS TAB -->
                    <div role="tabpanel" class="tab-pane fade" id="logs">
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="mb-4">
                                    <h6 class="mb-1 fw-bold">Visualizador de logs</h6>
                                    <p class="text-muted small">Consulta los registros de actividad de los procesos de
                                        Supervisor en tiempo real.</p>
                                </div>

                                <div class="row">
                                    <!-- LEFT SIDEBAR: Controls -->
                                    <div class="col-md-12 mb-3">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="mb-3 fw-semibold">Configuración</h6>

                                                <!-- Process selector -->
                                                <div class="mb-3">
                                                    <label for="processSelect" class="form-label fw-semibold">Proceso
                                                        <span class="text-danger">*</span></label>
                                                    <select class="form-select" id="processSelect"
                                                            onchange="loadProcessLogs()">
                                                        <option value="">Selecciona un proceso</option>
                                                        @foreach($alsarnetProcesses as $process)
                                                            <option value="{{ $process['name'] }}">{{ $process['name'] }}</option>
                                                        @endforeach
                                                    </select>
                                                    <small class="text-muted">Proceso a monitorear</small>
                                                </div>

                                                <!-- Lines limit -->
                                                <div class="mb-3">
                                                    <label for="logLines" class="form-label fw-semibold">Líneas</label>
                                                    <select class="form-select" id="logLines"
                                                            onchange="loadProcessLogs()">
                                                        <option value="50" selected>50 líneas</option>
                                                        <option value="100">100 líneas</option>
                                                        <option value="200">200 líneas</option>
                                                        <option value="500">500 líneas</option>
                                                        <option value="1000">1000 líneas</option>
                                                    </select>
                                                    <small class="text-muted">Cantidad de líneas a mostrar</small>
                                                </div>

                                                <!-- Auto-scroll toggle -->
                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="autoScroll"
                                                               checked>
                                                        <label class="form-check-label fw-semibold" for="autoScroll">
                                                            Auto-scroll
                                                        </label>
                                                    </div>
                                                    <small class="text-muted">Desplazar automáticamente al final</small>
                                                </div>

                                                <!-- Show line numbers -->
                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox"
                                                               id="showLineNumbers">
                                                        <label class="form-check-label fw-semibold"
                                                               for="showLineNumbers">
                                                            Números de línea
                                                        </label>
                                                    </div>
                                                    <small class="text-muted">Mostrar numeración</small>
                                                </div>

                                                <hr>

                                                <!-- Action buttons -->
                                                <div class="d-grid gap-2">
                                                    <button type="button" class="btn  btn-primary"
                                                            onclick="loadProcessLogs()" id="refreshLogsBtn" disabled>
                                                        <i class="fa fa-arrows-rotate me-1"></i> Refrescar
                                                    </button>
                                                    <button type="button" class="btn  btn-primary"
                                                            onclick="downloadLogs()" id="downloadLogsBtn" disabled>
                                                        <i class="fa fa-download me-1"></i> Descargar
                                                    </button>
                                                    <button type="button" class="btn btn-secondary"
                                                            onclick="clearLogs()">
                                                        <i class="fa fa-broom me-1"></i> Limpiar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- RIGHT: Log viewer -->
                                    <div class="col-md-12 mb-3">
                                        <div class="card  ">
                                            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-0 fw-semibold ">
                                                        <i class="fa fa-terminal me-2"></i>
                                                        <span id="logProcessName">Salida de logs</span>
                                                    </h6>
                                                </div>
                                                <div class="d-flex gap-2 align-items-center">
                                                    <small class="-50" id="logLineCount">0 líneas</small>
                                                    <input type="text"
                                                           class="form-control form-control-sm   border-secondary"
                                                           id="logSearch" placeholder="Buscar..."
                                                           onkeyup="filterLogs()">
                                                </div>
                                            </div>
                                            <div class="card-body p-0">
                                                <div id="logsContentWrapper"
                                                     style="position: relative; max-height: 600px; overflow-y: auto; background-color: #1a1a1a;">
                                                    <pre id="logsContent" class=" p-3 mb-0"
                                                         style="color:#fff; margin: 0; font-size: 12px; font-family: 'Courier New', monospace; line-height: 1.5;"><i
                                                                class="fa fa-circle-info"></i> Selecciona un proceso para ver sus logs</pre>
                                                </div>
                                            </div>
                                            <div class="card-footer border-secondary d-flex justify-content-between align-items-center">
                                                <small class="-50">
                                                    <i class="fa fa-circle text-success me-1" id="logStatus"></i>
                                                    <span id="logStatusText">Esperando selección</span>
                                                </small>
                                                <small class="-50" id="logLastUpdate">--</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>
            </div>
        </div>

    </div>

@endsection

<!-- Reload Supervisor Modal -->
<div class="modal fade" id="reloadSupervisorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    Recargar configuración
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div>
                        <h6 class="fw-bold mb-2">¿Recargar la configuración de Supervisor?</h6>
                        <p class="text-muted mb-0">
                            Esta acción recargará la configuración de Supervisor. Los procesos pueden interrumpirse
                            brevemente durante este proceso.
                        </p>
                    </div>
                </div>
                <div class="alert alert-info border-0 bg-info-subtle mb-0">
                    <small class="text-info">
                        <i class="fa fa-circle-info me-1"></i>
                        <strong>Nota:</strong> Los procesos en ejecución continuarán funcionando, pero la nueva
                        configuración se aplicará.
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary w-100 mb-1" id="confirmReloadBtn">
                    <i class="fa fa-repeat me-1"></i> Sí, recargar ahora
                </button>
                <button type="button" class="btn btn-secondary  w-100" data-bs-dismiss="modal">
                    <i class="fa fa-xmark me-1"></i> Cancelar
                </button>

            </div>
        </div>
    </div>
</div>

<!-- Restart Supervisor Modal -->
<div class="modal fade" id="restartSupervisorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header ">
                <h5 class="modal-title">
                    Reiniciar servicio completo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div>
                        <h6 class="fw-bold mb-2">Esto reiniciará TODO el servicio Supervisor</h6>
                        <p class="text-muted mb-0">
                            Esta acción detendrá y reiniciará completamente el servicio Supervisor. <strong>Todos los
                                procesos administrados se detendrán temporalmente.</strong>
                        </p>
                    </div>
                </div>
                <div class="alert alert-info border-0  mb-0">
                    <small class="text-info">
                        <i class="fa fa-circle-exclamation me-1"></i>
                        <strong>Advertencia:</strong> Esta operación afectará a todos los servicios y procesos
                        supervisados. Use solo si es absolutamente necesario.
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary w-100 mb-1" id="confirmRestartBtn">
                    <i class="fa fa-arrows-rotate me-1"></i> Sí, reiniciar ahora
                </button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">
                    <i class="fa fa-xmark me-1"></i> Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        // Global variable to control auto-refresh
        var supervisorAutoRefresh = null;
        var sudoErrorShown = false;

        $(document).ready(function () {
            // Toastr configuration
            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: "toast-bottom-right",
                timeOut: 0,
                extendedTimeOut: 0,
                tapToDismiss: false
            };

            // Start auto-refresh (every 5 seconds)
            startAutoRefresh();

            // Load backups when backups tab is clicked
            $('#backups-tab').on('click', function () {
                loadBackups();
            });

            // Load config files when config tab is clicked
            $('#config-tab').on('click', function () {
                loadConfigFiles();
            });

            // Initial load
            refreshStatus();
        });

        function startAutoRefresh() {
            if (supervisorAutoRefresh) {
                clearInterval(supervisorAutoRefresh);
            }
            supervisorAutoRefresh = setInterval(refreshStatus, 5000);
        }

        function stopAutoRefresh() {
            if (supervisorAutoRefresh) {
                clearInterval(supervisorAutoRefresh);
                supervisorAutoRefresh = null;
            }
        }

        function refreshStatus() {
            $.ajax({
                url: '{{ route("settings.system.supervisor.status-ajax") }}',
                type: 'GET',
                dataType: 'json',
                success: function (data) {
                    if (data.success) {
                        updateStatusCards(data);
                        // Reset error flag if it was previously set
                        sudoErrorShown = false;
                    } else {
                        handleSudoError(data.message);
                    }
                },
                error: function (xhr, status, error) {
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        handleSudoError(xhr.responseJSON.message);
                    } else {
                        console.error('AJAX error:', error);
                    }
                }
            });
        }

        function handleSudoError(message) {
            // Check if it's a sudo permission error
            if (message && (message.includes('sudo') || message.includes('password'))) {
                // Only show the alert once
                if (!sudoErrorShown) {
                    sudoErrorShown = true;
                    stopAutoRefresh();

                    // Show persistent alert with action button
                    var alertHtml = '<div class="row justify-content-center mb-0" id="sudoErrorAlert">';
                    alertHtml += '    <div class="col-12">';
                    alertHtml += '        <div class="alert alert-dismissible fade show border-0 bg-warning-subtle" role="alert">';
                    alertHtml += '            <div class="d-flex align-items-center gap-3">';
                    alertHtml += '                <i class="fa fa-triangle-exclamation text-warning fs-9"></i>';
                    alertHtml += '                <div class="flex-grow-1">';
                    alertHtml += '                    <h6 class="alert-heading fw-bold text-warning mb-0">Configuración de Supervisor requerida</h6>';
                    alertHtml += '                    <p class="mb-2 text-warning small mb-0 ">El servidor necesita configuración de passwordless sudo para ejecutar comandos de Supervisor.</p>';
                    alertHtml += '                    <p class="mb-3 text-warning small mt-0 mb-0"><strong>Error:</strong> ' + message + '</p>';
                    alertHtml += '                    <div class="d-flex gap-2 ">';
                    alertHtml += '                        <button type="button" class="btn btn-sm btn-primary" onclick="showSudoInstructions()">';
                    alertHtml += '                            <i class="fa fa-circle-info me-1"></i> Ver instrucciones';
                    alertHtml += '                        </button>';
                    alertHtml += '                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="retryConnection()">';
                    alertHtml += '                            <i class="fa fa-arrows-rotate me-1"></i> Reintentar';
                    alertHtml += '                        </button>';
                    alertHtml += '                    </div>';
                    alertHtml += '                </div>';
                    alertHtml += '                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                    alertHtml += '            </div>';
                    alertHtml += '        </div>';
                    alertHtml += '    </div>';
                    alertHtml += '</div>';

                    // Insert alert before the widget-content searchable-container list
                    $('.widget-content.searchable-container.list').before(alertHtml);

                    console.error('Supervisor sudo error:', message);
                }
                return true; // Error was handled
            }
            return false; // Not a sudo error
        }

        function retryConnection() {
            // Remove the alert
            $('#sudoErrorAlert').remove();
            sudoErrorShown = false;

            // Retry status check
            refreshStatus();

            // Restart auto-refresh
            startAutoRefresh();

            toastr.info('Reintentando conexión...', 'Supervisor');
        }

        function showSudoInstructions() {
            var modalHtml = '<div class="modal fade" id="sudoInstructionsModal" tabindex="-1">';
            modalHtml += '    <div class="modal-dialog modal-lg modal-dialog-centered">';
            modalHtml += '        <div class="modal-content ">';
            modalHtml += '            <div class="modal-header bg-warning-subtle">';
            modalHtml += '                <h5 class="modal-title">Configuración de sudo passwordless</h5>';
            modalHtml += '                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>';
            modalHtml += '            </div>';
            modalHtml += '            <div class="modal-body">';
            modalHtml += '                <h6 class="mb-3">Paso 1: Identificar el usuario web</h6>';
            modalHtml += '                <pre class="bg-light-secondary p-3 rounded"><code>ps aux | grep -E \'(apache|nginx|php-fpm)\' | head -n 1</code></pre>';
            modalHtml += '                <h6 class="mb-3 mt-4">Paso 2: Configurar sudoers</h6>';
            modalHtml += '                <pre class="bg-light-secondary p-3 rounded"><code>sudo visudo -f /etc/sudoers.d/supervisor-web</code></pre>';
            modalHtml += '                <h6 class="mb-3 mt-4">Paso 3: Agregar estas líneas (reemplaza www-data con tu usuario web):</h6>';
            modalHtml += '                <pre class="bg-light-secondary p-3 rounded"><code># Allow web user to run supervisorctl without password\n';
            modalHtml += 'www-data ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl\n';
            modalHtml += 'www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl restart supervisor\n';
            modalHtml += 'www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl status supervisor</code></pre>';
            modalHtml += '                <h6 class="mb-3 mt-4">Paso 4: Verificar permisos</h6>';
            modalHtml += '                <pre class="bg-light-secondary p-3 rounded"><code>sudo chmod 0440 /etc/sudoers.d/supervisor-web</code></pre>';
            modalHtml += '                <div class="alert alert-info border-0 bg-info-subtle text-info mt-4">';
            modalHtml += '                    <i class="fa fa-circle-info me-2"></i>';
            modalHtml += '                    <strong>Nota:</strong> Para más detalles, consulta: <code>docs/devops/supervisor-sudo-setup.md</code>';
            modalHtml += '                </div>';
            modalHtml += '            </div>';
            modalHtml += '            <div class="modal-footer">';
            modalHtml += '                <button type="button" class="btn btn-primary w-100 mb-1" data-bs-dismiss="modal" onclick="retryConnection()">';
            modalHtml += '                    <i class="fa fa-arrows-rotate me-1"></i> Reintentar conexión';
            modalHtml += '                </button>';
            modalHtml += '                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cerrar</button>';
            modalHtml += '            </div>';
            modalHtml += '        </div>';
            modalHtml += '    </div>';
            modalHtml += '</div>';

            // Remove existing modal if any
            $('#sudoInstructionsModal').remove();

            // Add modal to body and show
            $('body').append(modalHtml);
            var modal = new bootstrap.Modal(document.getElementById('sudoInstructionsModal'));
            modal.show();

            // Clean up when modal is hidden
            $('#sudoInstructionsModal').on('hidden.bs.modal', function () {
                $(this).remove();
            });
        }

        function filterProcesses() {
            var filterValue = $('#processTypeFilter').val();
            var visibleCount = 0;

            $('.process-row').each(function () {
                var processType = $(this).data('process-type');

                if (filterValue === 'all') {
                    $(this).show();
                    visibleCount++;
                } else if (processType === filterValue) {
                    $(this).show();
                    visibleCount++;
                } else {
                    $(this).hide();
                }
            });

            // Update count
            $('#processCount').text(visibleCount);
        }

        function updateStatusCards(data) {
            var allProcesses = data.processes || [];
            var alsarnetProcesses = data.alsarnetProcesses || [];
            var running = allProcesses.filter(function (p) {
                return (p.state || '').toUpperCase() === 'RUNNING';
            }).length;
            var stopped = allProcesses.filter(function (p) {
                return (p.state || '').toUpperCase() === 'STOPPED';
            }).length;

            $('#totalProcesses').text(allProcesses.length);
            $('#runningProcesses').text(running);
            $('#stoppedProcesses').text(stopped);
            $('#alsarnetCount').text(alsarnetProcesses.length);
        }

        function startProcess(processName) {
            if (confirm('¿Iniciar el proceso "' + processName + '"?')) {
                $.ajax({
                    url: '{{ route("settings.system.supervisor.start", ["processName" => ":processName"]) }}'.replace(':processName', processName),
                    type: 'POST',
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (data) {
                        if (data.success) {
                            toastr.success(data.message, 'Proceso iniciado');
                            refreshStatus();
                        } else {
                            if (!handleSudoError(data.message)) {
                                toastr.error(data.message, 'Error');
                            }
                        }
                    },
                    error: function (xhr, status, error) {
                        var errorMsg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : error;
                        if (!handleSudoError(errorMsg)) {
                            toastr.error('Error: ' + error, 'Error');
                        }
                    }
                });
            }
        }

        function stopProcess(processName) {
            if (confirm('¿Detener el proceso "' + processName + '"?')) {
                $.ajax({
                    url: '{{ route("settings.system.supervisor.stop", ["processName" => ":processName"]) }}'.replace(':processName', processName),
                    type: 'POST',
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (data) {
                        if (data.success) {
                            toastr.success(data.message, 'Proceso detenido');
                            refreshStatus();
                        } else {
                            if (!handleSudoError(data.message)) {
                                toastr.error(data.message, 'Error');
                            }
                        }
                    },
                    error: function (xhr, status, error) {
                        var errorMsg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : error;
                        if (!handleSudoError(errorMsg)) {
                            toastr.error('Error: ' + error, 'Error');
                        }
                    }
                });
            }
        }

        function restartProcess(processName) {
            if (confirm('¿Reiniciar el proceso "' + processName + '"?')) {
                $.ajax({
                    url: '{{ route("settings.system.supervisor.restart", ["processName" => ":processName"]) }}'.replace(':processName', processName),
                    type: 'POST',
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (data) {
                        if (data.success) {
                            toastr.success(data.message, 'Proceso reiniciado');
                            refreshStatus();
                        } else {
                            if (!handleSudoError(data.message)) {
                                toastr.error(data.message, 'Error');
                            }
                        }
                    },
                    error: function (xhr, status, error) {
                        var errorMsg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : error;
                        if (!handleSudoError(errorMsg)) {
                            toastr.error('Error: ' + error, 'Error');
                        }
                    }
                });
            }
        }

        function reloadSupervisor() {
            // Show modal instead of confirm()
            var modal = new bootstrap.Modal(document.getElementById('reloadSupervisorModal'));
            modal.show();
        }

        // Reload confirmation handler
        $('#confirmReloadBtn').on('click', function () {
            var modal = bootstrap.Modal.getInstance(document.getElementById('reloadSupervisorModal'));
            modal.hide();

            $.ajax({
                url: '{{ route("settings.system.supervisor.reload") }}',
                type: 'POST',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (data) {
                    if (data.success) {
                        toastr.success(data.message, 'Configuración recargada');
                        setTimeout(function () {
                            refreshStatus();
                        }, 2000);
                    } else {
                        if (!handleSudoError(data.message)) {
                            toastr.error(data.message, 'Error');
                        }
                    }
                },
                error: function (xhr, status, error) {
                    var errorMsg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : error;
                    if (!handleSudoError(errorMsg)) {
                        toastr.error('Error: ' + error, 'Error');
                    }
                }
            });
        });

        function restartSupervisor() {
            // Show modal instead of confirm()
            var modal = new bootstrap.Modal(document.getElementById('restartSupervisorModal'));
            modal.show();
        }

        // Restart confirmation handler
        $('#confirmRestartBtn').on('click', function () {
            var modal = bootstrap.Modal.getInstance(document.getElementById('restartSupervisorModal'));
            modal.hide();

            $.ajax({
                url: '{{ route("settings.system.supervisor.restart-service") }}',
                type: 'POST',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (data) {
                    if (data.success) {
                        toastr.success(data.message, 'Supervisor reiniciado');
                        setTimeout(function () {
                            refreshStatus();
                        }, 3000);
                    } else {
                        if (!handleSudoError(data.message)) {
                            toastr.error(data.message, 'Error');
                        }
                    }
                },
                error: function (xhr, status, error) {
                    var errorMsg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : error;
                    if (!handleSudoError(errorMsg)) {
                        toastr.error('Error: ' + error, 'Error');
                    }
                }
            });
        });

        function createBackup(event) {
            event.preventDefault();
            var formData = $('#backupForm').serialize();

            $.ajax({
                url: '{{ route("settings.system.supervisor.backup-create") }}',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function (data) {
                    if (data.success) {
                        toastr.success(data.message, 'Backup creado');
                        $('#backupForm')[0].reset();
                        loadBackups();
                    } else {
                        toastr.error(data.message, 'Error');
                    }
                },
                error: function (xhr, status, error) {
                    toastr.error('Error: ' + error, 'Error');
                }
            });
        }

        function loadBackups() {
            var env = $('#filterEnv').val();
            var url = '{{ route("settings.system.supervisor.backups-list") }}';
            if (env) url += '?environment=' + env;

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                success: function (data) {
                    var tbody = $('#backupsTableBody');
                    if (data.backups && data.backups.length > 0) {
                        var html = '';
                        $.each(data.backups, function (index, b) {
                            var downloadUrl = '{{ route("settings.system.supervisor.backup-download", ["backupId" => ":id"]) }}'.replace(':id', b.id);

                            html += '<tr>';
                            html += '<td><strong>' + b.name + '</strong><br><small class="text-muted">' + (b.description || '') + '</small></td>';
                            html += '<td><span class="badge bg-primary">' + b.environment + '</span></td>';
                            html += '<td>' + b.backup_size + '</td>';
                            html += '<td>' + b.backed_up_at + '<br><small class="text-muted">' + b.relative_time + '</small></td>';
                            html += '<td>' + (b.restored_at ? b.restored_at : '<span class="text-muted">-</span>') + '</td>';
                            html += '<td>';
                            html += '    <div class="dropdown dropstart">';
                            html += '        <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">';
                            html += '            <i class="fa fa-ellipsis"></i>';
                            html += '        </a>';
                            html += '        <ul class="dropdown-menu">';
                            html += '            <li>';
                            html += '                <a href="javascript:void(0)" class="dropdown-item" onclick="restoreBackup(' + b.id + ')">';
                            html += '                    Restaurar';
                            html += '                </a>';
                            html += '            </li>';
                            html += '            <li>';
                            html += '                <a href="' + downloadUrl + '" class="dropdown-item">';
                            html += '                    Descargar';
                            html += '                </a>';
                            html += '            </li>';
                            html += '            <li><hr class="dropdown-divider"></li>';
                            html += '            <li>';
                            html += '                <a href="javascript:void(0)" class="dropdown-item text-black" onclick="deleteBackup(' + b.id + ')">';
                            html += '                    Eliminar';
                            html += '                </a>';
                            html += '            </li>';
                            html += '        </ul>';
                            html += '    </div>';
                            html += '</td>';
                            html += '</tr>';
                        });
                        tbody.html(html);
                    } else {
                        tbody.html('<tr><td colspan="6" class="text-center text-muted">No hay backups disponibles</td></tr>');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error loading backups:', error);
                }
            });
        }

        function restoreBackup(backupId) {
            if (confirm('⚠️ Restaurar un backup sobrescribirá la configuración actual. ¿Continuar?')) {
                $.ajax({
                    url: '{{ route("settings.system.supervisor.backup-restore", ["backupId" => ":id"]) }}'.replace(':id', backupId),
                    type: 'POST',
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (data) {
                        if (data.success) {
                            toastr.success(data.message, 'Backup restaurado');
                            loadBackups();
                        } else {
                            toastr.error(data.message, 'Error');
                        }
                    },
                    error: function (xhr, status, error) {
                        toastr.error('Error: ' + error, 'Error');
                    }
                });
            }
        }

        function deleteBackup(backupId) {
            if (confirm('¿Eliminar este backup?')) {
                $.ajax({
                    url: '{{ route("settings.system.supervisor.backup-delete", ["backupId" => ":id"]) }}'.replace(':id', backupId),
                    type: 'DELETE',
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (data) {
                        if (data.success) {
                            toastr.success(data.message, 'Backup eliminado');
                            loadBackups();
                        } else {
                            toastr.error(data.message, 'Error');
                        }
                    },
                    error: function (xhr, status, error) {
                        toastr.error('Error: ' + error, 'Error');
                    }
                });
            }
        }

        function loadConfigFiles() {
            $.ajax({
                url: '{{ route("settings.system.supervisor.config-files-list") }}',
                type: 'GET',
                dataType: 'json',
                success: function (data) {
                    var list = $('#configFilesList');
                    if (data.files && data.files.length > 0) {
                        var html = '';
                        $.each(data.files, function (index, file) {
                            html += '<a href="#" class="list-group-item list-group-item-action" onclick="loadConfigFile(\'' + file + '\', event)">';
                            html += file;
                            html += '</a>';
                        });
                        list.html(html);
                    } else {
                        list.html('<a class="list-group-item list-group-item-action disabled">No hay archivos de configuración</a>');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error loading config files:', error);
                }
            });
        }

        function loadConfigFile(filePath, event) {
            event.preventDefault();

            $.ajax({
                url: '{{ route("settings.system.supervisor.config-file-get") }}',
                type: 'GET',
                data: {file: filePath},
                dataType: 'json',
                success: function (data) {
                    if (data.success) {
                        $('#configFilePath').val(data.file);
                        $('#configContent').val(data.content);
                        $('#configEditorContainer').hide();
                        $('#configEditor').show();
                    } else {
                        toastr.error(data.error || 'Error al cargar archivo', 'Error');
                    }
                },
                error: function (xhr, status, error) {
                    toastr.error('Error: ' + error, 'Error');
                }
            });
        }

        function closeConfigEditor() {
            $('#configEditorContainer').show();
            $('#configEditor').hide();
        }

        function updateConfigFile(event) {
            event.preventDefault();
            var formData = $('#configForm').serialize();

            $.ajax({
                url: '{{ route("settings.system.supervisor.config-file-update") }}',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function (data) {
                    if (data.success) {
                        toastr.success(data.message, 'Configuración guardada');
                        closeConfigEditor();
                        loadConfigFiles();
                    } else {
                        toastr.error(data.message, 'Error');
                    }
                },
                error: function (xhr, status, error) {
                    toastr.error('Error: ' + error, 'Error');
                }
            });
        }

        // Global variable for logs
        var currentLogContent = '';

        function loadProcessLogs() {
            var processName = $('#processSelect').val();
            var lines = $('#logLines').val() || 50;

            if (!processName) {
                $('#logsContent').html('<i class="fa fa-circle-info"></i> Selecciona un proceso para ver sus logs');
                $('#refreshLogsBtn').prop('disabled', true);
                $('#downloadLogsBtn').prop('disabled', true);
                $('#logProcessName').text('Salida de logs');
                $('#logStatusText').text('Esperando selección');
                $('#logStatus').removeClass('text-success').addClass('text-secondary');
                return;
            }

            // Enable buttons
            $('#refreshLogsBtn').prop('disabled', false);
            $('#downloadLogsBtn').prop('disabled', false);

            // Update UI
            $('#logProcessName').text(processName);
            $('#logStatusText').text('Cargando...');
            $('#logStatus').removeClass('text-success text-secondary').addClass('text-warning');
            $('#logsContent').html('<i class="fa fa-spinner fa-spin"></i> Cargando logs...');

            $.ajax({
                url: '{{ route("settings.system.supervisor.logs", ["processName" => ":name"]) }}'.replace(':name', processName) + '?lines=' + lines,
                type: 'GET',
                dataType: 'json',
                success: function (data) {
                    if (data.success) {
                        currentLogContent = data.logs || 'Sin logs disponibles';
                        displayLogs(currentLogContent);

                        // Update status
                        $('#logStatusText').text('Actualizado correctamente');
                        $('#logStatus').removeClass('text-warning').addClass('text-success');
                        $('#logLastUpdate').text('Última actualización: ' + new Date().toLocaleTimeString());
                    } else {
                        $('#logsContent').text('Error: ' + data.message);
                        $('#logStatusText').text('Error al cargar');
                        $('#logStatus').removeClass('text-success text-warning').addClass('text-danger');
                    }
                },
                error: function (xhr, status, error) {
                    $('#logsContent').text('Error al cargar logs: ' + error);
                    $('#logStatusText').text('Error de conexión');
                    $('#logStatus').removeClass('text-success text-warning').addClass('text-danger');
                }
            });
        }

        function displayLogs(content) {
            var showLineNumbers = $('#showLineNumbers').is(':checked');
            var lines = content.split('\n');

            if (showLineNumbers) {
                var numberedContent = '';
                for (var i = 0; i < lines.length; i++) {
                    var lineNum = (i + 1).toString().padStart(4, ' ');
                    numberedContent += lineNum + ' | ' + lines[i] + '\n';
                }
                $('#logsContent').text(numberedContent);
            } else {
                $('#logsContent').text(content);
            }

            // Update line count
            $('#logLineCount').text(lines.length + ' líneas');

            // Auto-scroll if enabled
            if ($('#autoScroll').is(':checked')) {
                var wrapper = $('#logsContentWrapper');
                wrapper.scrollTop(wrapper[0].scrollHeight);
            }

            // Apply any active filter
            if ($('#logSearch').val()) {
                filterLogs();
            }
        }

        function filterLogs() {
            var searchTerm = $('#logSearch').val().toLowerCase();
            var wrapper = $('#logsContentWrapper');
            var pre = $('#logsContent');

            if (!searchTerm) {
                // Reset highlighting
                displayLogs(currentLogContent);
                return;
            }

            var lines = currentLogContent.split('\n');
            var showLineNumbers = $('#showLineNumbers').is(':checked');
            var highlightedContent = '';
            var matchCount = 0;

            for (var i = 0; i < lines.length; i++) {
                var line = lines[i];
                var lineNum = showLineNumbers ? ((i + 1).toString().padStart(4, ' ') + ' | ') : '';

                if (line.toLowerCase().includes(searchTerm)) {
                    // Highlight matching line
                    var highlightedLine = line.replace(new RegExp('(' + searchTerm + ')', 'gi'), '<span style="background-color: yellow; color: black;">$1</span>');
                    highlightedContent += lineNum + highlightedLine + '\n';
                    matchCount++;
                } else {
                    highlightedContent += lineNum + line + '\n';
                }
            }

            pre.html(highlightedContent);
            $('#logLineCount').text(lines.length + ' líneas (' + matchCount + ' coincidencias)');
        }

        function downloadLogs() {
            var processName = $('#processSelect').val();
            if (!processName || !currentLogContent) {
                toastr.warning('No hay logs para descargar', 'Advertencia');
                return;
            }

            var filename = 'supervisor-' + processName.replace(/:/g, '-') + '-' + new Date().toISOString().slice(0, 19).replace(/:/g, '-') + '.log';
            var blob = new Blob([currentLogContent], {type: 'text/plain'});
            var link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = filename;
            link.click();
            window.URL.revokeObjectURL(link.href);

            toastr.success('Logs descargados: ' + filename, 'Descarga completa');
        }

        function clearLogs() {
            $('#logsContent').html('<i class="fa fa-circle-info"></i> Logs limpiados. Presiona "Refrescar" para recargar.');
            currentLogContent = '';
            $('#logLineCount').text('0 líneas');
            $('#logSearch').val('');
        }

        // Event listeners for log viewer controls
        $(document).ready(function () {
            $('#showLineNumbers').on('change', function () {
                if (currentLogContent) {
                    displayLogs(currentLogContent);
                }
            });

            $('#autoScroll').on('change', function () {
                if ($(this).is(':checked') && currentLogContent) {
                    var wrapper = $('#logsContentWrapper');
                    wrapper.scrollTop(wrapper[0].scrollHeight);
                }
            });
        });
    </script>
@endpush
