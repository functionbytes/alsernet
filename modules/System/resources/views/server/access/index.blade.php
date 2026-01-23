@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Registros de Acceso'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Main Content Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Registros de acceso del servidor</h5>
                        <p class="small mb-0 text-muted">Monitorea y gestiona los registros de acceso y actividad del
                            sistema en tiempo real</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="badge bg-primary-subtle text-primary d-flex align-items-center gap-2">
                            <i class="fa fa-file-text"></i> {{ $total }} registros
                        </span>
                        <span class="badge bg-info-subtle text-info d-flex align-items-center gap-2">
                            <i class="fa fa-database"></i> {{ $source === 'database' ? 'Base de Datos' : 'Archivos' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card-body border-bottom ">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-primary-subtle p-3">
                                <i class="fa fa-chart-line text-primary fs-6"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">{{ $total }}</h6>
                                <p class="text-muted small mb-0">Total de registros</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-danger-subtle p-3">
                                <i class="fa fa-exclamation-circle text-danger fs-6"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">{{ collect($logs)->where('level', 'ERROR')->count() }}</h6>
                                <p class="text-muted small mb-0">Errores</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-warning-subtle p-3">
                                <i class="fa fa-exclamation-triangle text-warning fs-6"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">{{ collect($logs)->where('level', 'WARNING')->count() }}</h6>
                                <p class="text-muted small mb-0">Advertencias</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-info-subtle p-3">
                                <i class="fa fa-info-circle text-info fs-6"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">{{ collect($logs)->where('level', 'INFO')->count() }}</h6>
                                <p class="text-muted small mb-0">Informativos</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons Section -->
            <div class="p-4 border-bottom">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-primary d-flex align-items-center gap-2" onclick="clearLogs()"
                                    title="Limpiar todos los registros">
                                <i class="fa fa-trash"></i> Limpiar registros
                            </button>
                            <a href="{{ route('settings.system.access.stats') }}"
                               class="btn btn-outline-primary d-flex align-items-center gap-2">
                                <i class="fa fa-bar-chart"></i> Estadísticas
                            </a>
                            <a href="{{ route('settings.system.access.download') }}"
                               class="btn btn-outline-primary d-flex align-items-center gap-2">
                                <i class="fa fa-download"></i> Descargar
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <!-- Filters Form -->
                        <form method="GET" class="d-flex gap-2 flex-wrap justify-content-end align-items-end"
                              id="filterForm">
                            <div style="min-width: 150px;">
                                <label class="form-label small fw-semibold mb-1">Fuente</label>
                                <select name="source" class="form-select form-select-sm"
                                        onchange="document.getElementById('filterForm').submit()">
                                    <option value="database" {{ $source === 'database' ? 'selected' : '' }}>Base de
                                        Datos
                                    </option>
                                    <option value="file" {{ $source === 'file' ? 'selected' : '' }}>Archivos</option>
                                </select>
                            </div>

                            @if($source === 'database')
                                <div style="min-width: 130px;">
                                    <label class="form-label small fw-semibold mb-1">Nivel</label>
                                    <select name="level" class="form-select form-select-sm"
                                            onchange="document.getElementById('filterForm').submit()">
                                        <option value="">Todos</option>
                                        <option value="ERROR" {{ $level === 'ERROR' ? 'selected' : '' }}>ERROR</option>
                                        <option value="WARNING" {{ $level === 'WARNING' ? 'selected' : '' }}>WARNING
                                        </option>
                                        <option value="INFO" {{ $level === 'INFO' ? 'selected' : '' }}>INFO</option>
                                        <option value="DEBUG" {{ $level === 'DEBUG' ? 'selected' : '' }}>DEBUG</option>
                                    </select>
                                </div>
                            @endif

                            <div style="min-width: 100px;">
                                <label class="form-label small fw-semibold mb-1">Límite</label>
                                <select name="limit" class="form-select form-select-sm"
                                        onchange="document.getElementById('filterForm').submit()">
                                    <option value="50" {{ $limit == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ $limit == 100 ? 'selected' : '' }}>100</option>
                                    <option value="250" {{ $limit == 250 ? 'selected' : '' }}>250</option>
                                    <option value="500" {{ $limit == 500 ? 'selected' : '' }}>500</option>
                                </select>
                            </div>

                            <a href="{{ route('settings.system.access.index') }}"
                               class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2">
                                <i class="fa fa-refresh"></i> Restablecer
                            </a>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="card-body p-4">
                <div class="mb-3">
                    <h6 class="fw-bold mb-3">Últimos {{ $limit }} Registros</h6>

                    @if(count($logs) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th class="fw-semibold text-muted small">Fecha y Hora</th>
                                    <th class="fw-semibold text-muted small" style="width: 120px;">Nivel</th>
                                    <th class="fw-semibold text-muted small">Mensaje</th>
                                    <th class="fw-semibold text-muted small text-center" style="width: 80px;">Acción
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($logs as $log)
                                    <tr>
                                        <td class="small">
                                            <span class="fw-semibold text-dark">{{ $log['timestamp'] }}</span>
                                        </td>
                                        <td>
                                            @if($log['level'] === 'ERROR')
                                                <span class="badge bg-danger-subtle text-danger">
                                                        <i class="fa fa-exclamation-circle"></i> {{ $log['level'] }}
                                                    </span>
                                            @elseif($log['level'] === 'WARNING')
                                                <span class="badge bg-warning-subtle text-warning">
                                                        <i class="fa fa-exclamation-triangle"></i> {{ $log['level'] }}
                                                    </span>
                                            @elseif($log['level'] === 'INFO')
                                                <span class="badge bg-info-subtle text-info">
                                                        <i class="fa fa-info-circle"></i> {{ $log['level'] }}
                                                    </span>
                                            @elseif($log['level'] === 'DEBUG')
                                                <span class="badge bg-secondary-subtle text-secondary">
                                                        <i class="fa fa-bug"></i> {{ $log['level'] }}
                                                    </span>
                                            @else
                                                <span class="badge bg-light text-dark">
                                                        <i class="fa fa-circle-o"></i> {{ $log['level'] }}
                                                    </span>
                                            @endif
                                        </td>
                                        <td class="small">
                                                <span class="text-muted">
                                                    {{ substr($log['message'], 0, 100) }}{{ strlen($log['message']) > 100 ? '...' : '' }}
                                                </span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-light-primary"
                                                    onclick="showLogDetail({{ $loop->index }})" title="Ver detalles">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info mb-0" role="alert">
                            <div class="d-flex align-items-center gap-3">
                                <i class="fa fa-circle-info fs-4"></i>
                                <div>
                                    <strong>No hay registros disponibles</strong>
                                    <p class="mb-0 small">No se encontraron registros con los filtros seleccionados.</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Footer Info -->
                <div class="alert alert-light border-start border-4 border-primary mb-0" role="alert">
                    <i class="fa fa-lightbulb text-primary me-2"></i>
                    <strong>Tip:</strong> Haz clic en el botón de ver detalles para obtener información completa del
                    registro. Los registros se muestran en orden descendente (más recientes primero).
                </div>
            </div>

        </div>

    </div>

    <!-- Modal de Confirmación para Limpiar Logs -->
    <div class="modal fade" id="clearLogsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">
                        <i class="fa fa-trash text-danger me-2"></i> Limpiar todos los registros
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning border-start border-4 border-warning" role="alert">
                        <div class="d-flex align-items-start gap-3">
                            <i class="fa fa-triangle-exclamation fs-3"></i>
                            <div>
                                <h6 class="mb-2"><strong>¿Estás seguro de que deseas continuar?</strong></h6>
                                <p class="mb-0 small">Esta acción eliminará permanentemente todos los registros de
                                    acceso del sistema y no se puede deshacer.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa fa-times me-1"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmClearBtn">
                        Confirmar limpieza
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Detalles del Log -->
    <div class="modal fade" id="logDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">
                        Detalles del registro
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold mb-1">Fecha y Hora</label>
                            <p class="fw-semibold mb-0" id="modalTimestamp">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold mb-1">Nivel</label>
                            <div id="modalLevel"></div>
                        </div>
                    </div>

                    <div id="databaseInfo" style="display: none;">
                        <hr class="my-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-semibold mb-1">Dirección IP</label>
                                <p class="fw-semibold small mb-0" id="modalIp">-</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-semibold mb-1">Usuario ID</label>
                                <p class="fw-semibold small mb-0" id="modalUser">-</p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label text-muted small fw-semibold mb-1">URL</label>
                            <p class="small text-break mb-0" id="modalUrl">-</p>
                        </div>
                        <div class="mt-3">
                            <label class="form-label text-muted small fw-semibold mb-1">Context (JSON)</label>
                            <pre id="modalContext" class="bg-light p-3 rounded small mb-0"
                                 style="white-space: pre-wrap; word-wrap: break-word; max-height: 150px; overflow-y: auto;">-</pre>
                        </div>
                    </div>

                    <hr class="my-3">
                    <div>
                        <label class="form-label text-muted small fw-semibold mb-2">Mensaje Completo</label>
                        <div class="bg-light p-3 rounded">
                            <pre id="modalMessage" class="mb-0 small"
                                 style="white-space: pre-wrap; word-wrap: break-word;">-</pre>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">

                    <button type="button" class="btn btn-primary w-100 mb-1" onclick="copyMessageToClipboard()">
                        <i class="fa fa-copy me-1"></i> Copiar mensaje
                    </button>
                    <button type="button" class="btn btn-secondary w-100 " data-bs-dismiss="modal">
                        <i class="fa fa-times me-1"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            const logsData = @json($logs);

            // Helper function to get badge HTML based on log level
            function getLevelBadge(level) {
                switch (level) {
                    case 'ERROR':
                        return '<span class="badge bg-danger-subtle text-danger"><i class="fa fa-exclamation-circle"></i> ERROR</span>';
                    case 'WARNING':
                        return '<span class="badge bg-warning-subtle text-warning"><i class="fa fa-exclamation-triangle"></i> WARNING</span>';
                    case 'INFO':
                        return '<span class="badge bg-info-subtle text-info"><i class="fa fa-info-circle"></i> INFO</span>';
                    case 'DEBUG':
                        return '<span class="badge bg-secondary-subtle text-secondary"><i class="fa fa-bug"></i> DEBUG</span>';
                    default:
                        return '<span class="badge bg-light text-dark"><i class="fa fa-circle-o"></i> ' + level + '</span>';
                }
            }

            // Show log detail modal
            window.showLogDetail = function (index) {
                const log = logsData[index];

                $('#modalTimestamp').text(log.timestamp);
                $('#modalLevel').html(getLevelBadge(log.level));
                $('#modalMessage').text(log.message);

                // Show database-specific information if available
                if (log.id) {
                    $('#databaseInfo').show();
                    $('#modalIp').text(log.ip_address || '-');
                    $('#modalUser').text(log.user_id || '-');
                    $('#modalUrl').text(log.url || '-');
                    $('#modalContext').text(log.context ? JSON.stringify(log.context, null, 2) : '-');
                } else {
                    $('#databaseInfo').hide();
                }

                var modal = new bootstrap.Modal(document.getElementById('logDetailModal'));
                modal.show();
            };

            // Copy message to clipboard
            window.copyMessageToClipboard = function () {
                const message = $('#modalMessage').text();

                navigator.clipboard.writeText(message).then(() => {
                    toastr.success('Mensaje copiado al portapapeles', 'Copiado');
                }).catch(() => {
                    toastr.error('Error al copiar al portapapeles', 'Error');
                });
            };

            // Show clear logs modal
            window.clearLogs = function () {
                var modal = new bootstrap.Modal(document.getElementById('clearLogsModal'));
                modal.show();
            };

            // Clear logs confirmation
            $('#confirmClearBtn').on('click', function () {
                var modal = bootstrap.Modal.getInstance(document.getElementById('clearLogsModal'));
                modal.hide();

                var btn = $(this);
                var originalContent = btn.html();
                btn.prop('disabled', true);
                btn.html('<i class="fa fa-spinner fa-spin me-1"></i> Limpiando...');

                $.ajax({
                    url: '{{ route("settings.system.access.clear") }}',
                    type: 'POST',
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (data) {
                        btn.prop('disabled', false);
                        btn.html(originalContent);

                        if (data.success) {
                            toastr.success(data.message, 'Registros limpiados');
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            toastr.error(data.message, 'Error');
                        }
                    },
                    error: function (xhr, status, error) {
                        btn.prop('disabled', false);
                        btn.html(originalContent);
                        toastr.error('Error al limpiar los registros', 'Error');
                    }
                });
            });
        });
    </script>
@endpush
