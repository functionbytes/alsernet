@extends('layouts.managers')

@section('content')

    @include('managers.includes.card', ['title' => 'Detalles del Almacén'])

    <div class="widget-content searchable-container">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div>
                                <h5 class="mb-1">{{ $warehouse->name }}</h5>
                                <p class="text-muted mb-0">
                                    <span class="badge {{ $warehouse->available == 1 ? 'bg-light-primary' : 'bg-light-secondary' }} rounded-3 py-1 text-primary fw-semibold fs-3">
                                        {{ $warehouse->available == 1 ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label class="form-label text-muted mb-0">Código:</label>
                                    <span class="fw-semibold">{{ $warehouse->code }}</span>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-start">
                                    <label class="form-label text-muted mb-0">Descripción:</label>
                                    <span class="fw-semibold text-end" style="max-width: 60%;">{{ $warehouse->description ?? 'Sin descripción' }}</span>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label class="form-label text-muted mb-0">Fecha de Creación:</label>
                                    <span class="fw-semibold">{{ date('d/m/Y H:i', strtotime($warehouse->created_at)) }}</span>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label class="form-label text-muted mb-0">Última Actualización:</label>
                                    <span class="fw-semibold">{{ date('d/m/Y H:i', strtotime($warehouse->updated_at)) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="border-top mt-4 pt-4">
                            <div class="d-flex gap-2">
                                <a href="{{ route('manager.warehouse.edit', $warehouse->uid) }}" class="btn btn-primary btn-sm">
                                    <i class="fa-duotone fa-pen"></i> Editar
                                </a>
                                <a href="{{ route('manager.warehouse') }}" class="btn btn-secondary btn-sm">
                                    <i class="fa-duotone fa-arrow-left"></i> Volver
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Resumen</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Pisos:</span>
                                <span class="badge bg-primary-subtle text-primary fw-semibold fs-5 py-2 px-3">
                                    {{ $summary['total_floors'] }}
                                </span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Ubicaciones:</span>
                                <span class="badge bg-info-subtle text-info fw-semibold fs-5 py-2 px-3">
                                    {{ $summary['total_locations'] }}
                                </span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Espacios Totales:</span>
                                <span class="badge bg-success-subtle text-success fw-semibold fs-5 py-2 px-3">
                                    {{ $summary['total_slots'] }}
                                </span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Espacios Ocupados:</span>
                                <span class="badge bg-warning-subtle text-warning fw-semibold fs-5 py-2 px-3">
                                    {{ $summary['occupied_slots'] }}
                                </span>
                            </div>

                            @if($summary['total_slots'] > 0)
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small">Ocupación:</span>
                                        <span class="fw-semibold small">
                                            {{ round(($summary['occupied_slots'] / $summary['total_slots']) * 100, 2) }}%
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar" role="progressbar"
                                             style="width: {{ ($summary['occupied_slots'] / $summary['total_slots']) * 100 }}%"
                                             aria-valuenow="{{ $summary['occupied_slots'] }}" aria-valuemin="0" aria-valuemax="{{ $summary['total_slots'] }}">
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">Acciones Rápidas</h5>
                    </div>
                    <div class="card-body d-flex flex-column gap-2">
                        <a href="{{ route('manager.warehouse.dashboard.index', $warehouse->uid) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fa-duotone fa-chart-line"></i> Dashboard
                        </a>
                        <a href="{{ route('manager.warehouse.map', $warehouse->uid) }}" class="btn btn-outline-success btn-sm">
                            <i class="fa-duotone fa-map-location-dot"></i> Mapa
                        </a>
                        <a href="{{ route('manager.warehouse.history', $warehouse->uid) }}" class="btn btn-outline-info btn-sm">
                            <i class="fa-duotone fa-history"></i> Histórico
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
