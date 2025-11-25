@extends('layouts.managers')

@section('content')

<div class="row">
    <div class="col-lg-12 d-flex align-items-stretch">
        <div class="card w-100">
            <form id="formLocationCreate" action="{{ route('manager.warehouse.locations.store', [$warehouse->uid, $floor->uid]) }}" method="POST" role="form">
                {{ csrf_field() }}
                <input type="hidden" name="warehouse_uid" value="{{ $warehouse->uid }}">
                <input type="hidden" name="floor_uid" value="{{ $floor->uid }}">

                <div class="card-body border-top">
                    <div class="d-flex no-block align-items-center">
                        <h5 class="mb-0">Crear Nueva Ubicación</h5>
                    </div>
                    <p class="card-subtitle mb-3 mt-3">
                        Complete los datos de la ubicación (estantería) que desea registrar en <strong>{{ $floor->name }}</strong>.
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

                        <!-- Floor Display (Read-only) -->
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Piso</label>
                                <input type="text" class="form-control" value="{{ $floor->name }} ({{ $floor->code }})" disabled>
                            </div>
                        </div>

                        <!-- Location Code -->
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Código de Ubicación <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" placeholder="ej: PAB01, ISLA02" value="{{ old('code') }}" maxlength="50" required>
                                @error('code')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Style Selection -->
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Estilo de Ubicación <span class="text-danger">*</span></label>
                                <select name="style_id" id="style_id" class="select2 form-control @error('style_id') is-invalid @enderror" required>
                                    <option value="">Seleccionar estilo</option>
                                    @foreach($styles as $id => $name)
                                        <option value="{{ $id }}" {{ old('style_id') == $id ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('style_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Position X -->
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Posición X (metros) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('position_x') is-invalid @enderror" id="position_x" name="position_x" value="{{ old('position_x', 0) }}" min="0" step="1" required>
                                @error('position_x')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Position Y -->
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Posición Y (metros) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('position_y') is-invalid @enderror" id="position_y" name="position_y" value="{{ old('position_y', 0) }}" min="0" step="1" required>
                                @error('position_y')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Available Status -->
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Estado</label>
                                <select name="available" id="available" class="select2 form-control @error('available') is-invalid @enderror">
                                    <option value="1" {{ old('available', 1) == 1 ? 'selected' : '' }}>Disponible</option>
                                    <option value="0" {{ old('available', 1) == 0 ? 'selected' : '' }}>No disponible</option>
                                </select>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Notas</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" placeholder="Notas adicionales: mantenimiento, daños, etc." rows="3" maxlength="500">{{ old('notes') }}</textarea>
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
                                <button type="button" class="btn btn-md btn-primary ms-auto" id="btnAddSection" >
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
                            <option value="front" selected>Fondo</option>
                            <option value="back">Adelante</option>
                            <option value="left" >Cara 1</option>
                            <option value="right" >Cara 2</option>
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
    const styleSelect = document.getElementById('style_id');
    const sectionsList = document.getElementById('sectionsList');
    const btnAddSection = document.getElementById('btnAddSection');
    const sectionTemplate = document.getElementById('sectionTemplate');
    let styleData = null;
    let sectionCount = 0;

    // Get style value - handles both Select2 and native select
    function getStyleValue() {
        if (typeof $ !== 'undefined' && $.fn.select2) {
            return $(styleSelect).val();
        }
        return styleSelect.value;
    }

    // Fetch style details
    function fetchStyleDetails(styleId) {
        if (!styleId) {
            sectionsList.innerHTML = '';
            styleData = null;
            return Promise.reject('No style ID provided');
        }

        console.log('Fetching style details for ID:', styleId);
        const url = `/manager/warehouse/api/styles/${styleId}`;
        console.log('Fetch URL:', url);

        return fetch(url)
            .then(response => {
                console.log('Response status:', response.status, response.statusText);
                if (!response.ok) {
                    console.log('Response text:', response.text());
                    throw new Error(`HTTP error! status: ${response.status} ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Style data received:', data);
                styleData = data;
                updateSectionsList();
                return data;
            })
            .catch(error => {
                console.error('Error fetching style:', error);
                console.error('Error details:', {
                    message: error.message,
                    stack: error.stack
                });
                alert('Error al cargar los detalles del estilo.\n\nDetalles: ' + error.message + '\n\nPor favor, verifica que:\n1. Los estilos hayan sido sembrados en la base de datos\n2. La página esté correctamente cargada\n3. Recarga la página e intenta de nuevo.');
                styleData = null;
                throw error;
            });
    }

    // Generate default sections based on style
    function updateSectionsList() {
        if (!styleData) return;

        const isDoubleFace = styleData.faces_count === 2;

        // Clear existing sections
        sectionsList.innerHTML = '';
        sectionCount = 0;

        // Generate sections based on default_levels or default_sections
        const totalSections = styleData.default_levels || styleData.default_sections || 1;

        for (let i = 0; i < totalSections; i++) {
            addSection(i + 1, isDoubleFace ? 'front' : null);
        }
    }

    // Add a new section
    function addSection(level, face) {
        const clone = sectionTemplate.content.cloneNode(true);
        const sectionDiv = clone.querySelector('.section-item');

        sectionDiv.classList.add('fade-in');
        sectionDiv.dataset.level = level;

        // Update input names and values
        clone.querySelector('.section-code').name = `sections[${sectionCount}][code]`;
        clone.querySelector('.section-code').value = `SEC-${level}`;

        clone.querySelector('.section-level').name = `sections[${sectionCount}][level]`;
        clone.querySelector('.section-level').value = level;

        const faceGroup = clone.querySelector('.face-group');
        const faceSelect = clone.querySelector('.section-face');

        if (styleData && styleData.faces_count === 2) {
            faceGroup.style.display = 'block';
            faceSelect.name = `sections[${sectionCount}][face]`;
            if (face) faceSelect.value = face;
        } else {
            faceGroup.style.display = 'none';
        }

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
            section.querySelector('.section-code').name = `sections[${index}][code]`;
            section.querySelector('.section-level').name = `sections[${index}][level]`;
            const faceSelect = section.querySelector('.section-face');
            if (faceSelect) {
                faceSelect.name = `sections[${index}][face]`;
            }
        });
    }

    // Event listeners - Use both native and Select2 change events
    styleSelect.addEventListener('change', function() {
        const value = getStyleValue();
        console.log('Style changed via native event, value:', value);
        fetchStyleDetails(value);
    });

    // Also listen for Select2 change event if Select2 is loaded
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $(styleSelect).on('select2:select', function() {
            const value = $(this).val();
            console.log('Style changed via Select2 event, value:', value);
            fetchStyleDetails(value);
        });
    }

    btnAddSection.addEventListener('click', function(e) {
        e.preventDefault();

        const currentValue = getStyleValue();
        console.log('btnAddSection clicked. currentValue:', currentValue, 'styleData:', styleData);

        if (!currentValue) {
            alert('Por favor selecciona un estilo primero');
            return;
        }

        // If styleData is not loaded yet, fetch it first
        if (!styleData) {
            console.log('styleData is null, fetching...');
            fetchStyleDetails(currentValue)
                .then(() => {
                    // After fetch completes, add the section
                    const maxLevel = Math.max(...Array.from(sectionsList.querySelectorAll('.section-item')).map(s => parseInt(s.dataset.level))) || 0;
                    addSection(maxLevel + 1, styleData && styleData.faces_count === 2 ? 'front' : null);
                })
                .catch(error => {
                    console.error('Failed to fetch style:', error);
                    alert('Error al cargar el estilo. Por favor recarga la página.');
                });
            return;
        }

        const maxLevel = Math.max(...Array.from(sectionsList.querySelectorAll('.section-item')).map(s => parseInt(s.dataset.level))) || 0;
        addSection(maxLevel + 1, styleData.faces_count === 2 ? 'front' : null);
    });

    // Initialize on page load if style is already selected
    const initialValue = getStyleValue();
    if (initialValue) {
        console.log('Initializing with style value:', initialValue);
        fetchStyleDetails(initialValue);
    }

    // Form validation
    document.getElementById('formLocationCreate').addEventListener('submit', function(e) {
        const sections = sectionsList.querySelectorAll('.section-item');
        if (sections.length === 0) {
            e.preventDefault();
            alert('Debes agregar al menos una sección');
            return false;
        }

        if (styleData && styleData.faces_count === 2) {
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
