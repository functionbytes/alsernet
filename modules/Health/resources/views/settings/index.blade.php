@extends('layouts.theme')

@section('content')
    @include('core::components.card', ['title' => 'Estado del sistema'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            {{-- Header Section --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Estado del sistema</h5>
                        <p class="small mb-0 text-muted">Monitoreo en tiempo real del estado de salud del sistema</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        @if($overallStatus === 'ok')
                            <div class="badge bg-primary">
                                <i class="fas fa-check-circle me-2"></i>Sistema operativo
                            </div>
                        @elseif($overallStatus === 'warning')
                            <div class="badge bg-warning text-warning fs-6 px-3 py-2">
                                <i class="fas fa-exclamation-triangle me-2"></i>Advertencia detectada
                            </div>
                        @else
                            <div class="badge bg-danger text-black fs-6 px-3 py-2">
                                <i class="fas fa-times-circle me-2"></i>Problemas detectados
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Stats Overview --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-12 col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">Total</h6>
                                        <h4 class="mb-1 fw-bold">{{ count($results) }}</h4>
                                        <small class="text-muted">Verificaciones</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-success mb-2">Correctos</h6>
                                        <h4 class="mb-1 fw-bold">{{ collect($results)->where('status.value', 'ok')->count() }}</h4>
                                        <small class="text-muted">Funcionando bien</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-warning mb-2">Advertencias</h6>
                                        <h4 class="mb-1 fw-bold">{{ collect($results)->where('status.value', 'warning')->count() }}</h4>
                                        <small class="text-muted">Requieren atención</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-black mb-2">Errores</h6>
                                        <h4 class="mb-1 fw-bold">{{ collect($results)->whereNotIn('status.value', ['ok', 'warning'])->count() }}</h4>
                                        <small class="text-muted">Críticos</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- System Actions Bar --}}
            <div class="card-body border-bottom bg-light">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="fw-bold mb-1">Acciones del sistema</h6>
                        <p class="small text-muted mb-0">Ejecuta operaciones manuales de mantenimiento</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="runScheduler()" title="Ejecutar scheduler">
                            <i class="fas fa-play-circle"></i>
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" onclick="processQueue()" title="Procesar cola">
                            <i class="fas fa-forward"></i>
                        </button>
                    </div>
                </div>

                <div id="systemActionResult" class="mt-3" style="display: none;">
                    <div class="alert alert-info mb-0">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1" id="systemActionMessage"></div>
                            <button type="button" class="btn-close btn-sm" onclick="document.getElementById('systemActionResult').style.display='none'"></button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Scheduled Tasks Section --}}
            <div class="card-body border-bottom">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="fw-bold mb-1">Tareas programadas</h6>
                        <p class="small text-muted mb-0">Comandos que se ejecutan automáticamente según su programación</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" onclick="showScheduledTasks()" title="Actualizar tareas programadas">
                        <i class="fas fa-sync"></i>
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="50%">Comando</th>
                                <th width="25%">Expresión</th>
                                <th width="25%">Próxima ejecución</th>
                            </tr>
                        </thead>
                        <tbody id="scheduledTasksTable">
                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    <i class="fas fa-spinner fa-spin me-1"></i> Cargando tareas...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Queue Status Section --}}
            <div class="card-body border-bottom">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="fw-bold mb-1">Estado de la cola</h6>
                        <p class="small text-muted mb-0">Información sobre workers y trabajos en cola</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" onclick="checkQueueStatus()" title="Actualizar estado de cola">
                        <i class="fas fa-sync"></i>
                    </button>
                </div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label text-muted small mb-1">Conexión:</label>
                        <div class="form-control-plaintext fw-bold" id="queueConnection">-</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted small mb-1">Workers:</label>
                        <div class="form-control-plaintext fw-bold" id="queueWorkers">-</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted small mb-1">Trabajos pendientes:</label>
                        <div class="form-control-plaintext fw-bold" id="queuePending">-</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted small mb-1">Trabajos fallidos:</label>
                        <div class="form-control-plaintext fw-bold" id="queueFailed">-</div>
                    </div>
                </div>
                <div id="queueWarning" class="alert alert-info mt-3 mb-0" style="display: none;">
                    <small><i class="fas fa-exclamation-triangle me-1"></i><span id="queueWarningText"></span></small>
                </div>
            </div>


            {{-- Actions Bar --}}
            <div class="card-body border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Última verificación: <strong id="lastChecked">{{ now()->format('d/m/Y H:i:s') }}</strong>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="autoRefresh" onchange="toggleAutoRefresh()">
                            <label class="form-check-label small" for="autoRefresh">
                                Auto-actualizar (30s)
                            </label>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" onclick="refreshHealthChecks()">
                            <i class="fas fa-sync-alt me-1"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Health Checks Grid --}}
            <div class="card-body">
                <div class="row g-3" id="healthChecksGrid">
                    @foreach($results as $result)
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card bg-light-secondary stat-card h-100
                                @if($result->status->value === 'ok')
                                @elseif($result->status->value === 'warning')
                                @else
                                @endif
                                shadow-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="fw-bold mb-2">
                                                {{ $result->check->getLabel() }}
                                            </h6>
                                            <p class="mb-2 small text-muted">
                                                {{ $result->shortSummary }}
                                            </p>

                                            <hr class="my-2">
                                            @if($result->meta)
                                                <div class="mt-2">
                                                    @foreach($result->meta as $key => $value)
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <span class="text-muted small">{{ str_replace('_', ' ', ucfirst($key)) }}:</span>
                                                            <span class="small fw-bold">
                                                                @if(is_bool($value))
                                                                    <span class="badge bg-{{ $value ? 'success' : 'danger' }}-subtle text-{{ $value ? 'success' : 'danger' }}">
                                                                        {{ $value ? 'Sí' : 'No' }}
                                                                    </span>
                                                                @elseif(is_numeric($value))
                                                                    {{ $value }}
                                                                @else
                                                                    {{ $value }}
                                                                @endif
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            @if($result->notificationMessage)
                                                <div class="alert alert-danger mt-2 mb-0 py-2 px-2">
                                                    <small><i class="fas fa-exclamation-triangle me-1"></i>{{ $result->notificationMessage }}</small>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="ms-2">
                                            @if($result->status->value === 'ok')
                                                <span class="badge bg-primary text-success rounded-circle p-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-check"></i>
                                                </span>
                                            @elseif($result->status->value === 'warning')
                                                <span class="badge bg-warning text-warning rounded-circle p-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-exclamation"></i>
                                                </span>
                                            @else
                                                <span class="badge bg-danger text-black rounded-circle p-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-times"></i>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
        let autoRefreshInterval = null;

        function refreshHealthChecks() {
            const $btn = $(event.target).closest('button');
            const originalHtml = $btn.html();
            $btn.html('<span class="spinner-border spinner-border-sm"></span>');
            $btn.prop('disabled', true);

            $.ajax({
                url: '{{ route('settings.health.check') }}',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    // Update timestamp
                    const now = new Date();
                    $('#lastChecked').text(now.toLocaleDateString('es-ES') + ' ' + now.toLocaleTimeString('es-ES'));

                    // Show success message with toastr
                    toastr.success('Verificaciones de salud completadas correctamente', 'Éxito', {
                        positionClass: 'toast-bottom-right'
                    });

                    // Reload page to show updated results
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                },
                error: function(xhr, status, error) {
                    toastr.error('Error al ejecutar las verificaciones de salud', 'Error', {
                        positionClass: 'toast-bottom-right'
                    });
                    console.error('Health check error:', error);
                },
                complete: function() {
                    $btn.html(originalHtml);
                    $btn.prop('disabled', false);
                }
            });
        }

        function toggleAutoRefresh() {
            const checkbox = document.getElementById('autoRefresh');

            if (checkbox.checked) {
                autoRefreshInterval = setInterval(() => {
                    refreshHealthChecks();
                }, 30000); // 30 seconds
                toastr.info('Auto-actualización activada (cada 30 segundos)', 'Información', {
                    positionClass: 'toast-bottom-right'
                });
            } else {
                if (autoRefreshInterval) {
                    clearInterval(autoRefreshInterval);
                    autoRefreshInterval = null;
                }
                toastr.info('Auto-actualización desactivada', 'Información', {
                    positionClass: 'toast-bottom-right'
                });
            }
        }

        // System action functions
        function runScheduler() {
            const btn = event.target.closest('button');
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            btn.disabled = true;

            fetch('{{ route('settings.health.schedule.run') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        toastr.success('Scheduler ejecutado correctamente', 'Éxito', {
                            positionClass: 'toast-bottom-right'
                        });
                        showSystemActionResult(data.message + '<br><pre class="mt-2 mb-0 small">' + data.output + '</pre>');

                        // Refresh health checks after 2 seconds
                        setTimeout(() => {
                            refreshHealthChecks();
                        }, 2000);
                    } else {
                        toastr.error('Error al ejecutar el scheduler', 'Error', {
                            positionClass: 'toast-bottom-right'
                        });
                        showSystemActionResult(data.message, 'danger');
                    }
                })
                .catch(error => {
                    toastr.error('Error al ejecutar el scheduler', 'Error', {
                        positionClass: 'toast-bottom-right'
                    });
                    console.error('Scheduler error:', error);
                })
                .finally(() => {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                });
        }

        function showScheduledTasks() {
            const btn = event.target.closest('button');
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            btn.disabled = true;

            const tableBody = document.getElementById('scheduledTasksTable');

            fetch('{{ route('settings.health.schedule.list') }}')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        if (data.tasks.length > 0) {
                            let tasksHtml = '';
                            data.tasks.forEach(task => {
                                tasksHtml += `<tr>
                                    <td><code class="small">${task.command}</code></td>
                                    <td><small class="text-muted">${task.expression || '-'}</small></td>
                                    <td><small>${task.next_due}</small></td>
                                </tr>`;
                            });
                            tableBody.innerHTML = tasksHtml;
                        } else {
                            tableBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No hay tareas programadas</td></tr>';
                        }
                        toastr.success('Tareas actualizadas', 'Éxito', {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        toastr.error('Error al obtener las tareas programadas', 'Error', {
                            positionClass: 'toast-bottom-right'
                        });
                        tableBody.innerHTML = '<tr><td colspan="3" class="text-center text-black">Error al cargar tareas</td></tr>';
                    }
                })
                .catch(error => {
                    toastr.error('Error al obtener las tareas programadas', 'Error', {
                        positionClass: 'toast-bottom-right'
                    });
                    console.error('Schedule list error:', error);
                    tableBody.innerHTML = '<tr><td colspan="3" class="text-center text-black">Error al cargar tareas</td></tr>';
                })
                .finally(() => {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                });
        }

        function checkQueueStatus() {
            const btn = event.target.closest('button');
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            btn.disabled = true;

            fetch('{{ route('settings.health.queue.status') }}')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Update connection
                        document.getElementById('queueConnection').innerHTML = `<code>${data.connection}</code>`;

                        // Update workers
                        const workersEl = document.getElementById('queueWorkers');
                        if (data.workers_running) {
                            workersEl.innerHTML = `<span class="text-success">${data.worker_count} activo(s)</span>`;
                        } else {
                            workersEl.innerHTML = `<span class="text-black">Ninguno</span>`;
                        }

                        // Update pending jobs
                        document.getElementById('queuePending').textContent = data.pending_jobs;

                        // Update failed jobs
                        const failedEl = document.getElementById('queueFailed');
                        failedEl.innerHTML = data.failed_jobs > 0
                            ? `<span class="text-warning">${data.failed_jobs}</span>`
                            : `<span class="text-success">${data.failed_jobs}</span>`;

                        // Show/hide warning
                        const warningEl = document.getElementById('queueWarning');
                        if (!data.workers_running && data.connection !== 'sync') {
                            document.getElementById('queueWarningText').textContent = 'No hay workers activos. Los trabajos no se procesarán automáticamente.';
                            warningEl.style.display = 'block';
                        } else {
                            warningEl.style.display = 'none';
                        }

                        toastr.success('Estado actualizado', 'Éxito', {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        toastr.error('Error al obtener el estado de la cola', 'Error', {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                })
                .catch(error => {
                    toastr.error('Error al obtener el estado de la cola', 'Error', {
                        positionClass: 'toast-bottom-right'
                    });
                    console.error('Queue status error:', error);
                })
                .finally(() => {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                });
        }

        function processQueue() {
            const btn = event.target.closest('button');
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            btn.disabled = true;

            fetch('{{ route('settings.health.queue.process') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        toastr.success('Trabajos de cola procesados', 'Éxito', {
                            positionClass: 'toast-bottom-right'
                        });
                        showSystemActionResult(data.message + '<br><pre class="mt-2 mb-0 small">' + data.output + '</pre>');

                        // Refresh health checks after 2 seconds
                        setTimeout(() => {
                            refreshHealthChecks();
                        }, 2000);
                    } else if (data.status === 'info') {
                        toastr.info(data.message, 'Información', {
                            positionClass: 'toast-bottom-right'
                        });
                        showSystemActionResult(data.message, 'info');
                    } else {
                        toastr.error('Error al procesar la cola', 'Error', {
                            positionClass: 'toast-bottom-right'
                        });
                        showSystemActionResult(data.message, 'danger');
                    }
                })
                .catch(error => {
                    toastr.error('Error al procesar la cola', 'Error', {
                        positionClass: 'toast-bottom-right'
                    });
                    console.error('Process queue error:', error);
                })
                .finally(() => {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                });
        }

        function generateSupervisorConfig() {
            const btn = event.target.closest('button');
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            btn.disabled = true;

            fetch('{{ route('settings.health.supervisor.generate') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    workers: 3,
                    tries: 3,
                    timeout: 300
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        toastr.success('Configuración generada exitosamente', 'Éxito', {
                            positionClass: 'toast-bottom-right'
                        });

                        // Show instructions
                        let instructionsHtml = '<h6 class="fw-bold mb-3">📋 Configuración de Supervisor Generada</h6>';

                        instructionsHtml += '<div class="mb-3">';
                        instructionsHtml += '<p class="small mb-2"><strong>📁 Archivo generado:</strong></p>';
                        instructionsHtml += `<code class="small d-block p-2 bg-dark text-light rounded">${data.config_file}</code>`;
                        instructionsHtml += '</div>';

                        instructionsHtml += '<div class="mb-3">';
                        instructionsHtml += '<p class="small mb-2"><strong>🔧 Instalación (Ubuntu/Debian):</strong></p>';
                        instructionsHtml += `<code class="small d-block p-2 bg-dark text-light rounded mb-1">${data.instructions.install.ubuntu}</code>`;
                        instructionsHtml += '</div>';

                        instructionsHtml += '<div class="mb-3">';
                        instructionsHtml += '<p class="small mb-2"><strong>🔧 Instalación (macOS):</strong></p>';
                        instructionsHtml += `<code class="small d-block p-2 bg-dark text-light rounded mb-1">${data.instructions.install.macos}</code>`;
                        instructionsHtml += '</div>';

                        instructionsHtml += '<div class="mb-3">';
                        instructionsHtml += '<p class="small mb-2"><strong>⚙️ Configurar Supervisor (ejecutar en orden):</strong></p>';
                        data.instructions.setup.forEach(cmd => {
                            instructionsHtml += `<code class="small d-block p-2 bg-dark text-light rounded mb-1">${cmd}</code>`;
                        });
                        instructionsHtml += '</div>';

                        instructionsHtml += '<div class="mb-3">';
                        instructionsHtml += '<p class="small mb-2"><strong>✅ Verificar estado:</strong></p>';
                        instructionsHtml += `<code class="small d-block p-2 bg-dark text-light rounded mb-1">${data.instructions.verify}</code>`;
                        instructionsHtml += '</div>';

                        instructionsHtml += '<div class="alert alert-success py-2 px-3 mb-0">';
                        instructionsHtml += '<small><i class="fas fa-lightbulb me-1"></i><strong>Tip:</strong> Después de desplegar código nuevo: <code>php artisan queue:restart</code></small>';
                        instructionsHtml += '</div>';

                        showSystemActionResult(instructionsHtml, 'success');

                        // Show download button
                        document.getElementById('downloadSupervisorBtn').style.display = 'block';
                    } else {
                        toastr.error('Error al generar configuración', 'Error', {
                            positionClass: 'toast-bottom-right'
                        });
                        showSystemActionResult(data.message, 'danger');
                    }
                })
                .catch(error => {
                    toastr.error('Error al generar configuración', 'Error', {
                        positionClass: 'toast-bottom-right'
                    });
                    console.error('Supervisor config error:', error);
                })
                .finally(() => {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                });
        }

        function showSystemActionResult(message, type = 'info') {
            const resultDiv = document.getElementById('systemActionResult');
            const messageDiv = document.getElementById('systemActionMessage');
            const alertDiv = resultDiv.querySelector('.alert');

            alertDiv.className = `alert alert-${type} mb-0`;
            messageDiv.innerHTML = message;
            resultDiv.style.display = 'block';

            // Scroll to result
            resultDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
            }
        });

        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Load scheduled tasks
            loadScheduledTasksOnInit();

            // Load queue status
            loadQueueStatusOnInit();
        });

        function loadScheduledTasksOnInit() {
            const tableBody = document.getElementById('scheduledTasksTable');

            fetch('{{ route('settings.health.schedule.list') }}')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.tasks.length > 0) {
                        let tasksHtml = '';
                        data.tasks.forEach(task => {
                            tasksHtml += `<tr>
                                <td><code class="small">${task.command}</code></td>
                                <td><small class="text-muted">${task.expression || '-'}</small></td>
                                <td><small>${task.next_due}</small></td>
                            </tr>`;
                        });
                        tableBody.innerHTML = tasksHtml;
                    } else {
                        tableBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No hay tareas programadas</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Schedule list error:', error);
                    tableBody.innerHTML = '<tr><td colspan="3" class="text-center text-black">Error al cargar tareas</td></tr>';
                });
        }

        function loadQueueStatusOnInit() {
            fetch('{{ route('settings.health.queue.status') }}')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById('queueConnection').innerHTML = `<code>${data.connection}</code>`;

                        const workersEl = document.getElementById('queueWorkers');
                        if (data.workers_running) {
                            workersEl.innerHTML = `<span class="text-success">${data.worker_count} activo(s)</span>`;
                        } else {
                            workersEl.innerHTML = `<span class="text-black">Ninguno</span>`;
                        }

                        document.getElementById('queuePending').textContent = data.pending_jobs;

                        const failedEl = document.getElementById('queueFailed');
                        failedEl.innerHTML = data.failed_jobs > 0
                            ? `<span class="text-warning">${data.failed_jobs}</span>`
                            : `<span class="text-success">${data.failed_jobs}</span>`;

                        const warningEl = document.getElementById('queueWarning');
                        if (!data.workers_running && data.connection !== 'sync') {
                            document.getElementById('queueWarningText').textContent = 'No hay workers activos. Los trabajos no se procesarán automáticamente.';
                            warningEl.style.display = 'block';
                        } else {
                            warningEl.style.display = 'none';
                        }
                    }
                })
                .catch(error => {
                    console.error('Queue status error:', error);
                });
        }
    </script>
    @endpush
@endsection

<style>
    .stat-card {
        border: none;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .bg-light-secondary {
        background-color: #f8f9fa;
    }

    .bg-success-subtle {
        background-color: rgba(19, 198, 114, 0.1);
    }

    .bg-danger-subtle {
        background-color: rgba(250, 137, 107, 0.1);
    }

    .bg-warning-subtle {
        background-color: rgba(254, 201, 15, 0.1);
    }

    .bg-info-subtle {
        background-color: rgba(33, 150, 243, 0.1);
    }

    .bg-primary-subtle {
        background-color: rgba(144, 187, 19, 0.1);
    }
</style>
