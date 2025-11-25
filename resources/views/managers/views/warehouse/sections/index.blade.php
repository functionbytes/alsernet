@extends('layouts.manager')

@section('content')
<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3">
                <i class="fas fa-cube"></i> Secciones de {{ $location->code }}
            </h1>
            <p class="text-muted">
                <strong>Ubicación:</strong> {{ $location->code }} |
                <strong>Piso:</strong> {{ $floor->name }} |
                <strong>Almacén:</strong> {{ $warehouse->name }}
            </p>
        </div>
        <div class="col-md-4 text-right">
            <button class="btn btn-primary" data-toggle="modal" data-target="#modalCreateSection">
                <i class="fas fa-plus"></i> Crear Sección
            </button>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="row mb-3">
        <div class="col-md-6">
            <input type="text" class="form-control" id="searchSections" placeholder="Buscar por código...">
        </div>
    </div>

    <!-- Tabla de Secciones -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="fas fa-layer-group"></i> Secciones
                <span class="badge badge-primary">{{ $sections->total() }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Código</th>
                            <th>Código de Barras</th>
                            <th>Nivel</th>
                            <th>Slots</th>
                            <th>Ocupados</th>
                            <th>Disponibles</th>
                            <th>Ocupación</th>
                            <th>Cantidad Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sections as $section)
                            <tr class="align-middle">
                                <td>
                                    <strong>{{ $section->code }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $section->uid }}</small>
                                </td>
                                <td>
                                    @if($section->barcode)
                                        <code>{{ $section->barcode }}</code>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-info">Nivel {{ $section->level }}</span>
                                </td>
                                <td>
                                    <strong>{{ $section->getTotalSlots() }}</strong>
                                </td>
                                <td>
                                    <span class="text-danger">{{ $section->getOccupiedSlots() }}</span>
                                </td>
                                <td>
                                    <span class="text-success">{{ $section->getAvailableSlots() }}</span>
                                </td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-warning" role="progressbar"
                                             style="width: {{ $section->getOccupancyPercentage() }}%;"
                                             aria-valuenow="{{ $section->getOccupancyPercentage() }}"
                                             aria-valuemin="0" aria-valuemax="100">
                                            {{ round($section->getOccupancyPercentage(), 1) }}%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong>{{ $section->getTotalQuantity() }}</strong>
                                    @if($section->max_quantity)
                                        / <span class="text-muted">{{ $section->max_quantity }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($section->available)
                                        <span class="badge badge-success">Activa</span>
                                    @else
                                        <span class="badge badge-secondary">Inactiva</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('manager.warehouse.section.view', [
                                            'warehouse_uid' => $warehouse->uid,
                                            'floor_uid' => $floor->uid,
                                            'location_uid' => $location->uid,
                                            'section_uid' => $section->uid
                                        ]) }}" class="btn btn-sm btn-info" title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('manager.warehouse.section.edit', [
                                            'warehouse_uid' => $warehouse->uid,
                                            'floor_uid' => $floor->uid,
                                            'location_uid' => $location->uid,
                                            'section_uid' => $section->uid
                                        ]) }}" class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="{{ route('manager.warehouse.section.destroy', [
                                            'warehouse_uid' => $warehouse->uid,
                                            'floor_uid' => $floor->uid,
                                            'location_uid' => $location->uid,
                                            'section_uid' => $section->uid
                                        ]) }}" class="btn btn-sm btn-danger"
                                           onclick="return confirm('¿Está seguro?')" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <p class="text-muted">No hay secciones creadas</p>
                                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCreateSection">
                                        Crear primera sección
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Paginación -->
    @if($sections->hasPages())
        <div class="mt-3">
            {{ $sections->links() }}
        </div>
    @endif
</div>

<!-- Modal: Crear Sección -->
<div class="modal fade" id="modalCreateSection" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i> Crear Nueva Sección
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('manager.warehouse.sections.store', [
                'warehouse_uid' => $warehouse->uid,
                'floor_uid' => $floor->uid,
                'location_uid' => $location->uid
            ]) }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="code" class="font-weight-bold">Código <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('code') is-invalid @enderror"
                               id="code" name="code" placeholder="ej: SEC-A1" required>
                        @error('code')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="barcode" class="font-weight-bold">Código de Barras</label>
                        <input type="text" class="form-control" id="barcode" name="barcode" placeholder="opcional">
                    </div>

                    <div class="form-group">
                        <label for="level" class="font-weight-bold">Nivel (Altura) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('level') is-invalid @enderror"
                               id="level" name="level" value="{{ $next_level ?? 1 }}" min="1" required>
                        <small class="form-text text-muted">Posición vertical en la ubicación</small>
                        @error('level')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="max_quantity" class="font-weight-bold">Cantidad Máxima</label>
                        <input type="number" class="form-control" id="max_quantity" name="max_quantity"
                               min="1" placeholder="opcional">
                        <small class="form-text text-muted">Capacidad máxima de productos</small>
                    </div>

                    <div class="form-group">
                        <label for="notes" class="font-weight-bold">Notas</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Notas adicionales..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Crear Sección
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .progress {
        background-color: #e9ecef;
    }
</style>

<script>
    // Buscar secciones
    document.getElementById('searchSections').addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        document.querySelectorAll('table tbody tr').forEach(row => {
            const code = row.querySelector('td:first-child').textContent.toLowerCase();
            row.style.display = code.includes(filter) ? '' : 'none';
        });
    });
</script>
@endsection
