@extends('layouts.managers')

@section('content')

<div class="row">
    <div class="col-lg-12 d-flex align-items-stretch">
        <div class="card w-100">
            <form id="formLocationTransfer" action="{{ route('manager.warehouse.locations.transfer.store', [$warehouse->uid, $floor->uid, $location->uid]) }}" method="POST" role="form">
                {{ csrf_field() }}
                <input type="hidden" name="warehouse_uid" value="{{ $warehouse->uid }}">
                <input type="hidden" name="floor_uid" value="{{ $floor->uid }}">

                <div class="card-body border-top">
                    <div class="d-flex no-block align-items-center">
                        <h5 class="mb-0">Trasladar Ubicación: {{ $location->code }}</h5>
                    </div>
                    <p class="card-subtitle mb-3 mt-3">
                        Selecciona el piso destino para trasladar esta ubicación.
                    </p>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row">
                        <!-- Header Info -->
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Almacén</label>
                                <input type="text" class="form-control" value="{{ $warehouse->name }}" disabled>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Piso Origen</label>
                                <input type="text" class="form-control" value="{{ $floor->name }} ({{ $floor->code }})" disabled>
                            </div>
                        </div>

                        <!-- Ubicación a Trasladar -->
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Ubicación a Trasladar</label>
                                <input type="text" class="form-control" value="{{ $location->code }} - {{ $location->style?->name ?? 'Sin estilo' }}" disabled>
                                <input type="hidden" name="location_uid" value="{{ $location->uid }}">
                            </div>
                        </div>

                        <!-- Piso Destino -->
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Piso Destino <span class="text-danger">*</span></label>
                                @if($availableFloors->isEmpty())
                                    <input type="text" class="form-control" value="No hay otros pisos disponibles" disabled>
                                    <small class="text-danger d-block mt-1">No hay pisos disponibles para trasladar</small>
                                @else
                                    <select id="target_floor_uid" name="target_floor_uid" class="select2 form-control @error('target_floor_uid') is-invalid @enderror" required>
                                        <option value="">-- Seleccionar Piso --</option>
                                        @foreach($availableFloors as $f)
                                            <option value="{{ $f->uid }}" {{ old('target_floor_uid') == $f->uid ? 'selected' : '' }}>
                                                {{ $f->name }} ({{ $f->code }}) - {{ $f->locations()->count() }} ubicaciones
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('target_floor_uid')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                @endif
                            </div>
                        </div>

                        <!-- Info Adicional -->
                        <div class="col-12">
                            <div class="alert alert-info">
                                <strong>⚠️ Advertencia:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Si una ubicación ya existe en el piso destino (mismo código), no se trasladará</li>
                                    <li>El traslado se registrará en el historial de cambios</li>
                                    <li>El inventario dentro de las ubicaciones no se verá afectado</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="col-12">
                            <div class="border-top pt-3 mt-4">
                                <button type="submit" class="btn btn-primary px-4 waves-effect waves-light mt-2" {{ $availableFloors->isEmpty() ? 'disabled' : '' }}>
                                    <i class="fa-duotone fa-arrow-right"></i> Trasladar Ubicaciones
                                </button>
                                <a href="{{ route('manager.warehouse.locations', [$warehouse->uid, $floor->uid]) }}" class="btn btn-secondary px-4 waves-effect waves-light mt-2">
                                    <i class="fa-duotone fa-times"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar Select2 para piso destino
    $('#target_floor_uid').select2({
        placeholder: 'Selecciona el piso destino',
        allowClear: true,
        width: '100%',
        language: 'es'
    });

    // Validación del formulario
    document.getElementById('formLocationTransfer').addEventListener('submit', function(e) {
        const targetFloor = document.getElementById('target_floor_uid').value;

        if (!targetFloor) {
            e.preventDefault();
            alert('Debes seleccionar un piso destino');
            return false;
        }

        // Confirmación
        const locationCode = '{{ $location->code }}';
        const confirmed = confirm(`¿Deseas trasladar la ubicación ${locationCode} al piso seleccionado?`);
        if (!confirmed) {
            e.preventDefault();
        }
    });
});
</script>

@endsection
