@extends('layouts.managers')

@section('content')

<div class="row">
    <div class="col-lg-12 d-flex align-items-stretch">
        <div class="card w-100">
            <form id="formLocationTransferBulk" action="{{ route('manager.warehouse.locations.transfer.bulk.store', [$warehouse->uid, $floor->uid]) }}" method="POST" role="form">
                {{ csrf_field() }}
                <input type="hidden" name="warehouse_uid" value="{{ $warehouse->uid }}">
                <input type="hidden" name="floor_uid" value="{{ $floor->uid }}">

                <div class="card-body border-top">
                    <div class="d-flex no-block align-items-center">
                        <h5 class="mb-0">Trasladar múltiples ubicaciones</h5>
                    </div>
                    <p class="card-subtitle mb-3 mt-3">
                        Selecciona una o múltiples ubicaciones para trasladarlas a otro piso del almacén.
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
                                <label class="control-label col-form-label">Piso origen</label>
                                <input type="text" class="form-control" value="{{ $floor->name }} ({{ $floor->code }})" disabled>
                            </div>
                        </div>

                        <!-- Ubicaciones a Trasladar -->
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Ubicaciones a trasladar <span class="text-danger">*</span></label>
                                <select id="location_uids" name="location_uids[]" class="select2 form-control @error('location_uids') is-invalid @enderror" multiple="multiple" required>
                                    @forelse($floor->locations()->orderBy('code')->get() as $loc)
                                        <option value="{{ $loc->uid }}">{{ $loc->code }} - {{ $loc->style?->name ?? 'Sin estilo' }}</option>
                                    @empty
                                        <option disabled>No hay ubicaciones en este piso</option>
                                    @endforelse
                                </select>
                                <small class="text-muted d-block mt-1">Puedes seleccionar una o varias ubicaciones. Usa Ctrl+Click para seleccionar múltiples.</small>
                                @error('location_uids')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Piso Destino -->
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Piso destino <span class="text-danger">*</span></label>
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
                                <strong>⚠️ Información importante:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Si una ubicación ya existe en el piso destino (mismo código), no se trasladará</li>
                                    <li>El traslado se registrará en el historial de cambios</li>
                                    <li>El inventario dentro de las ubicaciones no se verá afectado</li>
                                    <li>Puedes seleccionar todas las ubicaciones del piso para trasladarlas</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="col-12">
                            <div class="border-top pt-3 mt-4">
                                <button type="submit" class="btn btn-primary px-4 waves-effect waves-light mt-2 w-100" {{ $availableFloors->isEmpty() ? 'disabled' : '' }}>
                                    Trasladar
                                </button>
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
    // Inicializar Select2 para múltiples ubicaciones
    $('#location_uids').select2({
        placeholder: 'Selecciona una o varias ubicaciones',
        allowClear: true,
        width: '100%',
        language: 'es',
        matcher: function(params, data) {
            if (!params.term) {
                return data;
            }
            let term = params.term.toLowerCase();
            if (data.text.toLowerCase().indexOf(term) > -1) {
                return data;
            }
            return null;
        }
    });

    // Inicializar Select2 para piso destino
    $('#target_floor_uid').select2({
        placeholder: 'Selecciona el piso destino',
        allowClear: true,
        width: '100%',
        language: 'es'
    });

    // Validación del formulario
    document.getElementById('formLocationTransferBulk').addEventListener('submit', function(e) {
        const selectedLocations = document.getElementById('location_uids').value;
        const targetFloor = document.getElementById('target_floor_uid').value;

        if (!selectedLocations || selectedLocations.length === 0) {
            e.preventDefault();
            alert('Debes seleccionar al menos una ubicación para trasladar');
            return false;
        }

        if (!targetFloor) {
            e.preventDefault();
            alert('Debes seleccionar un piso destino');
            return false;
        }

        // Confirmación
        const count = selectedLocations.length;
        const confirmed = confirm(`¿Deseas trasladar ${count} ubicación(es) al piso seleccionado?`);
        if (!confirmed) {
            e.preventDefault();
        }
    });
});
</script>

@endsection
