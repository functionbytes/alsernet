@extends('layouts.theme')

@section('title', 'Logs del endpoint')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Logs del endpoint</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('settings.mailrelay.endpoints.index') }}">Endpoints</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('settings.mailrelay.endpoints.edit', $endpoint->id) }}">{{ $endpoint->name }}</a></li>
                    <li class="breadcrumb-item active">Logs</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('settings.mailrelay.endpoints.edit', $endpoint->id) }}" class="btn btn-light">
                <i class="fas fa-arrow-left me-2"></i>Volver al endpoint
            </a>
            @if($logs->total() > 0)
                <form action="{{ route('settings.mailrelay.endpoints.clear-logs', $endpoint->id) }}" method="POST" class="d-inline clear-logs-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Limpiar logs
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Endpoint Info Card --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h6 class="fw-bold mb-2">{{ $endpoint->name }}</h6>
                    <div class="d-flex gap-4">
                        <div>
                            <small class="text-muted">Endpoint Key:</small>
                            <code class="ms-2">{{ $endpoint->endpoint_key }}</code>
                        </div>
                        <div>
                            <small class="text-muted">Template:</small>
                            <strong class="ms-2">{{ $endpoint->template->name ?? 'N/A' }}</strong>
                        </div>
                        <div>
                            <small class="text-muted">Estado:</small>
                            <span class="badge {{ $endpoint->status === 'active' ? 'bg-success' : 'bg-secondary' }} ms-2">
                                {{ $endpoint->status === 'active' ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="mb-1">
                        <small class="text-muted">Total de logs:</small>
                        <strong class="fs-5 ms-2">{{ number_format($logs->total()) }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card bg-light-info mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('settings.mailrelay.endpoints.logs', $endpoint->id) }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="search" class="form-control" placeholder="IP, email, respuesta..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estado</label>
                    <select name="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Exitoso</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Fallido</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Desde</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-info flex-fill">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="{{ route('settings.mailrelay.endpoints.logs', $endpoint->id) }}" class="btn btn-light">
                        <i class="fas fa-times"></i>
                    </a>
                    <button type="button" class="btn btn-success" id="export-btn" title="Exportar a Excel">
                        <i class="fas fa-file-excel"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Logs List --}}
    @if($logs->isEmpty())
        <div class="alert alert-info mb-0">
            <i class="fas fa-info-circle me-2"></i>
            No se encontraron logs para este endpoint.
        </div>
    @else
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 140px;">Fecha/Hora</th>
                                <th style="width: 120px;">IP</th>
                                <th style="width: 80px;">Estado</th>
                                <th>Destinatario</th>
                                <th>Variables</th>
                                <th>Respuesta</th>
                                <th style="width: 60px;" class="text-center">Detalles</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr>
                                    <td>
                                        <small class="text-muted d-block">{{ $log->created_at->format('d/m/Y') }}</small>
                                        <strong>{{ $log->created_at->format('H:i:s') }}</strong>
                                    </td>
                                    <td>
                                        <code class="small">{{ $log->ip_address }}</code>
                                    </td>
                                    <td>
                                        @if($log->status === 'success')
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i>Exitoso
                                            </span>
                                        @else
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times-circle me-1"></i>Fallido
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->payload && isset($log->payload['to']))
                                            <div class="small">
                                                <i class="fas fa-envelope me-1 text-muted"></i>
                                                {{ $log->payload['to'] }}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->payload && isset($log->payload['variables']) && count($log->payload['variables']) > 0)
                                            <button type="button" class="btn btn-sm btn-light view-variables-btn"
                                                data-variables="{{ json_encode($log->payload['variables']) }}"
                                                title="Ver variables">
                                                <i class="fas fa-code me-1"></i>
                                                {{ count($log->payload['variables']) }} var(s)
                                            </button>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->response)
                                            <small class="text-muted">
                                                {{ Str::limit($log->response['message'] ?? json_encode($log->response), 50) }}
                                            </small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-info view-details-btn"
                                            data-log="{{ json_encode($log) }}"
                                            title="Ver detalles completos">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="text-muted">
                        Mostrando {{ $logs->firstItem() ?? 0 }} a {{ $logs->lastItem() ?? 0 }} de {{ $logs->total() }} logs
                    </div>
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Variables Modal --}}
<div class="modal fade" id="variablesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Variables enviadas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre id="variables-content" class="bg-light p-3 rounded"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- Details Modal --}}
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles del log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-3 border-bottom pb-2">Información general</h6>
                        <div id="general-info"></div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-3 border-bottom pb-2">Estado</h6>
                        <div id="status-info"></div>
                    </div>
                </div>

                <h6 class="fw-bold mb-3 border-bottom pb-2 mt-4">Payload enviado</h6>
                <pre id="payload-content" class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;"></pre>

                <h6 class="fw-bold mb-3 border-bottom pb-2 mt-4">Respuesta</h6>
                <pre id="response-content" class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // View variables
    $('.view-variables-btn').on('click', function() {
        const variables = JSON.parse($(this).data('variables'));
        $('#variables-content').text(JSON.stringify(variables, null, 2));
        $('#variablesModal').modal('show');
    });

    // View details
    $('.view-details-btn').on('click', function() {
        const log = JSON.parse($(this).data('log'));

        // General info
        const generalHtml = `
            <div class="mb-2"><strong>ID:</strong> ${log.id}</div>
            <div class="mb-2"><strong>Fecha:</strong> ${log.created_at}</div>
            <div class="mb-2"><strong>IP:</strong> <code>${log.ip_address}</code></div>
            <div class="mb-2"><strong>User Agent:</strong> <small>${log.user_agent || 'N/A'}</small></div>
        `;
        $('#general-info').html(generalHtml);

        // Status info
        const statusBadge = log.status === 'success'
            ? '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Exitoso</span>'
            : '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Fallido</span>';
        const statusHtml = `
            <div class="mb-2"><strong>Estado:</strong> ${statusBadge}</div>
            <div class="mb-2"><strong>Código HTTP:</strong> ${log.http_code || 'N/A'}</div>
            <div class="mb-2"><strong>Tiempo de ejecución:</strong> ${log.execution_time || 'N/A'}ms</div>
        `;
        $('#status-info').html(statusHtml);

        // Payload
        $('#payload-content').text(JSON.stringify(log.payload, null, 2));

        // Response
        $('#response-content').text(JSON.stringify(log.response, null, 2));

        $('#detailsModal').modal('show');
    });

    // Clear logs confirmation
    $('.clear-logs-form').on('submit', function(e) {
        if (!confirm('¿Estás seguro de eliminar TODOS los logs de este endpoint? Esta acción no se puede deshacer.')) {
            e.preventDefault();
        }
    });

    // Export to Excel (placeholder)
    $('#export-btn').on('click', function() {
        toastr.info('Función de exportación en desarrollo');
        // TODO: Implement export functionality
        // window.location.href = '{{ route("settings.mailrelay.endpoints.export-logs", $endpoint->id) }}' + window.location.search;
    });
});
</script>
@endpush
