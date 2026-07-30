@extends('layouts.theme')

@section('title', \$processName)

@section('page_header')
    @include('core::components.card', ['title' => \$processName])
@endsection

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <div class="card-body">
                    <div class="d-flex no-block align-items-center mb-4">
                        <div>
                            <h5 class="mb-2">
                                <i class="fa fa-person-running"></i> {{ $processName }}
                            </h5>
                            <p class="text-muted mb-0">Detalles y logs del proceso</p>
                        </div>
                        <div class="ms-auto d-flex gap-2">
                            <a href="{{ route('settings.system.supervisor.index') }}"
                               class="btn btn-outline-secondary waves-effect">
                                <i class="fa fa-arrow-left me-1"></i>Volver
                            </a>
                        </div>
                    </div>

                    @include('core::components.alerts')

                    <!-- Process Status Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card  bg-light-secondary ">
                                <div class="card-body">
                                    <h6 class="card-title">Estado del Proceso</h6>
                                    @php
                                        $state = $processStatus['process']['state'] ?? 'UNKNOWN';
                                        $badgeClass = 'bg-success';
                                        if ($state === 'STOPPED') $badgeClass = 'bg-danger';
                                        elseif ($state === 'STOPPING') $badgeClass = 'bg-warning';
                                        elseif ($state === 'STARTING') $badgeClass = 'bg-info';
                                        elseif ($state === 'BACKOFF') $badgeClass = 'bg-warning';
                                        elseif ($state === 'FATAL') $badgeClass = 'bg-danger';
                                        elseif ($state === 'EXITED') $badgeClass = 'bg-secondary';
                                    @endphp
                                    <h3>
                                        <span class="badge {{ $badgeClass }} px-3 py-2">{{ $state }}</span>
                                    </h3>
                                    <small class="text-muted">Estado actual del proceso en Supervisor</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card  bg-light-secondary ">
                                <div class="card-body">
                                    <h6 class="card-title">PID</h6>
                                    <h3>{{ $pid ?? '-' }}</h3>
                                    <small class="text-muted">Identificador del proceso del sistema</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card  bg-light-secondary ">
                                <div class="card-body">
                                    <h6 class="card-title">Uptime</h6>
                                    <h3>{{ $uptime ?? '-' }}</h3>
                                    <small class="text-muted">Tiempo de ejecución continua</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Process Details -->
                    <div class="row mb-4">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Información Completa del Proceso</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="mb-2">Nombre</h6>
                                            <p class="mb-3">{{ $processName }}</p>

                                            <h6 class="mb-2">Estado</h6>
                                            <p class="mb-3">{{ $state }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="mb-2">Detalles Adicionales</h6>
                                            <p class="mb-3 text-muted">
                                                {{ $processStatus['process']['details'] ?? 'No hay detalles disponibles' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    @if($state === 'RUNNING')
                        <div class="row mb-4">
                            <div class="col-lg-12">
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-warning waves-effect"
                                            onclick="stopProcess('{{ $processName }}')">
                                        <i class="fa fa-stop me-1"></i> Detener Proceso
                                    </button>
                                    <button type="button" class="btn btn-info waves-effect"
                                            onclick="restartProcess('{{ $processName }}')">
                                        <i class="fa fa-arrows-rotate me-1"></i> Reiniciar Proceso
                                    </button>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="row mb-4">
                            <div class="col-lg-12">
                                <button type="button" class="btn btn-success waves-effect"
                                        onclick="startProcess('{{ $processName }}')">
                                    <i class="fa fa-play me-1"></i> Iniciar Proceso
                                </button>
                            </div>
                        </div>
                    @endif

                    <!-- Process Logs -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header bg-light-secondary d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Logs del Proceso (últimas 100 líneas)</h6>
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick="refreshLogs()">
                                        <i class="fa fa-arrows-rotate"></i> Actualizar
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="bg-black p-3 rounded" style="max-height: 500px; overflow-y: auto;">
                                        <pre id="logsContent" class="text-light mb-0" style="font-size: 12px;">
@if(isset($logs['logs']))
                                                {{ $logs['logs'] }}
                                            @else
                                                No hay logs disponibles para este proceso
                                            @endif
                                        </pre>
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

@section('js')
    <script>
        toastr.options = { closeButton: true, progressBar: true, positionClass: 'toast-bottom-right', timeOut: 3000 };

        // Auto-refresh logs every 10 seconds
        setInterval(refreshLogs, 10000);

        function processAction(processName, action, url) {
            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (data) {
                    if (data.success) {
                        toastr.success(data.message, 'Éxito');
                        setTimeout(function () { location.reload(); }, 1500);
                    } else {
                        toastr.error(data.message || 'Error al ejecutar la acción', 'Error');
                    }
                },
                error: function (xhr) {
                    var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error de conexión';
                    toastr.error(msg, 'Error');
                }
            });
        }

        function startProcess(processName) {
            processAction(processName, 'start',
                '{{ route('settings.system.supervisor.start', ['processName' => ':p']) }}'.replace(':p', processName));
        }

        function stopProcess(processName) {
            processAction(processName, 'stop',
                '{{ route('settings.system.supervisor.stop', ['processName' => ':p']) }}'.replace(':p', processName));
        }

        function restartProcess(processName) {
            processAction(processName, 'restart',
                '{{ route('settings.system.supervisor.restart', ['processName' => ':p']) }}'.replace(':p', processName));
        }

        function refreshLogs() {
            fetch(`{{ route('settings.system.supervisor.logs', ['processName' => ':processName']) }}`.replace(':processName', '{{ $processName }}') + '?lines=100', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('logsContent').textContent = data.logs || 'No hay logs disponibles';
                        // Auto-scroll to bottom
                        const logsContainer = document.querySelector('.bg-black');
                        if (logsContainer) {
                            logsContainer.scrollTop = logsContainer.scrollHeight;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error refreshing logs:', error);
                });
        }
    </script>
@endsection
