@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-12">

            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="card-title mb-1">{{ $location->code }}</h5>
                            <p class="text-muted mb-0">Estantería en: <strong>{{ $location->floor->name }}</strong></p>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('manager.warehouse.locations.edit', [$location->floor->warehouse->uid,$location->floor->uid, $location->uid]) }}" class="btn btn-md btn-primary me-2">
                                <i class="ti ti-pencil"></i>
                            </a>
                            <a href="{{ route('manager.warehouse.locations',[$location->floor->warehouse->uid,$location->floor->uid]) }}" class="btn btn-md btn-secondary">
                                <i class="ti ti-arrow-left"></i>
                            </a>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-light-primary border-0">
                                <div class="card-body">
                                    <div class="">
                                        <h2 class="text-primary">{{ $location->getTotalSlots() }}</h2>
                                        <p class="text-muted mb-0">Total de Posiciones</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-primary border-0">
                                <div class="card-body">
                                    <div class="">
                                        <h2 class="text-primary">{{ $location->getOccupiedSlots() }}</h2>
                                        <p class="text-muted mb-0">Posiciones Ocupadas</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-primary border-0">
                                <div class="card-body">
                                    <div class="">
                                        <h2 class="text-primary">{{ $location->getTotalSlots() - $location->getOccupiedSlots() }}</h2>
                                        <p class="text-muted mb-0">Posiciones Disponibles</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-primary border-0">
                                <div class="card-body">
                                    <div class="">
                                        <h2 class="text-primary">{{ round($location->getOccupancyPercentage(), 1) }}%</h2>
                                        <p class="text-muted mb-0">Ocupación</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información General -->
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
                                        <p class="mb-0"><strong class="text-dark">{{ $location->code }}</strong></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted small mb-1">Piso</label>
                                        <p class="mb-0">
                                            <a href="{{ route('manager.warehouse.floors.view',[$location->floor->warehouse->uid, $location->floor->uid]) }}" class="text-primary">
                                                {{ $location->floor->name }}
                                            </a>
                                        </p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted small mb-1">Estilo</label>
                                        <p class="mb-0">
                                             {{ $location->style->name }}
                                        </p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted small mb-1">Estado</label>
                                        <p class="mb-0">
                                            @if($location->available)
                                                   Disponible
                                            @else
                                               No disponible
                                            @endif
                                        </p>
                                    </div>
                                    <hr class="my-3">
                                    <div class="row">
                                        <div class="col-6">
                                            <label class="form-label text-muted small mb-1">Creado</label>
                                            <p class="d-block">{{ $location->created_at->format('d/m/Y H:i') }}</p>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label text-muted small mb-1">Actualizado</label>
                                            <p class="d-block">{{ $location->updated_at->format('d/m/Y H:i') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Configuración Física -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-light-primary border-bottom">
                                    <p class="mb-0">
                                        <i class="ti ti-box me-2"></i>Configuración Física
                                    </p>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <label class="form-label text-muted small mb-1">Posición X</label>
                                            <p class="mb-0"><strong>{{ $location->position_x }} m</strong></p>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label text-muted small mb-1">Posición Y</label>
                                            <p class="mb-0"><strong>{{ $location->position_y }} m</strong></p>
                                        </div>
                                    </div>
                                    <hr class="my-3">
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <label class="form-label text-muted small mb-1">Niveles</label>
                                            <p class="mb-0"><strong>{{ $location->total_levels }}</strong></p>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>


                    @if($location->notes)
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6 class="mb-3">Notas</h6>
                                <div class="alert alert-light-info border">
                                    {{ $location->notes }}
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Secciones -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light-primary border-bottom d-flex justify-content-between align-items-center">
                                    <p class="mb-0">
                                       Secciones
                                    </p>
                                </div>
                                <div class="card-body p-0">

                            @if($location->sections && count($location->sections) > 0)
                                <div class="table-responsive border rounded-2 bg-white">
                                    <table class="table table-sm table-hover table-striped mb-0">
                                        <thead class="table-light sticky-top" style="top: 0; z-index: 10;">
                                            <tr>
                                                <th class="fw-bold text-dark py-3">
                                                    Código
                                                </th>
                                                <th class="fw-bold text-dark py-3">
                                                    Nivel
                                                </th>
                                                <th class="fw-bold text-dark py-3">
                                                    Total Slots
                                                </th>
                                                <th class="fw-bold text-dark py-3">
                                                    Ocupados
                                                </th>
                                                <th class="fw-bold text-dark py-3">
                                                   Disponibles
                                                </th>
                                                <th class="fw-bold text-dark py-3">
                                                    Ocupación
                                                </th>
                                                <th class="fw-bold text-dark py-3">
                                                    Estado
                                                </th>
                                                <th class="fw-bold text-dark py-3">
                                                    Acciones
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($location->sections as $section)
                                                <tr>
                                                    <td>
                                                        {{ $section->code }}
                                                    </td>
                                                    <td>
                                                         {{ $section->level }}
                                                    </td>
                                                    <td class="">
                                                        {{ $section->getTotalSlots() }}
                                                    </td>
                                                    <td class="">
                                                        {{ $section->getOccupiedSlots() }}
                                                    </td>
                                                    <td class="">
                                                       {{ $section->getAvailableSlots() }}
                                                    </td>
                                                    <td class="">
                                                            @php
                                                                $occupancy = $section->getOccupancyPercentage();
                                                                $color = $occupancy < 50 ? 'success' : ($occupancy < 85 ? 'warning' : 'danger');
                                                            @endphp
                                                           {{ round($occupancy, 1) }}%
                                                    </td>
                                                    <td>
                                                        @if($section->available)
                                                            Activa
                                                        @else
                                                            Inactiva
                                                        @endif
                                                    </td>

                                                    <td class="text-left">
                                                        <div class="dropdown dropstart">
                                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="ti ti-dots fs-5"></i>
                                                            </a>
                                                            <ul class="dropdown-menu">
                                                                <li>
                                                                    <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('manager.warehouse.section.view', [$location->floor->warehouse->uid, $location->floor->uid, $location->uid, $section->uid]) }}" >
                                                                        Ver
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('manager.warehouse.section.edit', [$location->floor->warehouse->uid, $location->floor->uid, $location->uid, $section->uid]) }}">
                                                                        Editar
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
                                <div class="alert alert-light-warning border">
                                    <i class="ti ti-alert-triangle me-2"></i>No hay secciones registradas para esta ubicación
                                </div>
                            @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light-primary border-bottom">
                                    <p class="mb-0">
                                        Posiciones de Inventario
                                    </p>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive border rounded-2 bg-white">
                                        <table class="table table-sm table-hover table-striped mb-0">
                                    <thead class="table-light sticky-top" style="top: 0; z-index: 10;">
                                        <tr>
                                            <th class="fw-bold text-dark py-3">
                                               Código de Barras
                                            </th>
                                            <th class="fw-bold text-dark py-3">
                                                Ubicación
                                            </th>
                                            <th class="fw-bold text-dark py-3">
                                               Producto
                                            </th>
                                            <th class="fw-bold text-dark py-3">
                                                Cantidad
                                            </th>
                                            <th class="fw-bold text-dark py-3">
                                                Peso
                                            </th>
                                            <th class="fw-bold text-dark py-3">
                                                Estado
                                            </th>
                                            <th class="fw-bold text-dark py-3">
                                                Acciones
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($location->slots as $slot)
                                            <tr class="align-middle">
                                                <td>
                                                    <code class="bg-light px-2 py-1 rounded">{{ $slot->barcode ?? '—' }}</code>
                                                </td>
                                                <td>
                                                    <small class="d-block">
                                                        <strong class="text-dark">{{ ucfirst($slot->face) }}</strong>
                                                    </small>
                                                    <small class="text-muted">Nivel {{ $slot->level }} • Secc. {{ $slot->section }}</small>
                                                </td>
                                                <td>
                                                    @if($slot->product)
                                                        <a href="#" class="text-decoration-none">
                                                            <small class="text-dark fw-500">{{ $slot->product->title }}</small>
                                                        </a>
                                                    @else
                                                        <span class="text-muted small">—</span>
                                                    @endif
                                                </td>
                                                <td class="">
                                                    <span class="badge badge-light-primary ">
                                                        {{ $slot->quantity }}{{ $slot->max_quantity ? " / {$slot->max_quantity}" : '' }}
                                                    </span>
                                                </td>
                                                <td class="">
                                                    <small class="d-block fw-500">{{ $slot->weight_current }} kg</small>
                                                    @if($slot->weight_max)
                                                        <small class="text-muted">/ {{ $slot->weight_max }} kg</small>
                                                    @endif
                                                </td>
                                                <td class="">
                                                    @if($slot->is_occupied)
                                                        <span class="badge badge-light-success">
                                                            <i class="ti ti-check me-1"></i>Ocupada
                                                        </span>
                                                    @else
                                                        <span class="badge badge-light-secondary">
                                                            <i class="ti ti-x me-1"></i>Disponible
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('manager.warehouse.slots.view', $slot->uid) }}" class="btn btn-sm btn-info">
                                                        <i class="ti ti-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted py-4">
                                                    No hay posiciones de inventario registradas
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

@endsection
