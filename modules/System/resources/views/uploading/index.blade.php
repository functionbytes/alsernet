@extends('layouts.theme')

@section('title', 'Configuración de carga de archivos')

@section('content')

    @include('core::components.card', ['title' => 'Configuración de carga de archivos'])

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">

            {{-- Columna izquierda: formulario --}}
            <div class="col-lg-8">
                <form method="POST" action="{{ route('settings.system.uploading.update') }}" id="uploading-form">
                    @csrf
                    @method('PUT')

                    <div class="card">

                        {{-- Límites de carga --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Limites de carga</h6>
                            <p class="text-muted mb-3">Define el tamaño maximo y la cantidad de archivos que se pueden subir simultaneamente.</p>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="maxFileSize" class="form-label fw-semibold">Tamaño maximo por archivo (KB) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="maxFileSize" name="max_file_size"
                                           value="{{ $settings['max_file_size'] }}" min="1" max="102400" required>
                                    <small class="text-muted d-block mt-1" id="maxFileSizeHelp">Tamaño maximo permitido por archivo (1 MB = 1024 KB)</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="maxFilesPerUpload" class="form-label fw-semibold">Archivos por carga <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="maxFilesPerUpload" name="max_files_per_upload"
                                           value="{{ $settings['max_files_per_upload'] }}" min="1" max="50" required>
                                    <small class="text-muted d-block mt-1">Cantidad maxima de archivos que se pueden subir a la vez</small>
                                </div>
                            </div>
                        </div>

                        <hr class="my-0">

                        {{-- Tipos de archivos permitidos --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Tipos de archivos permitidos</h6>
                            <p class="text-muted mb-3">Selecciona las extensiones permitidas para cada tipo de archivo. Limitar los tipos reduce riesgos de seguridad.</p>

                            @php
                                $generalTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar'];
                                $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico'];
                                $documentTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'odt', 'ods'];
                            @endphp

                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="allowedFileTypes" class="form-label fw-semibold">Archivos generales <span class="text-danger">*</span></label>
                                    <select class="form-select select2-tags" id="allowedFileTypes" name="allowed_file_types[]" multiple>
                                        @foreach($generalTypes as $type)
                                            <option value="{{ $type }}" {{ in_array($type, $settings['allowed_file_types']) ? 'selected' : '' }}>{{ strtoupper($type) }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-1">Extensiones permitidas para cualquier tipo de archivo</small>
                                </div>
                                <div class="col-12">
                                    <label for="allowedImageTypes" class="form-label fw-semibold">Solo imagenes <span class="text-danger">*</span></label>
                                    <select class="form-select select2-tags" id="allowedImageTypes" name="allowed_image_types[]" multiple>
                                        @foreach($imageTypes as $type)
                                            <option value="{{ $type }}" {{ in_array($type, $settings['allowed_image_types']) ? 'selected' : '' }}>{{ strtoupper($type) }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-1">Extensiones permitidas para subir imagenes</small>
                                </div>
                                <div class="col-12">
                                    <label for="allowedDocumentTypes" class="form-label fw-semibold">Solo documentos <span class="text-danger">*</span></label>
                                    <select class="form-select select2-tags" id="allowedDocumentTypes" name="allowed_document_types[]" multiple>
                                        @foreach($documentTypes as $type)
                                            <option value="{{ $type }}" {{ in_array($type, $settings['allowed_document_types']) ? 'selected' : '' }}>{{ strtoupper($type) }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-1">Extensiones permitidas para documentos</small>
                                </div>
                            </div>
                        </div>

                        <hr class="my-0">

                        {{-- Almacenamiento --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Almacenamiento</h6>
                            <p class="text-muted mb-3">Define donde se guardan los archivos subidos. Para servicios externos configura las credenciales en el archivo .env.</p>

                            <label for="storageDriver" class="form-label fw-semibold">Driver de almacenamiento <span class="text-danger">*</span></label>
                            <select class="form-select select2" id="storageDriver" name="storage_driver" required>
                                <option value="local" {{ $settings['storage_driver'] === 'local' ? 'selected' : '' }}>Local (Servidor)</option>
                                <option value="s3" {{ $settings['storage_driver'] === 's3' ? 'selected' : '' }}>Amazon S3</option>
                                <option value="spaces" {{ $settings['storage_driver'] === 'spaces' ? 'selected' : '' }}>DigitalOcean Spaces</option>
                                <option value="ftp" {{ $settings['storage_driver'] === 'ftp' ? 'selected' : '' }}>FTP</option>
                            </select>
                            <small class="text-muted d-block mt-1">Donde se almacenaran los archivos subidos al sistema</small>

                            <div id="s3Settings" class="mt-3 {{ in_array($settings['storage_driver'], ['s3', 'spaces']) ? '' : 'd-none' }}">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="s3Bucket" class="form-label fw-semibold">Bucket / Contenedor</label>
                                        <input type="text" class="form-control" id="s3Bucket" name="s3_bucket"
                                               value="{{ $settings['s3_bucket'] }}" placeholder="mi-bucket">
                                        <small class="text-muted d-block mt-1">Nombre del bucket de S3 o Spaces</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="s3Region" class="form-label fw-semibold">Region</label>
                                        <input type="text" class="form-control" id="s3Region" name="s3_region"
                                               value="{{ $settings['s3_region'] }}" placeholder="us-east-1">
                                        <small class="text-muted d-block mt-1">Region del servicio de almacenamiento</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-0">

                        {{-- Seguridad --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Seguridad</h6>
                            <p class="text-muted mb-3">Opciones adicionales para proteger el sistema contra archivos maliciosos.</p>

                            <label for="enableVirusScan" class="form-label fw-semibold">Escaneo de virus</label>
                            <select class="form-select select2" id="enableVirusScan" name="enable_virus_scan">
                                <option value="1" {{ $settings['enable_virus_scan'] ? 'selected' : '' }}>Habilitado</option>
                                <option value="0" {{ !$settings['enable_virus_scan'] ? 'selected' : '' }}>Deshabilitado</option>
                            </select>
                            <small class="text-muted d-block mt-1">Escanea archivos en busca de virus antes de almacenarlos. Requiere ClamAV instalado en el servidor.</small>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                Guardar configuración
                            </button>
                        </div>

                    </div>
                </form>
            </div>

            {{-- Columna derecha: sidebar --}}
            <div class="col-lg-4">

                <div class="card mb-3">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Limites del servidor</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Estos valores estan definidos en la configuracion PHP del servidor y no se pueden cambiar desde aqui.</p>
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Tamaño maximo de subida</span>
                                <strong>{{ $phpLimits['upload_max_filesize'] }}</strong>
                            </li>
                            <li class="d-flex justify-content-between mb-2">
                                <span class="text-muted">POST maximo</span>
                                <strong>{{ $phpLimits['post_max_size'] }}</strong>
                            </li>
                            <li class="d-flex justify-content-between">
                                <span class="text-muted">Archivos simultaneos</span>
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
                        <p class="text-muted mb-3">Limita las extensiones permitidas al minimo necesario. Evita permitir archivos ejecutables como <code>.exe</code>, <code>.sh</code> o <code>.php</code>.</p>

                        <hr class="my-3">

                        <h6 class="fw-semibold mb-2">Tamaño de archivos</h6>
                        <p class="text-muted mb-3">Establece un limite razonable segun el uso. Para imagenes web, 5 MB suele ser suficiente.</p>

                        <hr class="my-3">

                        <h6 class="fw-semibold mb-2">Escaneo de virus</h6>
                        <p class="text-muted mb-0">Si el servidor tiene ClamAV instalado, habilita el escaneo para detectar archivos maliciosos antes de almacenarlos.</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Almacenamiento externo</h6>
                    </div>
                    <div class="card-body">
                        <ol class="text-muted ps-3 mb-0">
                            <li class="mb-2">Selecciona el driver (S3, Spaces o FTP)</li>
                            <li class="mb-2">Configura las credenciales en el archivo <code>.env</code></li>
                            <li class="mb-2">Ingresa el bucket y la region</li>
                            <li>Verifica la conexion subiendo un archivo de prueba</li>
                        </ol>
                    </div>
                </div>

            </div>

        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });
    $('.select2-tags').select2({ width: '100%', tags: false });

    $('#storageDriver').on('change', function () {
        $('#s3Settings').toggleClass('d-none', !['s3', 'spaces'].includes($(this).val()));
    });

    $('#maxFileSize').on('input', function () {
        var kb = parseInt($(this).val() || 0);
        var mb = (kb / 1024).toFixed(2);
        $('#maxFileSizeHelp').text('Tamaño maximo permitido por archivo (~' + mb + ' MB)');
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
