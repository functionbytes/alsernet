@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-12">

            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="card-title mb-1">Posición de Inventario</h5>
                            <p class="text-muted mb-0">Código de Barras: <strong>{{ $slot->barcode ?? 'N/A' }}</strong></p>
                        </div>
                        <div>
                            <a href="{{ route('manager.warehouse.slots.edit', $slot->uid) }}" class="btn btn-sm btn-primary">
                                <i class="ti ti-pencil"></i> Editar
                            </a>
                            <a href="{{ route('manager.warehouse.slots') }}" class="btn btn-sm btn-secondary">
                                <i class="ti ti-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-light-primary border-0">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h2 class="text-primary">{{ $slot->quantity }}</h2>
                                        <p class="text-muted mb-0">Cantidad</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-warning border-0">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h2 class="text-warning">{{ round(($slot->quantity / ($slot->max_quantity ?? 1)) * 100) }}%</h2>
                                        <p class="text-muted mb-0">Ocupación Cantidad</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-success border-0">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h2 class="text-success">{{ $slot->weight_current }}</h2>
                                        <p class="text-muted mb-0">Peso Actual (kg)</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-info border-0">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h2 class="text-info">{{ round(($slot->weight_current / ($slot->weight_max ?? 1)) * 100) }}%</h2>
                                        <p class="text-muted mb-0">Ocupación Peso</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3">Ubicación</h6>
                            <table class="table table-bordered table-sm">
                                <tr>
                                    <th width="40%">Estantería</th>
                                    <td>
                                        <a href="{{ route('manager.warehouse.locations.view', $slot->stand->uid) }}">
                                            <strong>{{ $slot->stand->code }}</strong>
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Piso</th>
                                    <td>
                                        <a href="{{ route('manager.warehouse.floors.view', $slot->stand->floor->uid) }}">
                                            {{ $slot->stand->floor->name }}
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Cara</th>
                                    <td><strong>{{ ucfirst($slot->face) }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Nivel</th>
                                    <td><strong>{{ $slot->level }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Sección</th>
                                    <td><strong>{{ $slot->section }}</strong></td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h6 class="mb-3">Información General</h6>
                            <table class="table table-bordered table-sm">
                                <tr>
                                    <th width="40%">Estado</th>
                                    <td>
                                        @if($slot->is_occupied)
                                            <span class="badge badge-light-success">Ocupada</span>
                                        @else
                                            <span class="badge badge-light-secondary">Disponible</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Código de Barras</th>
                                    <td>{{ $slot->barcode ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Creado</th>
                                    <td>{{ $slot->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Actualizado</th>
                                    <td>{{ $slot->updated_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Último Movimiento</th>
                                    <td>{{ $slot->last_movement ? $slot->last_movement->format('d/m/Y H:i') : '—' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($slot->product)
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6 class="mb-3">Producto Almacenado</h6>
                                <div class="card bg-light-info border">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Nombre:</strong> {{ $slot->product->title }}</p>
                                                <p><strong>Barcode:</strong> {{ $slot->product->barcode ?? 'N/A' }}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>SKU:</strong> {{ $slot->product->sku ?? 'N/A' }}</p>
                                                <p><strong>Categoría:</strong> {{ $slot->product->category ?? 'N/A' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="mb-3">Capacidad y Contenido</h6>
                            <div class="table-responsive">
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
                                        <tr>
                                            <td><strong>Cantidad</strong></td>
                                            <td>{{ $slot->quantity }}</td>
                                            <td>{{ $slot->max_quantity ?? '—' }}</td>
                                            <td>{{ $slot->max_quantity ? ($slot->max_quantity - $slot->quantity) : '—' }}</td>
                                            <td>
                                                @if($slot->max_quantity)
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar {{ $slot->quantity / $slot->max_quantity > 0.75 ? 'bg-danger' : ($slot->quantity / $slot->max_quantity > 0.5 ? 'bg-warning' : 'bg-success') }}"
                                                             role="progressbar"
                                                             style="width: {{ ($slot->quantity / $slot->max_quantity) * 100 }}%"
                                                             aria-valuenow="{{ ($slot->quantity / $slot->max_quantity) * 100 }}" aria-valuemin="0" aria-valuemax="100">
                                                            {{ round(($slot->quantity / $slot->max_quantity) * 100) }}%
                                                        </div>
                                                    </div>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Peso (kg)</strong></td>
                                            <td>{{ $slot->weight_current }}</td>
                                            <td>{{ $slot->weight_max ?? '—' }}</td>
                                            <td>{{ $slot->weight_max ? ($slot->weight_max - $slot->weight_current) : '—' }}</td>
                                            <td>
                                                @if($slot->weight_max)
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar {{ $slot->weight_current / $slot->weight_max > 0.75 ? 'bg-danger' : ($slot->weight_current / $slot->weight_max > 0.5 ? 'bg-warning' : 'bg-success') }}"
                                                             role="progressbar"
                                                             style="width: {{ ($slot->weight_current / $slot->weight_max) * 100 }}%"
                                                             aria-valuenow="{{ ($slot->weight_current / $slot->weight_max) * 100 }}" aria-valuemin="0" aria-valuemax="100">
                                                            {{ round(($slot->weight_current / $slot->weight_max) * 100) }}%
                                                        </div>
                                                    </div>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        </tr>
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
