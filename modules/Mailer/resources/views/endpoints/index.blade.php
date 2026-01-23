@extends('layouts.theme')

@section('title', 'Email Endpoints')

@section('content')

    @include('core::components.card', ['title' => 'Email Endpoints API'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        {{-- Main Card --}}
        <div class="card">
            {{-- Header Section --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Endpoints configurados</h5>
                        <p class="small mb-0 text-muted">Gestiona los endpoints para enviar correos desde aplicaciones externas</p>
                    </div>
                    <a href="{{ route('mailers.endpoints.create') }}" class="btn btn-primary">
                        Crear endpoint
                    </a>
                </div>
            </div>

            {{-- Statistics Cards --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">
                                            Total endpoints
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $endpoints->total() }}</h4>
                                        <small class="text-muted">Configurados</small>
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
                                            Activos
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $endpoints->where('is_active', true)->count() }}</h4>
                                        <small class="text-muted">En funcionamiento</small>
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
                                        <h6 class="card-title text-warning mb-2">
                                            Inactivos
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $endpoints->where('is_active', false)->count() }}</h4>
                                        <small class="text-muted">Desactivados</small>
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
                                            Total requests
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $endpoints->sum('requests_count') }}</h4>
                                        <small class="text-muted">Enviados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search & Filter --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('mailers.endpoints.index') }}">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="search" name="search" class="form-control" placeholder="Buscar por nombre o slug..." value="{{ $search ?? '' }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="source" class="form-select select2">
                                <option value="">Todas las fuentes</option>
                                @foreach($sources as $src)
                                    <option value="{{ $src }}" @if($source === $src) selected @endif>{{ $src }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select select2">
                                <option value="">Todos los estados</option>
                                <option value="active" @if($status === 'active') selected @endif>Activos</option>
                                <option value="inactive" @if($status === 'inactive') selected @endif>Inactivos</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                Buscar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Endpoints List --}}
            <div class="card-body">
                @if($endpoints->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover search-table align-middle text-nowrap mb-0">
                            <thead class="header-item">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Fuente</th>
                                    <th>Tipo</th>
                                    <th class="text-center">Requests</th>
                                    <th class="text-center">Éxito</th>
                                    <th class="text-center">Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($endpoints as $endpoint)
                                    @php
                                        $successCount = $endpoint->successLogs()->count();
                                        $failedCount = $endpoint->failedLogs()->count();
                                        $total = $endpoint->requests_count;
                                        $successRate = $total > 0 ? round(($successCount / $total) * 100, 1) : 0;
                                    @endphp
                                    <tr class="search-items">
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold">{{ $endpoint->name }}</span>
                                                <small class="text-muted">{{ $endpoint->slug }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light-secondary text-primary rounded-3 py-2 fw-semibold fs-2 d-inline-flex align-items-center">
                                                {{ $endpoint->source }}
                                            </span>
                                        </td>
                                        <td>
                                            <code class="bg-light px-2 py-1 rounded">{{ $endpoint->type }}</code>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light-secondary text-dark rounded-3 py-2 fw-semibold fs-2">{{ $total }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($total > 0)
                                                <div class="d-flex align-items-center justify-content-center gap-2">
                                                    <small class="fw-semibold">{{ $successRate }}%</small>
                                                    <div class="progress" style="width: 60px; height: 6px;">
                                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $successRate }}%"></div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($endpoint->is_active)
                                                <span class="badge bg-success-subtle text-success rounded-3 py-2 fw-semibold fs-2">Activo</span>
                                            @else
                                                <span class="badge bg-danger-subtle  rounded-3 py-2 fw-semibold fs-2">Inactivo</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="dropdown dropstart">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fa-duotone fa-solid fa-ellipsis"></i>
                                                </a>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('mailers.endpoints.edit', $endpoint) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('mailers.endpoints.logs', $endpoint) }}">
                                                            Ver logs
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item d-flex align-items-center gap-3  confirm-delete"
                                                           data-href="{{ route('mailers.endpoints.destroy', $endpoint) }}">
                                                            Eliminar
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
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
                            <h6 class="mb-1">No hay endpoints configurados</h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No se encontraron resultados para "{{ request('search') }}"
                                @else
                                    Crea tu primer endpoint para gestionar emails desde aplicaciones externas
                                @endif
                            </p>
                            @if(!request('search'))
                                <a href="{{ route('mailers.endpoints.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear endpoint
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($endpoints->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="result-body">
                            <span>Mostrando {{ $endpoints->firstItem() }}-{{ $endpoints->lastItem() }} de {{ $endpoints->total() }} resultados</span>
                        </div>
                        <nav>
                            {{ $endpoints->links() }}
                        </nav>
                    </div>
                </div>
            @endif
        </div>
    </div>

{{-- API Documentation Modal --}}
<div class="modal fade" id="apiDocModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-book me-2"></i>Documentación API
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-bold mb-2">URL Base</h6>
                <div class="bg-black text-light p-3 rounded mb-3">
                    <code>POST /api/email-endpoints/{slug}/send</code>
                </div>

                <h6 class="fw-bold mb-2">Headers Requeridos</h6>
                <ul class="small mb-3">
                    <li><code>X-API-Token: {tu-token-api}</code></li>
                    <li><code>Content-Type: application/json</code></li>
                </ul>

                <h6 class="fw-bold mb-2">Body (JSON)</h6>
                <div class="bg-black text-light p-3 rounded mb-3" style="font-size: 11px; overflow-x: auto;">
                    <code><pre>{
  "customer_email": "user@example.com",
  "customer_name": "Juan Pérez",
  "other_var": "value"
}</pre></code>
                </div>

                <h6 class="fw-bold mb-2">Respuestas</h6>
                <ul class="small">
                    <li><strong>200 OK:</strong> Email enviado exitosamente</li>
                    <li><strong>401 Unauthorized:</strong> Token inválido o expirado</li>
                    <li><strong>422 Unprocessable:</strong> Datos incompletos o inválidos</li>
                    <li><strong>500 Server Error:</strong> Error al procesar la solicitud</li>
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- Examples Modal --}}
<div class="modal fade" id="exampleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-code me-2"></i>Ejemplos de Request
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-bold mb-2">cURL</h6>
                <div class="bg-black text-light p-3 rounded mb-3" style="font-size: 11px; overflow-x: auto;">
                    <code><pre>curl -X POST https://tu-dominio.com/api/email-endpoints/password-reset/send \
  -H "X-API-Token: abc123xyz..." \
  -H "Content-Type: application/json" \
  -d '{
    "customer_email": "user@example.com",
    "customer_name": "Juan",
    "reset_link": "https://..."
  }'</pre></code>
                </div>

                <h6 class="fw-bold mb-2">JavaScript (Fetch)</h6>
                <div class="bg-black text-light p-3 rounded" style="font-size: 11px; overflow-x: auto;">
                    <code><pre>fetch('/api/email-endpoints/password-reset/send', {
  method: 'POST',
  headers: {
    'X-API-Token': 'abc123xyz...',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    customer_email: 'user@example.com',
    customer_name: 'Juan',
    reset_link: 'https://...'
  })
}).then(r => r.json())</pre></code>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        allowClear: false,
        language: {
            noResults: function() {
                return 'Sin resultados';
            }
        }
    });

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
