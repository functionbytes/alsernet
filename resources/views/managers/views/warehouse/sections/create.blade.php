@extends('layouts.manager')

@section('content')
<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3">
                <i class="fas fa-plus"></i> Crear Nueva Sección
            </h1>
            <p class="text-muted">
                <strong>Ubicación:</strong> {{ $location->code }} |
                <strong>Piso:</strong> {{ $floor->name }} |
                <strong>Almacén:</strong> {{ $warehouse->name }}
            </p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('manager.warehouse.sections', [
                'warehouse_uid' => $warehouse->uid,
                'floor_uid' => $floor->uid,
                'location_uid' => $location->uid
            ]) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <!-- Formulario de Creación -->
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('manager.warehouse.sections.store', [
                'warehouse_uid' => $warehouse->uid,
                'floor_uid' => $floor->uid,
                'location_uid' => $location->uid
            ]) }}">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="code" class="font-weight-bold">Código <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror"
                                   id="code" name="code" placeholder="ej: SEC-A1" value="{{ old('code') }}" required>
                            <small class="form-text text-muted">Identificador único de la sección dentro de esta ubicación</small>
                            @error('code')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="barcode" class="font-weight-bold">Código de Barras</label>
                            <input type="text" class="form-control" id="barcode" name="barcode"
                                   placeholder="opcional" value="{{ old('barcode') }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="level" class="font-weight-bold">Nivel (Altura) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('level') is-invalid @enderror"
                                   id="level" name="level" value="{{ old('level', $next_level ?? 1) }}" min="1" required>
                            <small class="form-text text-muted">Posición vertical dentro de la ubicación</small>
                            @error('level')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="max_quantity" class="font-weight-bold">Cantidad Máxima</label>
                            <input type="number" class="form-control" id="max_quantity" name="max_quantity"
                                   placeholder="opcional" value="{{ old('max_quantity') }}" min="1">
                            <small class="form-text text-muted">Capacidad máxima de productos</small>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes" class="font-weight-bold">Notas</label>
                    <textarea class="form-control" id="notes" name="notes" rows="4" placeholder="Notas adicionales...">{{ old('notes') }}</textarea>
                </div>

                <hr>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Crear Sección
                    </button>
                    <a href="{{ route('manager.warehouse.sections', [
                        'warehouse_uid' => $warehouse->uid,
                        'floor_uid' => $floor->uid,
                        'location_uid' => $location->uid
                    ]) }}" class="btn btn-secondary btn-lg">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Información de Ayuda -->
    <div class="row mt-4">
        <div class="col-md-8">
            <div class="card bg-light">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle"></i> Información sobre Secciones
                    </h5>
                </div>
                <div class="card-body">
                    <ul>
                        <li><strong>Código:</strong> Debe ser único dentro de esta ubicación (ej: SEC-A1, SEC-B1)</li>
                        <li><strong>Nivel:</strong> Indica la posición vertical (1 = primer nivel, 2 = segundo nivel, etc.)</li>
                        <li><strong>Código de Barras:</strong> Opcional, útil para scanning rápido</li>
                        <li><strong>Cantidad Máxima:</strong> Opcional, establece un límite de productos en esta sección</li>
                        <li>Una vez creada, podrás agregar productos específicos a esta sección</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted">Próximo Nivel Disponible</h6>
                    <h2 class="font-weight-bold text-primary">{{ $next_level ?? 1 }}</h2>
                    <p class="text-muted mt-2">Ya existen {{ max(($next_level ?? 1) - 1, 0) }} secciones</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
