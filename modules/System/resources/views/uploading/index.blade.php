@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Configuración de carga de archivos'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <form method="POST" action="{{ route('settings.system.uploading.update') }}">
            @csrf
            @method('PUT')

            <!-- Uploading Settings Card -->
            <div class="card">
                <!-- Header Section -->
                <div class="card-header p-4 border-bottom ">
                    <div>
                        <h5 class="mb-1 fw-bold">Configuración de carga de archivos</h5>
                        <p class="small mb-0">Configura los límites y tipos de archivos permitidos para subir, además
                            del almacenamiento y seguridad.</p>
                    </div>
                </div>

                <!-- Form -->
                <div class="card-body">


                    <div class="row">
                        <!-- File Size & Limits -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="mb-3 fw-bold">Límites de carga</h6>

                                    <div class="mb-3">
                                        <label for="maxFileSize" class="form-label fw-semibold">Tamaño máximo de archivo
                                            (KB) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="maxFileSize" name="max_file_size"
                                               value="{{ $settings['max_file_size'] }}" min="1" max="102400" required>
                                        <small class="text-muted">Tamaño máximo permitido por archivo (1 MB = 1024
                                            KB)</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="maxFilesPerUpload" class="form-label fw-semibold">Archivos por carga
                                            <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="maxFilesPerUpload"
                                               name="max_files_per_upload"
                                               value="{{ $settings['max_files_per_upload'] }}" min="1" max="50"
                                               required>
                                        <small class="text-muted">Cantidad máxima de archivos que se pueden subir
                                            simultáneamente</small>
                                    </div>

                                    <div class="alert alert-info border-0 bg-info-subtle text-info mb-0">
                                        <div class="d-flex align-items-start gap-2">
                                            <div>
                                                <strong>Límites PHP actuales:</strong><br>
                                                <small>
                                                    Tamaño máximo: {{ $phpLimits['upload_max_filesize'] }}<br>
                                                    POST máximo: {{ $phpLimits['post_max_size'] }}<br>
                                                    Archivos: {{ $phpLimits['max_file_uploads'] }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Allowed File Types -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="mb-3 fw-bold">Tipos de archivos permitidos</h6>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Archivos generales <span
                                                    class="text-danger">*</span></label>
                                        @php
                                            $generalTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar'];
                                        @endphp
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($generalTypes as $type)
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox"
                                                           id="file_{{ $type }}" name="allowed_file_types[]"
                                                           value="{{ $type }}" {{ in_array($type, $settings['allowed_file_types']) ? 'checked' : '' }}>
                                                    <label class="form-check-label"
                                                           for="file_{{ $type }}">{{ strtoupper($type) }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                        <small class="text-muted">Extensiones permitidas para cualquier tipo de
                                            archivo</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Solo imágenes <span
                                                    class="text-danger">*</span></label>
                                        @php
                                            $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico'];
                                        @endphp
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($imageTypes as $type)
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox"
                                                           id="image_{{ $type }}" name="allowed_image_types[]"
                                                           value="{{ $type }}" {{ in_array($type, $settings['allowed_image_types']) ? 'checked' : '' }}>
                                                    <label class="form-check-label"
                                                           for="image_{{ $type }}">{{ strtoupper($type) }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                        <small class="text-muted">Extensiones permitidas para imágenes</small>
                                    </div>

                                    <div class="mb-0">
                                        <label class="form-label fw-semibold">Solo documentos <span class="text-danger">*</span></label>
                                        @php
                                            $documentTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'odt', 'ods'];
                                        @endphp
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($documentTypes as $type)
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox"
                                                           id="document_{{ $type }}" name="allowed_document_types[]"
                                                           value="{{ $type }}" {{ in_array($type, $settings['allowed_document_types']) ? 'checked' : '' }}>
                                                    <label class="form-check-label"
                                                           for="document_{{ $type }}">{{ strtoupper($type) }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                        <small class="text-muted">Extensiones permitidas para documentos</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Storage Configuration -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="mb-3 fw-bold">Configuración de almacenamiento</h6>

                                    <div class="mb-3">
                                        <label for="storageDriver" class="form-label fw-semibold">Driver de
                                            almacenamiento <span class="text-danger">*</span></label>
                                        <select class="form-select" id="storageDriver" name="storage_driver" required>
                                            <option value="local" {{ $settings['storage_driver'] === 'local' ? 'selected' : '' }}>
                                                Local (Servidor)
                                            </option>
                                            <option value="s3" {{ $settings['storage_driver'] === 's3' ? 'selected' : '' }}>
                                                Amazon S3
                                            </option>
                                            <option value="spaces" {{ $settings['storage_driver'] === 'spaces' ? 'selected' : '' }}>
                                                DigitalOcean Spaces
                                            </option>
                                            <option value="ftp" {{ $settings['storage_driver'] === 'ftp' ? 'selected' : '' }}>
                                                FTP
                                            </option>
                                        </select>
                                        <small class="text-muted">Donde se almacenarán los archivos subidos</small>
                                    </div>

                                    <div id="s3Settings"
                                         style="display: {{ in_array($settings['storage_driver'], ['s3', 'spaces']) ? 'block' : 'none' }};">
                                        <div class="mb-3">
                                            <label for="s3Bucket" class="form-label fw-semibold">Bucket /
                                                Contenedor</label>
                                            <input type="text" class="form-control" id="s3Bucket" name="s3_bucket"
                                                   value="{{ $settings['s3_bucket'] }}" placeholder="mi-bucket">
                                            <small class="text-muted">Nombre del bucket de S3 o Spaces</small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="s3Region" class="form-label fw-semibold">Región</label>
                                            <input type="text" class="form-control" id="s3Region" name="s3_region"
                                                   value="{{ $settings['s3_region'] }}" placeholder="us-east-1">
                                            <small class="text-muted">Región del servicio de almacenamiento</small>
                                        </div>
                                    </div>

                                    <div class="alert alert-info border-0 bg-info-subtle text-info mb-0">
                                        <div class="d-flex align-items-start gap-2">
                                            <div>
                                                <strong>Importante:</strong> Para usar S3 o Spaces, configura las
                                                credenciales en el archivo .env
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Security Settings -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="mb-3 fw-bold">Seguridad</h6>

                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="enableVirusScan"
                                                   name="enable_virus_scan"
                                                   value="1" {{ $settings['enable_virus_scan'] ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="enableVirusScan">
                                                Habilitar escaneo de virus
                                            </label>
                                        </div>
                                        <small class="text-muted">Escanea archivos en busca de virus antes de subirlos
                                            (requiere ClamAV)</small>
                                    </div>

                                    <div class="alert alert-info border-0 bg-info-subtle text-info mb-0">
                                        <div class="d-flex align-items-start gap-2">
                                            <div>
                                                <strong>Recomendaciones de seguridad:</strong><br>
                                                <small>
                                                    - Limita los tipos de archivos permitidos<br>
                                                    - Establece un tamaño máximo razonable<br>
                                                    - Habilita escaneo de virus si está disponible<br>
                                                    - Revisa periódicamente los archivos subidos
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->

                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary w-100 mb-1">Guardar configuración</button>
                    <a href="{{ route('settings.system.index') }}" class="btn btn-secondary w-100">Cancelar</a>
                </div>

            </div>

        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Show/Hide S3 Settings
                const storageDriver = document.getElementById('storageDriver');
                const s3Settings = document.getElementById('s3Settings');

                if (storageDriver && s3Settings) {
                    storageDriver.addEventListener('change', function () {
                        s3Settings.style.display = ['s3', 'spaces'].includes(this.value) ? 'block' : 'none';
                    });
                }

                // Convert KB to MB display
                const maxFileSize = document.getElementById('maxFileSize');
                if (maxFileSize) {
                    const updateMBDisplay = function () {
                        const kb = parseInt(this.value || 0);
                        const mb = (kb / 1024).toFixed(2);
                        const helpText = this.nextElementSibling;
                        if (helpText) {
                            helpText.textContent = `Tamaño máximo permitido por archivo (~${mb} MB)`;
                        }
                    };

                    maxFileSize.addEventListener('input', updateMBDisplay);
                    updateMBDisplay.call(maxFileSize);
                }
            });
        </script>
    @endpush

@endsection
