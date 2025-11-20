@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-12">

            <div class="card">
                <div class="card-body">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="card-title mb-1">
                                <i class="ti ti-layout-grid me-2"></i>{{ $section->code }} - {{ $section->level }}
                            </h5>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('manager.warehouse.section.edit', [$location->floor->warehouse->uid, $location->floor->uid, $location->uid, $section->uid]) }}"
                               class="btn btn-md btn-primary">
                                <i class="ti ti-pencil me-1"></i>
                            </a>
                            <a href="{{ route('manager.warehouse.sections', [$location->floor->warehouse->uid, $location->floor->uid, $location->uid]) }}"
                               class="btn btn-md btn-secondary">
                                <i class="ti ti-arrow-left me-1"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Estadísticas KPI -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-light-primary border-0">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h2 class="text-primary">{{ $section->getTotalSlots() }}</h2>
                                        <p class="text-muted mb-0">Total de Slots</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-primary border-0">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h2 class="text-primary">{{ $section->getOccupiedSlots() }}</h2>
                                        <p class="text-muted mb-0">Slots Ocupados</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-primary border-0">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h2 class="text-primary">{{ $section->getAvailableSlots() }}</h2>
                                        <p class="text-muted mb-0">Slots Disponibles</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-primary border-0">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h2 class="text-primary">{{ round($section->getOccupancyPercentage(), 1) }}%</h2>
                                        <p class="text-muted mb-0">Ocupación</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información General & Configuración -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-light-primary border-bottom">
                                    <p class="mb-0">
                                        <i class="ti ti-info-circle me-2"></i>Información General
                                    </p>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label text-muted small mb-1">Código</label>
                                        <p class="mb-0"><strong class="text-dark">{{ $section->code }}</strong></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted small mb-1">Código de Barras</label>
                                        <p class="mb-0">{{ $section->barcode ?? '—' }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted small mb-1">Ubicación</label>
                                        <p class="mb-0">
                                            <a href="{{ route('manager.warehouse.locations.view', [$location->floor->warehouse->uid, $location->floor->uid, $location->uid]) }}" class="text-primary">
                                                {{ $location->code }}
                                            </a>
                                        </p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted small mb-1">Piso</label>
                                        <p class="mb-0">
                                            <a href="{{ route('manager.warehouse.floors.view', [$location->floor->warehouse->uid, $location->floor->uid]) }}" class="text-primary">
                                                {{ $floor->name }}
                                            </a>
                                        </p>
                                    </div>
                                    <hr class="my-3">
                                    <div class="row">
                                        <div class="col-6">
                                            <label class="form-label text-muted small mb-1">Creada</label>
                                            <p class="d-block">{{ $section->created_at->format('d/m/Y H:i') }}</p>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label text-muted small mb-1">Actualizada</label>
                                            <p class="d-block">{{ $section->updated_at->format('d/m/Y H:i') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-light-primary border-bottom">
                                    <p class="mb-0">
                                        <i class="ti ti-settings me-2"></i>Configuración
                                    </p>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label text-muted small mb-1">Nivel (Altura)</label>
                                        <p class="mb-0"><strong class="text-dark">{{ $section->level }}</strong></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted small mb-1">Cantidad Máxima</label>
                                        <p class="mb-0"><strong class="text-dark">{{ $section->max_quantity ?? '—' }} unidades</strong></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted small mb-1">Estado</label>
                                        <p class="mb-0">
                                            @if($section->available)
                                               Activa
                                            @else
                                                Inactiva
                                            @endif
                                        </p>
                                    </div>
                                    <hr class="my-3">
                                    <div class="mb-0">
                                        <label class="form-label text-muted small mb-2">Notas</label>
                                        <p class="mb-0 text-dark">{{ $section->notes ?? 'Sin notas' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Slots -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light-primary border-bottom d-flex justify-content-between align-items-center">
                                    <p class="mb-0">
                                        <i class="ti ti-package me-2"></i>Productos en Sección
                                    </p>
                                    <span class="badge badge-primary fs-6">{{ $slots->total() }}</span>
                                </div>
                                <div class="card-body pt-3">
                                    @if($slots->count() > 0)
                                        <div class="table-responsive border rounded-2 bg-white">
                                            <table class="table table-sm table-hover table-striped mb-0">
                                                <thead class="table-light sticky-top" style="top: 0; z-index: 10;">
                                                    <tr>
                                                        <th class="fw-bold text-dark py-3">
                                                            <i class="ti ti-package me-2"></i>Producto
                                                        </th>
                                                        <th class="fw-bold text-dark py-3 text-center">
                                                            <i class="ti ti-hash me-2"></i>Kardex
                                                        </th>
                                                        <th class="fw-bold text-dark py-3 text-center">
                                                            <i class="ti ti-hash me-2"></i>Cantidad Real
                                                        </th>
                                                        <th class="fw-bold text-dark py-3 text-center">
                                                            <i class="ti ti-calculator me-2"></i>Diferencia
                                                        </th>
                                                        <th class="fw-bold text-dark py-3 text-center">
                                                            <i class="ti ti-circle-dot me-2"></i>Estado
                                                        </th>
                                                        <th class="fw-bold text-dark py-3 text-center">
                                                            <i class="ti ti-settings me-2"></i>Acciones
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($slots as $slot)
                                                        @php
                                                            $difference = $slot->quantity - ($slot->kardex ?? 0);
                                                            $statusColor = match(true) {
                                                                $difference == 0 => 'success',
                                                                $difference > 0 => 'warning',
                                                                default => 'danger'
                                                            };
                                                            $statusLabel = match(true) {
                                                                $difference == 0 => 'Coincide',
                                                                $difference > 0 => 'Sobrante: +' . $difference,
                                                                default => 'Faltante: ' . abs($difference)
                                                            };
                                                        @endphp
                                                        <tr class="align-middle">
                                                            <td>
                                                                <small class="d-block fw-500">{{ $slot->product?->title ?? 'Sin Producto' }}</small>
                                                                <code class="text-muted small">{{ $slot->uid }}</code>
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="badge badge-light-info fs-6">{{ $slot->kardex ?? '0' }}</span>
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="badge badge-light-primary fs-6">{{ $slot->quantity }}</span>
                                                            </td>
                                                            <td class="text-center">
                                                                <strong class="text-{{ $statusColor }}">
                                                                    {{ $difference > 0 ? '+' : '' }}{{ $difference }}
                                                                </strong>
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="badge badge-light-{{ $statusColor }}">
                                                                    {{ $statusLabel }}
                                                                </span>
                                                            </td>
                                                            <td class="text-center">
                                                                <div class="btn-group btn-group-sm" role="group">
                                                                    <a href="{{ route('manager.warehouse.slots.view', $slot->uid) }}"
                                                                       class="btn btn-info" title="Ver Detalle">
                                                                        <i class="ti ti-eye"></i>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Paginación -->
                                        @if($slots->hasPages())
                                            <div class="mt-3 d-flex justify-content-center">
                                                {{ $slots->links() }}
                                            </div>
                                        @endif
                                    @else
                                        <div class="alert alert-light-info border">
                                            <i class="ti ti-alert-triangle me-2"></i>No hay productos en esta sección
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

@endsection
