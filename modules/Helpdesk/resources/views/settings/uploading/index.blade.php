@extends('helpdesk::settings.layout')

@php
    $title = 'Configuración de subida de archivos';
    $description = 'Controla los tipos y tamaños de archivos permitidos en tickets y chats';
    $icon = 'fa-upload';
    $breadcrumb = 'Subida de archivos';
@endphp

@section('backups-content')
<form method="POST" action="{{ route('settings.helpdesk.uploading.update') }}">
    @csrf
    @method('PUT')

    <div class="form-section">
        <h5><i class="fas fa-file"></i> Restricciones de archivos</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="max_file_size_mb">Tamaño máximo (MB)</label>
                <input type="number" class="form-control" id="max_file_size_mb" name="max_file_size_mb"
                    value="{{ old('max_file_size_mb', $backups['max_file_size_mb'] ?? 25) }}" min="1" max="1000" required>
            </div>
            <div class="col-md-8">
                <label class="form-label" for="allowed_extensions">Extensiones permitidas</label>
                <input type="text" class="form-control" id="allowed_extensions" name="allowed_extensions"
                    value="{{ old('allowed_extensions', $backups['allowed_extensions'] ?? 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,zip') }}"
                    placeholder="pdf,doc,jpg" required>
                <span class="form-help">Separadas por coma, sin punto ni espacios</span>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h5><i class="fas fa-compress-arrows-alt"></i> Compresión de imágenes</h5>
        <div class="row g-3">
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="enable_image_compression"
                        name="enable_image_compression" value="1"
                        {{ ($backups['enable_image_compression'] ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="enable_image_compression">Comprimir imágenes automáticamente</label>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="image_max_width">Ancho máximo (px)</label>
                <input type="number" class="form-control" id="image_max_width" name="image_max_width"
                    value="{{ old('image_max_width', $backups['image_max_width'] ?? 1920) }}" min="100" max="4000" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="image_max_height">Alto máximo (px)</label>
                <input type="number" class="form-control" id="image_max_height" name="image_max_height"
                    value="{{ old('image_max_height', $backups['image_max_height'] ?? 1080) }}" min="100" max="4000" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="image_quality">Calidad de imagen (%)</label>
                <input type="number" class="form-control" id="image_quality" name="image_quality"
                    value="{{ old('image_quality', $backups['image_quality'] ?? 85) }}" min="10" max="100" required>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h5><i class="fas fa-shield-alt"></i> Seguridad</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="enable_virus_scan"
                        name="enable_virus_scan" value="1"
                        {{ ($backups['enable_virus_scan'] ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="enable_virus_scan">Escaneo de virus</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="enable_quarantine"
                        name="enable_quarantine" value="1"
                        {{ ($backups['enable_quarantine'] ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="enable_quarantine">Cuarentena de archivos sospechosos</label>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary btn-save">
            <i class="fas fa-save me-2"></i> Guardar configuración
        </button>
    </div>
</form>
@endsection
