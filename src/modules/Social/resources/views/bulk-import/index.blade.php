@extends('layouts.theme')

@section('title', 'Importación Masiva')

@section('content')

    <div class="widget-content searchable-container list">

        <!-- Alerts -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Main Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">
                            <i class="fas fa-file-upload me-2"></i>Importación Masiva
                        </h5>
                        <p class="small mb-0 text-muted">Importa publicaciones desde archivos CSV</p>
                    </div>
                    <a href="{{ route('admin.social.bulk-import.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nueva Importación
                    </a>
                </div>
            </div>

            <!-- Imports Table -->
            <div class="card-body">
                @if($imports->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Importación</th>
                                    <th>Progreso</th>
                                    <th>Resultados</th>
                                    <th>Estado</th>
                                    <th width="120">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($imports as $import)
                                    <tr>
                                        <td>
                                            <div>
                                                <div class="fw-semibold">
                                                    Importación #{{ $import->id }}
                                                </div>
                                                <small class="text-muted">
                                                    <i class="fas fa-user me-1"></i>
                                                    {{ $import->user->name }} -
                                                    {{ $import->created_at->format('d/m/Y H:i') }}
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <div class="progress flex-fill" style="height: 20px; min-width: 150px;">
                                                        <div class="progress-bar bg-{{ $import->status === 'completed' ? 'success' : ($import->status === 'failed' ? 'danger' : 'primary') }}"
                                                             role="progressbar"
                                                             style="width: {{ $import->progress_percentage }}%"
                                                             aria-valuenow="{{ $import->progress_percentage }}"
                                                             aria-valuemin="0"
                                                             aria-valuemax="100">
                                                            {{ $import->progress_percentage }}%
                                                        </div>
                                                    </div>
                                                </div>
                                                <small class="text-muted">
                                                    {{ $import->processed_rows }} / {{ $import->total_rows }} filas procesadas
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>
                                                    {{ $import->created_posts }} creadas
                                                </span>
                                                @if($import->failed_rows > 0)
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times me-1"></i>
                                                        {{ $import->failed_rows }} errores
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $statusConfig = [
                                                    'pending' => ['color' => 'secondary', 'icon' => 'ti-clock', 'label' => 'Pendiente'],
                                                    'processing' => ['color' => 'primary', 'icon' => 'ti-loader', 'label' => 'Procesando'],
                                                    'completed' => ['color' => 'success', 'icon' => 'ti-check', 'label' => 'Completado'],
                                                    'failed' => ['color' => 'danger', 'icon' => 'ti-x', 'label' => 'Fallido'],
                                                ];
                                                $config = $statusConfig[$import->status] ?? $statusConfig['pending'];
                                            @endphp
                                            <span class="badge bg-{{ $config['color'] }}">
                                                <i class="{{ $config['icon'] }} me-1"></i>
                                                {{ $config['label'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('admin.social.bulk-import.show', $import) }}"
                                                   class="btn btn-sm btn-light"
                                                   data-bs-toggle="tooltip"
                                                   title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <form action="{{ route('admin.social.bulk-import.destroy', $import) }}"
                                                      method="POST"
                                                      onsubmit="return confirm('¿Eliminar esta importación?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="btn btn-sm btn-light-danger"
                                                            data-bs-toggle="tooltip"
                                                            title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($imports->hasPages())
                        <div class="mt-4">
                            {{ $imports->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-file-upload fs-1 text-muted"></i>
                        </div>
                        <h5 class="text-muted mb-2">No hay importaciones</h5>
                        <p class="text-muted mb-4">Importa múltiples publicaciones a la vez desde un archivo CSV</p>
                        <a href="{{ route('admin.social.bulk-import.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Primera Importación
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    // Auto-refresh for processing imports
    @if($imports->where('status', 'processing')->count() > 0)
        setTimeout(() => {
            location.reload();
        }, 5000);
    @endif
</script>
@endpush
