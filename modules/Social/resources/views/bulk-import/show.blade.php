@extends('layouts.admin')

@section('title', 'Detalles de Importación')

@section('content')

    <div class="widget-content">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.social.bulk-import.index') }}">Importación Masiva</a></li>
                <li class="breadcrumb-item active">Importación #{{ $bulkImport->id }}</li>
            </ol>
        </nav>

        <!-- Status Card -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle p-3 bg-{{ $bulkImport->status === 'completed' ? 'success' : ($bulkImport->status === 'failed' ? 'danger' : 'primary') }}-subtle">
                                <i class="fas fa-file-upload fs-2 text-{{ $bulkImport->status === 'completed' ? 'success' : ($bulkImport->status === 'failed' ? 'danger' : 'primary') }}"></i>
                            </div>
                            <div>
                                <h5 class="mb-1">Importación #{{ $bulkImport->id }}</h5>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-user me-1"></i>{{ $bulkImport->user->name }} -
                                    <i class="fas fa-calendar ms-2 me-1"></i>{{ $bulkImport->created_at->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        @php
                            $statusConfig = [
                                'pending' => ['color' => 'secondary', 'icon' => 'ti-clock', 'label' => 'Pendiente'],
                                'processing' => ['color' => 'primary', 'icon' => 'ti-loader', 'label' => 'Procesando'],
                                'completed' => ['color' => 'success', 'icon' => 'ti-check', 'label' => 'Completado'],
                                'failed' => ['color' => 'danger', 'icon' => 'ti-x', 'label' => 'Fallido'],
                            ];
                            $config = $statusConfig[$bulkImport->status] ?? $statusConfig['pending'];
                        @endphp
                        <span class="badge bg-{{ $config['color'] }} fs-6 px-3 py-2">
                            <i class="{{ $config['icon'] }} me-1"></i>
                            {{ $config['label'] }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Progress Card -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-spinner me-2"></i>Progreso
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Progreso Total</span>
                                <span class="fw-bold">{{ $bulkImport->progress_percentage }}%</span>
                            </div>
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar bg-{{ $bulkImport->status === 'completed' ? 'success' : ($bulkImport->status === 'failed' ? 'danger' : 'primary') }}"
                                     role="progressbar"
                                     style="width: {{ $bulkImport->progress_percentage }}%">
                                    {{ $bulkImport->progress_percentage }}%
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-6">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="fs-4 fw-bold text-primary">{{ $bulkImport->total_rows }}</div>
                                    <small class="text-muted">Total Filas</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="fs-4 fw-bold text-info">{{ $bulkImport->processed_rows }}</div>
                                    <small class="text-muted">Procesadas</small>
                                </div>
                            </div>
                        </div>

                        @if($bulkImport->started_at)
                            <div class="mt-3 pt-3 border-top">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    Iniciado: {{ $bulkImport->started_at->format('d/m/Y H:i:s') }}
                                </small>
                                @if($bulkImport->completed_at)
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-check me-1"></i>
                                        Completado: {{ $bulkImport->completed_at->format('d/m/Y H:i:s') }}
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-hourglass me-1"></i>
                                        Duración: {{ $bulkImport->started_at->diffForHumans($bulkImport->completed_at, true) }}
                                    </small>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Results Card -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Resultados
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between p-3 bg-success-subtle rounded">
                                    <div>
                                        <i class="fas fa-check fs-3 text-success"></i>
                                    </div>
                                    <div class="text-end">
                                        <div class="fs-3 fw-bold text-success">{{ $bulkImport->created_posts }}</div>
                                        <small class="text-muted">Publicaciones Creadas</small>
                                    </div>
                                </div>
                            </div>

                            @if($bulkImport->failed_rows > 0)
                                <div class="col-12">
                                    <div class="d-flex align-items-center justify-content-between p-3 bg-danger-subtle rounded">
                                        <div>
                                            <i class="fas fa-times fs-3 text-danger"></i>
                                        </div>
                                        <div class="text-end">
                                            <div class="fs-3 fw-bold text-danger">{{ $bulkImport->failed_rows }}</div>
                                            <small class="text-muted">Filas con Errores</small>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($bulkImport->status === 'completed')
                                <div class="col-12">
                                    <div class="alert alert-success mb-0" role="alert">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <strong>Importación completada exitosamente</strong>
                                    </div>
                                </div>
                            @elseif($bulkImport->status === 'failed')
                                <div class="col-12">
                                    <div class="alert alert-danger mb-0" role="alert">
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        <strong>La importación falló</strong>
                                    </div>
                                </div>
                            @elseif($bulkImport->status === 'processing')
                                <div class="col-12">
                                    <div class="alert alert-info mb-0" role="alert">
                                        <i class="fas fa-spinner me-2"></i>
                                        <strong>Procesando... Esta página se actualizará automáticamente</strong>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Errors Card -->
        @if($bulkImport->errors && count($bulkImport->errors) > 0)
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>Errores Detectados ({{ count($bulkImport->errors) }})
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th width="100">#</th>
                                    <th>Error</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bulkImport->errors as $index => $error)
                                    <tr>
                                        <td>
                                            <span class="badge bg-danger">{{ $index + 1 }}</span>
                                        </td>
                                        <td>
                                            <code class="text-danger">{{ $error }}</code>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- Actions -->
        <div class="mt-4">
            <a href="{{ route('admin.social.bulk-import.index') }}" class="btn btn-light">
                <i class="fas fa-arrow-left me-1"></i> Volver a Importaciones
            </a>
            @if($bulkImport->created_posts > 0)
                <a href="{{ route('admin.social.publishing.index') }}" class="btn btn-primary">
                    <i class="fas fa-eye me-1"></i> Ver Publicaciones Creadas
                </a>
            @endif
            <form action="{{ route('admin.social.bulk-import.destroy', $bulkImport) }}"
                  method="POST"
                  class="d-inline"
                  onsubmit="return confirm('¿Eliminar esta importación?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-light-danger">
                    <i class="fas fa-trash me-1"></i> Eliminar
                </button>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    // Auto-refresh if processing
    @if($bulkImport->status === 'processing')
        setTimeout(() => {
            location.reload();
        }, 3000);
    @endif
</script>
@endpush
