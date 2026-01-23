@extends('layouts.theme')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Trabajos fallidos</h4>
            <small class="text-muted">Total: {{ $failedCount }}</small>
        </div>
        <a href="{{ route('settings.horizon.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>

    <!-- Bulk Actions -->
    @if($failedCount > 0 && auth()->user()->can('manage-horizon-backups'))
        <div class="mb-3">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm btn-outline-success" onclick="retryAllFailed()">
                    <i class="fas fa-sync-alt me-2"></i>Reintentar todos
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteAllFailed()">
                    <i class="fas fa-trash me-2"></i>Eliminar todos
                </button>
            </div>
        </div>
    @endif

    <!-- Failed Jobs Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if(count($failedJobs) > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="border-bottom">
                            <tr>
                                <th class="fw-bold text-muted small">Job ID</th>
                                <th class="fw-bold text-muted small">Cola</th>
                                <th class="fw-bold text-muted small">Nombre</th>
                                <th class="fw-bold text-muted small">Error</th>
                                <th class="fw-bold text-muted small">Tiempo</th>
                                <th class="fw-bold text-muted small">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($failedJobs as $job)
                                <tr>
                                    <td>
                                        <code class="text-muted small">{{ substr($job['id'] ?? 'N/A', 0, 8) }}...</code>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning">{{ $job['queue'] ?? 'default' }}</span>
                                    </td>
                                    <td>
                                        <small>{{ $job['name'] ?? 'Unknown' }}</small>
                                    </td>
                                    <td>
                                        <small class="text-danger">
                                            {{ substr($job['exception'] ?? 'Unknown error', 0, 50) }}...
                                        </small>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $job['timestamp'] ?? '—' }}</small>
                                    </td>
                                    <td>
                                        @can('manage-horizon-backups')
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button class="btn btn-outline-success" title="Reintentar" onclick="retryJob('{{ $job['id'] ?? '' }}')">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" title="Eliminar" onclick="deleteJob('{{ $job['id'] ?? '' }}')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        @else
                                            <button class="btn btn-sm btn-outline-secondary" onclick="viewJobDetails('{{ $job['id'] ?? '' }}')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-check-circle text-success mb-3" style="font-size: 3rem;"></i>
                    <p class="text-muted">No hay trabajos fallidos</p>
                    <small class="text-muted">¡Todo está funcionando correctamente!</small>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    const apiBase = '{{ route('settings.horizon.index') }}';
    const csrfToken = '{{ csrf_token() }}';

    function retryJob(jobId) {
        if (!confirm('¿Desea reintentar este trabajo?')) return;

        fetch(`{{ route('settings.horizon.api.retryJob', '') }}/${jobId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Trabajo reintentado exitosamente');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error en la solicitud');
        });
    }

    function deleteJob(jobId) {
        if (!confirm('¿Desea eliminar este trabajo fallido? Esta acción no se puede deshacer.')) return;

        fetch(`{{ route('settings.horizon.api.forgetJob', '') }}/${jobId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Trabajo eliminado');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error en la solicitud');
        });
    }

    function retryAllFailed() {
        if (!confirm('¿Desea reintentar TODOS los trabajos fallidos? Esta acción afectará a {{ $failedCount }} trabajos.')) return;

        fetch('{{ route('settings.horizon.api.retryAll') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`${data.count} trabajos reintentados`);
                location.reload();
            } else {
                alert('Error al reintentar trabajos');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error en la solicitud');
        });
    }

    function deleteAllFailed() {
        if (!confirm('¿Desea ELIMINAR TODOS los trabajos fallidos? Esta acción no se puede deshacer.')) return;

        alert('Esta función requiere actualización. Contacte al administrador.');
    }

    function viewJobDetails(jobId) {
        alert('Detalles del trabajo: ' + jobId);
    }
</script>
@endpush
@endsection
