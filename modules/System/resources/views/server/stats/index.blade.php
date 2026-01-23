@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Estadísticas del Servidor'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Quick Stats Overview -->
        <div class="card mb-3">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Estado del sistema</h5>
                        <p class="small mb-0 text-muted">Información en tiempo real del servidor y recursos del
                            sistema</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="badge bg-success-subtle text-success d-flex align-items-center gap-2">
                            <span style="width: 8px; height: 8px; background: #13C672; border-radius: 50%; display: inline-block;"></span>
                            En línea
                        </span>
                        <button type="button" class="btn btn-sm btn-primary" onclick="location.reload()">
                            <i class="fa fa-arrows-rotate me-1"></i> Actualizar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Quick Stats Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-primary-subtle p-3">
                                <i class="fa fa-server text-primary fs-6"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">{{ $stats['server_ip'] ?? 'N/A' }}</h6>
                                <p class="text-muted small mb-0">IP del Servidor</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-info-subtle p-3">
                                <i class="fa fa-code text-info fs-6"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">PHP {{ $stats['php_version'] ?? 'N/A' }}</h6>
                                <p class="text-muted small mb-0">Versión de PHP</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-success-subtle p-3">
                                <i class="fa fa-memory text-success fs-6"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">{{ $stats['memory_limit'] ?? 'N/A' }}</h6>
                                <p class="text-muted small mb-0">Límite de Memoria</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-warning-subtle p-3">
                                <i class="fa fa-clock text-warning fs-6"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">{{ $stats['uptime'] ?? 'N/A' }}</h6>
                                <p class="text-muted small mb-0">Tiempo de Actividad</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row g-3">
            <!-- Left Column - Server Information -->
            <div class="col-lg-8">

                <!-- System Information Card -->
                <div class="card mb-3">
                    <div class="card-header p-4 border-bottom">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-circle-info text-primary me-2"></i>
                            Información del sistema
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-semibold mb-1">IP del Servidor</label>
                                    <p class="fw-semibold mb-0">{{ $stats['server_ip'] ?? 'N/A' }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-semibold mb-1">Nombre del
                                        Servidor</label>
                                    <p class="fw-semibold mb-0">{{ $stats['server_name'] ?? 'N/A' }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-semibold mb-1">Sistema
                                        Operativo</label>
                                    <p class="fw-semibold mb-0">{{ $stats['os'] ?? 'N/A' }}</p>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label text-muted small fw-semibold mb-1">Versión de PHP</label>
                                    <p class="fw-semibold mb-0">{{ $stats['php_version'] ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-semibold mb-1">Tiempo de
                                        Actividad</label>
                                    <p class="fw-semibold mb-0">{{ $stats['uptime'] ?? 'N/A' }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-semibold mb-1">Límite de
                                        Memoria</label>
                                    <p class="fw-semibold mb-0">{{ $stats['memory_limit'] ?? 'N/A' }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-semibold mb-1">Max Execution
                                        Time</label>
                                    <p class="fw-semibold mb-0">{{ $stats['max_execution_time'] ?? 'N/A' }}s</p>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label text-muted small fw-semibold mb-1">Max Upload Size</label>
                                    <p class="fw-semibold mb-0">{{ $stats['upload_max_filesize'] ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Disk Space Card -->
                <div class="card mb-3">
                    <div class="card-header p-4 border-bottom">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-hard-drive text-primary me-2"></i>
                            Espacio en disco
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-primary-subtle p-3">
                                        <i class="fa fa-database text-primary fs-6"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold">{{ $stats['disk_total'] ?? 'N/A' }}</h6>
                                        <p class="text-muted small mb-0">Espacio Total</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-success-subtle p-3">
                                        <i class="fa fa-circle-check text-success fs-6"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold">{{ $stats['disk_free'] ?? 'N/A' }}</h6>
                                        <p class="text-muted small mb-0">Espacio Libre</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label text-muted small fw-semibold mb-0">Uso de disco</label>
                                <span class="badge
                                    {{ $stats['disk_usage_percent'] > 80 ? 'bg-danger-subtle text-danger' : ($stats['disk_usage_percent'] > 60 ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success') }}">
                                    {{ $stats['disk_usage_percent'] ?? 0 }}%
                                </span>
                            </div>
                            <div class="progress mb-3" style="height: 20px;">
                                <div class="progress-bar {{ $stats['disk_usage_percent'] > 80 ? 'bg-danger' : ($stats['disk_usage_percent'] > 60 ? 'bg-warning' : 'bg-success') }}"
                                     role="progressbar"
                                     style="width: {{ $stats['disk_usage_percent'] }}%"
                                     aria-valuenow="{{ $stats['disk_usage_percent'] }}"
                                     aria-valuemin="0"
                                     aria-valuemax="100">
                                </div>
                            </div>
                            <div class="alert
                                {{ $stats['disk_usage_percent'] > 80 ? 'alert-danger' : ($stats['disk_usage_percent'] > 60 ? 'alert-warning' : 'alert-success') }}
                                border-start border-4 mb-0" role="alert">
                                @if($stats['disk_usage_percent'] > 80)
                                    <i class="fa fa-triangle-exclamation me-2"></i>
                                    <strong>Crítico:</strong> El espacio en disco está casi lleno. Considera liberar
                                    espacio inmediatamente.
                                @elseif($stats['disk_usage_percent'] > 60)
                                    <i class="fa fa-circle-exclamation me-2"></i>
                                    <strong>Advertencia:</strong> El espacio en disco está por encima del 60%. Monitorea
                                    el uso.
                                @else
                                    <i class="fa fa-check me-2"></i>
                                    <strong>Óptimo:</strong> El espacio en disco está en niveles saludables.
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column - Actions & Quick Info -->
            <div class="col-lg-4">

                <!-- Actions Card -->
                <div class="card mb-3">
                    <div class="card-header p-4 border-bottom">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-bolt text-primary me-2"></i>
                            Acciones rápidas
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-grid gap-2">
                            <a href="{{ route('settings.system.access.index') }}"
                               class="btn btn-outline-primary text-start d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa fa-list"></i>
                                    <div>
                                        <div class="fw-semibold">Registros</div>
                                        <small class="text-muted">Ver logs de acceso</small>
                                    </div>
                                </div>
                                <i class="fa fa-chevron-right"></i>
                            </a>

                            <button type="button"
                                    class="btn btn-outline-primary text-start d-flex align-items-center justify-content-between"
                                    onclick="location.reload()">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa fa-arrows-rotate"></i>
                                    <div>
                                        <div class="fw-semibold">Actualizar</div>
                                        <small class="text-muted">Recargar datos</small>
                                    </div>
                                </div>
                                <i class="fa fa-chevron-right"></i>
                            </button>

                            <a href="{{ route('settings.system.cache.index') }}"
                               class="btn btn-outline-secondary text-start d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa fa-arrow-left"></i>
                                    <div>
                                        <div class="fw-semibold">Volver</div>
                                        <small class="text-muted">A caché del sistema</small>
                                    </div>
                                </div>
                                <i class="fa fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- PHP Configuration Card -->
                <div class="card mb-3">
                    <div class="card-header p-4 border-bottom">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-cog text-primary me-2"></i>
                            Configuración PHP
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-semibold mb-1">Max POST Size</label>
                            <p class="fw-semibold mb-0">{{ $stats['post_max_size'] ?? 'N/A' }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-semibold mb-1">Max Execution Time</label>
                            <p class="fw-semibold mb-0">{{ $stats['max_execution_time'] ?? 'N/A' }}s</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-semibold mb-1">Upload Max Filesize</label>
                            <p class="fw-semibold mb-0">{{ $stats['upload_max_filesize'] ?? 'N/A' }}</p>
                        </div>
                        <div class="mb-0">
                            <label class="form-label text-muted small fw-semibold mb-1">Memory Limit</label>
                            <p class="fw-semibold mb-0">{{ $stats['memory_limit'] ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <!-- System Status Card -->
                <div class="card">
                    <div class="card-header p-4 border-bottom">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-signal text-primary me-2"></i>
                            Estado del sistema
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-semibold mb-2">Estado del servidor</label>
                            <div>
                                <span class="badge bg-success-subtle text-success d-inline-flex align-items-center gap-2 px-3 py-2">
                                    <span style="width: 8px; height: 8px; background: #13C672; border-radius: 50%; display: inline-block;"></span>
                                    En línea y operativo
                                </span>
                            </div>
                        </div>

                        <div class="alert alert-light border-start border-4 border-primary mb-0" role="alert">
                            <div class="d-flex align-items-start gap-2">
                                <i class="fa fa-clock text-primary mt-1"></i>
                                <div class="small">
                                    <strong>Última actualización:</strong><br>
                                    <span id="last-update">{{ now()->format('d/m/Y H:i:s') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            // Update the last update time
            function updateTime() {
                const now = new Date();
                const formatted = now.toLocaleDateString('es-ES') + ' ' + now.toLocaleTimeString('es-ES');
                $('#last-update').text(formatted);
            }

            // Update time every second
            setInterval(updateTime, 1000);
        });
    </script>
@endpush
