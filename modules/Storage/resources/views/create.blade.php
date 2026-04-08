@extends('layouts.theme')

@section('page_title', 'Agregar disco de almacenamiento')

@section('content')

    @include('core::components.card', ['title' => 'Agregar disco de almacenamiento'])

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">

            {{-- Columna izquierda --}}
            <div class="col-lg-8">

                <form method="POST" action="{{ route('settings.storage.store') }}" id="formCreate">
                    @csrf

                    <div class="card">

                        {{-- Configuración básica --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Configuración básica</h6>
                            <p class="text-muted mb-3">Define el nombre y tipo del disco de almacenamiento.</p>

                            <div class="row g-3">
                                <div class="col-sm-12">
                                    <label class="form-label fw-semibold">
                                        Nombre del disco <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           id="name" name="name" value="{{ old('name') }}"
                                           placeholder="Ej: network_shared" required>
                                    <small class="text-muted d-block mt-1">Sin espacios, solo letras, números y guiones bajos</small>
                                    @error('name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-sm-12">
                                    <label class="form-label fw-semibold">
                                        Tipo de almacenamiento <span class="text-danger">*</span>
                                    </label>
                                    <select class="select2 form-select @error('driver') is-invalid @enderror"
                                            id="driver" name="driver" required>
                                        <option value="">Selecciona un tipo</option>
                                        @foreach($driverOptions as $driverKey => $driverLabel)
                                            <option value="{{ $driverKey }}" {{ old('driver') == $driverKey ? 'selected' : '' }}>
                                                {{ $driverLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('driver')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-0">

                        {{-- Configuración específica --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Configuración específica</h6>
                            <p class="text-muted mb-3">Parámetros de conexión según el tipo de almacenamiento seleccionado.</p>

                            {{-- Local Driver Fields --}}
                            <div id="localFields" class="driver-fields" style="display: none;">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">
                                            Ubicación del almacenamiento <span class="text-danger">*</span>
                                        </label>
                                        <select class="select2 form-select @error('storage_type') is-invalid @enderror" name="storage_type">
                                            <option value="public" {{ old('storage_type', 'public') === 'public' ? 'selected' : '' }}>
                                                Público — Accesible desde el navegador
                                            </option>
                                            <option value="private" {{ old('storage_type') === 'private' ? 'selected' : '' }}>
                                                Privado — Solo accesible por la aplicación
                                            </option>
                                        </select>
                                        @error('storage_type')
                                            <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <div class="alert alert-info mb-0">
                                            <strong>¿Dónde se guardarán los archivos?</strong>
                                            <ul class="mb-0 mt-2">
                                                <li><strong>Público:</strong> <code id="previewPublic">public/storage/{{ old('name', '[nombre]') }}</code></li>
                                                <li><strong>Privado:</strong> <code id="previewPrivate">storage/{{ old('name', '[nombre]') }}</code></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- FTP Driver Fields --}}
                            <div id="ftpFields" class="driver-fields row g-3" style="display: none;">
                                @include('storage::partials.remote-fields', ['driver' => 'ftp'])
                            </div>

                            {{-- SFTP Driver Fields --}}
                            <div id="sftpFields" class="driver-fields row g-3" style="display: none;">
                                @include('storage::partials.remote-fields', ['driver' => 'sftp'])
                            </div>

                            {{-- S3 Driver Fields --}}
                            <div id="s3Fields" class="driver-fields" style="display: none;">
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="form-label fw-semibold">Bucket <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('bucket') is-invalid @enderror"
                                               name="bucket" value="{{ old('bucket') }}"
                                               placeholder="mi-bucket">
                                        @error('bucket')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-sm-6">
                                        <label class="form-label fw-semibold">Región <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('region') is-invalid @enderror"
                                               name="region" value="{{ old('region') }}"
                                               placeholder="us-east-1">
                                        <small class="text-muted d-block mt-1">Región de AWS donde está el bucket</small>
                                        @error('region')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-sm-6">
                                        <label class="form-label fw-semibold">Access Key ID <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control @error('key') is-invalid @enderror"
                                               name="key" placeholder="Access Key ID de AWS"
                                               data-create-mode="1">
                                        @error('key')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-sm-6">
                                        <label class="form-label fw-semibold">Secret Access Key <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control @error('secret') is-invalid @enderror"
                                               name="secret" value="{{ old('secret') }}"
                                               data-create-mode="1"
                                               placeholder="Secret key de AWS">
                                        @error('secret')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                Guardar disco
                            </button>
                        </div>

                    </div>

                </form>

            </div>

            {{-- Columna derecha --}}
            <div class="col-lg-4">

                <div class="card mb-3">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Tipos disponibles</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Local</span>
                            <span class="badge bg-light text-dark border">Carpeta del servidor</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Amazon S3</span>
                            <span class="badge bg-light text-dark border">Nube AWS</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">FTP</span>
                            <span class="badge bg-light text-dark border">Servidor remoto</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">SFTP</span>
                            <span class="badge bg-light text-dark border">Servidor seguro</span>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Cancelar</h6>
                        <p class="text-muted mb-3">Descartar y volver al listado de almacenamientos.</p>
                        <a href="{{ route('settings.storage') }}" class="btn btn-secondary w-100">
                            Volver a almacenamiento
                        </a>
                    </div>
                </div>

            </div>

        </div>

    </div>

@endsection

@push('scripts')
    @include('storage::partials.driver-toggle')
    <script>
    $(document).ready(function () {
        function updatePathPreview() {
            const name = $('#name').val().trim() || '[nombre]';
            $('#previewPublic').text('public/storage/' + name);
            $('#previewPrivate').text('storage/' + name);
        }

        $('#name').on('input', updatePathPreview);

        @if (session('success'))
            toastr.success(@json(session('success')), 'Éxito');
        @endif
        @if (session('error'))
            toastr.error(@json(session('error')), 'Error');
        @endif
    });
    </script>
@endpush
