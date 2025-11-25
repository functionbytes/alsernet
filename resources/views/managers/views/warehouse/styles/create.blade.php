@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formStyles" action="{{ route('manager.warehouse.styles.store') }}" method="POST" role="form">

                    {{ csrf_field() }}

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Crear nuevo estilo de estantería</h5>
                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Complete los datos del estilo que desea registrar en el sistema de almacén.
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

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Código <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('code') is-invalid @enderror"  id="code" name="code" value="{{ old('code') }}"   placeholder="ROW, ISLAND, WALL, etc."   maxlength="50" required>
                                    @error('code')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"   id="name" name="name" value="{{ old('name') }}"   placeholder="Pasillo Lineal, Isla, Pared, etc." maxlength="100" required>
                                    @error('name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Tipo de estantería <span class="text-danger">*</span></label>
                                    <select name="type" id="type" class="select2 form-control @error('type') is-invalid @enderror" required>
                                        <option value="">Seleccionar tipo</option>
                                        @foreach($types as $value => $label)
                                            <option value="{{ $value }}" {{ old('type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-1">Defina si es un pasillo, isla o estantería de pared. Las caras se seleccionarán automáticamente</small>
                                    @error('type')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Estado</label>
                                    <div class="input-group">
                                        <select name="available" id="available" class="select2 form-control @error('available') is-invalid @enderror">
                                            <option value="">Seleccionar estado</option>
                                            <option value="1" {{ old('available', '1') == '1' ? 'selected' : '' }}>Disponible</option>
                                            <option value="0" {{ old('available', '1') == '0' ? 'selected' : '' }}>No disponible</option>
                                        </select>
                                    </div>
                                    @error('available')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Caras disponibles <span class="text-danger">*</span></label>
                                    <select name="faces[]" id="faces" class="select2 form-control @error('faces') is-invalid @enderror" multiple="multiple" required disabled>
                                        @foreach($faces as $value => $label)
                                            <option value="{{ $value }}" {{ in_array($value, old('faces', [])) ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-1">Se seleccionan automáticamente según el tipo: Wall (1 cara), Row (2 caras), Island (4 caras)</small>
                                    @error('faces')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Niveles por defecto <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('default_levels') is-invalid @enderror" id="default_levels" name="default_levels" value="{{ old('default_levels', 3) }}"  min="1" max="20" required>
                                    @error('default_levels')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Ancho mapa <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('width') is-invalid @enderror" id="width" name="width" value="{{ old('width', 3) }}"  min="1" max="200" required>
                                    @error('width')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Alto mapa <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('height') is-invalid @enderror" id="height" name="height" value="{{ old('height', 3) }}"  min="1" max="200" required>
                                    @error('height')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Secciones por defecto <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('default_sections') is-invalid @enderror" id="default_sections" name="default_sections" value="{{ old('default_sections', 5) }}"  min="1" max="30" required>
                                    @error('default_sections')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>


                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Descripción</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"  placeholder="Descripción adicional del estilo"  rows="3">{{ old('description') }}</textarea>
                                    @error('description')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>


                            <div class="col-12">
                                <div class="errors d-none">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border-top pt-1 mt-4">
                                    <button type="submit" class="btn btn-info  px-4 waves-effect waves-light mt-2 w-100">
                                        Guardar
                                    </button>
                                </div>

                        </div>

                    </div>
                </form>
            </div>

        </div>

    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const facesSelect = document.getElementById('faces');

    // Mapeo de tipo a caras
    const typeFacesMap = {
        'wall': ['front'], // Pared: 1 cara
        'row': ['front', 'back'], // Pasillo: 2 caras
        'island': ['front', 'back', 'left', 'right'] // Isla: 4 caras
    };

    // Función para actualizar las caras según el tipo
    function updateFaces(type) {
        if (!type || !typeFacesMap[type]) {
            // Si no hay tipo seleccionado, deshabilitar
            $(facesSelect).val(null).trigger('change');
            facesSelect.disabled = true;
            return;
        }

        // Habilitar el select
        facesSelect.disabled = false;

        // Seleccionar las caras correspondientes
        const facesToSelect = typeFacesMap[type];
        $(facesSelect).val(facesToSelect).trigger('change');
    }

    // Event listener para cambios en el tipo
    $('#type').on('change', function() {
        updateFaces(this.value);
    });

    // Inicializar al cargar la página si hay un valor seleccionado
    if (typeSelect.value) {
        updateFaces(typeSelect.value);
    }
});
</script>

@endsection
