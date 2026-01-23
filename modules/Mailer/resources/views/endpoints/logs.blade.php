@extends('layouts.theme')

@section('title', 'Logs de Endpoint')

@section('content')

    @include('core::components.card', ['title' => 'Logs de Requests'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        {{-- Endpoint Info Card --}}
        <div class="card card-body mb-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-4">
                    <div>
                        <h6 class="text-muted mb-1">Endpoint</h6>
                        <h5 class="mb-0 fw-bold">{{ $endpoint->name }}</h5>
                        <small class="text-muted">{{ $endpoint->slug }}</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div>
                        <h6 class="text-muted mb-1">Clasificación</h6>
                        <span class="badge bg-light-secondary text-primary rounded-3 py-2 fw-semibold fs-2 d-inline-flex align-items-center">{{ $endpoint->source }}</span>
                        <span class="badge bg-light-secondary text-dark rounded-3 py-2 fw-semibold fs-2 d-inline-flex align-items-center">{{ $endpoint->type }}</span>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <a href="{{ route('mailers.endpoints.edit', $endpoint) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-edit me-1"></i> Editar
                    </a>
                    <a href="{{ route('mailers.endpoints.index') }}" class="btn btn-sm btn-light">
                        <i class="fas fa-arrow-left me-1"></i> Atrás
                    </a>
                </div>
            </div>
        </div>

        {{-- Main Logs Card --}}
        <div class="card">
            {{-- Statistics Cards --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">
                                            Total Logs
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $logs->total() }}</h4>
                                        <small class="text-muted">Registrados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-success mb-2">
                                            Exitosos
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $successCount }}</h4>
                                        <small class="text-muted">Enviados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title  mb-2">
                                            Fallidos
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $failedCount }}</h4>
                                        <small class="text-muted">Con errores</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-info mb-2">
                                            Tasa de Éxito
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $successRate }}%</h4>
                                        <small class="text-muted">Rendimiento</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filter & Search --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('mailers.endpoints.logs', $endpoint) }}">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="search" name="search" class="form-control" placeholder="Buscar email..." value="{{ $search ?? '' }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pendiente</option>
                                <option value="processing" {{ $statusFilter === 'processing' ? 'selected' : '' }}>Procesando</option>
                                <option value="success" {{ $statusFilter === 'success' ? 'selected' : '' }}>Éxito</option>
                                <option value="failed" {{ $statusFilter === 'failed' ? 'selected' : '' }}>Fallido</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="period" class="form-select">
                                <option value="">Todos los períodos</option>
                                <option value="24h" {{ $period === '24h' ? 'selected' : '' }}>Últimas 24 horas</option>
                                <option value="7d" {{ $period === '7d' ? 'selected' : '' }}>Últimos 7 días</option>
                                <option value="30d" {{ $period === '30d' ? 'selected' : '' }}>Últimos 30 días</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                Filtrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($logs->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover search-table align-middle text-nowrap mb-0">
                            <thead class="header-item">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Destinatario</th>
                                    <th>Asunto</th>
                                    <th class="text-center">Estado</th>
                                    <th>Mensaje de Error</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($logs as $log)
                                    <tr class="search-items">
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-semibold">{{ $log->created_at->format('d/m/Y') }}</span>
                                                <small class="text-muted">{{ $log->created_at->format('H:i:s') }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <code class="bg-light px-2 py-1 rounded">{{ $log->recipient_email ?? 'N/A' }}</code>
                                        </td>
                                        <td>
                                            <small>{{ Str::limit($log->email_subject ?? 'Sin asunto', 40) }}</small>
                                        </td>
                                        <td class="text-center">
                                            @if($log->status === 'success')
                                                <span class="badge bg-success-subtle text-success rounded-3 py-2 fw-semibold fs-2">
                                                    <i class="fas fa-check-circle me-1"></i> Éxito
                                                </span>
                                            @elseif($log->status === 'processing')
                                                <span class="badge bg-info-subtle text-info rounded-3 py-2 fw-semibold fs-2">
                                                    <i class="fas fa-spinner fa-spin me-1"></i> Procesando
                                                </span>
                                            @elseif($log->status === 'pending')
                                                <span class="badge bg-warning-subtle text-warning rounded-3 py-2 fw-semibold fs-2">
                                                    <i class="fas fa-clock me-1"></i> Pendiente
                                                </span>
                                            @else
                                                <span class="badge bg-danger-subtle  rounded-3 py-2 fw-semibold fs-2">
                                                    <i class="fas fa-times-circle me-1"></i> Fallido
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->error_message)
                                                <small class="">{{ Str::limit($log->error_message, 50) }}</small>
                                            @else
                                                <small class="text-muted">-</small>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal"
                                                    data-bs-target="#logDetailModal{{ $log->id }}" title="Ver detalles">
                                                <i class="fas fa-eye me-1"></i> Ver
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-inbox fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay logs registrados</h6>
                            <p class="text-muted mb-0">
                                @if($search || $statusFilter || $period)
                                    No se encontraron logs con los filtros seleccionados
                                @else
                                    No hay registros de requests para este endpoint aún
                                @endif
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($logs->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="result-body">
                            <span>Mostrando {{ $logs->firstItem() }}-{{ $logs->lastItem() }} de {{ $logs->total() }} resultados</span>
                        </div>
                        <nav>
                            {{ $logs->appends(request()->input())->links() }}
                        </nav>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Modals for Log Details --}}
    @foreach($logs as $log)
        <div class="modal fade" id="logDetailModal{{ $log->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold">
                            <i class="fas fa-file-alt me-2"></i>
                            Detalles del Log #{{ $log->id }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-0">
                        {{-- Info Row --}}
                        <div class="p-4 border-bottom bg-light">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div>
                                        <small class="text-muted fw-semibold text-uppercase">Estado</small>
                                        <div class="mt-1">
                                            @if($log->status === 'success')
                                                <span class="badge bg-success-subtle text-success rounded-3 py-2 fw-semibold fs-5">
                                                    <i class="fas fa-check-circle me-1"></i> Éxito
                                                </span>
                                            @elseif($log->status === 'processing')
                                                <span class="badge bg-info-subtle text-info rounded-3 py-2 fw-semibold fs-5">
                                                    <i class="fas fa-spinner fa-spin me-1"></i> Procesando
                                                </span>
                                            @elseif($log->status === 'pending')
                                                <span class="badge bg-warning-subtle text-warning rounded-3 py-2 fw-semibold fs-5">
                                                    <i class="fas fa-clock me-1"></i> Pendiente
                                                </span>
                                            @else
                                                <span class="badge bg-danger-subtle  rounded-3 py-2 fw-semibold fs-5">
                                                    <i class="fas fa-times-circle me-1"></i> Fallido
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div>
                                        <small class="text-muted fw-semibold text-uppercase">Fecha</small>
                                        <div class="mt-1">
                                            <span class="fw-semibold">{{ $log->created_at->format('d/m/Y H:i:s') }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div>
                                        <small class="text-muted fw-semibold text-uppercase">Destinatario</small>
                                        <div class="mt-1">
                                            <code class="bg-white px-2 py-1 rounded">{{ $log->recipient_email ?? 'N/A' }}</code>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div>
                                        <small class="text-muted fw-semibold text-uppercase">Asunto</small>
                                        <div class="mt-1">
                                            <span>{{ $log->email_subject ?? 'Sin asunto' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Payload Section --}}
                        <div class="p-4 border-bottom">
                            <h6 class="fw-bold mb-3">
                                <i class="fas fa-code me-2"></i> Payload Recibido
                            </h6>
                            <pre class="bg-light p-3 rounded mb-0" style="max-height: 300px; overflow-y: auto; font-size: 12px;"><code>{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                        </div>

                        {{-- Error Message Section (if failed) --}}
                        @if($log->error_message)
                            <div class="p-4 border-bottom bg-danger-subtle">
                                <h6 class="fw-bold mb-3 ">
                                    <i class="fas fa-exclamation-circle me-2"></i> Mensaje de Error
                                </h6>
                                <pre class="bg-white p-3 rounded mb-0 " style="max-height: 200px; overflow-y: auto; font-size: 12px;"><code>{{ $log->error_message }}</code></pre>
                            </div>
                        @endif

                        {{-- Metadata Section --}}
                        @if($log->job_id || $log->sent_at)
                            <div class="p-4 bg-light">
                                <div class="row g-3 small">
                                    @if($log->job_id)
                                        <div class="col-md-6">
                                            <div>
                                                <strong class="text-uppercase">Job ID:</strong>
                                                <code class="ms-2">{{ $log->job_id }}</code>
                                            </div>
                                        </div>
                                    @endif
                                    @if($log->sent_at)
                                        <div class="col-md-6">
                                            <div>
                                                <strong class="text-uppercase">Enviado:</strong>
                                                <span class="ms-2">{{ $log->sent_at->format('d/m/Y H:i:s') }}</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Show toastr notifications
    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
