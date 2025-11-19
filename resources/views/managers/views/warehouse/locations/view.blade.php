@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-12">

            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="card-title mb-1">{{ $stand->code }}</h5>
                            <p class="text-muted mb-0">Estantería en: <strong>{{ $stand->floor->name }}</strong></p>
                        </div>
                        <div>
                            <a href="{{ route('manager.warehouse.locations.edit', $stand->uid) }}" class="btn btn-sm btn-primary">
                                <i class="ti ti-pencil"></i> Editar
                            </a>
                            <a href="{{ route('manager.warehouse.locations') }}" class="btn btn-sm btn-secondary">
                                <i class="ti ti-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-light-primary border-0">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h2 class="text-primary">{{ $stand->getTotalSlots() }}</h2>
                                        <p class="text-muted mb-0">Total de Posiciones</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-warning border-0">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h2 class="text-warning">{{ $stand->getOccupiedSlots() }}</h2>
                                        <p class="text-muted mb-0">Posiciones Ocupadas</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-success border-0">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h2 class="text-success">{{ $stand->getTotalSlots() - $stand->getOccupiedSlots() }}</h2>
                                        <p class="text-muted mb-0">Posiciones Disponibles</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-info border-0">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h2 class="text-info">{{ round($stand->getOccupancyPercentage(), 1) }}%</h2>
                                        <p class="text-muted mb-0">Ocupación</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3">Información General</h6>
                            <table class="table table-bordered table-sm">
                                <tr>
                                    <th width="40%">Código</th>
                                    <td><strong>{{ $stand->code }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Código de Barras</th>
                                    <td>{{ $stand->barcode ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Piso</th>
                                    <td>
                                        <a href="{{ route('manager.warehouse.floors.view', $stand->floor->uid) }}">
                                            {{ $stand->floor->name }}
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Estilo</th>
                                    <td>
                                        <a href="{{ route('manager.warehouse.styles.view', $stand->style->uid) }}">
                                            {{ $stand->style->name }}
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Estado</th>
                                    <td>
                                        @if($stand->available)
                                            <span class="badge badge-light-success">Disponible</span>
                                        @else
                                            <span class="badge badge-light-danger">No disponible</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Creado</th>
                                    <td>{{ $stand->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Actualizado</th>
                                    <td>{{ $stand->updated_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h6 class="mb-3">Configuración Física</h6>
                            <table class="table table-bordered table-sm">
                                <tr>
                                    <th width="40%">Posición X</th>
                                    <td><strong>{{ $stand->position_x }}m</strong></td>
                                </tr>
                                <tr>
                                    <th>Posición Y</th>
                                    <td><strong>{{ $stand->position_y }}m</strong></td>
                                </tr>
                                <tr>
                                    <th>Posición Z (Altura)</th>
                                    <td><strong>{{ $stand->position_z ?? '—' }}m</strong></td>
                                </tr>
                                <tr>
                                    <th>Niveles Totales</th>
                                    <td><strong>{{ $stand->total_levels }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Secciones Totales</th>
                                    <td><strong>{{ $stand->total_sections }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Caras</th>
                                    <td>
                                        @foreach($stand->style->faces as $face)
                                            <span class="badge badge-light-info">{{ ucfirst($face) }}</span>
                                        @endforeach
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="mb-3">Capacidad y Contenido</h6>
                            <table class="table table-sm">
                                <thead class="header-item">
                                    <tr>
                                        <th>Parámetro</th>
                                        <th>Actual</th>
                                        <th>Máximo</th>
                                        <th>Disponible</th>
                                        <th>Ocupación %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($stand->capacity)
                                        <tr>
                                            <td><strong>Peso (kg)</strong></td>
                                            <td>{{ round($stand->getCurrentWeight(), 2) }}</td>
                                            <td>{{ round($stand->capacity, 2) }}</td>
                                            <td>{{ round($stand->capacity - $stand->getCurrentWeight(), 2) }}</td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar {{ ($stand->getCurrentWeight() / $stand->capacity) * 100 > 75 ? 'bg-danger' : (($stand->getCurrentWeight() / $stand->capacity) * 100 > 50 ? 'bg-warning' : 'bg-success') }}"
                                                         role="progressbar"
                                                         style="width: {{ min(($stand->getCurrentWeight() / $stand->capacity) * 100, 100) }}%"
                                                         aria-valuenow="{{ ($stand->getCurrentWeight() / $stand->capacity) * 100 }}" aria-valuemin="0" aria-valuemax="100">
                                                        {{ round(($stand->getCurrentWeight() / $stand->capacity) * 100, 1) }}%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">
                                                No hay límite de capacidad configurado para esta estantería
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if($stand->notes)
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6 class="mb-3">Notas</h6>
                                <div class="alert alert-light-info border">
                                    {{ $stand->notes }}
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="mb-3">Posiciones de Inventario</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="header-item">
                                        <tr>
                                            <th>Código de Barras</th>
                                            <th>Ubicación</th>
                                            <th>Producto</th>
                                            <th>Cantidad</th>
                                            <th>Peso</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($stand->slots as $slot)
                                            <tr>
                                                <td>{{ $slot->barcode ?? '—' }}</td>
                                                <td>
                                                    <small>
                                                        <strong>{{ ucfirst($slot->face) }}</strong> /
                                                        Nivel {{ $slot->level }} /
                                                        Secc. {{ $slot->section }}
                                                    </small>
                                                </td>
                                                <td>
                                                    @if($slot->product)
                                                        <a href="#">{{ $slot->product->title }}</a>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>{{ $slot->quantity }} {{ $slot->max_quantity ? "/ {$slot->max_quantity}" : '' }}</td>
                                                <td>{{ $slot->weight_current }} {{ $slot->weight_max ? "/ {$slot->weight_max}" : '' }} kg</td>
                                                <td>
                                                    @if($slot->is_occupied)
                                                        <span class="badge badge-light-success">Ocupada</span>
                                                    @else
                                                        <span class="badge badge-light-secondary">Disponible</span>
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

@endsection
