@extends('layouts.theme')

@section('title', 'Agregar disco de almacenamiento')

@section('content')

    <div class="card">

        <form method="POST" action="{{ route('settings.storage.store') }}" id="formCreate">
            @csrf

            <div class="card-header border-bottom p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Agregar disco de almacenamiento</h5>
                        <p class="mb-0 text-muted small">Configura un nuevo disco de almacenamiento personalizado</p>
                    </div>
                </div>
            </div>

            <div class="card-body">

                @include('core::components.alerts')


                <div class="row">

                    {{-- Basic Information Section --}}
                    <div class="col-12">
                        <h6 class="fw-bold mb-0">
                            Configuración básica
                        </h6>
                        <p class="text-muted mb-4">Define el nombre y tipo del disco de almacenamiento.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre del disco <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}"
                                   placeholder="Ej: network_shared" required>
                            <small class="form-text text-muted">Sin espacios, solo letras, números y guiones bajos</small>
                            @error('name')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
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
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Driver-specific Configuration --}}
                    <div class="col-12">
                        <h6 class="fw-bold mb-0 mt-4">
                            Configuración específica
                        </h6>
                        <p class="text-muted mb-4">Configuración específica para el tipo de almacenamiento seleccionado.</p>
                    </div>

                    {{-- Local Driver Fields (Simplified) --}}
                    <div id="localFields" class="driver-fields" style="display: none;">
                        <div class="col-12">
                            <div class="mb-4">
                                <label class="control-label col-form-label mb-3">
                                    Ubicación del almacenamiento <span class="text-danger">*</span>
                                </label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="storage_type" id="storagePublic"
                                               value="public" {{ old('storage_type') === 'public' || !old('storage_type') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="storagePublic">
                                            <strong>Público</strong>
                                            <div class="text-muted small">Accesible desde el navegador (web-accessible)</div>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="storage_type" id="storagePrivate"
                                               value="private" {{ old('storage_type') === 'private' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="storagePrivate">
                                            <strong>Privado</strong>
                                            <div class="text-muted small">Solo accesible por la aplicación</div>
                                        </label>
                                    </div>
                                </div>
                                @error('storage_type')
                                    <span class="field-validation-error d-block mt-2"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Information Box --}}
                        <div class="col-12">
                            <div class="alert alert-info" role="alert">
                                <div class="d-flex">
                                    <div>
                                        <strong>¿Dónde se guardarán los archivos?</strong>
                                        <ul class="mb-0 mt-2">
                                            <li><strong>Público:</strong> <code>public/storage/{{ old('name') || 'nombre' }}</code></li>
                                            <li><strong>Privado:</strong> <code>storage/{{ old('name') || 'nombre' }}</code></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- FTP Driver Fields --}}
                    <div id="ftpFields" class="driver-fields row" style="display: none;">
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">
                                    Host <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('host') is-invalid @enderror"
                                       name="host" value="{{ old('host') }}"
                                       placeholder="ftp.ejemplo.com">
                                @error('host')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Puerto</label>
                                <input type="number" class="form-control @error('port') is-invalid @enderror"
                                       name="port" value="{{ old('port', '21') }}"
                                       placeholder="21">
                                @error('port')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">
                                    Usuario <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('username') is-invalid @enderror"
                                       name="username" value="{{ old('username') }}">
                                @error('username')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Contraseña</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                       name="password" value="{{ old('password') }}"
                                       placeholder="Contraseña FTP">
                                @error('password')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- SFTP Driver Fields --}}
                    <div id="sftpFields" class="driver-fields row" style="display: none;">
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">
                                    Host <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('host') is-invalid @enderror"
                                       name="host" value="{{ old('host') }}"
                                       placeholder="sftp.ejemplo.com">
                                @error('host')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Puerto</label>
                                <input type="number" class="form-control @error('port') is-invalid @enderror"
                                       name="port" value="{{ old('port', '22') }}"
                                       placeholder="22">
                                @error('port')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">
                                    Usuario <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('username') is-invalid @enderror"
                                       name="username" value="{{ old('username') }}">
                                @error('username')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Contraseña</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                       name="password" value="{{ old('password') }}"
                                       placeholder="Contraseña SFTP">
                                @error('password')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- S3 Driver Fields --}}
                    <div id="s3Fields" class="driver-fields" style="display: none;">
                        <div class="col-12 col-md-12">
                            <div class="mb-3">
                                <label class="control-label col-form-label">
                                    Bucket <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('bucket') is-invalid @enderror"
                                       name="bucket" value="{{ old('bucket') }}"
                                       placeholder="mi-bucket">
                                @error('bucket')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-12">
                            <div class="mb-3">
                                <label class="control-label col-form-label">
                                    Región <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('region') is-invalid @enderror"
                                       name="region" value="{{ old('region') }}"
                                       placeholder="us-east-1">
                                <small class="form-text text-muted">Región de AWS donde está el bucket</small>
                                @error('region')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-12">
                            <div class="mb-3">
                                <label class="control-label col-form-label">
                                    Access Key ID <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('key') is-invalid @enderror"
                                       name="key" value="{{ old('key') }}">
                                @error('key')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">
                                    Secret Access Key <span class="text-danger">*</span>
                                </label>
                                <input type="password" class="form-control @error('secret') is-invalid @enderror"
                                       name="secret" value="{{ old('secret') }}"
                                       placeholder="Secret key de AWS">
                                @error('secret')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                </div>

            </div>

            <div class="card-footer border-top">

                    <button type="submit" class="btn btn-primary w-100 mb-1">
                        Guardar
                    </button>
                    <a href="{{ route('settings.storage') }}" class="btn btn-secondary w-100">
                        Cancelar
                    </a>
            </div>

        </form>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Show driver-specific fields based on driver selection
    function toggleDriverFields() {
        const selectedDriver = $('#driver').val();

        // Hide all driver fields
        $('.driver-fields').hide();

        // Show fields for selected driver
        if (selectedDriver === 'local') {
            $('#localFields').show();
        } else if (selectedDriver === 'ftp') {
            $('#ftpFields').show();
        } else if (selectedDriver === 'sftp') {
            $('#sftpFields').show();
        } else if (selectedDriver === 's3') {
            $('#s3Fields').show();
        }
    }

    // Initialize on page load
    toggleDriverFields();

    // Toggle on driver change
    $('#driver').on('change', toggleDriverFields);

    // Show toastr notifications
    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
