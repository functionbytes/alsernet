@extends('layouts.theme')

@section('title', 'Editar disco de almacenamiento')

@section('content')

    <div class="card">

        <form method="POST" action="{{ route('manager.backups.storage.update-disk', $diskIndex) }}" id="formEdit">
            @csrf
            @method('PATCH')

            <div class="card-header border-bottom p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Editar disco de almacenamiento</h5>
                        <p class="mb-0 text-muted small">Modifica la configuración del disco <strong
                                    class="text-primary">{{ $disk['name'] }}</strong></p>
                    </div>
                </div>
            </div>

            <div class="card-body">
                @include('core::components.alerts')

                <div class="row">

                    {{-- Disk Information Section --}}
                    <div class="col-12">
                        <h6 class="fw-bold mb-0">
                            Información del disco
                        </h6>
                        <p class="text-muted mb-4">
                            @if($isFromConfig)
                                Este disco está definido en <code class="text-warning">config/filesystems.php</code>.
                                Puedes crear una copia editable en la base de datos.
                            @else
                                Este disco está almacenado en la base de datos y es completamente editable.
                            @endif
                        </p>
                    </div>

                    @if($isFromConfig)
                        <div class="col-12">
                            <div class="alert bg-light border-0 mb-3">
                                <h6 class="mb-1 fw-semibold"><i class="fas fa-exclamation-triangle me-2"></i>Disco del
                                    archivo de configuración</h6>
                                <p class="mb-0">Los cambios crearán una copia en la base de datos que sobrescribirá la
                                    configuración del archivo.</p>
                            </div>
                        </div>
                    @endif

                    {{-- Disk Type Badge --}}
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Origen</label>
                            <div>
                                @if($isFromConfig)
                                    <span class="badge bg-warning-subtle text-warning rounded-pill py-2 px-3">
                                        Configuración (config/filesystems.php)
                                    </span>
                                @else
                                    <span class="badge bg-success-subtle text-success rounded-pill py-2 px-3">
                                        Base de datos
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Basic Information Section --}}
                    <div class="col-12">
                        <h6 class="fw-bold mb-0 mt-4">
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
                                   id="name" name="name" value="{{ old('name', $disk['name']) }}"
                                   placeholder="Ej: network_shared" required>
                            <small class="form-text text-muted">Sin espacios, solo letras, números y guiones
                                bajos</small>
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
                                    <option value="{{ $driverKey }}" {{ old('driver', $disk['driver']) == $driverKey ? 'selected' : '' }}>
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
                        <p class="text-muted mb-4">Configuración específica para el tipo de almacenamiento
                            seleccionado.</p>
                    </div>

                    {{-- Local Driver Fields --}}
                    <div id="localFields" class="driver-fields" style="display: none;">
                        <div class="col-12 col-md-12">
                            <div class="mb-3">
                                <label class="control-label col-form-label">
                                    Ruta raíz <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('root') is-invalid @enderror"
                                       name="root" value="{{ old('root', $disk['root'] ?? '') }}"
                                       placeholder="/mnt/red_compartida">
                                <small class="form-text text-muted">Ruta absoluta en el servidor</small>
                                @error('root')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-12">
                            <div class="mb-3">
                                <label class="control-label col-form-label">URL (opcional)</label>
                                <input type="text" class="form-control @error('url') is-invalid @enderror"
                                       name="url" value="{{ old('url', $disk['url'] ?? '') }}"
                                       placeholder="http://localhost/storage">
                                <small class="form-text text-muted">URL pública para acceder a los archivos</small>
                                @error('url')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
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
                                       name="host" value="{{ old('host', $disk['host'] ?? '') }}"
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
                                       name="port" value="{{ old('port', $disk['port'] ?? '21') }}"
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
                                       name="username" value="{{ old('username', $disk['username'] ?? '') }}">
                                @error('username')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Contraseña</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                       name="password" value="{{ old('password', $disk['password'] ?? '') }}"
                                       placeholder="Dejar en blanco para mantener la actual">
                                <small class="form-text text-muted">Dejar vacío para mantener la contraseña
                                    actual</small>
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
                                       name="host" value="{{ old('host', $disk['host'] ?? '') }}"
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
                                       name="port" value="{{ old('port', $disk['port'] ?? '22') }}"
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
                                       name="username" value="{{ old('username', $disk['username'] ?? '') }}">
                                @error('username')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Contraseña</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                       name="password" value="{{ old('password', $disk['password'] ?? '') }}"
                                       placeholder="Dejar en blanco para mantener la actual">
                                <small class="form-text text-muted">Dejar vacío para mantener la contraseña
                                    actual</small>
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
                                       name="bucket" value="{{ old('bucket', $disk['bucket'] ?? '') }}"
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
                                       name="region" value="{{ old('region', $disk['region'] ?? '') }}"
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
                                       name="key" value="{{ old('key', $disk['key'] ?? '') }}">
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
                                       name="secret" value="{{ old('secret', $disk['secret'] ?? '') }}"
                                       placeholder="Dejar en blanco para mantener la actual">
                                <small class="form-text text-muted">Dejar vacío para mantener el secret actual</small>
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
                <a href="{{ route('manager.backups.storage') }}" class="btn btn-secondary w-100">
                    Cancelar
                </a>
                @if(!$isFromConfig)
                    <button type="button" class="btn btn-primary w-100" id="deleteBtn">
                        Eliminar
                    </button>
                @endif
            </div>

        </form>

    </div>

    {{-- Delete Form --}}
    @if(!$isFromConfig)
        <form id="deleteDiskForm" method="POST" action="{{ route('manager.backups.storage.destroy') }}"
              style="display: none;">
            @csrf
            @method('DELETE')
            <input type="hidden" name="disk_name" value="{{ $disk['name'] }}">
        </form>
    @endif

@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            // Show driver-specific fields based on current driver
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

            // Delete button
            $('#deleteBtn').on('click', function () {
                if (confirm('¿Estás seguro de que deseas eliminar este disco?\n\nEsta acción no se puede deshacer.')) {
                    $('#deleteDiskForm').submit();
                }
            });

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
