@extends('layouts.theme')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Trabajos activos</h4>
        <a href="{{ route('settings.horizon.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>

    <!-- Jobs Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if(count($jobs) > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="border-bottom">
                            <tr>
                                <th class="fw-bold text-muted small">Job ID</th>
                                <th class="fw-bold text-muted small">Cola</th>
                                <th class="fw-bold text-muted small">Nombre</th>
                                <th class="fw-bold text-muted small">Estado</th>
                                <th class="fw-bold text-muted small">Tiempo</th>
                                <th class="fw-bold text-muted small">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($jobs as $job)
                                <tr>
                                    <td>
                                        <code class="text-muted small">{{ substr($job['id'] ?? 'N/A', 0, 8) }}...</code>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $job['queue'] ?? 'default' }}</span>
                                    </td>
                                    <td>
                                        <small>{{ $job['name'] ?? 'Unknown' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">Procesando</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $job['timestamp'] ?? '—' }}</small>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="viewJobDetails('{{ $job['id'] ?? '' }}')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-inbox text-muted mb-3" style="font-size: 3rem;"></i>
                    <p class="text-muted">No hay trabajos activos en este momento</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Pagination Info -->
    <div class="mt-3 text-muted small">
        <p class="mb-0">Mostrando {{ count($jobs) }} trabajos activos (máximo 50)</p>
        <p class="mb-0">Auto-actualizar cada 10 segundos</p>
    </div>
</div>

@push('scripts')
<script>
    function viewJobDetails(jobId) {
        alert('Detalles del trabajo: ' + jobId);
    }

    // Auto-refresh
    setInterval(() => {
        location.reload();
    }, 10000);
</script>
@endpush
@endsection
