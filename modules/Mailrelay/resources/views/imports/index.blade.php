@extends('layouts.theme')

@section('title', 'Importaciones')

@section('content')
<div class="widget-content searchable-container list">

    @include('core::components.alerts')

    {{-- Main Card --}}
    <div class="card">
        {{-- Header Section --}}
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Historial de importaciones</h5>
                    <p class="small mb-0 text-muted">Gestiona y rastrea tus importaciones de datos</p>
                </div>
                <a href="{{ route('mailrelay.imports.create') }}" class="btn btn-primary">
                    <i class="fas fa-upload me-2"></i>Nueva importación
                </a>
            </div>
        </div>

        {{-- Imports Table --}}
        <div class="card-body">
            @if($imports->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover search-table align-middle text-nowrap mb-0">
                        <thead class="header-item">
                            <tr>
                                <th>ID</th>
                                <th>Nombre de archivo</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Progreso</th>
                                <th>Registros</th>
                                <th>Fecha de inicio</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($imports as $import)
                                <tr class="search-items">
                                    <td>
                                        <span class="text-muted">#{{ $import->id }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold">{{ $import->file_name }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light-secondary text-primary rounded-3 py-2 fw-semibold fs-2 d-inline-flex align-items-center">
                                            {{ strtoupper($import->file_type) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($import->status === 'pending')
                                            <span class="badge bg-warning-subtle text-warning rounded-3 py-2 fw-semibold fs-2">
                                                <i class="fas fa-clock me-1"></i>Pendiente
                                            </span>
                                        @elseif($import->status === 'processing')
                                            <span class="badge bg-info-subtle text-info rounded-3 py-2 fw-semibold fs-2">
                                                <i class="fas fa-spinner me-1"></i>Procesando
                                            </span>
                                        @elseif($import->status === 'completed')
                                            <span class="badge bg-success-subtle text-success rounded-3 py-2 fw-semibold fs-2">
                                                <i class="fas fa-check-circle me-1"></i>Completado
                                            </span>
                                        @elseif($import->status === 'failed')
                                            <span class="badge bg-danger-subtle text-danger rounded-3 py-2 fw-semibold fs-2">
                                                <i class="fas fa-times-circle me-1"></i>Fallido
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary rounded-3 py-2 fw-semibold fs-2">{{ $import->status }}</span>
                                        @endif
                                    </td>
                                    <td style="min-width: 150px;">
                                        @php
                                            $progress = $import->total_rows > 0
                                                ? round(($import->processed_rows / $import->total_rows) * 100)
                                                : 0;
                                        @endphp
                                        <div class="d-flex align-items-center gap-2">
                                            <small class="fw-semibold">{{ $progress }}%</small>
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar
                                                    @if($import->status === 'completed') bg-success
                                                    @elseif($import->status === 'failed') bg-danger
                                                    @elseif($import->status === 'processing') bg-info
                                                    @else bg-secondary
                                                    @endif"
                                                    role="progressbar"
                                                    style="width: {{ $progress }}%"
                                                    aria-valuenow="{{ $progress }}"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100">
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div><strong>{{ number_format($import->processed_rows) }}</strong> / {{ number_format($import->total_rows) }}</div>
                                            @if($import->successful_rows > 0)
                                                <span class="text-success">
                                                    <i class="fas fa-check"></i>{{ number_format($import->successful_rows) }}
                                                </span>
                                            @endif
                                            @if($import->failed_rows > 0)
                                                <span class="text-danger ms-2">
                                                    <i class="fas fa-times"></i>{{ number_format($import->failed_rows) }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div>{{ $import->created_at->format('M d, Y') }}</div>
                                            <div class="text-muted">{{ $import->created_at->format('h:i A') }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="dropdown dropstart">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-duotone fa-solid fa-ellipsis"></i>
                                            </a>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('mailrelay.imports.show', $import->id) }}">
                                                        Ver detalles
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-3 text-danger confirm-delete"
                                                       data-href="{{ route('mailrelay.imports.destroy', $import->id) }}">
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
                        <h6 class="mb-1">No hay importaciones todavía</h6>
                        <p class="text-muted mb-3">Comienza cargando tu primer archivo CSV o Excel</p>
                        <a href="{{ route('mailrelay.imports.create') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-upload me-2"></i>Crear primera importación
                        </a>
                    </div>
                </div>
            @endif
        </div>

        {{-- Pagination --}}
        @if($imports->hasPages())
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="result-body">
                        <span>Mostrando {{ $imports->firstItem() }}-{{ $imports->lastItem() }} de {{ $imports->total() }} resultados</span>
                    </div>
                    <nav>
                        {{ $imports->links() }}
                    </nav>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
