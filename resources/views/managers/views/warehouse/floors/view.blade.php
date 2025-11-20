@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-12">

            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="card-title mb-1">{{ $floor->name }}</h5>
                            <p class="text-muted mb-0">Código: <strong>{{ $floor->code }}</strong></p>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('manager.warehouse.floors.edit',[$warehouse->uid, $floor->uid]) }}"
                               class="btn btn-md btn-primary me-2">
                                <i class="ti ti-pencil"></i>
                            </a>
                            <a href="{{ route('manager.warehouse.floors',[$warehouse->uid]) }}"
                               class="btn btn-md btn-secondary">
                                <i class="ti ti-arrow-left"></i>
                            </a>
                        </div>

                    </div>

                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-light-primary border-0">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h2 class="text-primary">{{ $floor->getLocationCount() }}</h2>
                                        <p class="text-muted mb-0">Estanterías</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-primary border-0">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h2 class="text-primary">{{ $floor->getTotalSlotsCount() }}</h2>
                                        <p class="text-muted mb-0">Posiciones</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-primary border-0">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h2 class="text-primary">{{ $floor->getOccupiedSlotsCount() }}</h2>
                                        <p class="text-muted mb-0">Ocupadas</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-primary border-0">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h2 class="text-primary">{{ round($floor->getOccupancyPercentage(), 1) }}%</h2>
                                        <p class="text-muted mb-0">Ocupación</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Descripción</label>
                                <p class="mb-0">
                                    {{ $floor->description ?? 'Sin descripción' }}
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Estado</label>
                                <p class="mb-0">
                                    @if($floor->available)
                                        <span class="badge bg-success">Activo</span>
                                    @else
                                        <span class="badge bg-danger">Inactivo</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="mt-4">
                        <h6 class="mb-3">Estanterías en este Piso</h6>
                        @if($floor->locations()->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Estilo</th>
                                            <th>Posiciones</th>
                                            <th>Ocupación</th>
                                            <th>Peso Actual</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($floor->locations as $stand)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('manager.warehouse.locations.view',[$warehouse->uid, $floor->uid]) }}">
                                                        {{ $stand->code }}
                                                    </a>
                                                </td>
                                                <td>{{ $stand->style?->name }}</td>
                                                <td>
                                                    <span class="badge bg-light-info">{{ $stand->getTotalSlots() }}</span>
                                                </td>
                                                <td>
                                                    <div style="width: 100px;">
                                                        <div class="progress" style="height: 15px;">
                                                            <div class="progress-bar" style="width: {{ $stand->getOccupancyPercentage() }}%">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    {{ round($stand->getOccupancyPercentage(), 1) }}%
                                                </td>
                                                <td>{{ round($stand->getCurrentWeight(), 2) }} kg</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info mb-0">
                                <i class="ti ti-info-circle me-2"></i>
                                Este piso aún no tiene estanterías registradas.
                            </div>
                        @endif
                    </div>

                </div>
            </div>

        </div>
    </div>

@endsection
