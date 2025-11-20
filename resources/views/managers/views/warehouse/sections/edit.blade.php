@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-8">

            <div class="card">
                <div class="card-body">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="card-title mb-1">
                                Editar sección
                            </h5>
                        </div>
                    </div>

                    <!-- Formulario de Edición -->
                    <form method="POST" action="{{ route('manager.warehouse.section.update', [
                        'warehouse_uid' => $warehouse->uid,
                        'floor_uid' => $floor->uid,
                        'location_uid' => $location->uid
                    ]) }}">
                        @csrf
                        <input type="hidden" name="section_uid" value="{{ $section->uid }}">

                        <!-- Información Básica -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light-primary border-bottom">
                                <p class="mb-0">
                                    Información básica
                                </p>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="code" class="form-label fw-bold">Código <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('code') is-invalid @enderror"
                                                   id="code" name="code" value="{{ $section->code }}" required>
                                            @error('code')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="barcode" class="form-label fw-bold">Código de barras</label>
                                            <input type="text" class="form-control @error('barcode') is-invalid @enderror"
                                                   id="barcode" name="barcode" value="{{ $section->barcode }}">
                                            @error('barcode')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Configuración -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light-primary border-bottom">
                                <p class="mb-0">
                                   Configuración
                                </p>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="level" class="form-label fw-bold">Nivel (Altura) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control @error('level') is-invalid @enderror"
                                                   id="level" name="level" value="{{ $section->level }}" min="1" required>
                                            @error('level')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Número de nivel/altura de la sección</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notas -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light-primary border-bottom">
                                <p class="mb-0">
                                    Notas
                                </p>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <textarea class="form-control @error('notes') is-invalid @enderror"
                                              id="notes" name="notes" rows="4" placeholder="Agregar notas sobre esta sección...">{{ $section->notes }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Estado -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light-primary border-bottom">
                                <p class="mb-0">
                                    Estado
                                </p>
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="available" name="available"
                                           value="1" {{ $section->available ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="available">
                                        Sección activa
                                    </label>
                                    <small class="d-block text-muted mt-1">La sección estará disponible para recibir productos</small>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100">
                                Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>

        <!-- Sidebar: Estadísticas -->
        <div class="col-lg-4">

            <!-- Información de Ubicación -->
            <div class="card mb-4">
                <div class="card-header bg-light-primary border-bottom">
                    <p class="mb-0">
                        Ubicación
                    </p>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small mb-1">Sección</label>
                        <p class="mb-0">{{ $section->code }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small mb-1">Ubicación</label>
                        <p class="mb-0">
                            <a href="{{ route('manager.warehouse.locations.view', [$warehouse->uid, $floor->uid, $location->uid]) }}" class="text-primary">
                                {{ $location->code }}
                            </a>
                        </p>
                    </div>
                    <div class="mb-0">
                        <label class="form-label text-muted small mb-1">Piso</label>
                        <p class="mb-0">
                            <a href="{{ route('manager.warehouse.floors.view', [$warehouse->uid, $floor->uid]) }}" class="text-primary">
                                {{ $floor->name }}
                            </a>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Estadísticas de Slots -->
            <div class="card mb-4">
                <div class="card-header bg-light-primary border-bottom">
                    <p class="mb-0">
                        Estadísticas de slots
                    </p>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Total de Slots</span>
                            <span class="badge badge-light-primary fs-6">{{ $section->getTotalSlots() }}</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Slots Ocupados</span>
                            <span class="badge badge-light-primary fs-6">{{ $section->getOccupiedSlots() }}</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Slots Disponibles</span>
                            <span class="badge badge-light-primary fs-6">{{ $section->getAvailableSlots() }}</span>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="mb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Ocupación</span>
                            <span class="badge badge-light-primary fs-6">{{ round($section->getOccupancyPercentage(), 1) }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información de Auditoría -->
            <div class="card">
                <div class="card-header bg-light-primary border-bottom">
                    <p class="mb-0">
                        Auditoría
                    </p>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small mb-1">Creada</label>
                        <p class="mb-0">{{ $section->created_at->format('d/m/Y H:i:s') }}</p>
                    </div>
                    <div class="mb-0">
                        <label class="form-label text-muted small mb-1">Última Actualización</label>
                        <p class="mb-0">{{ $section->updated_at->format('d/m/Y H:i:s') }}</p>
                    </div>
                </div>
            </div>

        </div>

    </div>

@endsection
