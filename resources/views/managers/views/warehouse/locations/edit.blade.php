@extends('layouts.managers')

@section('content')

<div class="row">
    <div class="col-lg-12 d-flex align-items-stretch">
        <div class="card w-100">
            <form id="formLocationEdit" action="{{ route('manager.warehouse.locations.update',[$warehouse->uid, $floor->uid, $location->uid]) }}" method="POST" role="form">
                {{ csrf_field() }}
                <input type="hidden" name="warehouse_uid" value="{{ $warehouse->uid }}">
                <input type="hidden" name="floor_uid" value="{{ $floor->uid }}">
                <input type="hidden" name="location_uid" value="{{ $location->uid }}">

                <div class="card-body border-top">
                    <div class="d-flex no-block align-items-center">
                        <h5 class="mb-0">Editar Ubicación: {{ $location->code }}</h5>
                    </div>
                    <p class="card-subtitle mb-3 mt-3">
                        Actualice los datos de la ubicación según sea necesario.
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
                        <!-- Warehouse Display (Read-only) -->
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Almacén</label>
                                <input type="text" class="form-control" value="{{ $warehouse->name }}" disabled>
                            </div>
                        </div>

                        <!-- Floor Display (Read-only) -->
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Piso</label>
                                <input type="text" class="form-control" value="{{ $floor->name }} ({{ $floor->code }})" disabled>
                            </div>
                        </div>

                        <!-- Location Code -->


                        <!-- Style Selection -->
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Estilo de Ubicación <span class="text-danger">*</span></label>
                                <select name="style_id" id="style_id" class="select2 form-control @error('style_id') is-invalid @enderror" required>
                                    @foreach($styles as $id => $name)
                                        <option value="{{ $id }}" {{ old('style_id', $location->style_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-1">Cambiar el estilo ajustará las secciones según la configuración del nuevo estilo</small>
                                @error('style_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Código de Ubicación <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $location->code) }}" maxlength="50" required>
                                @error('code')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <!-- Position X -->
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Posición X (metros) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('position_x') is-invalid @enderror" id="position_x" name="position_x" value="{{ old('position_x', $location->position_x) }}" min="0" step="1" required>
                                @error('position_x')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Position Y -->
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Posición Y (metros) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('position_y') is-invalid @enderror" id="position_y" name="position_y" value="{{ old('position_y', $location->position_y) }}" min="0" step="1" required>
                                @error('position_y')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Available Status -->
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Estado</label>
                                <select name="available" id="available" class="select2 form-control @error('available') is-invalid @enderror">
                                    <option value="1" {{ old('available', $location->available) == 1 ? 'selected' : '' }}>Disponible</option>
                                    <option value="0" {{ old('available', $location->available) == 0 ? 'selected' : '' }}>No disponible</option>
                                </select>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Notas</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" placeholder="Notas adicionales: mantenimiento, daños, etc." rows="3" maxlength="500">{{ old('notes', $location->notes) }}</textarea>
                                <small class="text-muted d-block mt-1">Máximo 500 caracteres</small>
                                @error('notes')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Sections Management -->
                        <div class="col-12">
                            <hr class="mt-4 mb-4">
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0">Secciones (Divisiones Verticales)</h6>
                                <button type="button" class="btn btn-md btn-primary ms-auto" id="btnAddSection" title="Agregar Sección">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <small class="text-muted d-block mb-3">Configure las divisiones verticales que tendrá esta ubicación</small>

                            <div id="sectionsList" class="row">
                                <!-- Sections will be generated here dynamically -->
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="col-12">
                            <div class="border-top pt-1 mt-4">
                                <button type="submit" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100">
                                    Guardar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Template for section input group -->
<template id="sectionTemplate">
    <div class="col-md-6 section-item" data-level="1">
        <div class="card border-light mb-3">
            <div class="card-body">
                <input type="hidden" class="section-uid" name="sections[0][uid]" value="">

                <div class="row g-2">
                    <!-- Section Code -->
                    <div class="col-12">
                        <label class="form-label">Código <span class="text-danger">*</span></label>
                        <input type="text" class="form-control section-code" name="sections[0][code]" placeholder="ej: SEC-1" maxlength="50" required>
                    </div>

                    <!-- Section Level -->
                    <div class="col-12">
                        <label class="form-label">Nivel <span class="text-danger">*</span></label>
                        <input type="number" class="form-control section-level" name="sections[0][level]" value="1" min="1" required>
                    </div>

                    <!-- Section Face (for 2-cara styles) -->
                    <div class="col-12 face-group d-none">
                        <label class="form-label">Cara <span class="text-danger">*</span></label>
                        <select class="form-select section-face" name="sections[0][face]">
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>

                    <!-- Remove Button -->
                    <div class="col-12">
                        <button type="button" class="btn btn-md btn-primary w-100 btn-remove-section">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
    .section-item {
        transition: all 0.3s ease;
    }
    .section-item.fade-in {
        animation: fadeIn 0.3s ease;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sectionsList = document.getElementById('sectionsList');
    const btnAddSection = document.getElementById('btnAddSection');
    const sectionTemplate = document.getElementById('sectionTemplate');
    const styleSelect = document.getElementById('style_id');
    let styleData = @json([
        'faces_count' => count($location->style->faces ?? []),
        'faces' => $location->style->faces ?? []
    ]);
    let sectionCount = 0;

    // Face labels mapping (inglés -> español)
    const faceLabels = {
        'left': 'Izquierda',
        'right': 'Derecha',
        'front': 'Adelante',
        'back': 'Fondo'
    };

    // Load existing sections
    const existingSections = {!! json_encode($location->sections()->orderBy('level')->get(['uid', 'code', 'level', 'face'])) !!};

    // Function to populate face select options
    function populateFaceOptions(selectElement, selectedValue = null) {
        selectElement.innerHTML = ''; // Clear existing options

        if (styleData.faces && styleData.faces.length > 0) {
            styleData.faces.forEach(face => {
                const option = document.createElement('option');
                option.value = face;
                option.textContent = faceLabels[face] || face;
                if (selectedValue && selectedValue === face) {
                    option.selected = true;
                } else if (!selectedValue && face === 'front') {
                    // Default to 'front' if no value selected
                    option.selected = true;
                }
                selectElement.appendChild(option);
            });
        }
    }

    // Add a new section
    function addSection(data = null) {
        const clone = sectionTemplate.content.cloneNode(true);
        const sectionDiv = clone.querySelector('.section-item');

        sectionDiv.classList.add('fade-in');

        // Set data
        const level = data?.level || sectionCount + 1;
        sectionDiv.dataset.level = level;

        // Set UID if editing
        if (data?.uid) {
            clone.querySelector('.section-uid').value = data.uid;
        }

        // Update input names and values
        clone.querySelector('.section-code').name = `sections[${sectionCount}][code]`;
        clone.querySelector('.section-code').value = data?.code || `SEC-${level}`;

        clone.querySelector('.section-level').name = `sections[${sectionCount}][level]`;
        clone.querySelector('.section-level').value = level;

        const faceGroup = clone.querySelector('.face-group');
        const faceSelect = clone.querySelector('.section-face');
        faceSelect.name = `sections[${sectionCount}][face]`;

        // Populate face options based on current style
        populateFaceOptions(faceSelect, data?.face);

        if (styleData.faces_count >= 2) {
            faceGroup.classList.remove('d-none');
        } else {
            faceGroup.classList.add('d-none');
        }

        clone.querySelector('.section-uid').name = `sections[${sectionCount}][uid]`;

        // Remove button handler
        clone.querySelector('.btn-remove-section').addEventListener('click', function(e) {
            e.preventDefault();
            sectionDiv.remove();
            renumberSections();
        });

        sectionsList.appendChild(clone);
        sectionCount++;
    }

    // Renumber sections after deletion
    function renumberSections() {
        const sections = sectionsList.querySelectorAll('.section-item');
        sections.forEach((section, index) => {
            section.querySelector('.section-uid').name = `sections[${index}][uid]`;
            section.querySelector('.section-code').name = `sections[${index}][code]`;
            section.querySelector('.section-level').name = `sections[${index}][level]`;
            section.querySelector('.section-face').name = `sections[${index}][face]`;
        });
    }

    // Update sections display based on current style (faces)
    function updateSectionsFaceDisplay() {
        const sections = sectionsList.querySelectorAll('.section-item');
        sections.forEach((section, index) => {
            const faceGroup = section.querySelector('.face-group');
            const faceSelect = section.querySelector('.section-face');
            const currentValue = faceSelect.value; // Store current value

            // Repopulate options with new style faces
            populateFaceOptions(faceSelect, currentValue);

            if (styleData.faces_count >= 2) {
                faceGroup.classList.remove('d-none');
                // Update the name attribute
                faceSelect.name = `sections[${index}][face]`;
            } else {
                faceGroup.classList.add('d-none');
                faceSelect.value = ''; // Clear face value for 1-cara styles
            }
        });
    }

    // Handle style change
    styleSelect.addEventListener('change', function() {
        const styleId = this.value;
        console.log('Style changed to ID:', styleId);

        if (!styleId) return;

        // Fetch style details via API
        fetch(`/manager/warehouse/api/styles/${styleId}`)
            .then(response => {
                console.log('API response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Style data received:', data);
                console.log('Faces count:', data.faces_count);
                console.log('Faces available:', data.faces);

                // Update styleData with new style information
                styleData.faces_count = data.faces_count;
                styleData.faces = data.faces || [];

                // Update existing sections to show/hide face field
                updateSectionsFaceDisplay();

                // Show notification
                console.log(`Estilo cambiado a: ${data.name} (${data.faces_count} cara(s))`);
                alert(`Estilo actualizado: ${data.name} (${data.faces_count} cara(s))\nCaras disponibles: ${styleData.faces.map(f => faceLabels[f]).join(', ')}`);
            })
            .catch(error => {
                console.error('Error al obtener detalles del estilo:', error);
                alert('Error al cargar la información del estilo. Por favor, intente nuevamente.');
            });
    });

    // Event listeners
    btnAddSection.addEventListener('click', function(e) {
        e.preventDefault();
        const maxLevel = Math.max(...Array.from(sectionsList.querySelectorAll('.section-item')).map(s => parseInt(s.dataset.level))) || 0;
        addSection({ level: maxLevel + 1 });
    });

    // Initialize with existing sections
    if (existingSections.length > 0) {
        existingSections.forEach(section => addSection(section));
    } else {
        // If no sections exist, add one default section
        addSection();
    }

    // Form validation
    document.getElementById('formLocationEdit').addEventListener('submit', function(e) {
        const sections = sectionsList.querySelectorAll('.section-item');
        if (sections.length === 0) {
            e.preventDefault();
            alert('Debes agregar al menos una sección');
            return false;
        }

        if (styleData.faces_count === 2) {
            let valid = true;
            sections.forEach(section => {
                const face = section.querySelector('.section-face').value;
                if (!face) {
                    section.querySelector('.section-face').classList.add('is-invalid');
                    valid = false;
                }
            });
            if (!valid) {
                e.preventDefault();
                alert('Debes seleccionar una cara para cada sección (estilos de 2 caras)');
            }
        }
    });
});
</script>

@endsection
