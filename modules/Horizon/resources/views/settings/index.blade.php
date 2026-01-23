@extends('layouts.theme')

@section('content')
<div class="container-fluid px-0">
    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <!-- Total Jobs Card -->
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stat-icon bg-primary bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-tasks text-primary" style="font-size: 1.75rem;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1 small">Trabajos totales</p>
                            <h4 class="mb-0 fw-bold">{{ number_format($stats['total_jobs'] ?? 0) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Jobs Card -->
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stat-icon bg-warning bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-hourglass-half text-warning" style="font-size: 1.75rem;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1 small">Trabajos pendientes</p>
                            <h4 class="mb-0 fw-bold">{{ number_format($stats['pending_jobs'] ?? 0) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Failed Jobs Card -->
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stat-icon bg-danger bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-exclamation-circle text-danger" style="font-size: 1.75rem;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1 small">Trabajos fallidos</p>
                            <h4 class="mb-0 fw-bold">{{ number_format($stats['failed_jobs'] ?? 0) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Jobs per Minute Card -->
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stat-icon bg-success bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-tachometer-alt text-success" style="font-size: 1.75rem;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1 small">Trabajos por minuto</p>
                            <h4 class="mb-0 fw-bold">{{ number_format($stats['jobs_per_minute'] ?? 0, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions and Info -->
    <div class="row g-3 mb-4">
        <!-- Quick Actions -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title fw-bold mb-3">Acciones rápidas</h6>
                    <div class="d-grid gap-2">
                        @can('manage-horizon-backups')
                            <button class="btn btn-outline-primary btn-sm" onclick="retryAllFailed()">
                                <i class="fas fa-sync-alt me-2"></i>Reintentar todos los fallidos
                            </button>
                        @endcan
                        <a href="{{ route('settings.horizon.failed') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-list me-2"></i>Ver trabajos fallidos
                        </a>
                        <a href="{{ route('settings.horizon.metrics') }}" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-chart-line me-2"></i>Ver métricas
                        </a>
                        <a href="/horizon" target="_blank" class="btn btn-outline-dark btn-sm">
                            <i class="fas fa-external-link-alt me-2"></i>Abrir Horizon nativo
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Info -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title fw-bold mb-3">Información del sistema</h6>
                    <div class="row g-2 small">
                        <div class="col-6">
                            <p class="text-muted mb-1">Tiempo de espera promedio</p>
                            <p class="fw-bold">{{ $stats['average_wait_time'] ?? 0 }}s</p>
                        </div>
                        <div class="col-6">
                            <p class="text-muted mb-1">Rendimiento promedio</p>
                            <p class="fw-bold">{{ number_format($stats['average_throughput'] ?? 0, 2) }}</p>
                        </div>
                        <div class="col-6">
                            <p class="text-muted mb-1">Trabajos procesados</p>
                            <p class="fw-bold">{{ number_format($stats['processed_count'] ?? 0) }}</p>
                        </div>
                        <div class="col-6">
                            <p class="text-muted mb-1">Tiempo de actividad</p>
                            <p class="fw-bold">{{ $stats['uptime_formatted'] ?? '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Queues -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h6 class="card-title fw-bold mb-3">Colas disponibles</h6>
            <div class="row g-2">
                @foreach(['default' => 'Default', 'emails' => 'Emails', 'deliveries' => 'Entregas', 'pdf-generation' => 'Generación PDF', 'bulk-operations' => 'Operaciones masivas', 'notifications' => 'Notificaciones', 'erp' => 'ERP', 'high' => 'Alta prioridad', 'ai-content' => 'Contenido IA'] as $queue => $label)
                    <div class="col-md-4 col-lg-3">
                        <div class="border rounded p-2 text-center small">
                            <p class="text-muted mb-1">{{ $label }}</p>
                            <p class="fw-bold mb-0">{{ $queue }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Helper function to format uptime
    function formatUptime(seconds) {
        if (seconds === 0) return '—';
        const days = Math.floor(seconds / 86400);
        const hours = Math.floor((seconds % 86400) / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);

        if (days > 0) return `${days}d ${hours}h`;
        if (hours > 0) return `${hours}h ${minutes}m`;
        return `${minutes}m`;
    }

    // Retry all failed jobs
    function retryAllFailed() {
        if (!confirm('¿Desea reintentar todos los trabajos fallidos?')) return;

        fetch('{{ route('settings.horizon.api.retryAll') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
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

    // Auto-refresh statistics
    setInterval(() => {
        fetch('{{ route('settings.horizon.api.stats') }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update stats without full page reload
                    console.log('Stats refreshed:', data.data);
                }
            })
            .catch(error => console.error('Error refreshing stats:', error));
    }, 30000); // Refresh every 30 seconds
</script>
@endpush
@endsection
