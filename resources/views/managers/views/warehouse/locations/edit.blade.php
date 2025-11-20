@extends('layouts.managers')

@section('content')

<div class="row">
    <div class="col-lg-12 d-flex align-items-stretch">
        <div class="card w-100">
            <form id="formLocationEdit" action="{{ route('manager.warehouse.locations.update') }}" method="POST" role="form">
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
                        <!-- Header Info -->
                        <div class="col-12">
                            <div class="alert alert-info">
                                <strong>Almacén:</strong> {{ $warehouse->name }} | <strong>Piso:</strong> {{ $floor->name }} ({{ $floor->code }})
                            </div>
                        </div>

                        <!-- Location Code -->
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Código de Ubicación <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $location->code) }}" maxlength="50" required>
                                @error('code')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Style Display (Read-only) -->
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Estilo de Ubicación</label>
                                <input type="text" class="form-control" value="{{ $location->style->name }}" disabled>
                                <small class="text-muted d-block mt-1">No se puede cambiar el estilo de una ubicación existente</small>
                            </div>
                        </div>

                        <!-- Position X -->
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Posición X (metros) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('position_x') is-invalid @enderror" id="position_x" name="position_x" value="{{ old('position_x', $location->position_x) }}" min="0" step="0.01" required>
                                @error('position_x')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Position Y -->
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Posición Y (metros) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('position_y') is-invalid @enderror" id="position_y" name="position_y" value="{{ old('position_y', $location->position_y) }}" min="0" step="0.01" required>
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
                                <button type="button" class="btn btn-sm btn-success ms-auto" id="btnAddSection" title="Agregar Sección">
                                    <i class="fas fa-plus"></i> Agregar Sección
                                </button>
                            </div>
                            <small class="text-muted d-block mb-3">Configure las divisiones verticales que tendrá esta ubicación</small>

                            <div id="sectionsList" class="row">
                                <!-- Sections will be generated here dynamically -->
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="col-12">
                            <div class="border-top pt-3 mt-4">
                                <button type="submit" class="btn btn-primary px-4 waves-effect waves-light mt-2">
                                    <i class="fa-duotone fa-check"></i> Actualizar Ubicación
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
                    <div class="col-6">
                        <label class="form-label">Nivel <span class="text-danger">*</span></label>
                        <input type="number" class="form-control section-level" name="sections[0][level]" value="1" min="1" required>
                    </div>

                    <!-- Section Face (for 2-cara styles) -->
                    <div class="col-6 face-group" style="display: none;">
                        <label class="form-label">Cara <span class="text-danger">*</span></label>
                        <select class="form-select section-face" name="sections[0][face]">
                            <option value="front" selected>📍 Frontal</option>
                            <option value="back">🔙 Posterior</option>
                        </select>
                    </div>

                    <!-- Section Barcode -->
                    <div class="col-6">
                        <label class="form-label">Código de Barras</label>
                        <input type="text" class="form-control section-barcode" name="sections[0][barcode]" placeholder="Opcional" maxlength="100">
                    </div>

                    <!-- Max Quantity -->
                    <div class="col-6">
                        <label class="form-label">Cantidad Máxima</label>
                        <input type="number" class="form-control section-max-qty" name="sections[0][max_quantity]" min="1" placeholder="Opcional">
                    </div>

                    <!-- Remove Button -->
                    <div class="col-12">
                        <button type="button" class="btn btn-sm btn-outline-danger w-100 btn-remove-section">
                            <i class="fas fa-trash"></i> Eliminar
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
    const styleData = @json(['faces_count' => count($location->style->faces ?? [])]);
    let sectionCount = 0;

    // Load existing sections
    const existingSections = @json($location->sections()->orderBy('level')->get(['uid', 'code', 'barcode', 'level', 'face', 'max_quantity']));

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

        clone.querySelector('.section-barcode').name = `sections[${sectionCount}][barcode]`;
        clone.querySelector('.section-barcode').value = data?.barcode || '';

        clone.querySelector('.section-max-qty').name = `sections[${sectionCount}][max_quantity]`;
        clone.querySelector('.section-max-qty').value = data?.max_quantity || '';

        const faceGroup = clone.querySelector('.face-group');
        const faceSelect = clone.querySelector('.section-face');

        if (styleData.faces_count === 2) {
            faceGroup.style.display = 'block';
            faceSelect.name = `sections[${sectionCount}][face]`;
            faceSelect.value = data?.face || 'front';
        } else {
            faceGroup.style.display = 'none';
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
            section.querySelector('.section-barcode').name = `sections[${index}][barcode]`;
            section.querySelector('.section-max-qty').name = `sections[${index}][max_quantity]`;
        });
    }

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
