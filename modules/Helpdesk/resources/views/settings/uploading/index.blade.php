@extends('layouts.theme')

@section('title', 'Configuracion de subida de archivos')

@section('content')

@include('core::components.alerts')

<div class="row g-4 align-items-start">

    {{-- Columna principal --}}
    <div class="col-lg-8">
        <form method="POST" action="{{ route('settings.helpdesk.uploading.update') }}" id="uploading-form">
            @csrf
            @method('PUT')

            <div class="card">

                {{-- Limites de carga --}}
                <div class="card-body">
                    <h6 class="fw-bold text-dark mb-1">Limites de carga</h6>
                    <p class="text-muted mb-3">Define el tamaño maximo de archivos que pueden subir los agentes y clientes en tickets y conversaciones.</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="max_file_size_mb" class="form-label fw-semibold">
                                Tamaño maximo por archivo (MB) <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control @error('max_file_size_mb') is-invalid @enderror"
                                id="max_file_size_mb" name="max_file_size_mb"
                                value="{{ old('max_file_size_mb', $settings['max_file_size_mb']) }}"
                                min="1" max="1000" required>
                            <small class="text-muted d-block mt-1" id="fileSizeHelp">
                                Tamaño maximo permitido por archivo
                            </small>
                            @error('max_file_size_mb')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <hr class="my-0">

                {{-- Tipos de archivos permitidos --}}
                <div class="card-body">
                    <h6 class="fw-bold text-dark mb-1">Tipos de archivos permitidos</h6>
                    <p class="text-muted mb-3">Selecciona las extensiones que agentes y clientes pueden adjuntar. Limitar los tipos reduce riesgos de seguridad.</p>

                    @php
                        $allExtensions = [
                            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp',
                            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
                            'txt', 'csv', 'odt', 'ods',
                            'zip', 'rar', '7z', 'tar', 'gz',
                            'mp3', 'mp4', 'mov', 'avi', 'mkv',
                            'xml', 'json', 'html',
                        ];
                        $selectedExtensions = old('allowed_extensions', $settings['allowed_extensions'] ?? []);
                    @endphp

                    <label for="allowed_extensions" class="form-label fw-semibold">
                        Extensiones permitidas <span class="text-danger">*</span>
                    </label>
                    <select class="form-select select2-tags @error('allowed_extensions') is-invalid @enderror"
                        id="allowed_extensions" name="allowed_extensions[]" multiple>
                        @foreach($allExtensions as $ext)
                            <option value="{{ $ext }}"
                                {{ in_array($ext, (array) $selectedExtensions) ? 'selected' : '' }}>
                                {{ strtoupper($ext) }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted d-block mt-1">
                        Selecciona de la lista o escribe una extension personalizada y presiona Enter.
                    </small>
                    @error('allowed_extensions')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <hr class="my-0">

                {{-- Compresion de imagenes --}}
                <div class="card-body">
                    <h6 class="fw-bold text-dark mb-1">Compresion de imagenes</h6>
                    <p class="text-muted mb-3">Reduce automaticamente el tamaño de las imagenes subidas para optimizar el almacenamiento.</p>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="enable_image_compression"
                            name="enable_image_compression" value="1"
                            {{ old('enable_image_compression', $settings['enable_image_compression']) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="enable_image_compression">
                            Comprimir imagenes automaticamente
                        </label>
                    </div>

                    <div id="compression-fields" class="{{ old('enable_image_compression', $settings['enable_image_compression']) ? '' : 'd-none' }}">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="image_max_width" class="form-label fw-semibold">
                                    Ancho maximo (px) <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control @error('image_max_width') is-invalid @enderror"
                                    id="image_max_width" name="image_max_width"
                                    value="{{ old('image_max_width', $settings['image_max_width']) }}"
                                    min="100" max="4000" required>
                                @error('image_max_width')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="image_max_height" class="form-label fw-semibold">
                                    Alto maximo (px) <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control @error('image_max_height') is-invalid @enderror"
                                    id="image_max_height" name="image_max_height"
                                    value="{{ old('image_max_height', $settings['image_max_height']) }}"
                                    min="100" max="4000" required>
                                @error('image_max_height')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="image_quality" class="form-label fw-semibold">
                                    Calidad (%) <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control @error('image_quality') is-invalid @enderror"
                                    id="image_quality" name="image_quality"
                                    value="{{ old('image_quality', $settings['image_quality']) }}"
                                    min="10" max="100" required>
                                <small class="text-muted d-block mt-1" id="qualityHelp">
                                    85% — equilibrio entre calidad y tamaño
                                </small>
                                @error('image_quality')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-0">

                {{-- Seguridad --}}
                <div class="card-body">
                    <h6 class="fw-bold text-dark mb-1">Seguridad</h6>
                    <p class="text-muted mb-3">Opciones adicionales para proteger el sistema contra archivos maliciosos.</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="enable_virus_scan" class="form-label fw-semibold">Escaneo de virus</label>
                            <select class="form-select select2" id="enable_virus_scan" name="enable_virus_scan">
                                <option value="1" {{ old('enable_virus_scan', $settings['enable_virus_scan']) ? 'selected' : '' }}>
                                    Habilitado
                                </option>
                                <option value="0" {{ ! old('enable_virus_scan', $settings['enable_virus_scan']) ? 'selected' : '' }}>
                                    Deshabilitado
                                </option>
                            </select>
                            <small class="text-muted d-block mt-1">
                                Escanea cada archivo antes de almacenarlo. Requiere ClamAV en el servidor.
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label for="enable_quarantine" class="form-label fw-semibold">Cuarentena</label>
                            <select class="form-select select2" id="enable_quarantine" name="enable_quarantine">
                                <option value="1" {{ old('enable_quarantine', $settings['enable_quarantine']) ? 'selected' : '' }}>
                                    Habilitada
                                </option>
                                <option value="0" {{ ! old('enable_quarantine', $settings['enable_quarantine']) ? 'selected' : '' }}>
                                    Deshabilitada
                                </option>
                            </select>
                            <small class="text-muted d-block mt-1">
                                Archivos sospechosos se aislan en lugar de eliminarse directamente.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary w-100">
                        Guardar configuracion
                    </button>
                </div>

            </div>
        </form>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">

        <div class="card mb-3">
            <div class="card-header border-bottom">
                <h6 class="mb-0 fw-bold">Limites del servidor</h6>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Estos valores estan definidos en la configuracion PHP del servidor. El tamaño maximo de la aplicacion no puede superarlos.</p>
                <ul class="list-unstyled mb-0">
                    <li class="d-flex justify-content-between mb-2">
                        <span class="text-muted">upload_max_filesize</span>
                        <strong>{{ $phpLimits['upload_max_filesize'] }}</strong>
                    </li>
                    <li class="d-flex justify-content-between mb-2">
                        <span class="text-muted">post_max_size</span>
                        <strong>{{ $phpLimits['post_max_size'] }}</strong>
                    </li>
                    <li class="d-flex justify-content-between">
                        <span class="text-muted">max_file_uploads</span>
                        <strong>{{ $phpLimits['max_file_uploads'] }}</strong>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header border-bottom">
                <h6 class="mb-0 fw-bold">Recomendaciones de seguridad</h6>
            </div>
            <div class="card-body">
                <h6 class="fw-semibold mb-2">Tipos de archivo</h6>
                <p class="text-muted mb-3">Permite solo las extensiones necesarias para el soporte. Evita ejecutables (<code>.exe</code>, <code>.sh</code>, <code>.php</code>) y archivos de script.</p>

                <hr class="my-3">

                <h6 class="fw-semibold mb-2">Tamaño de archivos</h6>
                <p class="text-muted mb-3">Para helpdesk, 10–25 MB suele ser suficiente. Limites mayores pueden afectar el rendimiento del servidor.</p>

                <hr class="my-3">

                <h6 class="fw-semibold mb-2">Compresion de imagenes</h6>
                <p class="text-muted mb-3">Con calidad 80–85% y dimensiones maximas de 1920×1080 se conserva buena calidad reduciendo el tamaño hasta un 60%.</p>

                <hr class="my-3">

                <h6 class="fw-semibold mb-2">Escaneo de virus</h6>
                <p class="text-muted mb-0">Activa el escaneo si el servidor tiene ClamAV instalado. La cuarentena preserva el archivo para revision en lugar de eliminarlo.</p>
            </div>
        </div>

    </div>

</div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });
    $('.select2-tags').select2({ width: '100%', tags: true, tokenSeparators: [',', ' '] });

    $('#enable_image_compression').on('change', function () {
        $('#compression-fields').toggleClass('d-none', !this.checked);
    });

    $('#max_file_size_mb').on('input', function () {
        var mb = parseInt($(this).val() || 0);
        $('#fileSizeHelp').text('Tamaño maximo permitido por archivo (' + mb + ' MB = ' + (mb * 1024) + ' KB)');
    });

    $('#image_quality').on('input', function () {
        var q = parseInt($(this).val() || 0);
        var label = q >= 90 ? 'alta calidad, archivo mayor' : q >= 70 ? 'equilibrio calidad/tamaño' : 'menor calidad, archivo mas pequeño';
        $('#qualityHelp').text(q + '% — ' + label);
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Guardado');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
