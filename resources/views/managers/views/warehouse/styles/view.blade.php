@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-12">

            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="card-title mb-1">{{ $style->name }}</h5>
                            <p class="text-muted mb-0">Código: <strong>{{ $style->code }}</strong></p>
                        </div>
                        <div>
                            <a href="{{ route('manager.warehouse.styles.edit', $style->uid) }}" class="btn btn-sm btn-primary">
                                <i class="ti ti-pencil"></i> Editar
                            </a>
                            <a href="{{ route('manager.warehouse.styles') }}" class="btn btn-sm btn-secondary">
                                <i class="ti ti-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-light-primary border-0">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h2 class="text-primary">{{ count($style->faces) }}</h2>
                                        <p class="text-muted mb-0">Caras</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-warning border-0">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h2 class="text-warning">{{ $style->default_levels }}</h2>
                                        <p class="text-muted mb-0">Niveles</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-success border-0">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h2 class="text-success">{{ $style->default_sections }}</h2>
                                        <p class="text-muted mb-0">Secciones</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-info border-0">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h2 class="text-info">{{ $style->locations()->count() }}</h2>
                                        <p class="text-muted mb-0">Estanterías</p>
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
                                    <td><strong>{{ $style->code }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Nombre</th>
                                    <td><strong>{{ $style->name }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Estado</th>
                                    <td>
                                        @if($style->available)
                                            <span class="badge badge-light-success">Disponible</span>
                                        @else
                                            <span class="badge badge-light-danger">Inactivo</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Creado</th>
                                    <td>{{ $style->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Actualizado</th>
                                    <td>{{ $style->updated_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h6 class="mb-3">Configuración</h6>
                            <table class="table table-bordered table-sm">
                                <tr>
                                    <th width="40%">Caras</th>
                                    <td>
                                        @foreach($style->faces as $face)
                                            <span class="badge badge-light-info">{{ ucfirst($face) }}</span>
                                        @endforeach
                                    </td>
                                </tr>
                                <tr>
                                    <th>Niveles por Defecto</th>
                                    <td><strong>{{ $style->default_levels }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Secciones por Defecto</th>
                                    <td><strong>{{ $style->default_sections }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Total Posiciones</th>
                                    <td><strong>{{ count($style->faces) * $style->default_levels * $style->default_sections }}</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($style->description)
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6 class="mb-3">Descripción</h6>
                                <div class="alert alert-light-info border">
                                    {{ $style->description }}
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($style->locations()->count() > 0)
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6 class="mb-3">Estanterías Usando Este Estilo</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead class="header-item">
                                            <tr>
                                                <th>Código</th>
                                                <th>Piso</th>
                                                <th>Niveles</th>
                                                <th>Secciones</th>
                                                <th>Posiciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($style->locations as $stand)
                                                <tr>
                                                    <td>
                                                        <a href="{{ route('manager.warehouse.locations.view', [$stand->floor->warehouse->uid, $stand->floor->uid, $stand->uid]) }}">
                                                            {{ $stand->code }}
                                                        </a>
                                                    </td>
                                                    <td>{{ $stand->floor->name }}</td>
                                                    <td>{{ $stand->total_levels }}</td>
                                                    <td>{{ $stand->total_sections }}</td>
                                                    <td>{{ $stand->slots()->count() }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>

    </div>

@endsection
