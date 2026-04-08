@extends('layouts.theme')

@section('title', 'Configuración de Subida de Archivos - Helpdesk')

@section('content')

    @include('core::components.card', ['title' => 'Configuración de Subida de Archivos'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card w-100">

            <form id="uploadingForm" method="POST" action="{{ route('manager.helpdesk.backups.uploading.update') }}">
                @csrf
                @method('PUT')

                <!-- Header Section -->
                <div class="card-header p-4 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Configuración de Subida de Archivos</h5>
                            <p class="small mb-0 text-muted">Configura restricciones, compresión y seguridad para archivos</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('manager.helpdesk.backups') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                            <button type="submit" class="btn btn-primary" id="saveBtn" disabled>
                                <i class="fas fa-check"></i> Guardar Cambios
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">

                        <!-- File Upload Settings -->
                        <div class="col-12">
                            <h5 class="mb-1"><i class="fas fa-upload"></i> Configuración de Subida</h5>
                            <p class="text-muted mb-3">Restricciones para la carga de archivos</p>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label for="max_file_size_mb" class="form-label fw-semibold">
                                    <i class="far fa-file-alt me-1"></i> Tamaño Máximo de Archivo (MB) <span class="text-danger">*</span>
                                </label>
                                <input type="number" name="max_file_size_mb"
                                       class="form-control @error('max_file_size_mb') is-invalid @enderror"
                                       id="max_file_size_mb"
                                       value="{{ old('max_file_size_mb', $settings['max_file_size_mb']) }}"
                                       min="1" max="1000" required>
                                <small class="text-muted">Tamaño máximo permitido para cada archivo individual</small>
                                @error('max_file_size_mb')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label for="allowed_extensions" class="form-label fw-semibold">
                                    <i class="far fa-file me-1"></i> Extensiones Permitidas <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="allowed_extensions"
                                       class="form-control @error('allowed_extensions') is-invalid @enderror"
                                       id="allowed_extensions"
                                       value="{{ old('allowed_extensions', $settings['allowed_extensions']) }}"
                                       placeholder="pdf,doc,jpg,png" required>
                                <small class="text-muted">Extensiones separadas por comas</small>
                                @error('allowed_extensions')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Image Compression -->
                        <div class="col-12">
                            <hr class="my-4">
                            <h5 class="mb-1"><i class="fas fa-image"></i> Compresión de Imágenes</h5>
                            <p class="text-muted mb-3">Reduce automáticamente el tamaño de las imágenes subidas</p>
                        </div>

                        <div class="col-12">
                            <div class="border-bottom pb-3 mb-3">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="enable_image_compression" value="0">
                                    <input type="checkbox" name="enable_image_compression"
                                           class="form-check-input" id="imageCompress" value="1"
                                           {{ old('enable_image_compression', $settings['enable_image_compression']) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="imageCompress">
                                        <strong><i class="fas fa-compress me-1"></i> Habilitar compresión de imágenes</strong>
                                        <small class="d-block text-muted mt-1">Reduce el tamaño de las imágenes para ahorrar espacio y mejorar el rendimiento</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Compression Options (conditional) -->
                        <div class="col-12" id="compressionOptions" style="{{ old('enable_image_compression', $settings['enable_image_compression']) ? '' : 'display: none;' }}">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="image_max_width" class="form-label fw-semibold">
                                        <i class="fas fa-arrows-alt-h me-1"></i> Ancho Máximo (px)
                                    </label>
                                    <input type="number" name="image_max_width"
                                           class="form-control @error('image_max_width') is-invalid @enderror"
                                           id="image_max_width"
                                           value="{{ old('image_max_width', $settings['image_max_width']) }}"
                                           min="100" max="4000" placeholder="1920">
                                    <small class="text-muted">Las imágenes más anchas se redimensionarán</small>
                                    @error('image_max_width')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="image_max_height" class="form-label fw-semibold">
                                        <i class="fas fa-arrows-alt-v me-1"></i> Alto Máximo (px)
                                    </label>
                                    <input type="number" name="image_max_height"
                                           class="form-control @error('image_max_height') is-invalid @enderror"
                                           id="image_max_height"
                                           value="{{ old('image_max_height', $settings['image_max_height']) }}"
                                           min="100" max="4000" placeholder="1080">
                                    <small class="text-muted">Las imágenes más altas se redimensionarán</small>
                                    @error('image_max_height')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="image_quality" class="form-label fw-semibold">
                                        <i class="fas fa-sliders-h me-1"></i> Calidad de Compresión
                                    </label>
                                    <input type="range" name="image_quality"
                                           class="form-range @error('image_quality') is-invalid @enderror"
                                           id="image_quality"
                                           value="{{ old('image_quality', $settings['image_quality']) }}"
                                           min="10" max="100"
                                           oninput="updateQuality(this.value)">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted">Baja</small>
                                        <span id="qualityValue" class="badge bg-primary-subtle text-primary">
                                            {{ old('image_quality', $settings['image_quality']) }}%
                                        </span>
                                        <small class="text-muted">Alta</small>
                                    </div>
                                    @error('image_quality')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Security Settings -->
                        <div class="col-12">
                            <hr class="my-4">
                            <h5 class="mb-1"><i class="fas fa-shield-alt"></i> Seguridad</h5>
                            <p class="text-muted mb-3">Protección contra archivos maliciosos</p>
                        </div>

                        <div class="col-12">
                            <div class="border-bottom pb-3 mb-3">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="enable_virus_scan" value="0">
                                    <input type="checkbox" name="enable_virus_scan"
                                           class="form-check-input" id="virusScan" value="1"
                                           {{ old('enable_virus_scan', $settings['enable_virus_scan']) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="virusScan">
                                        <strong><i class="fas fa-search me-1"></i> Escaneo antivirus</strong>
                                        <small class="d-block text-muted mt-1">Escanea archivos en busca de virus y malware (requiere ClamAV)</small>
                                    </label>
                                </div>
                            </div>

                            <div class="mb-0">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="enable_quarantine" value="0">
                                    <input type="checkbox" name="enable_quarantine"
                                           class="form-check-input" id="quarantine" value="1"
                                           {{ old('enable_quarantine', $settings['enable_quarantine']) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="quarantine">
                                        <strong><i class="fas fa-lock me-1"></i> Cuarentena de archivos sospechosos</strong>
                                        <small class="d-block text-muted mt-1">Mueve archivos potencialmente peligrosos a cuarentena para revisión manual</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </form>

        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const form = $('#uploadingForm');
    const saveBtn = $('#saveBtn');
    let originalFormData = form.serialize();

    // Form Dirty Detection
    function checkFormDirty() {
        const currentFormData = form.serialize();
        const isDirty = originalFormData !== currentFormData;
        saveBtn.prop('disabled', !isDirty);
    }

    // Monitor all form inputs for changes
    form.on('change input', 'input, select, textarea', function() {
        checkFormDirty();
    });

    // Update quality slider value
    window.updateQuality = function(value) {
        $('#qualityValue').text(value + '%');
    };

    // Toggle compression options
    $('#imageCompress').on('change', function() {
        if ($(this).is(':checked')) {
            $('#compressionOptions').slideDown(300);
        } else {
            $('#compressionOptions').slideUp(300);
        }
    });

    // Handle form submission
    form.on('submit', function() {
        saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Guardando...');
    });

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Configuración actualizada');
        // Update original form data after save
        setTimeout(function() {
            originalFormData = form.serialize();
            checkFormDirty();
        }, 100);
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
